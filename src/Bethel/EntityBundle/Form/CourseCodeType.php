<?php

namespace Bethel\EntityBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class CourseCodeType extends AbstractType
{
    private $appTitle;

    public function __construct($appTitle) {
        $this->appTitle = $appTitle;
    }
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('coursecode', 'text', array(
                'label' => ' ',
                'required' => false
            ))
            ->add('save','submit', array(
                'attr' => array('class'=>'button success radius right', 'style' => 'margin-top:16px;'),
                'label' => 'Submit Courses'
            ))
        ;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'bethel_entitybundle_coursecode';
    }
}