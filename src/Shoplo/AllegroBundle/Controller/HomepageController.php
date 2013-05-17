<?php

namespace Shoplo\AllegroBundle\Controller;

use Shoplo\AllegroBundle\Entity\SellAgainItem;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Request;
use Shoplo\AllegroBundle\Entity\Item;

class HomepageController extends Controller
{
    /**
     * @Secure(roles="ROLE_USER")
     */
    public function indexAction($action, $page)
    {
		$security = $this->get('security.context');
		$user = $security->getToken()->getUser();
		if (!$user->getUsername()) {
			return $this->redirect($this->generateUrl('shoplo_allegro_settings'));
		}

		$request = $this->getRequest();
        if ($ids = $request->query->get('ids')) {
            $ids = explode(',', $ids);

            return $this->redirect($this->generateUrl('shoplo_allegro_wizard', array('product' => $ids)));
        }

		$action = !$action ? 'trwajace' : $action;

        $shoplo  = $this->container->get('shoplo');
		$shop   = $shoplo->get('shop');


		$limit = 25;
		$offset = ($page-1)*$limit;

		$now = date('Y-m-d H:i:s');
		$where = "WHERE i.user_id = " . $this->getUser()->getId();
		$where .= $action == 'zakonczone' ? " AND (i.end_at < '{$now}' OR i.quantity = i.quantity_sold)" : " AND i.end_at > '{$now}' AND i.quantity > i.quantity_sold";
		$total = $this->getDoctrine()
			->getManager()
			->createQuery('SELECT COUNT(i) FROM ShoploAllegroBundle:Item i '.$where)
			->getSingleScalarResult();
		if ( $total == 0 )
		{
			$total = $this->getDoctrine()
				->getManager()
				->createQuery("SELECT COUNT(i) FROM ShoploAllegroBundle:Item i WHERE i.user_id = " . $this->getUser()->getId())
				->getSingleScalarResult();
			if ( $total > 0 )
			{
				return $this->redirect($this->generateUrl('shoplo_allegro_homepage', array(
					'action' => $action == 'zakonczone' ? 'trwajace' : 'zakonczone',
					'page'	 => 1
				)));
			}
		}

		$items = $this->getDoctrine()
            ->getManager()
			->createQuery('SELECT i FROM ShoploAllegroBundle:Item i '.$where. ' ORDER BY i.id DESC')
			->setFirstResult($offset)
			->setMaxResults($limit)
			->getResult();

		$finishItems = $activeItems = array();
		if ( $action == 'zakonczone' )
		{
			$finishItems = $items;
		}
		else
		{
			$activeItems = $items;
		}

		$allegro = $this->get('allegro');
		$allegro->login($this->getUser());
		#TODO: odpytywac sie nie czesciej niz co 5min, uzyc cache'a
		$ids = array_keys($activeItems);
		$result = !empty($ids) ? $allegro->getItemsInfo($ids) : array();
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

		$url = $this->generateUrl('shoplo_allegro_homepage', array('action'=>$action, 'page'=>3), true);
		$currentPage = $page;
		$lastPage = floor($total/$limit);
		$pager = (object) array(
			'base_url'		=>	substr($url, 0, strrpos($url, '/')),
			'current_page'	=>	$currentPage,
			'first_page'	=>	$this->generateUrl('shoplo_allegro_homepage', array('action'=>$action, 'page'=>1), true),
			'last_page'		=>	$this->generateUrl('shoplo_allegro_homepage', array('action'=>$action, 'page'=>$lastPage), true),
			'total_page'	=>	$lastPage,
			'previous_page' =>	$currentPage > 1 ? $this->generateUrl('shoplo_allegro_homepage', array('action'=>$action, 'page'=>$currentPage-1), true) : null,
			'next_page'		=>	$offset+$limit < $total ? $this->generateUrl('shoplo_allegro_homepage', array('action'=>$action, 'page'=>$currentPage+1), true) : null,
			'pagination_need'=> $lastPage > 1 ? true : false
		);

        return $this->render('ShoploAllegroBundle::homepage.html.twig', array(
			'active_items' => $activeItems,
			'finish_items' => $finishItems,
			'shoplo' => $shoplo,
			'shop'	 => $shop,
			'pager'	 => $pager,
			'finish_url'	=> $this->generateUrl('shoplo_allegro_homepage', array('action'=>'zakonczone', 'page'=>1), true),
			'active_url'	=> $this->generateUrl('shoplo_allegro_homepage', array('action'=>'trwajace', 'page'=>1), true),
		));
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

	public function deleteAction($itemId)
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

        $sellAgainItem = $this->getDoctrine()
            ->getRepository('ShoploAllegroBundle:SellAgainItem')
            ->findOneBy(
                array('item_id' => $itemId, 'user_id' => $this->getUser()->getId())
            );
        if ( $sellAgainItem instanceof SellAgainItem )
        {
            $this->get('session')->setFlash(
                "error",
                "Aukcja jest ponownie wystawiana. Spróbuj ponownie później."
            );
            return $this->redirect( $this->generateUrl('shoplo_allegro_homepage') );
        }

		$em = $this->getDoctrine()->getManager();
		$em->remove($item);
		$em->flush();

		$this->get('session')->setFlash(
			"success",
			"Aukcja została usunięta."
		);

		return $this->redirect( $this->generateUrl('shoplo_allegro_homepage') );
	}

	public function finishAction($itemId, $force=false)
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
		if ( $result === true || $force == true )
		{
			$em = $this->getDoctrine()->getManager();
			$item->setEndAt( new \DateTime() );
			$em->flush();

			$this->get('session')->setFlash(
				"success",
				"Aukcja została zakończona."
			);
		}
		else
		{
			$link = $this->generateUrl('shoplo_allegro_finish_item_force', array('itemId'=>$itemId));
			$this->get('session')->setFlash(
				"error",
				"Komunikat od Allegro<br />".
					$result."<br />".
					"<a href='{$link}'>Zakończ aukcję w Shoplo</a>"

			);
		}

		return $this->redirect( $this->generateUrl('shoplo_allegro_homepage') );
	}

    public function sellagainAction()
    {
        $itemId = $this->getRequest()->request->get('item_id');
        $duration = $this->getRequest()->request->get('duration');
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
        if (!$allegro->login($this->getUser())) {
            throw new AccessDeniedException();
        }

        $localId = rand(1, 999999);
        try {
            $result = $allegro->doSellSomeAgain($allegro->getSession(), array($itemId), 0, $duration, null, array($localId));
//            $lastRequest = $allegro->__getLastRequest();
//            $lastResponse = $allegro->__getLastResponse();
//            $this->get('logger')->err('doSellSomeAgain request: '.$lastRequest);
//            $this->get('logger')->err('doSellSomeAgain response: '.$lastResponse);
        }
        catch ( \SoapFault $e ) {
            $this->getRequest()->getSession()->setFlash(
                "error",
                $e->getMessage()
            );
            return $this->redirect($this->generateUrl('shoplo_allegro_homepage'));
        }

        if( !empty($result['items-sell-not-found']) )
        {
            $this->get('logger')->err('doSellSomeAgain: items-sell-not-found, item id: '.$itemId.', result: '.print_r($result, true));
        }

        if( !empty($result['items-sell-failed']) )
        {
            $this->get('logger')->err('doSellSomeAgain: items-sell-failed, item id: '.$itemId.', result: '.print_r($result, true));
            $message = $result['items-sell-failed'][0]->{'sell-fault-string'};
            $this->getRequest()->getSession()->setFlash(
                "error",
                $message
            );
            return $this->redirect($this->generateUrl('shoplo_allegro_homepage'));
        }

        $em     = $this->get('doctrine')->getManager();

        $verifyResult = $allegro->doVerifyItem($allegro->getSession(), $localId);
        if($verifyResult['item-listed'] == 1)
        {
            $newItem = new Item();
            $endTime = strtotime('+' . $duration . ' days', $verifyResult['item-starting-time']);
            $newItem
                ->setId($verifyResult['item-id'])
                ->setUser($this->getUser())
                ->setVariantId($item->getVariantId())
                ->setProductId($item->getProductId())
                ->setPrice($item->getPrice())
                ->setQuantity($item->getQuantity())
                ->setQuantityAll($item->getQuantityAll())
                ->setQuantitySold(0)
                ->setViewsCount(0)
                ->setWatchCount(0)
                ->setAuctionPrice($item->getAuctionPrice())
                ->setStartAt(new \DateTime(date('Y-m-d H:i:s', $verifyResult['item-starting-time'])))
                ->setEndAt(new \DateTime(date('Y-m-d H:i:s', $endTime)));

            $em->persist($newItem);
            $message = "Gratulacje. Twoja Aukcja została utworzona:) Będzie ona widoczna w przeciągu kilku minut.";
            $this->getRequest()->getSession()->setFlash(
                "success",
                $message
            );
            return $this->redirect($this->generateUrl('shoplo_allegro_homepage'));
        }
        elseif($verifyResult['item-listed'] == -1)
        {
            $message = 'Wystąpił błąd i oferta nie została wystawiona. Spróbuj ponownie później.';
            $this->getRequest()->getSession()->setFlash(
                "error",
                $message
            );
            return $this->redirect($this->generateUrl('shoplo_allegro_homepage'));
        }
        else
        {
            $message = 'Aukcja czeka w kolejce do wystawienia.';
        }

        $sellAgainItem = new SellAgainItem();
        $sellAgainItem->setItemId($result['items-sell-again'][0]->{'sell-item-id'});
        $sellAgainItem->setLocalId($localId);
        $sellAgainItem->setDuration($duration);
        $sellAgainItem->setUserId( $this->getUser()->getId() );

        $em->persist($sellAgainItem);
        $em->flush();

        $this->getRequest()->getSession()->setFlash(
            "success",
            $message
        );
        return $this->redirect($this->generateUrl('shoplo_allegro_homepage'));
    }
}
