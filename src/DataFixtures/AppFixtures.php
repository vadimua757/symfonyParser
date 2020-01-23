<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Faker\Provider\ru_RU\Internet;

class AppFixtures extends Fixture
{
    private $faker;

    public function __construct()
    {
        $this->faker = Factory::create();
//        $this->faker = new Generator();
        $this->faker->addProvider(new Internet($this->faker));
    }

    public function load(ObjectManager $manager)
    {
        for ($i = 1; $i < 20; $i++) {
            $post = new Product();
            $post->setUrl($this->faker->url);
            $post->setName($this->faker->sentence(3));
            $post->setPicture($this->faker->imageUrl());
            $post->setPrice($this->faker->randomFloat(2));
            $post->setPrice_old($this->faker->randomFloat(2));
            $post->setCurrency('Грн.');
            $post->setUser_id($this->faker->randomNumber(4));
            $post->setCreatedAt($this->faker->dateTime);

            $manager->persist($post);
        }
        $manager->flush();
    }
}
