<?php

namespace Mapbender\SearchBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ResolveParametricServiceIdsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $targettedServiceIds = array(
            'mapbender.search.query.manager',
        );
        foreach ($targettedServiceIds as $consumerId) {
            $definition = $container->getDefinition($consumerId);
            $arguments = $definition->getArguments();
            $argumentsOut = array();
            $modified = false;
            foreach ($arguments as $key => $argument) {
                if ($argument instanceof Reference && preg_match('/^%.*%$/', $argument->__toString())) {
                    $realServiceId = $container->getParameter(trim($argument->__toString(), '%'));
                    $argumentsOut[$key] = new Reference($realServiceId, $argument->getInvalidBehavior());
                    $modified = true;
                } else {
                    $argumentsOut[$key] = $argument;
                }
            }
            if ($modified) {
                $definition->setArguments($argumentsOut);
            }
        }
    }
}
