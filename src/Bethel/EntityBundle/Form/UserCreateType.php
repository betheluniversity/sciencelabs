<?php

namespace Bethel\EntityBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Count;

class UserCreateType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username','hidden')
            ->add('roles', 'entity', array(
                'label' => 'Roles',
                'query_builder' => function($repository) {
                    /** @var \Bethel\EntityBundle\Entity\RoleRepository $repository */
                    return $repository->createQueryBuilder('r')
                            ->where('r.role != :apirole')
                            ->setParameter('apirole', 'ROLE_API_USER')
                            ->orderBy('r.sort');
                    },
                'class' => 'BethelEntityBundle:Role',
                'expanded' => true,
                'multiple' => true,
                'constraints' => new Count(
                    array('min' => 1, 'minMessage' => 'Please select at least one role')
                )
            ))
            ->add('create','submit', array(
                'attr' => array('class'=>'button success radius right')
            ))
        ;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'bethel_entitybundle_usercreate';
    }
}
