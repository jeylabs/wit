<?php

namespace Jeylabs\Wit;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\PromiseInterface;
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

    protected function makeRequest($method, $uri, $query = [])
    {
        $options[GuzzleRequestOptions::QUERY] = $query;
        $options[GuzzleRequestOptions::HEADERS] = $this->getDefaultHeaders();
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
