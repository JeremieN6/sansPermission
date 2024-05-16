<?php

namespace App\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OpenAIServiceCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('App\Service\OpenAIService')) {
            return;
        }

        $definition = $container->findDefinition('App\Service\OpenAIService');
        $definition->addMethodCall('setApiKey', [$container->getParameter('OPENAI_API_KEY')]);
    }
}
