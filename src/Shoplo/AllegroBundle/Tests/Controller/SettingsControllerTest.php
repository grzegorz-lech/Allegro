<?php

namespace Shoplo\AllegroBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SettingsControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/ustawienia');

        $this->assertTrue($crawler->filter('html:contains("Witaj w aplikacji Integracja z Allegro")')->count() > 0);
    }
}
