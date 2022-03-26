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
		[,,$entityId] = explode('_', $documentId);

		return [
			'TYPE' => \CCrmOwnerType::SmartInvoiceName,
			'TYPE_ID' => \CCrmOwnerType::SmartInvoice,
			'ID' => (int)$entityId,
			'DOCUMENT_TYPE' => ['crm', static::class, \CCrmOwnerType::SmartInvoiceName],
		];
	}
}
