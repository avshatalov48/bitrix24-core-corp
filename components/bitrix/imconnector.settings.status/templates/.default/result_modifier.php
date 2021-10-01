<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

use \Bitrix\ImConnector\Connector;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Web\Uri;

Connector::initIconCss();
Loader::includeModule('imopenlines');

$codeMap = Connector::getIconClassMap();
$uri = new Uri(\Bitrix\ImOpenLines\Common::getContactCenterPublicFolder() . 'connector/');
$uri->addParams(array('LINE' => $arParams['LINE']));
$uri->addParams(array('LINE_SETTING' => 'Y'));

foreach ($arResult as &$connector)
{
	$uri->deleteParams(array('ID'));
	$uri->addParams(array('ID' => $connector['ID']));
	$connector['LINK'] = CUtil::JSEscape($uri->getUri());
	$connector['LOGO_CLASS'] = "ui-icon ui-icon-service-" . $codeMap[$connector['ID']];
	$connector['COLOR_CLASS'] = 'imconnector-' . $connector['ID'] . '-background-color';
}