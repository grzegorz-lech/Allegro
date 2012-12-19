<?php

namespace Shoplo\AllegroBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * CategoryAllegro
 *
 * @ORM\Table(indexes={@ORM\Index(name="country", columns={"country_id"})})
 * @ORM\Entity(repositoryClass="Shoplo\AllegroBundle\Entity\CategoryAllegroRepository")
 */
class CategoryAllegro
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="CategoryAllegro", mappedBy="parent")
     */
    private $children;

    /**
     * @ORM\ManyToOne(targetEntity="CategoryAllegro", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent;

    /**
     * @var integer
     *
     * @ORM\Column(name="position", type="integer")
     */
    private $position;

    /**
     * @var integer
     *
     * @ORM\Column(name="country_id", type="integer")
     */
    private $country_id;

    /**
     * @return CategoryAllegro
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return CategoryAllegro
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * Set name
     *
     * @param string $name
     * @return CategoryAllegro
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set parent
     *
     * @param CategoryAllegro $parent
     * @return CategoryAllegro
     */
    public function setParent(CategoryAllegro $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return CategoryAllegro
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set position
     *
     * @param integer $position
     * @return CategoryAllegro
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return integer
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set country_id
     *
     * @param integer $countryId
     * @return CategoryAllegro
     */
    public function setCountryId($countryId)
    {
        $this->country_id = $countryId;

        return $this;
    }

    /**
     * Get country_id
     *
     * @return integer
     */
    public function getCountryId()
    {
        return $this->country_id;
    }
}
