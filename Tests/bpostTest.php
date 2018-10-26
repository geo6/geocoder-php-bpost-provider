<?php

declare(strict_types=1);

/*
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Geocoder\Provider\bpost\Tests;

use Geocoder\IntegrationTest\BaseTestCase;
use Geocoder\Provider\bpost\bpost;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;

class bpostTest extends BaseTestCase
{
    protected function getCacheDir()
    {
        return __DIR__.'/.cached_responses';
    }

    /**
     * @expectedException \Geocoder\Exception\UnsupportedOperation
     * @expectedExceptionMessage The bpost provider does not support IP addresses, only street addresses.
     */
    public function testGeocodeWithLocalhostIPv4()
    {
        $provider = new bpost($this->getMockedHttpClient(), 'Geocoder PHP/bpost Provider/bpost Test');
        $provider->geocodeQuery(GeocodeQuery::create('127.0.0.1'));
    }

    /**
     * @expectedException \Geocoder\Exception\UnsupportedOperation
     * @expectedExceptionMessage The bpost provider does not support IP addresses, only street addresses.
     */
    public function testGeocodeWithLocalhostIPv6()
    {
        $provider = new bpost($this->getMockedHttpClient(), 'Geocoder PHP/bpost Provider/bpost Test');
        $provider->geocodeQuery(GeocodeQuery::create('::1'));
    }

    /**
     * @expectedException \Geocoder\Exception\UnsupportedOperation
     * @expectedExceptionMessage The bpost provider does not support IP addresses, only street addresses.
     */
    public function testGeocodeWithRealIPv6()
    {
        $provider = new bpost($this->getMockedHttpClient(), 'Geocoder PHP/bpost Provider/bpost Test');
        $provider->geocodeQuery(GeocodeQuery::create('::ffff:88.188.221.14'));
    }

    /**
     * @expectedException \Geocoder\Exception\UnsupportedOperation
     * @expectedExceptionMessage The bpost provider does not support reverse geocoding.
     */
    public function testReverseQuery()
    {
        $provider = new bpost($this->getMockedHttpClient(), 'Geocoder PHP/bpost Provider/bpost Test');
        $provider->reverseQuery(ReverseQuery::fromCoordinates(0, 0));
    }

    public function testGeocodeQuery()
    {
        $provider = new bpost($this->getHttpClient(), 'Geocoder PHP/bpost Provider/bpost Test');
        $results = $provider->geocodeQuery(GeocodeQuery::create('5 Place des Palais 1000 Bruxelles'));

        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $results);
        $this->assertCount(1, $results);

        /** @var \Geocoder\Model\Address $result */
        $result = $results->first();
        $this->assertInstanceOf('\Geocoder\Model\Address', $result);
        $this->assertEquals(50.842931, $result->getCoordinates()->getLatitude(), '', 0.00001);
        $this->assertEquals(4.361186, $result->getCoordinates()->getLongitude(), '', 0.00001);
        $this->assertEquals('5', $result->getStreetNumber());
        $this->assertEquals('PLACE DES PALAIS', $result->getStreetName());
        $this->assertEquals('1000', $result->getPostalCode());
        $this->assertEquals('BRUXELLES', $result->getLocality());
        $this->assertEquals('BELGIQUE', $result->getCountry());
    }

    public function testGeocodeQueryWithData()
    {
        $query = GeocodeQuery::create('5 Place des Palais 1000 Bruxelles')
            ->withData('streetNumber', '5')
            ->withData('streetName', 'Place des Palais')
            ->withData('postalCode', '1000')
            ->withData('locality', 'bruxelles');

        $provider = new bpost($this->getHttpClient(), 'Geocoder PHP/bpost Provider/bpost Test');
        $results = $provider->geocodeQuery($query);

        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $results);
        $this->assertCount(1, $results);

        /** @var \Geocoder\Model\Address $result */
        $result = $results->first();
        $this->assertInstanceOf('\Geocoder\Model\Address', $result);
        $this->assertEquals(50.842931, $result->getCoordinates()->getLatitude(), '', 0.00001);
        $this->assertEquals(4.361186, $result->getCoordinates()->getLongitude(), '', 0.00001);
        $this->assertEquals('5', $result->getStreetNumber());
        $this->assertEquals('PLACE DES PALAIS', $result->getStreetName());
        $this->assertEquals('1000', $result->getPostalCode());
        $this->assertEquals('BRUXELLES', $result->getLocality());
        $this->assertEquals('BELGIQUE', $result->getCountry());
    }
}
