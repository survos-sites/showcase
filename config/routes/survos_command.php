<?php
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\HttpFoundation\Request;

return static function (RoutingConfigurator $routes): void {
    $routes->import('@SurvosCommandBundle/config/routes.php')
        ->methods([Request::METHOD_GET, Request::METHOD_POST])
        ->prefix('/admin') // consider adding this path to the access_control key in security
    ;
};

