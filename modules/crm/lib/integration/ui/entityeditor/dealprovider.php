<?php

namespace Bitrix\Crm\Integration\UI\EntityEditor;

use CCrmOwnerType;

class DealProvider extends BaseProvider
{
	public function getEntityTypeName(): string
	{
		return CCrmOwnerType::DealName;
	}

	protected function getComponentName(): string
	{
		return 'bitrix:crm.deal.details';
	}
}
