<?php

namespace Shoplo\AllegroBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Request;

class HomepageController extends Controller
{
    /**
     * @Secure(roles="ROLE_ADMIN")
     */
    public function indexAction(Request $request)
    {
        if ($ids = $request->query->get('ids')) {
            $ids = explode(',', $ids);

            return $this->redirect($this->generateUrl('shoplo_allegro_wizard', array('product' => $ids)));
        }

		$items = $this->getDoctrine()
			->getRepository('ShoploAllegroBundle:Item')
			->findAll(
			array(),
			array('id' => 'DESC')
		);

        return $this->render('ShoploAllegroBundle::homepage.html.twig', array('items' => $items));
    }

    /**
     * @Secure(roles="ROLE_USER")
     */
    public function footerAction()
    {
        $shoplo = $this->container->get('shoplo');
        $shop   = $shoplo->get('shop');

        return $this->render('ShoploAllegroBundle::footer.html.twig', array('shop' => $shop));
    }

	/**
	 * @Secure(roles="ROLE_USER")
	 */
	public function navbarAction()
	{
		$shoplo = $this->container->get('shoplo');
		$shop   = $shoplo->get('shop');

		return $this->render('ShoploAllegroBundle::navbar.html.twig', array('shop' => $shop));
	}

    public function loginAction()
    {
        $url = $this->container->get('hwi_oauth.security.oauth_utils')->getAuthorizationUrl('shoplo');

        return $this->redirect($url);
    }
}
