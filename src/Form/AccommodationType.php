<?php

namespace App\Form;

use App\Entity\Accommodation;
use App\Entity\TransportType;
use App\Entity\TripTraveler;
use App\Repository\TripTravelerRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccommodationType extends AbstractType
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
                    'placeholder' => 'Nom du logement',
                ]
            ])
            ->add('address', TextType::class, [
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                    'placeholder' => 'Adresse du logement',
                ]
            ])
            ->add('zipCode', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                    'placeholder' => 'Code postal',
                ]
            ])
            ->add('city', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                    'placeholder' => 'Ville',
                ]
            ])
            ->add('country', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                    'placeholder' => 'Pays',
                ]
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                    'placeholder' => 'La pension est-elle comprise ? Y a-t-il des choses à savoir avant le départ ?',
                ]
            ])
            ->add('price', NumberType::class, [
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                    'placeholder' => 'Prix du logement',
                ]
            ])
            ->add('deposit', NumberType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                    'placeholder' => 'Dépôt de garantie ou caution',
                ]
            ])
            ->add('arrivalDate', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'input_format' => 'd/m/Y',
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                    'placeholder' => 'Date d\'arrivée',
                ]
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
                ]
            ])
            ->add('additionalExpensive', CollectionType::class, [
                'entry_type' => AccommodationAdditionalType::class,
                'entry_options' => ['label' => false],
                'by_reference' => false,
                'allow_add' => true,
                'allow_delete' => true,
            ])
            ->add('booked', CheckboxType::class, [
                'required' => false,
                'attr' => [
                    'style' => 'width: 1.5em; height: 1.5em;'
                ]
            ])
            ->add('payedBy', EntityType::class, [
                'required' => false,
                'placeholder' => 'Choisis le voyageur',
                'class' => TripTraveler::class,
                'choice_label' => 'name',
                'query_builder' => function (TripTravelerRepository $er) use ($options): QueryBuilder {
                    return $er->createQueryBuilder('t')
                        ->where('t.trip = :trip')
                        ->setParameter('trip', $options['data']->getTrip())
                        ->orderBy('t.id', 'ASC');
                },
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Accommodation::class,
        ]);
    }
}
