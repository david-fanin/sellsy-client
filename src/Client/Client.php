<?php

/**
 * Sellsy Client.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2016 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/sellsy-client Project website
 *
 * @license     http://teknoo.software/sellsy-client/license/mit         MIT License
 *
 * @author      Richard Déloge <richarddeloge@gmail.com>
 *
 * @version     0.8.0
 */
namespace Teknoo\Sellsy\Client;

use Teknoo\Curl\RequestGenerator;
use Teknoo\Sellsy\Client\Collection\CollectionGeneratorInterface;
use Teknoo\Sellsy\Client\Collection\CollectionInterface;
use Teknoo\Sellsy\Client\Exception\ErrorException;
use Teknoo\Sellsy\Client\Exception\RequestFailureException;

/**
 * Class Client
 * Main implementation of ClientInterface to perform Sellsy API requests as a local methods.
 *
 *
 * @copyright   Copyright (c) 2009-2016 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/sellsy-client Project website
 *
 * @license     http://teknoo.software/sellsy-client/license/mit         MIT License
 *
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Client implements ClientInterface
{
    /**
     * cUrl generator used to communicate with Sellsy API.
     *
     * @var RequestGenerator
     */
    protected $requestGenerator;

    /**
     * Sellsy collections of methods generator.
     *
     * @var CollectionGeneratorInterface
     */
    protected $collectionGenerator;

    /**
     * API End point.
     *
     * @var string
     */
    protected $apiUrl;

    /**
     * OAuth access token (provided by Sellsy).
     *
     * @var string
     */
    protected $oauthAccessToken;

    /**
     * OAuth secret (provided by Sellsy).
     *
     * @var string
     */
    private $oauthAccessTokenSecret;

    /**
     * OAuth consumer token (provided by Sellsy).
     *
     * @var string
     */
    protected $oauthConsumerKey;

    /**
     * OAuth consumer secret  (provided by Sellsy).
     *
     * @var string
     */
    private $oauthConsumerSecret;

    /**
     * Var to store the last request to facility debugging.
     *
     * @var array
     */
    protected $lastRequest;

    /**
     * Var to store the last answer of Sellsy API to facility debugging.
     *
     * @var mixed|\stdClass
     */
    protected $lastAnswer;

    /**
     * @var \DateTime
     */
    protected $now;

    /**
     * Constructor.
     *
     * @param RequestGenerator             $requestGenerator
     * @param CollectionGeneratorInterface $collectionGenerator
     * @param string                       $apiUrl
     * @param string                       $oauthAccessToken
     * @param string                       $oauthAccessTokenSecret
     * @param string                       $oauthConsumerKey
     * @param string                       $oauthConsumerSecret
     * @param \DateTime                    $now                    To allow developer to specify date to use to compute header. By default use now
     */
    public function __construct(
        RequestGenerator $requestGenerator,
        CollectionGeneratorInterface $collectionGenerator,
        $apiUrl = '',
        $oauthAccessToken = '',
        $oauthAccessTokenSecret = '',
        $oauthConsumerKey = '',
        $oauthConsumerSecret = '',
        \DateTime $now = null
    ) {
        $this->requestGenerator = $requestGenerator;
        $this->collectionGenerator = $collectionGenerator;
        $this->apiUrl = $apiUrl;
        $this->oauthAccessToken = $oauthAccessToken;
        $this->oauthAccessTokenSecret = $oauthAccessTokenSecret;
        $this->oauthConsumerKey = $oauthConsumerKey;
        $this->oauthConsumerSecret = $oauthConsumerSecret;
        $this->now = $now;
    }

    /**
     * Update the api url.
     *
     * @param string $apiUrl
     *
     * @return $this
     */
    public function setApiUrl($apiUrl)
    {
        $this->apiUrl = $apiUrl;

        return $this;
    }

    /**
     * Get the api url.
     *
     * @return string
     */
    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    /**
     * Update the OAuth access token.
     *
     * @param string $oauthAccessToken
     *
     * @return $this
     */
    public function setOAuthAccessToken($oauthAccessToken)
    {
        $this->oauthAccessToken = $oauthAccessToken;

        return $this;
    }

    /**
     * Get the OAuth access token.
     *
     * @return string
     */
    public function getOAuthAccessToken()
    {
        return $this->oauthAccessToken;
    }

    /**
     * Update the OAuth access secret token.
     *
     * @param string $oauthAccessTokenSecret
     *
     * @return $this
     */
    public function setOAuthAccessTokenSecret($oauthAccessTokenSecret)
    {
        $this->oauthAccessTokenSecret = $oauthAccessTokenSecret;

        return $this;
    }

    /**
     * Get the OAuth access secret token.
     *
     * @return string
     */
    public function getOAuthAccessTokenSecret()
    {
        return $this->oauthAccessTokenSecret;
    }

    /**
     * Update the OAuth consumer key.
     *
     * @param string $oauthConsumerKey
     *
     * @return $this
     */
    public function setOAuthConsumerKey($oauthConsumerKey)
    {
        $this->oauthConsumerKey = $oauthConsumerKey;

        return $this;
    }

    /**
     * Get the OAuth consumer key.
     *
     * @return string
     */
    public function getOAuthConsumerKey()
    {
        return $this->oauthConsumerKey;
    }

    /**
     * Update the OAuth consumer secret.
     *
     * @param string $oauthConsumerSecret
     *
     * @return $this
     */
    public function setOAuthConsumerSecret($oauthConsumerSecret)
    {
        $this->oauthConsumerSecret = $oauthConsumerSecret;

        return $this;
    }

    /**
     * Get the OAuth consumer secret.
     *
     * @return string
     */
    public function getOAuthConsumerSecret()
    {
        return $this->oauthConsumerSecret;
    }

    /**
     * Transform an array to HTTP headers OAuth string.
     *
     * @param array $oauth
     *
     * @return string
     */
    protected function encodeHeaders(&$oauth)
    {
        $values = array();
        foreach ($oauth as $key => &$value) {
            $values[] = $key.'="'.\rawurlencode($value).'"';
        }

        return 'Authorization: OAuth '.\implode(', ', $values);
    }

    /**
     * Internal method to generate HTTP headers to use for the API authentication with OAuth protocol.
     */
    protected function computeHeaders()
    {
        if ($this->now instanceof \DateTime) {
            $now = clone $this->now;
        } else {
            $now = new \DateTime();
        }

        //Generate HTTP headers
        $encodedKey = \rawurlencode($this->oauthConsumerSecret).'&'.\rawurlencode($this->oauthAccessTokenSecret);
        $oauthParams = array(
            'oauth_consumer_key' => $this->oauthConsumerKey,
            'oauth_token' => $this->oauthAccessToken,
            'oauth_nonce' => \md5($now->getTimestamp() + \rand(0, 1000000)),
            'oauth_timestamp' => $now->getTimestamp(),
            'oauth_signature_method' => 'PLAINTEXT',
            'oauth_version' => '1.0',
            'oauth_signature' => $encodedKey,
        );

        //Generate header
        return array($this->encodeHeaders($oauthParams), 'Expect:');
    }

    /**
     * @return array
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * @return mixed|\stdClass
     */
    public function getLastAnswer()
    {
        return $this->lastAnswer;
    }

    /**
     * Method to perform a request to the api.
     *
     * @param array $requestSettings
     *
     * @return \stdClass
     *
     * @throws RequestFailureException is the request can not be performed on the server
     * @throws ErrorException          if the server returned an error for this request
     */
    public function requestApi($requestSettings)
    {
        //Arguments for the Sellsy API
        $this->lastRequest = $requestSettings;
        $this->lastAnswer = null;
        $encodedRequest = array(
            'request' => 1,
            'io_mode' => 'json',
            'do_in' => \json_encode($requestSettings),
        );

        //Generate client request
        $request = $this->requestGenerator->getRequest();

        //Configure to contact the api with POST request and return value
        $request->setMethod('POST')
            ->setUrl($this->apiUrl)
            ->setReturnValue(true)
            ->setOptionArray(//Add custom headers and post values
                array(
                    CURLOPT_HTTPHEADER => $this->computeHeaders(),
                    CURLOPT_POSTFIELDS => $encodedRequest,
                    CURLOPT_SSL_VERIFYPEER => !\preg_match('!^https!i', $this->apiUrl),
                )
            );

        //Execute the request
        try {
            $result = $request->execute();
        } catch (\Exception $e) {
            throw new RequestFailureException($e->getMessage(), $e->getCode(), $e);
        }

        //OAuth issue, throw an exception
        if (false !== \strpos($result, 'oauth_problem')) {
            throw new RequestFailureException($result);
        }

        $answer = \json_decode($result);

        //Bad request, error returned by the api, throw an error
        if (!empty($answer->status) && 'error' == $answer->status) {
            if (!empty($answer->error->message)) {
                //Retrieve error message like it's defined in Sellsy API documentation
                throw new ErrorException($answer->error->message);
            } elseif (\is_string($answer->error)) {
                //Retrieve error message (sometime, error is not an object...)
                throw new ErrorException($answer->error);
            } else {
                //Other case, return directly the answer
                throw new ErrorException($result);
            }
        }

        $this->lastAnswer = $answer;

        return $this->lastAnswer;
    }

    /**
     * @return \stdClass
     */
    public function getInfos()
    {
        $requestSettings = array(
            'method' => 'Infos.getInfos',
            'params' => array(),
        );

        return $this->requestApi($requestSettings);
    }

    /**
     * Return collection methods of the api for Accountdatas.
     *
     * @return CollectionInterface
     */
    public function accountData()
    {
        return $this->collectionGenerator->getCollection($this, 'Accountdatas');
    }

    /**
     * Return collection methods of the api for AccountPrefs.
     *
     * @return CollectionInterface
     */
    public function accountPrefs()
    {
        return $this->collectionGenerator->getCollection($this, 'AccountPrefs');
    }

    /**
     * Return collection methods of the api for Purchase.
     *
     * @return CollectionInterface
     */
    public function purchase()
    {
        return $this->collectionGenerator->getCollection($this, 'Purchase');
    }

    /**
     * Return collection methods of the api for Agenda.
     *
     * @return CollectionInterface
     */
    public function agenda()
    {
        return $this->collectionGenerator->getCollection($this, 'Agenda');
    }

    /**
     * Return collection methods of the api for Annotations.
     *
     * @return CollectionInterface
     */
    public function annotations()
    {
        return $this->collectionGenerator->getCollection($this, 'Annotations');
    }

    /**
     * Return collection methods of the api for Catalogue.
     *
     * @return CollectionInterface
     */
    public function catalogue()
    {
        return $this->collectionGenerator->getCollection($this, 'Catalogue');
    }

    /**
     * Return collection methods of the api for CustomFields.
     *
     * @return CollectionInterface
     */
    public function customFields()
    {
        return $this->collectionGenerator->getCollection($this, 'CustomFields');
    }

    /**
     * Return collection methods of the api for Client.
     *
     * @return CollectionInterface
     */
    public function client()
    {
        return $this->collectionGenerator->getCollection($this, 'Client');
    }

    /**
     * Return collection methods of the api for Staffs.
     *
     * @return CollectionInterface
     */
    public function staffs()
    {
        return $this->collectionGenerator->getCollection($this, 'Staffs');
    }

    /**
     * Return collection methods of the api for Peoples.
     *
     * @return CollectionInterface
     */
    public function peoples()
    {
        return $this->collectionGenerator->getCollection($this, 'Peoples');
    }

    /**
     * Return collection methods of the api for Document.
     *
     * @return CollectionInterface
     */
    public function document()
    {
        return $this->collectionGenerator->getCollection($this, 'Document');
    }

    /**
     * Return collection methods of the api for Mails.
     *
     * @return CollectionInterface
     */
    public function mails()
    {
        return $this->collectionGenerator->getCollection($this, 'Mails');
    }

    /**
     * Return collection methods of the api for Event.
     *
     * @return CollectionInterface
     */
    public function event()
    {
        return $this->collectionGenerator->getCollection($this, 'Event');
    }

    /**
     * Return collection methods of the api for Expense.
     *
     * @return CollectionInterface
     */
    public function expense()
    {
        return $this->collectionGenerator->getCollection($this, 'Expense');
    }

    /**
     * Return collection methods of the api for Opportunities.
     *
     * @return CollectionInterface
     */
    public function opportunities()
    {
        return $this->collectionGenerator->getCollection($this, 'Opportunities');
    }

    /**
     * Return collection methods of the api for Prospects.
     *
     * @return CollectionInterface
     */
    public function prospects()
    {
        return $this->collectionGenerator->getCollection($this, 'Prospects');
    }

    /**
     * Return collection methods of the api for SmartTags.
     *
     * @return CollectionInterface
     */
    public function smartTags()
    {
        return $this->collectionGenerator->getCollection($this, 'SmartTags');
    }

    /**
     * Return collection methods of the api for Stat.
     *
     * @return CollectionInterface
     */
    public function stat()
    {
        return $this->collectionGenerator->getCollection($this, 'Stat');
    }

    /**
     * Return collection methods of the api for Stock.
     *
     * @return CollectionInterface
     */
    public function stock()
    {
        return $this->collectionGenerator->getCollection($this, 'Stock');
    }

    /**
     * Return collection methods of the api for Support.
     *
     * @return CollectionInterface
     */
    public function support()
    {
        return $this->collectionGenerator->getCollection($this, 'Support');
    }

    /**
     * Return collection methods of the api for Timetracking.
     *
     * @return CollectionInterface
     */
    public function timeTracking()
    {
        return $this->collectionGenerator->getCollection($this, 'Timetracking');
    }

    /**
     * @return CollectionInterface
     */
    public function bankAccount()
    {
        return $this->collectionGenerator->getCollection($this, 'BankAccount');
    }

    /**
     * @return CollectionInterface
     */
    public function addresses()
    {
        return $this->collectionGenerator->getCollection($this, 'Addresses');
    }
}
