<?php

namespace App\Workflow;

use App\Entity\Project;
use Psr\Log\LoggerInterface;
use Survos\StateBundle\Attribute\Workflow;
use Symfony\Component\Process\Process;
use Symfony\Component\Workflow\Attribute\AsGuardListener;
use Symfony\Component\Workflow\Attribute\AsTransitionListener;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Event\TransitionEvent;
use App\Workflow\IProjectWorkflow as WF;
class ProjectWorkflow
{
	public const WORKFLOW_NAME = 'ProjectWorkflow';

	public function __construct(
        private LoggerInterface $logger,
    )
	{
	}


	public function getProject(TransitionEvent|GuardEvent $event): Project
	{
		/** @var Project */ return $event->getSubject();
	}


	#[AsGuardListener(WF::WORKFLOW_NAME)]
	public function onGuard(GuardEvent $event): void
	{
		/** @var Project project */
		$project = $event->getSubject();

		switch ($event->getTransition()->getName()) {
		/*
		e.g.
		if ($event->getSubject()->cannotTransition()) {
		  $event->setBlocked(true, "reason");
		}
		App\Entity\Project
		*/
		    case WF::TRANSITION_UPDATE:
		        break;
		    case WF::TRANSITION_LOCK:
		        break;
		    case WF::TRANSITION_REFRESH:
		        break;
		}
	}


	#[AsTransitionListener(WF::WORKFLOW_NAME, WF::TRANSITION_UPDATE)]
	public function onUpdate(TransitionEvent $event): void
	{
		$project = $this->getProject($event);
        $this->updateGit($project->getLocalDir());

    }


	#[AsTransitionListener(WF::WORKFLOW_NAME, WF::TRANSITION_LOCK)]
	public function onLock(TransitionEvent $event): void
	{
		$project = $this->getProject($event);
	}


	#[AsTransitionListener(WF::WORKFLOW_NAME, WF::TRANSITION_REFRESH)]
	public function onRefresh(TransitionEvent $event): void
	{
		$project = $this->getProject($event);
	}

    private function updateGit($dir)
    {
        $processes = [
            new Process(['composer', 'config', 'minimum-stability', 'beta', "--working-dir=$dir"]),
            new Process(['composer', 'config', 'extra.symfony.require', '^7.3', "--working-dir=$dir"]),
            new Process(['composer', 'update', "--working-dir=$dir"])
            // composer req phpunit/phpunit:^12.1 --dev phpunit/php-code-coverage:^12.1 -W
        ];
        foreach ($processes as $process ) {
            $process->setTimeout(600);
            $this->logger->warning($process->getCommandLine());
            $process->run(function ($type, $buffer): void {
                if (Process::ERR === $type) {
                    echo 'ERR > ' . $buffer;
                } else {
                    echo 'OUT > ' . $buffer;
                }
            });
            $list = $process->getOutput();
            $this->logger->info($list);

        }
    }
}
