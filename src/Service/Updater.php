<?php

namespace App\Service;

use App\Controller\ProductsController;
use App\Entity\Statistic;
use Clue\React\Buzz\Browser;
use DateTime;
use Doctrine\ORM\EntityManager;
use React\EventLoop\Factory;
use App\Service\Mailer;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Updater
{

    protected $em;
    protected $mailer;

    public function __construct(EntityManager $em, Mailer $mailer)
    {
        $this->em = $em;
        $this->mailer = $mailer;
    }

    public function updateAll(){

        $products = $this->em->getRepository('App\Entity\Product')->findAll();
        $i = 0;
        $time_start = microtime(true);
        $time = date("Y-m-d H:i:s");
        foreach ($products as $product) {
            try {
                $loop = Factory::create();
                $client = new Browser($loop);
                $parser = new Parser($client);
                $parser->parse($product->getUrl());
                $loop->run();
                $parsed = $parser->getData();
                $price_old = $product->getPrice();
                $price_new = $parsed['price'];

                $product->setPrice_old($price_old);
                $product->setPrice($price_new);
                $product->setUpdatedAt(new DateTime());

                $this->em->persist($product);
                $this->em->flush();

                $users = $product->getUser()->toArray();
                $statistic = $this->em->getRepository('App\Entity\Statistic')->findOneBy(['product' => $product]);

                if (!$statistic || $price_old != $price_new) {
                    $statistic = new Statistic();
                    $statistic->setProduct($product);
                    $statistic->setPrice($parsed['price']);
                    $statistic->setDate(new DateTime());

                    $this->em->persist($statistic);
                    $this->em->flush();
                } elseif ($price_old != $price_new){
                    foreach ($users as $user) {
                        try {
                        $this->mailer->sendChangePriceMessage($product->getName(), $price_old, $price_new, $user->email);
                        } catch (LoaderError $e) {
                            return $e;
                        } catch (RuntimeError $e) {
                            return $e;
                        } catch (SyntaxError $e) {
                            return $e;
                        }
                    }
                }
            } catch (\Exception $e) {
                return $e->getMessage();
            }
            sleep(1);
            $i++;
        }
        $time_end = microtime(true);
        $execution_time = round(($time_end - $time_start), 0);

        $msg = "$time; Updated $i products. Time spend: $execution_time seconds";
//        try {
//            $this->mailer->sendParsedMessage($msg);
//        } catch (LoaderError $e) {
//            return $e;
//        } catch (RuntimeError $e) {
//            return $e;
//        } catch (SyntaxError $e) {
//            return $e;
//        }
        return $msg;
    }
}