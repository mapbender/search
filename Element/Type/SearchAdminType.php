<?php


namespace Mapbender\SearchBundle\Element\Type;


use Mapbender\ManagerBundle\Form\Type\YAMLConfigurationType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SearchAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('schemas', YAMLConfigurationType::class)
            ->add('clustering', YAMLConfigurationType::class)
        ;
    }
}
