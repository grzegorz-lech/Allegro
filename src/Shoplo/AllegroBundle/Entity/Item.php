<?php

namespace Shoplo\AllegroBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Item
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Shoplo\AllegroBundle\Entity\ItemRepository")
 */
class Item
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="variant_id", type="integer")
     */
    private $variant_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="quantity", type="integer")
     */
    private $quantity;

    /**
     * @var integer
     *
     * @ORM\Column(name="quantity_sold", type="integer")
     */
    private $quantity_sold = 0;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_at", type="datetimetz")
     */
    private $start_at;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_at", type="datetimetz")
     */
    private $end_at;

    /**
     * @param integer $id
     * @return Item
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
     * Set variant_id
     *
     * @param  integer $variantId
     * @return Item
     */
    public function setVariantId($variantId)
    {
        $this->variant_id = $variantId;

        return $this;
    }

    /**
     * Get variant_id
     *
     * @return integer
     */
    public function getVariantId()
    {
        return $this->variant_id;
    }

    /**
     * Set quantity
     *
     * @param  integer $quantity
     * @return Item
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity
     *
     * @return integer
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set quantity_sold
     *
     * @param  integer $quantitySold
     * @return Item
     */
    public function setQuantitySold($quantitySold)
    {
        $this->quantity_sold = $quantitySold;

        return $this;
    }

    /**
     * Get quantity_sold
     *
     * @return integer
     */
    public function getQuantitySold()
    {
        return $this->quantity_sold;
    }

    /**
     * Set start_at
     *
     * @param  \DateTime $startAt
     * @return Item
     */
    public function setStartAt($startAt)
    {
        $this->start_at = $startAt;

        return $this;
    }

    /**
     * Get start_at
     *
     * @return \DateTime
     */
    public function getStartAt()
    {
        return $this->start_at;
    }

    /**
     * Set end_at
     *
     * @param  \DateTime $endAt
     * @return Item
     */
    public function setEndAt($endAt)
    {
        $this->end_at = $endAt;

        return $this;
    }

    /**
     * Get end_at
     *
     * @return \DateTime
     */
    public function getEndAt()
    {
        return $this->end_at;
    }
}
