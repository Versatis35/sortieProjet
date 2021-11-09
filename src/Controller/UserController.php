<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class UserController extends AbstractController
{
    /**
     * @Route("/connexion", name="se_connecter")
     */
    public function seConnecter(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render('user/connexion.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        return $this->render('trip/index.html.twig');
    }

    /**
     * @Route("/user/gestionUtilisateur/{type}/{id}", name="gestion_utilisateur")
     */
    public function gererUtilisateur($type,$id,Request $request,EntityManagerInterface $em, UserRepository $repo, UserPasswordHasherInterface $passwordEncoder): Response
    {
        // Type = modification ou creation
        switch($type) {
            case "modification":
                $user = new User();
                $formUser = $this->createForm(UserType::class,$user);
                $formUser->handleRequest($request);
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();
                break;
            case "creation":
                $user = new User();
                $formUser = $this->createForm(UserType::class,$user);
                $formUser->handleRequest($request);
                if($this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
                    if ($formUser->isSubmitted()) {
                        $user->setRoles(["ROLE_USER"]);
                        $user->setPassword(
                            $passwordEncoder->hashPassword(
                                $user,
                                $formUser->get('password')->getData()
                            )
                        );
                        $em = $this->getDoctrine()->getManager();
                        $em->persist($user);
                        $em->flush();
                        return $this->redirectToRoute('home');
                    }

                } else {
                    throw $this->createNotFoundException('Vous n\'avez pas accès à cette page');
                }
                break;
            case 1:
                throw $this->createNotFoundException('Page inexistante');
                break;
        }

        return $this->render('user/inscriptionetmodification.html.twig', [
            'formUser' => $formUser->createView()
        ]);
    }

    /**
     * @Route("/profil/{id}", name="profil")
     */
    public function profil($id, UserRepository $repo): Response
    {
        $user = $repo->findOneBy(
            [
                'id'=>$id
            ]
        );
        return $this->render('user/profil.html.twig', [
            'utilisateur'=>$user
        ]);
    }
}
