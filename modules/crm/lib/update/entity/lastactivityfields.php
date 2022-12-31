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
	protected static $moduleId = 'crm';

	private Factory $factory;
	private Monitor $monitor;

	function execute(array &$option)
	{
		if (!$this->initFactory())
		{
			return self::FINISH_EXECUTION;
		}

		$this->initMonitor();

		$lastId = (isset($option['lastId']) && is_numeric($option['lastId'])) ? (int)$option['lastId'] : null;

		$processedCount = 0;
		foreach ($this->getRowsToProcess(self::getSingleStepLimit(), $lastId) as $row)
		{
			$this->processRow($row);

			$lastId = $row->getId();
			$processedCount++;
		}

		$option['lastId'] = $lastId;

		if ($processedCount >= self::getSingleStepLimit())
		{
			return self::CONTINUE_EXECUTION;
		}

		// default value for this option is Y. remove unnecessary data
		Option::delete(
			'crm',
			[
				'name' => 'enable_last_activity_for_' . mb_strtolower($this->factory->getEntityName()),
			],
		);

		return self::FINISH_EXECUTION;
	}

	private function initFactory(): bool
	{
		$entityTypeId = $this->getOuterParams()[0] ?? \CCrmOwnerType::Undefined;
		if (!\CCrmOwnerType::IsDefined($entityTypeId))
		{
			return false;
		}

		$factory = Container::getInstance()->getFactory((int)$entityTypeId);
		if (!$factory || !$factory->isFieldExists(Item::FIELD_NAME_LAST_ACTIVITY_TIME))
		{
			return false;
		}

		$this->factory = $factory;

		return true;
	}

	private function initMonitor(): void
	{
		Monitor::onLastActivityRecalculationByAgent();

		$this->monitor = Monitor::getInstance();
	}

	private function getRowsToProcess(int $singleStepLimit, ?int $lastId): Collection
	{
		$query = $this->factory->getDataClass()::query();

		$select = [
			Item::FIELD_NAME_ID,
			$this->factory->getEntityFieldNameByMap(Item::FIELD_NAME_CREATED_TIME),
			$this->factory->getEntityFieldNameByMap(Item::FIELD_NAME_CREATED_BY),
		];

		if ($this->factory->isFieldExists(Item::FIELD_NAME_LAST_ACTIVITY_TIME))
		{
			$select[] = $this->factory->getEntityFieldNameByMap(Item::FIELD_NAME_LAST_ACTIVITY_TIME);
		}
		if ($this->factory->isFieldExists(Item::FIELD_NAME_LAST_ACTIVITY_BY))
		{
			$select[] = $this->factory->getEntityFieldNameByMap(Item::FIELD_NAME_LAST_ACTIVITY_BY);
		}

		$query
			->setSelect($select)
			->setOrder([
				Item::FIELD_NAME_ID => 'ASC',
			])
			->setLimit($singleStepLimit)
		;

		if (!is_null($lastId))
		{
			$query->where(Item::FIELD_NAME_ID, '>', $lastId);
		}

		return $query->exec()->fetchCollection();
	}

	private function processRow(EntityObject $row): void
	{
		$identifier = new ItemIdentifier($this->factory->getEntityTypeId(), $row->getId());
		[$lastActivityTime, $lastActivityBy] = $this->monitor->calculateLastActivityInfo($identifier);

		$lastActivityTime ??= $row->get($this->factory->getEntityFieldNameByMap(Item::FIELD_NAME_CREATED_TIME));
		$lastActivityBy ??= $row->get($this->factory->getEntityFieldNameByMap(Item::FIELD_NAME_CREATED_BY));

		if ($this->factory->isFieldExists(Item::FIELD_NAME_LAST_ACTIVITY_TIME))
		{
			$row->set(
				$this->factory->getEntityFieldNameByMap(Item::FIELD_NAME_LAST_ACTIVITY_TIME),
				$lastActivityTime,
			);
		}

		if ($this->factory->isFieldExists(Item::FIELD_NAME_LAST_ACTIVITY_BY))
		{
			$row->set(
				$this->factory->getEntityFieldNameByMap(Item::FIELD_NAME_LAST_ACTIVITY_BY),
				$lastActivityBy,
			);
		}

		$row->save();
	}

	private static function getSingleStepLimit(): int
	{
		return (int)Option::get('crm', 'update_last_activity_stepper_step_limit', 25);
	}
}
