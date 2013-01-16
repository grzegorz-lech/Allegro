<?php

namespace Shoplo\AllegroBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand as Command;
use Shoplo\AllegroBundle\WebAPI\Allegro;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Sensio\Bundle\GeneratorBundle\Command\Helper\DialogHelper;
use Doctrine\ORM\EntityManager;
use Shoplo\AllegroBundle\Entity\CategoryAllegro;

class CategoryCommand extends Command
{
    protected $countries = array(
        1   => 'Polska',
        22  => 'Białoruś',
        34  => 'Bułgaria',
        56  => 'Czechy',
        107 => 'Kazachstan',
        168 => 'Rosja',
        181 => 'Słowacja',
        209 => 'Ukraina', // = 232
        228 => 'Test WebAPI',
    );

    protected function configure()
    {
        $this
            ->setName('allegro:category:tree')
            ->setDescription('Utworzenie drzewa kategorii z Allegro');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Country list
        $output->writeln('Found <info>' . count($this->countries) . '</info> countries:');
        foreach ($this->countries as $key => $value) {
            $output->writeln(sprintf('<info>%3d</info> %s', $key, $value));
        }

        /** @var $dialog DialogHelper */
        $dialog    = $this->getHelperSet()->get('dialog');
        $countryId = $dialog->askAndValidate(
            $output,
            '<question>Podaj kod kraju:</question> ',
            function ($value) {
                if (!in_array($value, array(1, 22, 34, 56, 107, 168, 181, 209, 228))) {
                    throw new \InvalidArgumentException('Invalid country code: ' . $value);
                }

                return $value;
            }
        );

        /** @var $allegro Allegro */
        $allegro = $this->getContainer()->get('allegro');
        $allegro->setCountry($countryId);

		$categories = $allegro->doGetCatsData($allegro->getCountry(), 0, $allegro->getKey());
		$tree       = array();

		foreach ($categories['cats-list'] as $c) {
			$tree[$c->{'cat-id'}] = $c->{'cat-parent'};
		}


		$em    = $this->getContainer()->get('doctrine')->getManager();
		$treeWithLeafs = array();
		foreach ( $tree as $child => $parent )
		{
			if ( $parent == 0 )
			{
				$treeWithLeafs[$child] = $child.'-'.$parent;
			}
			else
			{
				$treeWithLeafs[$child] = $child.'-'.$this->completeTree($parent, $tree);
			}
			$tmp = explode('-', $treeWithLeafs[$child]);
			$tmp = array_reverse($tmp);
			$treeWithLeafs[$child] = implode('-', $tmp);

			$category = $this->getContainer()->getDoctrine()->getRepository('ShoploAllegroBundle:CategoryAllegro')->findById($child);
			$category->setTree($treeWithLeafs[$child]);
		}
		$em->flush();
    }

	protected function completeTree($parent, $tree)
	{
		$list = '';
		foreach ( $tree as $child => $grandParent )
		{
			if ( $child == $parent )
			{
				if ( $grandParent == 0 )
				{
					$list .= $child.'-'.$grandParent;
				}
				else
				{
					$list .= $child.'-'.$this->completeTree($grandParent, $tree);
				}
			}
		}
		return $list;
	}
}
