<?php

namespace App\Workflow;

use App\Entity\Project;
use Psr\Log\LoggerInterface;
use Survos\WorkflowBundle\Attribute\Workflow;
use Symfony\Component\Process\Process;
use Symfony\Component\Workflow\Attribute\AsGuardListener;
use Symfony\Component\Workflow\Attribute\AsTransitionListener;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Event\TransitionEvent;

#[Workflow(supports: [Project::class], name: self::WORKFLOW_NAME)]
class ProjectWorkflow implements IProjectWorkflow
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


	#[AsGuardListener(self::WORKFLOW_NAME)]
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
		    case self::TRANSITION_UPDATE:
		        break;
		    case self::TRANSITION_LOCK:
		        break;
		    case self::TRANSITION_REFRESH:
		        break;
		}
	}


	#[AsTransitionListener(self::WORKFLOW_NAME, self::TRANSITION_UPDATE)]
	public function onUpdate(TransitionEvent $event): void
	{
		$project = $this->getProject($event);
        $this->updateGit($project->getLocalDir());

    }


	#[AsTransitionListener(self::WORKFLOW_NAME, self::TRANSITION_LOCK)]
	public function onLock(TransitionEvent $event): void
	{
		$project = $this->getProject($event);
	}


	#[AsTransitionListener(self::WORKFLOW_NAME, self::TRANSITION_REFRESH)]
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
