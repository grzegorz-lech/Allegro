<?php

namespace Shoplo\AllegroBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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
            //return $this->redirect($this->generateUrl('shoplo_allegro_settings_profile'));
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

                    return $this->redirect($this->generateUrl('shoplo_allegro_settings_profile'));
                }
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
    public function profileAction()
    {
		$user = $this->getUser();

		$allegro = $this->container->get('allegro');
		$allegro->login($user);

		$shoploCategories = $this->container->get('shoplo')->get('categories');
		$allegroCategories = $this->getDoctrine()
			->getRepository('ShoploAllegroBundle:CategoryAllegro')
			->findBy(
			array('parent'=>0),
			array('position'=>'ASC')
		);


		if ( $this->getRequest()->isMethod('POST') )
		{
			$params = $this->getRequest()->request->all();


			$shoplo  = $this->container->get('shoplo');
			$shop    = $shoplo->get('shop');


			$allegroCategoryIds = array_values($params['map']);
			$allegroCategories = $this->getDoctrine()
				->getRepository('ShoploAllegroBundle:CategoryAllegro')
				->findBy(
				array('id'=>$allegroCategoryIds)
			);
			$allegroCategoriesMap = array();
			foreach ( $allegroCategories as $ac )
			{
				$allegroCategoriesMap[$ac->getId()] = $ac;
			}


			$em = $this->getDoctrine()->getManager();
			foreach ( $shoploCategories as $sc )
			{
				$allegroCategory = $allegroCategoriesMap[$params['map'][$sc['id']]];
				$c = new Category();
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

	public function getCategoryChildrenAction()
	{
		$user = $this->getUser();
		$allegro = $this->container->get('allegro');
		$allegro->login($user);

		$categoryId = $this->getRequest()->get('category_id', 0);
		$allegroCategories = $this->getDoctrine()
			->getRepository('ShoploAllegroBundle:CategoryAllegro')
			->findBy(
			array('parent'=>$categoryId),
			array('position'=>'ASC')
		);

		$categories = array();
		foreach ( $allegroCategories as $ac )
		{
			$categories[] = array(
				'id'	=>	$ac->getId(),
				'name'	=>	$ac->getName(),
				'childs_count'	=>	count($this->getDoctrine()
					->getRepository('ShoploAllegroBundle:CategoryAllegro')
					->findBy(
					array('parent'=>$ac->getId())
				))
			);
		}

		$json = json_encode($categories);
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent($json);

		return $response;
	}
}
