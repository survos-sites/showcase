<?php

namespace App\Workflow;

use App\Dto\Player;
use App\Dto\PlayerEvent;
use App\Entity\Ciine;
use App\Entity\Show;
use App\Repository\ShowRepository;
use App\Workflow\CiineWFDefinition as WF;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Survos\CoreBundle\Service\ChunkDownloader;
use Survos\StateBundle\Attribute\Workflow;
use Symfony\Component\Workflow\Attribute\AsGuardListener;
use Symfony\Component\Workflow\Attribute\AsTransitionListener;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Zenstruck\Bytes;

class CiineWorkflow
{

	public function __construct(
        private ChunkDownloader $chunkDownloader,
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private readonly ShowRepository $showRepository,
    )
	{
	}


	public function getCiine(\Symfony\Component\Workflow\Event\Event $event): Ciine
	{
		/** @var Ciine */ return $event->getSubject();
	}


	#[AsTransitionListener(WF::WORKFLOW_NAME, WF::TRANSITION_DOWNLOAD)]
	public function onDownload(TransitionEvent $event): void
	{
		$ciine = $this->getCiine($event);
        $downloadDir = 'data'; // hmm, env var?
        $destination = $downloadDir . '/' . $ciine->id . '.ciine';
        if (!file_exists($destination)) {
            $size = $this->chunkDownloader->download($ciine->downloadUrl, $destination);
            $this->logger->warning("Downloaded $ciine->downloadUrl " . Bytes::parse($size));
            $ciine->filesize = $size;
        }

        // this should probably be the next transition, but for now we'll get it here.
        $code = 'ascii_' . $ciine->id;
//        $newLines = $this->cleanup(file_get_contents($destination), $code);
        if (!$show = $this->showRepository->find($code)) {
            $show = new Show($code);
            $this->entityManager->persist($show);
        }
        $show->asciinamaId = $ciine->id;
        $show->title = $ciine->title;
        $show->totalTime = $ciine->duration;
        $show->markerCount = 0;
        $show->fileSize = filesize($destination);
        $lines = file($destination);
        // during dev only!
        $show->asciiCast = file_get_contents($destination);
        $show->lineCount = count($lines);
        $this->entityManager->flush();

	}


	#[AsTransitionListener(WF::WORKFLOW_NAME, WF::TRANSITION_SCRAPE)]
	public function onScrape(TransitionEvent $event): void
	{
		$ciine = $this->getCiine($event);
	}



}
