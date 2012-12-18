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
        if (!is_array($ids)) {
            throw $this->createNotFoundException('Product IDs missing');
        }

        // Informacje o produktach
        $shoplo   = $this->container->get('shoplo');
        $products = array();
        foreach ($ids as $id) {
            $product    = $shoplo->get('products', $id);
            $products[] = $product;
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

                foreach ($products as $product) {
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
