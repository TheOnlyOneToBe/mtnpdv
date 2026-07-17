<?php

declare(strict_types=1);

namespace App\Form;

use App\Domain\Entity\PointVente;
use App\Domain\Enum\StatutPointVente;
use App\Domain\Enum\VilleCameroon;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PointVenteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomPdv', TextType::class, [
                'label' => 'Nom du point de vente',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom du point de vente est requis.']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Le nom doit contenir au moins 2 caractères.',
                        'maxMessage' => 'Le nom ne doit pas dépasser 255 caractères.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Kiosque Principal',
                ],
            ])
            ->add('codeRef', TextType::class, [
                'label' => 'Code de référence',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le code de référence est requis.']),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 50,
                        'minMessage' => 'Le code doit contenir au moins 3 caractères.',
                        'maxMessage' => 'Le code ne doit pas dépasser 50 caractères.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: PDV001',
                ],
            ])
            ->add('latitude', NumberType::class, [
                'label' => 'Latitude',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La latitude est requise.']),
                    new Assert\Range([
                        'min' => -90,
                        'max' => 90,
                        'notInRangeMessage' => 'La latitude doit être entre -90 et 90.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.000001',
                ],
                // Coordonnees est un value object immuable : géré manuellement dans le contrôleur
                'mapped' => false,
            ])
            ->add('longitude', NumberType::class, [
                'label' => 'Longitude',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La longitude est requise.']),
                    new Assert\Range([
                        'min' => -180,
                        'max' => 180,
                        'notInRangeMessage' => 'La longitude doit être entre -180 et 180.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.000001',
                ],
                // Coordonnees est un value object immuable : géré manuellement dans le contrôleur
                'mapped' => false,
            ])
            ->add('ville', EnumType::class, [
                'label' => 'Ville',
                'class' => VilleCameroon::class,
                'constraints' => [
                    new Assert\NotNull(['message' => 'Veuillez sélectionner une ville.']),
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Optionnel',
                ],
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le téléphone est requis.']),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '+237 XXX XXX XXX',
                ],
                // Telephone est un value object immuable : géré manuellement dans le contrôleur
                'mapped' => false,
            ])
            ->add('seuilMinCash', NumberType::class, [
                'label' => 'Seuil minimal de cash (FCFA)',
                'required' => false,
                'constraints' => [
                    new Assert\PositiveOrZero(['message' => 'Le seuil doit être positif ou zéro.']),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'step' => '1',
                    'placeholder' => 'Ex: 100000',
                ],
                'mapped' => false,
            ])
            ->add('seuilMinFlotte', NumberType::class, [
                'label' => 'Seuil minimal de flotte (FCFA)',
                'required' => false,
                'constraints' => [
                    new Assert\PositiveOrZero(['message' => 'Le seuil doit être positif ou zéro.']),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'step' => '1',
                    'placeholder' => 'Ex: 50000',
                ],
                'mapped' => false,
            ])
            ->add('statutActuel', EnumType::class, [
                'label' => 'Statut',
                'class' => StatutPointVente::class,
                'constraints' => [
                    new Assert\NotNull(['message' => 'Veuillez sélectionner un statut.']),
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PointVente::class,
        ]);
    }
}
