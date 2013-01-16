<?php

namespace Shoplo\AllegroBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Shoplo\AllegroBundle\Entity\Profile;
use Shoplo\AllegroBundle\WebAPI\Allegro;
use Shoplo\AllegroBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Response;
use Shoplo\AllegroBundle\Entity\CategoryAllegro;
use Shoplo\AllegroBundle\Entity\Category;
use Shoplo\AllegroBundle\Entity\ShoploOrder;

class SettingsController extends Controller
{
    /**
     * @Secure(roles="ROLE_USER")
     */
    public function loginAction(Request $request)
    {
        $security = $this->get('security.context');

        if ($security->isGranted('ROLE_ADMIN')) {
            return $this->redirect($this->generateUrl('shoplo_allegro_settings_location'));
        }

        $allegro = $this->container->get('allegro');
        $shoplo  = $this->container->get('shoplo');
        $shop    = $shoplo->get('shop');
        $user    = $security->getToken()->getUser()->setCountry($allegro->getCountryCode($shop['country']));
        $form    = $this->createFormBuilder($user)
            ->add('username', 'text')
            ->add('password', 'password')
            ->getForm();

        if ($request->isMethod('POST')) {
            $form->bind($request);

            if ($form->isValid()) {
                if ($allegro->login($form->getData())) {
                    /** @var $user User */
                    $user = $form->getData();

                    // Save in DB
                    $em = $this->getDoctrine()->getManager();
                    $em->merge($user);
                    $em->flush();

                    // Add role
                    $user->addRole('ROLE_ADMIN', $security->getToken(), $request->getSession());

                    return $this->redirect($this->generateUrl('shoplo_allegro_settings_location'));
                }
            }
        }

        return $this->render(
            'ShoploAllegroBundle::settings.html.twig',
            array(
                'form' => $form->createView(),
                'step' => 1,
				'stage'=> 'init',
            )
        );
    }

    /**
     * @Secure(roles="ROLE_ADMIN")
     */
    public function locationAction(Request $request)
    {
        /** @var $allegro Allegro */
        $allegro = $this->get('allegro');
		$user    = $this->getUser();
        $allegro->login($user);
        $states = array();
        foreach ($allegro->doGetStatesInfo($allegro->getCountry(), $allegro->getKey()) as $state) {
            $states[$state->{'state-id'}] = $state->{'state-name'};
        }

        // Województwo na podstawie GeoAPI
        $shop            = $this->get('shoplo')->get('shop');
        $preferredStates = array();
        $url             = 'http://geoapi.goldenline.pl/?' . http_build_query(
            array(
                'method'   => 'geo.city.getByZipCode',
                'zip_code' => $shop['zip_code'],
            )
        );
        if (false !== $json = @file_get_contents($url)) {
            if (false !== $data = json_decode($json, true)) {
                if (false !== $key = array_search($data['province'], $states)) {
                    $preferredStates[] = $key;
                }
            }
        }

        $form = $this->createFormBuilder()
            ->add('state', 'choice', array('choices' => $states, 'preferred_choices' => $preferredStates))
            ->add('city', 'text', array('data' => $shop['city']))
            ->add(
            'zipcode',
            'text',
            array('data' => $shop['zip_code'], 'attr' => array('pattern' => '[0-9]{2}-[0-9]{3}'))
        )
            ->getForm();

        if ($request->isMethod('POST')) {
            $form->bind($request);

            if ($form->isValid()) {
                /** @var $session \Symfony\Component\HttpFoundation\Session\Session */
                $session         = $this->get('session');
                $data            = $form->getData();
                $data['country'] = $this->getUser()->getCountry();
                $session->set('default_profile', $data);

                return $this->redirect($this->generateUrl('shoplo_allegro_settings_auction'));
            }
        }

        return $this->render(
            'ShoploAllegroBundle::settings.html.twig',
            array(
                'form' => $form->createView(),
                'step' => 2,
				'stage'=> 'init',
            )
        );
    }

    /**
     * @Secure(roles="ROLE_ADMIN")
     */
    public function auctionAction(Request $request, $profileId=null)
    {
		/** @var $allegro Allegro */
        $allegro = $this->get('allegro');
        $user    = $this->getUser();
        $allegro->login($user);
        $fields = $allegro->getSellFormFields();

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
		$promotions = array_filter(
			$promotions,
			function ($promotion) {
				return !in_array($promotion, array('-'));
			}
		);

		$defaults = array();
		$stage = 'init';
		if ( !is_null($profileId) )
		{
			$profile = $this->getDoctrine()
				->getRepository('ShoploAllegroBundle:Profile')
				->findOneById($profileId);
			if ( !($profile instanceof Profile) )
			{
				throw $this->createNotFoundException('Profile not found');
			}
			$stage = 'edit';
			$defaults['profile_name'] = $profile->getName();
		}
		else
		{
			$count = $this->getDoctrine()
				->getManager()
				->createQuery('SELECT COUNT(p) FROM ShoploAllegroBundle:Profile p WHERE p.user_id=:user_id')
				->setParameter('user_id', $this->getUser()->getId())
				->getSingleScalarResult();
			if ( $count > 0 )
			{
				$stage = 'new';
			}
		}



		$form = $this->createFormBuilder($defaults)
            ->add('duration', 'choice', array('choices' => $durations, 'preferred_choices' => $preferredDurations))
			->add('all_stock', 'checkbox', array('required' => false, 'attr'=> array('class' => 'ez-hide')))
            ->add('promotions', 'choice', array('choices' => $promotions, 'multiple' => true, 'expanded' => true));

		if ( $stage != 'init' )
		{
			$form->add('profile_name', 'text');
		}

		$form = $form->getForm();

        if ($request->isMethod('POST')) {
            $form->bind($request);

            if ($form->isValid()) {

				$data               = $form->getData();

				$data['promotions'] = array_sum($data['promotions']); // TODO: Symfony way

                /** @var $session \Symfony\Component\HttpFoundation\Session\Session */
                $session = $this->get('session');
                $session->set('default_profile', array_merge($session->get('default_profile'), $data));

                return $this->redirect($this->generateUrl('shoplo_allegro_settings_payment'));
            }
        }

        return $this->render(
            'ShoploAllegroBundle::settings.html.twig',
            array(
                'form' => $form->createView(),
				'step' => 3,
				'stage'=> $stage,
			)
        );
    }

    /**
     * @Secure(roles="ROLE_ADMIN")
     */
    public function paymentAction(Request $request)
    {
        /** @var $allegro Allegro */
        $allegro = $this->get('allegro');
        $user    = $this->getUser();
        $allegro->login($user);
        $fields = $allegro->getSellFormFields();

        // Sposób płatności
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

		$count = $this->getDoctrine()
			->getManager()
			->createQuery('SELECT COUNT(p) FROM ShoploAllegroBundle:Profile p WHERE p.user_id=:user_id')
			->setParameter('user_id', $this->getUser()->getId())
			->getSingleScalarResult();


        $form = $this->createFormBuilder()
            ->add('payments', 'choice', array('choices' => $payments, 'multiple' => true, 'expanded' => true))
            ->getForm();

        if ($request->isMethod('POST')) {
            $form->bind($request);

            if ($form->isValid()) {
                $data             = $form->getData();

                $data['payments'] = array_sum($data['payments']); // TODO: Symfony way
                $data['pod']      = isset($_POST['pod']) && $_POST['pod'];

                /** @var $session \Symfony\Component\HttpFoundation\Session\Session */
                $session = $this->get('session');
                $session->set('default_profile', array_merge($session->get('default_profile'), $data));

                return $this->redirect($this->generateUrl('shoplo_allegro_settings_delivery'));
            }
        }

        return $this->render(
            'ShoploAllegroBundle::settings.html.twig',
            array(
				'stage' => $count == 0 ? 'init' : 'new',
                'form' => $form->createView(),
                'step' => 4,
            )
        );
    }

    /**
     * @Secure(roles="ROLE_ADMIN")
     */
    public function deliveryAction(Request $request)
    {
        /** @var $allegro Allegro */
        $allegro = $this->get('allegro');
        $user    = $this->getUser();
        $allegro->login($user);
        $fields = $allegro->getSellFormFields();

        $form = $this->createFormBuilder();

        // Darmowa dostawa
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

		$count = $this->getDoctrine()
			->getManager()
			->createQuery('SELECT COUNT(p) FROM ShoploAllegroBundle:Profile p WHERE p.user_id=:user_id')
			->setParameter('user_id', $this->getUser()->getId())
			->getSingleScalarResult();

        $form->add('delivery', 'choice', array('choices' => $delivery, 'multiple' => true, 'expanded' => true));

        // Sposoby dostawy
        $pod      = $this->get('session')->get('default_profile');
        $delivery = array();

        for ($i = 36; $i <= 52; $i++) {
            $field = $fields[$i];
            $label = $field->{'sell-form-title'};
            $label = preg_replace('/\([a-z\s]+\)/i', '', $label);

            if (!$pod && false !== stripos($label, 'pobraniowa')) {
                continue;
            }

            $delivery[$i] = $label;
        }

        asort($delivery);

        foreach ($delivery as $key => $value) {
            $form->add('fid' . $key, 'text', array('label' => $value, 'required' => false));
        }

        $form = $form->getForm();

        if ($request->isMethod('POST')) {
            $form->bind($request);

            if ($form->isValid()) {
                $data             = $form->getData();
                $data['delivery'] = array_sum($data['delivery']); // TODO: Symfony way

                // Dodatki
                $extras = array();
                foreach ($data as $key => $value) {
                    if (false === strpos($key, 'fid')) {
                        continue;
                    }

                    unset($data[$key]);

                    if (null === $value) {
                        continue;
                    }

                    $key          = substr($key, 3);
                    $value        = str_replace(',', '.', $value);
                    $value        = round($value, 2);
                    $extras[$key] = $value;
                }

                /** @var $session \Symfony\Component\HttpFoundation\Session\Session */
                $session = $this->get('session');
                $data    = array_merge($session->get('default_profile'), $data);
				$profileName = isset($data['profile_name']) ? $data['profile_name'] : 'Domyślny';
				unset($data['pod'], $data['profile_name']);

                $em      = $this->getDoctrine()->getManager();
                $profile = new Profile($data);

                $profile
                    ->setUserId($this->getUser()->getId())
                    ->setName( $profileName )
                    ->setExtras($extras)
					->setCreatedAt( new \DateTime() );

                $em->persist($profile);
                $em->flush();

				$redirect = $count == 0 ? 'shoplo_allegro_settings_mapping' : 'shoplo_allegro_profiles';
				if ( $count > 0 )
				{
					$this->get('session')->setFlash(
						"success",
						"Twój profil aukcji został utworzony."
					);
				}

                return $this->redirect($this->generateUrl($redirect));
            }
        }

        return $this->render(
            'ShoploAllegroBundle::settings.html.twig',
            array(
				'stage' => $count == 0 ? 'init' : 'new',
                'form' => $form->createView(),
                'step' => 5,
            )
        );
    }

    /**
     * @Secure(roles="ROLE_ADMIN")
     */
    public function mappingAction(Request $request)
    {
        $user    = $this->getUser();
        $allegro = $this->container->get('allegro');
        $allegro->login($user);

        $shoplo            = $this->get('shoplo');
        $count             = $shoplo->get('categories/count');
        $shoploCategories  = $shoplo->get('categories', null, array('limit' => $count));

		$sorted = $matches = array();
		foreach ( $shoploCategories as $sc )
		{
			if ( isset($sorted[$sc['parent']]) )
			{
				$sorted[$sc['parent']]['childs'][$sc['id']] = $sc;
			}
			else
			{
				$sorted[$sc['id']] = $sc;
			}
		}
		foreach ( $sorted as $s )
		{
			$tmp = $s;
			unset($tmp['childs']);
			if ( $s['parent'] == 0 )
			{
				$matches[$tmp['id']] = $tmp;
				if ( isset($s['childs']) )
				{
					$matches = $matches + $s['childs'];
				}
			}
			else
			{
				$keys = array_keys($matches);
				$pos = array_search($tmp['parent'], $keys);
				$matches = array_slice($matches, 0, $pos+1, true) + array($tmp['id']=>$tmp) + (isset($s['childs']) ? $s['childs'] : array()) +  array_slice($matches, $pos, count($matches)-$pos, true);
			}

		}

        $allegroCategories = $this->getDoctrine()
            ->getRepository('ShoploAllegroBundle:CategoryAllegro')
            ->findBy(
            array('country_id' => $allegro->getCountry(), 'parent' => null),
            array('position' => 'ASC')
        );

        // TODO: Pobranie i wyświetlenie już zmapowanych kategorii

        $form = $this->createFormBuilder()
            ->add(
            'categories',
            'collection',
            array(
                'type'      => 'integer',
                'allow_add' => true,
            )
        )
            ->getForm();

        if ($this->getRequest()->isMethod('POST')) {
            $form->bind($request);

            if ($form->isValid()) {
                $data                 = $form->getData();
                $allegroCategories    = $this->getDoctrine()
                    ->getRepository('ShoploAllegroBundle:CategoryAllegro')
                    ->findBy(array('id' => $data['categories']));
                $allegroCategoriesMap = array();
                foreach ($allegroCategories as $ac) {
                    /** @var $ac CategoryAllegro */
                    $allegroCategoriesMap[$ac->getId()] = $ac;
                }

                $shop  = $shoplo->get('shop');
                $em    = $this->getDoctrine()->getManager();
                $query = $em->createQuery(
                    'DELETE FROM ShoploAllegroBundle:Category c WHERE c.shop_id = ' . $shop['id']
                );
                $query->execute();

                foreach ($shoploCategories as $sc) {
                    /** @var $allegroCategory CategoryAllegro */
                    $allegroCategory = $allegroCategoriesMap[$data['categories'][$sc['id']]];
                    $c               = new Category();
                    $c->setAllegroId($allegroCategory->getId());
                    $c->setAllegroName($allegroCategory->getName());
                    $c->setAllegroParent($allegroCategory->getParent()->getId());
                    $c->setAllegroPosition($allegroCategory->getPosition());
                    $c->setShopId($shop['id']);
                    $c->setShoploId($sc['id']);
                    $c->setShoploName($sc['name']);
                    $c->setShoploParent($sc['parent']);
                    $c->setShoploPosition($sc['pos']);

                    $em->persist($c);
                }

                $em->flush();

                return $this->redirect($this->generateUrl('shoplo_allegro_homepage'));
            }
        }

        return $this->render(
            'ShoploAllegroBundle::categories.html.twig',
            array(
                'form'               => $form->createView(),
                'shoplo_categories'  => $matches,
                'allegro_categories' => $allegroCategories,
            )
        );
    }

    public function getCategoryChildrenAction($id)
    {
        $user    = $this->getUser();
        $allegro = $this->container->get('allegro');
        $allegro->login($user);

        $allegroCategories = $this->getDoctrine()
            ->getRepository('ShoploAllegroBundle:CategoryAllegro')
            ->findBy(
            array('country_id' => $allegro->getCountry(), 'parent' => $id),
            array('position' => 'ASC')
        );

        $categories = array();
        foreach ($allegroCategories as $ac) {
            $categories[] = array(
                'id'           => $ac->getId(),
                'name'         => $ac->getName(),
                'childs_count' => count(
                    $this->getDoctrine()
                        ->getRepository('ShoploAllegroBundle:CategoryAllegro')
                        ->findBy(
                        array('parent' => $ac->getId())
                    )
                )
            );
        }

        $json     = json_encode($categories);
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json; charset=utf-8');
        $response->setContent($json);

        return $response;
    }

    public function webhookAction(Request $request)
    {
        $whitelist = array(
            $request->server->get('SERVER_ADDR'),
            gethostbyname('shoplo.com'),
        );

//        if (!in_array($request->getClientIp(), $whitelist)) {
//            throw new AccessDeniedException();
//        }

		$shoploSection 	= trim($request->headers->get('shoplo-section'));
        $shoploShopId	= trim($request->headers->get('shoplo-shop-id'));
        $shoploHmacKey	= trim($request->headers->get('shoplo-hmac-sha256'));

        list($objectType, $action) = explode('/', $shoploSection, 2);
        $shoploObjectId = trim($request->headers->get('shoplo-'.strtolower($objectType).'-id'));

        $user = $this->getDoctrine()
            ->getRepository('ShoploAllegroBundle:User')
            ->findOneByShopId($shoploShopId);

		$msg = '';

        $calculatedHmacKey = base64_encode(hash_hmac('sha256', http_build_query($_POST), $this->container->getParameter('oauth_consumer_secret')));
        if ($calculatedHmacKey == $shoploHmacKey) {
            $allegro = $this->get('allegro');
            $allegro->login($user);

			$data = $request->request->get($objectType);
			$order = $this->getDoctrine()->getRepository('ShoploAllegroBundle:ShoploOrder')->findOneBy(array('order_id'=>$data['id'], 'user_id'=>$user->getId()));
			if ( !($order instanceof ShoploOrder) )
			{
				foreach ($data['order_items'] as $item) {
					// pobieramy aukcje, w których warianty zostaly sprzedane
					$repository = $this->getDoctrine()
						->getRepository('ShoploAllegroBundle:Item');
					$query = $repository->createQueryBuilder('p')
						->where('p.variant_id = :variant_id')
						->andWhere('p.user_id = :user_id')
						->andWhere('p.end_at > :end_at')
						->setParameters(array(
						'variant_id' => $item['variant_id'],
						'user_id'	 => $user->getId(),
						'end_at'	 => date('Y-m-d H:i:s')
					))
						->getQuery();
					$allegroItems = $query->getResult();
					foreach ($allegroItems as $allegroItem) {
						$quantityAll = $allegroItem->getQuantityAll();
						$quantity 	 = $allegroItem->getQuantity();
						if ( $quantityAll != -1 && $quantity > $allegroItem->getQuantitySold() )
						{
							$quantityAll = $quantityAll - $item['quantity'];
							$allegroItem->setQuantityAll($quantityAll);
							if ( $quantityAll <= $allegroItem->getQuantitySold() )
							{
								$result = $allegro->removeItem($allegroItem->getId());
								if ( !$result )
								{
									#TODO: log this
								}
								$allegroItem->setQuantity($allegroItem->getQuantitySold());
							}
							elseif ( $quantityAll < $quantity )
							{
								$allegroItem->setQuantity($quantityAll);
								$msg .= "Set {$allegroItem->getQuantity()} for item: " . $allegroItem->getId() . "|  <br />";
								$result = $allegro->updateItemQuantity($allegroItem->getId(), $allegroItem->getQuantity());
							}
						}
					}
				}

				$this->getDoctrine()->getManager()->flush();
			}
        }

        $response = new Response();
        $response->setContent('<html><body><h1>OK!<br />'.$msg.'</h1></body></html>');
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }
}
