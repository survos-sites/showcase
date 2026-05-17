<?php

declare(strict_types=1);

namespace App\Workflow;

use App\Entity\Component;
use Survos\StateBundle\Attribute\Place;
use Survos\StateBundle\Attribute\Transition;
use Survos\StateBundle\Attribute\Workflow;

#[Workflow(supports: [Component::class], name: self::WORKFLOW_NAME)]
class ComponentFlow
{
    public const WORKFLOW_NAME = 'ComponentWorkflow';

    #[Place(initial: true)]
    public const PLACE_NEW = 'new';

    #[Place]
    public const PLACE_UPDATED = 'updated';

    #[Place]
    public const PLACE_LOCKED = 'locked';

    #[Transition(from: [self::PLACE_NEW], to: self::PLACE_UPDATED)]
    public const TRANSITION_UPDATE = 'update';

    #[Transition(from: [self::PLACE_UPDATED], to: self::PLACE_LOCKED)]
    public const TRANSITION_LOCK = 'lock';

    #[Transition(from: [self::PLACE_LOCKED], to: self::PLACE_NEW)]
    public const TRANSITION_REFRESH = 'refresh';
}
