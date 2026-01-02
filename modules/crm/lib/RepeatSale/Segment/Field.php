<?php

namespace Bitrix\Crm\RepeatSale\Segment;

use Bitrix\Crm\Item;
use Bitrix\Crm\RepeatSale\Segment\Controller\RepeatSaleSegmentController;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use CCrmFieldInfoAttr;

final class Field
{
	private static ?bool $isChecked = null;

	public function getInfoForFilter(): array
	{
		if (!$this->check())
		{
			return [];
		}

		return [
			Item::FIELD_NAME_REPEAT_SALE_SEGMENT_ID,
			[
				'type' => 'list',
				'partial' => true,
			],
		];
	}

	public function getPreparedDataForFilter(): array
	{
		$items = [];

		if ($this->check() && Container::getInstance()->getUserPermissions()->repeatSale()->canRead())
		{
			$segments = RepeatSaleSegmentController::getInstance()->getList([
				'select' => ['ID', 'TITLE'],
				'order' => ['TITLE' => 'ASC'],
			]);

			foreach ($segments as $segment)
			{
				$items[$segment->getId()] = $segment->getTitle();
			}
		}

		return [
			'params' => [
				'multiple' => 'Y',
			],
			'items' => $items,
		];
	}

	public function getInfo(): array
	{
		if (!$this->check())
		{
			return [];
		}

		return [
			Item::FIELD_NAME_REPEAT_SALE_SEGMENT_ID => [
				'TYPE' => \Bitrix\Crm\Field::TYPE_STRING,
				'ATTRIBUTES' => [
					CCrmFieldInfoAttr::ReadOnly,
				],
			],
		];
	}

	public function getSqlInfo(int $entityTypeId): array
	{
		if (!$this->check())
		{
			return [];
		}

		$logTableAlias = 'RS_SEGMENT_LOG';

		return [
			'REPEAT_SALE_SEGMENT_ID' => [
				'FIELD' => "{$logTableAlias}.SEGMENT_ID",
				'TYPE' => 'int',
				'FROM' => 'LEFT JOIN b_crm_repeat_sale_log' . " {$logTableAlias} ON"
					. " {$logTableAlias}.ENTITY_TYPE_ID = " . $entityTypeId
					. " AND {$logTableAlias}.ENTITY_ID = L.ID",
			],
		];
	}

	public function getGridHeader(): array
	{
		if (!$this->check())
		{
			return [];
		}

		return [
			'id' => Item::FIELD_NAME_REPEAT_SALE_SEGMENT_ID,
			'name' => Loc::getMessage('CRM_TYPE_ITEM_FIELD_NAME_REPEAT_SALE_SEGMENT_ID'),
			'sort' => mb_strtolower(Item::FIELD_NAME_REPEAT_SALE_SEGMENT_ID),
			'first_order' => 'desc',
			'editable' => false,
			'class' => 'string',
			'default' => false,
		];
	}

	private function check(): bool
	{
		if (self::$isChecked === null)
		{
			$availabilityChecker = Container::getInstance()->getRepeatSaleAvailabilityChecker();

			self::$isChecked = $availabilityChecker->isAvailable() && $availabilityChecker->hasPermission();
		}

		return self::$isChecked;
	}
}
