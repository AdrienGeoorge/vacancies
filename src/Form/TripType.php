<?php

namespace App\Form;

use App\Entity\Trip;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class TripType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                    'placeholder' => 'Nom du voyage',
                ]
            ])
            ->add('countryCode', CountryType::class, [
                'label' => 'Pays du voyage',
                'placeholder' => 'Sélectionnez un pays',
                'required' => true,
                'choice_translation_locale' => 'fr',
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                ],
            ])
            ->add('departureDate', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'input_format' => 'd/m/Y',
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                    'placeholder' => 'Date de départ',
                    'min' => (new \DateTime('yesterday'))->format('Y-m-d')
                ]
            ])
            ->add('returnDate', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'input_format' => 'd/m/Y',
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                    'placeholder' => 'Date de retour',
                    'min' => (new \DateTime('yesterday'))->format('Y-m-d')
                ]
            ])
            ->add('image', FileType::class, [
                'label' => 'Illustres ton voyage avec une image',
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '128M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Votre image ne peut pas être ajouté. Les types autorisés sont les suivants : .jpeg / .png / .gif',
                        'maxSizeMessage' => 'Le fichier est trop volumineux ({{ size }}{{ suffix }}). Le taille maximale autorisée est de {{ limit }}{{ suffix }}.'

                    ])
                ],
                'mapped' => false,
                'required' => !$options['data']->getId()
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trip::class,
        ]);
    }
}
