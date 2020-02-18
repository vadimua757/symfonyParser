<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Service\Mailer;
use App\Service\Parser;
use DateTime;
use Exception;
use React\EventLoop\Factory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;
use Clue\React\Buzz\Browser;
use App\Controller\StatisticController;

class ProductsController extends AbstractController
{
    /**
     * @var ProductRepository
     */
    private $productRepository;
    private $mailer;

    /**
     *
     *
     * @param Mailer $mailer
     * @var ProductRepository $productRepository
     */
    public function __construct(Mailer $mailer, ProductRepository $productRepository = null)
    {
        $this->productRepository = $productRepository;
        $this->mailer = $mailer;
    }

    /**
     * @Route("/products", name="products")
     * @param Request $request
     * @param UserInterface $user
     * @param Breadcrumbs $breadcrumbs
     * @return Response
     * @throws Exception
     */
    public function index(Request $request, UserInterface $user, Breadcrumbs $breadcrumbs)
    {
        $breadcrumbs->addRouteItem("Products", "products");
        $breadcrumbs->prependRouteItem("Home", "site");
        $em = $this->getDoctrine()->getManager();

        if ($this->isGranted('ROLE_ADMIN')) {
            $products = $em->getRepository('App\Entity\Product')->findAll();
        } else {
            $products = $user->getProducts()->toArray();
        }

        //form for adding product
        $loop = Factory::create();
        $client = new Browser($loop);

        $post = new Product();
        $form = $this->createForm(ProductType::class, $post);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $parser = new Parser($client);
            $product = $request->request->get('product');
            $parser->parse($product['url']);
            $loop->run();
            $parsed = $parser->getData();

            if ($parsed['status'] == 0) {
                $this->mailer->sendNewSite(($parsed['url']));
                $this->addFlash('warning', 'Извините, но такой сайт мы еще не добавили в систему');
                return $this->redirectToRoute('products');
            }

            //check if product exist
            $existing_product = $em->getRepository('App\Entity\Product')->findOneBy(['url' => $product['url']]);
            if ($existing_product && $user == $existing_product->getUser()->current()){
                $this->addFlash('danger', 'Извините, но этот товар уже есть у Вас в списке');
                return $this->redirectToRoute('products');
            } elseif ($existing_product) {
                $this->addProduct($user, $existing_product);
            } else{
                $post->setCreatedAt(new DateTime());
                $post->setUpdatedAt(new DateTime());
                $post->setName($parsed['title']);
                $post->setPicture($parsed['picture']);
                $post->setPrice($parsed['price']);
                $post->setPrice_old('');
                $post->setCurrency($parsed['currency']);
                $post->addUser($user);

                $em->persist($post);
                $em->flush();
            }
            return $this->redirectToRoute('products');
        }

        return $this->render('products/index.html.twig', [
            'products' => $products,
            'breadcrumbs' => $breadcrumbs,
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

        if ($this->isGranted('ROLE_ADMIN')) {
            $product = $em->getRepository('App\Entity\Product')->findOneBy(['id' => $id]);
        } else {
            $product = $em->getRepository('App\Entity\Product')->findOneBy(['user_id' => $user->getId(), 'id' => $id]);
        }

        if ($product != null){
            $em->remove($product);
            $em->flush();
        }
        return $this->redirectToRoute('products');
    }

    /**
     * @Route("/products/{id}/update", name="products_update")
     * @param $id
     * @param UserInterface $user
     * @return RedirectResponse|Response
     * @throws Exception
     */
    public function update($id)
    {

        $em = $this->getDoctrine()->getManager();
        $product = $em->getRepository('App\Entity\Product')->findOneBy(['id' => $id]);

        $loop = Factory::create();
        $client = new Browser($loop);
        $parser = new Parser($client);
        $parser->parse($product->getUrl());
        $loop->run();
        $parsed = $parser->getData();

        $product->setPrice_old($product->getPrice());
        $product->setPrice($parsed['price']);
        $product->setUpdatedAt(new DateTime());

        $em->persist($product);
        $em->flush();

        return $this->redirectToRoute('products');
    }

    /**
     * @Route("/products/batch_update", name="products_batch_update")
     * @return RedirectResponse|Response
     */
    public function batchUpdate()
    {
        $em = $this->getDoctrine()->getManager();
        $products = $em->getRepository('App\Entity\Product')->findAll();

        $i = 0;
        $time_start = microtime(true);
        foreach ($products as $product) {
            try {
                $this->update($product->getId());
            } catch (Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
            sleep(1);
            $i++;
        }
        $time_end = microtime(true);
        $execution_time = round(($time_end - $time_start), 0);
        $this->addFlash('success', "Updated $i products. Time spend: $execution_time seconds");
        return $this->redirectToRoute('products');
    }

    /**
     * Add product
     *
     * @param User $user
     * @param Product $product
     *
     */
    public function addProduct(User $user, Product $product)
    {
        $em = $this->getDoctrine()->getManager();

        $product->addUser($user);
        $user->addProduct($product);

        $em->persist($product);
        $em->persist($user);
        $em->flush();
    }
}
