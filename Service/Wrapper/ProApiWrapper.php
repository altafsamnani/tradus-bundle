<?php

namespace TradusBundle\Service\Wrapper;

use Exception;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

class ProApiWrapper
{
    private $endpoint;
    private $auth_token;
    private $curl;

    public function __construct($options = [])
    {
        $this->endpoint = $options['endpoint'];

        if (! empty($options['auth_token'])) {
            $this->auth_token = $options['auth_token'];
        }
        $this->curl = curl_init();
    }

    public function get($method, $args = [])
    {
        return $this->makeRequest('get', $method, $args);
    }

    private function makeRequest($http_verb, $method, $args = [])
    {
        $url = "{$this->endpoint}/{$method}";
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: '.$this->auth_token,
        ];
        $query = http_build_query($args, '', '&');
        if (! empty($query)) {
            $url .= "?{$query}";
        }

        curl_setopt_array($this->curl, [
        CURLOPT_URL => $url,
        CURLOPT_HTTPGET => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 300,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => strtoupper($http_verb),
        CURLOPT_POSTFIELDS => '',
        CURLOPT_HTTPHEADER => $headers,
        ]);

        $ret = curl_exec($this->curl);
        $response = json_decode($ret, true);
        $err = curl_error($this->curl);

        $status_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

        switch ($status_code) {
            case 405:
                throw new MethodNotAllowedException([]);
                break;
            case 404:
                if (! empty($response['message'])) {
                    $msg = $response['message'];
                } else {
                    $msg = 'not found';
                }

                throw new NotFoundHttpException(sprintf('[PRO API ERROR] [%s] %s: %s', strtoupper($http_verb), $method, $msg));
                break;
            case 400:
                if (! empty($response['error'])) {
                    throw new BadRequestHttpException(sprintf('[PRO API ERROR] [%s] %s: %s', strtoupper($http_verb), $method, $response['error']));
                }

                if (! empty($response['message'])) {
                    throw new BadRequestHttpException(sprintf('[PRO API ERROR] [%s] %s: %s', strtoupper($http_verb), $method, $response['message']));
                }

                throw new BadRequestHttpException(sprintf('[PRO API ERROR] [%s] %s: %s', strtoupper($http_verb), $method, 'Bad Request'));
                break;
            case 500:
                $error_msg = sprintf(
                    '%s: [%s] /%s: %s',
                    $status_code,
                    strtoupper($http_verb),
                    $method,
                    $response['message']
                );
                throw new Exception($error_msg);
                break;
            default:
                break;
        }

        if ($err) {
            throw new Exception(sprintf('[PRO API ERROR] [%s] %s: %s', strtoupper($http_verb), $method, 'cURL Error #:'.$err), 1);
        }

        if (! empty($response['error'])) {
            if (! empty($response['error']['exception'])) {
                $exceptions = $response['error']['exception'];
                $msg = $exceptions[0]['message'];
            } else {
                $msg = $response['error'];
            }

            throw new Exception(sprintf('[PRO API ERROR] [%s] %s: %s', strtoupper($http_verb), $method, "API Error\n".$msg), 1);
        }

        return $response;
    }
}
