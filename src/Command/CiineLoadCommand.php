<?php

namespace App\Command;

use ApiPlatform\State\ObjectMapper\ObjectMapper;
use App\Entity\Ciine;
use App\Entity\Show;
use App\Repository\CiineRepository;
use App\Repository\ShowRepository;
use App\Workflow\CiineWFDefinition;
use Doctrine\ORM\EntityManagerInterface;
use Survos\JsonlBundle\IO\JsonlReader;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\ObjectMapper\Exception\MappingTransformException;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

#[AsCommand('ciine:load', 'load from the scraped jsonl file')]
class CiineLoadCommand
{
	public function __construct(
        private CiineRepository        $ciineRepository,
        private EntityManagerInterface $entityManager,
        private ObjectMapperInterface  $objectMapper,
        private readonly ShowRepository $showRepository,
    )
	{
	}


	public function __invoke(
		SymfonyStyle $io,
		#[Argument('path to the jsonl file')]
		string $filename = AsciinemaScrapeCommand::DEFAULT_JSONL,
        #[Option()] int $batch = 500,
	): int
	{
        $reader = new JsonlReader($filename, asArray: false);
        $progressBar = new ProgressBar($io, 28000);
        foreach ($reader as $idx => $row) {
            $progressBar->advance();
            if (0) {
                $code = 'asc_' . $row->id;
                if (!$this->showRepository->find($code)) {
                    $show = new Show($code);
                    $this->entityManager->persist($show);
                }
                $show->title = $row->title;
                $show->author = $row->author;
                $show->asciinamaId = $row->id;

                if ($idx % $batch === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                    $io->write("$batch written");
                }
            }

            if (0) {
                $row->marking = CiineWFDefinition::PLACE_BASIC;
                $row->markingHistory = [];
                $row->lastTransitionTime = null;
                $row->enabledTransitions = [];
                $row->filesize = null;
                try {
                    if (!$ciine = $this->ciineRepository->find($row->id)) {
                        $ciine = new Ciine();
                        $ciine->id = $row->id;
                        $ciine = $this->objectMapper->map($row, Ciine::class);
                        $this->entityManager->persist($ciine);
                    } else {
                        $ciine = $this->objectMapper->map($row, $ciine);
                    }
                } catch (MappingTransformException $e) {
                    dd($e->getMessage());
                }
            }

            if (!$ciine = $this->ciineRepository->find($row->id)) {
                $ciine = new Ciine();
                $ciine->id = $row->id;
                $this->entityManager->persist($ciine);
            }

            $ciine->title = $row->title;
            $ciine->author = $row->author;
            $ciine->featured = $row->featured;

            if ($idx % $batch === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }


        }
        $this->entityManager->flush();
        $progressBar->finish();
		$io->success(self::class . " success. " . $this->ciineRepository->count());
		return Command::SUCCESS;
	}
}
