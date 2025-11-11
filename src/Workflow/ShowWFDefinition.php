<?php

namespace App\Workflow;

use App\Entity\Show;
use Survos\StateBundle\Attribute\Place;
use Survos\StateBundle\Attribute\Transition;
use Survos\StateBundle\Attribute\Workflow;

#[Workflow(supports: [Show::class], name: self::WORKFLOW_NAME)]

class ShowWFDefinition
{
	public const WORKFLOW_NAME = 'ShowWorkflow';

	#[Place(initial: true)]
	public const PLACE_NEW = 'new';

	#[Place]
	public const PLACE_IMPORTED = 'imported';

	#[Place]
	public const PLACE_UPLOADED = 'uploaded';

	#[Place]
	public const PLACE_CLEANED = 'cleaned';

	#[Transition(from: [self::PLACE_NEW], to: self::PLACE_IMPORTED)]
	public const TRANSITION_IMPORT = 'import';

	#[Transition(from: [self::PLACE_IMPORTED], to: self::PLACE_UPLOADED)]
	public const TRANSITION_UPLOAD = 'upload';

	#[Transition(from: [self::PLACE_NEW, self::PLACE_UPLOADED], to: self::PLACE_CLEANED)]
	public const TRANSITION_CLEAN = 'clean';
}
