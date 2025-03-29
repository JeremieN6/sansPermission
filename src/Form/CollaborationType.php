<?php

namespace App\Form;

use App\Entity\Collaboration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CollaborationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'placeholder' => 'Votre nom',
                    'class' => 'shadow-xs bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 dark:shadow-xs-light',
                ],
                'label_attr' => ['class' => 'block mb-2 text-sm font-medium text-gray-900 dark:text-white'],
            ])
            ->add('companyName', TextType::class, [
                'label' => 'Nom de l\'entreprise',
                'attr' => [
                    'placeholder' => 'Nom de votre entreprise',
                    'class' => 'shadow-xs bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 dark:shadow-xs-light',
                ],
                'label_attr' => ['class' => 'block mb-2 text-sm font-medium text-gray-900 dark:text-white'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'placeholder' => 'Email',
                    'class' => 'shadow-xs bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 dark:shadow-xs-light',
                ],
                'label_attr' => ['class' => 'block mb-2 text-sm font-medium text-gray-900 dark:text-white'],
            ])
            ->add('website', TextType::class, [
                'label' => 'Site Web',
                'attr' => [
                    'placeholder' => 'Site Web',
                    'class' => 'shadow-xs bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 dark:shadow-xs-light',
                ],
                'label_attr' => ['class' => 'block mb-2 text-sm font-medium text-gray-900 dark:text-white'],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'attr' => [
                    'placeholder' => 'Message',
                    'class' => 'block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500',
                ],
                'label_attr' => ['class' => 'block mb-2 text-sm font-medium text-gray-900 dark:text-white'],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Envoyer',
                'attr' => ['class' => 'text-white bg-[#4ADE80] hover:bg-[#22c55e] focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Collaboration::class,
        ]);
    }
} 