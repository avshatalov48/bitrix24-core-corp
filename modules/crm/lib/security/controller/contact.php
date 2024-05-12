<?php

namespace Bitrix\Crm\Security\Controller;

class Contact extends Base
{
	/** @var string */
	protected static $permissionEntityType = 'CONTACT';

	public function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Contact;
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
			'CATEGORY_ID',
		];
	}

	//region Observable
	public function isObservable(): bool
	{
		return true;
	}
	//endregion
}
