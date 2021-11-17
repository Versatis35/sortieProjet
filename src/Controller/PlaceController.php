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
            return $this->redirectToRoute('gerer_site');
        }

        return $this->render('admin/modificationSite.html.twig', [
            'modifyPlaceForm' => $modifySiteForm->createView(),
            'id' => $id
        ]);
    }
}