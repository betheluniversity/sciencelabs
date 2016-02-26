<?php

namespace Bethel\EntityBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Bethel\EntityBundle\Entity\CourseCode;

class StudentSessionType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            //->add('courseCodes')
            ->add('timeIn', 'time', array(
                'widget' => 'single_text'
            ))
            ->add('timeOut', 'time', array(
                'widget' => 'single_text'
            ))
            ->add('courses', 'entity', array(
                'label' => 'Courses',
                'class' => 'BethelEntityBundle:Course',
                'expanded' => true,
                'multiple' => true
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
            'data_class' => 'Bethel\EntityBundle\Entity\StudentSession'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'bethel_entitybundle_studentsession';
    }
}
