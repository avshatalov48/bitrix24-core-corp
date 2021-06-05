<?php

namespace Bitrix\Crm\Integration\BizProc\Document;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

if(!Loader::includeModule('bizproc'))
{
	return false;
}

class Quote extends Item
{
	public static function getEntityName($entity)
	{
		return Loc::getMessage('CRM_BP_DOCUMENT_QUOTE_ENTITY_NAME');
	}

	protected static function GetDocumentInfo($documentId)
	{
		[$entityType, $entityId] = explode('_', $documentId);

		if ($entityType !== \CCrmOwnerType::QuoteName)
		{
			return false;
		}

		return [
			'TYPE' => $entityType,
			'TYPE_ID' => \CCrmOwnerType::Quote,
			'ID' => (int)$entityId,
			'DOCUMENT_TYPE' => ['crm', static::class, \CCrmOwnerType::QuoteName],
		];
	}
}
