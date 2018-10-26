# :belgium: [Geocoder PHP](https://github.com/geocoder-php/Geocoder) "bpost" provider

[![Build Status](https://travis-ci.org/geo6/geocoder-php-bpost-provider.svg?branch=master)](https://travis-ci.org/geo6/geocoder-php-bpost-provider)
[![Latest Stable Version](https://poser.pugx.org/geo6/geocoder-php-bpost-provider/v/stable)](https://packagist.org/packages/geo6/geocoder-php-bpost-provider)
[![Total Downloads](https://poser.pugx.org/geo6/geocoder-php-bpost-provider/downloads)](https://packagist.org/packages/geo6/geocoder-php-bpost-provider)
[![Monthly Downloads](https://poser.pugx.org/geo6/geocoder-php-bpost-provider/d/monthly.png)](https://packagist.org/packages/geo6/geocoder-php-bpost-provider)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)

> [Geocoder PHP](https://github.com/geocoder-php/Geocoder) is a PHP library which helps you build geo-aware applications by providing a powerful abstraction layer for geocoding manipulations.

This is the "bpost" provider for the [Geocoder PHP](https://github.com/geocoder-php/Geocoder).

**Coverage:** Belgium  
**API:** <https://www.bpost.be/site/en/webservice-address>

## Install

    composer require geo6/geocoder-php-bpost-provider

## Usage

See [Geocoder PHP README file](https://github.com/geocoder-php/Geocoder/blob/master/README.md).

```php
use Geocoder\Query\GeocodeQuery;

$httpClient = new \Http\Adapter\Guzzle6\Client();
$provider = new \Geocoder\Provider\bpost\bpost($httpClient);
$geocoder = new \Geocoder\StatefulGeocoder($provider, 'en');

// Query with unstructured address
$result = $geocoder->geocodeQuery(GeocodeQuery::create('5 Place des Palais 1000 Bruxelles'));

// Query with structured address
$query = GeocodeQuery::create('5 Place des Palais 1000 Bruxelles')
    ->withData('streetNumber', '5')
    ->withData('streetName', 'Place des Palais')
    ->withData('postalCode', '1000')
    ->withData('locality', 'Bruxelles');
$results = $geocoder->geocodeQuery($query);
```
