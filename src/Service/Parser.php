<?php

namespace App\Service;

use Clue\React\Buzz\Browser;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DomCrawler\Crawler;

class Parser
{
    /**
     * @var Browser
     */
    private $client;

    /**
     * @var array
     */
    private $parsed = [];

    public function __construct(Browser $client)
    {
        $this->client = $client;
    }

    public function parse($url)
    {
            $this->client->get($url)->then(
                function (ResponseInterface $response) use ($url) {
                    $this->parsed = $this->extractFromHtml((string) $response->getBody(), $url);
                });
    }

    public function extractFromHtml($html, $url)
    {
        $crawler = new Crawler($html);

        $sites =
            [
                'rozetka.com.ua',
                'foxtrot.com.ua',
                'avtoduma.ua',
            ];

        foreach ($sites as $site) {
            if(strpos($url, $site) !== false){
                switch ($site):
                    case 'rozetka.com.ua':
                            $title = trim($crawler->filter('h1')->text());
                            $picture = $crawler->filter('.product-photo__large-inner img')->eq(0)->attr('src');
                            $price = (int) filter_var($crawler->filter('.product-prices__big')->text(), FILTER_SANITIZE_NUMBER_INT);
                            $currency = $crawler->filter('.product-prices__symbol')->text();
                        break;
                    case 'foxtrot.com.ua':
                            $title = $crawler->filter('meta[itemprop="name"]')->first()->attr('content');
                            $picture = $crawler->filter('meta[itemprop="image"]')->first()->attr('content');
                            $price = (int) filter_var($crawler->filter('.price__relevant')->children('.numb')->text(), FILTER_SANITIZE_NUMBER_INT);
                            $currency = $crawler->filter('.price__relevant')->children('.currency')->text();
                        break;
                    case 'avtoduma.ua':
                            $title = $crawler->filter('.name')->first()->text() . " " . $crawler->filter('.panel-title-text')->first()->text();
                            $picture = 'none';
                            $price = (int) filter_var($crawler->filter('.vprice.p_UAH')->text(), FILTER_SANITIZE_NUMBER_INT);
                            $currency = 'грн';

                        break;
                endswitch;
            }
        }

        return [
            'title'     => $title,
            'price'     => $price,
            'picture'   => $picture,
            'currency'  => $currency,
        ];
    }

    public function getData()
    {
        return $this->parsed;
    }
}