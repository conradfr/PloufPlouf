<?php

namespace PloufPlouf;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DilemmaType extends AbstractType {

    /**
     * There is two validation groups :	"main" run each time.
     * 									"emails" is run only if email is given
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver) {

        $resolver->setDefaults(array(
            'validation_groups' => function(FormInterface $form) {
                    $data = $form->getData();
                    $groups[] = 'main';
                    if (!empty($data['email'])) { $groups[] = 'emails'; }

                    return $groups;
                }
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {

        // "validation_groups" was not working on form fields so instead "groups" in each constraints is used
        return $builder
                ->add('question', 'text', [
                    'error_bubbling' => false,
                    'constraints' => [	/* new Assert\NotBlank(['groups' => ['main']]), */
                                        new Assert\Length(['min' => 2, 'groups' => ['main']])
                                    ]
                ])
                ->add('choices', 'collection', [
                    'type'   => 'text',
                    'error_bubbling' => false,
                    'options'  => [
                        'required'  => true
                    ],
                    'constraints' => [new Assert\NotBlank(['groups' => ['main']]), new Assert\Count(['min' => 2, 'groups' => ['main']])],
                    'allow_add' => true
                ])
                ->add('emails', 'collection', [
                    'type'   => 'email',
                    'error_bubbling' => false,
                    'options'  => [
                        'required'  => false,
                        'constraints' => [new Assert\Email(['groups' => ['emails']])]
                    ],
                    'allow_add' => true
                ])
                ->add('name', 'text', [
                    'error_bubbling' => false,
                    'constraints' => [	new Assert\NotBlank(['groups' => ['emails']]),
                                        new Assert\Length(['min' => 2, 'groups' => ['emails']])
                                    ]
                ])
                ->add('email', 'text', [
                    'error_bubbling' => false,
                    'constraints' => [
                                        new Assert\NotBlank(['groups' => ['emails']]),
                                        new Assert\Email(['groups' => ['emails']])
                                    ]
                ])
                ->add('submit', 'submit', [
                    'validation_groups' => false
                ]);
    }

    public function getName() {
        return '';
    }
}