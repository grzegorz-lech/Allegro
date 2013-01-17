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

		return $codes['pl'];
        //throw new \InvalidArgumentException();
    }

	/**
	 * @param  int $code
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function getCountryKey($code)
	{
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
		$codes = array_flip($codes);
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
	 * @param $manager
     * @return Deal[]
     */
    public function getDeals($lastEventId, $manager, $repository)
    {
        $deals = array();

		foreach ($this->doGetSiteJournalDeals($this->session['session-handle-part'], $lastEventId) as $deal) {
			$dealObject = new Deal();
            $dealObject->setDealId($deal->{'deal-id'})
                ->setEventType($deal->{'deal-event-type'})
                ->setEventTime(new \DateTime('@' . $deal->{'deal-event-time'}))
                ->setId($deal->{'deal-event-id'})
                ->setTransactionId($deal->{'deal-transaction-id'})
                ->setSellerId($deal->{'deal-seller-id'})
                ->setItemId($deal->{'deal-item-id'})
                ->setBuyerId($deal->{'deal-buyer-id'})
                ->setQuantity($deal->{'deal-quantity'});
            $deals[] = $dealObject;

			if ( $obj = $repository->findOneById($deal->{'deal-event-id'}) )
			{
				continue;
			}
			$manager->persist($dealObject);
        }
		$manager->flush();

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

            return $result;
        } catch (\SoapFault $sf) {
            if ($sf->faultcode == 'ERR_NO_SESSION' || $sf->faultcode == 'ERR_SESSION_EXPIRED') {
                if ($this->doLogin()) {
                    $result = $this->doGetPostBuyFormsDataForSellers(
                        $this->session['session-handle-part'],
                        $transactionIds
                    );

                    return $result;
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
            $result   = (array) $this->doGetShipmentData($this->country, $this->key);
            $shipping = array();
            foreach ($result['shipment-data-list'] as $sl) {
				$sl = (array) $sl;
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
    public function calculateAuctionPrice($price, $categoryPath, $quantity = 1)
    {
        $categoriesMedia = array(
            '7'		=>	'Książki i Komiksy',
            '98713'	=>	'Płyty 3D',
            '89054'	=>	'Płyty Blue-ray',
            '100075'=>	'Płyty DVD',
            '20664'	=>	'Płyty VCD',
        );
        $common          = array_intersect($categoryPath, $categoriesMedia);
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

    /**
     * @param  int   $categoryId
     * @param  bool  $onlyRequired
     * @return array
     */
    public function getCategoryFields($categoryId, $onlyRequired = true)
    {
        $fields = $this->doGetSellFormFieldsForCategory($this->getKey(), $this->getCountry(), $categoryId);
        $fields = $fields->{'sell-form-fields-list'};
        $fields = array_map(
            function ($field) {
                return (array) $field;
            },
            $fields
        );

        if ($onlyRequired) {
            $fields = array_filter(
                $fields,
                function ($field) {
                    return 1 === $field['sell-form-opt'];
                }
            );
        }

        $output = array();

        foreach ($fields as $field) {
            $output[$field['sell-form-id']] = $field;
        }

        return $output;
    }

    /**
     * @param  array $fields
     * @param  array $extraFields
     * @return array
     */
    public static function getMissingFields(array $fields, array $extraFields)
    {
        $fieldIDs = $extraFieldIDs = array();

        foreach ($fields as $field) {
            $fieldIDs[] = $field['fid'];
        }

        foreach ($extraFields as $field) {
            $extraFieldIDs[] = $field['sell-form-id'];
        }

        $missingFieldIDs = array_diff($extraFieldIDs, $fieldIDs);
        $missingFields   = array_filter(
            $extraFields,
            function ($field) use ($missingFieldIDs) {
                return in_array($field['sell-form-id'], $missingFieldIDs);
            }
        );

        return $missingFields;
    }

    public function updateItemQuantity($itemId, $quantity)
    {
        try {
			$itemId = (float) $itemId;
            $item = $this->doChangeQuantityItem($this->session['session-handle-part'], $itemId, $quantity);

            return true;
        } catch (\SoapFault $sf) {
			if ($sf->faultcode == 'ERR_NO_SESSION' || $sf->faultcode == 'ERR_SESSION_EXPIRED') {
                if ($this->doLogin()) {
                    $item = $this->doChangeQuantityItem($this->session['session-handle-part'], $itemId, $quantity);

                    return true;
                }
            }
			else
			{
				# TODO: log this error: $sf->faultcode/$itemId
			}

            return false;
        }
    }

	/**
	 * Remove item from Allegro
	 *
	 * @param $itemId
	 * @param int $finishCancelAllBids
	 * @return bool
	 */
	public function removeItem($itemId, $finishCancelAllBids=1, $finishCancelReason='Przedmioty zostaly wykupione')
	{
		try {
			$itemId = (float) $itemId;
			$item = $this->doFinishItem($this->session['session-handle-part'], $itemId, $finishCancelAllBids, $finishCancelReason);

			return $item ? true : false;
		} catch (\SoapFault $sf) {
			if ($sf->faultcode == 'ERR_NO_SESSION' || $sf->faultcode == 'ERR_SESSION_EXPIRED') {
				if ($this->doLogin()) {
					$item = $this->doFinishItem($this->session['session-handle-part'], $itemId, $finishCancelAllBids, $finishCancelReason);

					return $item ? true : false;
				}
			}
			else
			{
				# TODO: log this error: $sf->faultcode/$itemId
			}

			return false;
		}
	}

	public function getCountryMap()
	{
		return array(
			"af" => "Afganistan",
			"al" => "Albania",
			"dz" => "Algeria",
			"ad" => "Andora",
			"ao" => "Angola",
			"ai" => "Anguilla",
			"ag" => "Antigua i Barbuda",
			"ah" => "Antyle Holenderskie",
			"sa" => "Arabia Saudyjska",
			"ar" => "Argentyna",
			"am" => "Armenia",
			"aw" => "Aruba",
			"au" => "Australia",
			"at" => "Austria",
			"bs" => "Bahamy",
			"bh" => "Bahrajn",
			"bd" => "Bangladesz",
			"bb" => "Barbados",
			"be" => "Belgia",
			"bz" => "Belize",
			"bj" => "Benin",
			"bm" => "Bermuda",
			"bt" => "Bhutan",
			"by" => "Białoruś",
			"bu" => "Birma",
			"bo" => "Boliwia",
			"ba" => "Bośnia i Hercegowina",
			"bw" => "Botswana",
			"br" => "Brazylia",
			"bn" => "Brunei",
			"io" => "Brytyjskie Wyspy Dziewicze",
			"bg" => "Bułgaria",
			"bf" => "Burkina Faso",
			"bi" => "Burundi",
			"cl" => "Chile",
			"cn" => "Chiny",
			"hr" => "Chorwacja",
			"cy" => "Cypr",
			"td" => "Czad",
			"me" => "Czarnogóra",
			"cz" => "Czechy",
			"dk" => "Dania",
			"dm" => "Dominika",
			"do" => "Dominikana",
			"dj" => "Dżibuti",
			"eg" => "Egipt",
			"ec" => "Ekwador",
			"er" => "Erytrea",
			"ee" => "Estonia",
			"et" => "Etiopia",
			"fk" => "Falklandy",
			"fj" => "Fidżi",
			"ph" => "Filipiny",
			"fi" => "Finlandia",
			"fr" => "Francja",
			"gm" => "Gambia",
			"gh" => "Ghana",
			"gi" => "Gibraltar",
			"gr" => "Grecja",
			"gd" => "Grenada",
			"gl" => "Grenlandia",
			"ge" => "Gruzja",
			"gu" => "Guam",
			"gg" => "Guernsey",
			"gy" => "Gujana",
			"gf" => "Gujana Francuska",
			"gp" => "Gwadelupa",
			"gt" => "Gwatemala",
			"gn" => "Gwinea",
			"gw" => "Gwinea Bissau",
			"gq" => "Gwinea Równikowa",
			"ht" => "Haiti",
			"es" => "Hiszpania",
			"nl" => "Holandia",
			"hn" => "Honduras",
			"hk" => "Hong Kong",
			"in" => "Indie",
			"id" => "Indonezja",
			"iq" => "Irak",
			"ir" => "Iran",
			"ie" => "Irlandia",
			"is" => "Islandia",
			"il" => "Izrael",
			"jm" => "Jamajka",
			"sj" => "Jan Mayen",
			"jp" => "Japonia",
			"yt" => "Jemen",
			"je" => "Jersey",
			"jo" => "Jordan",
			"ky" => "Kajmany",
			"kh" => "Kambodża",
			"cm" => "Kamerun",
			"ca" => "Kanada",
			"qa" => "Katar",
			"kz" => "Kazachstan",
			"ke" => "Kenia",
			"kg" => "Kirgistan",
			"ki" => "Kiribati",
			"co" => "Kolumbia",
			"km" => "Komory",
			"cg" => "Kongo, Demokratyczna Republika",
			"cg" => "Kongo, Republika",
			"kr" => "Korea Południowa",
			"cr" => "Kostaryka",
			"kw" => "Kuwejt",
			"la" => "Laos",
			"lb" => "Liban",
			"li" => "Liechtenstein",
			"lt" => "Litwa",
			"lu" => "Luksemburg",
			"lv" => "Łotwa",
			"mk" => "Macedonia",
			"mg" => "Madagaskar",
			"yt" => "Majotta",
			"mo" => "Makau",
			"mw" => "Malawi",
			"mv" => "Malediwy",
			"my" => "Malezja",
			"ml" => "Mali",
			"mt" => "Malta",
			"ma" => "Maroko",
			"mq" => "Martynika",
			"mr" => "Mauretania",
			"mu" => "Mauritius",
			"mx" => "Meksyk",
			"fm" => "Mikronezja",
			"md" => "Mołdawia",
			"mc" => "Monako",
			"mn" => "Mongolia",
			"ms" => "Montserrat",
			"mz" => "Mozambik",
			"na" => "Namibia",
			"nr" => "Nauru",
			"np" => "Nepal",
			"de" => "Niemcy",
			"ne" => "Niger",
			"ng" => "Nigeria",
			"ni" => "Nikaragua",
			"nu" => "Niue",
			"no" => "Norwegia",
			"nc" => "Nowa Kaledonia",
			"nz" => "Nowa Zelandia",
			"om" => "Oman",
			"pk" => "Pakistan",
			"pw" => "Palau",
			"pa" => "Panama",
			"pg" => "Papua-Nowa Gwinea",
			"py" => "Paragwaj",
			"pe" => "Peru",
			"pf" => "Polinezja Francuska",
			"pl" => "Polska",
			"pr" => "Portoryko",
			"pt" => "Portugalia",
			"az" => "Republika Azerbejdżanu",
			"ga" => "Republika Gabonu",
			"za" => "Republika Południowej Afryki",
			"cf" => "Republika Środkowoafrykańska",
			"cv" => "Republika Zielonego Przylądka",
			"ru" => "Rosja",
			"ro" => "Rumunia",
			"rw" => "Rwanda",
			"eh" => "Sahara Zachodnia",
			"kn" => "Saint Kitts i Nevis",
			"lc" => "Saint Lucia",
			"vc" => "Saint Vincent i Grenadyny",
			"pm" => "Saint-Pierre i Miquelon",
			"sv" => "Salwador",
			"as" => "Samoa Amerykańska",
			"ws" => "Samoa Zachodnie",
			"sm" => "San Marino",
			"sn" => "Senegal",
			"rs" => "Serbia",
			"sc" => "Seszele",
			"sl" => "Sierra Leone",
			"sg" => "Singapur",
			"sk" => "Słowacja",
			"si" => "Słowenia",
			"so" => "Somalia",
			"lk" => "Sri Lanka",
			"us" => "Stany Zjednoczone",
			"sz" => "Suazi",
			"sr" => "Surinam",
			"sj" => "Svalbard",
			"sy" => "Syria",
			"ch" => "Szwajcaria",
			"se" => "Szwecja",
			"tj" => "Tadżykistan",
			"th" => "Tajlandia",
			"tw" => "Tajwan",
			"tz" => "Tanzania",
			"tg" => "Togo",
			"to" => "Tonga",
			"tt" => "Trynidad i Tobago",
			"tn" => "Tunezja",
			"tr" => "Turcja",
			"tm" => "Turkmenistan",
			"tc" => "Turks i Caicos",
			"tv" => "Tuvalu",
			"ug" => "Uganda",
			"ua" => "Ukraina",
			"uy" => "Urugwaj",
			"uz" => "Uzbekistan",
			"vu" => "Vanuatu",
			"wf" => "Wallis i Futuna",
			"va" => "Watykan",
			"ve" => "Wenezuela",
			"hu" => "Węgry",
			"gb" => "Wielka Brytania",
			"vn" => "Wietnam",
			"it" => "Włochy",
			"ci" => "Wybrzeże Kości Słoniowej",
			"sh" => "Wyspa Świętej Heleny",
			"ck" => "Wyspy Cooka",
			"vg" => "Wyspy Dziewicze (USA)",
			"mh" => "Wyspy Marshalla",
			"sb" => "Wyspy Salomona",
			"zm" => "Zambia",
			"zw" => "Zimbabwe",
			"ae" => "Zjednoczone Emiraty Arabskie",
		);
	}

	public function getItemsInfo($ids)
	{
		foreach ( $ids as $k => $i )
		{
			$ids[$k] = (float) $i;
		}

		try {
			$result = $this->doGetItemsInfo($this->getSession(), $ids, 1);

			return $result;
		} catch (\SoapFault $sf) {
			if ($sf->faultcode == 'ERR_NO_SESSION' || $sf->faultcode == 'ERR_SESSION_EXPIRED') {
				if ($this->doLogin()) {
					return $this->doGetItemsInfo($this->getSession(), $ids, 1);
				}
			}
			else
			{
				# TODO: log this error: $sf->faultcode/$itemId
			}

			return false;
		}
	}
}
