<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\User;
use App\Form\AddCityType;
use App\Form\ModifyCityType;
use App\Form\UserType;
use App\Repository\CityRepository;
use App\Repository\PlaceRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class CityController extends AbstractController
{

    /**
     * @Route("/admin/ville/gerer", name="gerer_ville")
     * @param CityRepository $cityRepository
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function gererVille(CityRepository $cityRepository, Request $request, EntityManagerInterface $em): Response
    {
        // Appel des données de l'entité "Ville"
        $Allcity = $cityRepository->findAll();

        // Ajouter une ville
        $city = New City();

        // Création du formulaire
        $addCityForm = $this->createForm(AddCityType::class, $city);

        // Exécute
        $addCityForm->handleRequest($request);

        // Si le formulaire est envoyé
        if($addCityForm->isSubmitted())
        {
            // On envoit dans la bdd
            $em->persist($city);
            $em->flush();

            // On renvoit un message de succès
            $this->addFlash('success', 'Ville "'. $city->getNom() .'" ajoutée avec succès !');

            // On redirige vers la page gerer_ville
            return $this->redirectToRoute('gerer_ville');
        }

        // On redirige vers la page gerer_ville
        return $this->render('admin/gererVille.html.twig', [
            'city' => $Allcity,
            "addCityForm" => $addCityForm->createView()
        ]);
    }


    /**
     * @Route("/admin/ville/gerer/{id}", name="delete_city")
     * @param EntityManagerInterface $em
     * @param int $id
     * @return Response
     */
    public function deleteCity(EntityManagerInterface $em, int $id): Response
    {
        // On récupère l'identifiant
        $identifiant = $em->getRepository(City::class)->find($id);

        // On supprime l'instance de la bdd
        $em->remove($identifiant);

        // On envoit dans la bdd
        $em->flush();

        // On retourne un message de succès
        $this->addFlash('success', 'Site supprimé avec succès !');

        // On redirige vers la page gerer_ville
        return $this->redirectToRoute('gerer_ville');
    }


    /**
     * @Route("/admin/ville/modify/{id}", name="modify_city")
     * @param EntityManagerInterface $em
     * @param int $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function modifyCity(EntityManagerInterface $em, int $id, Request $request)
    {
        // On récupère l'identifiant de l'instance
        $identifiant = $em->getRepository(City::class)->find($id);

        // Création du formulaire sur la page
        $modifyCityForm = $this->createForm(ModifyCityType::class, $identifiant);

        // Exécution
        $modifyCityForm->handleRequest($request);

        // Si le formulaire est renvoyé
        if($modifyCityForm->isSubmitted() && $modifyCityForm->isValid())
        {
            // On envoit les modifiacations dans la base de donnée
            $em->flush();

            // On renvoit un message de succès
            $this->addFlash('success', 'Site modifié avec succès !');

            // On se redirige vers la page gerer ville
            return $this->redirectToRoute('gerer_ville');
        }

        // redirige vers la page gerer ville
        return $this->render('admin/modificationVille.html.twig', [
            'modifyCityForm' => $modifyCityForm->createView(),
            'id' => $id
        ]);
    }

    /**
     * @Route("/ville/filtre", name="filtre_ville", methods={"POST"})
     */
    public function filterCity(Request $request, CityRepository $cityRepo): Response
    {
        // Requête pour récupérer les données nécessaires
        $result = $cityRepo->createQueryBuilder('c');
        $result->select(["c.id","c.nom", "c.codePostal", "c.pays"]);

        // Le json récupère le résultat de la requête
        $json = $request->getContent();

        // Récupère la chaine encodée en JSON et la convertit en variable PHP que l'on stocke dans la variable $obj
        $obj = json_decode($json);

        // La variable $searchWord récupère le résultat du JSON
        $searchWord = $obj->searchWord;

        // Si le résultat n'est pas vide
        if($searchWord != "")
        {
            // On récupère la/les valeurs
            $result->where("c.nom LIKE :nom");
            $result->orWhere("c.codePostal LIKE :nom");
            $result->setParameter("nom", "%".$searchWord."%");
        }

        // $tabCity contient le résultat (la/les valeurs)
        $tabCity = $result->getQuery()->getResult();

        // Retourne le résultat
        return $this->json($tabCity);
    }

}