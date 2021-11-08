<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TripController extends AbstractController
{
    /**
     * @Route("/creationSortie", name="creation_sortie")
     */
    public function creation(): Response
    {
        return $this->render('trip/creation.html.twig', [
            'controller_name' => 'Créer une sortie',
        ]);
    }

    /**
     * @Route("/detail", name="detail_sortie")
     */
    public function afficherLeDetail(): Response
    {
        return $this->render('trip/detail.html.twig', [
            'controller_name' => 'detail d\'une sortie',
        ]);
    }

    /**
     * @Route("/annulationSortie", name="annuler_sortie")
     */
    public function annulerLaSortie(): Response
    {
        return $this->render('trip/annulation.html.twig', [
            'controller_name' => 'annulation d\'une sortie',
        ]);
    }
}
