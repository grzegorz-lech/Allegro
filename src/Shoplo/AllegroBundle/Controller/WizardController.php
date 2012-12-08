<?php

namespace Shoplo\AllegroBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Request;
use Shoplo\AllegroBundle\Form\Wizard;

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
            ->add('layout', 'choice', array(
                'choices'  => array(1, 2, 3),
                'expanded' => true,
            ))
            ->add('description', 'textarea')
            ->getForm();

        if ($request->isMethod('POST')) {
            $form->bind($request);

            if ($form->isValid()) {
                var_dump($ids, $form->getData());
                exit;
            }
        }

        return $this->render('ShoploAllegroBundle::wizard.html.twig', array(
            'form'     => $form->createView(),
            'ids'      => $ids,
            'products' => $products,
        ));
    }
}
