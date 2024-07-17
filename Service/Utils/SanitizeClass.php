<?php

namespace TradusBundle\Service\Utils;

class SanitizeClass
{
    /*
     * Apply this to strings type, will allow numbers and special chars
     * */
    public function onlyString($v, $sub = null)
    {
        if ($sub) {
            if (strlen($v) > $sub) {
                $v = substr($v, 0, $sub);
            }
        }

        return filter_var($v, FILTER_SANITIZE_STRING);
    }

    /*
     * Apply this to integers
     * */
    public function onlyInteger($v)
    {
        return filter_var($v, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Apply this to floats.
     *
     * @param $v
     * @return mixed
     */
    public function onlyFloat($v)
    {
        return filter_var($v, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /*
     * Apply this to bool type
     * */
    public function onlyBool($v)
    {
        return filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    /*
     * Apply this to emails
     * */
    public function onlyEmail($v)
    {
        return filter_var($v, FILTER_SANITIZE_EMAIL);
    }

    /**
     * Apply this to Arrays.
     *
     * @param $v
     * @return mixed
     */
    public function onlyArray($v)
    {
        return filter_var($v, FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY);
    }

    /**
     * Apply this to Arrays.
     *
     * @param $v
     * @return mixed
     */
    public function onlyIntegerArray($v)
    {
        return array_values(array_filter(filter_var($v, FILTER_SANITIZE_NUMBER_INT, FILTER_REQUIRE_ARRAY)));
    }

    /**
     * Apply this to IPs.
     *
     * @param $v
     * @return mixed
     */
    public function onlyIp($ip)
    {
        $r = [];
        $array = explode(',', $ip);
        foreach ($array as $eval) {
            if (filter_var(trim($eval), FILTER_VALIDATE_IP)) {
                $r[] = filter_var(trim($eval), FILTER_VALIDATE_IP);
            }
        }

        return implode(',', $r);
    }

    /*
     * Function sanitizeGeoLocation
     * @param $jsonString
     * @return string
     */
    public function sanitizeGeolocation($jsonString): string
    {
        $result = [];
        $jsonObject = json_decode($jsonString);
        $result['latitude'] = 0;
        $result['longitude'] = 0;
        if (isset($jsonObject->latitude)) {
            $result['latitude'] = is_numeric($jsonObject->latitude) ? $jsonObject->latitude : 0;
        }
        if (isset($jsonObject->longitude)) {
            $result['longitude'] = is_numeric($jsonObject->longitude) ? $jsonObject->longitude : 0;
        }
        if (isset($jsonObject->city)) { //this one is optional
            $result['city'] = $this->onlyString($jsonObject->city, 100);
        }

        return json_encode($result);
    }
}
