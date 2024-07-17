<?php

//curl -X POST 'http://127.0.0.1:8983/solr/tradus/update?versions=true&commit=true' -H "Content-Type: text/xml" --data-binary '<delete><query>*:*</query></delete>'

namespace TradusBundle\Service\Wrapper;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use TradusBundle\Entity\Sitecodes;
use TradusBundle\Service\Sitecode\SitecodeService;

class SolrWrapper
{
    private $endpoint;
    private $curl;
    private $autocommit;
    private $sellerCase;

    public function __construct($options = [])
    {
        $this->endpoint = $options['endpoint'];
        $this->curl = curl_init();
        $this->autocommit = false;
        if (isset($options['autocommit']) && $options['autocommit'] === true) {
            $this->autocommit = true;
        }
        if (! empty($options['sellerCase'])) {
            $this->sellerCase = true;
        }
    }

    public function get($method, $args = [], $timeout = 10)
    {
        return $this->makeRequest('get', $method, $args, $timeout);
    }

    public function post($method, $args = [], $timeout = 10)
    {
        return $this->makeRequest('post', $method, $args, $timeout);
    }

    public function delete($method, $args = [], $timeout = 10)
    {
        return $this->makeRequest('delete', $method, $args, $timeout);
    }

    public function parseWhitelabel(string $q): string
    {
        $ssc = new SitecodeService();
        $siteId = $ssc->getSitecodeId() ? $ssc->getSitecodeId() : Sitecodes::SITECODE_TRADUS;
        $sitecodeQuery = 'site_facet_m_int:'.$siteId;

        if ($q == '*:*') {
            return $sitecodeQuery;
        }

        $temp = explode('site_facet_m_int', $q);
        if (count($temp) <= 1) {
            return $q.' AND '.$sitecodeQuery;
        }

        return $q;
    }

    private function makeRequest($http_verb, $method, $args = [], $timeout = 10)
    {
        $time_start = microtime(true);
        $url = "{$this->endpoint}/{$method}";
        $facet = 'facet.field=make&facet.field=category&facet.field=seller_country&facet.field=year';
        $stats_get = 'stats=true&stats.field=price';
        $indexId = 'offer_id';
        if ($this->sellerCase) {
            $facet = 'facet.field=category&facet.field=country';
            $stats_get = 'stats=true';
            $indexId = 'seller_id';
        }

//        $curl = curl_init();

        curl_setopt_array($this->curl, [
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
            ],
        ]);

        $commit = ($this->autocommit ? '?versions=true&commit=true' : '');
        switch ($http_verb) {
            case 'post':
                curl_setopt($this->curl, CURLOPT_POST, true);
                $this->attachRequestPayload($this->curl, $args);
                curl_setopt($this->curl, CURLOPT_URL, $url.$commit);

                $ret = curl_exec($this->curl);
                $err = curl_error($this->curl);
//                curl_close($curl);

                if ($err) {
                    throw new Exception('cURL Error #:'.$err, 1);
                }

                return json_decode($ret, true);

                break;

            case 'get':
                $args['wt'] = 'json';
                $args['facet'] = 'on';
                $query = http_build_query($args, '', '&');
                $query .= '&'.$stats_get;
                $query .= '&'.$facet;

                $q = isset($args['q']) ? $args['q'] : '*:*';
                if (gettype($args) == 'array') {
                    $args['q'] = $this->parseWhitelabel($q);
                }
                // we reuse the connection make sure the method is correct
                curl_setopt($this->curl, CURLOPT_HTTPGET, true);
                curl_setopt($this->curl, CURLOPT_URL, $url.'?'.$query);
                break;

            case 'delete':
                $var = '<delete><query>'.$indexId.':'.$args.'</query></delete>';
                $header = ['Content-type:text/xml; charset=utf-8'];

                curl_setopt($this->curl, CURLOPT_POST, true);
                curl_setopt($this->curl, CURLOPT_URL, $url.$commit);
                curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header);
                curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($this->curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, $var);
                curl_setopt($this->curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                curl_setopt($this->curl, CURLINFO_HEADER_OUT, 1);

                $ret = curl_exec($this->curl);
                $err = curl_error($this->curl);

//                curl_close($curl);

                if ($err) {
                    throw new Exception('cURL Error #:'.$err, 1);
                }

                return Response::HTTP_OK;

                break;
        }

        $ret = curl_exec($this->curl);

        $response = json_decode($ret, true);
        $err = curl_error($this->curl);

        $status_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

        if ($status_code != Response::HTTP_OK) {
            throw new HttpException($status_code, $response['error']['msg']);
        }

//        curl_close($curl);

        if ($err) {
            throw new Exception('cURL Error #:'.$err, 1);
        }
        $result_count = $response['response']['numFound'];

        $doc_ids = [];
        foreach ($response['response']['docs'] as $doc) {
            $id = $doc[$indexId];
            $doc_ids[] = $id;
        }
        $data[$indexId.'s'] = $doc_ids;
        $data['result_count'] = $result_count;
        $data['response'] = $response['response'];
        $data['facet'] = $response['facet_counts'];
        $data['stats'] = $response['stats'];
        $time_end = microtime(true);

        $data['exec'] = ($time_end - $time_start);

        return $data;
    }

    private function attachRequestPayload(&$ch, $data)
    {
        $encoded = json_encode($data);
        $this->last_request['body'] = $encoded;

        curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
    }
}
