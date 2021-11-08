<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    /**
     * @Route("/connexion", name="se_connecter")
     */
    public function seConnecter(Request $request,EntityManagerInterface $em): Response
    {
        $user = new User();
        // relier $wish au formulaire
        $formWish = $this->createForm(WishType::class,$wish);
        // Hydrater le $wish
        $formWish->handleRequest($request);
        if ( $formWish->isSubmitted()){
            $wish->setIsPublished(true);
            $wish->setDateCreated(new \DateTime());
            $em->persist($wish);
            $em->flush();
            return $this->redirectToRoute('home');
        }
        return $this->render('back/ajouter.html.twig', [
            'formWish' =>  $formWish->createView(),
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
