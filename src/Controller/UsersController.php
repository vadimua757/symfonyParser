<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class UsersController extends AbstractController
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /** @var UserRepository $userRepository */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @Route("/users", name="users")
     * @param Breadcrumbs $breadcrumbs
     * @return Response
     */
    public function index(Breadcrumbs $breadcrumbs)
    {
        $breadcrumbs->addRouteItem("Users", "users");
        $breadcrumbs->prependRouteItem("Home", "site");

        $users = $this->userRepository->findAll();
        return $this->render('users/index.html.twig', [
            'users' => $users,
        ]);
    }

    /**
     * @Route("/users/{id}/delete", name="user_delete")
     * @param $id
     * @param UserInterface $user
     * @return RedirectResponse
     */
    public function delete($id, UserInterface $user)
    {
        $em = $this->getDoctrine()->getManager();
//        $em->remove($product);
//        $em->flush();


        $delete = $em->getRepository('App\Entity\User')->findOneBy(['id' => $id]);

        if ($delete != null){
            $em->remove($delete);
            $em->flush();
        }
        $this->addFlash('success', 'Deleted!');
        return $this->redirectToRoute('users');
    }
}
