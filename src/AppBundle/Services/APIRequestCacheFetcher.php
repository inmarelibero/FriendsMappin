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

    public function getAPICallResult($urlAction)
    {
        $baseUrl = 'https://api.twitter.com';

        $apiRequestCacheUrl = $baseUrl.$urlAction;

        $apiRequestCache = $this->em->getRepository('AppBundle:APIRequestCache')->findOneByUrl($apiRequestCacheUrl);

        if (!$apiRequestCache) {
            $apiRequestCache = new APIRequestCache();

            $apiRequestCache->setUrl($apiRequestCacheUrl);

            $response = $this->getAPICallResponse($baseUrl, $urlAction);

            $i = 1;
            while ($response == null && $i <= count($this->oauthDatas)*2) {
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
