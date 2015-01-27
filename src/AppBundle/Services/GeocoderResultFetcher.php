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

    public function __construct(EntityManager $em, $ivoryGoogleMapGeocoder)
    {
        $this->em = $em;
        $this->ivoryGoogleMapGeocoder = $ivoryGoogleMapGeocoder;
    }

    public function getLatLongForLocation($location)
    {
        $location = strtolower($location);

        $geocoderResult = $this->em->getRepository('AppBundle:GeocoderResult')->findOneByLocation($location);

        if (!$geocoderResult) {
            $geocoderResult = new GeocoderResult();
            $geocoderResult->setLocation($location);
        }

        if ($geocoderResult->getUpdated()) {
            $interval = (new \DateTime('now'))->diff($geocoderResult->getUpdated());

            if ($interval->format('%R%a') < -7) {
                list ($lat, $long) = $this->getLatLongFromGeocoding($location);

                $geocoderResult->setLatitude($lat);
                $geocoderResult->setLongitude($long);
            }
        } else {
            list ($lat, $long) = $this->getLatLongFromGeocoding($location);

            $geocoderResult->setLatitude($lat);
            $geocoderResult->setLongitude($long);
        }

        $this->em->persist($geocoderResult);
        $this->em->flush();

        return array(
            $geocoderResult->getLatitude(),
            $geocoderResult->getLongitude()
        );
    }

    private function getLatLongFromGeocoding($location)
    {
        $geocoderResponse = $this->ivoryGoogleMapGeocoder->geocode($location);

        if (GeocoderStatus::OK !== $geocoderResponse->getStatus()) {
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