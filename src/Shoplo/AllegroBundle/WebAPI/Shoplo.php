<?php

namespace Shoplo\AllegroBundle\WebAPI;

use Symfony\Component\Security\Core\SecurityContext;

class Shoplo extends \OAuth
{
    const GATEWAY = 'http://api.shoplo.com/services';

    public function __construct($key, $secret, SecurityContext $security)
    {
        $token = $security->getToken();
        $token = $token->getAccessToken();

        parent::__construct($key, $secret);

        $this->setToken($token['oauth_token'], $token['oauth_token_secret']);
    }

	/**
     * @param string $uri
     * @param int $id
     * @return array
     * @throws \OAuthException
     */
    public function get($uri, $id = null)
    {
        $url = sprintf('%s/%s', self::GATEWAY, $uri);

        if (null !== $id) {
            $url .= '/' . $id;
        }

        $this->fetch($url);
        $json = $this->getLastResponse();
        $data = json_decode($json, true);

        if (isset($data['status']) && $data['status'] == 'err') {
            throw new \OAuthException($data['error_msg'], $data['error']);
        }

        $data = array_shift($data);

        return $data;
    }

	public function post($uri, $data=array())
	{
		$url = sprintf('%s', self::GATEWAY, $uri);

		$this->fetch($url, $data, 'POST');
		$json = $this->getLastResponse();
		$data = json_decode($json, true);

		if (isset($data['status']) && $data['status'] == 'err') {
			throw new \OAuthException($data['error_msg'], $data['error']);
		}

		$data = array_shift($data);

		return $data;
	}
}
