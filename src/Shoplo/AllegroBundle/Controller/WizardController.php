<?php

namespace Shoplo\AllegroBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Shoplo\AllegroBundle\Entity\Item;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Request;
use Shoplo\AllegroBundle\Form\Wizard;
use Shoplo\AllegroBundle\WebAPI\Allegro;

class WizardController extends Controller
{
    /**
     * @Secure(roles="ROLE_ADMIN")
     */
    public function indexAction(Request $request)
    {
        $ids = $request->query->get('product');
		$ids = !is_array($ids) ? explode(',',$ids) : $ids;
		if (!is_array($ids)) {
            throw $this->createNotFoundException('Product IDs missing');
        }

		# TODO: przeniesc to do config'ów category_id => price za wyroznienie
		/*$highlightCategoryPriceMap = array(
			1	=>	12, // Antyki i Sztuka
			2	=>	12, // Delikatesy,
			3	=>	12, // Filmy,
			4	=>	12, // Gry,
			5	=>	12, // Kolekcje,
			6	=>	12, // Książki i Komiksy,
			7	=>	12, // Muzyka,
			8	=>	12, // Instrumenty,
			9	=>	12, // Rękodzieło
		);*/
		# TODO: przeniesc to do config'ów category_id => price za strona kategorii
		/*$pageCategoryPriceMap = array(
			1	=>	12, // Filmy,
			2	=>	12, // Gry,
			3	=>	12, // Kolekcje,
			4	=>	12, // Książki i Komputery,
			5	=>	12, // Muzyka,
			6	=>	12, // Instrumenty
			7	=>	15, // Muzyka,
			8	=>	15, // Antyki i Sztuka,
			9	=>	15, // Biżuteria i Zegarki,
			10	=>	15, // Delikatesy,
			11	=>	15, // Fotografia,
			12	=>	15, // Rękodzieło,
			13	=>	15, // Sport i Turystyka,
			14	=>	15, // Telefony i Akcesoria
			15	=>	18, // Dla Dzieci,
			16	=>	18, // Dom i Ogród,
			17	=>	18, // Komputery,
			18	=>	18, // RTV i ADG,
			19	=>	18, // Zdrowie,
			20	=>	18, // Uroda,
			21	=>	22, // Biuro i Reklama,
			22	=>	22, // Odzież, Obuwie,
			23	=>	22, // Dodatki
		);*/

        // Informacje o produktach
        $shoplo   = $this->container->get('shoplo');
        $products = array();
		$auctionPrice = 0;
        foreach ($ids as $id) {
            $product    = $shoplo->get('products', $id);
			# TODO: zmapować kategorie z Shoplo na kategorie z Allegro
			$product['categories'] = array(
				array(
					'id'	=>	1,
					'title'	=>	'Elektronika',
					'price'	=>	0.3, // cena za wystawienie w danej kategorii
				),
				array(
					'id'	=>	2,
					'title'	=>	'Odzież, Obuwie, Dodatki',
					'price'	=>	0.5,
				),
			);

			$product['auction_price'] = $product['categories'][0]['price'];
			$products[] = $product;

			$auctionPrice += $product['categories'][0]['price'];
        }

        $wizard = new Wizard();
        $form   = $this->createFormBuilder($wizard)
            ->add(
            'layout',
            'choice',
            array(
                'choices'  => array(1, 2, 3),
                'expanded' => true,
            )
        )
            ->add('description', 'textarea')
            ->getForm();

        if ($request->isMethod('POST')) {
            $form->bind($request);

            if ($form->isValid()) {
                $wizard = $form->getData();
                $em     = $this->get('doctrine')->getManager();

                foreach ($products as $k => $product) {
                    foreach ($product['variants'] as $variant) {
                        $fields = $wizard->export($product, $variant);
                        $itemId = $this->createAuction($fields);

                        $item = new Item();
                        $item
                            ->setId($itemId)
                            ->setVariantId($variant['id'])
                            ->setQuantity($variant['quantity'])
                            ->setStartAt(new \DateTime('now'))
                            ->setEndAt(new \DateTime('+3 days'));

                        $em->persist($item);
                    }
                }

                $em->flush();

                return $this->redirect($this->generateUrl('shoplo_allegro_homepage'));
            }
        }

        return $this->render(
            'ShoploAllegroBundle::wizard.html.twig',
            array(
                'form'     => $form->createView(),
                'ids'      => $ids,
                'products' => $products,
				'products_count'	=>	count($products),
				'init_price'		=>	sprintf("%0.2f", $auctionPrice),
            )
        );
    }

    private function createAuction(array $fields)
    {
        /** @var $allegro Allegro */
        $allegro = $this->get('allegro');
        if (!$allegro->login($this->getUser())) {
            throw new AccessDeniedException();
        }

        $item = $allegro->doNewAuctionExt($allegro->getSession(), $fields);

        return $item['item-id'];
    }
}
