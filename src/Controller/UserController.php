<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    /**
     * @Route("/connexion", name="se_connecter")
     */
    public function seConnecter(): Response
    {
        return $this->render('user/connexion.html.twig', [
            'controller_name' => 'Connexion',
        ]);
    }

    /**
     * @Route("/gestionUtilisateur", name="gestion_utilisateur")
     */
    public function gererUtilisateur(): Response
    {
        return $this->render('user/inscriptionetmodification.html.twig', [
            'controller_name' => 'Modifier et créer',
        ]);
    }

    /**
     * @Route("/profil", name="profil")
     */
    public function profil(): Response
    {
        return $this->render('user/profil.html.twig', [
            'controller_name' => 'Profil',
        ]);
    }
}
