<?php

namespace GtfsMerger\Output;

class CsvStdOutPrinter
{
    /**
     * @param array $items
     */
    public function dump(array $items)
    {
        if (count($items) === 0) {
            return;
        }
        $output = fopen("php://stdout", 'w');
        $header = array_keys(reset($items));
        fputcsv($output, $header);
        foreach ($items as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
    }
}