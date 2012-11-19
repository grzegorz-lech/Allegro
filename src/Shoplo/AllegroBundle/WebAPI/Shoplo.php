<?php

namespace Shoplo\AllegroBundle\WebAPI;

use Symfony\Component\Security\Core\SecurityContext;

class Shoplo extends \OAuth
{
    const GATEWAY = 'https://api.shoplo.com/services';

    public function __construct($key, $secret, SecurityContext $security)
    {
        $token = $security->getToken();
        $token = $token->getAccessToken();

        // TODO: Read key/secret from config
        parent::__construct($key, $secret);

        $this->setToken($token['oauth_token'], $token['oauth_token_secret']);
    }

    public function get($uri)
    {
        $url = sprintf('%s/%s', self::GATEWAY, $uri);
        $this->fetch($url);
        $json = $this->getLastResponse();
        $data = json_decode($json, true);

        return $data[$uri];
    }
}
