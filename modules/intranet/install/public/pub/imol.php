<?php
use Bitrix\Main\Localization\Loc;

define('SKIP_TEMPLATE_AUTH_ERROR', true);
define('NOT_CHECK_PERMISSIONS', true);

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php';
Loc::loadMessages($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/pub/imol.php");

$arLang = \Bitrix\Intranet\Util::getLanguageList();
if (isset($_GET['user_lang']) && in_array($_GET['user_lang'], $arLang))
{
	define("LANGUAGE_ID", $_GET['user_lang']);
}

$APPLICATION->SetTitle(Loc::getMessage("IMOL_USERCONSENT_TITLE"));
$APPLICATION->SetPageProperty("BodyClass", "flexible-middle-width");
\Bitrix\Main\Page\Asset::getInstance()->addString('<meta name="viewport" content="width=device-width, initial-scale=1.0">');

$request = \Bitrix\Main\Context::getCurrent()->getRequest();
if (CModule::IncludeModule('imopenlines'))
{
	$agreementId = $request->get('id');
	$securityCode =  $request->get('sec');
	$fields = \Bitrix\ImOpenLines\Session\Common::getAgreementFields();

	if ($request->get('iframe') == 'Y')
	{
		$APPLICATION->SetPageProperty("BodyClass", "iframe-body");
	}
}
else
{
	$agreementId = 0;
	$securityCode = '';
	$fields = Array();
}

$APPLICATION->IncludeComponent(
	"bitrix:main.userconsent.view",
	"",
	array(
		'ID' => $agreementId,
		'SECURITY_CODE' => $securityCode,
		'REPLACE' => array('fields' => $fields)
	),
	null,
	array("HIDE_ICONS" => "Y")
);

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php';
