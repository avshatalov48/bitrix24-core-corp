<?php

namespace Bitrix\Crm\Integration\BizProc\Document;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

if(!Loader::includeModule('bizproc'))
{
	return;
}

class Dynamic extends Item
{
	public static function getEntityName($entity)
	{
		return Loc::getMessage('CRM_BP_DOCUMENT_DYNAMIC_ENTITY_NAME');
	}

	protected static function GetDocumentInfo($documentId)
	{
		$documentIdParts = explode('_', $documentId);
		$typePrefix = $documentIdParts[0] ?? null;
		$entityTypeId = $documentIdParts[1] ?? null;
		$entityId = $documentIdParts[2] ?? null;

		if (is_null($entityTypeId))
		{
			return false;
		}

		return [
			'TYPE' => $typePrefix . '_' . $entityTypeId,
			'TYPE_ID' => (int)$entityTypeId,
			'ID' => (int)$entityId,
			'DOCUMENT_TYPE' => ['crm', static::class, $typePrefix . '_' . $entityTypeId],
		];
	}
}
