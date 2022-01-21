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

    public function testGeocodeWithLocalhostIPv4()
    {
        $this->expectException(\Geocoder\Exception\UnsupportedOperation::class);
        $this->expectExceptionMessage('The bpost provider does not support IP addresses, only street addresses.');

        $provider = new bpost($this->getMockedHttpClient(), $_SERVER['BPOST_API_KEY']);
        $provider->geocodeQuery(GeocodeQuery::create('127.0.0.1'));
    }

    public function testGeocodeWithLocalhostIPv6()
    {
        $this->expectException(\Geocoder\Exception\UnsupportedOperation::class);
        $this->expectExceptionMessage('The bpost provider does not support IP addresses, only street addresses.');

        $provider = new bpost($this->getMockedHttpClient(), $_SERVER['BPOST_API_KEY']);
        $provider->geocodeQuery(GeocodeQuery::create('::1'));
    }

    public function testGeocodeWithRealIPv6()
    {
        $this->expectException(\Geocoder\Exception\UnsupportedOperation::class);
        $this->expectExceptionMessage('The bpost provider does not support IP addresses, only street addresses.');

        $provider = new bpost($this->getMockedHttpClient(), $_SERVER['BPOST_API_KEY']);
        $provider->geocodeQuery(GeocodeQuery::create('::ffff:88.188.221.14'));
    }

    public function testReverseQuery()
    {
        $this->expectException(\Geocoder\Exception\UnsupportedOperation::class);
        $this->expectExceptionMessage('The bpost provider does not support reverse geocoding.');

        $provider = new bpost($this->getMockedHttpClient(), $_SERVER['BPOST_API_KEY']);
        $provider->reverseQuery(ReverseQuery::fromCoordinates(0, 0));
    }

    public function testGeocodeQuery()
    {
        $provider = new bpost($this->getHttpClient($_SERVER['BPOST_API_KEY']), $_SERVER['BPOST_API_KEY']);
        $results = $provider->geocodeQuery(GeocodeQuery::create('5 Place des Palais 1000 Bruxelles'));

        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $results);
        $this->assertCount(1, $results);

        /** @var \Geocoder\Model\Address $result */
        $result = $results->first();
        $this->assertInstanceOf('\Geocoder\Model\Address', $result);
        $this->assertEqualsWithDelta(50.842931, $result->getCoordinates()->getLatitude(), 0.00001);
        $this->assertEqualsWithDelta(4.361186, $result->getCoordinates()->getLongitude(), 0.00001);
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
            ->withData('locality', 'Bruxelles');

        $provider = new bpost($this->getHttpClient($_SERVER['BPOST_API_KEY']), $_SERVER['BPOST_API_KEY']);
        $results = $provider->geocodeQuery($query);

        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $results);
        $this->assertCount(1, $results);

        /** @var \Geocoder\Model\Address $result */
        $result = $results->first();
        $this->assertInstanceOf('\Geocoder\Model\Address', $result);
        $this->assertEqualsWithDelta(50.842931, $result->getCoordinates()->getLatitude(), 0.00001);
        $this->assertEqualsWithDelta(4.361186, $result->getCoordinates()->getLongitude(), 0.00001);
        $this->assertEquals('5', $result->getStreetNumber());
        $this->assertEquals('PLACE DES PALAIS', $result->getStreetName());
        $this->assertEquals('1000', $result->getPostalCode());
        $this->assertEquals('BRUXELLES', $result->getLocality());
        $this->assertEquals('BELGIQUE', $result->getCountry());
    }
}
