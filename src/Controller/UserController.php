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
     * @Route("/profil/{id}", name="profil")
     */
    public function profil($id, UserRepository $repo): Response
    {
        $user = $repo->findOneBy(
            [
                'id'=>$id
            ]
        );
        $userActuel = $this->getUser();
        if($user->getPhoto() != null) {
            $photo = base64_encode(stream_get_contents($user->getPhoto()));
        } else {
            $photo = "";
        }
        return $this->render('user/profil.html.twig', [
            'user'=>$user,
            'photo'=>$photo,
            'userActual'=>$userActuel
        ]);
    }

    /**
     * @Route("/profil", name="mon_profil")
     */
    public function monProfil(): Response
    {
        $user = $this->getUser();
        $userActuel = $user;
        if($user->getPhoto() != null) {
            $photo = base64_encode(stream_get_contents($user->getPhoto()));
        } else {
            $photo = "";
        }
        return $this->render('user/profil.html.twig', [
            'user'=>$user,
            'photo'=>$photo,
            'userActual'=>$userActuel
        ]);
    }

}
