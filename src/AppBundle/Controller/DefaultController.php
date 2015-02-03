<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        // old redirect
        if ($request->get('username')) {
            return $this->redirectToRoute('show_user_map', array('username' => $request->get('username')));
        }

        // background map
        $map = $this->get('ivory_google_map.map');

        return array(
            'map' => $map,
        );
    }

    /**
     * @Route("/user", name="user_form_submit")
     * @Method("POST")
     * @Template()
     */
    public function submitUserFormAction(Request $request)
    {
        // old redirect
        if ($request->get('username')) {
            return $this->redirectToRoute('show_user_map', array('username' => $request->get('username')));
        }

        // should throw exception
        return $this->redirectToRoute('homepage');

        throw new HttpException(500, "You must specify a username.");
    }

    /**
     * @Route("/user/{username}", name="show_user_map")
     * @Template("AppBundle:Default:show_user_map.html.twig")
     */
    public function showUserMapAction(Request $request, $username)
    {
        // redirects if $username begins with "@"
        if (substr($username, 0, 1) == '@') {
            return $this->redirectToRoute('show_user_map', array('username' => substr($username, 1)));
        }

        return array(
            'username' => $username
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

        // fetch response from database or from Twitter
        $response = $this->get('apirequestcache.service')->getAPICallResult($urlAction);
        $response = json_decode($response, true);

        /**
         * to every user, add fields:
         *      - latitude:         null|float
         *      - longitude:        null|float
         *      - invalid_location: boolean
         */
        foreach ($response['users'] as $k => $user) {
            list($latitude, $longitude) = $this->get('geocoder_result.service')->getLatLongForLocation($user['location']);

            $response['users'][$k]['latitude'] = $latitude;
            $response['users'][$k]['longitude'] = $longitude;

            if (empty($latitude) || empty($longitude)) {
                $response['users'][$k]['invalid_location'] = true;
            } else {
                $response['users'][$k]['invalid_location'] = false;
            }

            $keysToUnset = array(
                'contributors_enabled',
                'created_at',
                'default_profile',
                'default_profile_image',
                'follow_request_sent',
                'following',
                'geo_enabled',
                'is_translation_enabled',
                'is_translator',
                'muting',
                'notifications',
                'profile_background_color',
                'profile_background_tile',
                'profile_link_color',
                'profile_location',
                'profile_sidebar_border_color',
                'profile_sidebar_fill_color',
                'profile_text_color',
                'profile_use_background_image',
                'protected',
                'statuses_count',
                'time_zone',
                'utc_offset',
                'verified'
            );
            foreach ($keysToUnset as $keyToUnset) {
                unset($response['users'][$k][$keyToUnset]);
            }
        }

        // set type of users
        $response['type_of_users'] = $typeOfUsers;

        return new JsonResponse($response);
    }
}
