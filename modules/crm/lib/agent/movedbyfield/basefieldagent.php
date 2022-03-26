<?php

namespace Bitrix\Crm\Agent\MovedByField;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Update\Stepper;

abstract class BaseFieldAgent extends Stepper
{
	protected static $moduleId = 'crm';

	public function execute(array &$result)
	{
		$result['steps'] = (int)($result['steps'] ?? 0);

		$limit = $this->getLimit();
		$lastId = ($result['lastId'] ?? 0);
		$processedCount = 0;

		$items = $this->getList($lastId, $limit);

		foreach ($items as $item)
		{
			$lastId = (int)$item['ID'];
			$fieldsToUpdate = $this->getMovedData($item);

			if (!empty($fieldsToUpdate))
			{
				$this->update($lastId, $fieldsToUpdate);
			}

			$result['steps']++;
			$processedCount++;
		}

		$result['lastId'] = $lastId;

		if ($processedCount < $limit)
		{
			$this->onStepperComplete();

			return self::FINISH_EXECUTION;
		}
		else
		{
			return self::CONTINUE_EXECUTION;
		}
	}

	protected function getMovedData(array $fields): array
	{
		$result = [];

		if (!$fields['MOVED_BY_ID']) // if wasn't set yet
		{
			$result['MOVED_BY_ID'] = $fields['MODIFY_BY_ID'];
		}
		if (!$fields['MOVED_TIME'])
		{
			$lastHistoryRecord = $this->getLastHistoryRecord($fields['ID']);
			$result['MOVED_TIME'] = $lastHistoryRecord['CREATED_TIME'] ?? $fields['DATE_MODIFY'];
		}

		return $result;
	}

	protected function getLimit(): int
	{
		return (int)Option::get('crm', 'MovedByFieldAgentLimit', 50);
	}

	abstract protected function onStepperComplete(): void;

	abstract protected function getList(int $lastId, int $limit);

	abstract protected function getLastHistoryRecord(int $id): ?array;

	abstract protected function update(int $id, array $fieldsToUpdate): void;

}