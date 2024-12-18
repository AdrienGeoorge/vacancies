<?php

namespace App\Form;

use App\Entity\EventType;
use App\Entity\PlanningEvent;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlanningEventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                    'placeholder' => 'Nom de l\'évènement',
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
                    'placeholder' => 'Où se déroule l\'évènement ? Que faut-il savoir ?',
                ]
            ])
            ->add('start', DateTimeType::class, [
                'widget' => 'single_text',
                'input_format' => 'd/m/Y H:i',
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                    'placeholder' => 'Date et heure de début',
                ]
            ])
            ->add('end', DateTimeType::class, [
                'required' => false,
                'widget' => 'single_text',
                'input_format' => 'd/m/Y H:i',
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                    'placeholder' => 'Date et heure de fin',
                ]
            ])
            ->add('timeToGo', IntegerType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                              placeholder-gray-400 text-sm
                              focus:outline-none focus:border-gray-400 focus:bg-white
                              dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                              dark:placeholder-gray-300',
                    'placeholder' => 'Temps en minutes',
                ]
            ])
            ->add('type', EntityType::class, [
                'placeholder' => 'Choisis un type d\'évènement',
                'class' => EventType::class,
                'choice_label' => 'name',
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
            'data_class' => PlanningEvent::class,
        ]);
    }
}
