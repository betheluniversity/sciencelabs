<?php

namespace Bethel\EntityBundle\Form;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class StudentSigninType extends AbstractType
{

    private $courses;

    public function __construct(ArrayCollection $courses) {
        $this->courses = $courses;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('courses', 'entity', array(
                'label' => 'Check the class(es) you are here for today, or check "Other" if no classes are listed or apply to this visit. When you are finished in this help session, don\'t forget to sign out or your time will be recorded as 0 minutes.',
                'class' => 'BethelEntityBundle:Course',
                'expanded' => true,
                'multiple' => true,
                'choices' => $this->courses
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
            ->add('timeIn', 'time', array(
                'widget' => 'single_text',
                'attr' => array(
                    'readonly' => 'readonly',
                    'style' => 'display:none'
                )
            ))
            ->add('save','submit', array(
                'attr' => array('class'=>'button scream-button radius right'),
                'label' => 'Sign in'
            ))
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
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
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'bethel_entitybundle_studentsignin';
    }
}
