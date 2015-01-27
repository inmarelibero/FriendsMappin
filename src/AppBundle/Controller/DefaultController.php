<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage", options={"expose" = true})
     * @Template()
     */
    public function indexAction(Request $request)
    {
        // map
        $map = $this->get('ivory_google_map.map');

        return array(
            'map' => $map,
        );
    }

    /**
     * @Cache(smaxage="86400")
     * @Route("/get_user_profile", name="get_profile_show", options={"expose" = true})
     */
    public function getUserProfile(Request $request)
    {
        $username = $request->get('username');

        if (!$username) {
            throw new HttpException(500, 'No username provided');
        }

        $urlAction = "/1.1/users/show.json?screen_name={$username}";

        $response = $this->get('apirequestcache.service')->getAPICallResult($urlAction);

        $response = json_decode($response, true);

        return new JsonResponse($response);
    }

    /**
     * @Cache(smaxage="86400")
     * @Route("/get_users_of_username", name="get_users_of_username", options={"expose" = true})
     */
    public function getUsersOfUsernameAction(Request $request)
    {
        $cursor = $request->get('cursor', '-1');

        $username = $request->get('username');
        if (!$username) {
            throw new HttpException(500, 'No username provided');
        }

        $typeOfUsers = $request->get('type_of_users');
        if (!in_array($typeOfUsers, array('friends', 'followers'))) {
            throw new HttpException(200, 'No user type provided');
        }

        $urlAction = "/1.1/{$typeOfUsers}/list.json?cursor={$cursor}&screen_name={$username}&skip_status=true&include_user_entities=false&count=200";

        $response = $this->get('apirequestcache.service')->getAPICallResult($urlAction);

        $response = json_decode($response, true);
        $response['type_of_users'] = $typeOfUsers;

        return new JsonResponse($response);
    }

    /**
     * @Cache(smaxage="3600")
     * @Route("/get_coordinates_from_location", name="get_coordinates_from_location", options={"expose" = true})
     */
    public function getCoordinatesFromLocationAction(Request $request)
    {
        $request_body = file_get_contents('php://input');
        $data = json_decode($request_body);

        $locations = $data->locations;

        $jsonParameters = array();

        foreach ($locations as $k => $location) {
            try {
                list($latitude, $longitude) = $this->get('geocoder_result.service')->getLatLongForLocation($location);
            } catch (\Exception $e) {
                $jsonParameters[$location] = array(
                    'latitude' => null,
                    'longitude' => null
                );

                continue;
            }

            $jsonParameters[$location] = array(
                'latitude' => $latitude,
                'longitude' => $longitude
            );
        }

        return new JsonResponse($jsonParameters);
    }
}