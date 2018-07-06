<?php

namespace Jeylabs\Wit;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\RequestOptions as GuzzleRequestOptions;

class Wit
{
    const VERSION = '1.0.0';
    const WIT_API_BASE_URI = 'https://api.wit.ai/';
    const TEXT_INTENT_API = 'message';
    const SPEECH_INTENT_API = 'speech';
    const ENTITIES_API = 'entities';
    const WIT_API_VERSION = '20160526';
    const DEFAULT_TIMEOUT = 5;
    protected $client;
    protected $access_token;
    protected $isAsyncRequest = false;
    protected $headers = [];
    protected $promises = [];
    protected $lastResponse;

    public function __construct($access_token, $isAsyncRequest = false, $httpClient = null)
    {
        $this->access_token = $access_token;
        $this->isAsyncRequest = $isAsyncRequest;
        $this->client = $httpClient ?: new Client([
            'base_uri' => self::WIT_API_BASE_URI,
            'timeout' => self::DEFAULT_TIMEOUT,
            'connect_timeout' => self::DEFAULT_TIMEOUT,
        ]);
    }

    public function isAsyncRequests()
    {
        return $this->isAsyncRequest;
    }

    public function setAsyncRequests($isAsyncRequest)
    {
        $this->isAsyncRequest = $isAsyncRequest;

        return $this;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function setHeaders($headers = [])
    {
        $this->headers = $headers;

        return $this;
    }

    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    public function getIntentByText($q, $params = [])
    {
        $query = array_merge(compact('q'), $params);

        return $this->makeRequest('GET', self::TEXT_INTENT_API, $query);
    }

    //Retrieve the list of all available entities
    public function getEntities()
    {
        return $this->makeRequest('GET', 'entities');
    }

    //Create a new entity
    public function createEntities($id, $doc)
    {
        $data = [
            'id' => $id,
            'doc' => $doc,
        ];
        return $this->makeRequest('POST', 'entities', [], $data);
    }

    //Retrieve all values of an entity
    public function getEntity($id)
    {
        return $this->makeRequest('GET', 'entities/' . $id);
    }

    //Update the information of an entity
    public function updateEntity($id, $data)
    {
        return $this->makeRequest('PUT', 'entities/' . $id, [], $data);
    }

    //Add new values to a keyword entity
    public function addNewValueToEntity($id, $value)
    {
        return $this->makeRequest('POST', 'entities/' . $id . '/values', [], $value);
    }

    //Remove a given value from an entity
    public function removeValueFromEntity($id, $value)
    {
        return $this->makeRequest('DELETE', 'entities/' . $id . '/values/' . $value);
    }

    //Create a new expression for a keyword entity -   POST https://api.wit.ai/entities/$ENTITY_ID/values/$ENTITY_VALUE/expressions
    public function createExpressionToEntity($id, $value, $expression)
    {
        $data = ['expression' => $expression];
        return $this->makeRequest('POST', 'entities/' . $id . '/values/' . $value . '/expressions', [], $data);
    }

    //Remove an expression from an entity
    public function removeExpressionFromEntity($id, $value, $expression)
    {
        return $this->makeRequest('DELETE', 'entities/' . $id . '/values/' . $value . '/expressions/' . $expression);
    }

    //Train the app
    public function train($samples)
    {
        return $this->makeRequest('POST', 'samples', [], $samples);
    }

    protected function makeRequest($method, $uri, $query = [], $data = [])
    {
        $options[GuzzleRequestOptions::QUERY] = $query;
        $options[GuzzleRequestOptions::HEADERS] = $this->getDefaultHeaders();
        if (count($data) > 0) {
            $options[GuzzleRequestOptions::JSON] = $data;
        }
        if ($this->isAsyncRequest) {
            return $this->promises[] = $this->client->requestAsync($method, $uri, $options);
        }
        $this->lastResponse = $this->client->request($method, $uri, $options);

        return json_decode($this->lastResponse->getBody(), true);
    }

    protected function getDefaultHeaders()
    {
        return array_merge([
            'User-Agent' => 'wit-' . self::VERSION,
            'Authorization' => 'Bearer ' . $this->access_token,
            'Accept' => 'application/vnd.wit.' . self::WIT_API_VERSION . '+json',
        ], $this->headers);
    }

    public function getIntentBySpeech($q, $params = [])
    {
        $this->setHeaders(['Content-type' => 'audio/wav']);
        $query = array_merge(compact('q'), $params);

        return $this->makeRequest('POST', self::SPEECH_INTENT_API, $query);
    }

    public function __destruct()
    {
        Promise\unwrap($this->promises);
    }
}
