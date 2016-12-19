<?php

namespace Bethel\EntityBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserAdminType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->show_roles = $options['show_roles'];

        $builder
            ->add('firstName')
            ->add('lastName')
            ->add('username', 'text', array(
                'attr' => array('readonly' => 'readonly'),
                'disabled' => true
            ))
            ->add('email');

            if ( $this->show_roles ) {
                $builder
                    ->add('roles', 'entity', array(
                        'label' => 'Roles',
                        'query_builder' => function ($repository) {
                            // a dummy return. Need to fix later.
                            return $repository->createQueryBuilder('r')
                                ->where('r.role != :apirole')
                                ->setParameter('apirole', 'test');
                        },
                        'class' => 'BethelEntityBundle:Role',
                        'expanded' => true,
                        'multiple' => true
                    ));
            }
        $builder
            ->add('courses', 'entity', array(
                'label' => 'Additional Viewable Courses for the Current Term',
                'class' => 'BethelEntityBundle:Course',
                'multiple' => true,
                'expanded' => true,
                'query_builder' => function($repository) {
                    return $repository->createQueryBuilder('c')
                        ->leftJoin('c.semester', 's')
                        ->where('s.active = 1');
                },
                'mapped' => false,
                'required' => false,
//                'attr' => array('class'=>'chosen-select','data-placeholder'=>'Choose new user ...','style'=>'height:200px')
            ))
            ->add('save','submit', array(
                    'attr' => array('class'=>'button success radius right')
                ))
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Bethel\EntityBundle\Entity\User',
            'show_roles' => null
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
