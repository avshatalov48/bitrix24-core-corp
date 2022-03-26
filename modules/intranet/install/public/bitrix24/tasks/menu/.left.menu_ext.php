<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$userId = $GLOBALS['USER']->getId();
$result = $GLOBALS['APPLICATION']->includeComponent(
	'bitrix:tasks.interface.topmenu',
	'',
	[
		'MENU_MODE' => 'Y',
		'PATH_TO_USER_TASKS' => SITE_DIR . 'company/personal/user/' . $userId . '/tasks/',
	]
);

if ($result)
{
	$data = $result->getData();
	$aMenuLinks = is_array($data) && isset($data['ITEMS']) ? $data['ITEMS'] : [];
}
