<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class DisplayUserMapType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', 'text', array(
                'constraints' => array(
                    new NotBlank()
                )
            ))
            ->add('show_friends', 'checkbox', array(
                'label' => 'Show Friends (users he follows)',
                'value' => 'true'
            ))
            ->add('show_followers', 'checkbox', array(
                'label' => 'Show Followers',
                'value' => 'true'
            ))
        ;
    }

    public function getName()
    {
        return 'display_user_map';
    }
}