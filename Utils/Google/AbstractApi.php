<?php

namespace TradusBundle\Utils\Google;

/**
 * Class AbstractApi.
 */
class AbstractApi
{
    protected $apiKey;

    /**
     * Function for executing a request for the google translation api.
     *
     * @param array $parameters
     *
     * @return mixed
     * @throws InvalidParameterException
     */
    protected function executeRequest(array $parameters, string $url = null)
    {
        $url = ! $url ? static::API_BASE_PATH : $url;
        if ($this->validParameters($parameters)) {
            $parameters[static::API_KEY_PARAMETER] = $this->apiKey;
            $response = file_get_contents($url.'?'.http_build_query($parameters));

            return json_decode($response, true);
        }

        return false;
    }

    /**
     * Function for validating the api parameters.
     *
     * @param array $parameters
     *
     * @return bool
     * @throws InvalidParameterException
     */
    private function validParameters(array $parameters)
    {
        foreach (array_keys($parameters) as $parameter_type) {
            if (! in_array($parameter_type, static::API_PARAMETERS)) {
                throw new InvalidParameterException("Parameter $parameter_type is not a valid parameter.");
            }
        }

        return true;
    }
}
