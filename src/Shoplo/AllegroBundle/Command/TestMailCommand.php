<?php

namespace Shoplo\AllegroBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand as Command;
use Shoplo\AllegroBundle\WebAPI\Allegro;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Sensio\Bundle\GeneratorBundle\Command\Helper\DialogHelper;
use Doctrine\ORM\EntityManager;
use Shoplo\AllegroBundle\Entity\CategoryAllegro;

class TestMailCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('mail:test')
            ->setDescription('Test sending emails');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $message = \Swift_Message::newInstance()
			->setSubject('Tst mail')
			->setFrom('sebastian@nexis.pl')
			->setTo('lech.grzegorz@gmail.com')
			->setBody('TEST MAIL');
		$this->get('mailer')->send($message);
    }
}
