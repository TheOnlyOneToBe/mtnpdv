<?php

declare(strict_types=1);

namespace App\Form;

use App\Domain\Entity\PointVente;
use App\Domain\Entity\Transaction;
use App\Domain\Enum\TypeTransaction;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class VisiteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $pointVentes = $options['pointVentes'] ?? [];

        $builder
            ->add('pointVente', EntityType::class, [
                'label' => 'Point de vente',
                'class' => PointVente::class,
                'choices' => $pointVentes,
                'choice_label' => 'nomPdv',
                'constraints' => [
                    new Assert\NotNull(['message' => 'Veuillez sélectionner un point de vente.']),
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
                'placeholder' => '-- Sélectionner un point de vente --',
            ])
            ->add('type', EnumType::class, [
                'label' => 'Type de visite',
                'class' => TypeTransaction::class,
                'constraints' => [
                    new Assert\NotNull(['message' => 'Veuillez sélectionner un type.']),
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('montant', MoneyType::class, [
                'label' => 'Montant',
                'currency' => 'EUR',
                'divisor' => 100,
                'required' => false,
                'constraints' => [
                    new Assert\PositiveOrZero(['message' => 'Le montant doit être positif.']),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0.00',
                ],
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Observations, notes...',
                ],
            ])
            ->add('photoPreuve', FileType::class, [
                'label' => 'Photo de preuve',
                'required' => false,
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Formats acceptés: JPG, PNG, WebP',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*',
                ],
                'data_class' => null,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer la visite',
                'attr' => [
                    'class' => 'btn btn-success btn-lg',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
            'pointVentes' => [],
        ]);
    }
}
