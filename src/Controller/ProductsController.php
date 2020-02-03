<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Service\Parser;
use DateTime;
use Exception;
use React\EventLoop\Factory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;
use Clue\React\Buzz\Browser;

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
        $products = $this->productRepository->findAll();

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

            $post->setCreatedAt(new DateTime());
            $post->setUpdatedAt(new DateTime());
            $post->setName($parsed['title']);
            $post->setPicture($parsed['picture']);
            $post->setPrice($parsed['price']);
            $post->setPrice_old('');
            $post->setCurrency($parsed['currency']);
            $post->setUser_id($user->getId());

            $em = $this->getDoctrine()->getManager();
            $em->persist($post);
            $em->flush();

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
     * @Route("/products{id}/update", name="products_update")
     * @param $id
     * @param UserInterface $user
     * @return RedirectResponse|Response
     * @throws Exception
     */
    public function update($id, UserInterface $user)
    {

        $em = $this->getDoctrine()->getManager();
        if ($this->isGranted('ROLE_ADMIN')) {
            $product = $em->getRepository('App\Entity\Product')->findOneBy(['id' => $id]);
        } else {
            $product = $em->getRepository('App\Entity\Product')->findOneBy(['user_id' => $user->getId(), 'id' => $id]);
        }

        $loop = Factory::create();
        $client = new Browser($loop);
        $parser = new Parser($client);
        $parser->parse($product->getUrl());
        $loop->run();
        $parsed = $parser->getData();

        $product->setPrice_old($product->getPrice());
        $product->setPrice($parsed['price']);
        $product->setUpdatedAt(new DateTime());

        $em = $this->getDoctrine()->getManager();
        $em->persist($product);
        $em->flush();

        return $this->redirectToRoute('products');
    }

    /**
     * @Route("/products/batch_update", name="products_batch_update")
     * @param UserInterface $user
     * @return RedirectResponse|Response
     */
    public function batchUpdate(UserInterface $user)
    {
        $products = $this->productRepository->findAll();

        foreach ($products as $product) {
            try {
                $this->update($product->getId(), $user);
            } catch (Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
            sleep(2);
        }

        return $this->redirectToRoute('products');
    }
}
