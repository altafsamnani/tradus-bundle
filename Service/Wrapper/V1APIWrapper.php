<?php

namespace TradusBundle\Service\Wrapper;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class V1APIWrapper
{
    private $endpoint;

    public function __construct($options = [])
    {
        $this->endpoint = $options['endpoint'];
    }

    public function delete($method, $args = [], $timeout = 10)
    {
        return $this->makeRequest('delete', $method, $args, $timeout);
    }

    public function get($method, $args = [], $timeout = 10)
    {
        $args['_format'] = 'json';

        return $this->makeRequest('get', $method, $args, $timeout);
    }

    public function patch($method, $args = [], $timeout = 10)
    {
        return $this->makeRequest('patch', $method, $args, $timeout);
    }

    public function post($method, $args = [], $timeout = 10)
    {
        return $this->makeRequest('post', $method, $args, $timeout);
    }

    public function put($method, $args = [], $timeout = 10)
    {
        return $this->makeRequest('put', $method, $args, $timeout);
    }

    private function makeRequest($http_verb, $method, $args = [], $timeout = 10)
    {
        $url = "{$this->endpoint}/{$method}";

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => strtoupper($http_verb),
            CURLOPT_POSTFIELDS => '',
            CURLOPT_HTTPHEADER => [
            'content-type: application/json',
            'token: i216ovlB%T$tI/!wswhkn0nir1wtYIGU#4&cmQz$V13nruYe{Lo2vtwQSm?a2BJ98A>JcS2+_kEdeJrgNA2b!1MXSJQgxd$B0A45',
            ],
        ]);

        switch ($http_verb) {
            case 'post':
                curl_setopt($curl, CURLOPT_POST, true);
                $this->attachRequestPayload($curl, $args);
                curl_setopt($curl, CURLOPT_URL, $url);
                break;

            case 'get':
                $query = http_build_query($args, '', '&');
                curl_setopt($curl, CURLOPT_URL, $url.'?'.$query);
                break;

            case 'delete':
                curl_setopt($curl, CURLOPT_POST, true);
                $this->attachRequestPayload($curl, $args);
                curl_setopt($curl, CURLOPT_URL, $url);
                break;

            case 'patch':
                curl_setopt($curl, CURLOPT_POST, true);
                $this->attachRequestPayload($curl, $args);
                curl_setopt($curl, CURLOPT_URL, $url);
                break;

            case 'put':
                curl_setopt($curl, CURLOPT_POST, true);
                $this->attachRequestPayload($curl, $args);
                curl_setopt($curl, CURLOPT_URL, $url);
                break;
        }

        $ret = curl_exec($curl);
        if ($ret == 'Permission denied') {
            throw new AccessDeniedHttpException($ret);
        }

        if ($ret == 'The website encountered an unexpected error. Please try again later.<br />') {
            throw new \Exception($ret);
        }

        $response = json_decode($ret, true);

        $err = curl_error($curl);

        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        switch ($status_code) {
            case 400:
                throw new BadRequestHttpException($response['message']);
                break;

            case 404:
                throw new NotFoundHttpException($response['message']);
                break;

            case 405:
                throw new HttpException(405, 'Method not allowed');
                break;

            case 406:
                throw new HttpException(422, $response['message']);
                break;

            default:
                break;
        }

        if ($status_code != 200 && $status_code != 204) {
            throw new HttpException($status_code);
        }

        curl_close($curl);

        if ($err) {
            throw new \Exception('cURL Error #:'.$err, 1);
        }

        if (! empty($response['error'])) {
            if (! empty($response['error']['exception'])) {
                $exceptions = $response['error']['exception'];
                $msg = $exceptions[0]['message'];
            } else {
                $msg = $response['error'];
            }

            throw new \Exception("API Error\n".$msg, 1);
        }

        return $response;
    }

    private function attachRequestPayload(&$ch, $data)
    {
        $encoded = json_encode($data);
        $this->last_request['body'] = $encoded;

        curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
    }
}
