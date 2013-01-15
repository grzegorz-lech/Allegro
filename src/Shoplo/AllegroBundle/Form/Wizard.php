<?php

namespace Shoplo\AllegroBundle\Form;

use Symfony\Component\Validator\Constraints as Assert;
use Shoplo\AllegroBundle\Entity\Profile;

class Wizard
{
    /**
     * @Assert\NotBlank()
     */
    protected $title;

    /**
     * @Assert\NotBlank()
     */
    protected $description;

	protected $profiles;

	protected $duration;

	protected $promotions;

	protected $payments;

	protected $delivery;

	protected $quantity;

	protected $all_stock;

	/**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    public function export(Profile $profile, array $product, array &$variant, $categoryId, $imagesOption)
    {
        $fields = array();

        // Nieskończony magazyn
        if (!$variant['add_to_magazine']) {
            $variant['quantity'] = $this->getAllStock() ? 100 : $this->getQuantity();
        }
		else {
			$variant['quantity'] = $this->getAllStock() ? $variant['quantity'] : $this->getQuantity();
		}

        // Cena
        $variant['price'] = round($variant['price'] / 100, 2);

        // Zmienne
        $search      = array(
            '{product_name}',
            '{product_short_description}',
            '{product_description}',
            '{product_sku}',
            '{product_price}',
        );
        $replace     = array(
            $product['name'],
            $product['short_description'],
            $product['description'],
            $variant['sku'],
            number_format($variant['price'], 2, ',', ''),
        );
        $title       = str_ireplace($search, $replace, $this->getTitle());
        $description = str_ireplace($search, $replace, $this->getDescription());

        $fields[] = $this->createField(1, $title);
        $fields[] = $this->createField(2, (int) $categoryId);
        $fields[] = $this->createField(4, $profile->getDuration());
        $fields[] = $this->createField(5, (int) $variant['quantity']);
        $fields[] = $this->createField(8, $variant['price']);
        $fields[] = $this->createField(9, $profile->getCountry());
        $fields[] = $this->createField(10, $profile->getState());
        $fields[] = $this->createField(11, $profile->getCity());
        $fields[] = $this->createField(12, 1);
        $fields[] = $this->createField(13, $profile->getDelivery());
        $fields[] = $this->createField(14, $profile->getPayments());
        $fields[] = $this->createField(32, $profile->getZipcode());
        $fields[] = $this->createField(24, $description);
        $fields[] = $this->createField(29, 0);

        // Zdjęcia
        $id     = 16;
        $prefix = ''; // http://src.sencha.io/200
        foreach ($product['images'] as $image) {
            // Maks. 8 zdjęć (16-23)
            if ($id > 23) {
                break;
            }

            if (false !== $image = file_get_contents($prefix . $image['src'])) {
                $fields[] = $this->createField($id++, $image, true);
				if ( $imagesOption == 'one' ) {
					break;
				}
            }
        }

        // Dodatkowe pola
        foreach ($profile->getExtras() as $key => $value) {
            $fields[] = $this->createField($key, (float) $value);
        }

        return $fields;
    }

    /**
     * @param  int   $id
     * @param  mixed $value
     * @param  bool  $image
     * @return array
     */
    public static function createField($id, $value, $image = false)
    {
        $field = array(
            'fid'                => $id,
            'fvalue-string'      => '',
            'fvalue-int'         => 0,
            'fvalue-float'       => 0,
            'fvalue-image'       => 0,
            'fvalue-datetime'    => 0,
            'fvalue-date'        => '',
            'fvalue-range-int'   => array(
                'fvalue-range-int-min' => 0,
                'fvalue-range-int-max' => 0,
            ),
            'fvalue-range-float' => array(
                'fvalue-range-float-min' => 0,
                'fvalue-range-float-max' => 0,
            ),
            'fvalue-range-date'  => array(
                'fvalue-range-date-min' => '',
                'fvalue-range-date-max' => '',
            ),
        );

        if ($image) {
            $field['fvalue-image'] = $value;
        } elseif (is_int($value)) {
            $field['fvalue-int'] = $value;
        } elseif (is_float($value)) {
            $field['fvalue-float'] = $value;
        } elseif (is_string($value)) {
            $field['fvalue-string'] = $value;
        }

        return $field;
    }

	public function setDuration($duration)
	{
		$this->duration = $duration;
	}

	public function getDuration()
	{
		return $this->duration;
	}

	public function setPromotions($promotions)
	{
		$this->promotions = $promotions;
	}

	public function getPromotions()
	{
		return $this->promotions;
	}

	public function setPayments($payments)
	{
		$this->payments = $payments;
	}

	public function getPayments()
	{
		return $this->payments;
	}

	public function setDelivery($delivery)
	{
		$this->delivery = $delivery;
	}

	public function getDelivery()
	{
		return $this->delivery;
	}

	public function setAllStock($all_stock)
	{
		$this->all_stock = $all_stock;
	}

	public function getAllStock()
	{
		return $this->all_stock;
	}

	public function setQuantity($quantity)
	{
		$this->quantity = $quantity;
	}

	public function getQuantity()
	{
		return $this->quantity;
	}

	public function setProfiles($profiles)
	{
		$this->profiles = $profiles;
	}

	public function getProfiles()
	{
		return $this->profiles;
	}
}
