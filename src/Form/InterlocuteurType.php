<?php

namespace App\Form;

use App\Entity\Interlocuteur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InterlocuteurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username')
            ->add('role')
            ->add('nom')
            ->add('prenom')
            ->add('mail')
            ->add('portable')
            ->add('statut')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Interlocuteur::class,
        ]);
    }
}
