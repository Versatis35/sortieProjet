<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Location;
use App\Entity\Place;
use App\Entity\State;
use App\Entity\User;
use App\Repository\CityRepository;
use App\Repository\PlaceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    /**
     * @Route("/api", name="api")
     */
    public function index(): Response
    {
        return $this->render('api/index.html.twig', [
            'controller_name' => 'ApiController',
        ]);
    }

    /**
     * @Route("/upData", name="data")
     */
    public function upData(): Response
    {
        $em = $this->getDoctrine()->getManager();

        $etat = new State();
        $etat->setLibelle('Créée');
        $em->persist($etat);

        $etat = new State();
        $etat->setLibelle('Ouverte');
        $em->persist($etat);

        $etat = new State();
        $etat->setLibelle('Clôturée');
        $em->persist($etat);

        $etat = new State();
        $etat->setLibelle('Activité en cours');
        $em->persist($etat);

        $etat = new State();
        $etat->setLibelle('Annulée');
        $em->persist($etat);

        $place = new Place();
        $place->setNom('ENI Rennes');
        $em->persist($place);

        $place = new Place();
        $place->setNom('ENI Saint Herblain');
        $em->persist($place);

        $place = new Place();
        $place->setNom('ENI Niort');
        $em->persist($place);

        $city = new City();
        $city->setNom('Nantes');
        $city->setCodePostal('44000');
        $city->setPays('France');
        $em->persist($city);

        $city = new City();
        $city->setNom('Anger');
        $city->setCodePostal('49000');
        $city->setPays('France');
        $em->persist($city);

        $city = new City();
        $city->setNom('Cholet');
        $city->setCodePostal('49300');
        $city->setPays('France');
        $em->persist($city);

        $city = new City();
        $city->setNom('Brest');
        $city->setCodePostal('29200');
        $city->setPays('France');
        $em->persist($city);

        $city = new City();
        $city->setNom('Niort');
        $city->setCodePostal('79000');
        $city->setPays('France');
        $em->persist($city);

        $city = new City();
        $city->setNom('Rennes');
        $city->setCodePostal('35000');
        $city->setPays('France');
        $em->persist($city);

        $city = new City();
        $city->setNom('Saint-Herblain');
        $city->setCodePostal('44800');
        $city->setPays('France');
        $em->persist($city);

        $em->flush();

        dd('finish');
    }

    /**
     * @Route("/upRelation", name="relation")
     */
    public function upRelation(CityRepository $cityRepo, PlaceRepository $placeRepo, UserPasswordHasherInterface $passwordEncoder): Response
    {
        $em = $this->getDoctrine()->getManager();

        /*$cit = $cityRepo->find(7);

        $location = new Location();
        $location->setNom('BowlCenter Nantes');
        $location->setRue('151 Rue du Moulin de la Rousselière');
        $location->setVille($cit);
        $location->setLatitude('47.23030');
        $location->setLongitude('-1.63856');
        $em->persist($location);

        $cit = $cityRepo->find(6);

        $location = new Location();
        $location->setNom('RennEscape');
        $location->setRue('35 Rue du Manoir de Servigné');
        $location->setVille($cit);
        $location->setLatitude('48.10056');
        $location->setLongitude('-1.73245');
        $em->persist($location);*/

        $pla = $placeRepo->find(2);

        $user = new User();
        $user->setNom('Tillier');
        $user->setPseudo('SteevT');
        $user->setPrenom('Steeven');
        $user->setEmail('tillier.steeven@campuseni.fr');
        $user->setTelephone('0604020503');
        $user->setPassword($passwordEncoder->hashPassword($user,'azerty123'));
        $user->setRoles(['ROLE_USER','ROLE_ADMIN']);
        $user->setSite($pla);
        $em->persist($user);

        $em->flush();

        dd('finish again');
    }
}
