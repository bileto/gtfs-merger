<?php

namespace GtfsMerger\Command;

use GtfsMerger\Cache\Cleaner;
use GtfsMerger\Merger\Agency;
use GtfsMerger\Merger\Calendar;
use GtfsMerger\Merger\CalendarDates;
use GtfsMerger\Merger\Routes;
use GtfsMerger\Merger\Stops;
use GtfsMerger\Merger\StopTimes;
use GtfsMerger\Merger\Trips;
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
        if (!count($files)){
            throw new InvalidArgumentException('No input files specified. See gtfs-merger merge --help for details.');
        }
        $progress = $this->getMergerProgressBar($output);
        $progress->start(count($files));

        foreach ($files as $file) {
            $progress->advance();
            $progress->setMessage(ltrim(strrchr($file, '/'), '/'), 'file');

            $this->processGtfs($file, $progress);

            $this->cacheCleaner->clean();
        }
        $progress->finish();
        $progress->clear();
        $progress = $this->getWriterProgressBar($output);
        $this->createGtfs($input->getOption('output'), $progress);
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
        ];

        if (!file_exists($file)) {
            throw new InvalidArgumentException('Cannot read file/s in path "' . $file . '". Aborting.');
        }
        $zip = new ZipArchiveAdapter($file);

        foreach ($mergers as $merger => $subfile) {
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
     * @param ProgressBar $progressBar
     */
    private function createGtfs($filename = null, ProgressBar $progressBar)
    {
        if ($filename === null) {
            $filename = 'merged_gtfs_' . date('Y_m_d_His') . '.zip';
        }
        $this->gtfsWriter->save($filename, $progressBar);
        $this->gtfsWriter->clean();
    }

    /**
     * @param OutputInterface $output
     * @return ProgressBar
     */
    private function getMergerProgressBar(OutputInterface $output)
    {
        $output->writeln(''); // do not override command line

        $progress = new ProgressBar($output);
        $progress->setProgressCharacter('<fg=green>▒</>');
        $progress->setBarCharacter('<fg=green>▒</>');
        $progress->setEmptyBarCharacter(' ');
        $progress->setBarWidth(60);
        $progress->setFormat(
             str_pad('', 80, '-') . "\n\n"
            . " <comment>Merging GTFS ...</comment> \n\n"
            . " %current%/%max% [%bar%] <fg=green>%percent:3s%%</> \n\n"
            . " Processing %file%/%gtfs_part% \n"
            . " Elapsed: %elapsed:6s%, ~%estimated:-6s% remaining\n"
            . " Memory usage: %memory:6s% \n"
            . str_pad('', 80, '-')
        );
        $progress->setMessage('', 'file');
        $progress->setMessage('', 'gtfs_part');
        // TODO: total stops, dates...
        return $progress;
    }

    /**
     * @param OutputInterface $output
     * @return ProgressBar
     */
    private function getWriterProgressBar(OutputInterface $output)
    {
        $output->writeln(''); // do not override command line

        $progress = new ProgressBar($output);
        $progress->setProgressCharacter('<fg=red>▒</>');
        $progress->setBarCharacter('<fg=red>▒</>');
        $progress->setEmptyBarCharacter(' ');
        $progress->setBarWidth(60);
        $progress->setFormat(
            str_pad('', 80, '-') . "\n\n"
            . " <comment>Writing output GTFS ...</comment> \n\n"
            . " %current%/%max% [%bar%] <fg=green>%percent:3s%%</> \n\n"
            . " Target: %file% \n"
            . " Elapsed: %elapsed:6s%, ~%estimated:-6s% remaining\n"
            . str_pad('', 80, '-')
        );
        $progress->setMessage('', 'file');
        return $progress;
    }
}