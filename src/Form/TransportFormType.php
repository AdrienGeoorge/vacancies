<?php

namespace App\Form;

use App\Entity\Transport;
use App\Entity\TransportType;
use App\Entity\TripTraveler;
use App\Repository\TripTravelerRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransportFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('departure', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                    'placeholder' => 'Lieu de départ',
                ]
            ])
            ->add('destination', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                    'placeholder' => 'Lieu d\'arrivée',
                ]
            ])
            ->add('departureDate', DateTimeType::class, [
                'required' => false,
                'widget' => 'single_text',
                'input_format' => 'd/m/Y H:i',
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                    'placeholder' => 'Date de départ',
                    'min' => $options['trip']->getDepartureDate()?->format('Y-m-d H:i'),
                    'max' => $options['trip']->getReturnDate()?->setTime(23,59)->format('Y-m-d H:i')
                ]
            ])
            ->add('arrivalDate', DateTimeType::class, [
                'required' => false,
                'widget' => 'single_text',
                'input_format' => 'd/m/Y H:i',
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                    'placeholder' => 'Date d\'arrivée',
                    'min' => $options['trip']->getDepartureDate()?->format('Y-m-d H:i'),
                    'max' => $options['trip']->getReturnDate()?->setTime(23,59)->format('Y-m-d H:i')
                ]
            ])
            ->add('company', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                    'placeholder' => 'Compagnie de transport',
                ]
            ])
            ->add('subscriptionDuration', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                    'placeholder' => 'Durée d\'abonnement',
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
                    'placeholder' => 'Décris les modalités de ce moyen de transport (bagages compris, nourriture...)',
                ]
            ])
            ->add('price', NumberType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                    'placeholder' => 'Prix du moyen de transport',
                ]
            ])
            ->add('perPerson', CheckboxType::class, [
                'required' => false,
                'attr' => [
                    'style' => 'width: 1.5em; height: 1.5em;'
                ]
            ])
            ->add('estimatedToll', NumberType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                    'placeholder' => 'Prix du péage',
                ]
            ])
            ->add('estimatedGasoline', NumberType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                    'placeholder' => 'Prix du carburant',
                ]
            ])
            ->add('paid', CheckboxType::class, [
                'required' => false,
                'attr' => [
                    'style' => 'width: 1.5em; height: 1.5em;'
                ]
            ])
            ->add('type', EntityType::class, [
                'placeholder' => 'Choisis un type de transport',
                'class' => TransportType::class,
                'choice_label' => 'name',
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
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
            'data_class' => Transport::class,
            'trip' => null
        ]);
    }
}
