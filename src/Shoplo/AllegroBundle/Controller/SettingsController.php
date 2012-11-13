<?php

namespace Shoplo\AllegroBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Shoplo\AllegroBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;

class SettingsController extends Controller
{
    public function loginAction(Request $request)
    {
        $user = new User();
        $user->setCountry(1); // 1 = Polska

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
                    return $this->render('ShoploAllegroBundle::settings.html.twig', array(
                        'step' => 2,
                    ));
                }
            }
        }

        return $this->render('ShoploAllegroBundle::settings.html.twig', array(
            'form' => $form->createView(),
            'step' => 1,
        ));
    }
}
