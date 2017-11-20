<?php
use PHPUnit\Framework\TestCase;


class MergeTest extends TestCase
{
    public function testGtfsMerging()
    {
        $cache_dir = "/tmp/phpunit";
        try {
            if (!is_dir($cache_dir))
                mkdir($cache_dir);
        }
        catch (Exception $e) {
            echo "File '$cache_dir' exists. Please remove it first";
            die;
        }

        $input_a = __DIR__ . "/assets/inputA.zip";
        $input_b = __DIR__ . "/assets/inputB.zip";
        $test_output = $cache_dir . "/output.zip";

        shell_exec("php src/index.php merge $input_a $input_b --output $test_output");

        $this->assertFileExists($test_output);

        $zip = zip_open($test_output);

        if ($zip) {
            $count_files_inside_gtfs_zip = 0;
            while ($zip_entry = zip_read($zip)) {
                $count_files_inside_gtfs_zip += 1;
                $name = zip_entry_name($zip_entry);

                if ($name == 'feed_info.txt') {
                    if (zip_entry_open($zip, $zip_entry)) {
                        $feed_info_file_content = zip_entry_read($zip_entry);
                        zip_entry_close($zip_entry);
                    }
                }
                if ($name == 'trips.txt') {
                    if (zip_entry_open($zip, $zip_entry)) {
                        $trips_file_content = zip_entry_read($zip_entry);
                        zip_entry_close($zip_entry);
                    }
                }
                if ($name == 'stop_time_limitations.txt') {
                    if (zip_entry_open($zip, $zip_entry)) {
                        $stop_time_lim_file_content = zip_entry_read($zip_entry);
                        zip_entry_close($zip_entry);
                    }
                }
            }
        }
        zip_close($zip);
        echo $test_output;
        unlink($test_output);
        rmdir($cache_dir);

        $this->assertEquals(10, $count_files_inside_gtfs_zip);

        // Testing stop_time_limitations trip_id
        $trips_content = explode(PHP_EOL, $trips_file_content);
        $stop_time_lim_content = explode(PHP_EOL, $stop_time_lim_file_content);

        $expected_trip_id_A = explode(',', $trips_content[1])[2];
        $expected_trip_id_B = explode(',', $trips_content[2])[2];
        $actual_trip_id_A = explode(',', $stop_time_lim_content[1])[0];
        $actual_trip_id_B = explode(',', $stop_time_lim_content[3])[0];

        $this->assertSame($expected_trip_id_A, $actual_trip_id_A);
        $this->assertSame($expected_trip_id_B, $actual_trip_id_B);

        // Testing feed_info content
        $expected_feed_info_content = ["feed_publisher_name,feed_publisher_url,feed_lang,script_version",
                                        "Bileto,https://bileto.com,cs,0.2.1", ""];
        $actual_feed_info_content = explode(PHP_EOL, $feed_info_file_content);

        $this->assertSame($expected_feed_info_content, $actual_feed_info_content);

    }
}
