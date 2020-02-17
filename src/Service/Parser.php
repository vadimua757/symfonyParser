<?php

namespace App\Service;

use Clue\React\Buzz\Browser;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DomCrawler\Crawler;
use function RingCentral\Psr7\uri_for;

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
                    if ((string) $response->getBody() != ""){
                        $this->parsed = $this->extractFromHtml((string) $response->getBody(), $url);
                    } elseif (file_get_contents($url) != "") {
                        $this->parsed = $this->extractFromHtml((string) file_get_contents($url), $url);
                    } else {
                        // create curl resource
                        $ch = curl_init();
                        // set url
                        curl_setopt($ch, CURLOPT_URL, $url);
                        //return the transfer as a string
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
                        curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
                        // $output contains the output string
                        $output = curl_exec($ch);
                        // close curl resource to free up system resources
                        curl_close($ch);
                        dd($output);
                        $this->parsed = $this->extractFromHtml((string) $output, $url);
                    }
                }
        );
    }

    public function extractFromHtml($html, $url)
    {

        $status = '';
        $title = '';
        $picture = '';
        $price = '';
        $currency = '';

        $crawler = new Crawler($html);

        $sites =
            [
                'rozetka.com.ua',
                'foxtrot.com.ua',
                'comfy.ua',
            ];

        foreach ($sites as $site) {
            if(strpos($url, $site) !== false){
                switch ($site):
                    case 'rozetka.com.ua':
                        $status = 1;
                        $title = trim($crawler->filter('h1')->text());
                        $picture = $crawler->filter('.product-photo__large-inner img')->eq(0)->attr('src');
                        $price = (int) filter_var($crawler->filter('.product-prices__big')->text(), FILTER_SANITIZE_NUMBER_INT);
                        $currency = $crawler->filter('.product-prices__symbol')->text();
                        break;
                    case 'foxtrot.com.ua':
                        $status = 1;
                        $title = $crawler->filter('meta[itemprop="name"]')->first()->attr('content');
                        $picture = $crawler->filter('meta[itemprop="image"]')->first()->attr('content');
                        $price = (int) filter_var($crawler->filter('.price__relevant')->children('.numb')->text(), FILTER_SANITIZE_NUMBER_INT);
                        $currency = $crawler->filter('.price__relevant')->children('.currency')->text();
                        break;
                    case 'comfy.ua':
                        $status = 1;
                        $title = trim($crawler->filter('h1')->text());
                        $picture = $crawler->filter('picture img')->eq(0)->attr('src');
                        $price = (int) filter_var($crawler->filter('meta[itemprop="price"]')->first()->attr('content'), FILTER_SANITIZE_NUMBER_INT);
                        $currency = $crawler->filter('meta[itemprop="priceCurrency"]')->first()->attr('content');
                        break;
                    default;
                        $status = 0;
                        break;
                endswitch;
            }
        }

        return [
            'url'       => $url,
            'status'    => $status,
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