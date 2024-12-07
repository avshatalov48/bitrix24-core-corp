<?php

namespace Bitrix\Tasks\Flow\Option;

use Bitrix\Tasks\Flow\Internal\FlowOptionTable;

class OptionRepository
{
	/**
	 * @param int $flowId
	 * @return array
	 */
	public function getOptions(int $flowId): array
	{
		$queryResult = FlowOptionTable::query()
			->setSelect(['NAME', 'VALUE'])
			->where('FLOW_ID', $flowId)
			->exec()
		;

		if (!$queryResult)
		{
			return [];
		}

		return $queryResult->fetchAll();
	}

	public function save(int $flowId, string $optionName, string $value): void
	{
		$insertFields = [
			'FLOW_ID' => $flowId,
			'NAME' => $optionName,
			'VALUE' => $value,
		];

		$updateFields = [
			'VALUE' => $value,
		];

		$uniqueFields = ['FLOW_ID', 'NAME'];

		FlowOptionTable::merge($insertFields, $updateFields, $uniqueFields);
	}

	public function delete(int $flowId, string $name): void
	{
		FlowOptionTable::deleteByFilter(['FLOW_ID' => $flowId, 'NAME' => $name]);
	}

	public function deleteAll(int $flowId): void
	{
		FlowOptionTable::deleteByFilter(['FLOW_ID' => $flowId]);
	}
}