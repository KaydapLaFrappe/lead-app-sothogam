<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Interlocuteur;
use App\Entity\Lead;
use App\Entity\Operation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class LeadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // --- Origine & Statut ---
            ->add('source', ChoiceType::class, [
                'choices' => [
                    'Sothoferm' => 'Sothoferm',
                    'Lumis' => 'Lumis',
                    'Bati' => 'Bati'
                ],
            ])
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    '0' => 'Non Traité',
                    '1' => 'Orienté vers un client',
                    '4' => 'Fermé',
                    'Archivé' => 'Archivé',
                    'direct' => 'Traité en direct'

                ],
            ])
            ->add('dateCreation', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => false,
            ])

            // --- Informations Demandeur ---
            ->add('nom', TextType::class, ['required' => false])
            ->add('prenom', TextType::class, ['required' => false])
            ->add('nomSociete', TextType::class, ['required' => false])
            ->add('mail', EmailType::class, ['required' => false])
            ->add('tel', TextType::class, ['required' => false])
            ->add('activiteDemandeur', TextType::class, ['required' => false])
           ->add('categorieDemandeur', ChoiceType::class, [
                'label' => 'Catégorie',
                'required' => false,
                'choices'  => [
                    'Particulier'   => 1,
                    'Professionnel' => 0,
                ],
                'placeholder' => '--- Choisir une catégorie ---', 
                'attr' => ['class' => 'form-control'],
            ])

            // --- Localisation ---
            ->add('adresseLigne1', TextType::class, ['required' => false])
            ->add('adresseCP', TextType::class, ['required' => false])
            ->add('adresseVille', TextType::class, ['required' => false])

            // --- Détails & Suivi ---
            ->add('message', TextareaType::class, ['required' => false])
            ->add('dateRelance', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('dateReponse', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('envoyeSe', IntegerType::class, [
                'required' => false,
                'label' => 'Envoyé SE (0/1)'
            ])
            ->add('numeroGenere', TextType::class, [
                'attr' => ['readonly' => true],
                'required' => false,
            ])

            // --- Relations ---
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => 'nom',
                'placeholder' => 'Choisir un client',
                'required' => false,
            ])
            ->add('interlocuteur', EntityType::class, [
                'class' => Interlocuteur::class,
                'choice_label' => function(Interlocuteur $i) {
                    return sprintf('[%03d] %s', $i->getId(), $i->getUsername());
                },
                'placeholder' => 'Choisir un commercial',
                'required' => false,
            ])
            ->add('operation', EntityType::class, [
                'class' => Operation::class,
                'choice_label' => 'nom',
                'placeholder' => 'Choisir une opération',
                'required' => false,
            ]);
        
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lead::class,
        ]);
    }
}