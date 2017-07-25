<?php
use PHPUnit\Framework\TestCase;


class MergeTest extends TestCase
{
    public function testGtfsMerging()
    {
        $cache_dir = __DIR__ . "/.cache";
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
        $test_output = __DIR__ . "/.cache/output.zip";

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
                        $contents = zip_entry_read($zip_entry);
                        zip_entry_close($zip_entry);
                    }
                }
            }
        }
        zip_close($zip);
        unlink($test_output);
        rmdir($cache_dir);

        $this->assertEquals(9, $count_files_inside_gtfs_zip);

        $expected_feed_info_content = ["feed_publisher_name,feed_publisher_url,feed_lang,script_version",
                                        "Bileto,https://bileto.com,cs,0.2.1", ""];
        $actual_feed_info_content = explode(PHP_EOL, $contents);

        $this->assertSame($expected_feed_info_content, $actual_feed_info_content);

    }
}
?>

