<?php

namespace Shoplo\AllegroBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand as Command;
use Shoplo\AllegroBundle\WebAPI\Allegro;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Sensio\Bundle\GeneratorBundle\Command\Helper\DialogHelper;
use Doctrine\ORM\EntityManager;
use Shoplo\AllegroBundle\Entity\CategoryAllegro;
use Symfony\Component\Console\Input\InputArgument;
class TestMailCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('mail:test')
            ->setDescription('Test sending emails')
			->addArgument('email', InputArgument::OPTIONAL, 'mail to', 'lech.grzegorz@gmail.com');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$emailTo = $input->getArgument('email');
        $message = \Swift_Message::newInstance()
			->setSubject('Tst mail')
			->setFrom('sebastian@nexis.pl')
			->setTo($emailTo)
			->setBody('TEST MAIL');
		$this->getContainer()->get('mailer')->send($message);
    }
}
