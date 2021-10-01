<?require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');
global $APPLICATION;?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?$APPLICATION->ShowTitle()?></title>
	<?php
	/** @var CMain $APPLICATION */
	use Bitrix\Main\Localization\Loc;
	Loc::loadMessages(__FILE__);
	$APPLICATION->ShowHead();
	$APPLICATION->ShowCSS(true, true);
	$APPLICATION->ShowHeadStrings();
	$APPLICATION->ShowHeadScripts();
	?>
</head>
<body style="height: 100%;margin: 0;padding: 0; background: #fff" id="workarea-content">
<?
if(\Bitrix\Main\Loader::includeModule('imopenlines'))
{
	$url = \Bitrix\ImOpenLines\Common::getContactCenterPublicFolder();
	$url .= 'connector/viber/';
	$queryString = \Bitrix\Main\Context::getCurrent()->getServer()->getValues()['QUERY_STRING'];
	if(!empty($queryString))
	{
		$url .= '?' . $queryString;
	}

	LocalRedirect($url);
}
?>
</body>
</html>
<?require_once($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/main/include/epilog_after.php');?>
