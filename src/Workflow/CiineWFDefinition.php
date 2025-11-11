<?php

namespace App\Workflow;

use App\Entity\Ciine;
use Survos\StateBundle\Attribute\Place;
use Survos\StateBundle\Attribute\Transition;
use Survos\StateBundle\Attribute\Workflow;

#[Workflow(supports: [Ciine::class], name: self::WORKFLOW_NAME)]
class CiineWFDefinition
{
	public const WORKFLOW_NAME = 'CiineWorkflow';

	#[Place(initial: true)]
	public const PLACE_BASIC = 'basic';

	#[Place]
	public const PLACE_DOWNLOADED = 'downloaded';

	#[Place]
	public const PLACE_DETAILED = 'detailed';

	#[Transition(from: [self::PLACE_BASIC], to: self::PLACE_DOWNLOADED, async: true)]
	public const TRANSITION_DOWNLOAD = 'download';

	#[Transition(from: [self::PLACE_DOWNLOADED], to: self::PLACE_DETAILED)]
	public const TRANSITION_SCRAPE = 'scrape';
}
