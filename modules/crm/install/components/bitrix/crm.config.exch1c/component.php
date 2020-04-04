<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/** @global CMain $APPLICATION */
global $APPLICATION;

$exch1cEnabled = COption::GetOptionString('crm', 'crm_exch1c_enable', 'N');
$exch1cEnabled = ($exch1cEnabled === 'Y');
if ($exch1cEnabled)
{
	if ($license_name = COption::GetOptionString("main", "~controller_group_name"))
	{
		preg_match("/(project|tf)$/is", $license_name, $matches);
		if (strlen($matches[0]) > 0)
			$exch1cEnabled = false;
	}
}

$arResult['CRM_EXCH1C_ENABLED'] = ($exch1cEnabled) ? 'Y' : 'N';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['CRM_EXCH1C_ENABLE']) && check_bitrix_sessid())
{
	$APPLICATION->RestartBuffer();
	header('Content-type: application/x-www-form-urlencoded; charset=UTF-8');
	$errNumber = 0;
	CUtil::JSPostUnescape();
	$exch1cEnabled = ($_POST['CRM_EXCH1C_ENABLE'] === 'Y');
	COption::SetOptionString('crm', 'crm_exch1c_enable', ($exch1cEnabled) ? 'Y' : 'N');
	$result = array('ERROR' => $errNumber);
	if ($errNumber === 0)
		$result['CHECKED'] = $exch1cEnabled ? 'Y' : 'N';
	echo CUtil::PhpToJSObject($result);
	exit();
}

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if(!CAllCrmInvoice::installExternalEntities())
	return;


if (!CModule::IncludeModule('iblock'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_IBLOCK'));
	return;
}
if (!CModule::IncludeModule('currency'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_CURRENCY'));
	return;
}
if (!CModule::IncludeModule('sale'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_SALE'));
	return;
}
if (!CModule::IncludeModule('catalog'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_CATALOG'));
	return;
}

global $APPLICATION, $USER;
$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['PATH_TO_CONFIGS_INDEX'] = isset($arParams['PATH_TO_CONFIGS_INDEX']) ? $arParams['PATH_TO_CONFIGS_INDEX'] : '/crm/configs/';

$componentPage = '';
$arDefaultUrlTemplates404 = array(
	'index' => '',
	'catalog' => 'catalog/',
	'invoice' => 'invoice/'
);

if ($arParams['SEF_MODE'] === 'Y')
{
	$arDefaultVariableAliases404 = array();
	$arComponentVariables = array();
	$arVariables = array();
	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams['SEF_URL_TEMPLATES']);
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams['VARIABLE_ALIASES']);
	$componentPage = CComponentEngine::ParseComponentPath($arParams['SEF_FOLDER'], $arUrlTemplates, $arVariables);

	if (!(is_string($componentPage) && isset($componentPage[0]) && isset($arDefaultUrlTemplates404[$componentPage])) || $this->__templateName === 'free')
	{
		$componentPage = 'index';
	}

	CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);

	foreach ($arUrlTemplates as $url => $value)
	{
		$key = 'PATH_TO_EXCH1C_'.strtoupper($url);
		$arResult[$key] = isset($arParams[$key][0]) ? $arParams[$key] : $arParams['SEF_FOLDER'].$value;
	}
}
else
{
	$arComponentVariables = array();
	$arDefaultVariableAliases = array();
	$arVariables = array();
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams['VARIABLE_ALIASES']);
	CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

	$componentPage = 'index';
	if ($this->__templateName !== 'free')
	{
		if (isset($_REQUEST['catalog']))
		{
			$componentPage = 'catalog';
		}
		elseif (isset($_REQUEST['invoice']))
		{
			$componentPage = 'invoice';
		}
	}

	$curPage = $APPLICATION->GetCurPage();

	$arResult['PATH_TO_EXCH1C_INDEX'] = $curPage;
	$arResult['PATH_TO_EXCH1C_CAT'] = $curPage.'?catalog';
	$arResult['PATH_TO_EXCH1C_INV'] = $curPage.'?invoice';
}

$arResult =
	array_merge(
		array(
			'VARIABLES' => $arVariables,
			'ALIASES' => $arParams['SEF_MODE'] == 'Y' ? array(): $arVariableAliases
		),
		$arResult
	);

$arResult['EXCH_1C_SCRIPT_URL'] = ($GLOBALS['APPLICATION']->IsHTTPS() ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].'/crm/1c_exchange.php';
$arResult['EXCH_1C_INFORMATION_URL'] = ($GLOBALS['APPLICATION']->IsHTTPS() ? 'https://' : 'http://').'www.bitrix24.ru/blogs/howto/integration1c.php';

$this->IncludeComponentTemplate($componentPage);
