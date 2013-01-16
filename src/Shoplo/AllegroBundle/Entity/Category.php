<?php

namespace Shoplo\AllegroBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Category
 *
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(name="shop_shoplo", columns={"shop_id", "shoplo_id"})}))
 * @ORM\Entity(repositoryClass="Shoplo\AllegroBundle\Entity\CategoryRepository")
 */
class Category
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
     * @var integer
     *
     * @ORM\Column(name="shop_id", type="integer")
     */
    private $shop_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="allegro_id", type="integer")
     */
    private $allegro_id;

    /**
     * @var string
     *
     * @ORM\Column(name="allegro_name", type="string", length=255)
     */
    private $allegro_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="allegro_parent", type="integer")
     */
    private $allegro_parent;

    /**
     * @var integer
     *
     * @ORM\Column(name="allegro_position", type="integer")
     */
    private $allegro_position;

    /**
     * @var integer
     *
     * @ORM\Column(name="shoplo_id", type="integer")
     */
    private $shoplo_id;

    /**
     * @var string
     *
     * @ORM\Column(name="shoplo_name", type="string", length=255)
     */
    private $shoplo_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="shoplo_parent", type="integer")
     */
    private $shoplo_parent;

    /**
     * @var integer
     *
     * @ORM\Column(name="shoplo_position", type="integer")
     */
    private $shoplo_position;

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
     * @param int $shop_id
     */
    public function setShopId($shopId)
    {
        $this->shop_id = $shopId;

        return $this;
    }

    /**
     * @return int
     */
    public function getShopId()
    {
        return $this->shop_id;
    }

    /**
     * Set allegro_id
     *
     * @param  integer  $allegroId
     * @return Category
     */
    public function setAllegroId($allegroId)
    {
        $this->allegro_id = $allegroId;

        return $this;
    }

    /**
     * Get allegro_id
     *
     * @return integer
     */
    public function getAllegroId()
    {
        return $this->allegro_id;
    }

    /**
     * Set allegro_name
     *
     * @param  string   $allegroName
     * @return Category
     */
    public function setAllegroName($allegroName)
    {
        $this->allegro_name = $allegroName;

        return $this;
    }

    /**
     * Get allegro_name
     *
     * @return string
     */
    public function getAllegroName()
    {
        return $this->allegro_name;
    }

    /**
     * Set allegro_parent
     *
     * @param  integer  $allegroParent
     * @return Category
     */
    public function setAllegroParent($allegroParent)
    {
        $this->allegro_parent = $allegroParent;

        return $this;
    }

    /**
     * Get allegro_parent
     *
     * @return integer
     */
    public function getAllegroParent()
    {
        return $this->allegro_parent;
    }

    /**
     * Set allegro_position
     *
     * @param  integer  $allegroPosition
     * @return Category
     */
    public function setAllegroPosition($allegroPosition)
    {
        $this->allegro_position = $allegroPosition;

        return $this;
    }

    /**
     * Get allegro_position
     *
     * @return integer
     */
    public function getAllegroPosition()
    {
        return $this->allegro_position;
    }

    /**
     * Set shoplo_id
     *
     * @param  integer  $shoploId
     * @return Category
     */
    public function setShoploId($shoploId)
    {
        $this->shoplo_id = $shoploId;

        return $this;
    }

    /**
     * Get shoplo_id
     *
     * @return integer
     */
    public function getShoploId()
    {
        return $this->shoplo_id;
    }

    /**
     * Set shoplo_name
     *
     * @param  string   $shoploName
     * @return Category
     */
    public function setShoploName($shoploName)
    {
        $this->shoplo_name = $shoploName;

        return $this;
    }

    /**
     * Get shoplo_name
     *
     * @return string
     */
    public function getShoploName()
    {
        return $this->shoplo_name;
    }

    /**
     * Set shoplo_parent
     *
     * @param  integer  $shoploParent
     * @return Category
     */
    public function setShoploParent($shoploParent)
    {
        $this->shoplo_parent = $shoploParent;

        return $this;
    }

    /**
     * Get shoplo_parent
     *
     * @return integer
     */
    public function getShoploParent()
    {
        return $this->shoplo_parent;
    }

    /**
     * Set shoplo_position
     *
     * @param  integer  $shoploPosition
     * @return Category
     */
    public function setShoploPosition($shoploPosition)
    {
        $this->shoplo_position = $shoploPosition;

        return $this;
    }

    /**
     * Get shoplo_position
     *
     * @return integer
     */
    public function getShoploPosition()
    {
        return $this->shoplo_position;
    }
}
