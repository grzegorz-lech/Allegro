<?php

namespace Shoplo\AllegroBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use InvalidArgumentException;
use Shoplo\AllegroBundle\Entity\Item;
use Shoplo\AllegroBundle\Entity\Profile;
use Shoplo\AllegroBundle\Entity\CategoryAllegro;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Request;
use Shoplo\AllegroBundle\Form\Wizard;
use Shoplo\AllegroBundle\WebAPI\Allegro;
use Shoplo\AllegroBundle\Utils\Admin;
use Shoplo\AllegroBundle\Entity\Category;

class WizardController extends Controller
{
	protected $_message = null;

    /**
     * @Secure(roles="ROLE_ADMIN")
     */
    public function indexAction(Request $request)
    {
		$ids = $request->query->get('product', array());
        $ids = !is_array($ids) ? explode(',', $ids) : $ids;
        if (empty($ids)) {
            throw $this->createNotFoundException('Product IDs missing');
        }

		/** @var $allegro Allegro */
		$allegro = $this->get('allegro');
		$allegro->login($this->getUser());

        // Informacje o produktach
        $shoplo = $this->get('shoplo');
		try {
			list($products, $variants, $productsWithNoCategories) = $this->getProducts($shoplo, $ids);
		}
		catch ( \Exception $e ) {
			return $this->redirect($this->generateUrl('shoplo_allegro_settings_mapping'));
		}


		if ( !empty($productsWithNoCategories) ) {
			$this->getRequest()->getSession()->setFlash(
				'error',
				'Produkty "'.implode('", "', $productsWithNoCategories).'" nie mają przypisanych kategorii w Twoim sklepie.'
			);
			return $this->redirect($this->generateUrl('shoplo_allegro_homepage'));
		}

		$profiles = $this->getDoctrine()
			->getRepository('ShoploAllegroBundle:Profile')
			->findBy(
				array('user_id' => $this->getUser()->getId() ),
				array('id'	=> 'ASC' )
			);

		$profilePromotions = $profileDurations = array();
		foreach ( $profiles as $profile )
		{
			$binary = str_pad(decbin($profile->getPromotions()), 5, "0", STR_PAD_LEFT);
			$profilePromotions[$profile->getId()] = $binary;
			$profileDurations[$profile->getId()] = $profile->getDuration();
		}

		$fields = $allegro->getSellFormFields();
//		$counter = 0;
//		foreach ( $fields as $f )
//		{
//			$counter++;
//			print_r($f);
//			if ( $counter == 50 )
//				exit;
//		}
//		exit;
		$form = $this->createWizardForm($fields, $profiles);


		// Sposoby dostawy
		$extrDelivery = $extras = array();
		for ($i = 36; $i <= 52; $i++) {
			$field = $fields[$i];
			$label = $field->{'sell-form-title'};
			$label = preg_replace('/\([a-z\s]+\)/i', '', $label);
			$extrDelivery[$i] = $label;
		}
		asort($extrDelivery);


		$form = $form->getForm();
        if ($request->isMethod('POST')) {
            $form->bind($request);

            if ($form->isValid()) {

				$wizard = $form->getData();
				$em     = $this->get('doctrine')->getManager();

                if ( $wizard->getProfiles() > 0 )
				{
					$profile = $this->getDoctrine()
						->getRepository('ShoploAllegroBundle:Profile')
						->findOneBy(array('id' => $wizard->getProfiles() ));
				}
				else
				{
					$defaultProfile = array_shift($profiles);

					$delivery = $wizard->getDelivery();
					$delivery = !empty($delivery) ? (int) array_sum($delivery) : 0;


					$profile = new Profile();
					$profile->setDuration( $wizard->getDuration() );
					$profile->setCountry( $defaultProfile->getCountry() );
					$profile->setCity( $defaultProfile->getCity() );
					$profile->setState( $defaultProfile->getState() );
					$profile->setZipcode( $defaultProfile->getZipcode() );
					$profile->setDelivery( $delivery );
					$profile->setPayments( $wizard->getPayments() );

					$extraDelivery = $wizard->getExtraDelivery();
					$optDeliveries = array();
					foreach ( $extraDelivery as $k )
					{
						if ( isset($_POST['extra_delivery_price'][$k]) )
						{
							$v = str_replace(',', '.', $_POST['extra_delivery_price'][$k]);
							$v = round($v, 2);
							$optDeliveries[$k] = $v;
						}
					}
					$profile->setExtras( $optDeliveries );
				}

				foreach ($products as $product) {
                    foreach ($product['variants'] as $variant) {
                        $categoryId = $_POST['category'][$variant['id']];

                        $fields     = $wizard->export($profile, $product, $variant, $categoryId);

						$repository = $this->getDoctrine()->getRepository('ShoploAllegroBundle:CategoryAllegro');
                        // Obsługa dodatkowych (wymaganych) pól Allegro
                        $extraFields = $allegro->getCategoryFields($categoryId, false, $repository);
						if (isset($_POST['extras'][$variant['id']][$categoryId])) {
                            foreach ($_POST['extras'][$variant['id']][$categoryId] as $key => $value) {
                                $field = $extraFields[$key];

								// Pomijamy opcjonalne nieuzupelnione pola
								if ( $field['sell-form-opt'] !== 1 && !$value ) {
									unset($extraFields[$key]);
									continue;
								}

                                switch ($field['sell-form-res-type']) {
                                    case 1: // string
                                        $fields[] = Wizard::createField($key, (string) $value);
                                        break;
                                    case 2: // integer
                                        $fields[] = Wizard::createField($key, (int) $value);
                                        break;
                                    case 3: // float
                                        $fields[] = Wizard::createField($key, (float) $value);
                                        break;
                                    case 7: // image (base64Binary)
                                        $fields[] = Wizard::createField($key, $value, true);
                                        break;
                                    case 9: // datetime (Unix time)
                                        $fields[] = Wizard::createField($key, $value);
                                        break;
                                    case 13: // date
                                        $fields[] = Wizard::createField($key, $value);
                                        break;
                                }
                            }
                        }

                        $missingFields = Allegro::getMissingFields($fields, $extraFields);

                        if (!empty($missingFields)) {
                            $extras[$categoryId] = $missingFields;
                        }

                        if (!empty($extras)) {
                            continue;
                        }

						$auctionPrice = $this->calculateAuction($fields);
						if ( $auctionPrice === false )
						{
							$this->getRequest()->getSession()->setFlash(
								"error",
								$this->_message
							);
//							return $this->redirect($this->generateUrl('shoplo_allegro_homepage'));
							$url = $this->getRequest()->getUri();
							return $this->redirect($url);
						}
						$auctionPrice = trim(substr(str_replace(',', '.', $auctionPrice), 0, -3));

						$itemId = $this->createAuction($fields);
						if ( $itemId === false )
						{
							$this->getRequest()->getSession()->setFlash(
								"error",
								$this->_message
							);
//							return $this->redirect($this->generateUrl('shoplo_allegro_homepage'));
							$url = $this->getRequest()->getUri();
							return $this->redirect($url);
						}


						if ( $auctionPrice != $wizard->getAuctionPrice() )
						{
							$admin = new Admin( $this->get('mailer') );
							$admin->notifyByEmail(
								"Auction Price Differ",
								"Allegro price: {$auctionPrice}\nOur price: {$wizard->getAuctionPrice()}\n in auction {$itemId}"
							);
						}


						$item = new Item();
                        $days = array(3, 5, 7, 10, 14, 30);
                        $item
                            ->setId($itemId)
                            ->setUser($this->getUser())
                            ->setVariantId($variant['id'])
                            ->setProductId($product['id'])
                            ->setPrice($variant['price'])
                            ->setQuantity($variant['quantity'])
							->setQuantityAll($variant['add_to_magazine'] ? $variant['quantity_all'] : -1)
							->setQuantitySold(0)
							->setViewsCount(0)
							->setWatchCount(0)
							->setAuctionPrice($auctionPrice)
                            ->setStartAt(new \DateTime('now'))
                            ->setEndAt(new \DateTime('+' . $days[$profile->getDuration()] . ' days'));

                        $em->persist($item);
						$em->flush();
                    }
                }


                if (empty($extras)) {
					$this->getRequest()->getSession()->setFlash(
						"success",
						"Gratulacje. Twoja Aukcja została utworzona:) Będzie ona widoczna w przeciągu kilku minut."
					);
                    return $this->redirect($this->generateUrl('shoplo_allegro_homepage'));
                }
				else {
					$this->getRequest()->getSession()->setFlash(
						"error",
						"Uzupełnij brakujące pola przypisane do kategorii."
					);
					return $this->redirect($this->generateUrl('shoplo_allegro_homepage'));
				}
            }
			else
			{
				$this->getRequest()->getSession()->setFlash(
					"error",
					"Uzupełnij brakujące pola formularza."
				);
				return $this->redirect($this->generateUrl('shoplo_allegro_homepage'));
			}
        }

        if (!empty($extras)) {
            foreach ($extras as $categoryId => $fields) {
                foreach ($fields as $k => $field) {
                    switch ($field['sell-form-type']) {
						case 1: // string
						case 2: // integer
						case 3: // float
							$field = array(
								'id'      => $field['sell-form-id'],
								'label'   => $field['sell-form-title'],
								'title'   => $field['sell-form-field-desc'],
								'type'	  => 'input',
								'required'=> $field['sell-form-opt'] == 1 ? true : false,
							);
							break;
						case 4: // combobox
						case 5: // radiobutton
						case 6: // checkbox
                            $field = array(
                                'id'      => $field['sell-form-id'],
                                'label'   => $field['sell-form-title'],
                                'title'   => $field['sell-form-field-desc'],
                                'options' => array_combine(
                                    explode('|', $field['sell-form-opts-values']),
                                    explode('|', $field['sell-form-desc'])
                                ),
								'type'	  => 'select',
								'required'=> $field['sell-form-opt'] == 1 ? true : false,
                            );
                            break;
						case 8: // text
							$field = array(
								'id'      => $field['sell-form-id'],
								'label'   => $field['sell-form-title'],
								'title'   => $field['sell-form-field-desc'],
								'type'	  => 'textarea',
								'required'=> $field['sell-form-opt'] == 1 ? true : false,
							);
							break;
                        default:
                            throw new InvalidArgumentException;
                    }

                    $extras[$categoryId][$k] = $field;
                }
            }
        }

		$repository = $this->getDoctrine()->getRepository('ShoploAllegroBundle:CategoryAllegro');
		$extraParams = $categoryFields = array();
		foreach ( $variants as $variant )
		{
			foreach ( $variant['categories'] as $category )
			{
				if ( !isset($categoryFields[$category->getAllegroId()]) )
				{
					/** @var Category $category **/
					$extraFields = $allegro->getCategoryFields($category->getAllegroId(), false, $repository);
					$categoryFields[$category->getAllegroId()] = $extraFields;

					usort($extraFields, function($a, $b){
						if ( $a['sell-form-opt'] == $b['sell-form-opt'] )
						{
							return 0;
						}
						return ($a['sell-form-opt'] < $b['sell-form-opt']) ? 1 : -1;
					});
				}
				else
				{
					$extraFields = $categoryFields[$category->getAllegroId()];
				}

				foreach ( $extraFields as $k => $field )
				{
					$f = array(
						'id'      => $field['sell-form-id'],
						'label'   => $field['sell-form-title'],
						'title'   => $field['sell-form-field-desc'],
						'unit'	  => $field['sell-form-unit'],
						'required'=> $field['sell-form-opt'] == 1 ? true : false,
					);
					switch ($field['sell-form-type']) {
						case 1: // string
						case 2: // integer
						case 3: // float
							$f['type'] = 'input';
							break;
						case 4: // combobox
						case 5: // radiobutton
						case 6: // checkbox
							$f['type'] = 'select';
							$f['options'] = array_combine(
								explode('|', $field['sell-form-opts-values']),
								explode('|', $field['sell-form-desc'])
							);
							break;
						case 8: // text
							$f['type'] = 'textarea';
							break;
						default:
							throw new InvalidArgumentException;
					}

					$extraParams[$variant['id']][$category->getAllegroId()][$k] = $f;
				}
			}
		}

        return $this->render(
            'ShoploAllegroBundle::wizard.html.twig',
            array(
                'form'     => $form->createView(),
                'extras'   => $extras,
                'ids'      => $ids,
                'variants' => $variants,
                'products' => $products,
				'profiles' => $profiles,
				'profile_promotions' => $profilePromotions,
				'profile_durations'	 => $profileDurations,
				'extra_delivery' => $extrDelivery,
				'extra_delivery_price' => isset($_POST['extra_delivery_price']) ? $_POST['extra_delivery_price'] : array(),
				'extra_params' => $extraParams,
            )
        );
    }

	private function getProducts($shoplo, $ids)
	{
		$variants = $products = $productsWithNoCategories = array();

		foreach ($ids as $id) {
			$product = $shoplo->get('products', $id);

			// Kategorie
			$categoryIDs = $categories = array();

			if ( isset($product['categories']) && !empty($product['categories']) )
			{
				foreach ($product['categories'] as $category) {
					$categoryIDs[] = $category['id'];
				}

				$categories = $this->getDoctrine()
					->getRepository('ShoploAllegroBundle:Category')
					->findBy(array('shop_id' => $this->getUser()->getShopId(), 'shoplo_id' => $categoryIDs));
			}
			else
			{
				$productsWithNoCategories[] = $product['name'];
			}


			if (count($categoryIDs) !== count($categories)) {
				throw new \Exception();
			}

			$doubles = array();
			foreach ( $categories as $k => $c )
			{
				if ( in_array($c->getAllegroId(), $doubles) )
				{
					unset($categories[$k]);
					continue;
				}
				else
				{
					$doubles[$k] = $c->getAllegroId();
				}

				$categoryAllegro = $this->getDoctrine()->getRepository('ShoploAllegroBundle:CategoryAllegro')->findOneById($c->getAllegroId());
				$categories[$k]->tree = ($categoryAllegro instanceof CategoryAllegro) ? $categoryAllegro->getTree() : '';
			}

			foreach ($product['variants'] as $variant) {
				$variant['categories']    = array_values($categories);
				$variant['default_category'] = $variant['categories'][0];
				$variant['image_count']	  = count($product['images']);
				$variants[$variant['id']] = $variant;

			}

			$products[] = $product;
		}
		return array($products, $variants, $productsWithNoCategories);
	}

	private function createWizardForm($fields, $profiles)
	{
		// Czas trwania
		$preferredDurations = array();
		$durations          = array_combine(
			explode('|', $fields[4]->{'sell-form-opts-values'}),
			explode('|', $fields[4]->{'sell-form-desc'})
		);
//		$durations = array_filter(
//			$durations,
//			function ($duration) {
//				return !in_array($duration, array('30'));
//			}
//		);
		$durations          = array_map(
			function ($value) {
				//return $value . ' dni';
				return $value == 30 ? $value . ' dni - sklepy allegro' : $value . ' dni';
			},
			$durations
		);
		if (false !== $key = array_search('10 dni', $durations)) {
			$preferredDurations[] = $key;
		}
		// Opcje dodatkowe
		$promotions = array_combine(
			explode('|', $fields[15]->{'sell-form-opts-values'}),
			explode('|', $fields[15]->{'sell-form-desc'})
		);
		$promotions = array_filter(
			$promotions,
			function ($promotion) {
				return !in_array($promotion, array('-', 'Znak wodny'));
			}
		);
		$payments = array_combine(
			explode('|', $fields[14]->{'sell-form-opts-values'}),
			explode('|', $fields[14]->{'sell-form-desc'})
		);

		$payments = array_filter(
			$payments,
			function ($payment) {
				return !in_array($payment, array('-', 'Inne rodzaje płatności', 'Szczegoly w opisie'));
			}
		);
		$delivery = array_combine(
			explode('|', $fields[35]->{'sell-form-opts-values'}),
			explode('|', $fields[35]->{'sell-form-desc'})
		);
		$delivery = array_filter(
			$delivery,
			function ($d) {
				return $d !== '-';
			}
		);
		$profileOptions = array();
		foreach ( $profiles as $p ) {
			$profileOptions[$p->getId()] = $p->getName();
		}
		$profileOptions += array(-1 => 'Bez profilu, ustawię ręcznie');

		$imageOptions = array(
			'all'	=>	'dodaj do aukcji wszystkie zdjęcia produktu',
			'one'	=>	'dodaj do aukcji tylko zdjęcie główne produktu',
		);


		// Sposoby dostawy
		$extrDelivery = $extras = array();
		for ($i = 36; $i <= 52; $i++) {
			$field = $fields[$i];
			$label = $field->{'sell-form-title'};
			$label = preg_replace('/\([a-z\s]+\)/i', '', $label);
			$extrDelivery[$i] = $label;
		}
		asort($extrDelivery);

		$wizard = new Wizard();
		$form   = $this->createFormBuilder($wizard)
			->add('title', 'text', array('attr'=> array('maxlength'=>Allegro::LIMIT_ALLEGRO_TITLE))) // TODO: Ustawienie maksymalnej długości LIMIT_ALLEGRO-MAX(nazwa_wariantu)
			->add('description', 'textarea')
			->add('quantity', 'text')
			->add('all_stock', 'checkbox', array('required' => false))
			->add('profiles', 'choice', array('choices' => $profileOptions))
			->add('duration', 'choice', array('choices' => $durations, 'preferred_choices' => $preferredDurations))
			->add('promotions', 'choice', array('choices' => $promotions, 'multiple' => true, 'expanded' => true))
			->add('payments', 'choice', array('choices' => $payments, 'multiple' => true, 'expanded' => true))
			->add('delivery', 'choice', array('choices' => $delivery, 'multiple' => true, 'expanded' => true))
			->add('extra_delivery', 'choice', array('choices' => $extrDelivery, 'multiple' => true, 'expanded' => true, 'required' => false))
			->add('images', 'choice', array('choices' => $imageOptions, 'expanded' => true))
			->add('price', 'choice', array('choices' => $wizard->getPriceOptions()))
			->add('extra_price', 'text', array('required' => false))
			->add('auction_price', 'hidden', array('required' => false));

		return $form;
	}

	private function calculateAuction(array $fields)
	{
		/** @var $allegro Allegro */
		$allegro = $this->get('allegro');
		if (!$allegro->login($this->getUser())) {
			$this->_message = 'Użytkownik nie jest zalogowany';
			return false;
			//throw new AccessDeniedException();
		}

		try {
			$item = $allegro->doCheckNewAuctionExt($allegro->getSession(), $fields);
		} catch (\SoapFault $sf) {
			$this->_message = $sf->getMessage();
//			$this->get('logger')->err('Method: doCheckNewAuctionExt | user id: '.$this->getUser()->getId().' | SoapFault code: '.$sf->getCode().' | SoapFault msg: '.$sf->getMessage());
//			$this->get('session')->setFlash(
//				"error",
//				$sf->getMessage()
//			);

			return false;
		}
		return $item['item-price'];
	}

	private function createAuction(array $fields)
    {
        /** @var $allegro Allegro */
        $allegro = $this->get('allegro');
        if (!$allegro->login($this->getUser())) {
			$this->_message = 'Użytkownik nie jest zalogowany';
            throw new AccessDeniedException();
        }

		try {
			$item = $allegro->doNewAuctionExt($allegro->getSession(), $fields);
		} catch (\SoapFault $sf) {
			$this->_message = $sf->getMessage();
//			$this->getRequest()->getSession()->setFlash(
//				"error",
//				$sf->getMessage()
//			);
			//$this->get('logger')->err('Method: doNewAuctionExt | user id: '.$this->getUser()->getId().' | SoapFault code: '.$sf->getCode().' | SoapFault msg: '.$sf->getMessage());
			return false;
		}


        return $item['item-id'];
    }

	private function getPromotions($fields)
	{
		// Czas trwania
		$preferredDurations = array();
		$durations          = array_combine(
			explode('|', $fields[4]->{'sell-form-opts-values'}),
			explode('|', $fields[4]->{'sell-form-desc'})
		);
		$durations          = array_map(
			function ($value) {
				return $value . ' dni';
			},
			$durations
		);
		if (false !== $key = array_search('10 dni', $durations)) {
			$preferredDurations[] = $key;
		}

		// Opcje dodatkowe
		$promotions = array_combine(
			explode('|', $fields[15]->{'sell-form-opts-values'}),
			explode('|', $fields[15]->{'sell-form-desc'})
		);
		return $promotions;
	}
}
