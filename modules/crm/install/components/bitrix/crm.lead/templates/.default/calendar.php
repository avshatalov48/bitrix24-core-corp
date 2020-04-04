<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Integration\Calendar;

//show the crm type popup (with or without leads)
if (!\Bitrix\Crm\Settings\LeadSettings::isEnabled())
{
	CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/common.js');
	?><script><?=\Bitrix\Crm\Settings\LeadSettings::showCrmTypePopup();?></script><?
}

/** @var CMain $APPLICATION */
$APPLICATION->IncludeComponent(
	'bitrix:crm.control_panel',
	'',
	array(
		'ID' => 'LEAD_LIST',
		'ACTIVE_ITEM_ID' => 'LEAD',
		'PATH_TO_COMPANY_LIST' => isset($arResult['PATH_TO_COMPANY_LIST']) ? $arResult['PATH_TO_COMPANY_LIST'] : '',
		'PATH_TO_COMPANY_EDIT' => isset($arResult['PATH_TO_COMPANY_EDIT']) ? $arResult['PATH_TO_COMPANY_EDIT'] : '',
		'PATH_TO_CONTACT_LIST' => isset($arResult['PATH_TO_CONTACT_LIST']) ? $arResult['PATH_TO_CONTACT_LIST'] : '',
		'PATH_TO_CONTACT_EDIT' => isset($arResult['PATH_TO_CONTACT_EDIT']) ? $arResult['PATH_TO_CONTACT_EDIT'] : '',
		'PATH_TO_DEAL_LIST' => isset($arResult['PATH_TO_DEAL_LIST']) ? $arResult['PATH_TO_DEAL_LIST'] : '',
		'PATH_TO_DEAL_EDIT' => isset($arResult['PATH_TO_DEAL_EDIT']) ? $arResult['PATH_TO_DEAL_EDIT'] : '',
		'PATH_TO_LEAD_LIST' => isset($arResult['PATH_TO_LEAD_LIST']) ? $arResult['PATH_TO_LEAD_LIST'] : '',
		'PATH_TO_LEAD_EDIT' => isset($arResult['PATH_TO_LEAD_EDIT']) ? $arResult['PATH_TO_LEAD_EDIT'] : '',
		'PATH_TO_QUOTE_LIST' => isset($arResult['PATH_TO_QUOTE_LIST']) ? $arResult['PATH_TO_QUOTE_LIST'] : '',
		'PATH_TO_QUOTE_EDIT' => isset($arResult['PATH_TO_QUOTE_EDIT']) ? $arResult['PATH_TO_QUOTE_EDIT'] : '',
		'PATH_TO_INVOICE_LIST' => isset($arResult['PATH_TO_INVOICE_LIST']) ? $arResult['PATH_TO_INVOICE_LIST'] : '',
		'PATH_TO_INVOICE_EDIT' => isset($arResult['PATH_TO_INVOICE_EDIT']) ? $arResult['PATH_TO_INVOICE_EDIT'] : '',
		'PATH_TO_REPORT_LIST' => isset($arResult['PATH_TO_REPORT_LIST']) ? $arResult['PATH_TO_REPORT_LIST'] : '',
		'PATH_TO_DEAL_FUNNEL' => isset($arResult['PATH_TO_DEAL_FUNNEL']) ? $arResult['PATH_TO_DEAL_FUNNEL'] : '',
		'PATH_TO_EVENT_LIST' => isset($arResult['PATH_TO_EVENT_LIST']) ? $arResult['PATH_TO_EVENT_LIST'] : '',
		'PATH_TO_PRODUCT_LIST' => isset($arResult['PATH_TO_PRODUCT_LIST']) ? $arResult['PATH_TO_PRODUCT_LIST'] : ''
	),
	$component
);

if(!Bitrix\Crm\Integration\Bitrix24Manager::isAccessEnabled(CCrmOwnerType::Lead))
{
	$APPLICATION->IncludeComponent('bitrix:bitrix24.business.tools.info', '', array());
}
elseif (\Bitrix\Main\Loader::includeModule('calendar'))
{
	\CJSCore::Init(array('userfield_resourcebooking'));
	$isBitrix24Template = SITE_TEMPLATE_ID === 'bitrix24';
	if($isBitrix24Template)
	{
		$this->SetViewTarget('below_pagetitle', 0);
	}

	$APPLICATION->IncludeComponent(
		'bitrix:crm.entity.counter.panel',
		'',
		array(
			'ENTITY_TYPE_NAME' => CCrmOwnerType::LeadName,
			'EXTRAS' => array(),
			'PATH_TO_ENTITY_LIST' => $arResult['PATH_TO_LEAD_LIST']
		)
	);

	if($isBitrix24Template)
	{
		$this->EndViewTarget();
	}

	$APPLICATION->ShowViewContent('crm-grid-filter');

	$settingsFilterSelect = CUserOptions::GetOption("calendar", "resourceBooking");
	$filterSelect = $settingsFilterSelect[CCrmOwnerType::LeadName];

	$modeList = array();
	$settingsParams = array(
		'entityType' => CCrmOwnerType::LeadName,
		'filterSelectValues' => array(
			array('TEXT' => Loc::getMessage('CRM_CALENDAR_SETTINGS_DATE'), 'VALUE' => 'DATE_CREATE')
		),
		'filterSelect' => $filterSelect
	);

	$userFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields("CRM_LEAD", 0, LANGUAGE_ID);
	$selectedField = $filterSelect == 'DATE_CREATE';

	foreach ($userFields as $userField)
	{
		if ($userField['USER_TYPE_ID'] == 'resourcebooking' && $userField['MULTIPLE'] == 'Y'
			|| $userField['USER_TYPE_ID'] == 'date' && $userField['MULTIPLE'] == 'N'
			|| $userField['USER_TYPE_ID'] == 'datetime' && $userField['MULTIPLE'] == 'N')
		{
			if (!Calendar::isUserfieldShownInForm($userField, "CRM_LEAD", $categoryID))
			{
				continue;
			}

			$settingsParams['filterSelectValues'][] = array(
				'TEXT' => Loc::getMessage('CRM_CALENDAR_SETTINGS_PROPERTY', array('#USERFIELD_NAME#' => $userField['EDIT_FORM_LABEL'])),
				'VALUE' => Calendar::getUserfieldKey($userField)
			);

			$modeList[] = array(
				'id' => Calendar::getUserfieldKey($userField),
				'label' => Loc::getMessage('CRM_CALENDAR_VIEW_MODE_USERFIELD', array('#USERFIELD_NAME#' => $userField['EDIT_FORM_LABEL'])),
				'selected' => $filterSelect == Calendar::getUserfieldKey($userField)
			);

			if (!$selectedField)
			{
				$selectedField = $filterSelect == Calendar::getUserfieldKey($userField);
			}
		}
	}

	if (!$selectedField)
	{
		$filterSelect = 'DATE_CREATE';
		$settingsParams['filterSelect'] = $filterSelect;
		$settingsFilterSelect[CCrmOwnerType::LeadName] = $filterSelect;
		CUserOptions::SetOption("calendar", "resourceBooking", $settingsFilterSelect);
	}

	$modeList = array_merge(array(
		array(
			'id' => 'DATE_CREATE',
			'label' => Loc::getMessage('CRM_CALENDAR_VIEW_MODE_DATECREATE'),
			'selected' => $filterSelect == 'DATE_CREATE'
		)
	), $modeList);

	$APPLICATION->IncludeComponent(
		'bitrix:crm.lead.menu',
		'',
		array(
			'PATH_TO_LEAD_LIST' => $arResult['PATH_TO_LEAD_LIST'],
			'PATH_TO_LEAD_SHOW' => $arResult['PATH_TO_LEAD_SHOW'],
			'PATH_TO_LEAD_EDIT' => $arResult['PATH_TO_LEAD_EDIT'],
			'ELEMENT_ID' => 0,
			'DISABLE_EXPORT' => 'Y',
			'DISABLE_DEDUPE' => 'Y',
			'DISABLE_IMPORT' => 'Y',
			'TYPE' => 'list',
			'ADDITIONAL_SETTINGS_MENU_ITEMS' => array(
				array(
					'TEXT' => Loc::getMessage('CRM_CALENDAR_SETTINGS'),
					'ONCLICK' => 'BX.Calendar.UserField.ResourceBooking.openExternalSettingsSlider('.\Bitrix\Main\Web\Json::encode($settingsParams).')'
				)
			)
		),
		$component
	);

	$calendarDateFrom = false;
	$calendarDateTo = false;

	if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
	{
		$request = \Bitrix\Main\Context::getCurrent()->getRequest()->toArray();
		if(isset($request['crm_calendar_action'])
			&& !empty($request['crm_calendar_start_date'])
			&& !empty($request['crm_calendar_finish_date']))
		{
			$calendarDateFrom = \CCalendar::Date(\CCalendar::Timestamp($request['crm_calendar_start_date']), false);
			$calendarDateTo = \CCalendar::Date(\CCalendar::Timestamp($request['crm_calendar_finish_date']), false);
		}
	}

	if (!$calendarDateFrom || !$calendarDateTo)
	{
		$calendarDateFrom = \CCalendar::Date(mktime(0, 0, 0, date("m") - 1, 1, date("Y")), false);
		$calendarDateTo = \CCalendar::Date(mktime(0, 0, 0, date("m") + 2, 0, date("Y")), false);
	}

	if (!isset($filterSelect))
	{
		$settingsFilterSelect = CUserOptions::GetOption("calendar", "resourceBooking");
		$filterSelect = $settingsFilterSelect[CCrmOwnerType::LeadName];
		if (empty($filterSelect))
		{
			$filterSelect = 'DATE_CREATE';
		}
	}

	$APPLICATION->IncludeComponent(
		'bitrix:crm.lead.list',
		'calendar',
		array(
			'LEAD_COUNT' => '3000',
			'INTERNAL_SORT' => ['id' => 'asc'],
			'PATH_TO_LEAD_SHOW' => $arResult['PATH_TO_LEAD_SHOW'],
			'PATH_TO_LEAD_EDIT' => $arResult['PATH_TO_LEAD_EDIT'],
			'PATH_TO_LEAD_CONVERT' => $arResult['PATH_TO_LEAD_CONVERT'],
			'PATH_TO_LEAD_WIDGET' => $arResult['PATH_TO_LEAD_WIDGET'],
			'PATH_TO_LEAD_KANBAN' => $arResult['PATH_TO_LEAD_KANBAN'],
			'PATH_TO_LEAD_CALENDAR' => $arResult['PATH_TO_LEAD_CALENDAR'],
			'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
			'NAVIGATION_CONTEXT_ID' => $arResult['NAVIGATION_CONTEXT_ID'],
			'ADDITIONAL_FILTER' => array(
				'CALENDAR_DATE_FROM' => $calendarDateFrom,
				'CALENDAR_DATE_TO' => $calendarDateTo,
				'CALENDAR_FIELD' => $filterSelect
			),
			'CALENDAR_MODE_LIST' => $modeList,
			'ENABLE_BIZPROC' => 'N'
		),
		$component
	);

	\Bitrix\Crm\Integration\Calendar::showViewModeCalendarSpotlight(CCrmOwnerType::LeadName);
}
?>