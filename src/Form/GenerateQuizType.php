<?php

namespace App\Form;

use App\Entity\Episodes;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GenerateQuizType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('episode', EntityType::class, [
                'class' => Episodes::class,
                'choice_label' => 'title',
                'placeholder' => 'Sélectionnez un épisode',
                'label' => 'Épisode',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Générer le quiz',
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
