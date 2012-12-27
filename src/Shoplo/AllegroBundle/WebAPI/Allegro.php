<?php

namespace Shoplo\AllegroBundle\WebAPI;

use Shoplo\AllegroBundle\Entity\User;
use Shoplo\AllegroBundle\Entity\Deal;

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
     * @var array
     */
    private $session = array();

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
        // TODO: Store & reuse session

        $this->setUsername($user->getUsername());
        $this->setPassword($user->getPassword(true));
        $this->setCountry($user->getCountry());

        return $this->doLogin();
    }

    private function doLogin()
    {
        try {
            $this->session = $this->doLoginEnc(
                $this->getUsername(),
                $this->getPassword(),
                $this->getCountry(),
                $this->getKey(),
                $this->getVersion()
            );
        } catch (\SoapFault $sf) {
            return false;
        }

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
            $status = (array) $status;
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

    /**
     * @param  string                    $code
     * @return int
     * @throws \InvalidArgumentException
     */
    public function getCountryCode($code)
    {
        $code  = mb_strtolower($code);
        $codes = array(
            'pl' => 1, // Polska (allegro.pl)
            'by' => 22, // Białoruś (allegro.by)
            'bg' => 34, // Bułgaria (aukro.bg)
            'cz' => 56, // Czechy (aukro.cz)
            'kz' => 107, // Kazachstan (allegro.kz)
            'ru' => 168, // Rosja (molotok.ru)
            'sk' => 181, // Słowacja (aukro.sk)
            'ua' => 209, // Ukraina (rosyjski) (aukro.ua)
            'xx' => 228, // WebAPI (testwebapi.pl)
            'ua' => 232, // Ukraina (ukraiński) (ua.aukro.ua) TODO: Język?
        );

        if (isset($codes[$code])) {
            return $codes[$code];
        }

        throw new \InvalidArgumentException();
    }

    /**
     * @param array $session
     */
    public function setSession($session)
    {
        $this->session = $session;
    }

    /**
     * @return string
     */
    public function getSession()
    {
        return $this->session['session-handle-part'];
    }

    public function getCategories($offset = 0, $limit = 50)
    {
        return $this->doGetCatsDataLimit($this->country, 0, $this->key, $offset, $limit);
    }

    public function getCategoryPath($categoryId)
    {
        return $this->doGetCategoryPath($this->session['session-handle-part'], 101359);
    }

    /**
     * Metoda pobiera informacje z dziennika zdarzeń
     *
     * @param $lastEventId
     * @return Deal[]
     */
    public function getDeals($lastEventId)
    {
        $deals = array();

        foreach ($this->doGetSiteJournalDeals($this->session['session-handle-part'], $lastEventId) as $deal) {
            $dealObject = new Deal();
            $dealObject->setEventId($deal->{'deal-event-id'})
                ->setEventType($deal->{'deal-event-type'})
                ->setEventTime(new \DateTime('@' . $deal->{'deal-event-time'}))
                ->setId($deal->{'deal-id'})
                ->setTransactionId($deal->{'deal-transaction-id'})
                ->setSellerId($deal->{'deal-seller-id'})
                ->setItemId($deal->{'deal-item-id'})
                ->setBuyerId($deal->{'deal-buyer-id'})
                ->setQuantity($deal->{'deal-quantity'});
            $deals[] = $dealObject;
        }

        return $deals;
    }

    /**
     * Pobranie informacji z dziennika zdarzeń nt. zdarzeń dot. formularzy pozakupowych
     *
     * @param  string      $sessionId
     * @param  int         $journalStart
     * @return \stdClass[]
     */
    public function doGetSiteJournalDeals($sessionId, $journalStart)
    {
        return parent::doGetSiteJournalDeals($sessionId, $journalStart);
    }

    /**
     *     Pobranie wszystkich danych z formularza pozakupowego
     *
     * @param $transactionId
     * @return bool|array()
     */
    public function getBuyersData($transactionIds)
    {
        try {
            // TODO: zapis danych sprzedazowych do bazy
            $result = $this->doGetPostBuyFormsDataForSellers($this->session['session-handle-part'], $transactionIds);

            return $result['post-buy-form-data'];
        } catch (\SoapFault $sf) {
            if ($sf->faultcode == 'ERR_NO_SESSION' || $sf->faultcode == 'ERR_SESSION_EXPIRED') {
                if ($this->doLogin()) {
                    $result = $this->doGetPostBuyFormsDataForSellers(
                        $this->session['session-handle-part'],
                        $transactionIds
                    );

                    return $result['post-buy-form-data'];
                }
            }

            return false;
        }
    }

    public function getPostBuyData($auctionIds)
    {
        try {
            // TODO: zapis danych sprzedazowych do bazy
            $result = (array) $this->doGetPostBuyData($this->session['session-handle-part'], $auctionIds);
        } catch (\SoapFault $sf) {
            if ($sf->faultcode == 'ERR_NO_SESSION' || $sf->faultcode == 'ERR_SESSION_EXPIRED') {
                if ($this->doLogin()) {
                    $result = (array) $this->doGetPostBuyData($this->session['session-handle-part'], $auctionIds);
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }

        $auctions = array();
        foreach ($result as $buyersInfo) {
            $buyersInfo = (array) $buyersInfo;
            $buyers     = array();
            foreach ($buyersInfo['users-post-buy-data'] as $buyer) {
                $buyer                                  = (array) $buyer;
                $buyer['user-data']                     = (array) $buyer['user-data'];
                $buyers[$buyer['user-data']['user-id']] = $buyer;
            }
            $auctions[] = array(
                'item_id' => $buyersInfo['item-id'],
                'buyers'  => $buyers
            );
        }

        return $auctions;
    }

    /**
     * Pobranie sposobow dostawy dla danego sprzedawcy
     *
     * @return array
     */
    public function getShippingMethods()
    {
        try {
            $result   = $this->doGetShipmentData($this->country, $this->key);
            $shipping = array();
            foreach ($result['shipment-data-list'] as $sl) {
                $shipping[$sl['shipment-id']] = $sl;
            }

            return $shipping;
        } catch (\SoapFault $sf) {
            return array();
        }
    }

    /**
     * Zwraca dostępne sposoby płatności
     * @static
     * @return array
     */
    public static function getPaymentMethods()
    {
        return array(
            'm'                   => 'mTransfer - mBank',
            'n'                   => 'MultiTransfer - MultiBank',
            'w'                   => 'BZWBK - Przelew24',
            'o'                   => 'Pekao24Przelew - Bank Pekao',
            'i'                   => 'Płacę z Inteligo',
            'd'                   => 'Płać z Nordea',
            'p'                   => 'Płać z iPKO',
            'h'                   => 'Płać z BPH',
            'g'                   => 'Płać z ING',
            'l'                   => 'Credit Agricole',
            'as'                  => 'Płacę z Alior Sync',
            'u'                   => 'Eurobank',
            'me'                  => 'Meritum Bank',
            'ab'                  => 'Płacę z Alior Bankiem',
            'wp'                  => 'Przelew z Polbank',
            'wm'                  => 'Przelew z Millennium',
            'wk'                  => 'Przelew z Kredyt Bank',
            'wg'                  => 'Przelew z BGŻ',
            'wd'                  => 'Przelew z Deutsche Bank',
            'wr'                  => 'Przelew z Raiffeisen Bank',
            'wc'                  => 'Przelew z Citibank',
            'wn'                  => 'Przelew z Invest Bank',
            'wi'                  => 'Przelew z Getin Bank',
            'wy'                  => 'Przelew z Bankiem Pocztowym',
            'c'                   => 'Karta kredytowa',
            'b'                   => 'Przelew bankowy',
            't'                   => 'płatność testowa',
            'pu'                  => 'Konto PayU',
            'co'                  => 'Checkout PayU',
            'ai'                  => 'Raty PayU',
            'collect_on_delivery' => 'Płatność przy odbiorze',
            'wire_transfer'       => 'Zwykły przelew',
            'not_specified'       => 'Nie określony',
        );
    }

    public function getSellFormFields()
    {
        $cacheKey = sprintf('[%s][%d]', __FUNCTION__, $this->getCountry());
        if (false === $fields = apc_fetch($cacheKey)) {
            $fields = $this->doGetSellFormFieldsExt($this->getCountry(), 0, $this->getKey());
            apc_store($cacheKey, $fields);
        }

        // Rewrite keys
        $output = array();
        foreach ($fields['sell-form-fields'] as $field) {
            $output[$field->{'sell-form-id'}] = $field;
        }

        return $output;
    }

    /**
     * Oblicza cene za wystawienie przedmiotu w zaleznosci od tego w jakiej kategorii znajduje sie przedmiot
     * @param $categoryPath - cala galaz kategorii
     * @param $price
     * @param $quantity
     * @return string provision
     */
    public function calculateAuctionPrice($price, $categoryPath, $quantity=1)
    {
        # TODO: przypisać id kategorii z produkcji
        $categoriesMedia = array(
            '1'		=>	'Książki i Komiksy',
            '2'		=>	'Płyty 3D',
            '3'		=>	'Płyty Blue-ray',
            '4'		=>	'Płyty DVD',
            '5'		=>	'Płyty VCD',
        );
        $common = array_intersect($categoryPath, $categoriesMedia);
        switch ($price) {
            case $price < 9.99:
                $provision = !empty($common) ? 0.05 : 0.08;
                break;
            case $price < 24.99:
                $provision = !empty($common) ? 0.08 : 0.13;
                break;
            case $price < 49.99:
                $provision = !empty($common) ? 0.10 : 0.25;
                break;
            case $price < 249.99:
                $provision = !empty($common) ? 0.15 : 0.50;
                break;
            default:
                $provision = !empty($common) ? 0.20 : 1.00;
                break;
        }

        return bcmul($provision, $quantity, 2);
    }
}
