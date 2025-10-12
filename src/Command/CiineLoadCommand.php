<?php

namespace App\Command;

use ApiPlatform\State\ObjectMapper\ObjectMapper;
use App\Entity\Ciine;
use App\Repository\CiineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Survos\JsonlBundle\IO\JsonlReader;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

#[AsCommand('ciine:load', 'load from the scraped jsonl file')]
class CiineLoadCommand
{
	public function __construct(
        private CiineRepository $ciineRepository,
        private EntityManagerInterface $entityManager,
        private ObjectMapperInterface $objectMapper,
    )
	{
	}


	public function __invoke(
		SymfonyStyle $io,
		#[Argument('path to the jsonl file')]
		string $filename = AsciinemaScrapeCommand::DEFAULT_JSONL,
	): int
	{
        $reader = new JsonlReader($filename, asArray: false);
        foreach ($reader as $row) {
            if (!$ciine = $this->ciineRepository->find($row->id)) {
                $ciine = $this->objectMapper->map($row, Ciine::class);
                $this->entityManager->persist($ciine);
            } else {
                $ciine = $this->objectMapper->map($row, $ciine);
            }
        }
        $this->entityManager->flush();
		$io->success(self::class . " success. " . $this->ciineRepository->count());
		return Command::SUCCESS;
	}
}
