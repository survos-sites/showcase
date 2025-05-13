<?php

namespace App\Workflow;

use Survos\WorkflowBundle\Attribute\Place;
use Survos\WorkflowBundle\Attribute\Transition;

interface IProjectWorkflow
{
	public const WORKFLOW_NAME = 'ProjectWorkflow';

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
