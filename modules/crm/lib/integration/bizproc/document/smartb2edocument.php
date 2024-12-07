<?php

namespace Bitrix\Crm\Integration\BizProc\Document;

use Bitrix\Main\Localization\Loc;

class SmartB2eDocument extends Item
{
	public static function getEntityName($entity)
	{
		return Loc::getMessage('CRM_BP_DOCUMENT_SMART_B2E_DOCUMENT_ENTITY_NAME');
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

		$entityType = mb_substr($documentId, 0, mb_strlen(\CCrmOwnerType::SmartB2eDocumentName));
		$entityId = (int)mb_substr($documentId, mb_strlen(\CCrmOwnerType::SmartB2eDocumentName) + 1);

		if ($entityType !== \CCrmOwnerType::SmartB2eDocumentName)
		{
			return false;
		}

		return [
			'TYPE' => $entityType,
			'TYPE_ID' => \CCrmOwnerType::SmartB2eDocument,
			'ID' => $entityId,
			'DOCUMENT_TYPE' => [
				'crm',
				static::class,
				\CCrmOwnerType::SmartB2eDocumentName,
			],
		];
	}
}
