<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('PUBLIC_AJAX_MODE', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$context = \Bitrix\Main\Application::getInstance()->getContext();
$request = $context->getRequest();

$result = [];
$action = $request->get('action');
$payload = $request->get('payload');

// do some actions
if (
	$payload && check_bitrix_sessid() &&
	\Bitrix\Main\Loader::includeModule('intranet')
)
{
	// logging action during opening menu item
	if (
		$action == 'openingLog' &&
		isset($payload['bindingId']) &&
		isset($payload['menuItemId'])
	)
	{
		$result = ['type' => 'success'];
		\Bitrix\Intranet\Binding\Menu::processMenuItemHit(
			$payload['bindingId'],
			$payload['menuItemId']
		);
	}
}

echo \CUtil::phpToJSObject($result, false, false, true);

\CMain::finalActions();
die();
