<?php

namespace Shoplo\AllegroBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Shoplo\AllegroBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;

class SettingsController extends Controller
{
    public function loginAction(Request $request)
    {
        $user     = new User();
        $security = $this->get('security.context');
        $token    = $security->getToken();
        $user->setCountry(1); // 1 = Polska
        $user->setShopId($token->getUsername());

        if ($security->isGranted('ROLE_ADMIN')) {
            return $this->redirect($this->generateUrl('shoplo_allegro_settings_profile'));
        }

        $form = $this->createFormBuilder($user)
            ->add('username', 'text')
            ->add('password', 'password')
            ->getForm();

        if ($request->isMethod('POST')) {
            $form->bind($request);

            if ($form->isValid()) {
                /** @var $allegro \Shoplo\AllegroBundle\WebAPI\Allegro */
                $allegro = $this->container->get('allegro');
                if ($allegro->login($form->getData())) {
                    $user = $form->getData();

                    // Save in DB
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($user);
                    $em->flush();

                    // Add role
                    $user->addRole('ROLE_ADMIN', $security->getToken(), $request->getSession());

                    return $this->redirect($this->generateUrl('shoplo_allegro_settings_profile'));
                }
            }
        }

        return $this->render('ShoploAllegroBundle::settings.html.twig', array(
            'form' => $form->createView(),
            'step' => 1,
        ));
    }

    /**
     * @Secure(roles="ROLE_ADMIN")
     */
    public function profileAction()
    {
        return $this->redirect($this->generateUrl('shoplo_allegro_homepage'));
    }
}
