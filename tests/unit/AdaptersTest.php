<?php

namespace GeoPHP\Tests;

use \GeoPHP\GeoPHP;
use PHPUnit\Framework\TestCase;

class AdaptersTest extends TestCase
{

    /**
     *
     * @return void
     */
    public function testAdapters()
    {
        foreach (scandir('tests/input') as $file) {
            $parts = explode('.', $file);
            if ($parts[0]) {
                $format = $parts[1];
                $input = file_get_contents('tests/input/' . $file);
                //echo "\nloading: " . $file . " for format: " . $format;
                $geometry = GeoPHP::load($input, $format);

                // Test adapter output and input. Do a round-trip and re-test
                foreach (GeoPHP::getAdapterMap() as $adapter_key => $adapter_class) {
                    if ($adapter_key === 'google_geocode') {
                        //Don't test google geocoder regularly. Comment to test
                        continue;
                    }
                    $output = $geometry->out($adapter_key);
                    $this->assertNotNull($output, "Empty output on " . $adapter_key);
                    if ($output) {
                        $adapter_name = 'GeoPHP\\Adapter\\' . $adapter_class;
                        /** @var \GeoPHP\Adapter\GeoAdapter $adapter_loader */
                        $adapter_loader = new $adapter_name();
                        $test_geom_1 = $adapter_loader->read($output);
                        $test_geom_2 = $adapter_loader->read($test_geom_1->out($adapter_key));
                        $this->assertEquals($test_geom_1->out('wkt'), $test_geom_2->out('wkt'), "Mismatched adapter output in " . $adapter_class . ' (test file: ' . $file . ')');
                    }
                }

                // Test to make sure adapter work the same wether GEOS is ON or OFF
                // Cannot test methods if GEOS is not intstalled
                if (!GeoPHP::geosInstalled()) {
                    return;
                }

                foreach (GeoPHP::getAdapterMap() as $adapter_key => $adapter_class) {
                    if ($adapter_key === 'google_geocode') {
                        //Don't test google geocoder regularily. Comment to test
                        continue;
                    }
                    // Turn GEOS on
                    GeoPHP::geosInstalled(true);

                    $output = $geometry->out($adapter_key);
                    if ($output) {
                        $adapter_name = 'GeoPHP\\Adapter\\' . $adapter_class;
                        $adapter_loader = new $adapter_name();

                        $test_geom_1 = $adapter_loader->read($output);

                        // Turn GEOS off
                        GeoPHP::geosInstalled(false);

                        $test_geom_2 = $adapter_loader->read($output);

                        // Turn GEOS back On
                        GeoPHP::geosInstalled(true);

                        // Check to make sure a both are the same with geos and without
                        $this->assertEquals($test_geom_1->out('wkt'), $test_geom_2->out('wkt'), "Mismatched adapter output between GEOS and NORM in " . $adapter_class . ' (test file: ' . $file . ')');
                    }
                }
            }
        }
    }
}
