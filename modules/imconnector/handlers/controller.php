<?php
use Bitrix\Main;
use Bitrix\Main\Web\Json;
use Bitrix\ImConnector\Input;
use Bitrix\ImConnector\Converter;

define('PUBLIC_AJAX_MODE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NOT_CHECK_PERMISSIONS', true);
define('DisableEventsCheck', true);
define('NO_AGENT_CHECK', true);

/* PROVIDER -> CONTROLLER -> PORTAL */

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');
header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

if (!Main\Loader::includeModule('imconnector'))
{
	echo Json::encode(
		[
		'OK' => false,
		'ERROR' => [
			'CODE' => 'MODULE_NOT_INSTALLED',
			'MESSAGE' => 'Module ImConnector isn\'t installed'
		]
	]
	);
}
else
{
	/** @global \CMain $APPLICATION */
	if ($APPLICATION instanceof \CMain)
	{
		$APPLICATION->RestartBuffer();
	}

	$request = Main\HttpApplication::getInstance()->getContext()->getRequest();
	$post = $request->getPostList()->toArray();
	$portal = new Input($post);

	$result = $portal->reception();

	if(is_object($result))
	{
		echo Json::encode(Converter::convertObjectArray($result));
	}
	else
	{
		echo 'You don\'t have access to this page.';
	}
}

\CMain::FinalActions();
