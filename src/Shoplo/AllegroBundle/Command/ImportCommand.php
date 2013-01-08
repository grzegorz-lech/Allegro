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

class ImportCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('allegro:import')
            ->setDescription('Importowanie zamówień z Allegro do Shoplo');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $doctrine \Doctrine\Bundle\DoctrineBundle\Registry */
        $doctrine = $this->getContainer()->get('doctrine');

        /** @var $repository \Shoplo\AllegroBundle\Entity\UserRepository */
        $repository = $doctrine->getRepository('ShoploAllegroBundle:User');

        /** @var $users User[] */
        $users = $repository->findAll();

        /** @var $allegro Allegro */
        $allegro = $this->getContainer()->get('allegro');

        foreach ($users as $user) {
            if (!$allegro->login($user)) {
                $output->writeln('<error>Unable to log-in to Allegro</error>');
                continue;
            }

            $auctionsIds = $newTransactionAuctionMap = array();
            $deals       = $allegro->getDeals(0);//$user->getLastEventId());

            if (empty($deals)) {
                $output->writeln('<comment>No deals found</comment>');
                continue;
            }

            $output->writeln('<info>Found ' . count($deals) . ' deals for user: ' . $user->getUsername() . '['.$user->getShopId().']</info>');

            foreach ($deals as $deal) {
                $auctionsIds[] = $deal->getItemId();

                /**
                 * Każde z typów zdarzeń (oprócz 1, dla którego ID transakcji nie jest jeszcze znane),
                 * generowane jest osobno dla każdego deala wchodzącego w skład transakcji.
                 */
                switch ($deal->getEventType()) {
                    case 1: # utworzenie aktu zakupowego (deala)
						$output->writeln('<info>Utworzenie aktu zakupowego. ItemId: '.$deal->getItemId().'</info>');
                        continue;
                    case 2: # utworzenie formularza pozakupowego (transakcji)
						$output->writeln('<info>Utworzenie formularza pozakupowego. ItemId: '.$deal->getItemId().'</info>');
                        $newTransactionAuctionMap[$deal->getTransactionId()] = $deal->getItemId();
                        break;
                    case 3: # anulowanie formularza pozakupowego (transakcji)
						$output->writeln('<info>Anulowanie formularza pozakupowego. ItemId: '.$deal->getItemId().'</info>');
                        // TODO: cancel order in Shoplo
                        break;
                    case 4: # zakończenie (opłacenie) transakcji przez PzA)
						$output->writeln('<info>Zakonczenie (oplacenie) transakcji. ItemId: '.$deal->getItemId().'</info>');
                        // TODO: mark order as paid
                        break;
                }
            }

            $postBuyData = $allegro->getPostBuyData(array_unique($auctionsIds));

            if (!empty($newTransactionAuctionMap))
			{
                $buyersFormsData = $allegro->getBuyersData(array_keys($newTransactionAuctionMap));
				$output->writeln('<comment>Buyers data: '.print_r($buyersFormsData, true).'</comment>');
                foreach ($buyersFormsData as $data)
				{
					$data = (array) $data;
					$auctionId = $newTransactionAuctionMap[$data['post-buy-form-id']];

                    $item = $doctrine->getRepository('ShoploAllegroBundle:Item')->findOneById($auctionId);

                    $buyerId   = $data['post-buy-form-buyer-id'];
                    $buyer     = array();
                    foreach ($postBuyData as $d) {
                        if ($d['item_id'] == $auctionId) {
                            $buyer = $d['buyers'][$buyerId];
                            break;
                        }
                    }

                    $shoplo = $this->getShop($user);
                    $order  = $this->createShoploOrder($item, $data, $user, $buyer, $allegro, $shoplo, $output);

					$output->writeln('<info>Result: '.print_r($order, true).'</info>');
                    // TODO: create order in DB

                    $item->setQuantitySold($item->getQuantitySold()+$data['post-buy-form-items']['post-buy-form-it-quantity']);
                }
            }

            // Zapamiętanie ostatniego zdarzenia
            $lastDeal = array_pop($deals);
            $user->setLastEventId($lastDeal->getEventId());
            $doctrine->getManager()->flush();
        }
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

    /**
     * Tworzy zamowienie w Shoplo
     *
     * @param $auction
     * @param $auctionData
     * @param $user
     * @param $buyer
     * @param  Allegro $allegro
     * @param  Shoplo  $shoplo
     * @return array
     */
    public function createShoploOrder($item, $auctionData, $user, $buyer, Allegro $allegro, Shoplo $shoplo, OutputInterface $output)
    {
		$shippingAddress = (array) $auctionData['post-buy-form-shipment-address'];
        list($shippingFirstName, $shippingLastName) = explode(
            ' ',
			$shippingAddress['post-buy-form-adr-full-name'],
            2
        );
        $paymentMethods  = Allegro::getPaymentMethods();
        $shippingMethods = $allegro->getShippingMethods();

		$order = array(
            'shipping_details' => array(
                'title' => isset($shippingMethods[$auctionData['post-buy-form-shipment-id']]) ? $shippingMethods[$auctionData['post-buy-form-shipment-id']] : 'Nie określony',
                'price' => bcmul($auctionData['post-buy-form-postage-amount'], 100),
            ),
            'payment_details'  => array(
                'title' => isset($paymentMethods[$auctionData['post-buy-form-pay-type']]) ? $paymentMethods[$auctionData['post-buy-form-pay-type']] : 'Nie określony',
            ),
            'customer'         => array(
                'first_name'        => $buyer['user-data']['user-first-name'],
                'last_name'         => $buyer['user-data']['user-last-name'],
                'email'             => $buyer['user-data']['user-email'],
                'phone'             => $buyer['user-data']['user-phone'],
                'accept_newsletter' => '0',
                'address'           => array(
                    'street'       => $shippingAddress['post-buy-form-adr-street'],
                    'city'         => $shippingAddress['post-buy-form-adr-city'],
                    'zip_code'     => $shippingAddress['post-buy-form-adr-postcode'],
                    'country_code' => $shippingAddress['post-buy-form-adr-country'],
                ),
            ),
            'shipping_address' => array(
                'first_name'   => $shippingFirstName,
                'last_name'    => $shippingLastName,
                'street'       => $shippingAddress['post-buy-form-adr-street'],
                'phone'        => $shippingAddress['post-buy-form-adr-phone'],
                'city'         => $shippingAddress['post-buy-form-adr-city'],
                'zip_code'     => $shippingAddress['post-buy-form-adr-postcode'],
                'country_code' => $shippingAddress['post-buy-form-adr-country'],
            ),
            /*'order_items'      => array(
                array(
                    'variant_id' => $item->getVariantId(),
                    'quantity'   => $auctionData['post-buy-form-items']['post-buy-form-it-quantity'],
                    'price'      => $auctionData['post-buy-form-items']['post-buy-form-it-price'],
                ),
            ),*/
            'referring_site'   => 'http://allegro.pl/i' . $item->getId() . '.html',
            'landing_site'     => '/',
            'notes'            => $auctionData['post-buy-form-msg-to-seller'],
        );

		$items = (array) $auctionData['post-buy-form-items'];
		foreach ( $items as $it )
		{
			$it = (array) $it;
			$order['order_items'][] = array(
				'variant_id' => $item->getVariantId(),
				'quantity'   => $it['post-buy-form-it-quantity'],
				'price'      => $it['post-buy-form-it-price'],
			);
		}

        if ($auctionData['post-buy-form-invoice-option']) {
			$invoiceData = (array) $auctionData['post-buy-form-invoice-data'];
            list($firstName, $lastName) = explode(
                ' ',
				$invoiceData['post-buy-form-adr-full-name'],
                2
            );
            $order['billing_address'] = array(
                'company'      => $invoiceData['post-buy-form-adr-company'],
                'first_name'   => $firstName,
                'last_name'    => $lastName,
                'street'       => $invoiceData['post-buy-form-adr-street'],
                'city'         => $invoiceData['post-buy-form-adr-city'],
                'zip_code'     => $invoiceData['post-buy-form-adr-postcode'],
                'country_code' => $invoiceData['post-buy-form-adr-country'],
                'tax_id'       => $invoiceData['post-buy-form-adr-nip'],
            );
        }

		$output->writeln('<info>Order: '.print_r($order, true).'</info>');

        return $shoplo->post('orders', array('order' => $order));
    }
}
