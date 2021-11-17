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
     */
    public function gererVille(CityRepository $cityRepository, Request $request, EntityManagerInterface $em): Response
    {
        // Appel des données de l'entité "Ville"
        $Allcity = $cityRepository->findAll();

        // Ajouter une ville
        $city = New City();
        $addCityForm = $this->createForm(AddCityType::class, $city);
        $addCityForm->handleRequest($request);

        if($addCityForm->isSubmitted())
        {
            $em->persist($city);
            $em->flush();
            $this->addFlash('success', 'Ville "'. $city->getNom() .'" ajoutée avec succès !');
            return $this->redirectToRoute('gerer_ville');
        }

        return $this->render('admin/gererVille.html.twig', [
            'city' => $Allcity,
            "addCityForm" => $addCityForm->createView()
        ]);
    }

    /**
     * @Route("/admin/ville/gerer/{id}", name="delete_city")
     */
    public function deleteCity(EntityManagerInterface $em, int $id): Response
    {
        $identifiant = $em->getRepository(City::class)->find($id);
        $em->remove($identifiant);
        $em->flush();
        $this->addFlash('success', 'Site supprimé avec succès !');
        return $this->redirectToRoute('gerer_ville');
    }

    /**
     * @Route("/admin/ville/modify/{id}", name="modify_city")
     */
    public function modifyCity(EntityManagerInterface $em, int $id, Request $request)
    {
        $class = $em->getRepository(City::class);
        $identifiant = $em->getRepository(City::class)->find($id);
        $modifyCityForm = $this->createForm(ModifyCityType::class, $identifiant);
        $modifyCityForm->handleRequest($request);

        if($modifyCityForm->isSubmitted())
        {
            $em->flush();
            $this->addFlash('success', 'Site modifié avec succès !');
            return $this->redirectToRoute('gerer_ville');
        }

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