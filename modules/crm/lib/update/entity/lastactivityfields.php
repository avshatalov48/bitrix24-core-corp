<?php

namespace Bitrix\Crm\Update\Entity;

use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\Timeline\Monitor;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\Update\Stepper;

final class LastActivityFields extends Stepper
{
	private const OPTION_PREFIX = 'update_last_activity_stepper_';

	protected static $moduleId = 'crm';

	private Monitor $monitor;

	function execute(array &$option)
	{
		$this->initMonitor();

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
	private function initMonitor(): void
	{
		Monitor::onLastActivityRecalculationByAgent();

		$this->monitor = Monitor::getInstance();
	}

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
		if ($factory && $factory->isFieldExists(Item::FIELD_NAME_LAST_ACTIVITY_TIME))
		{
			return $factory;
		}

		return null;
	}

	private function getRowsToProcess(Factory $factory, ?int $lastId): Collection
	{
		$query = $factory->getDataClass()::query();

		$select = [
			Item::FIELD_NAME_ID,
			$factory->getEntityFieldNameByMap(Item::FIELD_NAME_CREATED_TIME),
			$factory->getEntityFieldNameByMap(Item::FIELD_NAME_CREATED_BY),
		];

		if ($factory->isFieldExists(Item::FIELD_NAME_LAST_ACTIVITY_TIME))
		{
			$select[] = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_LAST_ACTIVITY_TIME);
		}
		if ($factory->isFieldExists(Item::FIELD_NAME_LAST_ACTIVITY_BY))
		{
			$select[] = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_LAST_ACTIVITY_BY);
		}

		$query
			->setSelect($select)
			->setOrder([
				Item::FIELD_NAME_ID => 'ASC',
			])
			->setLimit(self::getSingleEntityStepLimit())
		;

		if (!is_null($lastId))
		{
			$query->where(Item::FIELD_NAME_ID, '>', $lastId);
		}

		return $query->exec()->fetchCollection();
	}

	private function processRow(Factory $factory, EntityObject $row): void
	{
		$identifier = new ItemIdentifier($factory->getEntityTypeId(), $row->getId());
		[$lastActivityTime, $lastActivityBy] = $this->monitor->calculateLastActivityInfo($identifier);

		$lastActivityTime ??= $row->get($factory->getEntityFieldNameByMap(Item::FIELD_NAME_CREATED_TIME));
		$lastActivityBy ??= $row->get($factory->getEntityFieldNameByMap(Item::FIELD_NAME_CREATED_BY));

		if ($factory->isFieldExists(Item::FIELD_NAME_LAST_ACTIVITY_TIME))
		{
			$row->set(
				$factory->getEntityFieldNameByMap(Item::FIELD_NAME_LAST_ACTIVITY_TIME),
				$lastActivityTime,
			);
		}

		if ($factory->isFieldExists(Item::FIELD_NAME_LAST_ACTIVITY_BY))
		{
			$row->set(
				$factory->getEntityFieldNameByMap(Item::FIELD_NAME_LAST_ACTIVITY_BY),
				$lastActivityBy,
			);
		}

		$row->save();
	}
	//endregion

	//region Options
	private static function getTypesToProcess(): array
	{
		$value = Option::get('crm', self::OPTION_PREFIX . 'types');
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
			$this->enableLastActivity($entityTypeId);
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
			$this->enableLastActivity($entityTypeId);

			Option::delete(
				'crm',
				[
					'name' => $this->getProgressOptionName($entityTypeId),
				],
			);
		}

		Option::delete('crm', ['name' => self::OPTION_PREFIX . 'types']);
		Option::delete('crm', ['name' => self::OPTION_PREFIX . 'entity_step_limit']);
	}

	private function enableLastActivity(int $entityTypeId): void
	{
		Option::delete(
			'crm',
			[
				'name' => 'enable_last_activity_for_' . mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeId)),
			],
		);
	}

	private function getProgressOptionName(int $entityTypeId): string
	{
		$entityName = \CCrmOwnerType::ResolveName($entityTypeId);

		return self::OPTION_PREFIX . 'progress_' . mb_strtolower($entityName);
	}

	private static function getSingleEntityStepLimit(): int
	{
		// we will process at least 6 types at the same time. but it's possible to have up to 70 types with dynamic types

		return (int)Option::get('crm',  self::OPTION_PREFIX . 'entity_step_limit', 10);
	}
	//endregion

	public static function bindOnCrmModuleInstallIfNeeded(): void
	{
		if (!empty(self::getTypesToProcess()))
		{
			self::addAgent();
		}
	}

	public static function bindForType(int $entityTypeId): void
	{
		Option::set(
			'crm',
			'enable_last_activity_for_' . mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeId)),
			'N'
		);

		$currentTypes = self::getTypesToProcess();
		if (!in_array($entityTypeId, $currentTypes, true))
		{
			$currentTypes[] = $entityTypeId;
			Option::set('crm', self::OPTION_PREFIX . 'types', serialize($currentTypes));
		}

		self::addAgent();
	}

	private static function addAgent(): void
	{
		\CAgent::AddAgent(
		/** @see self::execAgent() */
			"\\Bitrix\\Crm\\Update\\Entity\\LastActivityFields::execAgent();",
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

	public static function onAfterTypeDelete(int $entityTypeId): void
	{
		Option::delete('crm', ['name' => '~last_activity_columns_alter_success_' . $entityTypeId]);
	}

	public static function wereLastActivityColumnsAddedSuccessfullyOnModuleUpdate(int $entityTypeId): bool
	{
		$value = Option::get('crm', '~last_activity_columns_alter_success_' . $entityTypeId, 'Y');

		return ($value === 'Y');
	}
}
