<?php

namespace Bitrix\Crm\Integration\Catalog\Contractor;

class StoreDocumentProvider extends Provider
{
	protected static function getComponentName(): string
	{
		return 'catalog.store.document.detail';
	}

	protected static function getDocumentPrimaryField(): string
	{
		return 'DOCUMENT_ID';
	}

	protected static function getTableName(): string
	{
		return StoreDocumentContractorTable::class;
	}
}
