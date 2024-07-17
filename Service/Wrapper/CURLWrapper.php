<?php

namespace TradusBundle\Service\Wrapper;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CURLWrapper
{
    private $url;
    private $method;
    private $body;
    private $contentType;
    private $headers;
    private $curlObject;
    private $curlOptions;
    const OK_RESPONSES = [200, 201, 204];

    public function __construct(
        string $url,
        string $method,
        string $contentType = null,
        $body = null,
        array $headers = null
    ) {
        $this->url = $url;
        $this->method = strtolower($method);
        $this->body = $body;
        $this->headers = $headers;
        $this->contentType = strtolower($contentType);
        $this->curlObject = curl_init();

        $this->curlOptions = [
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => strtoupper($this->method),
            CURLOPT_POSTFIELDS => $this->body,
            CURLOPT_HTTPHEADER => $this->parseHeaders($this->headers),
        ];

        $this->curlOptions[CURLOPT_HTTPHEADER] = $this->parseHeaders(
            $this->headers + ['Content-Length' => strlen($this->body)]
        );
    }

    public function exec()
    {
        curl_setopt_array(
            $this->curlObject,
            $this->curlOptions
        );

        switch ($this->method) {
            case 'post':
                curl_setopt($this->curlObject, CURLOPT_POSTFIELDS, $this->body);
                curl_setopt($this->curlObject, CURLOPT_POST, true);
                break;
            //Create extra methods here if needed...
        }

        $execution = curl_exec($this->curlObject);
        if ($execution === 'Permission denied') {
            throw new AccessDeniedHttpException($execution);
        }

        if ($execution === 'The website encountered an unexpected error. Please try again later.<br />') {
            throw new \Exception($execution);
        }

        $response = json_decode($execution, true);

        $err = curl_error($this->curlObject);
        $statusCode = curl_getinfo($this->curlObject, CURLINFO_HTTP_CODE);

        if (! in_array($statusCode, self::OK_RESPONSES)) {
            $error = $err;
            if (isset($response['message'])) {
                $error = $response['message'];
            } else {
                if (isset($response['error']) && isset($response['error']['exception'])) {
                    $error = $response['error']['exception'][0]['message'];
                }
            }

            $response = [
                'status' => $statusCode,
                'error' => $error,
            ];
        }

        return $response;
    }

    public function enableRedirectFollow()
    {
        $this->curlOptions[CURLOPT_FOLLOWLOCATION] = true;
    }

    private function parseHeaders($headers)
    {
        $result = [];
        foreach ($headers as $k=>$v) {
            $result[] = $k.':'.$v;
        }

        return $result;
    }
}
