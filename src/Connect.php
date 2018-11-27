<?php

namespace Fssp;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;

class Connect
{
    const API_POINT = 'https://api-ip.fssprus.ru/api/v1.0';
    protected $token;
    protected $method;
    protected $params;
    protected $task;
    private $httpClient;
    private $httpParams = [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ],
        'connect_timeout' => 15,
        'timeout' => 25,
        'verify' => false,
    ];
    private $lastResponse;
    private $availableStatusCodes = [400, 401, 200];

    protected function __construct($token)
    {
        $this->token = $token;
        $this->httpClient = new Client();
    }

    public function task()
    {
        return $this->task;
    }

    public function isSuccess()
    {
        return (!$this->lastResponse || empty($this->lastResponse['code']));
    }

    public function isError()
    {
        return ($this->lastResponse && !empty($this->lastResponse['code']));
    }

    public function lastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * @param array $params
     * @return mixed
     * @throws GuzzleException
     */
    protected function get(array $params)
    {
        $httpParams = $this->httpParams;
        $httpParams['query'] = [
            'token' => $this->token,
        ];
        if (!empty($params)) {
            $httpParams['query'] = array_merge($httpParams['query'], $params);
        }
        $request = new Request('GET', self::API_POINT . $this->method);
        return $this->send($request, $httpParams);
    }

    /**
     * @param array $params
     * @return array
     * @throws GuzzleException
     */
    protected function post(array $params)
    {
        $httpParams = $this->httpParams;
        $httpParams['form_params'] = $params;
        $httpParams['form_params']['token'] = $this->token;
        // fixme: временный костыль. Разбираемся с ТП. Не работает, если не послать token в GET-параметрах
        $httpParams['query'] = [
            'token' => $this->token,
        ];
        $request = new Request('POST', self::API_POINT . $this->method);
        return $this->send($request, $httpParams);
    }

    /**
     * @param $request
     * @param $httpParams
     * @return mixed
     * @throws GuzzleException
     */
    private function send($request, $httpParams)
    {
        $response = $this->httpClient->send($request, $httpParams);
        if (in_array($response->getStatusCode(), $this->availableStatusCodes)) {
            $this->lastResponse = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
            if (!empty($this->lastResponse['task'])) {
                $this->task = $this->lastResponse['task'];
            }
            return $this->lastResponse;
        }
        throw new BadResponseException('Bad response', $request, $response);
    }
}