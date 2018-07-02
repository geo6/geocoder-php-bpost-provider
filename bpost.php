<?php

declare(strict_types=1);

/*
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Geocoder\Provider\bpost;

use Geocoder\Collection;
use Geocoder\Exception\InvalidArgument;
use Geocoder\Exception\InvalidServerResponse;
use Geocoder\Exception\UnsupportedOperation;
use Geocoder\Http\Provider\AbstractHttpProvider;
use Geocoder\Model\Address;
use Geocoder\Model\AddressBuilder;
use Geocoder\Model\AddressCollection;
use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use GuzzleHttp\Psr7;
use Http\Client\HttpClient;

/**
 * @author Jonathan Beliën <jbe@geo6.be>
 */
final class bpost extends AbstractHttpProvider implements Provider
{
    /**
     * @var string
     */
    const GEOCODE_ENDPOINT_URL = 'https://webservices-pub.bpost.be/ws/ExternalMailingAddressProofingCSREST_v1/address/validateAddresses';

    /**
     * @param HttpClient $client an HTTP adapter
     */
    public function __construct(HttpClient $client)
    {
        parent::__construct($client);
    }

    /**
     * {@inheritdoc}
     */
    public function geocodeQuery(GeocodeQuery $query): Collection
    {
        $address = $query->getText();
        // This API does not support IP
        if (filter_var($address, FILTER_VALIDATE_IP)) {
            throw new UnsupportedOperation('The UrbIS provider does not support IP addresses, only street addresses.');
        }

        // Save a request if no valid address entered
        if (empty($address)) {
            throw new InvalidArgument('Address cannot be empty.');
        }

        $address = $query->getText();
        $streetName = $query->getData('streetName');
        $streetNumber = $query->getData('streetNumber');

        if (!is_null($streetName) && !is_null($streetNumber)) {
            $addressToValidate = [
              '@id'           => 1,
              'PostalAddress' => [
                'DeliveryPointLocation' => [
                  'StructuredDeliveryPointLocation' => [
                    'StreetName'   => $query->getData('streetName'),
                    'StreetNumber' => $query->getData('streetNumber'),
                  ],
                ],
                'PostalCodeMunicipality' => [
                  'StructuredPostalCodeMunicipality' => [
                    'PostalCode'       => $query->getData('postalCode', ''),
                    'MunicipalityName' => $query->getData('locality', ''),
                  ],
                ],
              ],
              'DeliveringCountryISOCode'  => 'BE',
              'DispatchingCountryISOCode' => 'BE',
            ];
        } else {
            $addressToValidate = [
            '@id'               => 1,
            'AddressBlockLines' => [
              'UnstructuredAddressLine' => $address,
            ],
            'DeliveringCountryISOCode'  => 'BE',
            'DispatchingCountryISOCode' => 'BE',
          ];
        }

        $request = [];
        $request['ValidateAddressesRequest'] = [
          'AddressToValidateList' => [
            'AddressToValidate' => [
              $addressToValidate,
            ],
          ],
          'ValidateAddressOptions' => [
            'IncludeSuggestions'        => false,
            'IncludeDefaultGeoLocation' => true,
            'IncludeSubmittedAddress'   => true,
          ],
          'CallerIdentification' => [
            'CallerName' => 'Geocoder PHP',
          ],
        ];

        $json = $this->executeQuery(self::GEOCODE_ENDPOINT_URL, json_encode($request));

        // no result
        if (empty($json->ValidateAddressesResponse->ValidatedAddressResultList->ValidatedAddressResult)) {
            return new AddressCollection([]);
        }

        $results = [];
        foreach ($json->ValidateAddressesResponse->ValidatedAddressResultList->ValidatedAddressResult as $location) {
            if (isset($location->ValidatedAddressList->ValidatedAddress[0]->ServicePointDetail, $location->ValidatedAddressList->ValidatedAddress[0]->PostalAddress)) {
                $coordinates = $location->ValidatedAddressList->ValidatedAddress[0]->ServicePointDetail->GeographicalLocationInfo->GeographicalLocation;

                $postalAddress = $location->ValidatedAddressList->ValidatedAddress[0]->PostalAddress;

                $streetName = !empty($postalAddress->StructuredDeliveryPointLocation->StreetName) ? $postalAddress->StructuredDeliveryPointLocation->StreetName : null;
                $number = !empty($postalAddress->StructuredDeliveryPointLocation->StreetNumber) ? $postalAddress->StructuredDeliveryPointLocation->StreetNumber : null;
                $municipality = !empty($postalAddress->StructuredPostalCodeMunicipality->MunicipalityName) ? $postalAddress->StructuredPostalCodeMunicipality->MunicipalityName : null;
                $postCode = !empty($postalAddress->StructuredPostalCodeMunicipality->PostalCode) ? $postalAddress->StructuredPostalCodeMunicipality->PostalCode : null;
                $country = !empty($postalAddress->CountryName) ? $postalAddress->CountryName : null;

                $builder = new AddressBuilder($this->getName());
                $builder->setCoordinates($coordinates->Latitude->Value, $coordinates->Longitude->Value)
                    ->setStreetNumber($number)
                    ->setStreetName($streetName)
                    ->setLocality($municipality)
                    ->setPostalCode($postCode)
                    ->setCountry($country ?? null)
                    ->setCountry($country);

                $results[] = $builder->build();
            }
        }

        return new AddressCollection($results);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseQuery(ReverseQuery $query): Collection
    {
        throw new UnsupportedOperation('The bpost provider does not support reverse geocoding.');
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'bpost';
    }

    /**
     * @param string $url
     *
     * @return \stdClass
     */
    private function executeQuery(string $url, string $data): \stdClass
    {
        $request = $this->getRequest($url);

        $request = $request->withMethod('POST');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withBody(Psr7\stream_for($data));

        $body = $this->getParsedResponse($request);

        $json = json_decode($body);
        // API error
        if (!isset($json)) {
            throw InvalidServerResponse::create($url);
        }

        return $json;
    }
}
