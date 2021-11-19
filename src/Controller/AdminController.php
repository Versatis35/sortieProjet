<?php

namespace App\Controller;

use App\Entity\Trip;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\PlaceRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    /**
     * @Route("/admin/import", name="admin")
     */
    public function index(Request $request, EntityManagerInterface $em, PlaceRepository $repo, UserPasswordHasherInterface $passwordEncoder): Response
    {
        $defaultData = [];
        $form = $this->createFormBuilder($defaultData)
            ->add('file', FileType::class, [
                'mapped' => false,
                'label'=>"Fichier d'import",

                'required' => false,])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            // Nom des colonnes
            $header = NULL;
            // données
            $data = array();
            // Si on peut ouvrir le fichier en mode r (Read)
            if (($handle = fopen($file->getRealPath(), 'r')) !== FALSE) {
                // Tant qu'il y a des lignes à lire, on défile
                while (($row = fgetcsv($handle, 1000, ";", '"')) !== FALSE) {
                    // Si nos noms de colonnes ne sont pas inscrites alors la premiere ligne lue = nom de colonne
                    if(!$header) {
                        $header = $row;
                    } else {
                        // Sinon on va mapper chaque cellule de la ligne lue avec la colonne correspondante
                        $data[] = array_combine($header, $row);
                    }
                }
            }
            // On ferme la lecture du fichier
            fclose($handle);
            $em = $this->getDoctrine()->getManager();
            // Tant qu'il y a de la donnée à lire
            foreach($data as $row) {
                // Il y a une erreur dans la récupération de la première colonne, on passe donc par une lecture par index
                $rowPseudo = array_values($row);
                $user = new User();
                // Et on mappe chaque donnée à un nouvel utilisateur
                $user->setPseudo($rowPseudo[0]);
                $user->setNom($row["nom"]);
                $user->setPrenom($row["prenom"]);
                $user->setActive($row["active"]);
                $user->setEmail($row["email"]);
                // Sans oublier de mapper avec les bonnes correlations
                $site = $repo->findOneBy([
                    "id"=>$row["site"]
                ]);
                $user->setSite($site);
                $user->setTelephone($row["telephone"]);
                $user->setRoles(["ROLE_USER"]);
                // Ainsi que de hasher le mot de passe
                $user->setPassword(
                    $passwordEncoder->hashPassword(
                        $user,
                        $row["password"]
                    )
                );
                // Pour sauvegarder chaque utilisateur créés
                $em->persist($user);
            }
            // et les envoyer dans la base de donnée
            $em->flush();
            // Et on affiche le message de succès
            $this->addFlash('success', "Les utilisateurs ont été importées avec succès !");

        }
        return $this->render('admin/importUser.html.twig',
        [
            "uploadform"=>$form->createView()
        ]);
    }

    /**
     * @Route("/admin/creationUtilisateur", name="creation_utilisateur")
     */
    public function creerUtilisateur(Request $request, UserPasswordHasherInterface $passwordEncoder): Response
    {
        $user = new User();
        $formUser = $this->createForm(UserType::class,$user);
        $formUser->handleRequest($request);
        if ($formUser->isSubmitted() && $formUser->isValid()) {
            $user->setRoles(["ROLE_USER"]);
            $user->setPassword(
                $passwordEncoder->hashPassword(
                    $user,
                    $formUser->get('password')->getData()
                )
            );
            $user->setActive(true);
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();
            $this->addFlash('success', "L'utilisateur a été créé avec succès");
            return $this->redirectToRoute('home');
        }

        return $this->render('admin/creation.html.twig', ['formulaireUser' => $formUser->createView(),
        ]);
    }

    /**
     * @Route("/admin/gestionUtilisateur", name="gestion_utilisateur")
     */
    public function gestionUser(UserRepository $userRepo): Response
    {
        if($this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN') == false) {
            return $this->redirectToRoute('home');
        }

        $users = $userRepo->findAll();

        return $this->render('admin/gestionUser.html.twig', ['users' => $users]);
    }

    /**
     * @Route("/admin/desactivation/{id}", name="desactiver_utilisateur")
     */
    public function desactiverUser(User $user): Response
    {
        if($this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN') == false) {
            return $this->redirectToRoute('home');
        }

        $em = $this->getDoctrine()->getManager();
        $user->setActive(false);
        $em->persist($user);
        $em->flush();

        $this->addFlash('success', "L'utilisateur est désactivé avec succès");
        return $this->redirectToRoute('gestion_utilisateur');
    }

    /**
     * @Route("/admin/activation/{id}", name="activer_utilisateur")
     */
    public function activerUser(User $user): Response
    {
        if($this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN') == false) {
            return $this->redirectToRoute('home');
        }

        $em = $this->getDoctrine()->getManager();
        $user->setActive(true);
        $em->persist($user);
        $em->flush();

        $this->addFlash('success', "L'utilisateur est activé avec succès");
        return $this->redirectToRoute('gestion_utilisateur');
    }

    /**
     * @Route("/admin/suppression/{id}", name="supprimer_utilisateur")
     */
    public function deleteUser(User $user): Response
    {
        if($this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN') == false) {
            return $this->redirectToRoute('home');
        }

        $em = $this->getDoctrine()->getManager();
        $trips = $user->getSorties();
        foreach ($trips as $trip){
            $trip->removeParticipant($user);
            $em->persist($trip);
        }
        $em->flush();

        $tripOrga = $user->getSortiesOrganisees();

        foreach ($tripOrga as $tri){
            foreach ($tri->getParticipants() as $use){
                $tri->removeParticipant($use);
            }
            $em->remove($tri);
            $em->flush();
        }
        $em->remove($user);
        $em->flush();

        $this->addFlash('success', "L'utilisateur est supprimé avec succès");
        return $this->redirectToRoute('gestion_utilisateur');
    }
}
