<?php

namespace Bitrix\CrmMobile\Kanban\ItemPreparer;

use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\CrmMobile\Kanban\Dto\Field;
use Bitrix\CrmMobile\Kanban\ItemPreparer\Counters\ItemCounterFactory;
use Bitrix\Main\Localization\Loc;

abstract class Base
{
	protected const EXCLUDED_FIELDS = [
		'TITLE',
		'DATE_CREATE',
	];

	protected const CRM_STATUS_FIELD_TYPE = 'crm_status';
	protected const CRM_FIELD_TYPE = 'crm';
	protected const IBLOCK_ELEMENT_FIELD_TYPE = 'iblock_element';
	protected const IBLOCK_SECTION_FIELD_TYPE = 'iblock_section';

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

		$fields = $this->prepareFields($item, $params);
		$fields = $this->addClientToFields($fields, $item, $params);
		$fields = $this->orderFields($fields, $item, $params);

		return [
			'id' => $id,
			'data' => [
				'id' => $id,
				'columnId' => $this->getColumnId($item),
				'name' => $this->getItemName($item),
				'date' => $this->getItemDate($item),
				'dateFormatted' => $this->getItemDateFormatted($item),
				'price' => $this->getItemPrice($item),
				'fields' => $fields,
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

	protected function addClientToFields(array $fields, array $item, array $params = []): array
	{
		$clientField = $this->getClientField($item, $params);
		if ($clientField)
		{
			$fields[] = $clientField;
		}

		return $fields;
	}

	protected function getClientField(array $item, array $params = []): ?Field
	{
		$client = $this->getClient($item, $params);
		if (!$client || !empty($client['hidden']))
		{
			return null;
		}

		$isCompanyHidden = true;
		$isContactHidden = true;

		if (!empty($client['company']) && is_array($client['company']))
		{
			$client['company'] = array_filter($client['company'], fn ($item) => empty($item['hiddenInKanbanFields']));
			$isCompanyHidden = count(array_filter($client['company'], fn ($item) => !$item['hidden'] || $item['title'] !== '')) === 0;
		}

		if (!empty($client['contact']) && is_array($client['contact']))
		{
			$client['contact'] = array_filter($client['contact'], fn ($item) => empty($item['hiddenInKanbanFields']));
			$isContactHidden = count(array_filter($client['contact'], fn ($item) => !$item['hidden'] || $item['title'] !== '')) === 0;
		}

		if ($isCompanyHidden && $isContactHidden)
		{
			return null;
		}

		$dtoField = Field::make([
			'name' => 'CLIENT',
			'title' => Loc::getMessage('CRMMOBILE_KANBAN_ITEM_PREPARER_BASE_CLIENT'),
			'type' => 'client',
			'value' => $client,
			'config' => [
				'owner' => [
					'id' => $this->getItemId($item),
				],
				'entityList' => $client,
			],
			'multiple' => false,
		]);
		$this->prepareField($dtoField);

		return $dtoField;
	}

	protected function orderFields(array $fields, array $item, array $params = []): array
	{
		$moneyField = null;
		$clientField = null;

		foreach ($fields as $index => $field)
		{
			if ($field->name === 'OPPORTUNITY')
			{
				$moneyField = $field;
				unset($fields[$index]);
				break;
			}
		}
		foreach ($fields as $index => $field)
		{
			if ($field->name === 'CLIENT')
			{
				$clientField = $field;
				unset($fields[$index]);
				break;
			}
		}

		return [...array_filter([$moneyField, $clientField, ...$fields])];
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
		$itemCounters = ItemCounterFactory::make();

		$res = $itemCounters->counters($item, $this->params, $this->getAssignedById($item));

		$renderLastActivityTime = ($params['renderLastActivityTime'] ?? false);

		return [
			'counters' => $res->getCounters(),
			'activityCounterTotal' => $res->getActivityCounterTotal(),
			'lastActivity' => $this->getLastActivityTimestamp($item),
			'indicator' => $res->getIndicator(),
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

		if ($field->name === 'OPPORTUNITY')
		{
			$field->title = (
				Loc::getMessage("CRMMOBILE_KANBAN_ITEM_PREPARER_BASE_TOTAL_SUM_{$this->getEntityType()}")
				?? Loc::getMessage('CRMMOBILE_KANBAN_ITEM_PREPARER_BASE_TOTAL_SUM')
			);
			$field->config['largeFont'] = true;
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
