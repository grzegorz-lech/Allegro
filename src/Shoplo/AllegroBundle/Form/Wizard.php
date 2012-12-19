<?php

namespace Shoplo\AllegroBundle\Form;

use Symfony\Component\Validator\Constraints as Assert;

class Wizard
{
    /**
     * @Assert\NotNull()
     */
    protected $layout;

    /**
     * @Assert\NotBlank()
     */
    protected $description;

    /**
     * @param int $layout
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;
    }

    /**
     * @return int
     */
    public function getLayout()
    {
        return $this->layout;
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

    public function export(array $product, array &$variant)
    {
        $fields = array();

        // Nieskończony magazyn
        if (!$variant['add_to_magazine']) {
            $variant['quantity'] = 100;
        }

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
            number_format($variant['price'] / 100, 2, ',', ''),
        );
        $description = str_ireplace($search, $replace, $this->getDescription());

        $fields[] = $this->createField(1, $variant['product_name']); // TODO: Tytuł
        $fields[] = $this->createField(2, 1874); // TODO: Kategoria
        $fields[] = $this->createField(4, 0); // TODO: Czas trwania aukcji (0 => 3d)
        $fields[] = $this->createField(5, (int)$variant['quantity']); // TODO: Liczba sztuk
        $fields[] = $this->createField(8, round($variant['price'] / 100, 2)); // TODO: Cena "Kup Teraz"
        $fields[] = $this->createField(9, 228); // TODO: Kraj
        $fields[] = $this->createField(10, 213); // TODO: Województwo
        $fields[] = $this->createField(11, 'Warszawa'); // TODO: Miasto
        $fields[] = $this->createField(12, 1); // TODO: Transport
        $fields[] = $this->createField(13, 1); // TODO: Opcje dot. transportu (2^x)
        $fields[] = $this->createField(14, 1); // TODO: Formy płatności (2^x)
        $fields[] = $this->createField(32, '02-495'); // TODO: Kod pocztowy
        $fields[] = $this->createField(24, $description);
        $fields[] = $this->createField(29, 0); // TODO: Forma sprzedaży (Aukcja lub Kup Teraz | Sklep)
        $fields[] = $this->createField(44, 16.10); // TODO: Kurier

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
            }
        }

        return $fields;
    }

    /**
     * @param int $id
     * @param mixed $value
     * @param bool $image
     * @return array
     */
    private function createField($id, $value, $image = false)
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
}
