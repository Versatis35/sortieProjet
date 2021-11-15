<?php

namespace App\Controller;

use App\Entity\Location;
use App\Form\LocationType;
use App\Repository\LocationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Entity\Trip;
use App\Entity\User;
use App\Entity\City;
use App\Entity\Place;
use App\Form\TripType;
use App\Repository\PlaceRepository;
use App\Repository\StateRepository;
use App\Repository\UserRepository;
use App\Repository\CityRepository;
use App\Repository\TripRepository;
use Doctrine\ORM\EntityManagerInterface;

class TripController extends AbstractController
{
    /**
     * @Route("/", name="home")
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
    public function creation(Request $request, StateRepository $stateRepo, PlaceRepository $placeRepo, LocationRepository $locRepo): Response
    {
        $authUser = $this->getUser();
        $location = $locRepo->find(1);
        $city = $location->getVille();
        $orga = $authUser->getSite();
        $error = "";

        $trip = new Trip();
        $locForm = new Location();
        $formLocation = $this->createForm(LocationType::class,$locForm);
        $formTrip = $this->createForm(TripType::class,$trip);
        $formTrip->handleRequest($request);
        $formLocation->handleRequest($request);
        if ($formTrip->isSubmitted() && $formTrip->isValid()){
            $dateDebut = $formTrip->get('dateSortie');
            $dateFin = $formTrip->get('dateLimite');
            if($dateFin->getData() < $dateDebut->getData()) {
                $error = [
                    'messageKey' => -1,
                    'messageData' => "La date de fin ne peut être inférieure à la date de début"
                ];
            }
            if($error == "") {
                $trip->setOrganisateur($authUser);
                $state = $stateRepo->find($request->get('btn'));
                $trip->setEtat($state);

                $em = $this->getDoctrine()->getManager();
                $em->persist($trip);
                $em->flush();

                return $this->redirectToRoute('home');
            }
        }

        if($formLocation->isSubmitted() && $formLocation->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($locForm);
            $em->flush();
            $location = $locForm;
            $locForm = new Location();
            $formLocation = $this->createForm(LocationType::class, $locForm);
            $trip->setLieu($location);
            $formTrip = $this->createForm(TripType::class,$trip);
        }

        return $this->render('trip/creation.html.twig', [
            'trip' => $trip,
            'user' => $authUser,
            'location' => $location,
            'city' => $city,
            'orga' => $orga,
            'formTrip' => $formTrip->createView(),
            'formLocation' => $formLocation->createView(),
            'url' =>  $this->getParameter('kernel.project_dir'),
            'error' => $error
        ]);
    }

    /**
     * @Route("/detailSortie/{id}", name="detail_sortie")
     */
    public function afficherLeDetail(Trip $trip): Response
    {
        $orga = $trip->getOrganisateur();
        $dateSortie = $trip->getDateSortie()->format('Y-m-d');
        $dateLimite = $trip->getDateLimite()->format('Y-m-d');
        $lieu = $trip->getLieu();
        $villeOrg = $orga->getSite()->getNom();
        $users = $trip->getParticipants();

        return $this->render('trip/detail.html.twig', [
            'trip' => $trip,
            'orga' => $orga,
            'villeOrg' => $villeOrg,
            'lieu' => $lieu,
            'users' => $users,
            'dateLimite' => $dateLimite,
            'dateSortie' => $dateSortie,
        ]);
    }

    /**
     * @Route("/editSortie/{id}", name="edit_sortie")
     */
    public function modifier(Trip $trip, Request $request, StateRepository $stateRepo, LocationRepository $locRepo): Response
    {
        $orga = $trip->getOrganisateur()->getSite();
        $location = $trip->getLieu();
        $city = $location->getVille();
        $authUser = $this->getUser();

        $formTrip = $this->createForm(TripType::class,$trip);
        $formTrip->handleRequest($request);

        if ($formTrip->isSubmitted()){
            $state = $stateRepo->find($request->get('btn'));
            $trip->setEtat($state);

            $em = $this->getDoctrine()->getManager();
            //$em->persist($trip);
            $em->flush();

            return $this->redirectToRoute('home');
        }

        return $this->render('trip/creation.html.twig', [
            'trip' => $trip,
            'orga' => $orga,
            'location' => $location,
            'city' => $city,
            'user' => $authUser,
            'formTrip' => $formTrip->createView(),
        ]);
    }

    /**
     * @Route("/annulationSortie/{id}", name="annuler_sortie")
     */
    public function pageAnnulation(Trip $trip): Response
    {
        $dateSortie = $trip->getDateSortie()->format('Y-m-d');
        $lieu = $trip->getLieu()->getNom();
        $orga = $trip->getOrganisateur()->getSite()->getNom();

        return $this->render('trip/annulation.html.twig', [
            'trip' => $trip,
            'dateSortie' => $dateSortie,
            'lieu' => $lieu,
            'orga' => $orga,
        ]);
    }

    /**
     * @Route("/annulation/{id}", name="annulation")
     */
    public function annulerLaSortie(Trip $trip, Request $request, StateRepository $stateRepo): Response
    {
        $state = $stateRepo->find(5);
        $trip->setMotifAnnulation($request->get('motif'));
        $trip->setEtat($state);

        $em = $this->getDoctrine()->getManager();
        $em->persist($trip);
        $em->flush();

        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/axiosLocation/{id}", name="location")
     */
    public function addLocation(Location $location): Response
    {
        $city = $location->getVille();

        return $this->render('trip/addCoordonnees.html.twig',[
            'location' => $location,
            'city' => $city,
        ]);
    }
}
