<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}


$currentUserID = $arResult['USER_ID'] = intval(CCrmSecurityHelper::GetCurrentUserID());
$arParams['ENTITY_TYPE_ID'] = isset($arParams['ENTITY_TYPE_ID']) ? intval($arParams['ENTITY_TYPE_ID']) : 0;
$arParams['ENTITY_ID'] = isset($arParams['ENTITY_ID']) ? intval($arParams['ENTITY_ID']) : 0;
$arParams['TYPE_ID'] = isset($arParams['TYPE_ID']) ? strtoupper($arParams['TYPE_ID']) : '';

$arParams['CONTACT_SHOW_URL_TEMPLATE'] = isset($arParams['CONTACT_SHOW_URL_TEMPLATE']) ? $arParams['CONTACT_SHOW_URL_TEMPLATE'] : '';
$arParams['COMPANY_SHOW_URL_TEMPLATE'] = isset($arParams['COMPANY_SHOW_URL_TEMPLATE']) ? $arParams['COMPANY_SHOW_URL_TEMPLATE'] : '';
$arParams['NAME_TEMPLATE'] = isset($arParams['NAME_TEMPLATE']) ? str_replace(array('#NOBR#', '#/NOBR#'), array('', ''), $arParams['NAME_TEMPLATE']) : CSite::GetNameFormat(false);

$arParams['UID'] = isset($arParams['UID']) ? $arParams['UID'] : '';
if(!isset($arParams['UID']) || $arParams['UID'] === '')
{
	$arParams['UID'] = 'mobile_crm_comm_list';
}
$arResult['UID'] = $arParams['UID'];

if($arParams['ENTITY_TYPE_ID'] <= 0 && isset($_REQUEST['entity_type_id']))
{
	$arParams['ENTITY_TYPE_ID'] = intval($_REQUEST['entity_type_id']);
}

if($arParams['ENTITY_ID'] === 0 && isset($_REQUEST['entity_id']))
{
	$arParams['ENTITY_ID'] = intval($_REQUEST['entity_id']);
}

if($arParams['TYPE_ID'] === '' && isset($_REQUEST['type_id']))
{
	$arParams['TYPE_ID'] = strtoupper($_REQUEST['type_id']);
}
if($arParams['TYPE_ID'] === '')
{
	$arParams['TYPE_ID'] = 'PHONE';
}

$typeID = $arResult['TYPE_ID'] = $arParams['TYPE_ID'];

$entityTypeID = $arResult['ENTITY_TYPE_ID'] = $arParams['ENTITY_TYPE_ID'];
$entityTypeName = $arResult['ENTITY_TYPE_NAME'] = CCrmOwnerType::ResolveName($entityTypeID);
$entityID = $arParams['ENTITY_ID'];

if($entityTypeID === CCrmOwnerType::Undefined)
{
	ShowError(GetMessage('CRM_COMM_LIST_ENTITY_TYPE_NOT_DEFINED'));
	return;
}

if($entityTypeID !== CCrmOwnerType::Lead
	&& $entityTypeID !== CCrmOwnerType::Contact
	&& $entityTypeID !== CCrmOwnerType::Company)
{
	ShowError(GetMessage('CRM_COMM_LIST_INVALID_ENTITY_TYPE'));
	return;
}

if($entityID <= 0)
{
	ShowError(GetMessage('CRM_COMM_LIST_ENTITY_ID_NOT_DEFINED'));
	return;
}

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if ($userPerms->HavePerm($entityTypeName, BX_CRM_PERM_NONE, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['ENTITY_IMAGE_ID'] = 0;
$arResult['ENTITY_TITLE'] = '';
$arResult['ENTITY_LEGEND'] = '';

if($entityTypeID === CCrmOwnerType::Lead)
{
	$dbEntity = CCrmLead::GetListEx(array(), array('ID' => $entityID), false, false, array('NAME', 'LAST_NAME', 'SECOND_NAME', 'COMPANY_TITLE', 'POST'));
	$entity = $dbEntity ? $dbEntity->Fetch() : null;
	if($entity)
	{
		$arResult['ENTITY_IMAGE_URL'] = SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_lead_big.png?ver=1';
		$arResult['ENTITY_TITLE'] = CUser::FormatName(
			$arParams['NAME_TEMPLATE'],
			array(
				'LOGIN' => '',
				'NAME' => isset($entity['NAME']) ? $entity['NAME'] : '',
				'LAST_NAME' => isset($entity['LAST_NAME']) ? $entity['LAST_NAME'] : '',
				'SECOND_NAME' => isset($entity['SECOND_NAME']) ? $entity['SECOND_NAME'] : ''
			),
			false, false
		);

		$companyTitle = isset($entity['COMPANY_TITLE']) ? $entity['COMPANY_TITLE'] : '';
		$post = isset($entity['POST']) ? $entity['POST'] : '';
		if($companyTitle !== '' && $post !== '')
		{
			$arResult['ENTITY_LEGEND'] = "{$companyTitle}, {$post}";
		}
		elseif($companyTitle !== '')
		{
			$arResult['ENTITY_LEGEND'] = $companyTitle;
		}
		elseif($post !== '')
		{
			$arResult['ENTITY_LEGEND'] = $post;
		}
	}
}
elseif($entityTypeID === CCrmOwnerType::Contact)
{
	$dbEntity = CCrmContact::GetListEx(array(), array('ID' => $entityID), false, false, array('NAME', 'LAST_NAME', 'SECOND_NAME', 'PHOTO', 'COMPANY_TITLE', 'POST'));
	$entity = $dbEntity ? $dbEntity->Fetch() : null;
	if($entity)
	{
		if(isset($entity['PHOTO']))
		{
			$arResult['ENTITY_IMAGE_ID'] = intval($entity['PHOTO']);
		}
		else
		{
			$arResult['ENTITY_IMAGE_URL'] = SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_contact_big.png?ver=1';
		}

		$arResult['ENTITY_TITLE'] = CUser::FormatName(
			$arParams['NAME_TEMPLATE'],
			array(
				'LOGIN' => '',
				'NAME' => isset($entity['NAME']) ? $entity['NAME'] : '',
				'LAST_NAME' => isset($entity['LAST_NAME']) ? $entity['LAST_NAME'] : '',
				'SECOND_NAME' => isset($entity['SECOND_NAME']) ? $entity['SECOND_NAME'] : ''
			),
			false, false
		);

		$companyTitle = isset($entity['COMPANY_TITLE']) ? $entity['COMPANY_TITLE'] : '';
		$post = isset($entity['POST']) ? $entity['POST'] : '';
		if($companyTitle !== '' && $post !== '')
		{
			$arResult['ENTITY_LEGEND'] = "{$companyTitle}, {$post}";
		}
		elseif($companyTitle !== '')
		{
			$arResult['ENTITY_LEGEND'] = $companyTitle;
		}
		elseif($post !== '')
		{
			$arResult['ENTITY_LEGEND'] = $post;
		}
	}
}
elseif($entityTypeID === CCrmOwnerType::Company)
{
	$dbEntity = CCrmCompany::GetListEx(array(), array('ID' => $entityID), false, false, array('TITLE', 'LOGO'));
	$entity = $dbEntity ? $dbEntity->Fetch() : null;
	if($entity)
	{
		if(isset($entity['LOGO']))
		{
			$arResult['ENTITY_IMAGE_ID'] = intval($entity['LOGO']);
		}
		else
		{
			$arResult['ENTITY_IMAGE_URL'] = SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_company_big.png?ver=1';
		}

		$arResult['ENTITY_TITLE'] = isset($entity['TITLE']) ? $entity['TITLE'] : '';
	}
}

$dbRes = CCrmFieldMulti::GetList(
	array('ID' => 'asc'),
	array(
		'TYPE_ID' => $arParams['TYPE_ID'],
		'ENTITY_ID' => $entityTypeName,
		'ELEMENT_ID' => $entityID
	)
);
$arResult['ITEMS'] = array();
if($dbRes)
{
	while($item = $dbRes->Fetch())
	{
		$value = $item['VALUE'];
		$url = '';
		if($typeID === 'PHONE')
		{
			$url = CCrmMobileHelper::PrepareCalltoUrl($value);
		}
		elseif($typeID === 'EMAIL')
		{
			$url = CCrmMobileHelper::PrepareMailtoUrl($value);
		}

		$arResult['ITEMS'][] = array(
			'VALUE' => $value,
			'VALUE_TYPE' => $item['VALUE_TYPE'],
			'URL' => $url
		);
	}
}

$this->IncludeComponentTemplate();
