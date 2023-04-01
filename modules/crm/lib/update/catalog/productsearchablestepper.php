<?php

namespace Bitrix\Crm\Update\Catalog;

use Bitrix\Crm\Product\B24Catalog;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Update\Stepper;

/**
 * Class ProductSearchableContentStepper
 *
 * <code>
 * \Bitrix\Main\Update\Stepper::bindClass('Bitrix\Crm\Update\Catalog\ProductSearchableStepper', 'crm');
 * </code>
 *
 * @package Bitrix\Iblock\Update
 */
final class ProductSearchableStepper extends Stepper
{
	private const OPTION_NAME = 'searchable_content_stepper_status';
	private const PORTION = 30;

	protected static $moduleId = 'iblock';

	public function execute(array &$option): bool
	{
		if (
			Loader::includeModule('search')
			|| !Loader::includeModule('catalog')
			|| !Loader::includeModule('iblock')
			|| !Loader::includeModule('crm')
		)
		{
			return self::FINISH_EXECUTION;
		}

		$status = $this->loadCurrentStatus();
		if (empty($status['count']) || $status['count'] < 0)
		{
			return self::FINISH_EXECUTION;
		}

		$iblockIds = array_filter([
			B24Catalog::getDefaultId(),
			B24Catalog::getDefaultOfferId(),
		]);
		if (empty($iblockIds))
		{
			return self::FINISH_EXECUTION;
		}

		\CIBlock::disableClearTagCache();
		$usedIblockIds = [];

		$newStatus = [
			'count' => $status['count'] ?? 0,
			'steps' => $status['steps'] ?? 0,
		];
		$elements = ElementTable::getList(
			[
				'select' => ['ID', 'NAME', 'IBLOCK_ID', 'MODIFIED_BY', 'TIMESTAMP_X'],
				'filter' => [
					'=ACTIVE' => 'Y',
					'IBLOCK_ID' => $iblockIds,
					'>ID' => $status['lastId'],
				],
				'order' => ['ID' => 'ASC'],
				'limit' => self::PORTION,
			]
		);
		foreach ($elements as $elementRow)
		{
			$usedIblockIds[$elementRow['IBLOCK_ID']] = true;
			$this->indexRow($elementRow);

			$newStatus['lastId'] = (int)$elementRow['ID'];
			$newStatus['steps']++;
		}

		\CIBlock::enableClearTagCache();
		foreach (array_keys($usedIblockIds) as $id)
		{
			\CIBlock::clearIblockTagCache($id);
		}

		if (!empty($newStatus['lastId']))
		{
			Option::set(self::$moduleId, self::OPTION_NAME, serialize($newStatus));
			$option = [
				'count' => $newStatus['count'],
				'steps' => $newStatus['steps'],
			];

			return self::CONTINUE_EXECUTION;
		}

		Option::delete(self::$moduleId, ['name' => self::OPTION_NAME]);

		return self::FINISH_EXECUTION;
	}

	private function loadCurrentStatus(): array
	{
		$status = Option::get(self::$moduleId, self::OPTION_NAME, '');
		$status = $status !== '' ? @unserialize($status, ['allowed_classes' => false]) : [];
		$status = is_array($status) ? $status : [];

		if (empty($status))
		{
			$status = [
				'lastId' => 0,
				'steps' => 0,
				'count' => (int)ElementTable::getCount([
					'=ACTIVE' => 'Y',
				]),
			];
		}

		return $status;
	}

	private function indexRow($elementRow): void
	{
		static $element = null;

		if ($element === null)
		{
			$element = new \CIBlockElement();
		}

		$element->update($elementRow['ID'], $elementRow);
	}
}