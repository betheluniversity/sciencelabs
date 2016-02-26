<?php

namespace Bethel\EntityBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SessionType extends AbstractType
{
     /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var \Bethel\EntityBundle\Entity\Session $session */
        $session = $builder->getData();
        $builder
            ->add('semester')
            ->add('date', 'date', array(
                'widget' => 'single_text',
                'label' => 'Date (click to select)',
                'format' => 'MM/dd/yyyy',
                'attr' => array('readonly' => 'readonly', 'class' => 'date-selector'),
            ))
            ->add('name', 'text', array(
                'label' => 'Name of Session'
            ))
            ->add('room', 'text', array(
                'label' => 'Room Number'
            ))
            ->add('schedStartTime', 'time', array(
                'label' => 'Scheduled Start Time',
                'widget' => 'single_text'
            ))
            ->add('schedEndTime', 'time', array(
                'label' => 'Scheduled End Time',
                'widget' => 'single_text'
            ))
            ->add('leadTutors', 'entity', array(
                    'label' => 'Lead Tutors',
                    'class' => 'BethelEntityBundle:User',
                    'multiple' => true,
                    'expanded' => false,
                    'query_builder' => function($repository) {
                        return $repository->createQueryBuilder('c')
                            ->innerJoin('c.roles','s')
                            ->where('s.role = :role')
                            ->orWhere('s.role = :leadRole')
                            ->setParameter('role', 'ROLE_TUTOR')
                            ->setParameter('leadRole', 'ROLE_LEAD_TUTOR');
                    },
                    'mapped' => false,
                    'required' => false,
                    'attr' => array('class'=>'chosen-select','data-placeholder'=>'Choose lead tutors ...')
                )
            )
            ->add('tutors', 'entity', array(
                    'label' => 'Tutors',
                    'class' => 'BethelEntityBundle:User',
                    'multiple' => true,
                    'expanded' => false,
                    'required' => false,
                    'query_builder' => function($repository) {
                            return $repository->createQueryBuilder('u')
                                ->innerJoin('u.roles','r')
                                ->where('r.role = :role')
                                ->orWhere('r.role = :leadRole')
                                ->setParameter('role', 'ROLE_TUTOR')
                                ->setParameter('leadRole', 'ROLE_LEAD_TUTOR')
                                ->orderBy('u.lastName', 'ASC');
                        },
                    'mapped' => false,
                    'attr' => array('class'=>'chosen-select','data-placeholder'=>'Choose tutors ...')
                )
            )
            ->add('startTime', 'time', array(
                'label' => 'Actual Start Time',
                'widget' => 'single_text',
                'required' => false
            ))
            ->add('endTime', 'time', array(
                'label' => 'Actual End Time',
                'widget' => 'single_text',
                'required' => false
            ))
            ->add('coursecodes', 'entity', array(
                'label' => 'Courses Offered at Session',
                'class' => 'BethelEntityBundle:CourseCode',
                'query_builder' => function($repository) {
                    /** @var \Bethel\EntityBundle\Entity\CourseCodeRepository $repository */
                    return $repository->getCourseCodesForActiveSemesterQB();
                },
                'expanded' => true,
                'multiple' => true
            ))
            ->add('anonStudents')
            ->add('comments')
            ->add('save','submit', array(
                'attr' => array('class'=>'button success radius right')
            ));
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Bethel\EntityBundle\Entity\Session'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'bethel_entitybundle_session';
    }
}
