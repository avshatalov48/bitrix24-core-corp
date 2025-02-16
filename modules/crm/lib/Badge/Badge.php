<?php

namespace Bitrix\Crm\Badge;

use Bitrix\Crm\Badge\Model\BadgeTable;
use Bitrix\Crm\Badge\Type\AiCallFieldsFillingResult;
use Bitrix\Crm\Badge\Type\AiCallScoringStatus;
use Bitrix\Crm\Badge\Type\BizprocWorkflowStatus;
use Bitrix\Crm\Badge\Type\CalendarSharingStatus;
use Bitrix\Crm\Badge\Type\CallStatus;
use Bitrix\Crm\Badge\Type\CopilotCallAssessmentStatus;
use Bitrix\Crm\Badge\Type\MailMessageDeliveryStatus;
use Bitrix\Crm\Badge\Type\OpenLineStatus;
use Bitrix\Crm\Badge\Type\PaymentStatus;
use Bitrix\Crm\Badge\Type\RestAppStatus;
use Bitrix\Crm\Badge\Type\SmsStatus;
use Bitrix\Crm\Badge\Type\TaskStatus;
use Bitrix\Crm\Badge\Type\TodoStatus;
use Bitrix\Crm\Badge\Type\WorkflowCommentStatus;
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
	public const MAIL_MESSAGE_DELIVERY_STATUS_TYPE = 'mail_message_delivery_status';
	public const AI_CALL_FIELDS_FILLING_RESULT = 'ai_call_fields_filling_result';
	public const TODO_STATUS_TYPE = 'todo_status';
	public const BIZPROC_WORKFLOW_STATUS_TYPE = 'workflow_status';
	public const WORKFLOW_COMMENT_STATUS_TYPE = 'workflow_comment_status';
	public const COPILOT_CALL_ASSESSMENT_STATUS_TYPE = 'copilot_call_assessment_status';
	public const AI_CALL_SCORING_STATUS = 'ai_call_scoring_status';

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

		if ($type === self::MAIL_MESSAGE_DELIVERY_STATUS_TYPE)
		{
			return new MailMessageDeliveryStatus($value);
		}

		if ($type === self::AI_CALL_FIELDS_FILLING_RESULT)
		{
			return new AiCallFieldsFillingResult($value);
		}

		if ($type === self::BIZPROC_WORKFLOW_STATUS_TYPE)
		{
			return new BizprocWorkflowStatus($value);
		}

		if ($type === self::WORKFLOW_COMMENT_STATUS_TYPE)
		{
			return new WorkflowCommentStatus($value);
		}

		if ($type === self::TODO_STATUS_TYPE)
		{
			return new TodoStatus($value);
		}

		if ($type === self::COPILOT_CALL_ASSESSMENT_STATUS_TYPE)
		{
			return new CopilotCallAssessmentStatus($value);
		}

		if ($type === self::AI_CALL_SCORING_STATUS)
		{
			return new AiCallScoringStatus($value);
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
		if ($this->isBound($itemIdentifier, $sourceItemIdentifier))
		{
			return new Result();
		}

		return BadgeTable::add(
			$this->prepareBadgeTableData(
				$itemIdentifier,
				$sourceItemIdentifier,
			),
		);
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
		];
	}

	public function getValue(): string
	{
		return $this->value;
	}
}
