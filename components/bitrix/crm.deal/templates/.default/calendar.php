<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$categoryID = isset($arResult['VARIABLES']['category_id']) ? (int)$arResult['VARIABLES']['category_id'] : -1;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Integration\Calendar;

/** @var CMain $APPLICATION */
$APPLICATION->IncludeComponent(
	'bitrix:crm.control_panel',
	'',
	array(
		'ID' => 'DEAL_LIST',
		'ACTIVE_ITEM_ID' => 'DEAL',
		'PATH_TO_COMPANY_LIST' => isset($arResult['PATH_TO_COMPANY_LIST']) ? $arResult['PATH_TO_COMPANY_LIST'] : '',
		'PATH_TO_COMPANY_EDIT' => isset($arResult['PATH_TO_COMPANY_EDIT']) ? $arResult['PATH_TO_COMPANY_EDIT'] : '',
		'PATH_TO_CONTACT_LIST' => isset($arResult['PATH_TO_CONTACT_LIST']) ? $arResult['PATH_TO_CONTACT_LIST'] : '',
		'PATH_TO_CONTACT_EDIT' => isset($arResult['PATH_TO_CONTACT_EDIT']) ? $arResult['PATH_TO_CONTACT_EDIT'] : '',
		'PATH_TO_DEAL_WIDGET' => isset($arResult['PATH_TO_DEAL_WIDGET']) ? $arResult['PATH_TO_DEAL_WIDGET'] : '',
		'PATH_TO_DEAL_LIST' => isset($arResult['PATH_TO_DEAL_LIST']) ? $arResult['PATH_TO_DEAL_LIST'] : '',
		'PATH_TO_DEAL_EDIT' => isset($arResult['PATH_TO_DEAL_EDIT']) ? $arResult['PATH_TO_DEAL_EDIT'] : '',
		'PATH_TO_DEAL_CATEGORY' => isset($arResult['PATH_TO_DEAL_CATEGORY']) ? $arResult['PATH_TO_DEAL_CATEGORY'] : '',
		'PATH_TO_DEAL_WIDGETCATEGORY' => isset($arResult['PATH_TO_DEAL_WIDGETCATEGORY']) ? $arResult['PATH_TO_DEAL_WIDGETCATEGORY'] : '',
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

if(!Bitrix\Crm\Integration\Bitrix24Manager::isAccessEnabled(CCrmOwnerType::Deal))
{
	$APPLICATION->IncludeComponent('bitrix:bitrix24.business.tools.info', '', array());
}
elseif (\Bitrix\Main\Loader::includeModule('calendar'))
{
	Calendar::loadResourcebookingUserfieldExtention();
	$isBitrix24Template = SITE_TEMPLATE_ID === 'bitrix24';

	if ($arResult['IS_RECURRING'] !== 'Y')
	{
		$APPLICATION->IncludeComponent(
			'bitrix:crm.entity.counter.panel',
			'',
			array(
				'ENTITY_TYPE_NAME' => CCrmOwnerType::DealName,
				'EXTRAS' => array('DEAL_CATEGORY_ID' => $categoryID),
				'PATH_TO_ENTITY_LIST' =>
					$categoryID < 0
						? $arResult['PATH_TO_DEAL_LIST']
						: CComponentEngine::makePathFromTemplate(
						$arResult['PATH_TO_DEAL_CATEGORY'],
						array('category_id' => $categoryID)
					)
			)
		);
	}

	if($isBitrix24Template)
	{
		$this->SetViewTarget('inside_pagetitle', 100);
	}

	$catalogPath = ($arResult['IS_RECURRING'] !== 'Y') ? $arResult['PATH_TO_DEAL_CATEGORY'] : $arResult['PATH_TO_DEAL_RECUR_CATEGORY'];

	if($isBitrix24Template)
	{
		$this->SetViewTarget('inside_pagetitle', 100);
	}

	if($isBitrix24Template)
	{
		$this->EndViewTarget();
	}
	$APPLICATION->ShowViewContent('crm-grid-filter');

	if (!isset($filterSelect))
	{
		$settingsFilterSelect = CUserOptions::GetOption("calendar", "resourceBooking");
		$filterSelect = $settingsFilterSelect[CCrmOwnerType::DealName];
	}

	$settingsParams = array(
		'entityType' => CCrmOwnerType::DealName,
		'filterSelectValues' => array(
			array('TEXT' => Loc::getMessage('CRM_CALENDAR_SETTINGS_DATE'), 'VALUE' => 'DATE_CREATE'),
			array('TEXT' => Loc::getMessage('CRM_CALENDAR_SETTINGS_CLOSEDATE'), 'VALUE' => 'CLOSEDATE')
		),
		'filterSelect' => $filterSelect
	);
	$modeList = array();

	$userFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields("CRM_DEAL", 0, LANGUAGE_ID);
	$selectedField = $filterSelect == 'CLOSEDATE' || $filterSelect == 'DATE_CREATE';

	$editorConfig = \Bitrix\Crm\Entity\EntityEditorConfig::createWithCurrentScope(
		CCrmOwnerType::Deal,
		[
			'DEAL_CATEGORY_ID' => $categoryID,
		]
	);

	foreach ($userFields as $userField)
	{
		if ($userField['USER_TYPE_ID'] == 'resourcebooking' && $userField['MULTIPLE'] == 'Y'
			|| $userField['USER_TYPE_ID'] == 'date' && $userField['MULTIPLE'] == 'N'
			|| $userField['USER_TYPE_ID'] == 'datetime' && $userField['MULTIPLE'] == 'N')
		{
			if (!$editorConfig->isFormFieldVisible($userField['FIELD_NAME']))
			{
				continue;
			}

			$settingsParams['filterSelectValues'][] = array(
				'TEXT' => Loc::getMessage('CRM_CALENDAR_SETTINGS_PROPERTY', array('#USERFIELD_NAME#' => $userField['EDIT_FORM_LABEL'])),
				'VALUE' => Calendar::getUserfieldKey($userField)
			);

			$modeList[] = array(
				'id' => Calendar::getUserfieldKey($userField),
				'label' => Loc::getMessage('CRM_CALENDAR_DEAL_VIEW_MODE_USERFIELD', array('#USERFIELD_NAME#' => $userField['EDIT_FORM_LABEL'])),
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
		$filterSelect = 'CLOSEDATE';
		$settingsParams['filterSelect'] = $filterSelect;
		$settingsFilterSelect[CCrmOwnerType::DealName] = $filterSelect;
		CUserOptions::SetOption("calendar", "resourceBooking", $settingsFilterSelect);
	}

	$modeList = array_merge(array(
			array(
				'id' => 'DATE_CREATE',
				'label' => Loc::getMessage('CRM_CALENDAR_DEAL_VIEW_MODE_DATECREATE'),
				'selected' => $filterSelect == 'DATE_CREATE'
			),
			array(
				'id' => 'CLOSEDATE',
				'label' => Loc::getMessage('CRM_CALENDAR_DEAL_VIEW_MODE_CLOSEDATE'),
				'selected' => $filterSelect == 'CLOSEDATE'
			)
		), $modeList);

	$APPLICATION->IncludeComponent(
		'bitrix:crm.deal.menu',
		'',
		array(
			'PATH_TO_DEAL_LIST' => $arResult['PATH_TO_DEAL_LIST'],
			'PATH_TO_DEAL_SHOW' => $arResult['PATH_TO_DEAL_SHOW'],
			'PATH_TO_DEAL_EDIT' => $arResult['PATH_TO_DEAL_EDIT'],
			'PATH_TO_DEAL_RECUR' => $arResult['PATH_TO_DEAL_RECUR'],
			'PATH_TO_DEAL_RECUR_CATEGORY' => $arResult['PATH_TO_DEAL_RECUR_CATEGORY'],
			'ELEMENT_ID' => 0,
			'DISABLE_EXPORT' => 'Y',
			'DISABLE_DEDUPE' => 'Y',
			'DISABLE_IMPORT' => 'Y',
			'TYPE' => 'list',
			'CATEGORY_ID' => $categoryID,
			'ADDITIONAL_SETTINGS_MENU_ITEMS' => array(
				array(
					'TEXT' => Loc::getMessage('CRM_CALENDAR_SETTINGS'),
					'ONCLICK' => Calendar::getCalendarSettingsOpenJs($settingsParams)
				)
			)
		),
		$component
	);

	$APPLICATION->IncludeComponent(
		'bitrix:crm.deal_category.panel',
		$isBitrix24Template ? 'tiny' : '',
		array(
			'PATH_TO_DEAL_LIST' => $arResult['PATH_TO_DEAL_CALENDAR'],
			'PATH_TO_DEAL_EDIT' => $arResult['PATH_TO_DEAL_EDIT'],
			'PATH_TO_DEAL_CATEGORY' => $arResult['PATH_TO_DEAL_CALENDARCATEGORY'],
			'PATH_TO_DEAL_CATEGORY_LIST' => $arResult['PATH_TO_DEAL_CATEGORY_LIST'],
			'PATH_TO_DEAL_CATEGORY_EDIT' => $arResult['PATH_TO_DEAL_CATEGORY_EDIT'],
			'CATEGORY_ID' => $categoryID,
			'LAYOUT_WRAP_CLASSNAME' => 'pagetitle-container pagetitle-align-right-container pagetitle-flexible-space'
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

	$dealCount = COption::GetOptionInt('crm', 'deal_calendar_count_limit', 3000);
	$dealCount = $dealCount > 0 ? $dealCount : 3000;
	$APPLICATION->IncludeComponent(
		'bitrix:crm.deal.list',
		'calendar',
		array(
			'DEAL_COUNT' => $dealCount,
			'INTERNAL_SORT' => ['id' => 'asc'],
			'IS_RECURRING' => $arResult['IS_RECURRING'],
			'PATH_TO_DEAL_RECUR_SHOW' => $arResult['PATH_TO_DEAL_RECUR_SHOW'],
			'PATH_TO_DEAL_RECUR' => $arResult['PATH_TO_DEAL_RECUR'],
			'PATH_TO_DEAL_RECUR_EDIT' => $arResult['PATH_TO_DEAL_RECUR_EDIT'],
			'PATH_TO_DEAL_LIST' => $arResult['PATH_TO_DEAL_LIST'],
			'PATH_TO_DEAL_SHOW' => $arResult['PATH_TO_DEAL_SHOW'],
			'PATH_TO_DEAL_EDIT' => $arResult['PATH_TO_DEAL_EDIT'],
			'PATH_TO_DEAL_DETAILS' => $arResult['PATH_TO_DEAL_DETAILS'],
			'PATH_TO_DEAL_WIDGET' => $arResult['PATH_TO_DEAL_WIDGET'],
			'PATH_TO_DEAL_KANBAN' => $arResult['PATH_TO_DEAL_KANBAN'],
			'PATH_TO_DEAL_CALENDAR' => $arResult['PATH_TO_DEAL_CALENDAR'],
			'PATH_TO_DEAL_CATEGORY' => $arResult['PATH_TO_DEAL_CATEGORY'],
			'PATH_TO_DEAL_RECUR_CATEGORY' => $arResult['PATH_TO_DEAL_RECUR_CATEGORY'],
			'PATH_TO_DEAL_WIDGETCATEGORY' => $arResult['PATH_TO_DEAL_WIDGETCATEGORY'],
			'PATH_TO_DEAL_KANBANCATEGORY' => $arResult['PATH_TO_DEAL_KANBANCATEGORY'],
			'PATH_TO_DEAL_CALENDARCATEGORY' => $arResult['PATH_TO_DEAL_CALENDARCATEGORY'],
			'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
			'NAVIGATION_CONTEXT_ID' => $arResult['NAVIGATION_CONTEXT_ID'],
			'GRID_ID_SUFFIX' => $categoryID >= 0 ? "C_{$categoryID}" : '',
			'CATEGORY_ID' => $categoryID,
			'ADDITIONAL_FILTER' => array(
				'CALENDAR_DATE_FROM' => $calendarDateFrom,
				'CALENDAR_DATE_TO' => $calendarDateTo,
				'CALENDAR_FIELD' => $filterSelect
			),
			'CALENDAR_MODE' => 'Y',
			'CALENDAR_MODE_LIST' => $modeList,
			'ENABLE_BIZPROC' => 'N'
		),
		$component
	);

	Calendar::showViewModeCalendarSpotlight(CCrmOwnerType::DealName);

	$APPLICATION->IncludeComponent(
		'bitrix:crm.deal.checker',
		'',
		['CATEGORY_ID' => $categoryID],
		null,
		['HIDE_ICONS' => 'Y']
	);
}
?>
