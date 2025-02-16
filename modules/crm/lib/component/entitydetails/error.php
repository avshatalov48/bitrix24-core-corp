<?php /** @noinspection ReturnTypeCanBeDeclaredInspection */

namespace Bitrix\Crm\Component\EntityDetails;

use Bitrix\Main\Localization\Loc;
use CCrmOwnerType;

class Error
{
	public const EntityNotExist = 1;
	public const NoAccessToEntityType = 2;
	public const NoReadPermission = 3;
	public const NoAddPermission = 4;

	public static function showError(int $error, int $entityTypeId)
	{
		global $APPLICATION;

		$APPLICATION->IncludeComponent(
			"bitrix:crm.entity.details.error",
			".default",
			[
				'ERROR' => $error,
				'ENTITY_TYPE_ID' => $entityTypeId,
			]
		);
	}

	public static function getErrorMessage(int $error, int $entityTypeId): string
	{
		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeId);
		if (strrpos($entityTypeName, CCrmOwnerType::DynamicTypePrefixName) !== false)
		{
			$entityTypeName = CCrmOwnerType::CommonDynamicName;
		}

		switch ($error)
		{
			case Error::EntityNotExist:
				$errorMessage = GetMessage('CRM_ENTITY_DETAIL_ERROR_' . $entityTypeName . '_NOT_EXIST');
				break;
			case Error::NoAccessToEntityType:
				$errorMessage = Loc::getMessage('CRM_ENTITY_DETAIL_ERROR_NO_ACCESS_TO_' . $entityTypeName);
				break;
			case Error::NoReadPermission:
				$errorMessage = Loc::getMessage('CRM_ENTITY_DETAIL_ERROR_NO_READ_PERMISSION_TO_' . $entityTypeName);
				break;
			case Error::NoAddPermission:
				$errorMessage = Loc::getMessage('CRM_ENTITY_DETAIL_ERROR_NO_CREATE_PERMISSION_TO_' . $entityTypeName);
				break;
		}

		return $errorMessage ?? '';
	}
}
