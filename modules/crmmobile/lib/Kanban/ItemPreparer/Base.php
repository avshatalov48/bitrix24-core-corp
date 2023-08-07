<?php


namespace Bitrix\CrmMobile\Kanban\ItemPreparer;

use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\CrmMobile\Kanban\Dto\Field;
use Bitrix\CrmMobile\Kanban\ItemCounter;
use Bitrix\CrmMobile\Kanban\ItemIndicator;

abstract class Base
{
	protected const EXCLUDED_FIELDS = [
		'TITLE',
		'OPPORTUNITY',
		'DATE_CREATE',
	];

	protected const CRM_STATUS_FIELD_TYPE = 'crm_status';
	protected const CRM_FIELD_TYPE = 'crm';
	protected const IBLOCK_ELEMENT_FIELD_TYPE = 'iblock_element';
	protected const IBLOCK_SECTION_FIELD_TYPE = 'iblock_section';

	protected const DEFAULT_COUNT_WITH_RECKON_ACTIVITY = 1;

	protected array $params;
	protected int $entityTypeId;

	public function setParams(array $params): Base
	{
		$this->params = $params;

		return $this;
	}

	public function setEntityTypeId(int $entityTypeId): Base
	{
		$this->entityTypeId = $entityTypeId;

		return $this;
	}

	public function execute(array $item, array $params = []): array
	{
		$id = $this->getItemId($item);
		$entityAttributes = ($params['permissionEntityAttributes'] ?? null);

		return [
			'id' => $id,
			'data' => [
				'id' => $id,
				'columnId' => $this->getColumnId($item),
				'name' => $this->getItemName($item),
				'date' => $this->getItemDate($item),
				'dateFormatted' => $this->getItemDateFormatted($item),
				'price' => $this->getItemPrice($item),
				'fields' => $this->prepareFields($item, $params),
				'badges' => $this->prepareBadges($item, $params),
				'return' => $this->getItemReturn($item),
				'returnApproach' => $this->getItemReturnApproach($item),
				'subTitleText' => $this->getSubTitleText($item),
				'descriptionRow' => $this->getDescriptionRow($item),
				'money' => $this->getMoney($item),
				'client' => $this->getClient($item, $params),
				'permissions' => $this->getPermissions($id, $entityAttributes),
				'counters' => $this->getItemCounters($item, $params),
			],
		];
	}

	protected function getClient(array $item, array $params = []): ?array
	{
		return ($item['client'] ?? null);
	}

	/**
	 * In the future, we can add the necessary permission checks.
	 * Now need to check permissions to edit an element in kanban
	 *
	 * @param int $id
	 * @param array|null $entityAttributes
	 * @return array
	 */
	protected function getPermissions(int $id, ?array $entityAttributes): array
	{
		$entityTypeName = $this->getPermissionEntityTypeName();

		$params = [
			$entityTypeName,
			$id,
			null,
			$entityAttributes,
		];

		return [
			'write' => \CCrmAuthorizationHelper::CheckUpdatePermission(...$params),
			'delete' => \CCrmAuthorizationHelper::CheckDeletePermission(...$params),
		];
	}

	protected function getPermissionEntityTypeName(): string
	{
		$filterParams = ($this->params['filterParams'] ?? []);
		$categoryId = ($filterParams['CATEGORY_ID'] ?? 0);

		return (new PermissionEntityTypeHelper($this->getEntityTypeId()))
			->getPermissionEntityTypeForCategory($categoryId)
		;
	}

	protected function getEntityType(): string
	{
		return \CCrmOwnerType::ResolveName($this->entityTypeId);
	}

	protected function getEntityTypeId(): int
	{
		return $this->entityTypeId;
	}

	protected function getItemCounters(array $item, array $params = []): array
	{
		$counters = [];

		$activityCounterTotal = ($item['activityCounterTotal'] ?? 0);
		$isCurrentUserAssigned = ((int)$this->params['userId'] === $this->getAssignedById($item));

		if (!$isCurrentUserAssigned)
		{
			$counters[] = ItemCounter::getInstance()->getEmptyCounter($activityCounterTotal);
		}
		else
		{
			$isReckonActivityLessItems = $this->params['isReckonActivityLessItems'];
			$activityErrorTotal = (int)($item['activityErrorTotal'] ?? 0);
			$activityIncomingTotal = (int)($item['activityIncomingTotal'] ?? 0);

			$itemCounter = ItemCounter::getInstance();

			if ($activityErrorTotal)
			{
				$counters[] = $itemCounter->getErrorCounter($activityErrorTotal);
			}

			if ($activityIncomingTotal)
			{
				$counters[] = $itemCounter->getIncomingCounter($activityIncomingTotal);
			}

			if (empty($counters))
			{
				if ($isReckonActivityLessItems)
				{
					$counters[] = $itemCounter->getErrorCounter(self::DEFAULT_COUNT_WITH_RECKON_ACTIVITY);
				}
				else
				{
					$counters[] = $itemCounter->getEmptyCounter(0);
				}
			}
		}

		$indicator = null;
		if (!$activityCounterTotal && !empty($item['activityProgress']))
		{
			$userId = (int)$this->params['userId'];

			$activityProgressForCurrentUser = 0;
			if (isset($item['activitiesByUser'][$userId]))
			{
				$activityProgressForCurrentUser = ($item['activitiesByUser'][$userId]['activityProgress'] ?? 0);
			}

			$indicatorInstance = ItemIndicator::getInstance();
			$indicator = (
				$activityProgressForCurrentUser
					? $indicatorInstance->getOwnIndicator()
					: $indicatorInstance->getSomeoneIndicator()
			);
		}

		$renderLastActivityTime = ($params['renderLastActivityTime'] ?? false);

		return [
			'counters' => $counters,
			'activityCounterTotal' => $activityCounterTotal,
			'lastActivity' => $this->getLastActivityTimestamp($item),
			'indicator' => $indicator,
			'skipTimeRender' => !$renderLastActivityTime,
		];
	}

	abstract protected function getAssignedById(array $item): ?int;

	abstract protected function getLastActivityTimestamp(array $item): ?int;

	protected function isExcludedField(string $fieldName): bool
	{
		return in_array($fieldName, static::EXCLUDED_FIELDS, true);
	}

	protected function prepareField(Field $field): void
	{
		$field->params['readOnly'] = true;

		$fields = [
			self::CRM_FIELD_TYPE,
			self::CRM_STATUS_FIELD_TYPE,
			self::IBLOCK_ELEMENT_FIELD_TYPE,
			self::IBLOCK_SECTION_FIELD_TYPE,
		];

		if (in_array($field->type, $fields))
		{
			$field->params['styleName'] = 'field';
		}
	}

	protected function hasVisibleField(array $item, string $fieldName): bool
	{
		foreach ($item['fields'] as $field)
		{
			if ($field['code'] === $fieldName)
			{
				return true;
			}
		}

		return false;
	}

	abstract protected function getItemId(array $item): int;

	abstract protected function getColumnId(array $item): ?string;

	abstract protected function getItemName(array $item): string;

	abstract protected function getItemDate(array $item): ?int;

	abstract protected function getItemDateFormatted(array $item): string;

	abstract protected function getItemPrice(array $item): ?float;

	abstract protected function prepareFields(array $item = [], array $params = []): array;

	abstract protected function prepareBadges(array $item = [], array $params = []): array;

	abstract protected function getItemReturn(array $item): bool;

	abstract protected function getItemReturnApproach(array $item): bool;

	abstract protected function getSubTitleText(array $item): string;

	abstract protected function getDescriptionRow(array $item): array;

	abstract protected function getMoney(array $item): ?array;
}
