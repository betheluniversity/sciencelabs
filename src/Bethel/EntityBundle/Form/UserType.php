<?php

namespace Bethel\EntityBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var \Bethel\EntityBundle\Entity\User $user */
        $user = $builder->getData();

        $builder
            ->add('firstName')
            ->add('lastName')
            ->add('username', 'text', array(
                'read_only' => true,
                'disabled' => true
            ))
            ->add('email', 'text', array(
                'read_only' => true,
                'disabled' => true
            ))
        ;

        if($user->hasRole('ROLE_ADMIN') || $user->hasRole('ROLE_PROFESSOR')) {
            $builder->add('sendEmail', 'choice', array(
                'label' => 'Receive an Email when a Session is Closed',
                'choices' => array(
                    true => 'Yes',
                    false => 'No'
                ),
                'multiple' => false,
                'expanded' => true,
                'attr' => array('class' => 'text-center')
            ));
        }

        $builder->add('save','submit', array(
            'attr' => array('class'=>'button success radius right')
        ));
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Bethel\EntityBundle\Entity\User'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'bethel_entitybundle_user';
    }
}
