<?php

namespace Bitrix\Crm\Integration\UI\EntityEditor;

use CCrmOwnerType;

class ContactProvider extends BaseProvider
{
	public function getEntityTypeName(): string
	{
		return CCrmOwnerType::ContactName;
	}

	protected function getComponentName(): string
	{
		return 'bitrix:crm.contact.details';
	}
}
