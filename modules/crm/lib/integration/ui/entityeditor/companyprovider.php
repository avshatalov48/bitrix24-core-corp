<?php

namespace Bitrix\Crm\Integration\UI\EntityEditor;

use CCrmOwnerType;

class CompanyProvider extends BaseProvider
{
	public function getEntityTypeName(): string
	{
		return CCrmOwnerType::CompanyName;
	}

	protected function getComponentName(): string
	{
		return 'bitrix:crm.company.details';
	}
}
