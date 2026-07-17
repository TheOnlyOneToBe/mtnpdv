<?php

declare(strict_types=1);

namespace App\Form;

use App\Domain\Entity\DemandeVisite;
use App\Domain\Entity\PointVente;
use App\Domain\Entity\Utilisateur;
use App\Domain\Enum\TypeTransaction;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class DemandeVisiteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', EnumType::class, [
                'label' => 'Type de mission',
                'class' => TypeTransaction::class,
                'choice_label' => fn(TypeTransaction $type) => $type->libelle(),
                'choices' => [
                    TypeTransaction::DISTRIBUTION_CASH,
                    TypeTransaction::APPROVISIONNEMENT_FLOTTE,
                ],
                'constraints' => [
                    new Assert\NotNull(['message' => 'Veuillez sélectionner un type de mission.']),
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('montant', MoneyType::class, [
                'label' => 'Montant',
                'currency' => 'XAF',
                'divisor' => 100,
                'constraints' => [
                    new Assert\NotNull(['message' => 'Veuillez saisir un montant.']),
                    new Assert\Positive(['message' => 'Le montant doit être supérieur à 0.']),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0',
                ],
            ])
            ->add('motif', TextType::class, [
                'label' => 'Motif',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez saisir un motif.']),
                    new Assert\Length(['max' => 255]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Motif de la demande',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description (optionnelle)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Détails supplémentaires',
                ],
            ])
        ;

        // Only add pointVente field if the user is admin
        if ($options['is_admin']) {
            $builder
                ->add('pointVente', EntityType::class, [
                    'label' => 'Point de vente',
                    'class' => PointVente::class,
                    'choice_label' => 'nomPdv',
                    'placeholder' => '-- Sélectionner un point de vente --',
                    'constraints' => [
                        new Assert\NotNull(['message' => 'Veuillez sélectionner un point de vente.']),
                    ],
                    'attr' => [
                        'class' => 'form-select',
                    ],
                ])
                ->add('agent', EntityType::class, [
                    'label' => 'Agent (optionnel)',
                    'class' => Utilisateur::class,
                    'choice_label' => fn(Utilisateur $u) => $u->getPrenomUt() . ' ' . $u->getNomUt(),
                    'placeholder' => '-- Sélectionner un agent (optionnel) --',
                    'required' => false,
                    'attr' => [
                        'class' => 'form-select',
                    ],
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'is_admin' => false,
        ]);
    }
}
