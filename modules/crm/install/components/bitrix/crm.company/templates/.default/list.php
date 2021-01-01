<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

/** @var array $arResult */
$cpID = ($arResult['MYCOMPANY_MODE'] === 'Y') ? 'MYCOMPANY_LIST' : 'COMPANY_LIST';
$cpActiveItemID = ($arResult['MYCOMPANY_MODE'] === 'Y') ? '' : 'COMPANY';

$isMyCompanyMode = (isset($arResult['MYCOMPANY_MODE']) && $arResult['MYCOMPANY_MODE'] === 'Y');

if ($_REQUEST['IFRAME'] !== 'Y')
{
	/** @var CMain $APPLICATION */
	$APPLICATION->IncludeComponent(
		'bitrix:crm.control_panel',
		'',
		array(
			'ID'                       => $cpID,
			'ACTIVE_ITEM_ID'           => $cpActiveItemID,
			'PATH_TO_COMPANY_LIST'     => (isset($arResult['PATH_TO_COMPANY_LIST']) && !$isMyCompanyMode) ? $arResult['PATH_TO_COMPANY_LIST'] : '',
			'PATH_TO_COMPANY_EDIT'     => (isset($arResult['PATH_TO_COMPANY_EDIT']) && !$isMyCompanyMode) ? $arResult['PATH_TO_COMPANY_EDIT'] : '',
			'PATH_TO_CONTACT_LIST'     => isset($arResult['PATH_TO_CONTACT_LIST']) ? $arResult['PATH_TO_CONTACT_LIST'] : '',
			'PATH_TO_CONTACT_EDIT'     => isset($arResult['PATH_TO_CONTACT_EDIT']) ? $arResult['PATH_TO_CONTACT_EDIT'] : '',
			'PATH_TO_DEAL_LIST'        => isset($arResult['PATH_TO_DEAL_LIST']) ? $arResult['PATH_TO_DEAL_LIST'] : '',
			'PATH_TO_DEAL_EDIT'        => isset($arResult['PATH_TO_DEAL_EDIT']) ? $arResult['PATH_TO_DEAL_EDIT'] : '',
			'PATH_TO_LEAD_LIST'        => isset($arResult['PATH_TO_LEAD_LIST']) ? $arResult['PATH_TO_LEAD_LIST'] : '',
			'PATH_TO_LEAD_EDIT'        => isset($arResult['PATH_TO_LEAD_EDIT']) ? $arResult['PATH_TO_LEAD_EDIT'] : '',
			'PATH_TO_QUOTE_LIST'       => isset($arResult['PATH_TO_QUOTE_LIST']) ? $arResult['PATH_TO_QUOTE_LIST'] : '',
			'PATH_TO_QUOTE_EDIT'       => isset($arResult['PATH_TO_QUOTE_EDIT']) ? $arResult['PATH_TO_QUOTE_EDIT'] : '',
			'PATH_TO_INVOICE_LIST'     => isset($arResult['PATH_TO_INVOICE_LIST']) ? $arResult['PATH_TO_INVOICE_LIST'] : '',
			'PATH_TO_INVOICE_EDIT'     => isset($arResult['PATH_TO_INVOICE_EDIT']) ? $arResult['PATH_TO_INVOICE_EDIT'] : '',
			'PATH_TO_REPORT_LIST'      => isset($arResult['PATH_TO_REPORT_LIST']) ? $arResult['PATH_TO_REPORT_LIST'] : '',
			'PATH_TO_DEAL_FUNNEL'      => isset($arResult['PATH_TO_DEAL_FUNNEL']) ? $arResult['PATH_TO_DEAL_FUNNEL'] : '',
			'PATH_TO_EVENT_LIST'       => isset($arResult['PATH_TO_EVENT_LIST']) ? $arResult['PATH_TO_EVENT_LIST'] : '',
			'PATH_TO_PRODUCT_LIST'     => isset($arResult['PATH_TO_PRODUCT_LIST']) ? $arResult['PATH_TO_PRODUCT_LIST'] : '',
			'MYCOMPANY_MODE'           => ($arResult['MYCOMPANY_MODE'] === 'Y' ? 'Y' : 'N'),
			'PATH_TO_COMPANY_WIDGET'   => isset($arResult['PATH_TO_COMPANY_WIDGET']) ? $arResult['PATH_TO_COMPANY_WIDGET'] : '',
			'PATH_TO_COMPANY_PORTRAIT' => isset($arResult['PATH_TO_COMPANY_PORTRAIT']) ? $arResult['PATH_TO_COMPANY_PORTRAIT'] : ''
		),
		$component
	);
}

if(!Bitrix\Crm\Integration\Bitrix24Manager::isAccessEnabled(CCrmOwnerType::Company))
{
	$APPLICATION->IncludeComponent('bitrix:bitrix24.business.tools.info', '', array());
}
else
{
	$isBitrix24Template = SITE_TEMPLATE_ID === 'bitrix24';
	if($isBitrix24Template)
	{
		$this->SetViewTarget('below_pagetitle', 0);
	}

	if (!$isMyCompanyMode)
	{
		$APPLICATION->IncludeComponent(
			'bitrix:crm.entity.counter.panel',
			'',
			array(
				'ENTITY_TYPE_NAME' => CCrmOwnerType::CompanyName,
				'EXTRAS' => array(),
				'PATH_TO_ENTITY_LIST' => $arResult['PATH_TO_COMPANY_LIST']
			)
		);
	}

	if($isBitrix24Template)
	{
		$this->EndViewTarget();
	}

	$APPLICATION->ShowViewContent('crm-grid-filter');

	if ($_REQUEST['IFRAME'] !== 'Y')
	{
		$APPLICATION->IncludeComponent(
			'bitrix:crm.company.menu',
			'',
			array(
				'PATH_TO_COMPANY_LIST'         => $arResult['PATH_TO_COMPANY_LIST'],
				'PATH_TO_COMPANY_SHOW'         => $arResult['PATH_TO_COMPANY_SHOW'],
				'PATH_TO_COMPANY_EDIT'         => $arResult['PATH_TO_COMPANY_EDIT'],
				'PATH_TO_COMPANY_IMPORT'       => $arResult['PATH_TO_COMPANY_IMPORT'],
				'PATH_TO_COMPANY_DEDUPE'       => $arResult['PATH_TO_COMPANY_DEDUPE'],
				'PATH_TO_COMPANY_DEDUPEWIZARD' => $arResult['PATH_TO_COMPANY_DEDUPEWIZARD'],
				'NAME_TEMPLATE'                => $arParams['NAME_TEMPLATE'],
				'ELEMENT_ID'                   => $arResult['VARIABLES']['company_id'],
				'TYPE'                         => 'list',
				'MYCOMPANY_MODE'               => ($arResult['MYCOMPANY_MODE'] === 'Y' ? 'Y' : 'N')
			),
			$component
		);

		if(\Bitrix\Main\ModuleManager::isModuleInstalled('rest'))
		{
			$APPLICATION->IncludeComponent(
				'bitrix:app.placement',
				'menu',
				array(
					'PLACEMENT' => "CRM_COMPANY_LIST_MENU",
					"PLACEMENT_OPTIONS" => array(),
					'INTERFACE_EVENT' => 'onCrmCompanyMenuInterfaceInit',
					'MENU_EVENT_MODULE' => 'crm',
					'MENU_EVENT' => 'onCrmCompanyListItemBuildMenu',
				),
				null,
				array('HIDE_ICONS' => 'Y')
			);
		}
	}

	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		array(
			'POPUP_COMPONENT_NAME' => 'bitrix:crm.company.list',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_PARAMS' => [
				'COMPANY_COUNT' => '20',
				'PATH_TO_COMPANY_LIST' => $arResult['PATH_TO_COMPANY_LIST'],
				'PATH_TO_COMPANY_SHOW' => $arResult['PATH_TO_COMPANY_SHOW'],
				'PATH_TO_COMPANY_EDIT' => $arResult['PATH_TO_COMPANY_EDIT'],
				'PATH_TO_COMPANY_WIDGET' => $arResult['PATH_TO_COMPANY_WIDGET'],
				'PATH_TO_CONTACT_EDIT' => $arResult['PATH_TO_CONTACT_EDIT'],
				'PATH_TO_DEAL_EDIT' => $arResult['PATH_TO_DEAL_EDIT'],
				'PATH_TO_COMPANY_MERGE' => $arResult['PATH_TO_COMPANY_MERGE'],
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
				'MYCOMPANY_MODE' => ($arResult['MYCOMPANY_MODE'] === 'Y' ? 'Y' : 'N'),
				'NAVIGATION_CONTEXT_ID' => $arResult['NAVIGATION_CONTEXT_ID']
			],
			'USE_PADDING' => false,
			'CLOSE_AFTER_SAVE' => true,
			'RELOAD_PAGE_AFTER_SAVE' => false,
			'USE_LINK_TARGETS_REPLACING' => true,
		)
	);
}
?>