<?php

namespace Bitrix\Crm\Service;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\DI\ServiceLocator;

class EventHandler
{
	public static function onGetUserFieldTypeFactory(): array
	{
		return [
			ServiceLocator::getInstance()->get('crm.type.factory'),
		];
	}

	public static function OnBeforeUserTypeAdd(&$field): bool
	{
		$crmEntityPrefix = ServiceLocator::getInstance()->get('crm.type.factory')->getUserFieldEntityPrefix();
		if (strpos($field['ENTITY_ID'], $crmEntityPrefix) === 0)
		{
			$entityTypeId = \CCrmOwnerType::ResolveIDByUFEntityID($field['ENTITY_ID']);
			$ufAddRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getUserFieldAddRestriction();
			if ($ufAddRestriction->isExceeded((int)$entityTypeId))
			{
				Container::getInstance()->getLocalization()->loadMessages();

				global $APPLICATION;
				$APPLICATION->ThrowException(Loc::getMessage('CRM_FEATURE_RESTRICTION_ERROR'));

				return false;
			}
			$resourceUfAddRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getResourceBookingRestriction();
			if ($field['USER_TYPE_ID'] === 'resourcebooking' && !$resourceUfAddRestriction->hasPermission())
			{
				Container::getInstance()->getLocalization()->loadMessages();

				global $APPLICATION;
				$APPLICATION->ThrowException(Loc::getMessage('CRM_FEATURE_RESTRICTION_ERROR'));

				return false;
			}
		}
		return true;
	}
}
