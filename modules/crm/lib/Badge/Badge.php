<?php

namespace Bitrix\Crm\Badge;

use Bitrix\Crm\Badge\Model\BadgeTable;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Result;

abstract class Badge
{
	protected string $value;
	protected ?array $settings = null;

	public const CALL_STATUS_TYPE = 'call_status';
	public const PAYMENT_STATUS_TYPE = 'payment_status';
	public const OPENLINE_STATUS_TYPE = 'open_line_status';
	public const REST_APP_TYPE = 'rest_app_status';
	public const SMS_STATUS_TYPE = 'sms_status';
	public const CALENDAR_SHARING_STATUS_TYPE = 'calendar_sharing_status';
	public const TASK_STATUS_TYPE = 'task_status';
	public const MAIL_MESSAGE_DELIVERY_STATUS_TYPE = 'mail_message_delivery_status';
	public const AI_CALL_FIELDS_FILLING_RESULT = 'ai_call_fields_filling_result';
	public const TODO_STATUS_TYPE = 'todo_status';
	public const BIZPROC_WORKFLOW_STATUS_TYPE = 'workflow_status';
	public const WORKFLOW_COMMENT_STATUS_TYPE = 'workflow_comment_status';
	public const COPILOT_CALL_ASSESSMENT_STATUS_TYPE = 'copilot_call_assessment_status';
	public const AI_CALL_SCORING_STATUS = 'ai_call_scoring_status';
	public const BOOKING_STATUS_TYPE = 'booking_status';

	public static function createByType(string $type, string $value): Badge
	{
		return Factory::getBadgeInstance($type, $value);
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

				$hint = $this->getSettings()['HINT'] ?? null;
				if (!empty($hint))
				{
					$result['hint'] = $hint;
				}

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
		if ($this->isBound($itemIdentifier, $sourceItemIdentifier))
		{
			return new Result();
		}

		$result = BadgeTable::add(
			$this->prepareBadgeTableData(
				$itemIdentifier,
				$sourceItemIdentifier,
			),
		);

		if ($result->isSuccess())
		{
			Container::getInstance()->getPullEventsQueue()->onBadgeChange($itemIdentifier);
		}

		return $result;
	}

	public function isBound(ItemIdentifier $itemIdentifier, SourceIdentifier $sourceItemIdentifier): bool
	{
		$query = BadgeTable::query()
			->setSelect(['ID'])
			->setLimit(1)
		;

		$data = $this->prepareBadgeTableData($itemIdentifier,$sourceItemIdentifier);
		foreach ($data as $column => $value)
		{
			$query->where($column, $value);
		}

		return (bool)$query->exec()->fetch();
	}

	public function upsert(ItemIdentifier $itemIdentifier, SourceIdentifier $sourceItemIdentifier): void
	{
		$this->unbind($itemIdentifier, $sourceItemIdentifier);
		$this->bind($itemIdentifier, $sourceItemIdentifier);
	}

	public function unbind(ItemIdentifier $itemIdentifier, SourceIdentifier $sourceItemIdentifier): void
	{
		BadgeTable::deleteByAllIdentifier($itemIdentifier, $sourceItemIdentifier, $this->getType(), $this->getValue());
		Container::getInstance()->getPullEventsQueue()->onBadgeChange($itemIdentifier);
	}

	public function unbindWithAnyValue(ItemIdentifier $itemIdentifier, SourceIdentifier $sourceItemIdentifier): void
	{
		BadgeTable::deleteByIdentifiersAndType($itemIdentifier, $sourceItemIdentifier, $this->getType());
		Container::getInstance()->getPullEventsQueue()->onBadgeChange($itemIdentifier);
	}

	public static function deleteByEntity(ItemIdentifier $itemIdentifier, string $type = null, string $value = null): void
	{
		BadgeTable::deleteByEntity($itemIdentifier, $type, $value);
		Container::getInstance()->getPullEventsQueue()->onBadgeChange($itemIdentifier);
	}

	public static function deleteBySource(SourceIdentifier $sourceItemIdentifier): void
	{
		BadgeTable::deleteBySource($sourceItemIdentifier);
		// no realtime here, we don't know targets :(
		// for now it's okay, but it can change in the future
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

	protected function prepareBadgeTableData(ItemIdentifier $itemIdentifier, SourceIdentifier $sourceIdentifier): array
	{
		return [
			'TYPE' => $this->getType(),
			'VALUE' => $this->getValue(),
			'ENTITY_TYPE_ID' => $itemIdentifier->getEntityTypeId(),
			'ENTITY_ID' => $itemIdentifier->getEntityId(),
			'SOURCE_PROVIDER_ID' => $sourceIdentifier->getProviderId(),
			'SOURCE_ENTITY_TYPE_ID' => $sourceIdentifier->getEntityTypeId(),
			'SOURCE_ENTITY_ID' => $sourceIdentifier->getEntityId(),
			'SETTINGS' => $this->getSettings(),
		];
	}

	public function getValue(): string
	{
		return $this->value;
	}

	public function setSettings(?array $settings): Badge
	{
		$this->settings = $settings;

		return $this;
	}

	public function getSettings(): ?array
	{
		return $this->settings;
	}
}
