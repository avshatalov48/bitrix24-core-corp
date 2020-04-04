<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $this
 * @global CMain $APPLICATION
 * @global CUser $USER
 */
use \Bitrix\Main\Localization\Loc;

if(!CModule::IncludeModule("rest"))
{
	return;
}

$arParams['APP'] = !empty($arParams['APP']) ? $arParams['APP'] : $_GET['app'];

$ver = false;

$arResult['CHECK_HASH'] = false;
$arResult['INSTALL_HASH'] = false;

if (isset($_GET["ver"]) && intval($_GET["ver"]) && isset($_GET["check_hash"]) && isset($_GET['install_hash']))
{
	$checkHash = $_GET['check_hash'];
	$check = md5(rtrim(CHTTP::URN2URI('/'), '/').'|'.$_GET['ver'].'|'.$arParams['APP']);

	if($checkHash === $check)
	{
		$ver = intval($_GET["ver"]);

		$arResult['CHECK_HASH'] = $check;
		$arResult['INSTALL_HASH'] = $_GET['install_hash'];

		$arResult['START_INSTALL'] = isset($_GET["install"]) && $_GET["install"] == "Y";
	}
}

$dbApp = \Bitrix\Rest\AppTable::getList(array(
	'filter' => array("=CODE" => $arParams["APP"])
));
$ar = $dbApp->fetch();

if($ver === false && $ar['ACTIVE'] === 'N' && $ar['STATUS'] === \Bitrix\Rest\AppTable::STATUS_PAID)
{
	$ver = intval($ar['VERSION']);
}

$arApp = \Bitrix\Rest\Marketplace\Client::getApp($arParams['APP'], $ver, $arResult['CHECK_HASH'], $arResult['INSTALL_HASH']);

if($arApp)
{
	$arApp = $arApp["ITEMS"];

	$APPLICATION->SetTitle(htmlspecialcharsbx($arApp["NAME"]));

	if($ar)
	{
		$arApp["ID"] = $ar["ID"];
		$arApp["INSTALLED"] = $ar["INSTALLED"];
		$arApp["ACTIVE"] = $ar["ACTIVE"];
		$arApp["STATUS"] = $ar["STATUS"];
		$arApp["DATE_FINISH"] = $ar["DATE_FINISH"];
		$arApp["IS_TRIALED"] = $ar["IS_TRIALED"];

		$arApp['APP_STATUS'] = \Bitrix\Rest\AppTable::getAppStatusInfo($arApp, $APPLICATION->GetCurPageParam());

		if(isset($arApp['APP_STATUS']['MESSAGE_REPLACE']['#DAYS#']))
		{
			$arApp['APP_STATUS']['MESSAGE_REPLACE']['#DAYS#']++;
			$arApp['APP_STATUS']['MESSAGE_REPLACE']['#DAYS#'] = FormatDate("ddiff", time(), time() + 24 * 60 * 60 * $arApp['APP_STATUS']['MESSAGE_REPLACE']["#DAYS#"]);
		}
	}

	if ($arApp["ACTIVE"] == "Y")
	{
		$arApp["UPDATES"] = \Bitrix\Rest\Marketplace\Client::getAvailableUpdate($arApp["CODE"]);
	}

	if ($arApp["DATE_PUBLIC"])
	{
		$stmp = MakeTimeStamp($arApp["DATE_PUBLIC"], "DD.MM.YYYY");
		$arApp["DATE_PUBLIC"] = ConvertTimeStamp($stmp);
	}
	if ($arApp["DATE_UPDATE"])
	{
		$stmp = MakeTimeStamp($arApp["DATE_UPDATE"], "DD.MM.YYYY");
		$arApp["DATE_UPDATE"] = ConvertTimeStamp($stmp);
	}

	if ($arApp["BY_SUBSCRIPTION"] == "Y")
	{
		if (\Bitrix\Rest\Marketplace\Client::isSubscriptionAvailable())
		{
			$arApp["STATUS"] = \Bitrix\Rest\AppTable::STATUS_PAID;
			$arApp["DATE_FINISH"] = \Bitrix\Rest\Marketplace\Client::getSubscriptionFinalDate();
		}
	}

	$arResult['REDIRECT_PRIORITY'] = false;
	if($arApp['TYPE'] === \Bitrix\Rest\AppTable::TYPE_CONFIGURATION)
	{
		$arResult['REDIRECT_PRIORITY'] = true;
	}

	$arResult["APP"] = $arApp;
}

$arResult["ADMIN"] = \CRestUtil::isAdmin();
$arResult["CAN_INSTALL"] = $arResult['ADMIN'] || \CRestUtil::canInstallApplication($arApp);

$request = \Bitrix\Main\Context::getCurrent()->getRequest();

if($request->isPost() && $request['install'] && check_bitrix_sessid())
{
	$APPLICATION->RestartBuffer();

	if(!$arResult['CAN_INSTALL'])
	{
		ShowError(GetMessage("RMP_ACCESS_DENIED"));
	}
	else
	{
		$obRestDesc = new \CRestProvider();
		$arRestDesc = $obRestDesc->getDescription();
		\Bitrix\Main\Localization\Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/rest/scope.php');
		$arResult['SCOPE_DENIED'] = array();
		if(is_array($arResult['APP']['RIGHTS']))
		{
			foreach($arResult['APP']['RIGHTS'] as $key => $scope)
			{
				$arResult['APP']['RIGHTS'][$key] = [
					"TITLE" => Loc::getMessage("REST_SCOPE_".strtoupper($key)) ?: $scope,
					"DESCRIPTION" => Loc::getMessage("REST_SCOPE_".strtoupper($key)."_DESCRIPTION")
				];
				if(!array_key_exists($key, $arRestDesc))
				{
					$arResult['SCOPE_DENIED'][$key] = 1;
				}
			}
		}

		$arResult['IS_HTTPS'] = \Bitrix\Main\Context::getCurrent()->getRequest()->isHttps();

		$this->includeComponentTemplate('install');
	}
	CMain::FinalActions();
	die();
}
else
{
	if(is_array($arResult["APP"]["PRICE"]) && !empty($arResult["APP"]["PRICE"]) && $arResult['ADMIN'])
	{
		$arResult['BUY'] = array();
		foreach($arResult["APP"]["PRICE"] as $num => $price)
		{
			if($num > 1)
			{
				$arResult['BUY'][] = array(
					'LINK' => \Bitrix\Rest\Marketplace\Client::getBuyLink($num, $arResult['APP']['CODE']),
					'TEXT' => GetMessage("RMP_APP_TIME_LIMIT_".$num).' - '.$price
				);
			}
		}
	}

	if($arResult['APP']['TYPE'] == \Bitrix\Rest\AppTable::TYPE_CONFIGURATION)
	{
		$url = \Bitrix\Rest\Marketplace\Url::getConfigurationImportAppUrl($arResult['APP']['CODE']);

		$request = Bitrix\Main\Application::getInstance()->getContext()->getRequest();
		$check_hash = $request->getQuery("check_hash");
		$install_hash = $request->getQuery("install_hash");
		if($install_hash && $check_hash)
		{
			$uri = new Bitrix\Main\Web\Uri($url);
			$uri->addParams(
				[
					'check_hash' => $check_hash,
					'install_hash' => $install_hash
				]
			);
			$arResult['IMPORT_PAGE'] = $uri->getUri();
		}
		else
		{
			$arResult['IMPORT_PAGE'] = $url;
		}
	}
	CJSCore::Init(array('marketplace', 'image', 'applayout'));

	$this->IncludeComponentTemplate();
}
?>