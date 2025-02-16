<?php

namespace Bitrix\Crm\Service\Broker;

use Bitrix\Crm\Service\Broker;
use Bitrix\Iblock;
use Bitrix\Main\Loader;
use Bitrix\Main\Type;

class IBlockSection extends Broker
{
	protected function loadEntry(int $id): ?array
	{
		if (!Loader::includeModule('iblock'))
		{
			return null;
		}

		if ($id <= 0)
		{
			return null;
		}

		$row = Iblock\SectionTable::getList([
			'select' => [
				'ID',
				'NAME',
			],
			'filter' => [
				'=ID' => $id,
			],
		])->fetch();

		return (!empty($row) ? $row : null);
	}

	/**
	 * @inheritDoc
	 */
	protected function loadEntries(array $ids): array
	{
		if (!Loader::includeModule('iblock'))
		{
			return [];
		}

		Type\Collection::normalizeArrayValuesByInt($ids);

		if (empty($ids))
		{
			return [];
		}

		$result = [];
		foreach (array_chunk($ids, 500) as $pageIds)
		{
			$iterator = Iblock\SectionTable::getList([
				'select' => [
					'ID',
					'NAME',
				],
				'filter' => [
					'@ID' => $pageIds,
				],
			]);
			
			while ($row = $iterator->fetch())
			{
				$row['ID'] = (int)$row['ID'];
				$result[$row['ID']] = $row;
			}
			unset($row, $iterator);
		}

		return $result;
	}
}
