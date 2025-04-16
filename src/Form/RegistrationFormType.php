<?php

namespace App\Form;

use App\Entity\Users;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('nom', TextType::class, [
            'attr' => [
                'class' => "mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 p-2",
            ]
        ])
        ->add('prenom', TextType::class, [
            'attr' => [
                'class' => "mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 p-2",
            ]
        ])
            ->add('email', NumberType::class, [
                'attr' => [
                    'class' => "mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 p-2",
                ]
            ])
            ->add('age', TextType::class, [
                'attr' => [
                    'class' => "mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 p-2",
                ]
            ])
            ->add('pseudo', TextType::class, [
                'attr' => [
                    'class' => "mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 p-2",
                ]
            ])
            ->add('plainPassword', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'class' => 'mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 p-2'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('rgpd', CheckboxType::class, [
                'label' => 'J\'accepte que mes données soient collectées dans le cadre de la loi RGPD.',
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'You should agree to our terms.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Users::class,
        ]);
    }
}
