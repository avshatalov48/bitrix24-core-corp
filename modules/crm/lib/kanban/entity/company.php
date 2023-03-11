<?php

namespace Bitrix\Crm\Kanban\Entity;

use Bitrix\Crm\Filter;
use Bitrix\Crm\Item;
use Bitrix\Crm\Kanban\Entity;

class Company extends Entity
{
	public function getTypeName(): string
	{
		return \CCrmOwnerType::CompanyName;
	}

	public function isCustomPriceFieldsSupported(): bool
	{
		return false;
	}

	public function isTotalPriceSupported(): bool
	{
		return false;
	}

	public function prepareItemCommonFields(array $item): array
	{
		$fields = parent::prepareItemCommonFields($item);

		$fields['ASSIGNED_BY'] = $item['ASSIGNED_BY_ID'];

		return $fields;
	}

	public function getItemsSelectPreset(): array
	{
		return [
			'ID',
			Item::FIELD_NAME_LAST_ACTIVITY_TIME,
			Item::FIELD_NAME_LAST_ACTIVITY_BY,
		];
	}

	public function getFilterPresets(): array
	{
		return (new Filter\Preset\Company())
			->setDefaultValues($this->getFilter()->getDefaultFieldIDs())
			->getDefaultPresets()
			;
	}

	public function getAdditionalEditFields(): array
	{
		return (array)$this->getAdditionalEditFieldsFromOptions();
	}

	protected function getDetailComponentName(): ?string
	{
		return 'bitrix:crm.company.details';
	}

	public function getTableAlias(): string
	{
		return \CCrmContact::TABLE_ALIAS;
	}
}
