<?php

declare(strict_types=1);

namespace App\Form;

use App\Domain\Entity\Utilisateur;
use App\Domain\Entity\Role;
use App\Domain\Enum\StatutUtilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UtilisateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] ?? false;

        $builder
            ->add('prenomUt', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prénom est requis.']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Le prénom doit contenir au moins 2 caractères.',
                        'maxMessage' => 'Le prénom ne doit pas dépasser 100 caractères.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('nomUt', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom est requis.']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Le nom doit contenir au moins 2 caractères.',
                        'maxMessage' => 'Le nom ne doit pas dépasser 100 caractères.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'email est requis.']),
                    new Assert\Email(['message' => 'L\'email n\'est pas valide.']),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'disabled' => $isEdit,
                ],
                'data_class' => null,
                'mapped' => false,
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
                'data_class' => null,
                'mapped' => false,
            ]);

        if (!$isEdit) {
            $builder->add('motDePasse', PasswordType::class, [
                'label' => 'Mot de passe',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le mot de passe est requis.']),
                    new Assert\Length([
                        'min' => 8,
                        'minMessage' => 'Le mot de passe doit contenir au moins 8 caractères.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'new-password',
                ],
                'mapped' => false,
            ]);
        } else {
            $builder
                ->add('motDePasse', PasswordType::class, [
                    'label' => 'Nouveau mot de passe',
                    'required' => false,
                    'constraints' => [
                        new Assert\Length([
                            'min' => 8,
                            'minMessage' => 'Le mot de passe doit contenir au moins 8 caractères.',
                        ]),
                    ],
                    'attr' => [
                        'class' => 'form-control',
                        'autocomplete' => 'new-password',
                        'placeholder' => 'Laissez vide pour conserver le mot de passe actuel',
                    ],
                    'mapped' => false,
                ])
                ->add('statutUtilisateur', EnumType::class, [
                    'label' => 'Statut',
                    'class' => StatutUtilisateur::class,
                    'constraints' => [
                        new Assert\NotNull(['message' => 'Veuillez sélectionner un statut.']),
                    ],
                    'attr' => [
                        'class' => 'form-select',
                    ],
                ]);
        }

        $builder->add('roles', EntityType::class, [
            'label' => 'Rôles',
            'class' => Role::class,
            // getRoles() renvoie des chaînes (sécurité Symfony) : la collection
            // d'entités Role est exposée par getRolesEntites()
            'property_path' => 'rolesEntites',
            'choice_label' => function (Role $role) {
                return $role->getCodeRole() . ': ' . $role->getLibelleRole();
            },
            'multiple' => true,
            'expanded' => true,
            'attr' => [
                'class' => 'form-check',
            ],
        ]);

        if ($isEdit) {
            $builder->add('photoFile', FileType::class, [
                'label' => 'Photo de profil',
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
                'mapped' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
            'is_edit' => false,
        ]);
    }
}
