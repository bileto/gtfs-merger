<?php

namespace GtfsMerger\Command;

use GtfsMerger\Cache\Cleaner;
use GtfsMerger\Merger\Agency;
use GtfsMerger\Merger\Calendar;
use GtfsMerger\Merger\CalendarDates;
use GtfsMerger\Merger\Routes;
use GtfsMerger\Merger\Stops;
use GtfsMerger\Merger\StopTimeLimitations;
use GtfsMerger\Merger\StopTimes;
use GtfsMerger\Merger\Trips;
use GtfsMerger\Merger\ExternalIDs;
use GtfsMerger\Merger\FeedInfo;
use GtfsMerger\Output\GtfsWriter;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Nette\InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Merge
 *
 * @package GtfsMerger\Command
 * @author Michal Sänger
 */
class Merge extends Command
{
    /** @var Agency */
    private $agencyMerger;

    /** @var Routes */
    private $routesMerger;

    /** @var Stops */
    private $stopsMerger;

    /** @var Calendar */
    private $calendarMerger;

    /** @var CalendarDates */
    private $calendarDatesMerger;

    /** @var Trips */
    private $tripsMerger;

    /** @var StopTimes */
    private $stopTimesMerger;

    /** @var ExternalIDs */
    private $externalIDsMerger;

    /** @var StopTimeLimitations */
    private $stopTimeLimitationsMerger;

    /** @var FeedInfo */
    private $feedInfoMerger;
    private $feed_info_already_seen;

    /** @var Cleaner */
    private $cacheCleaner;

    /** @var GtfsWriter */
    private $gtfsWriter;

    /**
     * Merge constructor.
     * @param Agency $agencyMerger
     * @param Routes $routesMerger
     * @param Stops $stopsMerger
     * @param Calendar $calendarMerger
     * @param CalendarDates $calendarDatesMerger
     * @param Trips $tripsMerger
     * @param StopTimes $stopTimesMerger
     * @param ExternalIDs $externalIDsMerger
     * @param StopTimeLimitations $stopTimeLimitationsMerger
     * @param FeedInfo $feedInfoMerger
     * @param Cleaner $cleaner
     * @param GtfsWriter $gtfsWriter
     */
    function __construct(
        Agency $agencyMerger,
        Routes $routesMerger,
        Stops $stopsMerger,
        Calendar $calendarMerger,
        CalendarDates $calendarDatesMerger,
        Trips $tripsMerger,
        StopTimes $stopTimesMerger,
        ExternalIDs $externalIDsMerger,
        StopTimeLimitations $stopTimeLimitationsMerger,
        FeedInfo $feedInfoMerger,
        Cleaner $cleaner,
        GtfsWriter $gtfsWriter
    ) {
        $this->agencyMerger = $agencyMerger;
        $this->routesMerger = $routesMerger;
        $this->stopsMerger = $stopsMerger;
        $this->calendarMerger = $calendarMerger;
        $this->calendarDatesMerger = $calendarDatesMerger;
        $this->tripsMerger = $tripsMerger;
        $this->stopTimesMerger = $stopTimesMerger;
        $this->externalIDsMerger = $externalIDsMerger;
        $this->stopTimeLimitationsMerger = $stopTimeLimitationsMerger;
        $this->feedInfoMerger = $feedInfoMerger;
        $this->gtfsWriter = $gtfsWriter;
        $this->cacheCleaner = $cleaner;

        parent::__construct();
    }

    /**
     *
     */
    protected function configure()
    {
        ini_set('memory_limit', -1);
        date_default_timezone_set('UTC'); // TODO: should be removed, it's set in config.neon, but container is ignoring yet

        $this->setName('merge')
            ->setDescription('Merge given GTFS files into one')
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Specify output filename')
            ->addArgument('files', InputArgument::IS_ARRAY, 'Path to GTFS files. Wildcards are accepted.', [])

        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $files = $input->getArgument('files');
        $progress = $this->getProgressBar($output);
        $progress->start(count($files));

        $this->feed_info_already_seen = false;

        foreach ($files as $file) {
            $progress->advance();
            $progress->setMessage($file, 'file');

            $this->processGtfs($file, $progress);

            $this->cacheCleaner->clean();
        }
        $this->createGtfs($input->getOption('output'), $output);
        $progress->finish();
    }

    /**
     * @param $file
     * @param ProgressBar $progress
     */
    private function processGtfs($file, ProgressBar $progress)
    {
        $mergers = [
            'agencyMerger' => 'agency.txt',
            'routesMerger' => 'routes.txt',
            'stopsMerger' => 'stops.txt',
            'calendarMerger' => 'calendar.txt',
            'calendarDatesMerger' => 'calendar_dates.txt',
            'tripsMerger' => 'trips.txt',
            'stopTimesMerger' => 'stop_times.txt',
            'externalIDsMerger' => 'stop_external_ids.txt',
            'stopTimeLimitationsMerger' => 'stop_time_limitations.txt',
            'feedInfoMerger' => 'feed_info.txt'
        ];

        if (!is_readable($file)) {
            throw new InvalidArgumentException('Cannot read file/s in path "' . $file . '". Aborting.');
        }
        $zip = new ZipArchiveAdapter($file);

        foreach ($mergers as $merger => $subfile) {
            if ($subfile === 'feed_info.txt') {
                if ($this->feed_info_already_seen) {
                    continue;
                }
                $this->feed_info_already_seen = true;
            }
            $progress->setMessage($subfile, 'gtfs_part');
            $progress->display();

            $resource = $zip->readStream($subfile);

            if ($resource === false || !is_resource($resource['stream'])) {
                throw new InvalidArgumentException('Cannot find or read GTFS part "' . $subfile . '" in file "' . $file . '". Aborting.');
            }

            $items = $this->{$merger}->merge($resource['stream']);
            $this->gtfsWriter->append($subfile, $items);
        }
    }

    /**
     * @param null $filename
     * @param OutputInterface $output
     */
    private function createGtfs($filename = null, OutputInterface $output)
    {
        if ($filename === null) {
            $filename = 'merged_gtfs_' . date('Y_m_d_His') . '.zip';
        }
        $this->gtfsWriter->save($filename);
        $output->writeln('Writing to '.$filename.'...');
        $this->gtfsWriter->clean();
    }

    /**
     * @param OutputInterface $output
     * @return ProgressBar
     */
    private function getProgressBar(OutputInterface $output)
    {
        $output->writeln(''); // do not override command line

        $progress = new ProgressBar($output);
        $progress->setFormat('%file%: %gtfs_part%' . "\n"
            . '%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%'
        );
        $progress->setMessage('', 'file');
        $progress->setMessage('', 'gtfs_part');
        // TODO: add memory usage, total stops, dates...
        return $progress;
    }
}