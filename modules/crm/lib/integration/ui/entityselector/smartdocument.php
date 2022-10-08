<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

class SmartDocument extends DynamicProvider
{
	protected function getEntityTypeName(): string
	{
		return 'smart_document';
	}

	protected function getEntityTypeId(): int
	{
		return \CCrmOwnerType::SmartDocument;
	}

	protected function getEntityTypeNameForMakeItemMethod()
	{
		return $this->getEntityTypeName();
	}
}