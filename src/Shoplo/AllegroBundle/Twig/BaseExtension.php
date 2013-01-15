<?php

namespace Shoplo\AllegroBundle\Twig;

class BaseExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            'price' => new \Twig_Filter_Method($this, 'priceFilter'),
            'price_with_currency' => new \Twig_Filter_Method($this, 'priceWithCurrencyFilter'),
            'nicedate' => new \Twig_Filter_Method($this, 'nicedateFilter'),
			'count' => new \Twig_Filter_Method($this, 'countFilter'),
        );
    }

    public function priceFilter($price)
    {
        $price = bcdiv($price, 100, 2);

        return $price;
    }

    public function priceWithCurrencyFilter($price, $currency='zł')
    {
        $price = bcdiv($price, 100, 2);
        $price = $price.$currency;

        return $price;
    }

	public function countFilter($array)
	{
		return count($array);
	}

    public function nicedateFilter($date)
    {
        $now = date_create('now');
        $interval = $now->diff($date);

        if ($now == $date || $interval->d == 0) {
            return 'dzisiaj';
        } elseif ($now > $date) {
            if ($interval->d == 1) {
                return 'wczoraj';
            } elseif ($interval->d < 7) {
                return sprintf("%d dni temu", $interval->d);
            }
        } else {
            if ($interval->d == 1) {
                return 'jutro';
            } elseif ($interval->d < 7) {
                return sprintf("za %d dni", $interval->d);
            }
        }

        return $date->format('Y-m-d');
    }

    public function getName()
    {
        return 'acme_extension';
    }
}
