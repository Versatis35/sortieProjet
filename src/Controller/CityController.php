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
        return $this->redirectToRoute('gerer_ville');
    }

    /**
     * @Route("/admin/ville/modify/{id}", name="modify_city")
     */
    public function modifyCity(EntityManagerInterface $em, int $id, Request $request)
    {
        $identifiant = $em->getRepository(City::class)->find($id);
        $modifyCityForm = $this->createForm(ModifyCityType::class, $identifiant);
        $modifyCityForm->handleRequest($request);

        if($modifyCityForm->isSubmitted())
        {
            $em->flush();
            return $this->redirectToRoute('gerer_ville');
        }

        return $this->render('admin/modificationVille.html.twig', [
            'modifyCityForm' => $modifyCityForm->createView(),
            'id' => $id
        ]);
    }

}