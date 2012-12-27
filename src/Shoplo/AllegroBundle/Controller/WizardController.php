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
        $ids = $request->query->get('product', array());
        $ids = !is_array($ids) ? explode(',', $ids) : $ids;
        if (!is_array($ids)) {
            throw $this->createNotFoundException('Product IDs missing');
        }

        // Informacje o produktach
        $shoplo   = $this->get('shoplo');
        $variants = $products = array();

        foreach ($ids as $id) {
            $product = $shoplo->get('products', $id);

            // Kategorie
            $categoryIDs = array();

            foreach ($product['categories'] as $category) {
                $categoryIDs[] = $category['id'];
            }

            $categories = $this->getDoctrine()
                ->getRepository('ShoploAllegroBundle:Category')
                ->findBy(array('shop_id' => $this->getUser()->getShopId(), 'shoplo_id' => $categoryIDs));

            if (count($categoryIDs) !== count($categories)) {
                return $this->redirect($this->generateUrl('shoplo_allegro_settings_mapping'));
            }

            foreach ($product['variants'] as $variant) {
                $variant['categories']    = $categories;
                $variant['thumbnail']     = $product['thumbnail'];
                $variants[$variant['id']] = $variant;
            }

            $products[] = $product;
        }

        $wizard = new Wizard();
        $form   = $this->createFormBuilder($wizard)
            ->add('title', 'text') // TODO: Ustawienie maksymalnej długości LIMIT_ALLEGRO-MAX(nazwa_wariantu)
            ->add('description', 'textarea')
            ->getForm();

        if ($request->isMethod('POST')) {
            $form->bind($request);

            if ($form->isValid()) {
                $wizard = $form->getData();
                $em     = $this->get('doctrine')->getManager();

                // TODO: Profil z listy
                $profile = $this->getDoctrine()
                    ->getRepository('ShoploAllegroBundle:Profile')
                    ->findOneBy(array('user_id' => $this->getUser()->getId()));

                foreach ($products as $product) {
                    foreach ($product['variants'] as $variant) {
                        $categoryId = $_POST['category'][$variant['id']];
                        $fields     = $wizard->export($profile, $product, $variant, $categoryId);
                        $itemId     = $this->createAuction($fields);

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
                'variants' => $variants,
                'products' => $products,
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
