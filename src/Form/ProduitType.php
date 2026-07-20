<?php

declare(strict_types=1);

namespace App\Form;

use App\Domain\Entity\CategorieProd;
use App\Domain\Entity\Produit;
use App\Domain\Enum\StatutProduit;
use App\Domain\ValueObject\Montant;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use App\Form\DataTransformer\MontantToNumberTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomProd', TextType::class, [
                'label' => 'Nom du produit',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom du produit est requis.']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Le nom doit contenir au moins 2 caractères.',
                        'maxMessage' => 'Le nom ne doit pas dépasser 255 caractères.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Crème hydratante',
                ],
            ])
            ->add('typePro', TextType::class, [
                'label' => 'Type de produit',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le type est requis.']),
                    new Assert\Length([
                        'max' => 50,
                        'maxMessage' => 'Le type ne doit pas dépasser 50 caractères.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: soin, maquillage, ...',
                ],
            ])
            ->add('prixUnitaire', NumberType::class, [
                'label' => 'Prix unitaire (FCFA)',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prix est requis.']),
                    new Assert\PositiveOrZero(['message' => 'Le prix doit être positif ou zéro.']),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'step' => '1',
                    'placeholder' => 'Ex: 2500',
                ],
            ])
            ->add('codeBarre', TextType::class, [
                'label' => 'Code-barres',
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'Le code-barres ne doit pas dépasser 100 caractères.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Optionnel',
                ],
            ])
            ->add('statutProd', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => StatutProduit::cases(),
                'choice_label' => fn (StatutProduit $statut): string => $statut->libelle(),
                'choice_value' => fn (StatutProduit $statut): string => $statut->name,
                'constraints' => [
                    new Assert\NotNull(['message' => 'Veuillez choisir un statut.']),
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('categorie', EntityType::class, [
                'label' => 'Catégorie',
                'class' => CategorieProd::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')->orderBy('c.libelle', 'ASC');
                },
                'choice_label' => 'libelle',
                'placeholder' => 'Sélectionnez une catégorie',
                'required' => false,
                'attr' => [
                    'class' => 'form-select',
                ],
            ]);

        $builder->get('prixUnitaire')->addModelTransformer(new MontantToNumberTransformer());

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
        ]);
    }
}