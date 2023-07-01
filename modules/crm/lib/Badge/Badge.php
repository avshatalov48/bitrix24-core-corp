<?php

namespace Bitrix\Crm\Badge;

use Bitrix\Crm\Badge\Model\BadgeTable;
use Bitrix\Crm\Badge\Type\CalendarSharingStatus;
use Bitrix\Crm\Badge\Type\CallStatus;
use Bitrix\Crm\Badge\Type\OpenLineStatus;
use Bitrix\Crm\Badge\Type\PaymentStatus;
use Bitrix\Crm\Badge\Type\RestAppStatus;
use Bitrix\Crm\Badge\Type\SmsStatus;
use Bitrix\Crm\Badge\Type\TaskStatus;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Result;

abstract class Badge
{
	protected string $value;

	public const CALL_STATUS_TYPE = 'call_status';
	public const PAYMENT_STATUS_TYPE = 'payment_status';
	public const OPENLINE_STATUS_TYPE = 'open_line_status';
	public const REST_APP_TYPE = 'rest_app_status';
	public const SMS_STATUS_TYPE = 'sms_status';
	public const CALENDAR_SHARING_STATUS_TYPE = 'calendar_sharing_status';
	public const TASK_STATUS_TYPE = 'task_status';

	public static function createByType(string $type, string $value): Badge
	{
		return self::getInstance($type, $value);
	}

	protected static function getInstance(string $type, string $value): Badge
	{
		if ($type === self::CALL_STATUS_TYPE)
		{
			return new CallStatus($value);
		}

		if ($type === self::PAYMENT_STATUS_TYPE)
		{
			return new PaymentStatus($value);
		}

		if ($type === self::OPENLINE_STATUS_TYPE)
		{
			return new OpenLineStatus($value);
		}

		if ($type === self::REST_APP_TYPE)
		{
			return new RestAppStatus($value);
		}

		if ($type === self::SMS_STATUS_TYPE)
		{
			return new SmsStatus($value);
		}

		if ($type === self::CALENDAR_SHARING_STATUS_TYPE)
		{
			return new CalendarSharingStatus($value);
		}

		if ($type === self::TASK_STATUS_TYPE)
		{
			return new TaskStatus($value);
		}

		throw new ArgumentException('Unknown badge type: ' . $type);
	}

	public function __construct(string $value)
	{
		if (!in_array($value, $this->getValuesFromMap(), true))
		{
			throw new ArgumentException('Unknown badge value: ' . $value . ' for type: ' . $this->getType());
		}

		$this->value = $value;
	}

	private function getValuesFromMap(): array
	{
		$result = [];

		$valuesList = $this->getValuesMap();
		foreach ($valuesList as $item)
		{
			$result[] = $item->getValue();
		}

		return $result;
	}

	public function getConfigFromMap(): array
	{
		$result = [
			'fieldName' => $this->getFieldName(),
		];

		$value = $this->getValue();
		$valuesList = $this->getValuesMap();

		foreach ($valuesList as $valueItem)
		{
			if ($value === $valueItem->getValue())
			{
				$result = array_merge($result, $valueItem->toArray());
				break;
			}
		}

		return $result;
	}

	/**
	 * @return ValueItem[]
	 */
	abstract public function getValuesMap(): array;

	abstract public function getFieldName(): string;
	abstract public function getType(): string;

	public function bind(ItemIdentifier $itemIdentifier, SourceIdentifier $sourceItemIdentifier): Result
	{
		$data = [
			'TYPE' => $this->getType(),
			'VALUE' => $this->getValue(),
			'ENTITY_TYPE_ID' => $itemIdentifier->getEntityTypeId(),
			'ENTITY_ID' => $itemIdentifier->getEntityId(),
			'SOURCE_PROVIDER_ID' => $sourceItemIdentifier->getProviderId(),
			'SOURCE_ENTITY_TYPE_ID' => $sourceItemIdentifier->getEntityTypeId(),
			'SOURCE_ENTITY_ID' => $sourceItemIdentifier->getEntityId(),
		];

		$query =
			BadgeTable::query()
				->setSelect(['ID'])
				->setLimit(1)
		;
		foreach ($data as $column => $value)
		{
			$query->where($column, $value);
		}

		if ($query->exec()->fetch())
		{
			return new Result();
		}

		return BadgeTable::add($data);
	}

	public function upsert(ItemIdentifier $itemIdentifier, SourceIdentifier $sourceItemIdentifier): void
	{
		$this->unbind($itemIdentifier, $sourceItemIdentifier);
		$this->bind($itemIdentifier, $sourceItemIdentifier);
	}

	public function unbind(ItemIdentifier $itemIdentifier, SourceIdentifier $sourceItemIdentifier): void
	{
		BadgeTable::deleteByAllIdentifier($itemIdentifier, $sourceItemIdentifier, $this->getType(), $this->getValue());
	}

	public function unbindWithAnyValue(ItemIdentifier $itemIdentifier, SourceIdentifier $sourceItemIdentifier): void
	{
		BadgeTable::deleteByIdentifiersAndType($itemIdentifier, $sourceItemIdentifier, $this->getType());
	}

	public static function deleteByEntity(ItemIdentifier $itemIdentifier, string $type = null, string $value = null): void
	{
		BadgeTable::deleteByEntity($itemIdentifier, $type, $value);
	}

	public static function deleteBySource(SourceIdentifier $sourceItemIdentifier): void
	{
		BadgeTable::deleteBySource($sourceItemIdentifier);
	}

	/**
	 * Rebind all badges from one entity to another
	 *
	 * @param ItemIdentifier $oldEntity
	 * @param ItemIdentifier $newEntity
	 * @return void
	 */
	public static function rebindEntity(ItemIdentifier $oldEntity, ItemIdentifier $newEntity): void
	{
		$dbResult = BadgeTable::query()
			->where('ENTITY_TYPE_ID', $oldEntity->getEntityTypeId())
			->where('ENTITY_ID', $oldEntity->getEntityId())
			->exec()
		;

		while ($row = $dbResult->fetchObject())
		{
			$row
				->set('ENTITY_TYPE_ID', $newEntity->getEntityTypeId())
				->set('ENTITY_ID', $newEntity->getEntityId())
			;

			$row->save();
		}
	}

	public static function rebindSource(SourceIdentifier $oldSource, SourceIdentifier $newSource): void
	{
		$dbResult = BadgeTable::query()
			->where('SOURCE_PROVIDER_ID', $oldSource->getProviderId())
			->where('SOURCE_ENTITY_TYPE_ID', $oldSource->getEntityTypeId())
			->where('SOURCE_ENTITY_ID', $oldSource->getEntityId())
			->exec()
		;

		while ($row = $dbResult->fetchObject())
		{
			$row
				->set('SOURCE_PROVIDER_ID', $newSource->getProviderId())
				->set('SOURCE_ENTITY_TYPE_ID', $newSource->getEntityTypeId())
				->set('SOURCE_ENTITY_ID', $newSource->getEntityId())
			;

			$row->save();
		}
	}

	/**
	 * @return mixed|string
	 */
	public function getValue(): string
	{
		return $this->value;
	}
}
