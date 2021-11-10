<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\DBAL\Types\TextType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserManagorController extends AbstractController
{
    /**
     * @Route("/profil/modification", name="modification_profil")
     */
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        # On appel le repository qui gère les users
        $repositoryUser = $entityManager->getRepository(User::class);

        #TODO :  On récupère l'utilisateur de la session (voir comment on le récupère)
        $user = $repositoryUser->find(1);

        #Création du formulaire de modification
        $formulaireUser = $this->createFormBuilder([], ['label' => 'options', 'attr' => ['enctype' => 'multipart/form-data']])
            ->add('pseudo', TextType::class, ['label' => 'Pseudo', 'attr' => ['value' => $user->getPseudo()]])
            ->add('prenom', TextType::class, ['label' => 'Prénom', 'attr' => ['value' => $user->getPrenom()]])
            ->add('nom', TextType::class, ['label' => 'Nom', 'attr' => ['value' => $user->getNom()]])
            ->add('telephone', TextType::class, ['label' => 'Prénom', 'attr' => ['value' => $user->getTelephone()]])
            ->add('email', TextType::class, ['label' => 'Email', 'attr' => ['value' => $user->getEmail()]])
            ->add('mdp', PasswordType::class, ['label' => 'Mot de passe', 'attr' => ['value' => $user->getMdp()]])
            ->add('confirmation', PasswordType::class, ['label' => 'Confirmation', 'attr' => ['value' => $user->getMdp()]])
            ->add('ville', TextType::class, ['label' => 'Ville', 'attr' => ['value' => $user->getVille()]])
            //TODO: A compléter ->add('photo', FileType::class, ['label' => 'Photo', 'attr' => ['value' => $profil->getPhoto()]])
            ->add('enregistrer', SubmitType::class, ['label' => 'Enregistrer'])
            ->add('annuler', ResetType::class, ['label' => 'Annuler'])
            ->getForm();

        $formulaireUser->handleRequest($request);

        if($formulaireUser->isSubmitted())
        {
            $data = $formulaireUser->getData();
            $user->setNom($data['nom']);
            $pseudo = ['pseudo' => $data['pseudo']];
            // On vérifie si le pseudo n'est pas prit
            $this->verifPseudo($pseudo, $repositoryUser, $data, $user);
            $user->setPseudo($data['pseudo']);
            $user->setPrenom($data['prenom']);
            $user->setTelephone($data['telephone']);
            $user->setEmail($data['email']);
            // On vérifie si les mots de passe correspondent
            $this->verifMdp($data, $user);
            $user->setSite($repositoryUser->find($data['ville']));
            $entityManager->flush();
            return $this->redirectToRoute('profil');
        }

        return $this->render('user/inscriptionetmodification.html.twig', [
                'controller_name' => 'UserManagorController',
                'formulaireProfil' => $formulaireUser->createView(),
                'user' => $user
                //TODO: A compléter 'imageProfil' => './../img'
        ]);
    }

    public function verifMdp($data, $profil)
    {
        if($data['password'] != $data['confirmation'])
        {
            $this->addFlash('alert', "Les mots de passe ne sont pas identiques");
        }

        $profil->setPassword(password_hash($data['password'], PASSWORD_DEFAULT));
    }

    public function verifPseudo($pseudo, $repositoryUser, $data, $user)
    {
        if(in_array($pseudo, $repositoryUser->getAllPseudo()) && $data['pseudo'] != $user->getPseudo())
        {
            $this->addFlash('alert', "Ce pseudo est déjà prit paar un autre utilisateur !");
        }
    }

}
