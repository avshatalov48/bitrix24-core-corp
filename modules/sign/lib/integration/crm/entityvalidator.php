<?php

namespace Bitrix\Sign\Integration\CRM;

class EntityValidator
{
	/**
	 * Check that entity in CRM exists
	 * @param $entityTypeId
	 * @param $entityId
	 * @param $errors
	 * @return bool
	 */
	public static function checkEntity($entityTypeId, $entityId): bool
	{
		if (\CModule::includeModule('crm') && $entityId && $entityTypeId === 'SMART')
		{

			$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::SmartDocument);
			$entity = $factory->getItem($entityId);
			if (!$entity)
			{
				return false;
			}
		}

		return true;
	}
}