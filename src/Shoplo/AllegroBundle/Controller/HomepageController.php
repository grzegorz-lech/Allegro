<?php

namespace Shoplo\AllegroBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Request;
use Shoplo\AllegroBundle\Entity\Item;

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

        $shoplo  = $this->container->get('shoplo');
		$shop   = $shoplo->get('shop');

		# TODO: stronicowanie
        $items = $this->getDoctrine()
            ->getRepository('ShoploAllegroBundle:Item')
            ->findBy(
            array('user_id' => $this->getUser()->getId()),
            array('id' => 'DESC')
        );

		$finishItems = $activeItems = array();
		foreach ( $items as $item )
		{
			if ( $item->isFinish() )
			{
				$finishItems[$item->getId()] = $item;
			}
			else
			{
				$activeItems[$item->getId()] = $item;
			}
		}

		$allegro = $this->get('allegro');
		$allegro->login($this->getUser());
		#TODO: odpytywac sie nie czesciej niz co 5min, uzyc cache'a
		$ids = array_keys($activeItems);
		$result = $allegro->getItemsInfo($ids);
		if ( is_array($result) && !empty($result['array-item-list-info']) )
		{
			foreach ( $result['array-item-list-info'] as $itemInfo )
			{
				$itemInfo = (array) $itemInfo->{'item-info'};
				if ( isset($activeItems[(string)$itemInfo['it-id']]) && $itemInfo['it-hit-count'] != $activeItems[(string)$itemInfo['it-id']]->getViewsCount() )
				{
					$activeItems[(string)$itemInfo['it-id']]->setViewsCount($itemInfo['it-hit-count']);
				}
			}
			$this->getDoctrine()->getManager()->flush();

		}


        return $this->render('ShoploAllegroBundle::homepage.html.twig', array('active_items' => $activeItems, 'finish_items' => $finishItems, 'shoplo' => $shoplo, 'shop'=>$shop));
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

    public function showAction($itemId)
    {
        switch ($this->getUser()->getCountry()) {
            case 1:
                $url = 'http://allegro.pl/i%d.html';
                break;

            case 228:
                $url = 'http://www.testwebapi.pl/show_item.php?item=%d';
                break;

            default:
                throw $this->createNotFoundException();
        }

        return $this->redirect(sprintf($url, $itemId));
    }

	public function deleteAction($itemId, $action)
	{
		$item = $this->getDoctrine()
			->getRepository('ShoploAllegroBundle:Item')
			->findOneBy(
			array('id' => $itemId, 'user_id' => $this->getUser()->getId())
		);
		if ( !($item instanceof Item) )
		{
			throw $this->createNotFoundException('Resource not found');
		}

		$allegro = $this->get('allegro');
		$allegro->login($this->getUser());

		$result = $allegro->removeItem($itemId);
		if ( $result === true || $action == 'force' )
		{
			$em = $this->getDoctrine()->getManager();
			$em->remove($item);
			$em->flush();

			$this->get('session')->setFlash(
				"success",
				"Aukcja została usunięta."
			);
		}
		else
		{
			$link = $this->generateUrl('shoplo_allegro_delete_item', array('action'=>'force'));
			$this->get('session')->setFlash(
				"error",
				"Komunikat od Allegro\n".
					$result."\n".
					"<a href='{$link}'>Usuń aukcję z Shoplo</a>"

			);
		}

		return $this->redirect( $this->generateUrl('shoplo_allegro_homepage') );
	}
}
