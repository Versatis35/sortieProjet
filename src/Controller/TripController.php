<?php

namespace App\Controller;

use App\Form\TripType;
use App\Repository\PlaceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Entity\Trip;
use App\Entity\User;
use App\Entity\City;
use App\Repository\UserRepository;
use App\Repository\CityRepository;
use App\Repository\TripRepository;
use App\Form\CocktailType;
use Doctrine\ORM\EntityManagerInterface;

class TripController extends AbstractController
{
    /**
     * @Route("/home", name="home")
     */
    public function index(TripRepository $tripRepo, UserRepository $userRepo): Response
    {
        if($this->container->get('security.authorization_checker')->isGranted('ROLE_USER') == false) {
            return $this->redirectToRoute('se_connecter');
        }

        $trips = $tripRepo->findAll();
        $authUser = $this->getUser();

        return $this->render('trip/index.html.twig', [
            'trips' => $trips,
            'user' => $authUser,
        ]);
    }

    /**
     * @Route("/creationSortie", name="creation_sortie")
     */
    public function creation(Request $request, PlaceRepository $placeRepo, CityRepository $cityRepo): Response
    {
        $authUser = $this->getUser();
        $places = $placeRepo->findAll();
        $cities = $cityRepo->findAll();
        $newTrip = new Trip();

        $formTrip = $this->createForm(TripType::class,$newTrip);
        $formTrip->handleRequest($request);

        if ($formTrip->isSubmitted()){
            $place = $placeRepo->find($request->get('place'));
            $newTrip->setLieu($places);

            $em = $this->getDoctrine()->getManager();
            $em->persist($newTrip);
            $em->flush();

            return $this->redirectToRoute('home');
        }

        return $this->render('trip/creation.html.twig', [
            'trip' => $newTrip,
            'user' => $authUser,
            'places' => $places,
            'cities' => $cities,
            'formTrip' => $formTrip->createView(),
        ]);
    }

    /**
     * @Route("/detail/{id}", name="detail_sortie")
     */
    public function afficherLeDetail(Trip $trip): Response
    {
        $users = $trip->getParticipants();

        return $this->render('trip/detail.html.twig', [
            'trip' => $trip,
            'users' => $users,
        ]);
    }

    /**
     * @Route("/edit/{id}", name="edit_sortie")
     */
    public function modifier(Request $request, Trip $trip, PlaceRepository $placeRepo, CityRepository $cityRepo): Response
    {
        $authUser = $this->getUser();
        $places = $placeRepo->findAll();
        $cities = $cityRepo->findAll();

        $formTrip = $this->createForm(TripType::class,$trip);
        $formTrip->handleRequest($request);

        if ($formTrip->isSubmitted()){
            $place = $placeRepo->find($request->get('place'));
            $trip->setLieu($place);

            $em = $this->getDoctrine()->getManager();
            //$em->persist($trip);
            $em->flush();

            return $this->redirectToRoute('home');
        }

        return $this->render('trip/creation.html.twig', [
            'trip' => $trip,
            'user' => $authUser,
            'places' => $places,
            'cities' => $cities,
            'formTrip' => $formTrip->createView(),
        ]);
    }

    /**
     * @Route("/annulationSortie/{id}", name="annuler_sortie")
     */
    public function annulerLaSortie(Trip $trip, Request $request): Response
    {
        $trip->setMotifAnnulation($request->get('motif'));

        $em = $this->getDoctrine()->getManager();
        $em->persist($trip);
        $em->flush();

        return $this->render('trip/annulation.html.twig', [
            'controller_name' => 'annulation d\'une sortie',
        ]);
    }
}
