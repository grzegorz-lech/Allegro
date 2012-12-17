<?php

namespace Shoplo\AllegroBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Shoplo\AllegroBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;

class SettingsController extends Controller
{
    /**
     * @Secure(roles="ROLE_USER")
     */
    public function loginAction(Request $request)
    {
        $security = $this->get('security.context');

        if ($security->isGranted('ROLE_ADMIN')) {
            return $this->redirect($this->generateUrl('shoplo_allegro_settings_profile'));
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
                'step' => 1,
            )
        );
    }

    /**
     * @Secure(roles="ROLE_ADMIN")
     */
    public function profileAction()
    {
        return $this->redirect($this->generateUrl('shoplo_allegro_homepage'));
    }
}
