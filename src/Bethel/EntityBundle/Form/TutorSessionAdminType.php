<?php

namespace Bethel\EntityBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TutorSessionAdminType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('timeIn', 'time', array(
                'label' => 'Time In',
                'widget' => 'single_text'
            ))
            ->add('timeOut', 'time', array(
                'label' => 'Time Out',
                'widget' => 'single_text',
                'required' => false
            ))
            ->add('lead', 'checkbox', array(
                'label' => 'This tutor served as the lead tutor for the session',
                'required' => false
            ))
            ->add('tutor', 'entity', array(
                    'label' => 'Registered Tutors',
                    'class' => 'BethelEntityBundle:User',
                    'multiple' => false,
                    'expanded' => false,
                    'required' => true,
                    'empty_value' => '',
                    'query_builder' => function(EntityRepository $repository) {
                        return $repository->createQueryBuilder('c')
                            ->innerJoin('c.roles','s')
                            ->where('s.role = :role')
                            ->orWhere('s.role = :leadRole')
                            ->setParameter('role', 'ROLE_TUTOR')
                            ->setParameter('leadRole', 'ROLE_LEAD_TUTOR');
                    },
                    'attr' => array('class'=>'chosen-select','data-placeholder'=>'Choose tutor ...')
                )
            )
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
            'data_class' => 'Bethel\EntityBundle\Entity\TutorSession'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'bethel_entitybundle_tutorsession';
    }
}
