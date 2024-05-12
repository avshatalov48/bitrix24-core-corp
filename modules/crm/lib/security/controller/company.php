<?php

namespace Bitrix\Crm\Security\Controller;

class Company extends Base
{
	public function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Company;
	}

	public function hasCategories(): bool
	{
		return true;
	}

	protected function extractCategoryFromFields(array $fields): int
	{
		return (isset($fields['CATEGORY_ID']) ? (int)$fields['CATEGORY_ID'] : 0);
	}

	protected function getSelectFields(): array
	{
		return [
			'ID',
			'ASSIGNED_BY_ID',
			'OPENED',
			'IS_MY_COMPANY',
			'CATEGORY_ID',
		];
	}

	protected function extractIsAlwaysReadableFromFields(array $fields): bool
	{
		return (isset($fields['IS_MY_COMPANY']) && $fields['IS_MY_COMPANY'] === 'Y');
	}

	//region Observable
	public function isObservable(): bool
	{
		return true;
	}
	//endregion
}
