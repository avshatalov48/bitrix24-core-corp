<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\ItemIdentifier;

Loc::loadMessages(__FILE__);

/**
 * Class OrderCheckController
 * @package Bitrix\Crm\Timeline
 */
class OrderCheckController extends EntityController
{
	/**
	 * @inheritDoc
	 */
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::OrderCheck;
	}

	/**
	 * @inheritDoc
	 */
	public function prepareHistoryDataModel(array $data, array $options = null)
	{
		if (isset($data['SETTINGS']['PRINTED']))
		{
			$data['PRINTED'] = $data['SETTINGS']['PRINTED'];
		}

		if (isset($data['SETTINGS']['PRINTING']))
		{
			$data['PRINTING'] = $data['SETTINGS']['PRINTING'];
		}

		if (isset($data['SETTINGS']['ERROR_MESSAGE']))
		{
			$data['ERROR_MESSAGE'] = $data['SETTINGS']['ERROR_MESSAGE'];
		}

		unset($data['SETTINGS']);

		return parent::prepareHistoryDataModel($data, $options);
	}

	protected static function resolveCreatorID(array $fields): int
	{
		$authorId = 0;
		if (isset($fields['CREATED_BY']))
		{
			$authorId = (int)$fields['CREATED_BY'];
		}

		if ($authorId <= 0 && isset($fields['RESPONSIBLE_ID']))
		{
			$authorId = (int)$fields['RESPONSIBLE_ID'];
		}

		if ($authorId <= 0)
		{
			$authorId = (int)self::getDefaultAuthorId();
		}

		return $authorId;
	}

	public function onSendCheckToIm(int $ownerId, array $params): void
	{
		$bindings = $params['BINDINGS'] ?? [];
		$settings = $params['SETTINGS'] ?? [];
		$orderFields = $params['ORDER_FIELDS'] ?? [];

		$timelineEntryId = OrderCheckEntry::create([
			'ENTITY_ID' => $ownerId,
			'TYPE_CATEGORY_ID' => TimelineType::MARK,
			'AUTHOR_ID' => self::resolveCreatorID($orderFields),
			'SETTINGS' => $settings,
			'BINDINGS' => $bindings
		]);

		foreach($bindings as $binding)
		{
			$this->sendPullEventOnAdd(
				new ItemIdentifier($binding['ENTITY_TYPE_ID'], $binding['ENTITY_ID']),
				$timelineEntryId
			);
		}
	}

	public function onPrintCheck(int $ownerId, array $params): void
	{
		$bindings = $params['BINDINGS'] ?? [];
		$settings = $params['SETTINGS'] ?? [];
		$orderFields = $params['ORDER_FIELDS'] ?? [];

		$timelineEntryId = OrderCheckEntry::create([
			'ENTITY_ID' => $ownerId,
			'TYPE_CATEGORY_ID' => TimelineType::UNDEFINED,
			'AUTHOR_ID' => self::resolveCreatorID($orderFields),
			'SETTINGS' => $settings,
			'BINDINGS' => $bindings,
		]);

		foreach ($bindings as $binding)
		{
			$this->sendPullEventOnAdd(
				new ItemIdentifier($binding['ENTITY_TYPE_ID'], $binding['ENTITY_ID']),
				$timelineEntryId
			);
		}
	}

	public function onPrintingCheck(int $ownerId, array $params): void
	{
		$bindings = $params['BINDINGS'] ?? [];
		$settings = $params['SETTINGS'] ?? [];
		$orderFields = $params['ORDER_FIELDS'] ?? [];

		$timelineEntryId = OrderCheckEntry::create([
			'ENTITY_ID' => $ownerId,
			'TYPE_CATEGORY_ID' => TimelineType::MARK,
			'AUTHOR_ID' => self::resolveCreatorID($orderFields),
			'SETTINGS' => $settings,
			'BINDINGS' => $bindings,
		]);

		foreach ($bindings as $binding)
		{
			$this->sendPullEventOnAdd(
				new ItemIdentifier($binding['ENTITY_TYPE_ID'], $binding['ENTITY_ID']),
				$timelineEntryId
			);
		}
	}

	public function onCheckFailure(array $params): void
	{
		$bindings = $params['BINDINGS'] ?? [];
		$settings = $params['SETTINGS'] ?? [];
		$orderFields = $params['ORDER_FIELDS'] ?? [];

		$timelineEntryId = OrderCheckEntry::create([
			'TYPE_CATEGORY_ID' => TimelineType::UNDEFINED,
			'AUTHOR_ID' => self::resolveCreatorID($orderFields),
			'SETTINGS' => $settings,
			'BINDINGS' => $bindings,
		]);

		foreach ($bindings as $binding)
		{
			$this->sendPullEventOnAdd(
				new ItemIdentifier($binding['ENTITY_TYPE_ID'], $binding['ENTITY_ID']),
				$timelineEntryId
			);
		}
	}
}
