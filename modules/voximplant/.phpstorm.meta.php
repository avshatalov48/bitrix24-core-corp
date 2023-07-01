<?php

namespace PHPSTORM_META
{
	registerArgumentsSet(
		'bitrix_voximplant_permissions_entities',
		\Bitrix\Voximplant\Security\Permissions::ENTITY_CALL_DETAIL,
		\Bitrix\Voximplant\Security\Permissions::ENTITY_CALL_RECORD,
		\Bitrix\Voximplant\Security\Permissions::ENTITY_CALL,
		\Bitrix\Voximplant\Security\Permissions::ENTITY_USER,
		\Bitrix\Voximplant\Security\Permissions::ENTITY_SETTINGS,
		\Bitrix\Voximplant\Security\Permissions::ENTITY_LINE,
		\Bitrix\Voximplant\Security\Permissions::ENTITY_BALANCE
	);

	registerArgumentsSet(
		'bitrix_voximplant_permissions_actions',
		\Bitrix\Voximplant\Security\Permissions::ACTION_MODIFY,
		\Bitrix\Voximplant\Security\Permissions::ACTION_VIEW,
		\Bitrix\Voximplant\Security\Permissions::ACTION_LISTEN,
		\Bitrix\Voximplant\Security\Permissions::ACTION_PERFORM,
	);

	registerArgumentsSet(
		'bitrix_voximplant_permissions_values',
		\Bitrix\Voximplant\Security\Permissions::PERMISSION_NONE,
		\Bitrix\Voximplant\Security\Permissions::PERMISSION_SELF,
		\Bitrix\Voximplant\Security\Permissions::PERMISSION_CALL_CRM,
		\Bitrix\Voximplant\Security\Permissions::PERMISSION_DEPARTMENT,
		\Bitrix\Voximplant\Security\Permissions::PERMISSION_CALL_USERS,
		\Bitrix\Voximplant\Security\Permissions::PERMISSION_ANY,
	);

	expectedArguments(\Bitrix\Voximplant\Security\Permissions::canPerform(), 0, argumentsSet('bitrix_voximplant_permissions_entities'));
	expectedArguments(\Bitrix\Voximplant\Security\Permissions::canPerform(), 1, argumentsSet('bitrix_voximplant_permissions_actions'));
	expectedArguments(\Bitrix\Voximplant\Security\Permissions::canPerform(), 2, argumentsSet('bitrix_voximplant_permissions_values'));

	expectedArguments(\Bitrix\Voximplant\Security\Permissions::getPermission(), 0, argumentsSet('bitrix_voximplant_permissions_entities'));
	expectedArguments(\Bitrix\Voximplant\Security\Permissions::getPermission(), 1, argumentsSet('bitrix_voximplant_permissions_actions'));


}
