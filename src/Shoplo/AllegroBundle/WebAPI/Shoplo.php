<?php

namespace Shoplo\AllegroBundle\WebAPI;

use Symfony\Component\Security\Core\SecurityContext;

class Shoplo extends \OAuth
{
    const GATEWAY = 'http://api.shoplo.com/services';

	protected $bucket = array();

    public function __construct($key, $secret, SecurityContext $security)
    {
        $token = $security->getToken();
        $token = $token->getAccessToken();

        parent::__construct($key, $secret);

        $this->setToken($token['oauth_token'], $token['oauth_token_secret']);
    }

    /**
     * @param  string          $uri
     * @param  int             $id
     * @return array
     * @throws \OAuthException
     */
    public function get($uri, $id = null, $data=array())
    {
        $url = sprintf('%s/%s', self::GATEWAY, $uri);

        if (null !== $id) {
            $url .= '/' . $id;
        }


		if ( isset($this->bucket[$url]) )
		{
			return $this->bucket[$url];
		}


        $this->fetch($url, $data);
        $json = $this->getLastResponse();
        $data = json_decode($json, true);
        if (isset($data['status']) && $data['status'] == 'err') {
            throw new \OAuthException($data['error_msg'], $data['error']);
        }
        $data = array_shift($data);


		if ( !isset($this->bucket[$url]) )
		{
			$this->bucket[$url] = $data;
		}

        return $data;
    }

    public function post($uri, $data = array())
    {
        $url = sprintf('%s/%s', self::GATEWAY, $uri);

        $this->fetch($url, http_build_query($data), OAUTH_HTTP_METHOD_POST);//, array('Connection'=>'close'));
        $json = $this->getLastResponse();
        $data = json_decode($json, true);

        if (isset($data['status']) && $data['status'] == 'err') {
			throw new \OAuthException('Msga: '.$data['error_msg'], $data['error']);
        }

        return $data;
    }
}
