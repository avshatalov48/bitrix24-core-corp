<?php

namespace Bitrix\Mobile\Integration\Catalog\ProductWizard;

use Bitrix\Main\Loader;

Loader::requireModule('catalog');

final class ConfigQuery
{
	private const MAX_DICTIONARY_ITEMS = 500;

	private string $wizardType;

	public function __construct(string $wizardType)
	{
		$this->wizardType = $wizardType;
	}

	public function execute(): array
	{
		if ($this->wizardType === 'store')
		{
			return [
				'dictionaries' => [
					'stores' => $this->getStoresList(),
					'measures' => $this->getMeasuresList(),
				],
			];
		}

		return [];
	}

	private function getStoresList(): array
	{
		$result = [];

		$stores = \CCatalogStore::GetList(
			[
				'SORT' => 'ASC',
			],
			[
				'ACTIVE' => 'Y',
			],
			false,
			['nTopCount' => self::MAX_DICTIONARY_ITEMS],
			['ID', 'TITLE', 'ADDRESS','IS_DEFAULT',]
		);
		while ($store = $stores->Fetch())
		{
			$result[] = [
				'id' => $store['ID'],
				'title' => $store['TITLE'] == '' ? $store['ADDRESS'] : $store['TITLE'],
				'type' => 'store',
				'isDefault' => $store['IS_DEFAULT'] == 'Y',
			];
		}

		return $result;
	}

	private function getMeasuresList(): array
	{
		$result = [];

		$measures = \CCatalogMeasure::getList(
			[
				'CODE' => 'ASC'
			],
			[],
			false,
			['nTopCount' => self::MAX_DICTIONARY_ITEMS],
			['CODE', 'SYMBOL_RUS', 'SYMBOL_INTL', 'IS_DEFAULT', ]
		);

		while ($measure = $measures->Fetch())
		{
			$result[] = [
				'value' => (int)$measure['CODE'],
				'isDefault' => $measure['IS_DEFAULT'] == 'Y',
				'name' => $measure['SYMBOL_RUS'] ?? $measure['SYMBOL_INTL'],
			];
		}

		return $result;
	}
}
