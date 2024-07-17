<?php

namespace TradusBundle\Service\Utils;

use AppBundle\Service\AuthorizationService;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;

class UserRequestService
{
    /** @var SanitizeClass */
    protected $sanitizeClass;

    /** @var FOSRestController */
    protected $controller;

    public function __construct(FOSRestController $controller, SanitizeClass $sanitizeClass)
    {
        $this->sanitizeClass = $sanitizeClass;
        $this->controller = $controller;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getUserAgent(Request $request)
    {
        $ua = $this->sanitizeClass->onlyString($request->get('client_agent'));
        $isApp = AuthorizationService::tokenIsMobile($this->controller);

        if (($isApp || ! $ua) && isset($_SERVER['HTTP_USER_AGENT'])) {
            return $this->sanitizeClass->onlyString($_SERVER['HTTP_USER_AGENT']);
        }

        return $ua;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getIp(Request $request)
    {
        $ip = $this->sanitizeClass->onlyIp($request->get('ip'));
        $isApp = AuthorizationService::tokenIsMobile($this->controller);

        if (($isApp || ! $ip) && isset($_SERVER['REMOTE_ADDR'])) {
            return $this->sanitizeClass->onlyIp($_SERVER['REMOTE_ADDR']);
        }

        return $ip;
    }
}
