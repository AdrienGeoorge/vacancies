<?php

namespace App\Form;

use App\Entity\OnSiteExpense;
use App\Entity\TripTraveler;
use App\Repository\TripTravelerRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OnSiteExpenseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                    'placeholder' => 'Nom',
                ]
            ])
            ->add('price', NumberType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                    'placeholder' => 'Prix',
                ]
            ])
            ->add('purchaseDate', DateTimeType::class, [
                'required' => true,
                'widget' => 'single_text',
                'input_format' => 'd/m/Y H:i',
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                    'placeholder' => 'Date de la dépense',
                    'min' => $options['trip']->getDepartureDate()?->format('Y-m-d H:i'),
                    'max' => $options['trip']->getReturnDate()?->setTime(23, 59)->format('Y-m-d H:i')
                ]
            ])
            ->add('payedBy', EntityType::class, [
                'required' => true,
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
            'data_class' => OnSiteExpense::class,
            'trip' => null
        ]);
    }
}
