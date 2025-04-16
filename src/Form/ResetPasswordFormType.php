<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ResetPasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('password', PasswordType::class,
        [
            'label' => false,
            'attr' =>[
                'class' => 'form-control mb-0',
                'placeholder' => 'Saissiez votre mot de passe'
            ],
            'constraints' => [
                new NotBlank([
                    'message' => 'Entrez un mot de passe',
                ]),
                new Length([
                    'min' => 6,
                    'minMessage' => 'Votre mot de passe doit être au moins de {{ limit }} caractères',
                    // max length allowed by Symfony for security reasons
                    'max' => 4096,
                ]),
            ],
        ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
