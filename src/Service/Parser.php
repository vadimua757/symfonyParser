<?php

namespace App\Service;

use Clue\React\Buzz\Browser;
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
                function (\Psr\Http\Message\ResponseInterface $response) {
                    $this->parsed[] = $this->extractFromHtml((string) $response->getBody());
                });
    }

    public function extractFromHtml($html)
    {
        $crawler = new Crawler($html);

        $title = trim($crawler->filter('h1')->text());
        $price = $crawler->filter('[itemprop="price"]')->extract(['content']);
//        $description = trim($crawler->filter('[itemprop="price"]')->text());

//        $crawler->filter('#titleDetails .txt-block')->each(
//            function (Crawler $crawler) {
//                foreach ($crawler->children() as $node) {
//                    $node->parentNode->removeChild($node);
//                }
//            }
//        );

//        $releaseDate = trim($crawler->filter('#titleDetails .txt-block')->eq(2)->text());

        return [
            'title'        => $title,
            'price'       => $price,
//            'description'  => $description,
//            'release_date' => $releaseDate,
        ];
    }

    public function getData()
    {
        return $this->parsed;
    }
}