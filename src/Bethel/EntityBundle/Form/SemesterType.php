<?php

namespace Bethel\EntityBundle\Form;

use Bethel\EntityBundle\Validator\TermValidator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SemesterType extends AbstractType
{

    private $termValidator;

    public function __construct(TermValidator $termValidator) {
        $this->termValidator = $termValidator;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $yearChoices = array();

        // Allow the admin to choose a year up to 10 years in the future
        // when they are transitioning to a new semester.
        for($i = 0; $i < 10; $i++) {
            $yearChoices[date('Y') + $i] = date('Y') + $i;
        }

        $builder
            ->add('term', 'choice', array(
                'label' => 'New Term',
                'choices' => $this->termValidator->getTerms()
            ))
            ->add('year', 'choice', array(
                'choices' => $yearChoices
            ))
            ->add('startDate', 'date', array(
                'widget' => 'single_text',
                'label' => 'Start Date (click to select)',
                'format' => 'MM/dd/yyyy',
                'attr' => array('readonly' => 'readonly', 'class' => 'date-selector')
            ))
            ->add('endDate', 'date', array(
                'widget' => 'single_text',
                'label' => 'End Date (click to select)',
                'format' => 'MM/dd/yyyy',
                'attr' => array('readonly' => 'readonly', 'class' => 'date-selector')
            ))
            ->add('save','submit', array(
                'label' => 'Set Term',
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
            'data_class' => 'Bethel\EntityBundle\Entity\Semester'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'bethel_entitybundle_semester';
    }
}
