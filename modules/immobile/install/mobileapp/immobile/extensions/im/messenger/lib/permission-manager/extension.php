<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!\Bitrix\Main\Loader::includeModule('im'))
{
	return;
}
$permissionManager = new \Bitrix\Im\V2\Chat\Permission(true);

return [
	'call_server_max_users' => \Bitrix\Main\Config\Option::get('im', 'call_server_max_users'),
	'userChatOptions' => CIMChat::GetChatOptions(),
	'permissions' => [
		'byChatType' => $permissionManager->getByChatTypes(),
		'actionGroups' => $permissionManager->getActionGroupDefinitions(),
		'actionGroupsDefaults' => $permissionManager->getDefaultPermissionForGroupActions()
	],
];
