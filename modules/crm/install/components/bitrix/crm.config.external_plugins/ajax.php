<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('PUBLIC_AJAX_MODE', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$context = \Bitrix\Main\Application::getInstance()->getContext();
$request = $context->getRequest();

$result = $APPLICATION->IncludeComponent(
	'bitrix:crm.config.external_plugins',
	'.default',
	array(
		'IS_AJAX' => 'Y',
		'CMS_ID' => $request->get('cms')
	)
);

$GLOBALS['APPLICATION']->RestartBuffer();
header('Content-Type: application/json');

if (SITE_CHARSET != 'UTF-8')
{
	$result = $GLOBALS['APPLICATION']->ConvertCharsetArray($result, SITE_CHARSET, 'UTF-8');
}
if (isset($result['ERROR']) && $result['ERROR'] != '')
{
	echo CUtil::PhpToJSObject(array('error' => $result['ERROR']));
}
else
{
	echo CUtil::PhpToJSObject(array(
		'url' => $result['CONNECTOR_URL']
	));
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');