<?php

namespace Bitrix\SalesCenter\Fields\Entity;

use Bitrix\Crm\ContactTable;
use Bitrix\SalesCenter\Driver;
use Bitrix\SalesCenter\Fields\Entity;
use Bitrix\SalesCenter\Fields\Field;
use Bitrix\SalesCenter\Integration\CrmManager;

class Contact extends Entity
{
	public function getCode(): string
	{
		return 'CONTACT';
	}

	protected function getUserFieldEntity(): ?string
	{
		return 'CRM_CONTACT';
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
			return ContactTable::class;
		}

		return null;
	}
}