<?php

namespace Bitrix\Crm\Integration\BizProc\Document;

use Bitrix\Main\Localization\Loc;

class SmartDocument extends Item
{
	public static function getEntityName($entity)
	{
		return Loc::getMessage('CRM_BP_DOCUMENT_SMART_DOCUMENT_ENTITY_NAME_1');
	}

	public static function getDocumentTypeName($documentType)
	{
		return static::getEntityName(static::class);
	}

	protected static function GetDocumentInfo($documentId)
	{
		if (!is_string($documentId))
		{
			return false;
		}

		$entityType = mb_substr($documentId, 0, mb_strlen(\CCrmOwnerType::SmartDocumentName));
		$entityId = (int)mb_substr($documentId, mb_strlen(\CCrmOwnerType::SmartDocumentName) + 1);

		if ($entityType !== \CCrmOwnerType::SmartDocumentName)
		{
			return false;
		}

		return [
			'TYPE' => $entityType,
			'TYPE_ID' => \CCrmOwnerType::SmartDocument,
			'ID' => $entityId,
			'DOCUMENT_TYPE' => [
				'crm',
				static::class,
				\CCrmOwnerType::SmartDocumentName,
			],
		];
	}
}