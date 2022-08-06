<?php

use Bitrix\Crm\Settings\InvoiceSettings;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('bizproc') || !CBPRuntime::isFeatureEnabled())
{
	ShowError(GetMessage('BIZPROC_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('bizprocdesigner'))
{
	ShowError(GetMessage('BIZPROCDESIGNER_MODULE_NOT_INSTALLED'));
	return;
}

\Bitrix\Crm\Service\Container::getInstance()->getLocalization()->loadMessages();

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arTypes = [
	'CRM_LEAD' => [
		'ID' => 'CRM_LEAD',
		'NAME' => GetMessage('CRM_BP_LEAD'),
		'DOCUMENT' => 'CCrmDocumentLead',
		'TYPE' => 'LEAD'
	],
	'CRM_CONTACT' => [
		'ID' => 'CRM_CONTACT',
		'NAME' => GetMessage('CRM_BP_CONTACT'),
		'DOCUMENT' => 'CCrmDocumentContact',
		'TYPE' => 'CONTACT'
	],
	'CRM_COMPANY' => [
		'ID' => 'CRM_COMPANY',
		'NAME' => GetMessage('CRM_BP_COMPANY'),
		'DOCUMENT' => 'CCrmDocumentCompany',
		'TYPE' => 'COMPANY'
	],
	'CRM_DEAL' => [
		'ID' => 'CRM_DEAL',
		'NAME' => GetMessage('CRM_BP_DEAL'),
		'DOCUMENT' => 'CCrmDocumentDeal',
		'TYPE' => 'DEAL'
	],
	'CRM_ORDER' => [
		'ID' => 'CRM_ORDER',
		'NAME' => GetMessage('CRM_BP_ORDER'),
		'DOCUMENT' => \Bitrix\Crm\Integration\BizProc\Document\Order::class,
		'TYPE' => 'ORDER'
	],
	'CRM_QUOTE' => [
		'ID' => 'CRM_QUOTE',
		'NAME' => \Bitrix\Main\Localization\Loc::getMessage('CRM_COMMON_QUOTE'),
		'DOCUMENT' => \Bitrix\Crm\Integration\BizProc\Document\Quote::class,
		'TYPE' => 'QUOTE',
	],
];

if (InvoiceSettings::getCurrent()->isSmartInvoiceEnabled())
{
	$arTypes['CRM_SMART_INVOICE'] = [
		'ID' => 'CRM_SMART_INVOICE',
		'NAME' => \CCrmOwnerType::GetCategoryCaption(\CCrmOwnerType::SmartInvoice),
		'DOCUMENT' => \Bitrix\Crm\Integration\BizProc\Document\SmartInvoice::class,
		'TYPE' => 'SMART_INVOICE',
	];
}

$dynamicTypesMap = \Bitrix\Crm\Service\Container::getInstance()->getDynamicTypesMap();
$dynamicTypesMap->load([
	'isLoadStages' => false,
	'isLoadCategories' => false,
]);

foreach ($dynamicTypesMap->getTypes() as $type)
{
	if (\Bitrix\Crm\Automation\Factory::isBizprocDesignerSupported($type->getEntityTypeId()))
	{
		$typeId = "CRM_DYNAMIC_{$type->getEntityTypeId()}";

		$arTypes[$typeId] = [
			'ID' => $typeId,
			'NAME' => $type->getTitle(),
			'DOCUMENT' => \Bitrix\Crm\Integration\BizProc\Document\Dynamic::class,
			'TYPE' => CCrmOwnerType::ResolveName($type->getEntityTypeId()),
		];
	}
}

$arResult['ENTITY_ID'] = isset($_REQUEST['entity_id']) ? $_REQUEST['entity_id']: $arParams['BP_ENTITY_ID'];
$arResult['BP_ID'] = isset($_REQUEST['bp_id']) ? $_REQUEST['bp_id']: $arParams['BP_BP_ID'];
$arResult['ENTITY_NAME'] = $arTypes[$arResult['ENTITY_ID']]['NAME'];
$arResult['DOCUMENT_TYPE'] = $arTypes[$arResult['ENTITY_ID']]['TYPE'];
$arResult['ENTITY_TYPE'] = $arTypes[$arResult['ENTITY_ID']]['DOCUMENT'];

if (!$arResult['DOCUMENT_TYPE'] || !$arResult['ENTITY_TYPE'])
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

define('CRM_ENTITY', $arResult['ENTITY_TYPE']);

$arResult['IS_BIZPROC_DESIGNER_ENABLED'] = \Bitrix\Crm\Automation\Factory::isBizprocDesignerEnabled(
	CCrmOwnerType::ResolveID($arResult['DOCUMENT_TYPE'])
);

if ($arResult['DOCUMENT_TYPE'] === 'ORDER')
{
	$arResult['IS_BIZPROC_DESIGNER_ENABLED'] = false;
}

$arResult['~ENTITY_LIST_URL'] = $arParams['~ENTITY_LIST_URL'];
$arResult['ENTITY_LIST_URL'] = htmlspecialcharsbx($arResult['~ENTITY_LIST_URL']);

$arResult['~BP_LIST_URL'] = str_replace('#entity_id#', $arResult['ENTITY_ID'], $arParams['~BP_LIST_URL']);
$arResult['BP_LIST_URL'] = htmlspecialcharsbx($arResult['~BP_LIST_URL']);

$arResult['~BP_EDIT_URL'] = str_replace(['#entity_id#'], [$arResult['ENTITY_ID']], $arParams['~BP_EDIT_URL']);
$arResult['BP_EDIT_URL'] = htmlspecialcharsbx($arResult['~BP_EDIT_URL']);

$arTemplate = null;
if ($arResult['BP_ID'] != '')
{
	$db_res = CBPWorkflowTemplateLoader::GetList(
		[$by => $order],
		[
			'DOCUMENT_TYPE' => ['crm', $arResult['ENTITY_TYPE'], $arResult['DOCUMENT_TYPE']],
			'ID' => $arResult['BP_ID']
		],
		false,
		false,
		['ID', 'NAME']
	);
	if ($db_res)
	{
		$arTemplate = $db_res->Fetch();
	}
}

$this->IncludeComponentTemplate();

if ($arTemplate && $arTemplate['NAME'])
{
	$title = GetMessage('CRM_BP_WFEDIT_TITLE_EDIT', [
		'#TEMPLATE#' => htmlspecialcharsbx($arTemplate['NAME']),
		'#NAME#' => $arResult['ENTITY_NAME']
	]);
}
else
{
	$title = GetMessage('CRM_BP_WFEDIT_TITLE_ADD', ['#NAME#' => $arResult['ENTITY_NAME']]);
}

$APPLICATION->SetTitle($title);
$APPLICATION->AddChainItem(GetMessage('CRM_BP_ENTITY_LIST'), $arResult['~ENTITY_LIST_URL']);
$APPLICATION->AddChainItem($arResult['ENTITY_NAME'], $arResult['~BP_LIST_URL']);
if ($arTemplate && $arTemplate['NAME'])
{
	$APPLICATION->AddChainItem($arTemplate['NAME'],
		CComponentEngine::MakePathFromTemplate(
			$arResult['~BP_EDIT_URL'],
			['bp_id' => $arResult['BP_ID']]
		)
	);
}
