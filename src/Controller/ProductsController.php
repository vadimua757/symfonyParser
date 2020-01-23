<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Omines\DataTablesBundle\Adapter\Doctrine\ORM\SearchCriteriaProvider;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\NumberColumn;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Omines\DataTablesBundle\Adapter\ArrayAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Faker\Factory;
use Faker\Generator;
use Faker\Provider\ru_RU\Internet;
use Symfony\Component\Security\Core\User\UserInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;
use Clue\React\Buzz\Browser;
use Parser;

class ProductsController extends AbstractController
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /** @var ProductRepository $productRepository */
    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * @Route("/products", name="products")
     * @param Breadcrumbs $breadcrumbs
     * @return Response
     */
    public function index(Breadcrumbs $breadcrumbs)
    {
//        $breadcrumbs->addItem("Home", $this->get("router")->generate("products"));
        $breadcrumbs->addRouteItem("Products", "products");
        $breadcrumbs->prependRouteItem("Home", "site");
        $products = $this->productRepository->findAll();
        return $this->render('products/index.html.twig', [
            'products' => $products,
            'breadcrumbs' => $breadcrumbs
        ]);
    }
    /**
     * @var Generator
     */
    private $faker;

//    /**
//     * @Route("/products", name="products")
//     * @param Request $request
//     * @param DataTableFactory $dataTableFactory
//     * @param UserInterface $user
//     * @return JsonResponse|Response
//     */
//    public function showAction(Request $request, DataTableFactory $dataTableFactory, UserInterface $user)
//    {
//        $table = $dataTableFactory->create()
//            ->add('url', TextColumn::class, ['label' => 'Название', 'className' => 'bold'])
//            ->add('name', TextColumn::class, ['render' => '<strong>%s</strong>',])
//            ->add('picture', TextColumn::class, ['render' => function($value, $context) {
//                return sprintf('<img src="%s" height="50" alt="%s">', $value, $value);
//            }])
//            ->add('price', NumberColumn::class)
//            ->add('created_at', DateTimeColumn::class, ['format' => 'd-m-Y'])
//            ->add('id',  TextColumn::class, ['render' => function($value, $context) {
//                return sprintf('<a href="products/%s/delete"><i class="fa fa-trash"></i></a>', $value);
//            }])
//            ->createAdapter(ORMAdapter::class, [
//                'entity' => Product::class,
//                'criteria' => [
//                    function (QueryBuilder $builder) use ($user) {
//                        $builder->andWhere($builder->expr()->like('product.user_id', ':id'))->setParameter('id', $user->getId());
//                    },
//                    new SearchCriteriaProvider(),
//                ],
//            ])
//            ->handleRequest($request);
//
//        if ($table->isCallback()) {
//            return $table->getResponse();
//        }
//
//        $products = $this->productRepository->findAll();
//
//        return $this->render('products/index.html.twig', [
//            'datatable' => $table,
//            'products' => $products
//        ]);
//    }

    /**
     * @Route("/products/new", name="new_product")
     * @param Request $request
     * @param UserInterface $user
     * @param Breadcrumbs $breadcrumbs
     * @return RedirectResponse|Response
     * @throws Exception
     */
    public function addProduct(Request $request, UserInterface $user, Breadcrumbs $breadcrumbs)
    {
        $loop = \React\EventLoop\Factory::create();
        $client = new Browser($loop);

        $this->faker = Factory::create();
        $this->faker->addProvider(new Internet($this->faker));

        $post = new Product();
        $form = $this->createForm(ProductType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $parser = new Parser($client);
            $product = $request->request->get('product');
            $parser->parse($product['url']);

            $loop->run();
            print_r($parser->getData());






//            $post->setCreatedAt(new \DateTime());
//            $post->setName($this->faker->sentence(3));
//            $post->setPicture($this->faker->imageUrl());
//            $post->setPrice($this->faker->randomFloat(2));
//            $post->setPrice_old($this->faker->randomFloat(2));
//            $post->setCurrency('Грн.');
//            $post->setUser_id($user->getId());
//
//            $em = $this->getDoctrine()->getManager();
//            $em->persist($post);
//            $em->flush();
//
//            return $this->redirectToRoute('products');
        }
        return $this->render('products/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/products/{id}/delete", name="products_delete")
     * @param $id
     * @param UserInterface $user
     * @return RedirectResponse
     */
    public function delete($id, UserInterface $user)
    {
        $em = $this->getDoctrine()->getManager();
//        $em->remove($product);
//        $em->flush();


        $product = $em->getRepository('App\Entity\Product')->findOneBy(['user_id' => $user->getId(), 'id' => $id]);

        if ($product != null){
            $em->remove($product);
            $em->flush();
        }
        return $this->redirectToRoute('products');
    }
}
