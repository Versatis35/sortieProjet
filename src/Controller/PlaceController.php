<?php

namespace App\Controller;

use App\Entity\Place;
use App\Entity\User;
use App\Form\AddPlaceType;
use App\Form\ModifyPlaceType;
use App\Form\UserType;
use App\Repository\PlaceRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\Types\TextType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class PlaceController extends AbstractController
{
    /**
     * @Route("/admin/site/gerer", name="gerer_site")
     */
    public function gererSite(PlaceRepository $placeRepository, Request $request, EntityManagerInterface $em): Response
    {
        // Appel des données de l'entité "Site"
        $places = $placeRepository->findAll();

        // Ajouter un site
        $namePlace = new Place();
        $addPlaceForm = $this->createForm(AddPlaceType::class, $namePlace);
        $addPlaceForm->handleRequest($request);

        if($addPlaceForm->isSubmitted())
        {
            $em->persist($namePlace);
            $em->flush();
            $this->addFlash('success', 'Site "'.$namePlace->getNom() .'" ajouté avec succès !');
            return $this->redirectToRoute('gerer_site');
        }

        return $this->render('admin/gererSite.html.twig', [
            "places" => $places,
            'addPlaceForm' => $addPlaceForm->createView()
        ]);
    }

    /**
     * @Route("/admin/site/gerer/{id}", name="delete_site")
     */
    public function deletePlace(EntityManagerInterface $em, int $id): Response
    {
        $identifiant = $em->getRepository(Place::class)->find($id);
        $em->remove($identifiant);
        $em->flush();
        $this->addFlash("success", 'Site supprimé avec succès !');
        return $this->redirectToRoute('gerer_site');
    }

    /**
     * @Route("/admin/site/modify/{id}", name="modify_site")
     */
    public function displayForm(EntityManagerInterface $em, int $id, Request $request): Response
    {
        $identifiant = $em->getRepository(Place::class)->find($id);
        $modifySiteForm = $this->createForm(ModifyPlaceType::class, $identifiant);
        $modifySiteForm->handleRequest($request);

        if ($modifySiteForm->isSubmitted())
        {
            $em->flush();
            $this->addFlash('success', 'Site modifié avec succès !');
            return $this->redirectToRoute('gerer_site');
        }

        return $this->render('admin/modificationSite.html.twig', [
            'modifyPlaceForm' => $modifySiteForm->createView(),
            'id' => $id
        ]);
    }

    /**
     * @Route("/site/filtre", name="filtre_site", methods={"POST"})
     */
    public function filterPlace(Request $request, PlaceRepository $placeRepo): Response
    {
        // Requête pour récupérer les données nécessaires
        $result = $placeRepo->createQueryBuilder('p');

        // Le json récupère le résultat de la requête
        $result->select(["p.id","p.nom"]);

        // Le json récupère le résultat de la requête
        $json = $request->getContent();

        // Récupère la chaine encodée en JSON et la convertit en variable PHP que l'on stocke dans la variable $obj
        $obj = json_decode($json);

        // La variable $searchWord récupère le résultat du JSON
        $searchWord = $obj->searchWord;

        // Si le résultat n'est pas vide
        if($searchWord !== "")
        {
            // On récupère la/les valeurs
            $result->where("p.nom LIKE :nom");
            $result->setParameter("nom", "%".$searchWord."%");
        }

        // $tabCity contient le résultat (la/les valeurs)
        $tabPlace = $result->getQuery()->getResult();

        // Retourne le résultat
        return $this->json($tabPlace);
    }
}