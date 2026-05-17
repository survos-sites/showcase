<?php

declare(strict_types=1);

namespace App\Workflow;

use App\Entity\Component;
use App\Workflow\ComponentFlow as WF;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Workflow\Attribute\AsGuardListener;
use Symfony\Component\Workflow\Attribute\AsTransitionListener;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Event\TransitionEvent;

class ComponentWorkflow
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function getComponent(TransitionEvent|GuardEvent $event): Component
    {
        /** @var Component */ return $event->getSubject();
    }

    #[AsGuardListener(WF::WORKFLOW_NAME)]
    public function onGuard(GuardEvent $event): void
    {
        switch ($event->getTransition()->getName()) {
            case WF::TRANSITION_UPDATE:
            case WF::TRANSITION_LOCK:
            case WF::TRANSITION_REFRESH:
                break;
        }
    }

    #[AsTransitionListener(WF::WORKFLOW_NAME, WF::TRANSITION_UPDATE)]
    public function onUpdate(TransitionEvent $event): void
    {
        $this->updateGit($this->getComponent($event)->localDir);
    }

    #[AsTransitionListener(WF::WORKFLOW_NAME, WF::TRANSITION_LOCK)]
    public function onLock(TransitionEvent $event): void
    {
        $this->getComponent($event);
    }

    #[AsTransitionListener(WF::WORKFLOW_NAME, WF::TRANSITION_REFRESH)]
    public function onRefresh(TransitionEvent $event): void
    {
        $this->getComponent($event);
    }

    private function updateGit(string $dir): void
    {
        $processes = [
            new Process(['composer', 'config', 'minimum-stability', 'beta', "--working-dir=$dir"]),
            new Process(['composer', 'config', 'extra.symfony.require', '^8.1', "--working-dir=$dir"]),
            new Process(['composer', 'update', "--working-dir=$dir"]),
        ];
        foreach ($processes as $process) {
            $process->setTimeout(600);
            $this->logger->warning($process->getCommandLine());
            $process->run(fn($type, $buffer) => $this->logger->info(($type === Process::ERR ? 'ERR' : 'OUT') . ": $buffer"));
            $this->logger->info($process->getOutput());
        }
    }
}
