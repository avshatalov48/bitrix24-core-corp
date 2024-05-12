<?php

namespace Bitrix\CrmMobile\Kanban\ItemPreparer;

use Bitrix\Crm\Format\Date;
use Bitrix\CrmMobile\Kanban\Client;
use Bitrix\CrmMobile\Kanban\Dto\Field;

class ListPreparer extends Base
{
	public function execute(array $item, array $params = []): array
	{
		$id = $this->getItemId($item);

		if (isset($params['displayValues']))
		{
			$params['displayValues'] = $params['displayValues'][$id] ?? [];
		}

		return parent::execute($item, $params);
	}

	protected function getItemId(array $item): int
	{
		return $item['ID'];
	}

	protected function getColumnId(array $item): ?string
	{
		return null;
	}

	protected function getItemName(array $item): string
	{
		return $item['TITLE'];
	}

	protected function getItemDate(array $item): ?int
	{
		if (!isset($item['CREATED_TIME']))
		{
			return null;
		}

		return $item['CREATED_TIME']->getTimestamp();
	}

	protected function getItemDateFormatted(array $item): string
	{
		if (!isset($item['CREATED_TIME']))
		{
			return '';
		}

		return (new Date())->format($item['CREATED_TIME'], true);
	}

	protected function getItemPrice(array $item): ?float
	{
		return null;
	}

	protected function prepareFields(array $item = [], array $params = []): array
	{
		$fields = [];
		foreach ($params['displayValues'] as $fieldName => $fieldValue)
		{
			$field = $params['fieldsCollection']->getField($fieldName);
			if (!$field || !isset($fieldValue['value']) || $this->isExcludedField($field->getName()))
			{
				continue;
			}

			$dtoField = Field::make([
				'name' => $field->getName(),
				'title' => $field->getTitle(),
				'type' => $field->getType(),
				'value' => $fieldValue['value'],
				'config' => $fieldValue['config'] ?? [],
				'multiple' => $field->isMultiple(),
			]);
			$this->prepareField($dtoField);

			$fields[] = $dtoField;
		}

		return $fields;
	}

	protected function prepareBadges(array $item = [], array $params = []): array
	{
		return [];
	}

	protected function getItemReturn(array $item): bool
	{
		return false;
	}

	protected function getItemReturnApproach(array $item): bool
	{
		return false;
	}

	protected function getSubTitleText(array $item): string
	{
		return '';
	}

	protected function getDescriptionRow(array $item): array
	{
		return [];
	}

	protected function getMoney(array $item): ?array
	{
		return null;
	}

	protected function getAssignedById(array $item): ?int
	{
		return $item['ASSIGNED_BY_ID'];
	}

	protected function getLastActivityTimestamp(array $item): ?int
	{
		return null;
	}

	protected function getSelfContactInfo(array $item, string $type): array
	{
		return Client\Info::get($item, $type, $this->getItemName($item));
	}
}
