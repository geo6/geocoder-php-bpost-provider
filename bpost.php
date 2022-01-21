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
use Geocoder\Exception\InvalidCredentials;
use Geocoder\Exception\InvalidServerResponse;
use Geocoder\Exception\UnsupportedOperation;
use Geocoder\Http\Provider\AbstractHttpProvider;
use Geocoder\Model\Address;
use Geocoder\Model\AddressBuilder;
use Geocoder\Model\AddressCollection;
use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use Http\Client\HttpClient;

/**
 * @author Jonathan Beliën <jbe@geo6.be>
 */
final class bpost extends AbstractHttpProvider implements Provider
{
    /**
     * @var string
     */
    const GEOCODE_ENDPOINT_TEST_URL = 'https://api.mailops-np.bpost.cloud/roa-info-st2/externalMailingAddressProofingRest/validateAddresses';
    const GEOCODE_ENDPOINT_UAT_URL = 'https://api.mailops-np.bpost.cloud/roa-info-ac/externalMailingAddressProofingRest/validateAddresses';
    const GEOCODE_ENDPOINT_PROD_URL = 'https://api.mailops.bpost.cloud/roa-info/externalMailingAddressProofingRest/validateAddresses';

    /**
     * @var string|null
     */
    private $apiKey;

    /**
     * @param HttpClient $client an HTTP adapter
     */
    public function __construct(HttpClient $client, string $apiKey = null)
    {
        parent::__construct($client);

        $this->apiKey = $apiKey;
    }

    /**
     * {@inheritdoc}
     */
    public function geocodeQuery(GeocodeQuery $query): Collection
    {
        $address = $query->getText();
        // This API does not support IP
        if (filter_var($address, FILTER_VALIDATE_IP)) {
            throw new UnsupportedOperation('The bpost provider does not support IP addresses, only street addresses.');
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
                    'UnstructuredAddressLine' => [
                        [
                            '*body'   => $address,
                            '@locale' => $query->getLocale(),
                        ],
                    ],
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

        $json = $this->executeQuery(self::GEOCODE_ENDPOINT_PROD_URL, json_encode($request));

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
        if (null === $this->apiKey) {
            throw new InvalidCredentials('You must provide an API key.');
        }

        $request = $this->getMessageFactory()->createRequest(
            'POST',
            $url,
            [
                'Content-Type' => 'application/json',
                'x-api-key' => $this->apiKey,
            ],
            $data
        );

        $body = $this->getParsedResponse($request);
        $json = json_decode($body);

        // API error
        if (!isset($json)) {
            throw InvalidServerResponse::create($url);
        }

        return $json;
    }
}
