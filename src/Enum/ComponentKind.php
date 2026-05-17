<?php

namespace App\Enum;

enum ComponentKind: string
{
    case Bundle = 'symfony-bundle';
    case Library = 'library';
    case App = 'project';
    case Plugin = 'composer-plugin';
}
