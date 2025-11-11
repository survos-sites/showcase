<?php

namespace App\Workflow;

use App\Entity\Show;
use App\Workflow\ShowWFDefinition as WF;
use Survos\StateBundle\Attribute\Workflow;
use Symfony\Component\Workflow\Attribute\AsGuardListener;
use Symfony\Component\Workflow\Attribute\AsTransitionListener;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Event\TransitionEvent;

class ShowWorkflow
{

	public function __construct()
	{
	}


	public function getShow(Event $event): Show
	{
		/** @var Show */ return $event->getSubject();
	}


	#[AsTransitionListener(WF::WORKFLOW_NAME,WF::TRANSITION_IMPORT)]
	public function onImport(TransitionEvent $event): void
	{
		$show = $this->getShow($event);
	}


	#[AsTransitionListener(WF::WORKFLOW_NAME,WF::TRANSITION_UPLOAD)]
	public function onUpload(TransitionEvent $event): void
	{
		$show = $this->getShow($event);
	}


	#[AsTransitionListener(WF::WORKFLOW_NAME,WF::TRANSITION_CLEAN)]
	public function onClean(TransitionEvent $event): void
	{
		$show = $this->getShow($event);
	}
}
