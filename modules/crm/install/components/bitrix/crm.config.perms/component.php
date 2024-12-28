<?php

use Bitrix\Crm\Feature;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));

	return;
}

if (!CAllCrmInvoice::installExternalEntities())
{
	return;
}

if (!CCrmQuote::LocalComponentCausedUpdater())
{
	return;
}

if (!CModule::IncludeModule('currency'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_CURRENCY'));

	return;
}

if (!CModule::IncludeModule('catalog'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_CATALOG'));

	return;
}

if (!CModule::IncludeModule('sale'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_SALE'));

	return;
}

$CrmPerms = CCrmPerms::GetCurrentUserPermissions();
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));

	return;
}

global $APPLICATION;

$arDefaultUrlTemplates404 = [
	'perms_list' => '',
	'role_edit' => '#role_id#/edit/',
];
$arDefaultVariableAliases404 = [];
$arDefaultVariableAliases = [];
$arComponentVariables = [
	'role_id',
	'mode'
];

if (isset($arParams['SEF_MODE']) && $arParams['SEF_MODE'] === 'Y')
{
	$arVariables = [];

	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates(
		$arDefaultUrlTemplates404,
		$arParams['SEF_URL_TEMPLATES'] ?? ''
	);
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases(
		$arDefaultVariableAliases404,
		$arParams['VARIABLE_ALIASES']
	);
	$componentPage = CComponentEngine::ParseComponentPath(
		$arParams['SEF_FOLDER'],
		$arUrlTemplates,
		$arVariables
	);

	if (!$componentPage)
	{
		$componentPage = 'perms_list';
	}

	CComponentEngine::InitComponentVariables(
		$componentPage,
		$arComponentVariables,
		$arVariableAliases,
		$arVariables
	);
	$arResult = [
		'FOLDER' => $arParams['SEF_FOLDER'],
		'URL_TEMPLATES' => $arUrlTemplates,
		'VARIABLES' => $arVariables,
		'ALIASES' => $arVariableAliases,
	];
}
else
{
	$arVariables = [];
	if (!isset($arParams['VARIABLE_ALIASES']['ID']))
	{
		$arParams['VARIABLE_ALIASES']['ID'] = 'ID';
	}

	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams['VARIABLE_ALIASES']);
	CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

	$componentPage = 'perms_list'; // default page

	if (isset($arVariables['mode']))
	{
		switch($arVariables['mode'])
		{
			case 'edit':
				if (isset($arVariables['role_id']))
					$componentPage = 'role_edit';
			break;
			case 'list':
				$componentPage = 'perms_list';
			break;
		}
	}

	$curPage = $APPLICATION->GetCurPage();
	$arResult = array(
		'FOLDER' => '',
		'URL_TEMPLATES' => array(
			'entity_list' => $curPage,
			'role_edit' => $curPage
				.'?'.$arVariableAliases['mode'].'=edit'
				.'&'.$arVariableAliases['role_id'].'=#role_id#'
		),
		'VARIABLES' => $arVariables,
		'ALIASES' => $arVariableAliases
	);
}

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
if (
	Feature::enabled(Feature\PermissionsLayoutV2::class)
	&& $request->getRequestMethod() === 'GET'
	&& !isset($_GET['nr'])
)
{
	$oldUri = new \Bitrix\Main\Web\Uri($request->getRequestUri());

	$newUri = (new \Bitrix\Crm\Security\Role\Manage\Manager\AllSelection())->getUrl();
	$newUri = $newUri->withQuery($oldUri->getQuery());

	LocalRedirect((string)$newUri);
}

$arResult['NEED_FOR_REBUILD_COMPANY_ATTRS'] = false;
if (CCrmPerms::IsAdmin() && COption::GetOptionString('crm', '~CRM_REBUILD_COMPANY_ATTR', 'N') === 'Y')
{
	$arResult['NEED_FOR_REBUILD_COMPANY_ATTRS'] = true;
}

$arResult['NEED_FOR_REBUILD_CONTACT_ATTRS'] = false;
if (CCrmPerms::IsAdmin() && COption::GetOptionString('crm', '~CRM_REBUILD_CONTACT_ATTR', 'N') === 'Y')
{
	$arResult['NEED_FOR_REBUILD_CONTACT_ATTRS'] = true;
}

$arResult['NEED_FOR_REBUILD_DEAL_ATTRS'] = false;
if (CCrmPerms::IsAdmin() && COption::GetOptionString('crm', '~CRM_REBUILD_DEAL_ATTR', 'N') === 'Y')
{
	$arResult['NEED_FOR_REBUILD_DEAL_ATTRS'] = true;
}

$arResult['NEED_FOR_REBUILD_LEAD_ATTRS'] = false;
if (CCrmPerms::IsAdmin() && COption::GetOptionString('crm', '~CRM_REBUILD_LEAD_ATTR', 'N') === 'Y')
{
	$arResult['NEED_FOR_REBUILD_LEAD_ATTRS'] = true;
}

$arResult['NEED_FOR_REBUILD_QUOTE_ATTRS'] = false;
if (CCrmPerms::IsAdmin() && COption::GetOptionString('crm', '~CRM_REBUILD_QUOTE_ATTR', 'N') === 'Y')
{
	$arResult['NEED_FOR_REBUILD_QUOTE_ATTRS'] = true;
}

$arResult['NEED_FOR_REBUILD_INVOICE_ATTRS'] = false;
if (CCrmPerms::IsAdmin() && COption::GetOptionString('crm', '~CRM_REBUILD_INVOICE_ATTR', 'N') === 'Y')
{
	$arResult['NEED_FOR_REBUILD_INVOICE_ATTRS'] = true;
}

$this->IncludeComponentTemplate($componentPage);
