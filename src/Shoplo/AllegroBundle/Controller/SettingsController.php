<?php

namespace Shoplo\AllegroBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Shoplo\AllegroBundle\Entity\Profile;
use Shoplo\AllegroBundle\WebAPI\Allegro;
use Shoplo\AllegroBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Response;
use Shoplo\AllegroBundle\Entity\CategoryAllegro;
use Shoplo\AllegroBundle\Entity\Category;

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
                $session = $this->get('session');
                $session->set('default_profile', $form->getData());

                return $this->redirect($this->generateUrl('shoplo_allegro_settings_auction'));
            }
        }

        return $this->render(
            'ShoploAllegroBundle::settings.html.twig',
            array(
                'form' => $form->createView(),
                'step' => 2,
            )
        );
    }

    /**
     * @Secure(roles="ROLE_ADMIN")
     */
    public function auctionAction(Request $request)
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

        $form = $this->createFormBuilder()
            ->add('duration', 'choice', array('choices' => $durations, 'preferred_choices' => $preferredDurations))
            ->add('promotions', 'choice', array('choices' => $promotions, 'multiple' => true, 'expanded' => true))
            ->getForm();

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
                return $payment !== '-';
            }
        );

        $form = $this->createFormBuilder()
            ->add('payments', 'choice', array('choices' => $payments, 'multiple' => true, 'expanded' => true))
            ->getForm();

        if ($request->isMethod('POST')) {
            $form->bind($request);

            if ($form->isValid()) {
                $data             = $form->getData();
                $data['payments'] = array_sum($data['payments']); // TODO: Symfony way

                /** @var $session \Symfony\Component\HttpFoundation\Session\Session */
                $session = $this->get('session');
                $session->set('default_profile', array_merge($session->get('default_profile'), $data));

                return $this->redirect($this->generateUrl('shoplo_allegro_settings_delivery'));
            }
        }

        return $this->render(
            'ShoploAllegroBundle::settings.html.twig',
            array(
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

        // Sposób płatności
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

        $form = $this->createFormBuilder()
            ->add('delivery', 'choice', array('choices' => $delivery, 'multiple' => true, 'expanded' => true))
            ->getForm();

        if ($request->isMethod('POST')) {
            $form->bind($request);

            if ($form->isValid()) {
                $data             = $form->getData();
                $data['delivery'] = array_sum($data['delivery']); // TODO: Symfony way

                /** @var $session \Symfony\Component\HttpFoundation\Session\Session */
                $session = $this->get('session');
                $data    = array_merge($session->get('default_profile'), $data);
                $em      = $this->getDoctrine()->getManager();
                $profile = new Profile($data);

                $profile
                    ->setUserId($this->getUser()->getId())
                    ->setName('Domyślny');

                $em->persist($profile);
                $em->flush();

                return $this->redirect($this->generateUrl('shoplo_allegro_homepage'));
            }
        }

        return $this->render(
            'ShoploAllegroBundle::settings.html.twig',
            array(
                'form' => $form->createView(),
                'step' => 5,
            )
        );
    }

    /**
     * @Secure(roles="ROLE_ADMIN")
     */
    public function profileAction()
    {
        $user = $this->getUser();

        $allegro = $this->container->get('allegro');
        $allegro->login($user);

        $shoploCategories  = $this->container->get('shoplo')->get('categories');
        $allegroCategories = $this->getDoctrine()
            ->getRepository('ShoploAllegroBundle:CategoryAllegro')
            ->findBy(
            array('country_id' => $allegro->getCountry(), 'parent' => null),
            array('position' => 'ASC')
        );


        if ($this->getRequest()->isMethod('POST')) {
            $params = $this->getRequest()->request->all();


            $shoplo = $this->container->get('shoplo');
            $shop   = $shoplo->get('shop');


            $allegroCategoryIds   = array_values($params['map']);
            $allegroCategories    = $this->getDoctrine()
                ->getRepository('ShoploAllegroBundle:CategoryAllegro')
                ->findBy(
                array('id' => $allegroCategoryIds)
            );
            $allegroCategoriesMap = array();
            foreach ($allegroCategories as $ac) {
                $allegroCategoriesMap[$ac->getId()] = $ac;
            }


            $em = $this->getDoctrine()->getManager();
            foreach ($shoploCategories as $sc) {
                $allegroCategory = $allegroCategoriesMap[$params['map'][$sc['id']]];
                $c               = new Category();
                $c->setAllegroId($allegroCategory->getId());
                $c->setAllegroName($allegroCategory->getName());
                $c->setAllegroParent($allegroCategory->getParent());
                $c->setAllegroPosition($allegroCategory->getPosition());
                $c->setShopId($shop['id']);
                $c->setShoploId($sc['id']);
                $c->setShoploName($sc['name']);
                $c->setShoploParent($sc['parent']);
                $c->setShoploPosition($sc['pos']);

                $em->persist($c);
            }
            $em->flush();

            // TODO: set success message to user

            return $this->redirect($this->generateUrl('shoplo_allegro_homepage'));
        }

//		$em = $this->getDoctrine()->getManager();
//		foreach ( $allegroCategories as $a )
//		{
//			$em->remove($a);
//		}
//		$em->flush();
//
//		$allegroCategories = $allegro->getCategories();
//		foreach ( $allegroCategories['cats-list'] as $a )
//		{
//			$cat = (array) $a;
//
//			$c = new CategoryAllegro();
//			$c->setId($cat['cat-id']);
//			$c->setName($cat['cat-name']);
//			$c->setParent($cat['cat-parent']);
//			$c->setPosition($cat['cat-position']);
//
//			$em->persist($c);
//		}
//		$em->flush();


        return $this->render(
            'ShoploAllegroBundle::categories.html.twig',
            array(
                'shoplo_categories'  => $shoploCategories,
                'allegro_categories' => $allegroCategories,
            )
        );

        //return $this->redirect($this->generateUrl('shoplo_allegro_homepage'));
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
}
