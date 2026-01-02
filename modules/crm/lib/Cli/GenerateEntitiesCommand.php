<?php

namespace Bitrix\Crm\Cli;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class GenerateEntitiesCommand extends Command
{
	public function isEnabled(): bool
	{
		return Loader::includeModule('crm');
	}

	protected function configure(): void
	{
		$this
			->setName('crm:generate-entities')
			->setDescription('Generate multiple CRM entities')
			->addArgument(
				'number',
				InputArgument::REQUIRED,
				'Number of entities to generate'
			)
			->addOption(
				'entity-type-id',
				'e',
				InputOption::VALUE_REQUIRED,
				'CRM entity type ID'
			)
			->addOption(
				'entity-category-id',
				'c',
				InputOption::VALUE_OPTIONAL,
				'CRM entity category ID (if necessary)'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$ioHandler = new SymfonyStyle($input, $output);

		$number = (int)$input->getArgument('number');
		if ($number < 1)
		{
			$ioHandler->error('Number of entities must be a positive integer');

			return Command::FAILURE;
		}

		$entityTypeId = (int)$input->getOption('entity-type-id');
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			$ioHandler->error("Entity type with ID '$entityTypeId' does not supported");

			return Command::FAILURE;
		}

		$entityCategoryId = $input->getOption('entity-category-id');
		if (
			$factory->isCategoriesEnabled()
			&& !$factory->isCategoryExists($entityCategoryId)
		)
		{
			$entityCategoryId = null; // use default from settings
		}

		$connection = Application::getConnection();
		$successNumber = 0;

		$entityName = $factory->getEntityName() ?? '';
		if ($entityName)
		{
			$ioHandler->info("Let's start creating '$entityName' entities");
		}

		if (isset($entityCategoryId))
		{
			$addFields = [
				'CATEGORY_ID' => $entityCategoryId,
			];
		}

		$progressBar = $this->createProgressBar($output, $number);
		$progressBar->start();

		for ($i = 0; $i < $number; $i++)
		{
			$item = $factory->createItem($addFields ?? []);
			$operation = $factory->getAddOperation($item);
			$operation->disableAllChecks();

			$connection->startTransaction();
			$result = $operation->launch();
			if ($result->isSuccess())
			{
				$connection->commitTransaction();

				$successNumber++;
			}
			else
			{
				$connection->rollbackTransaction();

				$ioHandler->error("Error creating item $i out of $number: " . $result->getError()?->getMessage());
			}

			$progressBar->advance();
		}

		$progressBar->finish();
		$ioHandler->newLine(2);
		$ioHandler->success("$successNumber out of $number item(s) of entities '$entityName' generated");

		return Command::SUCCESS;
	}

	private function createProgressBar(OutputInterface $output, int $count): ProgressBar
	{
		$progress = new ProgressBar($output, $count);
		$progress->setBarCharacter('<fg=magenta>=</>');
		$progress->setRedrawFrequency(floor($count / 100));
		$progress->start();

		return $progress;
	}
}
