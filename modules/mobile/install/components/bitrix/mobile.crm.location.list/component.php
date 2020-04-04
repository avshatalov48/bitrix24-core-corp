<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

global $APPLICATION;

if (!CCrmPerms::IsAccessEnabled())
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$mode = isset($arParams['MODE']) ? $arParams['MODE'] : '';
if($mode === '' && isset($_REQUEST['mode']))
{
	$mode = $_REQUEST['mode'];
}

$mode = strtoupper(trim($mode));
$arResult['MODE'] = $arParams['MODE'] = $mode;

$enableSearch = $arResult['ENABLE_SEARCH'] = isset($_REQUEST['SEARCH']) && strtoupper($_REQUEST['SEARCH']) === 'Y';
if($enableSearch)
{
	// decode encodeURIComponent params
	CUtil::JSPostUnescape();
}

$contextID = isset($arParams['CONTEXT_ID']) ? $arParams['CONTEXT_ID'] : '';
if($contextID === '' && isset($_REQUEST['context_id']))
{
	$contextID = $_REQUEST['context_id'];
}
$arResult['CONTEXT_ID'] = $arParams['CONTEXT_ID'] = $contextID;

$UID = isset($arParams['UID']) ? $arParams['UID'] : '';
if($UID === '')
{
	$UID = 'mobile_crm_location_list';
}
$arResult['UID'] = $arParams['UID'] = $UID;

$arResult['ITEMS'] = array();
if(!$enableSearch)
{
	$itemIDs = CCrmMobileHelper::GetRecentlyUsedLocations();
	if(!empty($itemIDs) && CModule::IncludeModule('sale'))
	{
		$order = array(
			'CITY_NAME_LANG' => 'ASC',
			'COUNTRY_NAME_LANG' => 'ASC',
			'SORT' => 'ASC'
		);
		$select = array('ID', 'CITY_ID', 'CITY_NAME', 'COUNTRY_NAME_LANG', 'REGION_NAME_LANG');
		$dbLocations = CSaleLocation::GetList($order,
			array('@ID' => $itemIDs, 'LID' => LANGUAGE_ID),
			false,
			false,
			$select
		);

		while ($data = $dbLocations->Fetch())
		{
			$dataID = $data['ID'];
			$arResult['ITEMS'][] = array(
				'ID' => $dataID,
				'NAME' => $data['CITY_NAME'],
				'REGION_NAME' => $data['REGION_NAME_LANG'],
				'COUNTRY_NAME' => $data['COUNTRY_NAME_LANG'],
				'TITLE' => CCrmInvoice::ResolveLocationName($dataID, $data)
			);
		}
	}
}
else
{
	$needle = isset($_REQUEST['NEEDLE']) ? $_REQUEST['NEEDLE'] : '';
	if($needle !== '' && CModule::IncludeModule('sale'))
	{
		$items = array();

		$order = array(
			'CITY_NAME_LANG' => 'ASC',
			'COUNTRY_NAME_LANG' => 'ASC',
			'SORT' => 'ASC'
		);
		$select = array('ID', 'CITY_ID', 'CITY_NAME', 'COUNTRY_NAME_LANG', 'REGION_NAME_LANG');
		$navParams = array('nTopCount' => 10);
		$locations = array();

		$dbLocations = CSaleLocation::GetList(
			$order,
			array('~CITY_NAME' => "{$needle}%", 'LID' => LANGUAGE_ID),
			false,
			$navParams,
			$select
		);
		while ($data = $dbLocations->Fetch())
		{
			$dataID = $data['ID'];
			$items[$dataID] = array(
				'ID' => $data['ID'],
				'NAME' => $data['CITY_NAME'],
				'REGION_NAME' => $data['REGION_NAME_LANG'],
				'COUNTRY_NAME' => $data['COUNTRY_NAME_LANG'],
				'TITLE' => CCrmInvoice::ResolveLocationName($dataID, $data)
			);
		}

		$dbLocations = CSaleLocation::GetList(
			$order,
			array('~REGION_NAME' => "{$needle}%", 'LID' => LANGUAGE_ID),
			false,
			$navParams,
			$select
		);
		while ($data = $dbLocations->Fetch())
		{
			$dataID = $data['ID'];
			if(isset($items[$dataID]))
			{
				continue;
			}

			$items[$data['ID']] = array(
				'ID' => $data['ID'],
				'NAME' => $data['CITY_NAME'],
				'REGION_NAME' => $data['REGION_NAME_LANG'],
				'COUNTRY_NAME' => $data['COUNTRY_NAME_LANG'],
				'TITLE' => CCrmInvoice::ResolveLocationName($dataID, $data)
			);
		}

		$arResult['ITEMS'] = array_values($items);
	}
}

$arResult['SEARCH_PAGE_URL'] = $APPLICATION->GetCurPageParam(
	'AJAX_CALL=Y&SEARCH=Y&FORMAT=json',
	array('AJAX_CALL', 'SEARCH', 'FORMAT')
);
$arResult['SERVICE_URL'] = ($arParams["SERVICE_URL"]
	? $arParams["SERVICE_URL"]
	: SITE_DIR . 'bitrix/components/bitrix/mobile.crm.location.list/ajax.php?siteID=' . SITE_ID . '&' . bitrix_sessid_get()
);


$format = isset($_REQUEST['FORMAT']) ? strtolower($_REQUEST['FORMAT']) : '';
// Only JSON format is supported
if($format !== '' && $format !== 'json')
{
	$format = '';
}
$this->IncludeComponentTemplate($format);
