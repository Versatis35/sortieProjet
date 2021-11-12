<?php

namespace App\Controller;

use App\Entity\Place;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;

class UserManagorController extends AbstractController
{
    /**
     * @Route("/myprofil/modification", name="modification_mon_profil")
     */
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        # On appel le repository qui gère les users
        $repositoryUser = $entityManager->getRepository(User::class);
        $repositoryPlace = $entityManager->getRepository(Place::class);
        $error = "";

        #TODO :  On récupère l'utilisateur de la session (voir comment on le récupère)
        $user = $this->getUser();
        #Création du formulaire de modification
        $formulaireUser = $this->createFormBuilder([], ['label' => 'options', 'attr' => ['enctype' => 'multipart/form-data']])
            ->add('pseudo', TextType::class, ['label' => 'Pseudo', 'attr' => ['value' => $user->getPseudo()]])
            ->add('telephone', TextType::class, ['label' => 'Prénom', 'attr' => ['value' => $user->getTelephone()]])
            ->add('email', TextType::class, ['label' => 'Email', 'attr' => ['value' => $user->getEmail()]])
            ->add('mdp', PasswordType::class, ['label' => 'Mot de passe', 'attr' => ['value' => $user->getMdp()]])
            ->add('confirmation', PasswordType::class, ['label' => 'Confirmation', 'attr' => ['value' => $user->getMdp()]])
            ->add('photo', FileType::class, [
                'label' => 'Photo (.pnj/.jpg)',
                'mapped'=> true,
                'constraints' => [
                    new File([
                        // Le maxSize ne s'applique pas si dans config>packages>dev>web_profiler only_exceptions n'est pas à true
                        'maxSize' => '20M',
                        'maxSizeMessage' => "La limite de taille d'image est de {{ limit }} bytes",
                    ])
                ],
            ])
            ->add('enregistrer', SubmitType::class, ['label' => 'Enregistrer'])
            ->add('annuler', ResetType::class, ['label' => 'Annuler'])
            ->getForm();
        $formulaireUser->handleRequest($request);
        // Si isValid n'est pas mis, problème lors de l'upload
        if($formulaireUser->isSubmitted() && $formulaireUser->isValid())
        {

            $data = $formulaireUser->getData();
            $error = $this->verifMdp($data, $user);
            if($error == "" && !$data['photo']) {
                $mime = $data['photo']->getClientMimeType();
                if($mime !== "image/png" && $mime != "image/jpeg") {
                    $error = "Format de fichier invalide";
                }
            }
            if($error == "")  {
                $pseudo = ['pseudo' => $data['pseudo']];
                // On vérifie si le pseudo n'est pas prit
                $this->verifPseudo($pseudo, $repositoryUser, $data, $user);
                $user->setPseudo($data['pseudo']);
                $user->setTelephone($data['telephone']);
                $user->setEmail($data['email']);
                // On vérifie si les mots de passe correspondent
                if($data['photo'] != "") {
                    $strm = fopen($data['photo']->getRealPath(), 'rb');
                    $user->setPhoto(stream_get_contents($strm));
                }
                $entityManager->flush();
                return $this->redirectToRoute('mon_profil');
            }
        }
        $photo = base64_encode(stream_get_contents($user->getPhoto()));
        return $this->render('user/modification.html.twig', [
                'formulaireUser' => $formulaireUser->createView(),
                'user' => $user,
                'photo' => $photo,
                'error' => $error
        ]);
    }

    public function verifMdp($data, $profil)
    {
        $error = "";
        if($data['mdp'] != $data['confirmation'])
        {
            $error = "Les mots de passe ne sont pas identiques";
            $this->addFlash('error', "Les mots de passe ne sont pas identiques");
        }

        $profil->setPassword(password_hash($data['mdp'], PASSWORD_DEFAULT));
        return $error;
    }

    public function verifPseudo($pseudo, $repositoryUser, $data, $user)
    {
        if(in_array($pseudo, $repositoryUser->getAllPseudo()) && $data['pseudo'] != $user->getPseudo())
        {
            $this->addFlash('alert', "Ce pseudo est déjà prit par un autre utilisateur !");
        }
    }

}
