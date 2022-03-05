<?php


namespace Mapbender\SearchBundle\Element\Type;


use Mapbender\ManagerBundle\Form\Type\YAMLConfigurationType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

class SearchAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('cluster_threshold', NumberType::class, array(
                'label' => 'Clustern ab Maßstab',
                'required' => false,
            ))
            ->add('schemas', YAMLConfigurationType::class)
        ;
    }
}
