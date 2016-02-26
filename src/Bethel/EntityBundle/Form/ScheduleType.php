<?php

namespace Bethel\EntityBundle\Form;

use Bethel\EntityBundle\Entity\Semester;
use Bethel\EntityBundle\Validator\TermValidator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ScheduleType extends AbstractType
{

    private $termValidator;
    private $term;

    public function __construct(TermValidator $termValidator, Semester $activeSemester) {
        $this->termValidator = $termValidator;
        $this->term = $activeSemester->getTerm();
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', array(
                'label' => 'Schedule Name'
            ))
            ->add('room')
            ->add('term', 'choice', array(
                'choices' => array(
                    $this->term => $this->term
                ),
                'attr' => array('style'=>'display:none')
            ))
            // TODO: make this more human readable (12h instead of 24h)
            ->add('startTime', 'time', array(
                'widget' => 'single_text'
            ))
            ->add('endTime', 'time', array(
                'widget' => 'single_text'
            ))
            ->add('dayOfWeek', 'choice', array(
                    'choices' => array(
                        // using idate('w', $timestamp) will produce a DOW integer
                        // like this from 0 - 6
                        0 => 'Sunday',
                        1 => 'Monday',
                        2 => 'Tuesday',
                        3 => 'Wednesday',
                        4 => 'Thursday',
                        5 => 'Friday',
                        6 => 'Saturday'
                    ),
                    'multiple' => false
                )
            )
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
                    'attr' => array('class'=>'chosen-select','data-placeholder'=>'Choose tutors ...')
                )
            )
            ->add('coursecodes', 'entity', array(
                'label' => 'Courses',
                'class' => 'BethelEntityBundle:CourseCode',
                'query_builder' => function($repository) {
                    /** @var \Bethel\EntityBundle\Entity\CourseCodeRepository $repository */
                    return $repository->getCourseCodesForActiveSemesterQB();
                },
                'expanded' => true,
                'multiple' => true,
                'mapped' => true
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
            'data_class' => 'Bethel\EntityBundle\Entity\Schedule'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'bethel_entitybundle_schedule';
    }
}
