<?php

namespace Bitrix\SalesCenter\Fields\Entity;

use Bitrix\Crm\CompanyTable;
use Bitrix\SalesCenter\Driver;
use Bitrix\SalesCenter\Fields\Entity;
use Bitrix\SalesCenter\Fields\Field;
use Bitrix\SalesCenter\Integration\CrmManager;

class Company extends Entity
{
	public function getCode(): string
	{
		return 'COMPANY';
	}

	protected function getUserFieldEntity(): ?string
	{
		return 'CRM_COMPANY';
	}

	public function getFields(): array
	{
		$fields = [
			(new Field('ASSIGNED_BY'))->setEntity(new Assigned()),
		];

		return array_merge($fields, parent::getFields());
	}

	protected function getTableClassName(): ?string
	{
		if(CrmManager::getInstance()->isEnabled())
		{
			return CompanyTable::class;
		}

		return null;
	}
}