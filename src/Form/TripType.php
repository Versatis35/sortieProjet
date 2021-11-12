<?php

namespace App\Form;

use App\Entity\Trip;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TripType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom',TextType::class)
            ->add('dateSortie',DateTimeType::class,['date_widget' => 'single_text','time_widget' => 'single_text'])
            ->add('dateLimite',DateType::class,['widget' => 'single_text'])
            ->add('nbPlace',IntegerType::class)
            ->add('duree',IntegerType::class, ['label'=>'Durée (min)'])
            ->add('description',TextareaType::class)
            //->add('motifAnnulation')
            ->add('lieu', null,['choice_label' => 'Nom'])
            //->add('participants')
            //->add('organisateur')
            //->add('etat')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Trip::class,
        ]);
    }
}
