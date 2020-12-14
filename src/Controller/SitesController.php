<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Sites;
use App\Form\SiteType;
use App\Service\Parser;
use Clue\React\Buzz\Browser;
use React\EventLoop\Factory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class SitesController extends AbstractController
{
    /**
     * @Route("/sites", name="sites")
     * @param Request $request
     * @param UserInterface $user
     * @param Breadcrumbs $breadcrumbs
     * @return RedirectResponse|Response
     */
    public function index(Request $request, UserInterface $user, Breadcrumbs $breadcrumbs)
    {
        $breadcrumbs->addRouteItem("Sites", "sites");
        $em = $this->getDoctrine()->getManager();

        if ($this->isGranted('ROLE_ADMIN')) {
            $sites = $em->getRepository('App\Entity\Sites')->findAll();
        } else {
            $sites = $user->getSites();
        }

        $site = new Sites();
        $form = $this->createForm(SiteType::class, $site);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $site = $form->getData();
            $site->addUser($user);

            $em->persist($site);
            $em->flush();
            return $this->redirectToRoute('sites');

        }

    return $this->render('sites/index.html.twig',
        [
            'sites' => $sites,
            'breadcrumbs' => $breadcrumbs,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/sites/{id}/delete", name="sites_delete")
     * @param $id
     * @param UserInterface $user
     * @return RedirectResponse
     */
    public function delete($id, UserInterface $user)
    {
        $em = $this->getDoctrine()->getManager();

        if ($this->isGranted('ROLE_ADMIN')) {
            $product = $em->getRepository('App\Entity\Sites')->findOneBy(['id' => $id]);
        } else {
            $product = $em->getRepository('App\Entity\Sites')->findOneBy(['user_id' => $user->getId(), 'id' => $id]);
        }

        if ($product != null){
            $em->remove($product);
            $em->flush();
        }
        return $this->redirectToRoute('sites');
    }
}
