<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use CCrmOwnerType;

class SmartInvoice extends DynamicProvider
{
	protected function getEntityTypeName(): string
	{
		return 'smart_invoice';
	}

	protected function getEntityTypeId(): int
	{
		return CCrmOwnerType::SmartInvoice;
	}

	protected function getEntityTypeNameForMakeItemMethod(): string
	{
		return $this->getEntityTypeName();
	}
}
