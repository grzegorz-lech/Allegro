<?php

namespace Shoplo\AllegroBundle\WebAPI;

use Shoplo\AllegroBundle\Entity\User;

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

        // TODO: Save session
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
     * @return array
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Metoda pobiera informacje z dziennika zdarzeń
     *
     * @param $lastEventId
     * @return bool|array(deal-event-id, deal-event-type, deal-event-time, deal-id, deal-transaction-id, deal-seller-id, deal-item-id, deal-buyer-id, deal-quantity)
     */
    public function getNewEvents($lastEventId)
    {
        try {
            // TODO: zapis event'ow do bazy
            return $this->doGetSiteJournalDeals($this->session['session-handle-part'], $lastEventId);
        } catch ( \SoapFault $sf ) {
            if ($sf->faultcode == 'ERR_NO_SESSION' || $sf->faultcode == 'ERR_SESSION_EXPIRED') {
                if ( $this->doLogin() ) {
                    return $this->doGetSiteJournalDeals($this->session['session-handle-part'], $lastEventId);
                }
            }

            return false;
        }
    }

    /**
     * 	Pobranie wszystkich danych z formularza pozakupowego
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
        } catch ( \SoapFault $sf ) {
            if ($sf->faultcode == 'ERR_NO_SESSION' || $sf->faultcode == 'ERR_SESSION_EXPIRED') {
                if ( $this->doLogin() ) {
                    $result = $this->doGetPostBuyFormsDataForSellers($this->session['session-handle-part'], $transactionIds);

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
        } catch ( \SoapFault $sf ) {
            if ($sf->faultcode == 'ERR_NO_SESSION' || $sf->faultcode == 'ERR_SESSION_EXPIRED') {
                if ( $this->doLogin() ) {
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
            $buyers = array();
            foreach ($buyersInfo['users-post-buy-data'] as $buyer) {
                $buyer = (array) $buyer;
                $buyer['user-data'] = (array) $buyer['user-data'];
                $buyers[$buyer['user-data']['user-id']] = $buyer;
            }
            $auctions[] = array(
                'item_id'	=>	$buyersInfo['item-id'],
                'buyers'	=>	$buyers
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
            $result = $this->doGetShipmentData($this->country, $this->key);
            $shipping = array();
            foreach ( $result['shipment-data-list'] as $sl )
                $shipping[$sl['shipment-id']] = $sl;

            return $shipping;
        } catch ( \SoapFault $sf ) {
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
                'm'		=>	'mTransfer - mBank',
                'n'		=>	'MultiTransfer - MultiBank',
                'w'		=>	'BZWBK - Przelew24',
                'o'		=>	'Pekao24Przelew - Bank Pekao',
                'i'		=>	'Płacę z Inteligo',
                'd'		=>	'Płać z Nordea',
                'p'		=>	'Płać z iPKO',
                'h'		=>	'Płać z BPH',
                'g'		=>	'Płać z ING',
                'l'		=>	'Credit Agricole',
                'as'	=>	'Płacę z Alior Sync',
                'u'		=>	'Eurobank',
                'me'	=>	'Meritum Bank',
                'ab'	=>	'Płacę z Alior Bankiem',
                'wp'	=>	'Przelew z Polbank',
                'wm'	=>	'Przelew z Millennium',
                'wk'	=>	'Przelew z Kredyt Bank',
                'wg'	=>	'Przelew z BGŻ',
                'wd'	=>	'Przelew z Deutsche Bank',
                'wr'	=>	'Przelew z Raiffeisen Bank',
                'wc'	=>	'Przelew z Citibank',
                'wn'	=>	'Przelew z Invest Bank',
                'wi'	=>	'Przelew z Getin Bank',
                'wy'	=>	'Przelew z Bankiem Pocztowym',
                'c'		=>	'Karta kredytowa',
                'b'		=>	'Przelew bankowy',
                't'		=>	'płatność testowa',
                'pu'	=>	'Konto PayU',
                'co'	=>	'Checkout PayU',
                'ai'	=>	'Raty PayU',
                'collect_on_delivery'	=>	'Płatność przy odbiorze',
                'wire_transfer'			=>	'Zwykły przelew',
                'not_specified'			=>	'Nie określony',
        );
    }
}
