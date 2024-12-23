<?php

namespace App\Form;

use App\Entity\TripTraveler;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TripTravelerType extends AbstractType
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
                    'placeholder' => 'Nom du voyageur',
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TripTraveler::class,
        ]);
    }
}
