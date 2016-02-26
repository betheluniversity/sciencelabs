<?php

namespace Bethel\EntityBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserSearchType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName','text',array(
                'label' => 'First Name'
            ))
            ->add('lastName','text',array(
                'label' => 'Last Name'
            ))
            ->add('search','submit', array(
                'attr' => array('class'=>'button success radius right')
            ))
        ;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'bethel_entitybundle_usersearch';
    }
}
