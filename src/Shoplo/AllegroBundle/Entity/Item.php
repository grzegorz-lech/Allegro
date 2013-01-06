<?php

namespace Shoplo\AllegroBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Shoplo\AllegroBundle\WebAPI\Shoplo;

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
     * @ORM\Column(name="user_id", type="integer")
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="variant_id", type="integer")
     */
    private $variant_id;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="product_id", type="integer")
	 */
	private $product_id;

	/**
	 * @var float
	 *
	 * @ORM\Column(name="price", type="float")
	 */
	private $price;

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
     * @param  integer $id
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
     * Set user
     *
     * @param  User $user
     * @return Item
     */
    public function setUser(User $user)
    {
        $this->user_id = $user->getId();

        return $this;
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
	 * Set product_id
	 *
	 * @param  integer $productId
	 * @return Item
	 */
	public function setProductId($productId)
	{
		$this->product_id = $productId;

		return $this;
	}

	/**
	 * Get product_id
	 *
	 * @return integer
	 */
	public function getProductId()
	{
		return $this->product_id;
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

	/**
	 * @param int $price
     * @return Item
	 */
	public function setPrice($price)
	{
		$this->price = $price;

        return $this;
	}

	/**
	 * @return int
	 */
	public function getPrice()
	{
		return $this->price;
	}

    /**
     * Get Product Variant
     *
     * @param Shoplo $shoplo
     * @return array
     */
	public function getVariant(Shoplo $shoplo)
	{
		return $shoplo->get('products/'.$this->product_id.'/variants/'.$this->variant_id);
	}

    /**
     * Get Product
     *
     * @param Shoplo $shoplo
     * @return array
     */
	public function getProduct(Shoplo $shoplo)
	{
		return $shoplo->get('products/'.$this->product_id);
	}
}
