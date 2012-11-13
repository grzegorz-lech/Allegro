<?php

namespace Shoplo\AllegroBundle\WebAPI;

use Shoplo\AllegroBundle\Entity\User;

class Allegro extends \SoapClient
{
    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $key;

    /**
     * @var int
     */
    private $country;

    public function __construct($key)
    {
        parent::__construct('https://webapi.allegro.pl/uploader.php?wsdl');

        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    public function login(User $user)
    {
        $this->setUsername($user->getUsername());
        $this->setPassword($user->getPassword(true));
        $this->setCountry($user->getCountry());

        try {
            $session = $this->doLoginEnc(
                $this->getUsername(),
                $this->getPassword(),
                $user->getCountry(),
                $this->getKey(),
                $this->getVersion()
            );
        } catch (\SoapFault $sf) {
            return false;
        }

        // TODO: Save session

        return true;
    }

    /**
     * @param int $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        $system  = $this->doQueryAllSysStatus($this->getCountry(), $this->getKey()); // TODO: Cache
        $version = 0;

        foreach ($system as $status) {
            $status = (array)$status;
            if ($this->getCountry() == $status['country-id']) {
                $version = $status['ver-key'];
                break;
            }
        }

        return $version;
    }

    /**
     * @return int
     */
    public function getCountry()
    {
        return $this->country;
    }
}
