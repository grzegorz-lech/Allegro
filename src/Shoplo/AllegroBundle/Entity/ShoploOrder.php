<?php

namespace Shoplo\AllegroBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Shoplo\AllegroBundle\WebAPI\Shoplo;

/**
 * Item
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Shoplo\AllegroBundle\Entity\ShoploOrderRepository")
 */
class ShoploOrder
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
	 * @ORM\GeneratedValue(strategy="AUTO")
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
     * @ORM\Column(name="order_id", type="integer")
     */
    private $order_id;

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
     * @var integer
     *
     * @ORM\Column(name="price", type="integer")
     */
    private $price;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="shipping_title", type="string", length=255)
	 */
	private $shipping_title;

    /**
     * @var integer
     *
     * @ORM\Column(name="shipping_price", type="integer")
     */
    private $shipping_price;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_title", type="string", length=255)
     */
    private $payment_title;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="referring_site", type="string", length=255)
	 */
	private $referring_site;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="landing_site", type="string", length=255)
	 */
	private $landing_site;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="notes", type="text", nullable=true)
	 */
	private $notes;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="customer_email", type="string", length=255)
	 */
	private $customer_email;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="customer_phone", type="string", length=255)
	 */
	private $customer_phone;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="shipping_name", type="string", length=255)
	 */
	private $shipping_name;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="shipping_address_1", type="string", length=255)
	 */
	private $shipping_address_1;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="shipping_city", type="string", length=255)
	 */
	private $shipping_city;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="shipping_zip_code", type="string", length=8)
	 */
	private $shipping_zip_code;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="shipping_country_code", type="string", length=2)
	 */
	private $shipping_country_code;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="shipping_phone", type="string", length=24, nullable=true)
	 */
	private $shipping_phone;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="billing_name", type="string", length=255, nullable=true)
	 */
	private $billing_name;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="billing_address_1", type="string", length=255, nullable=true)
	 */
	private $billing_address_1;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="billing_city", type="string", length=255, nullable=true)
	 */
	private $billing_city;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="billing_zip_code", type="string", length=8, nullable=true)
	 */
	private $billing_zip_code;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="billing_country_code", type="string", length=2, nullable=true)
	 */
	private $billing_country_code;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="billing_tax_id", type="string", length=16, nullable=true)
	 */
	private $billing_tax_id;

	/**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetimetz")
     */
    private $created_at;

	/**
	 * @param string $billing_address_1
	 */
	public function setBillingAddress1($billing_address_1)
	{
		$this->billing_address_1 = $billing_address_1;
	}

	/**
	 * @return string
	 */
	public function getBillingAddress1()
	{
		return $this->billing_address_1;
	}

	/**
	 * @param string $billing_city
	 */
	public function setBillingCity($billing_city)
	{
		$this->billing_city = $billing_city;
	}

	/**
	 * @return string
	 */
	public function getBillingCity()
	{
		return $this->billing_city;
	}

	/**
	 * @param string $billing_country_code
	 */
	public function setBillingCountryCode($billing_country_code)
	{
		$this->billing_country_code = $billing_country_code;
	}

	/**
	 * @return string
	 */
	public function getBillingCountryCode()
	{
		return $this->billing_country_code;
	}

	/**
	 * @param string $billing_name
	 */
	public function setBillingName($billing_name)
	{
		$this->billing_name = $billing_name;
	}

	/**
	 * @return string
	 */
	public function getBillingName()
	{
		return $this->billing_name;
	}

	/**
	 * @param string $billing_tax_id
	 */
	public function setBillingTaxId($billing_tax_id)
	{
		$this->billing_tax_id = $billing_tax_id;
	}

	/**
	 * @return string
	 */
	public function getBillingTaxId()
	{
		return $this->billing_tax_id;
	}

	/**
	 * @param string $billing_zip_code
	 */
	public function setBillingZipCode($billing_zip_code)
	{
		$this->billing_zip_code = $billing_zip_code;
	}

	/**
	 * @return string
	 */
	public function getBillingZipCode()
	{
		return $this->billing_zip_code;
	}

	/**
	 * @param \DateTime $created_at
	 */
	public function setCreatedAt($created_at)
	{
		$this->created_at = $created_at;
	}

	/**
	 * @return \DateTime
	 */
	public function getCreatedAt()
	{
		return $this->created_at;
	}

	/**
	 * @param string $customer_email
	 */
	public function setCustomerEmail($customer_email)
	{
		$this->customer_email = $customer_email;
	}

	/**
	 * @return string
	 */
	public function getCustomerEmail()
	{
		return $this->customer_email;
	}

	/**
	 * @param int $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param string $landing_site
	 */
	public function setLandingSite($landing_site)
	{
		$this->landing_site = $landing_site;
	}

	/**
	 * @return string
	 */
	public function getLandingSite()
	{
		return $this->landing_site;
	}

	/**
	 * @param string $notes
	 */
	public function setNotes($notes)
	{
		$this->notes = $notes;
	}

	/**
	 * @return string
	 */
	public function getNotes()
	{
		return $this->notes;
	}

	/**
	 * @param int $order_id
	 */
	public function setOrderId($order_id)
	{
		$this->order_id = $order_id;
	}

	/**
	 * @return int
	 */
	public function getOrderId()
	{
		return $this->order_id;
	}

	/**
	 * @param string $payment_title
	 */
	public function setPaymentTitle($payment_title)
	{
		$this->payment_title = $payment_title;
	}

	/**
	 * @return string
	 */
	public function getPaymentTitle()
	{
		return $this->payment_title;
	}

	/**
	 * @param integer $price
	 */
	public function setPrice($price)
	{
		$this->price = $price;
	}

	/**
	 * @return integer
	 */
	public function getPrice()
	{
		return $this->price;
	}

	/**
	 * @param int $product_id
	 */
	public function setProductId($product_id)
	{
		$this->product_id = $product_id;
	}

	/**
	 * @return int
	 */
	public function getProductId()
	{
		return $this->product_id;
	}

	/**
	 * @param string $referring_site
	 */
	public function setReferringSite($referring_site)
	{
		$this->referring_site = $referring_site;
	}

	/**
	 * @return string
	 */
	public function getReferringSite()
	{
		return $this->referring_site;
	}

	/**
	 * @param string $shipping_address_1
	 */
	public function setShippingAddress1($shipping_address_1)
	{
		$this->shipping_address_1 = $shipping_address_1;
	}

	/**
	 * @return string
	 */
	public function getShippingAddress1()
	{
		return $this->shipping_address_1;
	}

	/**
	 * @param string $shipping_city
	 */
	public function setShippingCity($shipping_city)
	{
		$this->shipping_city = $shipping_city;
	}

	/**
	 * @return string
	 */
	public function getShippingCity()
	{
		return $this->shipping_city;
	}

	/**
	 * @param string $shipping_country_code
	 */
	public function setShippingCountryCode($shipping_country_code)
	{
		$this->shipping_country_code = $shipping_country_code;
	}

	/**
	 * @return string
	 */
	public function getShippingCountryCode()
	{
		return $this->shipping_country_code;
	}

	/**
	 * @param string $shipping_name
	 */
	public function setShippingName($shipping_name)
	{
		$this->shipping_name = $shipping_name;
	}

	/**
	 * @return string
	 */
	public function getShippingName()
	{
		return $this->shipping_name;
	}

	/**
	 * @param string $shipping_phone
	 */
	public function setShippingPhone($shipping_phone)
	{
		$this->shipping_phone = $shipping_phone;
	}

	/**
	 * @return string
	 */
	public function getShippingPhone()
	{
		return $this->shipping_phone;
	}

	/**
	 * @param integer $shipping_price
	 */
	public function setShippingPrice($shipping_price)
	{
		$this->shipping_price = $shipping_price;
	}

	/**
	 * @return integer
	 */
	public function getShippingPrice()
	{
		return $this->shipping_price;
	}

	/**
	 * @param string $shipping_title
	 */
	public function setShippingTitle($shipping_title)
	{
		$this->shipping_title = $shipping_title;
	}

	/**
	 * @return string
	 */
	public function getShippingTitle()
	{
		return $this->shipping_title;
	}

	/**
	 * @param string $shipping_zip_code
	 */
	public function setShippingZipCode($shipping_zip_code)
	{
		$this->shipping_zip_code = $shipping_zip_code;
	}

	/**
	 * @return string
	 */
	public function getShippingZipCode()
	{
		return $this->shipping_zip_code;
	}

	/**
	 * @param int $user_id
	 */
	public function setUserId($user_id)
	{
		$this->user_id = $user_id;
	}

	/**
	 * @return int
	 */
	public function getUserId()
	{
		return $this->user_id;
	}

	/**
	 * @param int $variant_id
	 */
	public function setVariantId($variant_id)
	{
		$this->variant_id = $variant_id;
	}

	/**
	 * @return int
	 */
	public function getVariantId()
	{
		return $this->variant_id;
	}

	/**
	 * @param string $customer_phone
	 */
	public function setCustomerPhone($customer_phone)
	{
		$this->customer_phone = $customer_phone;
	}

	/**
	 * @return string
	 */
	public function getCustomerPhone()
	{
		return $this->customer_phone;
	}
}
