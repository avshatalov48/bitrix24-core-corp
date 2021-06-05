<?php
namespace Bitrix\Crm\Controller;

use Bitrix\Main;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\UserField\Access\Permission\PermissionDictionary;
use Bitrix\Main\UserField\Access\Permission\UserFieldPermissionTable;
use Bitrix\Crm;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\UserField\Visibility\VisibilityManager;

class UserFieldVisibility extends Main\Engine\Controller
{
	/** @var \CCrmPerms|null  */
	private static $userPermissions = null;

	/**
	 * @param Action $action
	 * @return bool
	 */
	protected function processBeforeAction(Action $action): bool
	{
		$restriction = RestrictionManager::getUfAccessRightsRestriction();

		return parent::processBeforeAction($action)
			&& \CCrmAuthorizationHelper::CheckConfigurationUpdatePermission(self::getCurrentUserPermissions())
			&& VisibilityManager::isEnabled()
			&& $restriction->hasPermission();
	}

	public function saveConfigurationAction($accessCodes, string $fieldName, int $entityTypeId)
	{
		UserFieldPermissionTable::saveEntityConfiguration(
			$accessCodes,
			$fieldName,
			$entityTypeId,
			PermissionDictionary::USER_FIELD_VIEW,
			\CCrmOwnerType::ResolveUserFieldEntityID($entityTypeId)
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
