<?php

namespace Billplz;

use GuzzleHttp\Psr7\Uri;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Client\Common\HttpMethodsClient as HttpClient;

class Client
{
    /**
     * Http Client instance.
     *
     * @var \Http\Client\Common\HttpMethodsClient
     */
    protected $http;

    /**
     * Billplz API Key.
     *
     * @var string
     */
    protected $apiKey;

    /**
     * Billplz API endpoint.
     *
     * @var string
     */
    protected $endpoint = 'https://www.billplz.com/api';

    protected $supportedVersions = [
        'v3' => 'Three',
    ];

    /**
     * Construct a new Billplz Client.
     *
     * @param \Http\Client\Common\HttpMethodsClient  $http
     * @param string  $apiKey
     */
    public function __construct(HttpClient $http, $apiKey)
    {
        $this->http   = $http;
        $this->apiKey = $apiKey;
    }

    /**
     * Make a client.
     *
     * @param string  $apiKey
     *
     * @return $this
     */
    public static function make($apiKey)
    {
        $client = new HttpClient(
            HttpClientDiscovery::find(),
            MessageFactoryDiscovery::find()
        );

        return new static($client, $apiKey);
    }

    /**
     * Use sandbox environment.
     *
     * @return $this
     */
    public function useSandbox()
    {
        return $this->useCustomEndpoint('https://billplz-staging.herokuapp.com/api');
    }

    /**
     * Use custom endpoint.
     *
     * @param  string  $endpoint
     *
     * @return $this
     */
    public function useCustomEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    /**
     * Get collection resource.
     *
     * @param  string  $version
     *
     * @return object
     */
    public function collection($version = 'v3')
    {
        return $this->getVersionedResource($version, 'Collection');
    }

    /**
     * Get bill resource.
     *
     * @param  string  $version
     *
     * @return object
     */
    public function bill($version = 'v3')
    {
        return $this->getVersionedResource($version, 'Bill');
    }

    /**
     * Send the HTTP request.
     *
     * @param  string  $method
     * @param  \Psr\Http\Message\UriInterface|string  $url
     * @param  array  $headers
     * @param  array  $data
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function send($method, $url, $headers = [], $data = [])
    {
        $uri = (new Uri($this->endpoint.'/'.$url))
                    ->withUserInfo($this->apiKey);

        $headers = $this->prepareRequestHeaders($headers);
        $body    = $this->prepareRequestBody($data, $headers);

        return $this->http->send($method, $uri, $headers, $body);
    }

    /**
     * Prepare request body.
     *
     * @param  mixed  $body
     * @param  array  $headers
     *
     * @return string
     */
    protected function prepareRequestBody($body = [], array $headers = [])
    {
        if (isset($headers['Content-Type']) && $headers['Content-Type'] == 'application/json') {
            return json_encode($body);
        }

        return http_build_query($body, null, '&');
    }

    /**
     * Prepare request headers.
     *
     * @param  array  $headers
     *
     * @return array
     */
    protected function prepareRequestHeaders(array $headers = [])
    {
        return $headers;
    }

    /**
     * Get versioned resource (service).
     *
     * @param  string  $version
     * @param  string  $service
     *
     * @throws \InvalidArgumentException
     *
     * @return object
     */
    protected function getVersionedResource($version, $service)
    {
        if (! array_key_exists($version, $this->supportedVersions)) {
            throw new InvalidArgumentException("API version {$version} is not supported");
        }

        $class = sprintf('%s\%s\%s', __NAMESPACE__, $this->supportedVersions[$version], $service);

        return new $class($this);
    }
}
