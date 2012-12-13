<?php
/**
 * Created by JetBrains PhpStorm.
 * User: grzegorzlech
 * Date: 12-12-10
 * Time: 09:07
 * To change this template use File | Settings | File Templates.
 */

namespace Shoplo\AllegroBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Shoplo\AllegroBundle\WebAPI\Shoplo;

class ImportCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('allegro:import')
			->setDescription('Import auctions from Allegro')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$auctions = array(
			12 => array(
				'id'		=> 	2860421339,
				'shop_id'	=>	1,
			),
		);

		foreach ( $auctions as $auction )
		{



			#TODO: pobranie aukcji z allegro

			#TODO: zapis do Shoplo

			$shoplo = Shoplo::getByShopId($auction['shop_id']);


			$data = array();
			$result = $shoplo->post('orders', $data);
		}

		$output->writeln('Hello World');
	}
}