<?php

namespace Bitrix\SalesCenter\Fields\Entity;

use Bitrix\Crm\DealTable;
use Bitrix\SalesCenter\Driver;
use Bitrix\SalesCenter\Fields\Entity;
use Bitrix\SalesCenter\Fields\Field;
use Bitrix\SalesCenter\Integration\CrmManager;

class Deal extends Entity
{
	public function getCode(): string
	{
		return 'DEAL';
	}

	protected function getUserFieldEntity(): ?string
	{
		return 'CRM_DEAL';
	}

	public function getFields(): array
	{
		$fields = [
			(new Field('COMPANY'))->setEntity(Driver::getInstance()->getFieldsManager()->getEntityByCode('COMPANY')),
			(new Field('CONTACT'))->setEntity(Driver::getInstance()->getFieldsManager()->getEntityByCode('CONTACT')),
			(new Field('ASSIGNED_BY'))->setEntity(new Assigned()),
		];

		return array_merge($fields, parent::getFields());
	}

	protected function getTableClassName(): ?string
	{
		if(CrmManager::getInstance()->isEnabled())
		{
			return DealTable::class;
		}

		return null;
	}
}