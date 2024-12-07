<?php

namespace Bitrix\Crm\Agent\Semantics;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\StatusTable;
use CCrmStatus;

final class EntityStageSemanticsFixAgent extends AgentBase
{
	public static function doRun(): bool
	{
		$list = self::getGroupedData();
		foreach ($list as $group)
		{
			if (self::isGroupNotSupportedSemantics($group))
			{
				continue;
			}

			self::fixSemantics($group);
		}

		return false;
	}

	private static function getGroupedData(): array
	{
		$result = [];

		$iterator = StatusTable::query()
			->setSelect([
				'ID',
				'ENTITY_ID',
				'SORT',
				'SEMANTICS',
			])
			->whereNotIn('ENTITY_ID', CCrmStatus::getAllowedInnerConfigTypes())
			->setOrder([
				'ENTITY_ID' => 'ASC',
				'SORT' => 'ASC',
			])
			->setGroup([
				'ENTITY_ID',
			])
			->exec()
		;
		while ($item = $iterator->fetch())
		{
			$lastEntityId = (string)$item['ENTITY_ID'];
			unset($item['ENTITY_ID']);
			$result[$lastEntityId][] = $item;
		}

		return $result;
	}

	private static function isGroupNotSupportedSemantics(array $group): bool
	{
		$filtered = array_filter($group, static fn(array $row) => !empty($row['SEMANTICS']));

		return count($filtered) === 0;
	}

	private static function fixSemantics(array $group): void
	{
		$index = array_key_last(
			array_filter(
				$group,
				static fn(array $row) => $row['SEMANTICS'] === PhaseSemantics::FAILURE
			)
		);
		if (!isset($index))
		{
			return;
		}

		$brokenFailedIds = array_map(
			static fn(array $row) => empty($row['SEMANTICS']) ? $row['ID'] : null,
			array_slice($group, $index + 1)
		);
		$brokenFailedIds = array_values(array_filter($brokenFailedIds));

		if (empty($brokenFailedIds))
		{
			return;
		}

		StatusTable::updateMulti($brokenFailedIds, ['SEMANTICS' => PhaseSemantics::FAILURE]);
	}
}
