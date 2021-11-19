<?php

namespace App\Controller;

use App\Entity\Location;
use App\Form\LocationType;
use App\Repository\LocationRepository;
use phpDocumentor\Reflection\Types\This;
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
    public function index(TripRepository $tripRepo, PlaceRepository $placeRepo): Response
    {
        if($this->container->get('security.authorization_checker')->isGranted('ROLE_USER') == false) {
            return $this->redirectToRoute('se_connecter');
        }

        $trips = $tripRepo->findAll();
        $authUser = $this->getUser();
        $places = $placeRepo->findAll();

        return $this->render('trip/index.html.twig', [
            'trips' => $trips,
            'user' => $authUser,
            'places' => $places,
        ]);
    }

    /**
     * @Route("/creationSortie", name="creation_sortie")
     * Création ou modification d'une sortie
     */
    public function creation(Request $request, StateRepository $stateRepo, PlaceRepository $placeRepo, LocationRepository $locRepo, CityRepository $repoCity): Response
    {
        //Récupération de l'utilisateur
        $authUser = $this->getUser();
        //Information sur les lieux de sortie pour afficher la map
        $location = $locRepo->find(1);
        $latitude = $location->getLatitude();
        $longitude = $location->getLongitude();
        $city = $location->getVille();
        $orga = $authUser->getSite();
        $error = "";
        $trip = new Trip();
        $locForm = new Location();
        //Formulaire d'ajout de lieu
        $formLocation = $this->createForm(LocationType::class,$locForm);
        //Formulaire d'ajout de sortie
        $formTrip = $this->createForm(TripType::class,$trip);
        //récupération des données des formulaires
        $formTrip->handleRequest($request);
        $formLocation->handleRequest($request);
        //Si le formulaire est envoyé et passe la validation
        if ($formTrip->isSubmitted() && $formTrip->isValid()){
            $dateDebut = $formTrip->get('dateSortie');
            $dateFin = $formTrip->get('dateLimite');
            //Gestion des erreurs de date
            if($dateFin->getData() > $dateDebut->getData()) {
                $error = [
                    'messageKey' => -1,
                    'messageData' => "La date de fin ne peut être inférieure à la date de début"
                ];
            }
            if($error == "") {
                //On enregistrer la sortie
                $trip->setOrganisateur($authUser);
                $state = $stateRepo->find($request->get('btn'));
                $trip->setEtat($state);
                $em = $this->getDoctrine()->getManager();
                $em->persist($trip);
                $em->flush();
                return $this->redirectToRoute('home');
            }
        }
        //Si le formulaire du lieu est validé on l'enregistre
        if($formLocation->isSubmitted()) {
            $em = $this->getDoctrine()->getManager();
            $locForm->setVille($repoCity->findOneBy(['id'=>$request->request->get("location")['ville']]));
            $em->persist($locForm);
            $em->flush();
            $latitude = $locForm->getLatitude();
            $longitude = $locForm->getLongitude();
            $location = $locForm;
            $locForm = new Location();
            $formLocation = $this->createForm(LocationType::class, $locForm);
            $trip->setLieu($location);
            $formTrip = $this->createForm(TripType::class,$trip);
        }
        //Envoie a la vue de toute les données nécéssaire
        return $this->render('trip/creation.html.twig', [
            'trip' => $trip,
            'user' => $authUser,
            'location' => $location,
            'city' => $city,
            'orga' => $orga,
            'formTrip' => $formTrip->createView(),
            'formLocation' => $formLocation->createView(),
            'url' =>  $this->getParameter('kernel.project_dir'),
            'error' => $error,
            'latitude'=> $latitude,
            'longitude'=> $longitude
        ]);
    }

    /**
     * @Route("/publierSortie/{id}", name="publier_sortie")
     */
    public function publierSortie(Trip $trip, StateRepository $stateRepo): Response
    {
        $em = $this->getDoctrine()->getManager();
        $state = $stateRepo->find(2);
        $trip->setEtat($state);
        $em->persist($trip);
        $em->flush();

        $this->addFlash('success', "La sortie a été publiée avec succès");
        return $this->redirectToRoute('home');
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
    public function modifier(Trip $trip, Request $request, StateRepository $stateRepo, CityRepository $repoCity, LocationRepository $locRepo): Response
    {
        $orga = $trip->getOrganisateur()->getSite();
        $location = $trip->getLieu();
        $latitude = $location->getLatitude();
        $longitude = $location->getLongitude();
        $city = $location->getVille();
        $authUser = $this->getUser();

        $locForm = new Location();
        $formLocation = $this->createForm(LocationType::class,$locForm);
        $formTrip = $this->createForm(TripType::class,$trip);
        $formTrip->handleRequest($request);
        $formLocation->handleRequest($request);

        if ($formTrip->isSubmitted()){
            $state = $stateRepo->find($request->get('btn'));
            $trip->setEtat($state);

            $em = $this->getDoctrine()->getManager();
            //$em->persist($trip);
            $em->flush();

            $this->addFlash('success', "La sortie a été modifiée avec succès");
            return $this->redirectToRoute('home');
        }

        if($formLocation->isSubmitted()) {
            $em = $this->getDoctrine()->getManager();
            $locForm->setVille($repoCity->findOneBy(['id'=>$request->request->get("location")['ville']]));
            $em->persist($locForm);
            $em->flush();
            $latitude = $locForm->getLatitude();
            $longitude = $locForm->getLongitude();
            $location = $locForm;
            $locForm = new Location();
            $formLocation = $this->createForm(LocationType::class, $locForm);
            $trip->setLieu($location);
            $formTrip = $this->createForm(TripType::class,$trip);
        }
        $error = "";
        return $this->render('trip/creation.html.twig', [
            'trip' => $trip,
            'orga' => $orga,
            'location' => $location,
            'city' => $city,
            'user' => $authUser,
            'latitude'=> $latitude,
            'longitude'=> $longitude,
            'error' => $error,
            'formLocation' => $formLocation->createView(),
            'url' =>  $this->getParameter('kernel.project_dir'),
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

        $this->addFlash('success', "La sortie a été annulée avec succès");
        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/inscriptionSortie/{id}", name="inscriptionSortie")
     */
    public function inscriptionSortie(Trip $trip, StateRepository $stateRepo): Response
    {
        $user = $this->getUser();
        $trip->addParticipant($user);

        $em = $this->getDoctrine()->getManager();
        $em->persist($trip);
        $em->flush();

        $this->addFlash('success', "Vous êtes inscrit a la sortie");
        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/desistement/{id}", name="desistement")
     */
    public function desisterSortie(Trip $trip, StateRepository $stateRepo): Response
    {
        $user = $this->getUser();
        $trip->removeParticipant($user);

        $em = $this->getDoctrine()->getManager();
        $em->persist($trip);
        $em->flush();

        $this->addFlash('success', "Vous êtes désinscrit de la sortie");
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

    /**
     * @Route("/axiosTable/", name="majTrip" ,methods={"POST"})
     */
    public function updateTable(Request $request, TripRepository $tripRepo, PlaceRepository $siteRepo): Response
    {
        $result = $tripRepo->createQueryBuilder('o');
        $result->leftJoin('o.participants', 'participant');
        $result->leftJoin('o.etat', 'etat');
        $result->leftJoin('o.organisateur', 'organisateur');
        $result->select(["o.id","o.nom","o.dateSortie","o.dateLimite","etat.libelle","o.nbPlace","COUNT(participant.id) AS nb_participants","organisateur.pseudo as Organisateur"]);
        $result->groupBy('o.id');
        $json =$request->getContent();
        $obj = json_decode($json);
        $obj = $obj->data;
        $userActual = $this->getUser();
        $site = $obj->site;
        $searchWord = $obj->searchWord;
        $dateStart = $obj->dateStart;
        $dateEnd = $obj->dateEnd;
        $checkOrga = $obj->checkOrga;
        $checkInscrit = $obj->checkInscrit;
        $checkNot = $obj->checkNot;
        $checkLast = $obj->checkLast;
        $checkLast = $obj->checkLast;

        $firstWhereSet = false;

        // Si le site = 0 : Tous les sites
        if($site !== "0") {
            $result->innerJoin("o.organisateur",'user');
            $result->where("user.site = :site");
            $result->setParameter("site",$site);
            $firstWhereSet = true;
        }
        if($searchWord !== "") {
            if($firstWhereSet) {
                $result->andWhere("o.nom LIKE :nom");
                $result->setParameter("nom", "%".$searchWord."%");
            } else {
                $result->where("o.nom LIKE :nom");
                $result->setParameter("nom", "%".$searchWord."%");
                $firstWhereSet = true;
            }
        }

        if($dateStart !== "") {
            if($firstWhereSet) {
                $result->andWhere("o.dateSortie >= :dateDebut");
                $result->setParameter("dateDebut", $dateStart);
            } else {
                $result->where("o.dateSortie >= :dateDebut");
                $result->setParameter("dateDebut", $dateStart);
                $firstWhereSet = true;
            }
        }

        if($dateEnd !== "") {
            if($firstWhereSet) {
                $result->andWhere("o.dateSortie <= :dateLimite");
                $result->setParameter("dateLimite", $dateEnd);
            } else {
                $result->where("o.dateSortie <= :dateLimite");
                $result->setParameter("dateLimite", $dateEnd);
                $firstWhereSet = true;
            }
        }

        if($checkOrga == true) {
            if($firstWhereSet) {
                $result->andWhere("o.organisateur = :organisateur");
                $result->setParameter("organisateur", $userActual);
            } else {
                $result->where("o.organisateur = :organisateur");
                $result->setParameter("organisateur", $userActual);
                $firstWhereSet = true;
            }
        }

        if($checkInscrit == true) {
            if($firstWhereSet) {
                $result->andWhere(":user MEMBER OF o.participants");
                $result->setParameter("user", $userActual);
            } else {
                $result->where(":user MEMBER OF o.participants");
                $result->setParameter("user", $userActual);
                $firstWhereSet = true;
            }
        }

        if($checkNot == true) {
            if($firstWhereSet) {
                $result->andWhere(":user NOT MEMBER OF o.participants");
                $result->setParameter("user", $userActual);
            } else {
                $result->where(":user NOT MEMBER OF o.participants");
                $result->setParameter("user", $userActual);
                $firstWhereSet = true;
            }
        }
        $dateNow =  new \DateTime;
        if($checkLast == true) {
            if($firstWhereSet) {
                $result->andWhere("o.dateSortie < :dateJour");
                $result->setParameter("dateJour", $dateNow);
            } else {
                $result->where("o.dateSortie < :dateJour");
                $result->setParameter("dateJour", $dateNow);
                $firstWhereSet = true;
            }
        }
        $tabTrip = $result->getQuery()->getResult();
        foreach($tabTrip as $key => $trip) {

            $tripSearch = $tripRepo->findOneBy(["id"=>$trip["id"]]);
            $trip["idOrganisateur"] = $tripSearch->getOrganisateur()->getId();
            $trip["detailSortie"] = true;

            if(($trip["Organisateur"] == $userActual->getPseudo() && $trip["libelle"] == "Créée")) {
                $trip["editSortie"] = true;
                $trip["publierSortie"] = true;
            } else {
                $trip["editSortie"] = false;
                $trip["publierSortie"] = false;
            }

            if($tripSearch->getNbPlace()>$tripSearch->getParticipants()->count() && $trip["Organisateur"] != $userActual->getPseudo() && !in_array($userActual, $tripSearch->getParticipants()->toArray())) {
                if($trip["libelle"] == "Ouverte" || $trip["libelle"] == "Activité en cours") {
                    $trip["inscription"] = true;
                } else {
                    $trip["inscription"] = false;
                }
            } else {
                $trip["inscription"] = false;
            }

            if($trip["Organisateur"] != $userActual->getPseudo() && in_array($userActual, $tripSearch->getParticipants()->toArray())) {
                if($trip["libelle"] == "Ouverte" || $trip["libelle"] == "Activité en cours") {
                    $trip["desistement"] = true;
                } else {
                    $trip["desistement"] = false;
                }
                $trip["isInscrit"] = true;
            } else {
                $trip["desistement"] = false;
                $trip["isInscrit"] = false;
            }

            if($trip["Organisateur"] == $userActual->getPseudo() || in_array("ROLE_ADMIN",$userActual->getRoles())) {
                if($trip["libelle"] == "Créée" || $trip["libelle"] == "Ouverte" || in_array("ROLE_ADMIN",$userActual->getRoles())) {
                    $trip["annulerSortie"] = true;
                } else {
                    $trip["annulerSortie"] = false;
                }
            } else {
                $trip["annulerSortie"] = false;
            }

            $tabTrip[$key] = $trip;
        }

        return $this->json($tabTrip);
    }
}
