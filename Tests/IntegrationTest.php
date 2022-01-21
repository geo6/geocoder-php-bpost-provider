<?php

/*
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Geocoder\Provider\bpost\Tests;

use Geocoder\IntegrationTest\ProviderIntegrationTest;
use Geocoder\Provider\bpost\bpost;
use Http\Client\HttpClient;

class IntegrationTest extends ProviderIntegrationTest
{
    protected $testAddress = true;

    protected $testReverse = false;

    protected $testIpv4 = false;

    protected $testIpv6 = false;

    protected $skippedTests = [
        'testGeocodeQuery' => 'Belgium only.',
    ];

    protected function createProvider(HttpClient $httpClient)
    {
        return new bpost($httpClient, $_SERVER['BPOST_API_KEY']);
    }

    protected function getCacheDir()
    {
        return __DIR__.'/.cached_responses';
    }

    protected function getApiKey()
    {
        return $_SERVER['BPOST_API_KEY'];
    }
}
