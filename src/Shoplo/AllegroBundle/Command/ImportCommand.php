<?php

namespace Shoplo\AllegroBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand as Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Shoplo\AllegroBundle\WebAPI\Shoplo;
use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken;
use Shoplo\AllegroBundle\Entity\User;
use Doctrine\ORM\EntityNotFoundException;

class ImportCommand extends Command
{
    protected function configure()
    {
        $this->setName('allegro:import');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $shoplo = $this->getShop(629);
        $shop   = $shoplo->get('shop');

        $output->writeln($shop['domain']);
    }

    /**
     * @param int $shopId
     * @throws EntityNotFoundException
     * @return Shoplo
     */
    private function getShop($shopId)
    {
        $em         = $this->getContainer()->get('doctrine')->getManager();
        $repository = $em->getRepository('ShoploAllegroBundle:User');
        $user       = $repository->findOneBy(array('shopId' => $shopId));

        if (!$user instanceof User) {
            throw new EntityNotFoundException();
        }

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
