<?php

declare(strict_types=1);

namespace App\Form;

use App\Domain\Entity\Utilisateur;
use App\Domain\Entity\Role;
use App\Domain\Enum\StatutUtilisateur;
use App\Form\UtilisateurCreateDTO;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType as FormTextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\Options;
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
            ->add('email', FormTextType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control',
                    'type' => 'email',
                    'disabled' => $isEdit,
                ],
                // En édition, le champ n'est pas mappé (value object)
                'mapped' => !$isEdit,
            ])
            ->add('telephone', FormTextType::class, [
                            'label' => 'Téléphone',
                            'required' => true,
                            'constraints' => [
                                new Assert\NotBlank(['message' => 'Le téléphone est requis.']),
                                new Assert\Regex([
                                    'pattern' => '/^\+?[0-9\s.\-()]{8,20}$/',
                                    'message' => 'Le numéro de téléphone n\'est pas valide.',
                                ]),
                            ],
                            'attr' => [
                                'class' => 'form-control',
                                'placeholder' => '+237 XXX XXX XXX',
                            ],
                            // En édition, le champ n'est pas mappé (value object)
                            'mapped' => !$isEdit,
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
                return $role->getCodeRole() . ': ' . $role->getLibelle();
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

            // Pré-remplir le champ téléphone avec la valeur actuelle
            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $data = $event->getData();
                if ($data instanceof Utilisateur) {
                    $event->getForm()->get('telephone')->setData($data->getTelephone()->toString());
                }
            });
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
            'is_edit' => false,
        ]);

        $resolver->setNormalizer('data_class', function (Options $options, $value) {
            // En mode création, utiliser le DTO ; en mode édition, utiliser l'entité
            return $options['is_edit'] ? Utilisateur::class : UtilisateurCreateDTO::class;
        });
    }
}
