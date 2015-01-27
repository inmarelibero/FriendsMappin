<?php

namespace AppBundle\Services;

use AppBundle\Entity\APIRequestCache;
use Doctrine\ORM\EntityManager;
use Guzzle\Plugin\Oauth\OauthPlugin;
use Guzzle\Service\Client;
use Symfony\Component\Security\Acl\Exception\Exception;

class APIRequestCacheFetcher
{
    protected $em;
    protected $oauthDatas = array(
        /*
         * inmarelibero
         */
        array(
            'consumer_key'    => 'KvQ7VUllDDsrttC5VIHSRfGgX',
            'consumer_secret' => 'Fu1xvihpyqlpABDpb0WEDeLHaiIOTjnsRj6HbMgzPORgZeaDMi',
            'token'           => '257724631-mU4KnIJmiy9tOCBS7o90ePwvAMDKplYlUv4OcH4M',
            'token_secret'    => 'kzjZkWrJPTRqoRIOG0rBVINPamIoPIYppC89ClKBSTXYG'
        ),
        array(
            'consumer_key'    => 'yzHaTT4f1qQzmPwM1rGoln0v1',
            'consumer_secret' => 'KXEP38Kox78OV7GtBrSUDlTiBrEtnv5FZ4RFJW1elXXq35JZ7v',
            'token'           => '257724631-DRvOtB58kq1wgUULE37aMaoSiDqs7Mucxr9joHJu',
            'token_secret'    => 'LXregqb1E3MvmLBkBdDUJZ1aLb7XTlUBObdDRFNiPUscs'
        ),
        array(
            'consumer_key'    => 'lFOzrHBzhkPZ6JTBDmVoEodEY',
            'consumer_secret' => 'KGvf5IX0UjwjHpFKlCiqKb3JBEBeGkThUjv69c38kDKFKucpHb',
            'token'           => '257724631-b53sEEG0TlnR49BMdAkp08UhuhBlrsGV3HJtn7F2',
            'token_secret'    => 'HpaENZRBmJsuCLo3q6527rkRuVoFs5cZt4icE5saKfqUe'
        ),
        array(
            'consumer_key'    => 'c4JULMWWsBrJzRU20z42fbeo6',
            'consumer_secret' => 'Ptq2Rp5lEhoDIFQI4MzOUHMxAhwHTHKdSBjDoEkhhduAjLAfS6',
            'token'           => '257724631-JfMpZYqCRjoVgIVdBUUZplhpo5DLJ1pijqjPNlZE',
            'token_secret'    => 'ZcT6UNKTqzo63ssZVMA4IxgPlTJ7coGT6EozZg21vmdUU'
        ),

        /*
         * friendsmappin
         */
        array(
            'consumer_key'    => 'AHHYaiDgGraQq0jmvLACHD9bg',
            'consumer_secret' => '1raUFdyOoF2w7VIwVYtbB6sKInaDlx82JirLQ7RN2cg9R8fzLr',
            'token'           => '3001047111-cnkeEKBKvo4GOEjEZPNGPquPrNKRbiPW87xdSpK',
            'token_secret'    => 'NJAFMowWBrRAk7RDiYQEJsjLdw2ssy6t2IyaYKAIxYHqm'
        ),
        array(
            'consumer_key'    => 'socXS60X8BicTqzW4yoNOxQR0',
            'consumer_secret' => 'gI4d07zJfG3YdFxH51qUzcrY04JbEIWVTUQaFc2XEIkCQQlQYC',
            'token'           => '3001047111-JyQa2A5FTluNbjEzfjKo7HpaOf3LMFAnFfYfsyf',
            'token_secret'    => '90UcvWgRMRbQ44MAXGeXCwrPGXjDGso2s5wYgT4Iq5wY4'
        ),
        array(
            'consumer_key'    => 'ZS5vphgufikxIaplP8GVgzNsI',
            'consumer_secret' => 'kfOirtKAmemUVmngRdJ05HwzfD9OHiXM78jiAijFJzjBpGBft9',
            'token'           => '3001047111-AQU70xlg61susjnPHcwZqopNl7KqhxQzOTeo6ER',
            'token_secret'    => 'Mp1hGDhLslmfVz00jAkgN5cwKO51ZGfIwZbYYtP5rjJ30'
        ),
    );

    public function __construct(EntityManager $em)
    {
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
        $oauthDatas = $this->oauthDatas[array_rand($this->oauthDatas)];

        return new OauthPlugin($oauthDatas);
    }
}
