<?php
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!Loader::includeModule('crm') || !Loader::includeModule('sale'))
{
	return;
}

Loc::loadMessages(__FILE__);

$request = Bitrix\Main\Application::getInstance()->getContext()->getRequest();

$signer = new \Bitrix\Main\Security\Sign\Signer;
try
{
	$params = $signer->unsign($request->get('signedParamsString'), 'crm.order.matcher.property.edit');
	$params = unserialize(base64_decode($params), ['allowed_classes' => false]);
}
catch (\Bitrix\Main\Security\Sign\BadSignatureException $e)
{
	die();
}

$request->set(
	\Bitrix\Sale\Internals\Input\File::getPostWithFiles(
		$request->toArray(),
		$request->getFileList()->toArray()
	)
);

global $APPLICATION;

$APPLICATION->IncludeComponent(
	'bitrix:crm.order.matcher.property.edit',
	'',
	$params
);