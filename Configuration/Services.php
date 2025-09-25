<?php

declare(strict_types=1);

namespace VV\T3locallang;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator, ContainerBuilder $container) {
    $services = $containerConfigurator->services()
        ->defaults()
        ->autowire(true)
        ->autoconfigure(true)
        ->private();

    $services->load('VV\\T3locallang\\', '../Classes/*')
        ->exclude(['../Classes/Domain/Model/*']);
};
