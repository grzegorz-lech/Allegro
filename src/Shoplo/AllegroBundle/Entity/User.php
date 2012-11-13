<?php

namespace Shoplo\AllegroBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Shoplo\AllegroBundle\Entity\UserRepository")
 */
class User
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=16)
     *
     * @Assert\NotBlank()
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=64)
     *
     * @Assert\NotBlank()
     */
    private $password;

    /**
     * @var integer
     *
     * @ORM\Column(name="country", type="integer")
     *
     * @Assert\NotBlank()
     */
    private $country;

    /**
     * @param int $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @return int
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = hash('sha256', $password);

        return $this;
    }

    /**
     * Get password
     *
     * @param bool $encoded
     * @return string
     */
    public function getPassword($encoded = false)
    {
        if (!$encoded) {
            return $this->password;
        }

        $length   = strlen($this->password);
        $password = '';

        for ($i = 0; $i < $length - 1; $i += 2) {
            $password .= chr(hexdec($this->password[$i] . $this->password[$i + 1]));
        }

        $password = base64_encode($password);

        return $password;
    }
}
