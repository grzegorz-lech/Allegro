<?php

namespace Shoplo\AllegroBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand as Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Shoplo\AllegroBundle\WebAPI\Shoplo;
use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken;
use Shoplo\AllegroBundle\Entity\User;
use Doctrine\ORM\EntityNotFoundException;
use Shoplo\AllegroBundle\WebAPI\Allegro;

class WebhookCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('allegro:webhook:create')
            ->setDescription('Stworzenie web hook\'a do Shoplo');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		/** @var $doctrine \Doctrine\Bundle\DoctrineBundle\Registry */
        $doctrine = $this->getContainer()->get('doctrine');

        $users = $doctrine->getRepository('ShoploAllegroBundle:User')->findAll();
        $user = array_pop($users);

		$url = $this->getContainer()->get('router')->generate('shoplo_allegro_webhook', array(), true);
		$data = array('section'=>'order/create', 'site_url'=>$url);
		$shoplo = $this->getShop($user);
		$webhook  = $shoplo->post('webhooks', array('webhook' => $data));
    }

    /**
     * @param  User                    $user
     * @throws EntityNotFoundException
     * @return Shoplo
     */
    private function getShop(User $user)
    {
        $token    = new OAuthToken(array(
            'oauth_token'        => $user->getOauthToken(),
            'oauth_token_secret' => $user->getOauthTokenSecret()
        ));
        $security = $this->getContainer()->get('security.context');
        $security->setToken($token);

        $key    = $this->getContainer()->getParameter('oauth_consumer_key');
        $secret = $this->getContainer()->getParameter('oauth_consumer_secret');

        return new Shoplo($key, $secret, $security);
    }
}
