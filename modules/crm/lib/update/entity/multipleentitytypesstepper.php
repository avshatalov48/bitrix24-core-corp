<?php

namespace Bitrix\Crm\Update\Entity;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\Update\Stepper;

abstract class MultipleEntityTypesStepper extends Stepper
{
	protected const OPTION_PREFIX = 'update_entity_stepper_';

	protected static $moduleId = 'crm';

	final function execute(array &$option)
	{
		$isProcessingFinishedForAllTypes = true;

		foreach (self::getTypesToProcess() as $entityTypeId)
		{
			$isAllItemsProcessed = $this->processType($entityTypeId);

			$isProcessingFinishedForAllTypes = $isProcessingFinishedForAllTypes && $isAllItemsProcessed;
		}

		if ($isProcessingFinishedForAllTypes)
		{
			$this->cleanUp();

			return self::FINISH_EXECUTION;
		}

		return self::CONTINUE_EXECUTION;
	}

	//region Processing
	private function processType(int $entityTypeId): bool
	{
		$factory = $this->getFactory($entityTypeId);
		if (!$factory)
		{
			$this->markTypeAsFinished($entityTypeId);

			return true;
		}

		[$lastId, $isAllItemsProcessed] = $this->getProgress($entityTypeId);
		if ($isAllItemsProcessed)
		{
			return true;
		}

		$processedCount = 0;
		foreach ($this->getRowsToProcess($factory, $lastId) as $row)
		{
			$this->processRow($factory, $row);

			$lastId = $row->getId();
			$processedCount++;
		}

		if ($processedCount < self::getSingleEntityStepLimit())
		{
			$isAllItemsProcessed = true;
		}

		$this->saveProgress($entityTypeId, $lastId, $isAllItemsProcessed);

		return $isAllItemsProcessed;
	}

	private function getFactory(int $entityTypeId): ?Factory
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if ($factory && $this->isSupported($factory))
		{
			return $factory;
		}

		return null;
	}

	protected function isSupported(Factory $factory): bool
	{
		return true;
	}

	abstract protected function getRowsToProcess(Factory $factory, ?int $lastId): Collection;

	abstract protected function processRow(Factory $factory, EntityObject $row): void;
	//endregion

	//region Options
	private static function getTypesToProcess(): array
	{
		$value = Option::get('crm', static::OPTION_PREFIX . 'types');
		if (!$value)
		{
			return [];
		}

		return unserialize($value, ['allowed_classes' => false]);
	}

	private function markTypeAsFinished(int $entityTypeId): void
	{
		[$lastId, ] = $this->getProgress($entityTypeId);
		$this->saveProgress($entityTypeId, $lastId, true);
	}

	private function getProgress(int $entityTypeId): array
	{
		$serialized = Option::get('crm', $this->getProgressOptionName($entityTypeId));

		$values = $serialized ? unserialize($serialized, ['allowed_classes' => false]) : [];

		return [
			isset($values['lastId']) ? (int)$values['lastId'] : null,
			isset($values['isFinished']) ? (bool)$values['isFinished'] : false,
		];
	}

	private function saveProgress(int $entityTypeId, ?int $lastId, bool $isFinished): void
	{
		if ($isFinished)
		{
			$this->onEntityFinish($entityTypeId);
		}

		Option::set(
			'crm',
			$this->getProgressOptionName($entityTypeId),
			serialize([
				'lastId' => $lastId,
				'isFinished' => $isFinished,
			]),
		);
	}

	private function cleanUp(): void
	{
		foreach (self::getTypesToProcess() as $entityTypeId)
		{
			$this->onEntityFinish($entityTypeId);

			Option::delete(
				'crm',
				[
					'name' => $this->getProgressOptionName($entityTypeId),
				],
			);
		}

		Option::delete('crm', ['name' => static::OPTION_PREFIX . 'types']);
		Option::delete('crm', ['name' => static::OPTION_PREFIX . 'entity_step_limit']);
	}

	protected function onEntityFinish(int $entityTypeId): void
	{
	}

	private function getProgressOptionName(int $entityTypeId): string
	{
		$entityName = \CCrmOwnerType::ResolveName($entityTypeId);

		return static::OPTION_PREFIX . 'progress_' . mb_strtolower($entityName);
	}

	final protected static function getSingleEntityStepLimit(): int
	{
		// we will process at least 6 types at the same time. but it's possible to have up to 70 types with dynamic types

		return (int)Option::get('crm',  static::OPTION_PREFIX . 'entity_step_limit', 10);
	}
	//endregion

	final public static function bindOnCrmModuleInstallIfNeeded(): void
	{
		if (!empty(self::getTypesToProcess()))
		{
			\CAgent::AddAgent(
			/** @see static::execAgent() */
				static::class . '::execAgent();',
				'crm',
				"Y",
				// run once every minute
				60,
				"",
				"Y",
				// 5 min delay
				\ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 300, 'FULL'),
				100,
				false,
				false
			);
		}
	}
}
