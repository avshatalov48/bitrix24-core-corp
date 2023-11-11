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
		return Loc::getMessage('CRM_BP_DOCUMENT_QUOTE_ENTITY_NAME_MSGVER_1');
	}

	protected static function GetDocumentInfo($documentId)
	{
		$documentIdParts = explode('_', $documentId);
		$entityType = $documentIdParts[0] ?? null;
		$entityId = $documentIdParts[1] ?? null;

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

	public static function getEntityFields($entityTypeId)
	{
		$fields = parent::getEntityFields($entityTypeId);

		unset(
			$fields[\Bitrix\Crm\Item\Quote::FIELD_NAME_STORAGE_ELEMENTS],
			$fields[\Bitrix\Crm\Item\Quote::FIELD_NAME_STORAGE_TYPE],
			$fields[\Bitrix\Crm\Item\Quote::FIELD_NAME_PERSON_TYPE_ID]
		);

		return $fields;
	}

	public static function convertFieldId(string $fieldId, int $convertTo = self::CONVERT_TO_BP): string
	{
		if ($convertTo === static::CONVERT_TO_DOCUMENT && $fieldId === \Bitrix\Crm\Item::FIELD_NAME_STAGE_ID)
		{
			return 'STATUS_ID';
		}

		return parent::convertFieldId($fieldId, $convertTo);
	}
}
