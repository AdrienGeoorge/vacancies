<?php

namespace App\Form;

use App\Entity\TripTraveler;
use App\Entity\VariousExpensive;
use App\Repository\TripTravelerRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VariousExpensiveType extends AbstractType
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
                    'placeholder' => 'Nom de la dépense',
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
                    'placeholder' => 'Quelles sont les modalités à connaître ? Où se situe cette activité ?',
                ]
            ])
            ->add('price', NumberType::class, [
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                    'placeholder' => 'Montant de la dépense',
                ]
            ])
            ->add('perPerson', CheckboxType::class, [
                'required' => false,
                'attr' => [
                    'style' => 'width: 1.5em; height: 1.5em;'
                ]
            ])
            ->add('paid', CheckboxType::class, [
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
            'data_class' => VariousExpensive::class,
        ]);
    }
}
