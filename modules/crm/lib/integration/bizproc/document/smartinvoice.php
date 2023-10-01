<?php

namespace Bitrix\Crm\Integration\BizProc\Document;

use Bitrix\Main\Loader;

if(!Loader::includeModule('bizproc'))
{
	return;
}

class SmartInvoice extends Item
{
	public static function getEntityName($entity)
	{
		return \CCrmOwnerType::GetCategoryCaption(\CCrmOwnerType::SmartInvoice);
	}

	protected static function GetDocumentInfo($documentId)
	{
		$documentIdParts = explode('_', $documentId);

		return [
			'TYPE' => \CCrmOwnerType::SmartInvoiceName,
			'TYPE_ID' => \CCrmOwnerType::SmartInvoice,
			'ID' => (int)($documentIdParts[2] ?? 0),
			'DOCUMENT_TYPE' => ['crm', static::class, \CCrmOwnerType::SmartInvoiceName],
		];
	}
}
