<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PasswordResetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'required' => true,
                'first_options' => [
                    'attr' => [
                        'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                                          placeholder-gray-400 text-sm
                                          focus:outline-none focus:border-gray-400 focus:bg-white
                                          dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                                          dark:placeholder-gray-300',
                        'placeholder' => 'Nouveau mot de passe',
                        'minlength' => 6,
                    ]
                ],
                'second_options' => [
                    'attr' => [
                        'class' => 'w-full px-8 py-4 rounded-2xl font-medium bg-gray-100 border border-gray-200
                                          placeholder-gray-400 text-sm
                                          focus:outline-none focus:border-gray-400 focus:bg-white mt-3
                                          dark:bg-transparent dark:border-gray-300 dark:focus:bg-transparent
                                          dark:placeholder-gray-300',
                        'placeholder' => 'Répétez-le',
                        'minlength' => 6,
                    ]
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'POST',
        ]);
    }
}
