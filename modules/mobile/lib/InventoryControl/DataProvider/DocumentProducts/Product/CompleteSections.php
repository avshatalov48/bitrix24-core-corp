<?php

namespace Bitrix\Mobile\InventoryControl\DataProvider\DocumentProducts\Product;

use Bitrix\Main\Loader;
use Bitrix\Mobile\InventoryControl\Dto\DocumentProductRecord;

Loader::includeModule('iblock');

final class CompleteSections implements Enricher
{
	/**
	 * @param DocumentProductRecord[] $records
	 * @return DocumentProductRecord[]
	 */
	public function enrich(array $records): array
	{
		$allSectionIds = array_unique($this->extractSectionIds($records));
		$sectionsReference = [];
		$result = [];

		if (!empty($allSectionIds))
		{
			$sort = [];
			$filter = [
				'=ID' => $allSectionIds,
				'ACTIVE' => 'Y',
			];
			$select = ['ID', 'NAME'];
			$rows = \CIBlockSection::GetList($sort, $filter, false, $select);
			while ($row = $rows->Fetch())
			{
				$sectionsReference[$row['ID']] = $row;
			}
		}

		foreach ($records as $origRecord)
		{
			$record = clone $origRecord;
			$sections = [];
			foreach ($record->sections as $section)
			{
				if ($sectionsReference[$section['id']])
				{
					$sections[] = [
						'id' => $sectionsReference[$section['id']]['ID'],
						'name' => $sectionsReference[$section['id']]['NAME'],
					];
				}
			}
			$record->sections = $sections;
			$result[] = $record;
		}

		return $result;
	}

	/**
	 * @param DocumentProductRecord[] $records
	 * @return int[]
	 */
	private function extractSectionIds(array $records): array
	{
		$sectionIds = [];
		foreach ($records as $record)
		{
			if (!empty($record->sections))
			{
				foreach ($record->sections as $section)
				{
					$sectionIds[] = (int)$section['id'];
				}
			}
		}
		return array_unique($sectionIds);
	}
}
