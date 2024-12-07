<?php
define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);

require_once($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/main/include/prolog_before.php');

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

if (!check_bitrix_sessid() || !$request->isPost())
{
	die();
}

if (!\Bitrix\Main\Loader::includeModule('crm'))
{
	die();
}

$currentUser = \CCrmSecurityHelper::GetCurrentUser();
$currentUserPermissions = \CCrmPerms::GetCurrentUserPermissions();
if (!$currentUser->IsAuthorized())
{
	die();
}


$params = array();

if (!empty($request->get('action')))
{
	$params['ACTION'] = $request->get('action');
}
else
{
	die();
}

global $APPLICATION;

$APPLICATION->IncludeComponent(
	'bitrix:crm.volume',
	'.default',
	$params
);
