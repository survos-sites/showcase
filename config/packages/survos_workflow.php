<?php

declare(strict_types=1);

use Survos\WorkflowBundle\Service\ConfigureFromAttributesService;
use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $framework) {
//return static function (ContainerConfigurator $containerConfigurator): void {

    if (class_exists(ConfigureFromAttributesService::class))
        foreach ([
                 \App\Workflow\ProjectWorkflow::class,
                 \App\Workflow\PlayerWorkflow::class,
                 ] as $workflowClass) {
            if (class_exists($workflowClass)) {
                ConfigureFromAttributesService::configureFramework($workflowClass, $framework, [$workflowClass]);
            }
        }

};
