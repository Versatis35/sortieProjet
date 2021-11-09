<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\PlaceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class AdminController extends AbstractController
{
    /**
     * @Route("/admin/import", name="admin")
     */
    public function index(Request $request, EntityManagerInterface $em, PlaceRepository $repo, UserPasswordHasherInterface $passwordEncoder): Response
    {
        $defaultData = [];
        $form = $this->createFormBuilder($defaultData)
            ->add('file', FileType::class, [
                // unmapped means that this field is not associated to any entity property
                'mapped' => false,
                'label'=>"Fichier d'import",

                // make it optional so you don't have to re-upload the PDF file
                // every time you edit the Product details
                'required' => false,])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // data is an array with "name", "email", and "message" keys

            $file = $form->get('file')->getData();
            $header = NULL;
            $data = array();
            if (($handle = fopen($file->getRealPath(), 'r')) !== FALSE) {

                while (($row = fgetcsv($handle, 1000, ";", '"')) !== FALSE) {

                    if(!$header) {
                        $header = $row;
                    } else {
                        $data[] = array_combine($header, $row);
                    }
                }
            }
            fclose($handle);
            $em = $this->getDoctrine()->getManager();
            foreach($data as $row) {
                $rowPseudo = array_values($row);
                $user = new User();
                $user->setPseudo($rowPseudo[0]);
                $user->setNom($row["nom"]);
                $user->setPrenom($row["prenom"]);
                $user->setEmail($row["email"]);
                $site = $repo->findOneBy([
                    "id"=>$row["site"]
                ]);
                $user->setSite($site);
                $user->setTelephone($row["telephone"]);
                $user->setRoles(["ROLE_USER"]);
                $user->setPassword(
                    $passwordEncoder->hashPassword(
                        $user,
                        $row["password"]
                    )
                );
                $em->persist($user);
            }
            $em->flush();
            $this->addFlash('success','Bien ajouté avec succès');

        }
        return $this->render('admin/importUser.html.twig',
        [
            "uploadform"=>$form->createView()
        ]);
    }
}
