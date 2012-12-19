<?php

namespace Shoplo\AllegroBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand as Command;
use Shoplo\AllegroBundle\WebAPI\Allegro;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
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
        209 => 'Ukraina (rosyjski)',
        228 => 'Test WebAPI',
        232 => 'Ukraina (ukraiński)',
    );

    protected function configure()
    {
        $this
            ->setName('allegro:category:import')
            ->setDescription('Import kategorii z Allegro');
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
                if (!in_array($value, array_keys($this->countries))) {
                    throw new \InvalidArgumentException('Invalid country code: ' . $value);
                }

                return $value;
            }
        );

        /** @var $allegro Allegro */
        $allegro = $this->getContainer()->get('allegro');
        $allegro->setCountry($countryId);
        $count = $allegro->doGetCatsDataCount($allegro->getCountry(), 0, $allegro->getKey());
        $count = $count['cats-count'];
        $output->writeln('Found <info>' . $count . '</info> categories.');

        /** @var $em EntityManager */
        $em    = $this->getContainer()->get('doctrine')->getManager();
        $query = $em->createQuery(
            'DELETE FROM ShoploAllegroBundle:CategoryAllegro m WHERE m.country_id = ' . $countryId
        );
        $query->execute();

        $categories = $allegro->doGetCatsData($allegro->getCountry(), 0, $allegro->getKey());
        $tree       = array();

        foreach ($categories['cats-list'] as $c) {
            $category = new CategoryAllegro();
            $category
                ->setId($c->{'cat-id'})
                ->setName($c->{'cat-name'})
                ->setPosition($c->{'cat-position'})
                ->setCountryId($countryId);

            $em->persist($category);

            $tree[$c->{'cat-id'}] = $category;
        }

        foreach ($categories['cats-list'] as $c) {
            $category = $tree[$c->{'cat-id'}];

            if ($c->{'cat-parent'} > 0) {
                $parent = $tree[$c->{'cat-parent'}];
                $category->setParent($parent);
            }
        }

        $em->flush();
    }
}
