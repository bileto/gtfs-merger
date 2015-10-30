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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
    )
    {
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

    protected function configure()
    {
        ini_set('memory_limit', -1);
        date_default_timezone_set('UTC'); // TODO: should be removed, it's set in config.neon, but container is ignoring yet

        $this->setName('merge')
            ->setDescription('Merge given GTFS files into one')
            ->addArgument('files', InputArgument::IS_ARRAY, 'Input GTFS files', []);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $files = $input->getArgument('files');
        $progress = $this->getProgressBar($output);
        $progress->start(count($files));

        foreach ($files as $file) {
            $progress->advance();
            $progress->setMessage($file, 'file');

            $this->processGtfs($file, $progress);

            $this->cacheCleaner->clean();
        }
        $this->createGtfs();
        $progress->finish();
    }

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

        foreach ($mergers as $merger => $subfile) {
            $progress->setMessage($subfile, 'gtfs_part');
            $progress->display();
            $items = $this->{$merger}->merge($file);
            $this->gtfsWriter->append($subfile, $items);
        }
    }

    private function createGtfs($filename = null)
    {
        if ($filename === null) {
            $filename = 'merged_gtfs_' . date('Y_m_d_His') . '.zip';
        }
        $this->gtfsWriter->save($filename);
        $this->gtfsWriter->clean();
    }

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