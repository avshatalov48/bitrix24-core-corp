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

\Bitrix\Crm\Service\Container::getInstance()->getLocalization()->loadMessages();

/**
 * @var array $arParams
 * @var CUser $USER
 */

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
		'DESC' => GetMessage('CRM_BP_LEAD_DESC')
	],
	'CRM_CONTACT' => [
		'ID' => 'CRM_CONTACT',
		'NAME' => GetMessage('CRM_BP_CONTACT'),
		'DESC' => GetMessage('CRM_BP_CONTACT_DESC')
	],
	'CRM_COMPANY' => [
		'ID' => 'CRM_COMPANY',
		'NAME' => GetMessage('CRM_BP_COMPANY'),
		'DESC' => GetMessage('CRM_BP_COMPANY_DESC')
	],
	'CRM_DEAL'=> [
		'ID' => 'CRM_DEAL',
		'NAME' => GetMessage('CRM_BP_DEAL'),
		'DESC' => GetMessage('CRM_BP_DEAL_DESC')
	],
	'CRM_QUOTE' => [
		'ID' => 'CRM_QUOTE',
		'NAME' => \Bitrix\Main\Localization\Loc::getMessage('CRM_COMMON_QUOTE_MSGVER_1'),
		'DESC' => GetMessage('CRM_BP_QUOTE_DESC_MSGVER_1'),
	],
];

if (InvoiceSettings::getCurrent()->isSmartInvoiceEnabled())
{
	$arTypes['CRM_SMART_INVOICE'] = [
		'ID' => 'CRM_SMART_INVOICE',
		'NAME' => \CCrmOwnerType::GetCategoryCaption(\CCrmOwnerType::SmartInvoice),
		'DESC' => GetMessage('CRM_BP_SMART_INVOICE_DESC'),
	];
}

$dynamicTypesMap = \Bitrix\Crm\Service\Container::getInstance()->getDynamicTypesMap();
$dynamicTypesMap->load([
	'isLoadStages' => false,
	'isLoadCategories' => false,
]);

foreach ($dynamicTypesMap->getTypes() as $type)
{
	if (\Bitrix\Crm\Automation\Factory::isBizprocDesignerEnabled($type->getEntityTypeId()))
	{
		$typeId = "CRM_DYNAMIC_{$type->getEntityTypeId()}";
		$typeName = htmlspecialcharsbx($type->getTitle());

		$arTypes[$typeId] = [
			'ID' => $typeId,
			'NAME' => $typeName,
			'DESC' => GetMessage('CRM_BP_DYNAMIC_DESC', ['#DYNAMIC_TYPE_NAME#' => $typeName]),
		];
	}
}

foreach ($arTypes as $key => $ar)
{
	$arResult['ROWS'][$ar['ID']] = $ar;
	$arResult['ROWS'][$ar['ID']]['LINK_LIST'] = str_replace('#entity_id#', $ar['ID'], $arParams['~BP_LIST_URL']);
	$arResult['ROWS'][$ar['ID']]['LINK_ADD'] = str_replace(
		['#entity_id#', '#bp_id#'],
		[$ar['ID'], 0],
		$arParams['~BP_EDIT_URL']
	);
}

$this->IncludeComponentTemplate();

$APPLICATION->AddChainItem(GetMessage('CRM_BP_ENTITY_LIST'), $arResult['~ENTITY_LIST_URL']);
