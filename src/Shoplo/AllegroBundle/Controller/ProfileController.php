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

class ProfileController extends Controller
{
    /**
     * @Secure(roles="ROLE_USER")
     */
    public function indexAction()
    {
		$profiles = $this->getDoctrine()
			->getRepository('ShoploAllegroBundle:Profile')
			->findBy(
				array('user_id'=>$this->getUser()->getId()),
				array('id' => 'DESC')
			);

        return $this->render(
            'ShoploAllegroBundle::profiles.html.twig',
            array(
                'profiles' => $profiles
            )
        );
    }

	/**
	 * @Secure(roles="ROLE_USER")
	 */
	public function editAction($profileId)
	{
		$profile = $this->getDoctrine()
			->getRepository('ShoploAllegroBundle:Profile')
			->findOneById($profileId);

		return $this->render(
			'ShoploAllegroBundle::profile_edit.html.twig',
			array(
				'profile' => $profile
			)
		);
	}

	/**
	 * @Secure(roles="ROLE_USER")
	 */
	public function deleteAction($profileId)
	{
		$profile = $this->getDoctrine()
			->getRepository('ShoploAllegroBundle:Profile')
			->findOneById($profileId);

		$em = $this->getDoctrine()->getManager();
		$em->remove($profile);
		$em->flush();

		$this->get('session')->setFlash(
			"success",
			"Twój profil aukcji został usunięty."
		);

		return $this->redirect($this->generateUrl('shoplo_allegro_profiles'));
	}
}
