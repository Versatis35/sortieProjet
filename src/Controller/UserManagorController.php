<?php

namespace App\Controller;

use App\Entity\Place;
use App\Entity\User;
use App\Repository\UserRepository;
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
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;

class UserManagorController extends AbstractController
{
    /**
     * @Route("/profil/modification/{id}", name="modification_profil")
     */
    public function index($id,Request $request, EntityManagerInterface $entityManager, UserRepository $repo): Response
    {
        # On appel le repository qui gère les users
        $repositoryUser = $repo;
        $repositoryPlace = $entityManager->getRepository(Place::class);
        $error = "";

        $user = $repo->findOneBy(['id'=>$id]);
        $userOrigin = $user;
        #Création du formulaire de modification
        $formulaireUser = $this->createFormBuilder([], ['label' => 'options', 'attr' => ['enctype' => 'multipart/form-data']])
            ->add('pseudo', TextType::class, ['label' => 'Pseudo', 'attr' => ['value' => $user->getPseudo()]])
            ->add('telephone', TextType::class, ['label' => 'Téléphone', 'attr' => ['value' => $user->getTelephone()]])
            ->add('email', TextType::class, ['label' => 'Email', 'attr' => ['value' => $user->getEmail()]])
            ->add('mdp', PasswordType::class, ['label' => 'Mot de passe', 'attr' => ['value' => $user->getMdp()]])
            ->add('confirmation', PasswordType::class, ['label' => 'Confirmation', 'attr' => ['value' => $user->getMdp()]])
            ->add('photo', FileType::class, [
                'label' => 'Photo (.pnj/.jpg)',
                'mapped'=> true,
                'required'=> false,
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
            if($error == "" && $data['photo'] != "") {
                $mime = $data['photo']->getClientMimeType();
                if($mime !== "image/png" && $mime != "image/jpeg") {
                    $error = "Format de fichier invalide";
                }
            }
            $pseudo = $data['pseudo'];
            if($error == "") $error = $this->verifPseudo($pseudo, $repositoryUser, $data, $user);
            if($error == "")  {
                // On vérifie si le pseudo n'est pas prit
                $user->setPseudo($data['pseudo']);
                $user->setTelephone($data['telephone']);
                $user->setEmail($data['email']);
                // On vérifie si les mots de passe correspondent
                if($data['photo'] != "") {
                    $strm = fopen($data['photo']->getRealPath(), 'rb');
                    $user->setPhoto(stream_get_contents($strm));
                }
                $entityManager->persist($user);
                $entityManager->flush();
                return $this->redirectToRoute('mon_profil');
            } else {
                $this->addFlash('alert', $error);
                return $this->redirectToRoute('home');
            }
        }
        if($user->getPhoto() != null) {
            $photo = base64_encode(stream_get_contents($user->getPhoto()));
        } else {
            $photo = "";
        }
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
        } else {
            $profil->setPassword(password_hash($data['mdp'], PASSWORD_DEFAULT));
        }

        return $error;
    }

    public function verifPseudo($pseudo, $repositoryUser, $data, $user)
    {
        $userTest = $repositoryUser->findOneBy(['pseudo'=>$pseudo]);
        if($userTest!="" && $userTest != $user)
        {
            return "Ce pseudo est déjà prit par un autre utilisateur !";
        }
        return "";

    }

}
