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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Merge extends Command
{
    /** @var Agency */
    private $agencyMerger;

    /** @var Routes */
    private $routeMerger;

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
        Routes $routeMerger,
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
        $this->routeMerger = $routeMerger;
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
        foreach ($files as $file) {
            dump($file);
            $agencies = $this->agencyMerger->merge($file);
            $routes = $this->routeMerger->merge($file);
            $stops = $this->stopsMerger->merge($file);
            $calendar = $this->calendarMerger->merge($file);
            $calendarDates = $this->calendarDatesMerger->merge($file);
            $trips = $this->tripsMerger->merge($file);
            $stopTimes = $this->stopTimesMerger->merge($file);

            $this->gtfsWriter->append('agency.txt', $agencies);
            $this->gtfsWriter->append('routes.txt', $routes);
            $this->gtfsWriter->append('stops.txt', $stops);
            $this->gtfsWriter->append('calendar.txt', $calendar);
            $this->gtfsWriter->append('calendar_dates.txt', $calendarDates);
            $this->gtfsWriter->append('trips.txt', $trips);
            $this->gtfsWriter->append('stop_times.txt', $stopTimes);
            $this->cacheCleaner->clean();
            dump(\PHP_Timer::resourceUsage());
        }
        $this->createGtfs();

        dump(\PHP_Timer::resourceUsage());
    }

    private function createGtfs($filename = null)
    {
        if ($filename === null) {
            $filename = 'merged_gtfs_' . date('Y_m_d_His') . '.zip';
        }
        $this->gtfsWriter->save($filename);
        $this->gtfsWriter->clean();
    }
}