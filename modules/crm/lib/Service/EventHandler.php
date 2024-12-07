<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\Category\ItemCategoryUserField;
use Bitrix\Crm\Entry\AddException;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\DI\ServiceLocator;

final class EventHandler
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
		if(strpos($field['ENTITY_ID'], $crmEntityPrefix) === 0)
		{
			$entityTypeId = \CCrmOwnerType::ResolveIDByUFEntityID($field['ENTITY_ID']);

			$ufAddRestriction = RestrictionManager::getUserFieldAddRestriction();
			if($ufAddRestriction->isExceeded((int)$entityTypeId))
			{
				Container::getInstance()->getLocalization()->loadMessages();

				global $APPLICATION;
				$APPLICATION->ThrowException(Loc::getMessage('CRM_FEATURE_RESTRICTION_ERROR'));

				return false;
			}

			$resourceUfAddRestriction = RestrictionManager::getResourceBookingRestriction();
			if($field['USER_TYPE_ID'] === 'resourcebooking' && !$resourceUfAddRestriction->hasPermission())
			{
				Container::getInstance()->getLocalization()->loadMessages();

				global $APPLICATION;
				$APPLICATION->ThrowException(Loc::getMessage('CRM_FEATURE_RESTRICTION_ERROR'));

				return false;
			}

			$categoryId = $field['CONTEXT_PARAMS']['CATEGORY_ID'] ?? 0; // if not set -> default category
			$fieldName = $field['FIELD_NAME'];
			if(isset($fieldName))
			{
				try
				{
					(new ItemCategoryUserField($entityTypeId))->add($categoryId, $fieldName);
				}
				catch (AddException $e)
				{
					global $APPLICATION;
					$APPLICATION->ThrowException($e->getMessage());

					return false;
				}
			}
		}

		return true;
	}
}
