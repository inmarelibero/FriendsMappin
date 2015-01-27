<?php

namespace AppBundle\Services;

use AppBundle\Entity\GeocoderResult;
use Doctrine\ORM\EntityManager;
use Ivory\GoogleMap\Services\Geocoding\Result\GeocoderStatus;
use Symfony\Component\Security\Acl\Exception\Exception;

class GeocoderResultFetcher
{
    protected $em;
    protected $ivoryGoogleMapGeocoder;
    protected $jmsSerializer;

    public function __construct(EntityManager $em, $ivoryGoogleMapGeocoder, $jmsSerializer)
    {
        $this->em = $em;
        $this->ivoryGoogleMapGeocoder = $ivoryGoogleMapGeocoder;
        $this->jmsSerializer = $jmsSerializer;
    }

    public function getLatLongForLocation($location)
    {
        $location = strtolower($location);

        $geocoderResult = $this->em->getRepository('AppBundle:GeocoderResult')->findOneByLocation($location);

        /**
         * persist a new entity if not in database
         */
        if (!$geocoderResult) {
            $geocoderResult = new GeocoderResult();
            $geocoderResult->setLocation($location);

            $this->persistGeocoderResult($geocoderResult);

            $this->updateCoordinatesIfNecessary($geocoderResult, true);
        }

        $this->updateCoordinatesIfNecessary($geocoderResult);

        $latitude   = $geocoderResult->getLatitude();
        $longitude  = $geocoderResult->getLongitude();

        return array(
            $latitude,
            $longitude
        );
    }

    private function persistGeocoderResult($geocoderResult)
    {
        if (is_array($geocoderResult)) {
            $geocoderResult = $this->getGeocoderResultEntityFromArray($geocoderResult);
        }

        if (!$geocoderResult instanceof GeocoderResult) {
            return;
        }

        // persist a new entity
        $this->em->persist($geocoderResult);
        $this->em->flush();
    }

    private function updateCoordinatesIfNecessary(GeocoderResult $geocoderResult, $forceUpdate = false)
    {
        if (!$geocoderResult->getLocation() || $geocoderResult->getLocation() == '') {
            return;
        }

        /**
         * check if coordinates must be updated
         */
        if ($forceUpdate !== true) {
            $interval = (new \DateTime('now'))->diff($geocoderResult->getUpdated());

            // prima di 31 giorni non aggiornare la location
            if ($interval->format('%R%a') > -31) {
                //die(var_dump('dont update', $geocoderResult->getId()));
                return;
            }
        }

        /**
         * update corrdinates
         */
        try {
            list ($lat, $long) = $this->getLatLongFromGeocoding($geocoderResult->getLocation());
        } catch (\Exception $e) {
            /**
             * suppress exceptions
             */
            list ($lat, $long) = array(null, null);
        }

        // update entity
        $geocoderResult->setLatitude($lat);
        $geocoderResult->setLongitude($long);

        $this->persistGeocoderResult($geocoderResult);
    }

    /**
     * Get latitude and longitude from a location, fetches results from geocoding
     *
     * @param $location
     * @return array
     * @throws Exception
     */
    private function getLatLongFromGeocoding($location)
    {
        // throws exceptions
        $geocoderResponse = $this->ivoryGoogleMapGeocoder->geocode($location);

        if (GeocoderStatus::OK !== $geocoderResponse->getStatus()) {
            /**
             * @todo return null?
             */
            throw new Exception($geocoderResponse->getStatus());
        }

        $results = $geocoderResponse->getResults();
        if (count($results) >= 1) {
            $result = $results[0];
        }

        return array(
            $result->getGeometry()->getLocation()->getLatitude(),
            $result->getGeometry()->getLocation()->getLongitude()
        );
    }
}