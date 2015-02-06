<?php

namespace AppBundle\Services;

use AppBundle\Entity\APIRequestCache;
use Doctrine\ORM\EntityManager;
use Guzzle\Plugin\Oauth\OauthPlugin;
use Guzzle\Service\Client;
use Symfony\Component\Security\Acl\Exception\Exception;

class APIRequestCacheFetcher
{
    protected $twitterOauthDatas;
    protected $em;

    public function __construct(array $twitterOauthDatas, EntityManager $em)
    {
        $this->twitterOauthDatas = $twitterOauthDatas;
        $this->em = $em;
    }

    /**
     * @param $urlAction
     * @param null $maxAPITrials
     * @return string
     * @throws Exception
     * @throws \Exception
     */
    public function getAPICallResult($urlAction, $maxAPITrials = null)
    {
        $baseUrl = 'https://api.twitter.com';

        $apiRequestCacheUrl = $baseUrl.$urlAction;

        $apiRequestCache = $this->em->getRepository('AppBundle:APIRequestCache')->findOneByUrl($apiRequestCacheUrl);

        if (!$apiRequestCache) {
            $apiRequestCache = new APIRequestCache();

            $apiRequestCache->setUrl($apiRequestCacheUrl);

            $response = $this->getAPICallResponse($baseUrl, $urlAction);

            /**
             * calculate max trials to APIs
             *
             * when fetching friends and followers, the number of trials could be high, while when fetching a user profile can be low because
             * if username does not exist, making many requests is useless
             */
            $maxAPITrials = (is_integer($maxAPITrials)) ? $maxAPITrials : count($this->twitterOauthDatas)*2;


            $i = 1;
            while ($response == null && $i <= $maxAPITrials) {
                $response = $this->getAPICallResponse($baseUrl, $urlAction);

                $i++;
            }

            if ($response == null) {
                throw new Exception("API response was null");
            }

            $apiRequestCache->setResponse($response);

            $this->em->persist($apiRequestCache);
            $this->em->flush();
        }

        return $apiRequestCache->getResponse();
    }

    private function getAPICallResponse($baseUrl, $actionUrl)
    {
        $client = new Client($baseUrl);

        $oauth  = $this->getRandomTwitterOAuth();

        $client->addSubscriber($oauth);

        $request = $client->get($actionUrl);
        //$request->getQuery()->set('count', 5);

        try {
            $response = $request->send();
        } catch (\Exception $e) {
            return null;
        }

        if ($response->getStatusCode() !== 200) {
            throw new \Exception($response->getBody());
        }

        return $response->getBody();
    }

    private function getRandomTwitterOAuth()
    {
        $oauthDatas = $this->twitterOauthDatas[array_rand($this->twitterOauthDatas)];

        return new OauthPlugin($oauthDatas);
    }
}
