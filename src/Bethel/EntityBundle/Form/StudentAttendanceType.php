<?php

namespace Bethel\EntityBundle\Form;

use Bethel\EntityBundle\Entity\CourseRepository;
use Bethel\EntityBundle\Entity\UserRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\ORM\EntityManager;

class StudentAttendanceType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $em = $options['em'];
        /** @var \Doctrine\ORM\EntityManager $em */
        $studentCourses = $options['studentCourses'];
        /** @var \Bethel\EntityBundle\Entity\UserRepository $userRepository */
        $roleRepository = $em->getRepository('BethelEntityBundle:Role');
        $semesterRepository = $em->getRepository('BethelEntityBundle:Semester');
        $builder
            ->add('timeIn', 'time', array(
                'widget' => 'single_text'
            ))
            ->add('timeOut', 'time', array(
                'widget' => 'single_text',
                'required' => false
            ))
            ->add('courses', 'entity', array(
                'label' => 'Courses',
                'class' => 'BethelEntityBundle:Course',
                'choices' => $studentCourses,
                'expanded' => true,
                'multiple' => true
            ))
            ->add('otherCourse', 'checkbox', array(
                'label' => 'Other',
                'required' => false,
                'error_bubbling' => true
            ))
            ->add('otherCourseName', 'text', array (
                'label' => 'Course Name or Reason',
                'required' => false
            ))
            ->add('save','submit', array(
                'attr' => array('class'=>'button success radius right')
            ))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
            ->setDefaults(
                array(
                    'data_class' => 'Bethel\EntityBundle\Entity\StudentSession',
                    'validation_groups' => function(FormInterface $form) {
                        /** @var \Bethel\EntityBundle\Entity\StudentSession $data */
                        $data = $form->getData();
                        $selectedCourses = $data->getCourses();
                        if($selectedCourses->isEmpty()) {
                            return array('Default','courseOrOther');
                        } else {
                            return array('Default');
                        }
                    }
                )
            )
            ->setRequired(array(
                'em',
                'studentCourses'
            ))
            ->setAllowedTypes(array(
                'em' => 'Doctrine\ORM\EntityManager',
                'studentCourses' => array('Doctrine\Common\Collections\ArrayCollection', 'null')
            ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'bethel_entitybundle_studentattendance';
    }
}
