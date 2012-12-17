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
        $this->setName('allegro:import');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $shops = $this->getActiveShops();
        foreach ($shops as $shop) {
            $auctionsIds = $newTransactionAuctionMap = array();
            $events = $shop['allegro']->getNewEvents($shop['last_event_id']);
            foreach ($events as $event) {
                $event = (array) $event;
                $auctionsIds[] = $event['deal-item-id'];
                continue;
                if ( $event['deal-event-type'] == 1 )
                    continue;

                switch ($event['deal-event-type']) {
                    case 2: # utworzenie formularza pozakupowego
                        // TODO: create order in Shoplo
                        $newTransactionAuctionMap[$event['deal-transaction-id']] = $event['deal-item-id'];
                        break;
                    case 3: # anulowanie formularza pozakupowego
                        // TODO: cancel order in Shoplo
                        break;
                    case 4: # oplacenie transakcji
                        // TODO: mark order as paid
                        break;
                }

            }

            $postBuyData = $shop['allegro']->getPostBuyData( array_unique($auctionsIds) );
            if ( !empty($newTransactionAuctionMap) ) {
                $buyersFormsData = $shop['allegro']->getBuyersData( array_keys($newTransactionAuctionMap) );
                $postBuyData 	 = $shop['allegro']->getPostBuyData( array_unique(array_values($newTransactionAuctionMap)) );
                foreach ($buyersFormsData as $data) {
                    // TODO: retrieve auctions to get variant_id by auction_id
                    $auction = (object) array(
                        'id'			=>	1,
                        'variant_id'	=>	11,
                        'quantity'		=>	100,
                        'quantity_sold'	=>	20,
                        'start_at'		=>	'2012-12-22 20:23:43',
                        'end_at'		=>	'2012-12-29 20:23:43',
                    );

                    $auctionId 	= $newTransactionAuctionMap[$data['post-buy-form-id']];
                    $buyerId 	= $data['post-buy-form-buyer-id'];
                    $buyer 		= array();
                    foreach ($postBuyData as $d) {
                        if ($d['item_id'] == $auctionId) {
                            $buyer = $d['buyers'][$buyerId];
                            break;
                        }
                    }

                    $order = $this->createShoploOrder($auction, $data, $shop, $buyer);
                    // TODO: create order in DB

                    // TODO: update in auction quantity_sold
                }
            }

            // TODO: zapis do bazy last_event_id
            $lastEventId = $events[count($events)-1]->{'deal-event-id'};
        }
    }

    /**
     * Pobranie aktywnych sklepow, ktore maja wystawiona choc jedna aukcje
     *
     * @return array
     */
    private function getActiveShops()
    {
        //TODO: pobrać z bazy aktywne sklepy. Przy sklepie powinna byc kolumna last_event_id okreslająca ostatnie pobrane id zdarzenia
        $shopIds = array(629);
        $shops 	 = array();
        foreach ($shopIds as $shopId) {
            $shoplo = $this->getShop($shopId);
            $shop 	= $shoplo->get('shop');

            //TODO: przy sklepie przetrzymywać dane do sesji (session_id, session_expired)
            // tymczasowo dopoki dane nie beda przy obiekcie shop
            $user = $this->getUser();
            $allegro = $this->getContainer()->get('allegro');
            $allegro->login($user);

            $shop['last_event_id'] = 0;
            $shop['allegro'] = $allegro;
            $shop['shoplo']  = $shoplo;
            $shops[] = $shop;
        }

        return $shops;
    }

    /**
     * @param  int                     $shopId
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

    /**
     * @return User
     */
    private function getUser()
    {
        $user = new User();
        $user->setUsername('shoplo');
        $user->setPassword('Y9dwhjd34foBN8dM');
        $user->setCountry(228);

        return $user;
    }

    /**
     * Tworzy zamowienie w Shoplo
     *
     * @param $auction
     * @param $auctionData
     * @param $shop
     * @param $buyer
     * return array
     */
    public function createShoploOrder($auction, $auctionData, $shop, $buyer)
    {
        list($shippingFirstName, $shippingLastName) = explode(' ', $auctionData['post-buy-form-shipment-address']['post-buy-form-adr-full-name'], 2);
        $paymentMethods = Allegro::getPaymentMethods();
        $shippingMethods = $shop['allegro']->getShippingMethods();

        $order = array(
            'shipping_details'		=>	array(
                'title'					=>	isset($shippingMethods[$auctionData['post-buy-form-shipment-id']]) ? $shippingMethods[$auctionData['post-buy-form-shipment-id']] : 'Nie określony',
                'price'					=>	bcmul($auctionData['post-buy-form-postage-amount'], 100),
            ),
            'payment_details'		=>	array(
                'title'					=>	isset($paymentMethods[$auctionData['post-buy-form-pay-type']]) ? $paymentMethods[$auctionData['post-buy-form-pay-type']] : 'Nie określony',
            ),
            'customer' => array(
                'first_name'		=>	$buyer['user-data']['user-first-name'],
                'last_name'			=>	$buyer['user-data']['user-last-name'],
                'email'				=>	$buyer['user-data']['user-email'],
                'phone'				=>	$buyer['user-data']['user-phone'],
                'accept_newsletter'	=>	'0',
                'address'			=>	array(
                    'street'			=>	$auctionData['post-buy-form-shipment-address']['post-buy-form-adr-street'],
                    'city'				=>	$auctionData['post-buy-form-shipment-address']['post-buy-form-adr-city'],
                    'zip_code'			=>	$auctionData['post-buy-form-shipment-address']['post-buy-form-adr-postcode'],
                    'country_code'		=>	$auctionData['post-buy-form-shipment-address']['post-buy-form-adr-country'],
                ),
            ),
            'shipping_address'		=>	array(
                'first_name'		=>	$shippingFirstName,
                'last_name'			=>	$shippingLastName,
                'street'			=>	$auctionData['post-buy-form-shipment-address']['post-buy-form-adr-street'],
                'phone'				=>	$auctionData['post-buy-form-shipment-address']['post-buy-form-adr-phone'],
                'city'				=>	$auctionData['post-buy-form-shipment-address']['post-buy-form-adr-city'],
                'zip_code'			=>	$auctionData['post-buy-form-shipment-address']['post-buy-form-adr-postcode'],
                'country_code'		=>	$auctionData['post-buy-form-shipment-address']['post-buy-form-adr-country'],
            ),
            'order_items'			=>	array(
                array(
                    'variant_id'		=>	$auction->variant_id,
                    'quantity'			=>	$auctionData['post-buy-form-items']['post-buy-form-it-quantity'],
                    'price'				=>	$auctionData['post-buy-form-items']['post-buy-form-it-price'],
                ),
            ),
            'referring_site'	=>	'http://allegro.pl/i'.$auction->id.'.html',
            'landing_site'		=>	'/',
            'notes'				=>	$auctionData['post-buy-form-msg-to-seller'],
        );

        if ($auctionData['post-buy-form-invoice-option']) {
            list($firstName, $lastName) = explode(' ', $auctionData['post-buy-form-invoice-data']['post-buy-form-adr-full-name'], 2);
            $order['billing_address'] = array(
                'company'			=>	$auctionData['post-buy-form-invoice-data']['post-buy-form-adr-company'],
                'first_name'		=>	$firstName,
                'last_name'			=>	$lastName,
                'street'			=>	$auctionData['post-buy-form-invoice-data']['post-buy-form-adr-street'],
                'city'				=>	$auctionData['post-buy-form-invoice-data']['post-buy-form-adr-city'],
                'zip_code'			=>	$auctionData['post-buy-form-invoice-data']['post-buy-form-adr-postcode'],
                'country_code'		=>	$auctionData['post-buy-form-invoice-data']['post-buy-form-adr-country'],
                'tax_id'			=>	$auctionData['post-buy-form-invoice-data']['post-buy-form-adr-nip'],
            );
        }

        return $shop['shoplo']->post('orders', array('order'=>$order));
    }
}
