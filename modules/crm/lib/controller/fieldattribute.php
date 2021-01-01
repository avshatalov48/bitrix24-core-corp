<?php
namespace Bitrix\Crm\Controller;
use Bitrix\Main;

use Bitrix\Crm;
use Bitrix\Main\Engine\Action;

class FieldAttribute extends Main\Engine\Controller
{
	/** @var \CCrmPerms|null  */
	private static $userPermissions = null;

	protected function processBeforeAction(Action $action)
	{
		return parent::processBeforeAction($action)
			&& \CCrmAuthorizationHelper::CheckConfigurationUpdatePermission(
				self::getCurrentUserPermissions()
			);
	}

	//BX.ajax.runAction("crm.api.fieldAttribute.saveConfiguration", { data: { config: { typeId: 3, groups: [...] },
	// fieldName: "UF_CRM_1519828243", entityTypeName: "DEAL", entityScope: "" } });
	public function saveConfigurationAction(array $config, $fieldName, $entityTypeName, $entityScope)
	{
		Crm\Attribute\FieldAttributeManager::saveEntityConfiguration(
			$config,
			$fieldName,
			\CCrmOwnerType::ResolveID($entityTypeName),
			$entityScope
		);
	}
	public function removeConfigurationAction($type, $fieldName, $entityTypeName, $entityScope)
	{
		Crm\Attribute\FieldAttributeManager::removeEntityConfiguration(
			$type,
			$fieldName,
			\CCrmOwnerType::ResolveID($entityTypeName),
			$entityScope
		);
	}

	protected static function getCurrentUserPermissions()
	{
		if(self::$userPermissions === null)
		{
			self::$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		}
		return self::$userPermissions;
	}
}