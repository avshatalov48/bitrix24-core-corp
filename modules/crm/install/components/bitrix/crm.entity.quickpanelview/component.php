<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

global $APPLICATION;
global $USER_FIELD_MANAGER;
//use Bitrix\Crm\Entity\QuickPanelView;

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$currentUser = CCrmSecurityHelper::GetCurrentUser();
if (!$currentUser || !$currentUser->IsAuthorized())
{
	ShowError(GetMessage('CRM_ENTITY_QPV_NOT_AUTHORIZED'));
	return;
}

$entityTypeName = isset($arParams['ENTITY_TYPE_NAME']) ? $arParams['ENTITY_TYPE_NAME'] : '';
if ($entityTypeName === '')
{
	ShowError(GetMessage('CRM_ENTITY_QPV_ENTITY_TYPE_NAME_NOT_DEFINED'));
	return;
}

use Bitrix\Crm\CompanyAddress;
use Bitrix\Crm\ContactAddress;
use Bitrix\Crm\Format\AddressFormatter;
use Bitrix\Crm\LeadAddress;
use Bitrix\Crm\Settings\CompanySettings;
use Bitrix\Crm\Settings\ContactSettings;
use Bitrix\Crm\EntityAddressType;

$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
$arResult['ENTITY_TYPE_ID'] = $entityTypeID;
$arResult['ENTITY_TYPE_NAME'] = $entityTypeName;

$permissionEntityType = isset($arParams['PERMISSION_ENTITY_TYPE']) ? $arParams['PERMISSION_ENTITY_TYPE'] : '';
if($permissionEntityType === '')
{
	$permissionEntityType = $entityTypeName;
}

$arResult['PERMISSION_ENTITY_TYPE'] = $permissionEntityType;

$entityID = isset($arParams['ENTITY_ID']) ? (int)$arParams['ENTITY_ID'] : 0;
if ($entityID <= 0)
{
	ShowError(GetMessage('CRM_ENTITY_QPV_ENTITY_ID_NOT_DEFINED'));
	return;
}

$arResult['ENTITY_ID'] = $entityID;

$currentUserPremissions = CCrmPerms::GetCurrentUserPermissions();
if(!CCrmAuthorizationHelper::CheckReadPermission($permissionEntityType, $entityID, $currentUserPremissions))
{
	ShowError(GetMessage('CRM_ENTITY_QPV_ACCESS_DENIED'));
	return;
}

$entityFields = isset($arParams['~ENTITY_FIELDS']) ? $arParams['~ENTITY_FIELDS'] : null;
if(!is_array($entityFields))
{
	ShowError(GetMessage('CRM_ENTITY_QPV_ENTITY_FIELDS_NOT_FOUND'));
	return;
}

$canEdit = $arResult['CAN_EDIT'] = CCrmAuthorizationHelper::CheckUpdatePermission($permissionEntityType, $entityID, $currentUserPremissions);
$userProfilePath = $arResult['PATH_TO_USER_PROFILE'] = $arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$nameTemplate = $arResult['NAME_TEMPLATE'] = $arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);
$enableInstantEdit = $arResult['ENABLE_INSTANT_EDIT'] = isset($arParams['ENABLE_INSTANT_EDIT']) ? $arParams['ENABLE_INSTANT_EDIT'] : false;
$arResult['INSTANT_EDITOR_ID'] = isset($arParams['INSTANT_EDITOR_ID']) ? $arParams['INSTANT_EDITOR_ID'] : '';
$arResult['SERVICE_URL'] = isset($arParams['SERVICE_URL']) ? $arParams['SERVICE_URL'] : '';
$arResult['FORM_ID'] = $arParams['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : mb_strtolower($entityTypeName).'_'.$entityID;
$arResult['GUID'] = isset($arParams['GUID']) ? $arParams['GUID'] : mb_strtolower($arResult['FORM_ID']).'_qpv';

//CONFIG -->
$config = CUserOptions::GetOption(
	'crm.entity.quickpanelview',
	$arResult['GUID'],
	null,
	$currentUser->GetID()
);

$enableDefaultConfig = !is_array($config);
if($enableDefaultConfig)
{
	$config = array('enabled' => 'N', 'expanded' => 'Y', 'fixed' => 'Y');
}
// <-- CONFIG

//$defaultCompanyLogoUrl = SITE_DIR.'bitrix/js/crm/images/crm-default-company.jpg';
$defaultCompanyLogoUrl = '';
$ufEntityID = '';
$entityData = array();
$entityContext = array(
	'ENTITY_TYPE_ID' => $arResult['ENTITY_TYPE_ID'],
	'ENTITY_TYPE_NAME' => $arResult['ENTITY_TYPE_NAME'],
	'ENTITY_ID' => $arResult['ENTITY_ID'],
	'SIP_MANAGER_CONFIG' => array()
);

if(!function_exists('__CrmQuickPanelViewPrepareResponsible'))
{
	function __CrmQuickPanelViewPrepareResponsible($entityFields, $userProfilePath, $nameTemplate, $enableEdit, $editorID, $serviveUrl, $key = '', $useTildeKey = true)
	{
		if($key === '')
		{
			$key = 'ASSIGNED_BY';
		}

		$map = array(
			'ID' => ($useTildeKey ? '~' : '').$key.'_ID',
			'FORMATTED_NAME' => ($useTildeKey ? '~' : '').$key.'_FORMATTED_NAME',
			'LOGIN' => ($useTildeKey ? '~' : '').$key.'_LOGIN',
			'NAME' => ($useTildeKey ? '~' : '').$key.'_NAME',
			'LAST_NAME' => ($useTildeKey ? '~' : '').$key.'_LAST_NAME',
			'SECOND_NAME' => ($useTildeKey ? '~' : '').$key.'_SECOND_NAME',
			'PERSONAL_PHOTO' => ($useTildeKey ? '~' : '').$key.'_PERSONAL_PHOTO',
			'WORK_POSITION' => ($useTildeKey ? '~' : '').$key.'_WORK_POSITION'
		);

		$userID = isset($entityFields[$map['ID']]) ? $entityFields[$map['ID']] : 0;
		$formattedName = isset($entityFields[$map['FORMATTED_NAME']]) ? $entityFields[$map['FORMATTED_NAME']] : '';
		if($formattedName === '')
		{
			$formattedName = CUser::FormatName(
				$nameTemplate,
				array(
					'LOGIN' => isset($entityFields[$map['LOGIN']]) ? $entityFields[$map['LOGIN']] : '',
					'NAME' => isset($entityFields[$map['NAME']]) ? $entityFields[$map['NAME']] : '',
					'LAST_NAME' => isset($entityFields[$map['LAST_NAME']]) ? $entityFields[$map['LAST_NAME']] : '',
					'SECOND_NAME' => isset($entityFields[$map['SECOND_NAME']]) ? $entityFields[$map['SECOND_NAME']] : ''
				),
				true, false
			);
		}

		$photoID = isset($entityFields[$map['PERSONAL_PHOTO']]) ? $entityFields[$map['PERSONAL_PHOTO']] : 0;
		$photoUrl = '';
		if($photoID > 0)
		{
			$file = new CFile();
			$fileInfo = $file->ResizeImageGet(
				$photoID,
				array('width' => 100, 'height'=> 100),
				BX_RESIZE_IMAGE_EXACT
			);
			if(is_array($fileInfo) && isset($fileInfo['src']))
			{
				$photoUrl = $fileInfo['src'];
			}
		}

		return array(
			'type' => 'responsible',
			'enableCaption' => false,
			'editable' => $enableEdit,
			'data' => array(
				'fieldID' => $useTildeKey? mb_substr($map['ID'], 1) : $map['ID'],
				'userID' => $userID,
				'name' => $formattedName,
				'photoID' => $photoID,
				'photoUrl' => $photoUrl,
				'position' => isset($entityFields[$map['WORK_POSITION']]) ? $entityFields[$map['WORK_POSITION']] : '',
				'profileUrlTemplate' => $userProfilePath,
				'profileUrl' => CComponentEngine::makePathFromTemplate($userProfilePath, array('user_id' => $userID)),
				'editorID' => $editorID,
				'serviceUrl' => $serviveUrl,
				'userInfoProviderID' => md5($serviveUrl)
			)
		);
	}
}
if(!function_exists('__CrmQuickPanelViewPrepareClientInfo'))
{
	function __CrmQuickPanelViewPrepareClientInfo($entityTypeName, &$entityContext, array $formFieldNames = null, array $params = array())
	{
		$isEntityReadPermitted = false;
		$prefix = isset($params['PREFIX']) ? $params['PREFIX'] : '';
		if($entityTypeName === CCrmOwnerType::CompanyName)
		{
			$entityInfo = $entityContext['COMPANY_INFO'];
			$isEntityReadPermitted = CCrmCompany::CheckReadPermission($entityInfo['ID']);

			if(!isset($entityContext['SIP_MANAGER_CONFIG'][CCrmOwnerType::CompanyName]))
			{
				$entityContext['SIP_MANAGER_CONFIG'][CCrmOwnerType::CompanyName] = array(
					'ENTITY_TYPE' => CCrmOwnerType::CompanyName,
					'SERVICE_URL' => '/bitrix/components/bitrix/crm.company.show/ajax.php?' . bitrix_sessid_get()
				);
			}

			$count = isset($entityInfo['COUNT']) ? $entityInfo['COUNT'] : 0;
			$selectedIndex = isset($entityInfo['SELECTED_INDEX']) ? $entityInfo['SELECTED_INDEX'] : 0;
			$isMultiple = $count > 1;

			$viewData = array(
				'ENTITY_TYPE_NAME' => CCrmOwnerType::CompanyName,
				'ENTITY_ID' => $entityInfo['ID'],
				'PREFIX' => $prefix
			);

			if(!$isEntityReadPermitted)
			{
				$viewData['NAME'] = GetMessage('CRM_ENTITY_QPV_HIDDEN_COMPANY');
			}
			else
			{
				$viewData = array_merge(
					$viewData,
					array(
						'NAME' => $entityInfo['TITLE'],
						'DESCRIPTION' => '',
						'SHOW_URL' => $entityInfo['SHOW_URL'],
						'IMAGE_URL' => $entityInfo['IMAGE_URL'],
					)
				);
			}

			if(!$isMultiple)
			{
				$fieldData = array('type' => 'client', 'entityTypeName' => CCrmOwnerType::CompanyName, 'enableCaption' => false);
			}
			else
			{
				$fieldData = array(
					'type' => 'multiple_client',
					'entityTypeName' => CCrmOwnerType::CompanyName,
					'enableCaption' => false,
					'owner' => array(
						'typeName' => $entityContext['ENTITY_TYPE_NAME'],
						'id' => $entityContext['ENTITY_ID'],
					),
					'service' => array(
						'url' => isset($entityInfo['SERVICE_URL']) ? $entityInfo['SERVICE_URL'] : '',
						'formId' => isset($params['FORM_ID']) ? $params['FORM_ID'] : ''
					)
				);
			}
		}
		else
		{
			$entityInfo = $entityContext['CONTACT_INFO'];
			$isEntityReadPermitted = CCrmContact::CheckReadPermission($entityInfo['ID']);

			if(!isset($entityContext['SIP_MANAGER_CONFIG'][CCrmOwnerType::ContactName]))
			{
				$entityContext['SIP_MANAGER_CONFIG'][CCrmOwnerType::ContactName] = array(
					'ENTITY_TYPE' => CCrmOwnerType::ContactName,
					'SERVICE_URL' => '/bitrix/components/bitrix/crm.contact.show/ajax.php?'.bitrix_sessid_get()
				);
			}

			$count = isset($entityInfo['COUNT']) ? $entityInfo['COUNT'] : 0;
			$selectedIndex = isset($entityInfo['SELECTED_INDEX']) ? $entityInfo['SELECTED_INDEX'] : 0;
			$isMultiple = $count > 1;

			$viewData = array(
				'ENTITY_TYPE_NAME' => CCrmOwnerType::ContactName,
				'ENTITY_ID' => $entityInfo['ID'],
				'PREFIX' => $prefix
			);

			if(!$isEntityReadPermitted)
			{
				$viewData['NAME'] = GetMessage('CRM_ENTITY_QPV_HIDDEN_CONTACT');
			}
			else
			{
				$viewData = array_merge(
					$viewData,
					array(
						'NAME' => $entityInfo['FORMATTED_NAME'],
						'DESCRIPTION' => $entityInfo['POST'],
						'SHOW_URL' => $entityInfo['SHOW_URL'],
						'IMAGE_URL' => $entityInfo['IMAGE_URL'],
					)
				);
			}

			if(!$isMultiple)
			{
				$fieldData = array('type' => 'client', 'entityTypeName' => CCrmOwnerType::ContactName, 'enableCaption' => false);
			}
			else
			{
				$fieldData = array(
					'type' => 'multiple_client',
					'entityTypeName' => CCrmOwnerType::ContactName,
					'enableCaption' => false,
					'owner' => array(
						'typeName' => $entityContext['ENTITY_TYPE_NAME'],
						'id' => $entityContext['ENTITY_ID'],
					),
					'service' => array(
						'url' => isset($entityInfo['SERVICE_URL']) ? $entityInfo['SERVICE_URL'] : '',
						'formId' => isset($params['FORM_ID']) ? $params['FORM_ID'] : ''
					)
				);
			}
		}

		$entityID = $entityInfo['ID'];
		if(!$isEntityReadPermitted)
		{
			$viewData['ENABLE_MULTIFIELDS'] = false;
		}
		elseif(isset($entityInfo['FM']))
		{
			if(isset($entityInfo['FM']['PHONE']))
			{
				$viewData['PHONE'] = __CrmQuickPanelViewPrepareMultiFields(
					$entityInfo['FM']['PHONE'],
					$entityTypeName,
					$entityID,
					'PHONE',
					$formFieldNames
				);
			}
			if(isset($entityInfo['FM']['EMAIL']))
			{
				$viewData['EMAIL'] = __CrmQuickPanelViewPrepareMultiFields(
					$entityInfo['FM']['EMAIL'],
					$entityTypeName,
					$entityID,
					'EMAIL',
					$formFieldNames
				);
			}
		}

		if(!$isMultiple)
		{
			$fieldData['data'] = $viewData;
		}
		else
		{
			$viewData['INDEX'] = $selectedIndex;
			$fieldData['data'] = array(
				'childCount' => $count,
				'currentChildIndex' => $selectedIndex,
				'children' => array(array('type' => 'client', 'data' => $viewData))
			);
		}

		return $fieldData;
	}
}
if(!function_exists('__CrmQuickPanelViewLoadMultiFields'))
{
	function __CrmQuickPanelViewLoadMultiFields($entityTypeName, $entityID)
	{
		$dbResult = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => $entityTypeName, 'ELEMENT_ID' => $entityID)
		);

		$result = array();
		while($arMultiFields = $dbResult->Fetch())
		{
			$result[$arMultiFields['TYPE_ID']][$arMultiFields['ID']] = array('VALUE' => $arMultiFields['VALUE'], 'VALUE_TYPE' => $arMultiFields['VALUE_TYPE']);
		}
		return $result;
	}
}
if(!function_exists('__CrmQuickPanelViewPrepareMultiFields'))
{
	function __CrmQuickPanelViewPrepareMultiFields(array $multiFields, $entityTypeName, $entityID, $typeID, array $formFieldNames = null)
	{
		if(empty($multiFields))
		{
			return null;
		}

		$arEntityTypeInfos = CCrmFieldMulti::GetEntityTypeInfos();
		$arEntityTypes = CCrmFieldMulti::GetEntityTypes();
		$sipConfig =  array(
			'STUB' => GetMessage('CRM_ENTITY_QPV_MULTI_FIELD_NOT_ASSIGNED'),
			'ENABLE_SIP' => true,
			'SIP_PARAMS' => array(
				'ENTITY_TYPE' => 'CRM_'.$entityTypeName,
				'ENTITY_ID' => $entityID)
		);

		$typeInfo = isset($arEntityTypeInfos[$typeID]) ? $arEntityTypeInfos[$typeID] : array();
		$caption = isset($typeInfo['NAME']) ? $typeInfo['NAME'] : $typeID;
		if(is_array($formFieldNames) && isset($formFieldNames[$typeID]))
		{
			$caption = $formFieldNames[$typeID];
		}

		$result = array(
			'type' => 'multiField',
			'caption' => $caption,
			'data' => array('type'=> $typeID, 'items'=> array())
		);
		foreach($multiFields as $multiField)
		{
			$value = isset($multiField['VALUE']) ? $multiField['VALUE'] : '';
			$valueType = isset($multiField['VALUE_TYPE']) ? $multiField['VALUE_TYPE'] : '';

			$entityType = $arEntityTypes[$typeID];
			$valueTypeInfo = isset($entityType[$valueType]) ? $entityType[$valueType] : null;

			$params = array('VALUE' => $value, 'VALUE_TYPE_ID' => $valueType, 'VALUE_TYPE' => $valueTypeInfo);
			$result['data']['items'][] = CCrmViewHelper::PrepareMultiFieldValueItemData($typeID, $params, $sipConfig);
		}

		return $result;
	}
}
if(!function_exists('__CrmQuickPanelViewPrepareContactInfo'))
{
	function __CrmQuickPanelViewPrepareContactInfo($entityFields, &$entityContext, $key = '', $useTildeKey = true, array $options = array())
	{
		if($key !== '')
		{
			$map = array(
				'ID' => ($useTildeKey ? '~' : '').$key.'_ID',
				'FORMATTED_NAME' => ($useTildeKey ? '~' : '').$key.'_FORMATTED_NAME',
				'POST' => ($useTildeKey ? '~' : '').$key.'_POST',
				'PHOTO' => ($useTildeKey ? '~' : '').$key.'_PHOTO'
			);
		}
		else
		{
			$map = array(
				'ID' => ($useTildeKey ? '~' : '').'ID',
				'FORMATTED_NAME' => ($useTildeKey ? '~' : '').'FORMATTED_NAME',
				'POST' => ($useTildeKey ? '~' : '').'POST',
				'PHOTO' => ($useTildeKey ? '~' : '').'PHOTO'
			);
		}

		$entityContext['CONTACT_INFO'] = array(
			'ID' => isset($entityFields[$map['ID']]) ? (int)$entityFields[$map['ID']] : 0,
			'FORMATTED_NAME' => ''
		);

		if($entityContext['CONTACT_INFO']['ID'] > 0 && isset($entityFields[$map['FORMATTED_NAME']]))
		{
			$entityContext['CONTACT_INFO']['FORMATTED_NAME'] = $entityFields[$map['FORMATTED_NAME']];
			$entityContext['CONTACT_INFO']['POST'] = isset($entityFields[$map['POST']]) ? $entityFields[$map['POST']] : '';

			$entityContext['CONTACT_INFO']['SHOW_URL'] = CCrmOwnerType::GetEntityShowPath(
				CCrmOwnerType::Contact,
				$entityContext['CONTACT_INFO']['ID'],
				false
			);

			if(isset($entityFields[$map['PHOTO']]))
			{
				$file = new CFile();
				$fileInfo = $file->ResizeImageGet(
					$entityFields[$map['PHOTO']],
					array('width' => 38, 'height' => 38),
					BX_RESIZE_IMAGE_EXACT
				);

				$entityContext['CONTACT_INFO']['IMAGE_URL'] = is_array($fileInfo) && isset($fileInfo['src']) ? $fileInfo['src'] : '';
			}
			else
			{
				$entityContext['CONTACT_INFO']['IMAGE_URL'] = '';
			}

			$count = isset($options['COUNT']) ? $options['COUNT'] : 0;
			if($count > 1)
			{
				$entityContext['CONTACT_INFO']['IS_MULTIPLE'] = true;
				$entityContext['CONTACT_INFO']['COUNT'] = $count;
				$entityContext['CONTACT_INFO']['SELECTED_INDEX'] = isset($options['SELECTED_INDEX'])
					? $options['SELECTED_INDEX'] : 0;
				$entityContext['CONTACT_INFO']['SERVICE_URL'] = isset($options['SERVICE_URL'])
					? $options['SERVICE_URL'] : '';
			}

			$entityContext['CONTACT_INFO']['FM'] = __CrmQuickPanelViewLoadMultiFields(CCrmOwnerType::ContactName, $entityContext['CONTACT_INFO']['ID']);
			$entityContext['CONTACT_INFO']['MULTI_FIELDS_OPTIONS'] = array(
				'ENABLE_SIP' => true,
				'SIP_PARAMS' => array(
					'ENTITY_TYPE' => 'CRM_'.CCrmOwnerType::ContactName,
					'ENTITY_ID' => $entityContext['CONTACT_INFO']['ID']
				)
			);
		}
	}
}
if(!function_exists('__CrmQuickPanelViewPrepareCompanyInfo'))
{
	function __CrmQuickPanelViewPrepareCompanyInfo($entityFields, &$entityContext, $key = '', $useTildeKey = true, array $options = array())
	{
		if($key !== '')
		{
			$map = array(
				'ID' => ($useTildeKey ? '~' : '').$key.'_ID',
				'TITLE' => ($useTildeKey ? '~' : '').$key.'_TITLE',
				'LOGO' => ($useTildeKey ? '~' : '').$key.'_LOGO'
			);
		}
		else
		{
			$map = array(
				'ID' => ($useTildeKey ? '~' : '').'ID',
				'TITLE' => ($useTildeKey ? '~' : '').'TITLE',
				'LOGO' => ($useTildeKey ? '~' : '').'LOGO',
			);
		}

		$entityContext['COMPANY_INFO'] = array(
			'ID' => isset($entityFields[$map['ID']]) ? (int)$entityFields[$map['ID']] : 0,
			'TITLE' => ''
		);
		if($entityContext['COMPANY_INFO']['ID'] > 0 && isset($entityFields[$map['TITLE']]))
		{
			$entityContext['COMPANY_INFO']['TITLE'] = $entityFields[$map['TITLE']];
			$entityContext['COMPANY_INFO']['SHOW_URL'] = CCrmOwnerType::GetEntityShowPath(
				CCrmOwnerType::Company,
				$entityContext['COMPANY_INFO']['ID'],
				false
			);

			if(isset($entityFields[$map['LOGO']]))
			{
				$file = new CFile();
				$fileInfo = $file->ResizeImageGet(
					$entityFields[$map['LOGO']],
					array('width' => 48, 'height' => 31),
					BX_RESIZE_IMAGE_PROPORTIONAL
				);

				$entityContext['COMPANY_INFO']['IMAGE_URL'] = is_array($fileInfo) && isset($fileInfo['src']) ? $fileInfo['src'] : '';
			}
			else
			{
				$entityContext['COMPANY_INFO']['IMAGE_URL'] = '';
			}

			$count = isset($options['COUNT']) ? $options['COUNT'] : 0;
			if($count > 0)
			{
				$entityContext['COMPANY_INFO']['IS_MULTIPLE'] = true;
				$entityContext['COMPANY_INFO']['COUNT'] = $count;
				$entityContext['COMPANY_INFO']['SELECTED_INDEX'] = isset($options['SELECTED_INDEX'])
					? $options['SELECTED_INDEX'] : 0;
				$entityContext['COMPANY_INFO']['SERVICE_URL'] = isset($options['SERVICE_URL'])
					? $options['SERVICE_URL'] : '';
			}

			$entityContext['COMPANY_INFO']['FM'] = __CrmQuickPanelViewLoadMultiFields(CCrmOwnerType::CompanyName, $entityContext['COMPANY_INFO']['ID']);
			$entityContext['COMPANY_INFO']['MULTI_FIELDS_OPTIONS'] = array(
				'ENABLE_SIP' => true,
				'SIP_PARAMS' => array(
					'ENTITY_TYPE' => 'CRM_'.CCrmOwnerType::CompanyName,
					'ENTITY_ID' => $entityContext['COMPANY_INFO']['ID']
				)
			);
		}
	}
}
if(!function_exists('__CrmQuickPanelViewPrepareStatusEnumeration'))
{
	function __CrmQuickPanelViewPrepareStatusEnumeration($statusTypeID, $statusID, $editable, &$entityContext)
	{
		$sourceItems = CCrmStatus::GetStatusList($statusTypeID);
		$items = array();
		$text = '';
		foreach($sourceItems as $k => $v)
		{
			if(!is_string($k))
			{
				$k = (string)$k;
			}
			$items[] = array('ID' => $k, 'VALUE' => $v);
			if($text === '' && $statusID !== '' && $statusID === $k)
			{
				$text = $v;
			}
		}

		return array(
			'type' => 'enumeration',
			'editable'=> $editable,
			'data' => array(
				'value' => $statusID,
				'text' => $text,
				'items' => $items
			)
		);
	}
}
if(!function_exists('__CrmQuickPanelViewPrepareCurrencyEnumeration'))
{
	function __CrmQuickPanelViewPrepareCurrencyEnumeration($currencyID, $editable, &$entityContext)
	{
		$list = CCrmCurrencyHelper::PrepareListItems();
		$items = array();
		foreach($list as $ID => $name)
		{
			$items[] = array('ID' => $ID, 'VALUE' => $name);
		}

		return array(
			'type' => 'enumeration',
			'editable'=> $editable,
			'data' => array(
				'value' => $currencyID,
				'text' => $currencyID !== '' && isset($list[$currencyID]) ? $list[$currencyID] : '',
				'items' => $items
			)
		);
	}
}
if(!function_exists('__CrmQuickPanelViewPrepareMoney'))
{
	function __CrmQuickPanelViewPrepareMoney($sum, $currencyID, $editable, $serviceUrl, &$entityContext)
	{
		$formattedSum = CCrmCurrency::MoneyToString($sum, $currencyID, '#');
		$formattedSumWithCurrency = CCrmCurrency::MoneyToString($sum, $currencyID, '');
		return array(
			'type' => 'money',
			'editable'=> $editable,
			'data' => array(
				'currencyId' => $currencyID,
				'value' => $sum,
				'text' => $formattedSum,
				'formatted_sum' => $formattedSum,
				'formatted_sum_with_currency' => $formattedSumWithCurrency,
				'serviceUrl' => $serviceUrl
			)
		);
	}
}

$file = new CFile();
$formFieldNames = CCrmViewHelper::getFormFieldNames($arResult['FORM_ID']);

if($entityTypeID === CCrmOwnerType::Contact)
{
	$enableOutmodedFields = ContactSettings::getCurrent()->areOutmodedRequisitesEnabled();
	$entityContext['SIP_MANAGER_CONFIG'][CCrmOwnerType::ContactName] = array(
		'ENTITY_TYPE' => CCrmOwnerType::ContactName,
		'SERVICE_URL' => '/bitrix/components/bitrix/crm.contact.show/ajax.php?'.bitrix_sessid_get()
	);

	$ufEntityID = CCrmContact::$sUFEntityID;
	$fieldKeys = array(
		'NAME' => true, 'SECOND_NAME' => true, 'LAST_NAME' => true,
		'BIRTHDATE' => true, 'TYPE_ID' => true,
		'SOURCE_ID' => true, 'SOURCE_DESCRIPTION' => true,
		'COMPANY_ID' => true, 'POST' => true,
		'OPENED' => true, 'EXPORT' => true,
		'ASSIGNED_BY_ID' => true, 'COMMENTS' => true
	);
	if($enableOutmodedFields)
	{
		$fieldKeys = array_merge($fieldKeys, array('ADDRESS' => true));
	}

	if($enableDefaultConfig)
	{
		$config['left'] = 'POST,TYPE_ID,SOURCE_ID';
		$config['center'] = 'PHONE,EMAIL,IM,COMPANY_ID';
		$config['right'] = 'ASSIGNED_BY_ID';
		$config['bottom'] = 'COMMENTS';
	}

	$selectedCompanyIndex = isset($entityFields['SELECTED_COMPANY_INDEX'])
		? $entityFields['SELECTED_COMPANY_INDEX'] : -1;
	$selectedCompany = isset($entityFields['SELECTED_COMPANY']) ? $entityFields['SELECTED_COMPANY'] : null;

	if($selectedCompanyIndex >= 0 && is_array($selectedCompany))
	{
		__CrmQuickPanelViewPrepareCompanyInfo(
			$selectedCompany,
			$entityContext,
			'',
			false,
			array(
				'SERVICE_URL' => '/bitrix/components/bitrix/crm.contact.show/ajax.php?'.bitrix_sessid_get(),
				'SELECTED_INDEX' => $selectedCompanyIndex,
				'COUNT' => isset($entityFields['COMPANY_COUNT']) ? $entityFields['COMPANY_COUNT'] : 0
			)
		);
	}
	else
	{
		__CrmQuickPanelViewPrepareCompanyInfo(
			$entityFields,
			$entityContext,
			'COMPANY',
			true,
			array(
				'SERVICE_URL' => '/bitrix/components/bitrix/crm.contact.show/ajax.php?'.bitrix_sessid_get(),
				'COUNT' => isset($entityFields['COMPANY_COUNT']) ? $entityFields['COMPANY_COUNT'] : 0
			)
		);
	}

	foreach($entityFields as $k => $v)
	{
		if(!isset($fieldKeys[$k]))
		{
			continue;
		}

		if($k === 'BIRTHDATE')
		{
			$entityData[$k] = array(
				'type' => 'date',
				'editable'=> $enableInstantEdit,
				'data' => array(
					'text' => ($v !== null && $v !== '') ? ConvertTimeStamp(MakeTimeStamp($v), 'SHORT', SITE_ID) : ''
				)
			);
		}
		elseif($k === 'TYPE_ID')
		{
			$entityData[$k] = __CrmQuickPanelViewPrepareStatusEnumeration('CONTACT_TYPE', $v, $enableInstantEdit, $entityContext);
		}
		elseif($k === 'SOURCE_ID')
		{
			$entityData[$k] = __CrmQuickPanelViewPrepareStatusEnumeration('SOURCE', $v, $enableInstantEdit, $entityContext);
		}
		elseif($k === 'COMPANY_ID')
		{
			$entityData['COMPANY_ID'] = __CrmQuickPanelViewPrepareClientInfo(
				CCrmOwnerType::CompanyName,
				$entityContext,
				$formFieldNames,
				array(
					'FORM_ID' => $arResult['FORM_ID'],
					'PREFIX' => "{$arResult['GUID']}_company"
				)
			);
		}
		elseif($k === 'OPENED' || $k === 'EXPORT')
		{
			$v = ($v !== null && $v !== '')? mb_strtoupper($v) : 'N';
			$entityData[$k] = array(
				'type' => 'boolean',
				'editable'=> $enableInstantEdit,
				'data' => array('baseType' => 'char', 'value' => $v)
			);
		}
		elseif($k === 'ASSIGNED_BY_ID')
		{
			$entityData['ASSIGNED_BY_ID'] = __CrmQuickPanelViewPrepareResponsible(
				$entityFields,
				$userProfilePath,
				$nameTemplate,
				$enableInstantEdit,
				$arResult['INSTANT_EDITOR_ID'],
				$arResult['SERVICE_URL']
			);
		}
		elseif($k === 'COMMENTS')
		{
			$entityData[$k] = array(
				'type' => 'html',
				'editable'=> $enableInstantEdit,
				'data' => array(
					'html' => $entityFields["~{$k}"],
					'serviceUrl' => $arResult['SERVICE_URL']
				)
			);
		}
		elseif($k === 'ADDRESS')
		{
			$addressLines = explode(
				"\n",
				str_replace(
					["\r\n", "\n", "\r"], "\n",
					AddressFormatter::getSingleInstance()->formatTextMultiline(
						ContactAddress::mapEntityFields($entityFields)
					)
				)
			);
			$entityData[$k] = array(
				'type' => 'address',
				'editable'=> false,
				'data' => array('lines' => (is_array($addressLines) ? $addressLines : []))
			);
			unset($addressLines);
		}
		elseif($k === 'SOURCE_DESCRIPTION')
		{
			$entityData[$k] = array(
				'type' => 'text',
				'editable'=> $enableInstantEdit,
				'data' => array('text' => $entityFields["~{$k}"], 'multiline' => true)
			);
		}
		else
		{
			$entityData[$k] = array(
				'type' => 'text',
				'editable'=> $enableInstantEdit,
				'data' => array('text' => $entityFields["~{$k}"])
			);
		}

		$caption = isset($formFieldNames[$k]) ? $formFieldNames[$k] : '';
		if($caption === '')
		{
			$caption = CCrmContact::GetFieldCaption($k);
		}
		$entityData[$k]['caption'] = $caption;
	}

	if(isset($entityFields['~PHOTO']))
	{
		$fileInfo = $file->ResizeImageGet(
			$entityFields['~PHOTO'],
			array('width' => 34, 'height' => 34),
			BX_RESIZE_IMAGE_EXACT
		);

		$arResult['HEAD_IMAGE_URL'] = isset($fileInfo['src']) ? $fileInfo['src'] : '';
	}
	else
	{
		$arResult['HEAD_IMAGE_URL'] = '';
	}
}
elseif($entityTypeID === CCrmOwnerType::Company)
{
	$enableOutmodedFields = CompanySettings::getCurrent()->areOutmodedRequisitesEnabled();
	$entityContext['SIP_MANAGER_CONFIG'][CCrmOwnerType::CompanyName] = array(
		'ENTITY_TYPE' => CCrmOwnerType::CompanyName,
		'SERVICE_URL' => '/bitrix/components/bitrix/crm.company.show/ajax.php?'.bitrix_sessid_get()
	);
	if($enableDefaultConfig)
	{
		$config['left'] = 'COMPANY_TYPE,INDUSTRY';
		$config['center'] = 'PHONE,EMAIL,WEB';
		$config['right'] = 'ASSIGNED_BY_ID';
		$config['bottom'] = 'COMMENTS';
	}

	$ufEntityID = CCrmCompany::$sUFEntityID;
	$fieldKeys = array(
		'TITLE' => true,
		'COMPANY_TYPE' => true, 'INDUSTRY' => true, 'EMPLOYEES' => true,
		'CURRENCY_ID' => true, 'REVENUE' => true,
		'BANKING_DETAILS' => true,
		'OPENED' => true, 'ASSIGNED_BY_ID' => true,
		'COMMENTS' => true
	);
	if($enableOutmodedFields)
	{
		$fieldKeys = array_merge(
			$fieldKeys,
			array('ADDRESS' => true, 'ADDRESS_LEGAL' => true, 'REG_ADDRESS' => true)
		);
	}

	foreach($entityFields as $k => $v)
	{
		if(!isset($fieldKeys[$k]))
		{
			continue;
		}

		if($k === 'COMPANY_TYPE' || $k === 'INDUSTRY' || $k === 'EMPLOYEES')
		{
			$entityData[$k] = __CrmQuickPanelViewPrepareStatusEnumeration($k, $v, $enableInstantEdit, $entityContext);
		}
		elseif($k === 'CURRENCY_ID')
		{
			$entityData[$k] = __CrmQuickPanelViewPrepareCurrencyEnumeration($v, $enableInstantEdit, $entityContext);
		}
		elseif($k === 'REVENUE')
		{
			$v = isset($entityFields['~REVENUE']) ? $entityFields['~REVENUE'] : 0.0;
			$currencyID = isset($entityFields['~CURRENCY_ID'])
				? $entityFields['~CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID();

			$entityData[$k] = array(
				'type' => 'money',
				'editable'=> $enableInstantEdit,
				'data' => array(
					'currencyId' => $currencyID,
					'value' => $v,
					'text' => CCrmCurrency::MoneyToString($v, $currencyID, '#'),
					'serviceUrl' => $arResult['SERVICE_URL']
				)
			);
		}
		elseif($k === 'OPENED' || $k === 'EXPORT')
		{
			$v = ($v !== null && $v !== '')? mb_strtoupper($v) : 'N';
			$entityData[$k] = array(
				'type' => 'boolean',
				'editable'=> $enableInstantEdit,
				'data' => array('baseType' => 'char', 'value' => $v)
			);
		}
		elseif($k === 'ASSIGNED_BY_ID')
		{
			$entityData['ASSIGNED_BY_ID'] = __CrmQuickPanelViewPrepareResponsible(
				$entityFields,
				$userProfilePath,
				$nameTemplate,
				$enableInstantEdit,
				$arResult['INSTANT_EDITOR_ID'],
				$arResult['SERVICE_URL']
			);
		}
		elseif($k === 'COMMENTS')
		{
			$entityData[$k] = array(
				'type' => 'html',
				'editable'=> $enableInstantEdit,
				'data' => array(
					'html' => $entityFields["~{$k}"],
					'serviceUrl' => $arResult['SERVICE_URL']
				)
			);
		}
		elseif($k === 'ADDRESS')
		{
			$addressLines = explode(
				"\n",
				str_replace(
					["\r\n", "\n", "\r"], "\n",
					AddressFormatter::getSingleInstance()->formatTextMultiline(
						CompanyAddress::mapEntityFields(
							$entityFields,
							['TYPE_ID' => EntityAddressType::Primary]
						)
					)
				)
			);
			$entityData[$k] = array(
				'type' => 'address',
				'editable'=> false,
				'data' => array('lines' => (is_array($addressLines) ? $addressLines : []))
			);
			unset($addressLines);
		}
		elseif($k === 'ADDRESS_LEGAL' || $k === 'REG_ADDRESS')
		{
			$addressLines = explode(
				"\n",
				str_replace(
					["\r\n", "\n", "\r"], "\n",
					AddressFormatter::getSingleInstance()->formatTextMultiline(
						CompanyAddress::mapEntityFields(
							$entityFields,
							['TYPE_ID' => EntityAddressType::Registered]
						)
					)
				)
			);
			$entityData[$k] = array(
				'type' => 'address',
				'editable'=> false,
				'data' => array('lines' => (is_array($addressLines) ? $addressLines : []))
			);
			unset($addressLines);
		}
		elseif($k === 'BANKING_DETAILS')
		{
			$entityData[$k] = array(
				'type' => 'text',
				'editable'=> $enableInstantEdit,
				'data' => array('text' => $entityFields["~{$k}"], 'multiline' => true)
			);
		}
		else
		{
			$entityData[$k] = array(
				'type' => 'text',
				'editable'=> $enableInstantEdit,
				'data' => array('text' => $entityFields["~{$k}"])
			);
		}

		$caption = isset($formFieldNames[$k]) ? $formFieldNames[$k] : '';
		if($caption === '')
		{
			$caption = CCrmCompany::GetFieldCaption($k);
		}
		$entityData[$k]['caption'] = $caption;
	}

	if(isset($entityFields['~LOGO']))
	{
		$fileInfo = $file->ResizeImageGet(
			$entityFields['~LOGO'],
			array('width' => 79, 'height' => 33),
			BX_RESIZE_IMAGE_PROPORTIONAL_ALT
		);

		$arResult['HEAD_IMAGE_URL'] = isset($fileInfo['src']) ? $fileInfo['src'] : $defaultCompanyLogoUrl;
	}
	else
	{
		$arResult['HEAD_IMAGE_URL'] = $defaultCompanyLogoUrl;
	}

	$arResult['HEAD_TITLE'] = isset($entityFields['TITLE']) ? $entityFields['TITLE'] : '';
	$arResult['HEAD_TITLE_FIELD_ID'] = 'TITLE';

}
elseif($entityTypeID === CCrmOwnerType::Deal)
{
	if($enableDefaultConfig)
	{
		$config['left'] = 'TYPE_ID,OPPORTUNITY,CURRENCY_ID,PROBABILITY,RECURRING_ACTIVE,RECURRING_COUNTER_REPEAT,RECURRING_NEXT_EXECUTION';
		$config['center'] = 'CLIENT';
		$config['right'] = 'BEGINDATE,CLOSEDATE,ASSIGNED_BY_ID';
		$config['bottom'] = 'COMMENTS';
	}

	$ufEntityID = CCrmDeal::$sUFEntityID;
	$fieldKeys = array(
		'TITLE' => true, 'STAGE_ID' => true,
		'CURRENCY_ID' => true, 'OPPORTUNITY' => true,
		'TYPE_ID' => true, 'PROBABILITY' => true,
		'BEGINDATE' => true, 'CLOSEDATE' => true,
		'CLOSED' => true, 'OPENED' => true,
		'CONTACT_ID' => true, 'COMPANY_ID' => true, 'QUOTE_ID' => true,
		'ASSIGNED_BY_ID' => true,
		'CLIENT' => true, 'COMMENTS' => true
	);

	$arResult['HEAD_PROGRESS_LEGEND_CONTAINER_ID'] = "{$arResult['GUID']}_header_stage_text";
	$arResult['HEAD_PROGRESS_LEGEND'] = isset($entityFields['~STAGE_TEXT']) ? $entityFields['~STAGE_TEXT'] : '';
	$stageText = htmlspecialcharsbx($arResult['HEAD_PROGRESS_LEGEND']);

	$arResult['HEAD_PROGRESS_BAR'] = CCrmViewHelper::RenderProgressControl(
		array(
			'ENTITY_TYPE_NAME' => CCrmOwnerType::DealName,
			'REGISTER_SETTINGS' => true,
			'CONTROL_ID' =>  "{$arResult['GUID']}_header_stage_id",
			'ENTITY_ID' => $entityFields['~ID'],
			'CURRENT_ID' => $entityFields['~STAGE_ID'],
			'CATEGORY_ID' => isset($entityFields['~CATEGORY_ID']) ? (int)$entityFields['~CATEGORY_ID'] : 0,
			'SERVICE_URL' => '/bitrix/components/bitrix/crm.deal.list/list.ajax.php',
			'READ_ONLY' => !$canEdit,
			'DISPLAY_LEGEND' => false,
			'LEGEND_CONTAINER_ID' => $arResult['HEAD_PROGRESS_LEGEND_CONTAINER_ID']
		)
	);

	$currencyID = isset($entityFields['~CURRENCY_ID'])
		? $entityFields['~CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID();
	$arResult['HEAD_FORMATTED_SUM'] = CCrmCurrency::MoneyToString(
			isset($entityFields['~OPPORTUNITY']) ? $entityFields['~OPPORTUNITY'] : 0.0, $currencyID
	);
	$arResult['HEAD_SUM_FIELD_ID'] = 'OPPORTUNITY';

	$selectedContactIndex = isset($entityFields['SELECTED_CONTACT_INDEX'])
		? $entityFields['SELECTED_CONTACT_INDEX'] : -1;
	$selectedContact = isset($entityFields['SELECTED_CONTACT']) ? $entityFields['SELECTED_CONTACT'] : null;

	if($selectedContactIndex >= 0 && is_array($selectedContact))
	{
		__CrmQuickPanelViewPrepareContactInfo(
			$selectedContact,
			$entityContext,
			'',
			false,
			array(
				'SERVICE_URL' => '/bitrix/components/bitrix/crm.deal.show/ajax.php?'.bitrix_sessid_get(),
				'SELECTED_INDEX' => isset($entityFields['SELECTED_CONTACT_INDEX']) ? $entityFields['SELECTED_CONTACT_INDEX'] : 0,
				'COUNT' => isset($entityFields['CONTACT_COUNT']) ? $entityFields['CONTACT_COUNT'] : 0
			)
		);
	}
	else
	{
		__CrmQuickPanelViewPrepareContactInfo(
			$entityFields,
			$entityContext,
			'CONTACT',
			true,
			array(
				'SERVICE_URL' => '/bitrix/components/bitrix/crm.deal.show/ajax.php?'.bitrix_sessid_get(),
				'COUNT' => isset($entityFields['CONTACT_COUNT']) ? $entityFields['CONTACT_COUNT'] : 0
			)
		);
	}

	__CrmQuickPanelViewPrepareCompanyInfo($entityFields, $entityContext, 'COMPANY');

	foreach($entityFields as $k => $v)
	{
		if(!isset($fieldKeys[$k]))
		{
			continue;
		}

		if($k === 'TYPE_ID')
		{
			$entityData[$k] = __CrmQuickPanelViewPrepareStatusEnumeration('DEAL_TYPE', $v, $enableInstantEdit, $entityContext);
		}
		elseif($k === 'STAGE_ID')
		{
			$legendContainerID = "{$arResult['GUID']}_stage_text";
			$progressHtml = CCrmViewHelper::RenderProgressControl(
				array(
					'ENTITY_TYPE_NAME' => CCrmOwnerType::DealName,
					'REGISTER_SETTINGS' => false,
					'CONTROL_ID' =>  "{$arResult['GUID']}_stage_id",
					'ENTITY_ID' => $entityFields['~ID'],
					'CURRENT_ID' => $entityFields['~STAGE_ID'],
					'CATEGORY_ID' => isset($entityFields['~CATEGORY_ID']) ? (int)$entityFields['~CATEGORY_ID'] : 0,
					'SERVICE_URL' => '/bitrix/components/bitrix/crm.deal.list/list.ajax.php',
					'READ_ONLY' => !$canEdit,
					'DISPLAY_LEGEND' => false,
					'LEGEND_CONTAINER_ID' => $legendContainerID
				)
			);
			$entityData[$k] = array(
				'type' => 'custom',
				'data' => array(
					'html' => "<div class=\"crm-detail-stage\"><div id=\"{$legendContainerID}\" class=\"crm-detail-stage-name\">{$stageText}</div>{$progressHtml}</div>"
				)
			);
		}
		elseif($k === 'CURRENCY_ID')
		{
			$entityData[$k] = __CrmQuickPanelViewPrepareCurrencyEnumeration($v, $enableInstantEdit, $entityContext);
		}
		elseif($k === 'OPPORTUNITY')
		{
			$entityData[$k] = __CrmQuickPanelViewPrepareMoney(
				isset($entityFields['~OPPORTUNITY']) ? $entityFields['~OPPORTUNITY'] : 0.0,
				isset($entityFields['~CURRENCY_ID']) ? $entityFields['~CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID(),
				$enableInstantEdit,
				$arResult['SERVICE_URL'],
				$entityContext
			);
		}
		elseif($k === 'BEGINDATE' || $k === 'CLOSEDATE')
		{
			$entityData[$k] = array(
				'type' => 'date',
				'editable'=> $enableInstantEdit,
				'data' => array(
					'text' => ($v !== null && $v !== '') ? ConvertTimeStamp(MakeTimeStamp($v), 'SHORT', SITE_ID) : ''
				)
			);
		}
		elseif($k === 'OPENED' || $k === 'CLOSED')
		{
			$v = ($v !== null && $v !== '')? mb_strtoupper($v) : 'N';
			$entityData[$k] = array(
				'type' => 'boolean',
				'editable'=> $enableInstantEdit,
				'data' => array('baseType' => 'char', 'value' => $v)
			);
		}
		elseif($k === 'COMPANY_ID' || $k === 'CONTACT_ID')
		{
			if(!isset($entityData['CLIENT']))
			{
				$entityData['CLIENT'] = array(
					'type' => 'composite_client',
					'primaryConfig' => __CrmQuickPanelViewPrepareClientInfo(
						CCrmOwnerType::CompanyName,
						$entityContext,
						$formFieldNames,
						array(
							'FORM_ID' => $arResult['FORM_ID'],
							'PREFIX' => "{$arResult['GUID']}_primary_client"
						)
					),
					'secondaryConfig' => __CrmQuickPanelViewPrepareClientInfo(
						CCrmOwnerType::ContactName,
						$entityContext,
						$formFieldNames,
						array(
							'FORM_ID' => $arResult['FORM_ID'],
							'PREFIX' => "{$arResult['GUID']}_secondary_client"
						)
					),
					'enableCaption' => false
				);
			}
			//region Outmoded data
			if($k === 'COMPANY_ID')
			{
				$entityData['COMPANY_ID'] = __CrmQuickPanelViewPrepareClientInfo(
					CCrmOwnerType::CompanyName,
					$entityContext,
					$formFieldNames,
					array(
						'FORM_ID' => $arResult['FORM_ID'],
						'PREFIX' => "{$arResult['GUID']}_company"
					)
				);
			}
			elseif($k === 'CONTACT_ID')
			{
				$entityData['CONTACT_ID'] = __CrmQuickPanelViewPrepareClientInfo(
					CCrmOwnerType::ContactName,
					$entityContext,
					$formFieldNames,
					array(
						'FORM_ID' => $arResult['FORM_ID'],
						'PREFIX' => "{$arResult['GUID']}_contact"
					)
				);
			}
			//endregion
		}
		elseif($k === 'QUOTE_ID')
		{
			$v = (int)$v;
			if($v <= 0)
			{
				$entityData[$k] = array('type' => 'text', 'data' => array('text' => GetMessage('CRM_ENTITY_QPV_QUOTE_NOT_ASSIGNED')));
			}
			else
			{
				$caption = isset($entityFields['QUOTE_TITLE']) ? $entityFields['QUOTE_TITLE'] : '';
				if($caption === '')
				{
					$caption = CCrmOwnerType::GetCaption(CCrmOwnerType::Quote, $v);
				}

				$showUrl = isset($entityFields['QUOTE_SHOW_URL']) ? $entityFields['QUOTE_SHOW_URL'] : '';
				if($showUrl === '')
				{
					$showUrl = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Quote, $v, true);
				}

				if($showUrl === '')
				{
					$entityData[$k] = array(
						'type' => 'text',
						'data' => array('text' => $caption)
					);
				}
				else
				{
					$entityData[$k] = array(
						'type' => 'link',
						'data' => array('text' => $caption, 'url' => $showUrl)
					);
				}
			}
		}
		elseif($k === 'ASSIGNED_BY_ID')
		{
			$entityData['ASSIGNED_BY_ID'] = __CrmQuickPanelViewPrepareResponsible(
				$entityFields,
				$userProfilePath,
				$nameTemplate,
				$enableInstantEdit,
				$arResult['INSTANT_EDITOR_ID'],
				$arResult['SERVICE_URL']
			);
		}
		elseif($k === 'PROBABILITY')
		{
			$entityData[$k] = array(
				'type' => 'text',
				'editable'=> $enableInstantEdit,
				'data' => array(
					'baseType' => 'int',
					'text' => $entityFields["~{$k}"]
				)
			);
		}
		elseif($k === 'COMMENTS')
		{
			$entityData[$k] = array(
				'type' => 'html',
				'editable'=> $enableInstantEdit,
				'data' => array(
					'html' => $entityFields["~{$k}"],
					'serviceUrl' => $arResult['SERVICE_URL']
				)
			);
		}
		else
		{
			$entityData[$k] = array(
				'type' => 'text',
				'editable'=> $enableInstantEdit,
				'data' => array('text' => $entityFields["~{$k}"])
			);
		}

		$caption = isset($formFieldNames[$k]) ? $formFieldNames[$k] : '';
		if($caption === '')
		{
			$caption = CCrmDeal::GetFieldCaption($k);
		}
		$entityData[$k]['caption'] = $caption;
	}

	$arResult['HEAD_TITLE'] = isset($entityFields['TITLE']) ? $entityFields['TITLE'] : '';
	$arResult['HEAD_TITLE_FIELD_ID'] = 'TITLE';
}
elseif($entityTypeID === CCrmOwnerType::Lead)
{
	$entityContext['SIP_MANAGER_CONFIG'][CCrmOwnerType::LeadName] = array(
		'ENTITY_TYPE' => CCrmOwnerType::LeadName,
		'SERVICE_URL' => '/bitrix/components/bitrix/crm.lead.show/ajax.php?'.bitrix_sessid_get()
	);

	$ufEntityID = CCrmLead::$sUFEntityID;
	$fieldKeys = array(
		'TITLE'=> true, 'COMPANY_TITLE' => true,
		'NAME' => true, 'SECOND_NAME' => true, 'LAST_NAME' => true,
		'STATUS_ID'=> true, 'STATUS_DESCRIPTION'=> true,
		'SOURCE_ID'=> true, 'SOURCE_DESCRIPTION'=> true,
		'CURRENCY_ID' => true, 'OPPORTUNITY' => true,
		'POST' => true, 'ADDRESS' => true,
		'BIRTHDATE' => true,
		'OPENED' => true,
		'ASSIGNED_BY_ID' => true,
		'COMMENTS' => true

	);

	if($enableDefaultConfig)
	{
		$config['left'] = 'SOURCE_ID,SOURCE_DESCRIPTION';
		$config['center'] = 'PHONE,EMAIL,IM';
		$config['right'] = 'ASSIGNED_BY_ID';
		$config['bottom'] = 'COMMENTS';
	}

	$arResult['HEAD_PROGRESS_LEGEND_CONTAINER_ID'] = "{$arResult['GUID']}_header_status_text";
	$arResult['HEAD_PROGRESS_LEGEND'] = isset($entityFields['~STATUS_TEXT']) ? $entityFields['~STATUS_TEXT'] : '';
	$statusText = htmlspecialcharsbx($arResult['HEAD_PROGRESS_LEGEND']);
	$arResult['HEAD_PROGRESS_BAR'] = CCrmViewHelper::RenderProgressControl(
		array(
			'ENTITY_TYPE_NAME' => CCrmOwnerType::LeadName,
			'REGISTER_SETTINGS' => true,
			'CONTROL_ID' =>  $arResult['GUID'],
			'ENTITY_ID' => $entityFields['~ID'],
			'CURRENT_ID' => $entityFields['~STATUS_ID'],
			'CONVERSION_SCHEME' => isset($arParams['CONVERSION_SCHEME']) ? $arParams['CONVERSION_SCHEME'] : null,
			'CONVERSION_TYPE_ID' => isset($arParams['CONVERSION_TYPE_ID']) ? (int)$arParams['CONVERSION_TYPE_ID'] : 0,
			'CAN_CONVERT' => !isset($arParams['CAN_CONVERT']) || $arParams['CAN_CONVERT'],
			'SERVICE_URL' => '/bitrix/components/bitrix/crm.lead.list/list.ajax.php',
			'READ_ONLY' => !$canEdit,
			'DISPLAY_LEGEND' => false,
			'LEGEND_CONTAINER_ID' => $arResult['HEAD_PROGRESS_LEGEND_CONTAINER_ID']
		)
	);

	$currencyID = isset($entityFields['~CURRENCY_ID'])
		? $entityFields['~CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID();
	$arResult['HEAD_FORMATTED_SUM'] = CCrmCurrency::MoneyToString(
			isset($entityFields['~OPPORTUNITY']) ? $entityFields['~OPPORTUNITY'] : 0.0, $currencyID
	);
	$arResult['HEAD_SUM_FIELD_ID'] = 'OPPORTUNITY';

	foreach($entityFields as $k => $v)
	{
		if(!isset($fieldKeys[$k]))
		{
			continue;
		}

		if($k === 'SOURCE_ID')
		{
			$entityData[$k] = __CrmQuickPanelViewPrepareStatusEnumeration('SOURCE', $v, $enableInstantEdit, $entityContext);
		}
		elseif($k === 'STATUS_ID')
		{
			$legendContainerID = "{$arResult['GUID']}_status_text";
			$progressHtml = CCrmViewHelper::RenderProgressControl(
				array(
					'ENTITY_TYPE_NAME' => CCrmOwnerType::LeadName,
					'REGISTER_SETTINGS' => false,
					'CONTROL_ID' =>  "{$arResult['GUID']}_status_id",
					'ENTITY_ID' => $entityFields['~ID'],
					'CURRENT_ID' => $entityFields['~STATUS_ID'],
					'CONVERSION_SCHEME' => isset($arParams['CONVERSION_SCHEME']) ? $arParams['CONVERSION_SCHEME'] : null,
					'CONVERSION_TYPE_ID' => \Bitrix\Crm\Conversion\LeadConversionType::resolveByEntityFields($entityFields),
					'SERVICE_URL' => '/bitrix/components/bitrix/crm.lead.list/list.ajax.php',
					'READ_ONLY' => !$canEdit,
					'DISPLAY_LEGEND' => false,
					'LEGEND_CONTAINER_ID' => $legendContainerID
				)
			);
			$entityData[$k] = array(
				'type' => 'custom',
				'data' => array(
					'html' => "<div class=\"crm-detail-stage\"><div id=\"{$legendContainerID}\" class=\"crm-detail-stage-name\">{$statusText}</div>{$progressHtml}</div>"
				)
			);
		}
		elseif($k === 'CURRENCY_ID')
		{
			$entityData[$k] = __CrmQuickPanelViewPrepareCurrencyEnumeration($v, $enableInstantEdit, $entityContext);
		}
		elseif($k === 'OPPORTUNITY')
		{
			$entityData[$k] = __CrmQuickPanelViewPrepareMoney(
				isset($entityFields['~OPPORTUNITY']) ? $entityFields['~OPPORTUNITY'] : 0.0,
				isset($entityFields['~CURRENCY_ID']) ? $entityFields['~CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID(),
				$enableInstantEdit,
				$arResult['SERVICE_URL'],
				$entityContext
			);
		}
		elseif($k === 'BIRTHDATE')
		{
			$entityData[$k] = array(
				'type' => 'date',
				'editable'=> $enableInstantEdit,
				'data' => array(
					'text' => ($v !== null && $v !== '') ? ConvertTimeStamp(MakeTimeStamp($v), 'SHORT', SITE_ID) : ''
				)
			);
		}
		elseif($k === 'OPENED')
		{
			$v = ($v !== null && $v !== '')? mb_strtoupper($v) : 'N';
			$entityData[$k] = array(
				'type' => 'boolean',
				'editable'=> $enableInstantEdit,
				'data' => array('baseType' => 'char', 'value' => $v)
			);
		}
		elseif($k === 'ASSIGNED_BY_ID')
		{
			$entityData['ASSIGNED_BY_ID'] = __CrmQuickPanelViewPrepareResponsible(
				$entityFields,
				$userProfilePath,
				$nameTemplate,
				$enableInstantEdit,
				$arResult['INSTANT_EDITOR_ID'],
				$arResult['SERVICE_URL']
			);
		}
		elseif($k === 'COMMENTS')
		{
			$entityData[$k] = array(
				'type' => 'html',
				'editable'=> $enableInstantEdit,
				'data' => array(
					'html' => $entityFields["~{$k}"],
					'serviceUrl' => $arResult['SERVICE_URL']
				)
			);
		}
		elseif($k === 'ADDRESS')
		{
			$addressLines = explode(
				"\n",
				str_replace(
					["\r\n", "\n", "\r"], "\n",
					AddressFormatter::getSingleInstance()->formatTextMultiline(
						LeadAddress::mapEntityFields($entityFields)
					)
				)
			);
			$entityData[$k] = array(
				'type' => 'address',
				'editable'=> false,
				'data' => array('lines' => (is_array($addressLines) ? $addressLines : []))
			);
			unset($addressLines);
		}
		elseif($k === 'STATUS_DESCRIPTION' || $k === 'SOURCE_DESCRIPTION')
		{
			$entityData[$k] = array(
				'type' => 'text',
				'editable'=> $enableInstantEdit,
				'data' => array('text' => $entityFields["~{$k}"], 'multiline' => true)
			);
		}
		else
		{
			$entityData[$k] = array(
				'type' => 'text',
				'editable'=> $enableInstantEdit,
				'data' => array('text' => $entityFields["~{$k}"])
			);
		}

		$caption = isset($formFieldNames[$k]) ? $formFieldNames[$k] : '';
		if($caption === '')
		{
			$caption = CCrmLead::GetFieldCaption($k);
		}
		$entityData[$k]['caption'] = $caption;
	}

	$arResult['HEAD_TITLE'] = isset($entityFields['TITLE']) ? $entityFields['TITLE'] : '';
	$arResult['HEAD_TITLE_FIELD_ID'] = 'TITLE';
}
elseif($entityTypeID === CCrmOwnerType::Quote)
{
	$entityContext['SIP_MANAGER_CONFIG'][CCrmOwnerType::QuoteName] = array(
		'ENTITY_TYPE' => CCrmOwnerType::QuoteName,
		'SERVICE_URL' => '/bitrix/components/bitrix/crm.quote.show/ajax.php?'.bitrix_sessid_get()
	);

	if($enableDefaultConfig)
	{
		$config['left'] = 'CLOSEDATE,LEAD_ID,DEAL_ID';
		$config['center'] = 'CLIENT';
		$config['right'] = 'ASSIGNED_BY_ID';
		$config['bottom'] = 'COMMENTS';
	}

	$ufEntityID = CCrmQuote::$sUFEntityID;
	$fieldKeys = array(
		'QUOTE_NUMBER' => true, 'TITLE' => true,
		'STATUS_ID' => true,
		'CURRENCY_ID' => true, 'OPPORTUNITY' => true,
		'CONTACT_ID' => true, 'COMPANY_ID' => true, 'LEAD_ID' => true, 'DEAL_ID' => true,
		'CLIENT_PHONE' => true, 'CLIENT_EMAIL' => true,
		'BEGINDATE' => true, 'CLOSEDATE' => true,
		'CLOSED' => true, 'OPENED' => true,
		'ASSIGNED_BY_ID' => true,
		'CLIENT' => true, 'COMMENTS' => true,
		'LOCATION_ID' => true
	);

	$arResult['HEAD_PROGRESS_LEGEND_CONTAINER_ID'] = "{$arResult['GUID']}_header_status_text";
	$arResult['HEAD_PROGRESS_LEGEND'] = isset($entityFields['~STATUS_TEXT']) ? $entityFields['~STATUS_TEXT'] : '';
	$statusText = htmlspecialcharsbx($arResult['HEAD_PROGRESS_LEGEND']);
	$progressHtml = $arResult['HEAD_PROGRESS_BAR'] = CCrmViewHelper::RenderProgressControl(
		array(
			'ENTITY_TYPE_NAME' => CCrmOwnerType::QuoteName,
			'REGISTER_SETTINGS' => true,
			'CONTROL_ID' =>  $arResult['GUID'],
			'ENTITY_ID' => $entityFields['~ID'],
			'CURRENT_ID' => $entityFields['~STATUS_ID'],
			'SERVICE_URL' => '/bitrix/components/bitrix/crm.quote.list/list.ajax.php',
			'READ_ONLY' => !$canEdit,
			'DISPLAY_LEGEND' => false,
			'LEGEND_CONTAINER_ID' => $arResult['HEAD_PROGRESS_LEGEND_CONTAINER_ID']
		)
	);

	$currencyID = isset($entityFields['~CURRENCY_ID'])
		? $entityFields['~CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID();
	$arResult['HEAD_FORMATTED_SUM'] = CCrmCurrency::MoneyToString(
			isset($entityFields['~OPPORTUNITY']) ? $entityFields['~OPPORTUNITY'] : 0.0, $currencyID
	);
	$arResult['HEAD_SUM_FIELD_ID'] = 'OPPORTUNITY';

	$selectedContactIndex = isset($entityFields['SELECTED_CONTACT_INDEX'])
		? $entityFields['SELECTED_CONTACT_INDEX'] : -1;
	$selectedContact = isset($entityFields['SELECTED_CONTACT']) ? $entityFields['SELECTED_CONTACT'] : null;

	if($selectedContactIndex >= 0 && is_array($selectedContact))
	{
		__CrmQuickPanelViewPrepareContactInfo(
			$selectedContact,
			$entityContext,
			'',
			false,
			array(
				'SERVICE_URL' => '/bitrix/components/bitrix/crm.quote.show/ajax.php?'.bitrix_sessid_get(),
				'SELECTED_INDEX' => isset($entityFields['SELECTED_CONTACT_INDEX']) ? $entityFields['SELECTED_CONTACT_INDEX'] : 0,
				'COUNT' => isset($entityFields['CONTACT_COUNT']) ? $entityFields['CONTACT_COUNT'] : 0
			)
		);
	}
	else
	{
		__CrmQuickPanelViewPrepareContactInfo(
			$entityFields,
			$entityContext,
			'CONTACT',
			true,
			array(
				'SERVICE_URL' => '/bitrix/components/bitrix/crm.quote.show/ajax.php?'.bitrix_sessid_get(),
				'COUNT' => isset($entityFields['CONTACT_COUNT']) ? $entityFields['CONTACT_COUNT'] : 0
			)
		);
	}
	__CrmQuickPanelViewPrepareCompanyInfo($entityFields, $entityContext, 'COMPANY');

	foreach($entityFields as $k => $v)
		{
			if(!isset($fieldKeys[$k]))
			{
				continue;
			}

			if($k === 'STATUS_ID')
			{
				$entityData[$k] = array(
					'type' => 'custom',
					'data' => array(
						'html' => "<div class=\"crm-detail-stage\"><div class=\"crm-detail-stage-name\">{$stageText}</div>{$progressHtml}</div>"
					)
				);
			}
			elseif($k === 'CURRENCY_ID')
			{
				$entityData[$k] = __CrmQuickPanelViewPrepareCurrencyEnumeration($v, $enableInstantEdit, $entityContext);
			}
			elseif($k === 'OPPORTUNITY')
			{
				$entityData[$k] = __CrmQuickPanelViewPrepareMoney(
					isset($entityFields['~OPPORTUNITY']) ? $entityFields['~OPPORTUNITY'] : 0.0,
					isset($entityFields['~CURRENCY_ID']) ? $entityFields['~CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID(),
					$enableInstantEdit,
					$arResult['SERVICE_URL'],
					$entityContext
				);
			}
			elseif($k === 'BEGINDATE' || $k === 'CLOSEDATE')
			{
				$entityData[$k] = array(
					'type' => 'date',
					'editable'=> $enableInstantEdit,
					'data' => array(
						'text' => ($v !== null && $v !== '') ? ConvertTimeStamp(MakeTimeStamp($v), 'SHORT', SITE_ID) : ''
					)
				);
			}
			elseif($k === 'OPENED' || $k === 'CLOSED')
			{
				$v = ($v !== null && $v !== '')? mb_strtoupper($v) : 'N';
				$entityData[$k] = array(
					'type' => 'boolean',
					'editable'=> $enableInstantEdit,
					'data' => array('baseType' => 'char', 'value' => $v)
				);
			}
			elseif($k === 'LEAD_ID')
			{
				$v = (int)$v;
				if($v > 0)
				{
					$entityData[$k] = array(
						'type' => 'link',
						'data' => array(
							'text' => CCrmOwnerType::GetCaption(CCrmOwnerType::Lead, $v),
							'url' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Lead, $v, true)
						)
					);
				}
				else
				{
					$entityData[$k] = array('type' => 'text', 'data' => array('text' => ''));
				}
			}
			elseif($k === 'DEAL_ID')
			{
				$v = (int)$v;
				if($v > 0)
				{
					$entityData[$k] = array(
						'type' => 'link',
						'data' => array(
							'text' => CCrmOwnerType::GetCaption(CCrmOwnerType::Deal, $v),
							'url' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Deal, $v, true)
						)
					);
				}
				else
				{
					$entityData[$k] = array('type' => 'text', 'data' => array('text' => ''));
				}
			}
			elseif($k === 'COMPANY_ID' || $k === 'CONTACT_ID')
			{
				if(!isset($entityData['CLIENT']))
				{
					$entityData['CLIENT'] = array(
						'type' => 'composite_client',
						'primaryConfig' => __CrmQuickPanelViewPrepareClientInfo(
							CCrmOwnerType::CompanyName,
							$entityContext,
							$formFieldNames,
							array(
								'FORM_ID' => $arResult['FORM_ID'],
								'PREFIX' => "{$arResult['GUID']}_primary_client"
							)
						),
						'secondaryConfig' => __CrmQuickPanelViewPrepareClientInfo(
							CCrmOwnerType::ContactName,
							$entityContext,
							$formFieldNames,
							array(
								'FORM_ID' => $arResult['FORM_ID'],
								'PREFIX' => "{$arResult['GUID']}_secondary_client"
							)
						),
						'enableCaption' => false
					);
				}
				//region Outmoded data
				if($k === 'COMPANY_ID')
				{
					$entityData['COMPANY_ID'] = __CrmQuickPanelViewPrepareClientInfo(
						CCrmOwnerType::CompanyName,
						$entityContext,
						$formFieldNames,
						array(
							'FORM_ID' => $arResult['FORM_ID'],
							'PREFIX' => "{$arResult['GUID']}_company"
						)
					);
				}
				elseif($k === 'CONTACT_ID')
				{
					$entityData['CONTACT_ID'] = __CrmQuickPanelViewPrepareClientInfo(
						CCrmOwnerType::ContactName,
						$entityContext,
						$formFieldNames,
						array(
							'FORM_ID' => $arResult['FORM_ID'],
							'PREFIX' => "{$arResult['GUID']}_contact"
						)
					);
				}
				//endregion
			}
			elseif($k === 'ASSIGNED_BY_ID')
			{
				$entityData['ASSIGNED_BY_ID'] = __CrmQuickPanelViewPrepareResponsible(
					$entityFields,
					$userProfilePath,
					$nameTemplate,
					$enableInstantEdit,
					$arResult['INSTANT_EDITOR_ID'],
					$arResult['SERVICE_URL']
				);
			}
			elseif($k === 'CLIENT_PHONE')
			{
				$params = array('VALUE' => $v, 'VALUE_TYPE_ID' => 'WORK');
				$entityData['CLIENT_PHONE'] = array(
					'type' => 'text',
					'data' => array(
						'html' => CCrmViewHelper::PrepareMultiFieldHtml(
							'PHONE',
							$params,
							array(
								'ENABLE_SIP' => true,
								'SIP_PARAMS' => array(
									'ENTITY_TYPE' => 'CRM_'.$entityTypeName,
									'ENTITY_ID' => $entityID)
							)
						)
					)
				);
			}
			elseif($k === 'CLIENT_EMAIL')
			{
				$params = array('VALUE' => $v, 'VALUE_TYPE_ID' => 'WORK');
				$entityData['CLIENT_EMAIL'] = array(
					'type' => 'text',
					'data' => array(
						'html' => CCrmViewHelper::PrepareMultiFieldHtml('EMAIL', $params)
					)
				);
			}
			elseif($k === 'COMMENTS')
			{
				$entityData[$k] = array(
					'type' => 'html',
					'editable'=> $enableInstantEdit,
					'data' => array(
						'html' => $entityFields["~{$k}"],
						'serviceUrl' => $arResult['SERVICE_URL']
					)
				);
			}
			elseif($k === 'LOCATION_ID')
			{
				$k = 'LOCATION_ID';
				$entityData[$k] = array(
					'type' => 'text',
					'data' => [
						'text' => $v > 0
							? CCrmLocations::getLocationStringByCode($v)
							: GetMessage('CRM_ENTITY_QPV_LOCATION_NOT_ASSIGNED')
					]
				);
			}
			else
			{
				$entityData[$k] = array(
					'type' => 'text',
					'editable'=> $enableInstantEdit,
					'data' => array('text' => $entityFields["~{$k}"])
				);
			}

			$caption = isset($formFieldNames[$k]) ? $formFieldNames[$k] : '';
			if($caption === '')
			{
				$caption = CCrmQuote::GetFieldCaption($k);
			}
			$entityData[$k]['caption'] = $caption;
		}

	$arResult['HEAD_TITLE'] = isset($entityFields['TITLE']) ? $entityFields['TITLE'] : '';
	$arResult['HEAD_TITLE_FIELD_ID'] = 'TITLE';
}
elseif($entityTypeID === CCrmOwnerType::Invoice)
{
	if($enableDefaultConfig)
	{
		$config['left'] = 'DATE_BILL,DATE_PAY_BEFORE,PAY_VOUCHER_DATE,UF_DEAL_ID,UF_QUOTE_ID,RECURRING_ACTIVE,RECURRING_COUNTER_REPEAT,RECURRING_NEXT_EXECUTION';
		$config['center'] = 'CLIENT';
		$config['right'] = 'RESPONSIBLE_ID';
		$config['bottom'] = 'COMMENTS';
	}

	$ufEntityID = CCrmInvoice::$sUFEntityID;
	$fieldKeys = array(
		'ACCOUNT_NUMBER' => true, 'ORDER_TOPIC' => true,
		'STATUS_ID' => true,
		'PAY_VOUCHER_DATE' => true, 'PAY_VOUCHER_NUM' => true,
		'DATE_BILL' => true, 'DATE_PAY_BEFORE' => true,
		'RECURRING_ACTIVE' => true, 'RECURRING_NEXT_EXECUTION' => true,	'RECURRING_COUNTER_REPEAT' => true,
		'REASON_MARKED_SUCCESS' => true, 'DATE_MARKED' => true, 'REASON_MARKED' => true,
		'RESPONSIBLE_ID' => true, 'CURRENCY' => true, 'PRICE' => true,
		'UF_CONTACT_ID' => true, 'UF_COMPANY_ID' => true,
		'UF_DEAL_ID' => true, 'UF_QUOTE_ID' => true,
		'PR_LOCATION' => true, 'PAYER_INFO' => true, 'PAY_SYSTEM_ID' => true,
		'CLIENT' => true, 'COMMENTS' => true
	);

	$arResult['HEAD_PROGRESS_LEGEND_CONTAINER_ID'] = "{$arResult['GUID']}_header_status_text";
	$arResult['HEAD_PROGRESS_LEGEND'] = isset($entityFields['STATUS_TEXT']) ? $entityFields['STATUS_TEXT'] : '';
	$statusText = htmlspecialcharsbx($arResult['HEAD_PROGRESS_LEGEND']);
	$progressHtml = $arResult['HEAD_PROGRESS_BAR'] = CCrmViewHelper::RenderProgressControl(
		array(
			'ENTITY_TYPE_NAME' => CCrmOwnerType::InvoiceName,
			'REGISTER_SETTINGS' => true,
			'CONTROL_ID' =>  $arResult['GUID'],
			'ENTITY_ID' => $entityFields['ID'],
			'CURRENT_ID' => $entityFields['STATUS_ID'],
			'SERVICE_URL' => '/bitrix/components/bitrix/crm.invoice.list/list.ajax.php',
			'READ_ONLY' => !$canEdit,
			'DISPLAY_LEGEND' => false,
			'LEGEND_CONTAINER_ID' => $arResult['HEAD_PROGRESS_LEGEND_CONTAINER_ID']
		)
	);

	$currencyID = isset($entityFields['CURRENCY'])
		? $entityFields['CURRENCY'] : CCrmInvoice::GetCurrencyID();
	$arResult['HEAD_FORMATTED_SUM'] = CCrmCurrency::MoneyToString(
			isset($entityFields['PRICE']) ? $entityFields['PRICE'] : 0.0, $currencyID
	);
	$arResult['HEAD_SUM_FIELD_ID'] = 'PRICE';

	__CrmQuickPanelViewPrepareContactInfo($entityFields, $entityContext, 'UF_CONTACT', false);
	__CrmQuickPanelViewPrepareCompanyInfo($entityFields, $entityContext, 'UF_COMPANY', false);

	$isSuccessfullStatus = isset($entityFields['STATUS_SUCCESS']) ? mb_strtoupper($entityFields['STATUS_SUCCESS']) === 'Y' : false;
	$isFailedStatus = isset($entityFields['STATUS_FAILED']) ? mb_strtoupper($entityFields['STATUS_FAILED']) === 'Y' : false;

	foreach($entityFields as $k => $v)
	{
		if(!isset($fieldKeys[$k]))
		{
			continue;
		}

		if($k === 'STATUS_ID')
		{
			$entityData[$k] = array(
				'type' => 'custom',
				'data' => array(
					'html' => "<div class=\"crm-detail-stage\"><div class=\"crm-detail-stage-name\">{$statusText}</div>{$progressHtml}</div>"
				)
			);
		}
		elseif($k === 'CURRENCY')
		{
			//HACK: EDIT FORM REFERS BY 'CURRENCY_ID'
			$k = 'CURRENCY_ID';
			$entityData[$k] = __CrmQuickPanelViewPrepareCurrencyEnumeration($v, $enableInstantEdit, $entityContext);
		}
		elseif($k === 'PAY_VOUCHER_DATE' || $k === 'DATE_BILL' || $k === 'DATE_PAY_BEFORE' || $k === 'DATE_MARKED' || $k === 'RECURRING_NEXT_EXECUTION')
		{
			$entityData[$k] = array(
				'type' => 'date',
				'editable'=> $enableInstantEdit,
				'data' => array(
					'text' => ($v !== null && $v !== '') ? ConvertTimeStamp(MakeTimeStamp($v), 'SHORT', SITE_ID) : ''
				)
			);
		}
		elseif($k === 'RECURRING_NEXT_EXECUTION')
		{
			$entityData[$k] = array(
				'type' => 'date',
				'editable'=> false,
				'data' => array(
					'text' => ($v !== null && $v !== '') ? ConvertTimeStamp(MakeTimeStamp($v), 'SHORT', SITE_ID) : ''
				)
			);
		}
		elseif($k === 'RECURRING_COUNTER_REPEAT')
		{
			$entityData[$k] = array(
				'type' => 'datetime',
				'editable'=> false,
				'data' => array('text' => (int)$entityFields[$k]));
		}
		elseif($k === 'RECURRING_ACTIVE')
		{
			$v = ($v !== null && $v !== '')? mb_strtoupper($v) : 'N';
			$entityData[$k] = array(
				'type' => 'boolean',
				'editable'=> false,
				'data' => array('baseType' => 'char', 'value' => $v)
			);
		}
		elseif($k === 'PRICE')
		{
			$entityData[$k] = __CrmQuickPanelViewPrepareMoney(
				isset($entityFields['PRICE']) ? $entityFields['PRICE'] : 0.0,
				$currencyID,
				$enableInstantEdit,
				$arResult['SERVICE_URL'],
				$entityContext
			);
		}
		elseif($k === 'UF_COMPANY_ID' || $k === 'UF_CONTACT_ID')
		{
			if(!isset($entityData['CLIENT']))
			{
				$entityData['CLIENT'] = array(
					'type' => 'composite_client',
					'primaryConfig' => __CrmQuickPanelViewPrepareClientInfo(
						CCrmOwnerType::CompanyName,
						$entityContext,
						$formFieldNames,
						array(
							'FORM_ID' => $arResult['FORM_ID'],
							'PREFIX' => "{$arResult['GUID']}_primary_client"
						)
					),
					'secondaryConfig' => __CrmQuickPanelViewPrepareClientInfo(
						CCrmOwnerType::ContactName,
						$entityContext,
						$formFieldNames,
						array(
							'FORM_ID' => $arResult['FORM_ID'],
							'PREFIX' => "{$arResult['GUID']}_secondary_client"
						)
					),
					'enableCaption' => false
				);
			}
			//region Outmoded data
			if($k === 'UF_COMPANY_ID' && $entityContext['COMPANY_INFO']['ID'] > 0)
			{
				//HACK: EDIT FORM TREAT 'UF_COMPANY_ID' AS 'CLIENT_ID'
				$k = 'CLIENT_ID';
				$entityData[$k] = __CrmQuickPanelViewPrepareClientInfo(
					CCrmOwnerType::CompanyName,
					$entityContext,
					$formFieldNames,
					array(
						'FORM_ID' => $arResult['FORM_ID'],
						'PREFIX' => "{$arResult['GUID']}_company"
					)
				);
			}
			elseif($k === 'UF_CONTACT_ID' && $entityContext['CONTACT_INFO']['ID'] > 0)
			{
				if($entityContext['COMPANY_INFO']['ID'] <= 0)
				{
					//HACK: EDIT FORM TREAT 'UF_CONTACT_ID' AS 'CLIENT_ID'
					$k = 'CLIENT_ID';
				}
				$entityData[$k] = __CrmQuickPanelViewPrepareClientInfo(
					CCrmOwnerType::ContactName,
					$entityContext,
					$formFieldNames,
					array(
						'FORM_ID' => $arResult['FORM_ID'],
						'PREFIX' => "{$arResult['GUID']}_contact"
					)
				);
			}
		}
		elseif($k === 'UF_DEAL_ID')
		{
			$v = (int)$v;
			if($v <= 0)
			{
				$entityData[$k] = array('type' => 'text', 'data' => array('text' => GetMessage('CRM_ENTITY_QPV_DEAL_NOT_ASSIGNED')));
			}
			else
			{
				$caption = isset($entityFields['UF_DEAL_TITLE']) ? $entityFields['UF_DEAL_TITLE'] : '';
				if($caption === '')
				{
					$caption = CCrmOwnerType::GetCaption(CCrmOwnerType::Deal, $v);
				}

				$showUrl = isset($entityFields['UF_DEAL_SHOW_URL']) ? $entityFields['UF_DEAL_SHOW_URL'] : '';
				if($showUrl === '')
				{
					$showUrl = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Deal, $v, true);
				}

				if($showUrl === '')
				{
					$entityData[$k] = array(
						'type' => 'text',
						'data' => array('text' => $caption)
					);
				}
				else
				{
					$entityData[$k] = array(
						'type' => 'link',
						'data' => array('text' => $caption, 'url' => $showUrl)
					);
				}
			}
		}
		elseif($k === 'UF_QUOTE_ID')
		{
			$v = (int)$v;
			if($v <= 0)
			{
				$entityData[$k] = array('type' => 'text', 'data' => array('text' => GetMessage('CRM_ENTITY_QPV_QUOTE_NOT_ASSIGNED')));
			}
			else
			{
				$caption = isset($entityFields['UF_QUOTE_TITLE']) ? $entityFields['UF_QUOTE_TITLE'] : '';
				if($caption === '')
				{
					$caption = CCrmOwnerType::GetCaption(CCrmOwnerType::Quote, $v);
				}

				$showUrl = isset($entityFields['UF_QUOTE_SHOW_URL']) ? $entityFields['UF_QUOTE_SHOW_URL'] : '';
				if($showUrl === '')
				{
					$showUrl = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Quote, $v, true);
				}

				if($showUrl === '')
				{
					$entityData[$k] = array(
						'type' => 'text',
						'data' => array('text' => $caption)
					);
				}
				else
				{
					$entityData[$k] = array(
						'type' => 'link',
						'data' => array('text' => $caption, 'url' => $showUrl)
					);
				}
			}
		}
		elseif($k === 'RESPONSIBLE_ID')
		{
			$entityData['RESPONSIBLE_ID'] = __CrmQuickPanelViewPrepareResponsible(
				$entityFields,
				$userProfilePath,
				$nameTemplate,
				$enableInstantEdit,
				$arResult['INSTANT_EDITOR_ID'],
				$arResult['SERVICE_URL'],
				'RESPONSIBLE',
				false
			);
		}
		elseif($k === 'PR_LOCATION')
		{
			//HACK: EDIT FORM REFERS 'PR_LOCATION' BY 'LOCATION_ID'
			$k = 'LOCATION_ID';
			$entityData[$k] = array(
				'type' => 'text',
				'data' => [
					'text' => $v > 0
						? CCrmLocations::getLocationStringByCode($v)
						: GetMessage('CRM_ENTITY_QPV_LOCATION_NOT_ASSIGNED')
				]
			);
		}
		elseif($k === 'PAY_SYSTEM_ID')
		{
			$entityData[$k] = array(
				'type' => 'text',
				'data' => array('text' => isset($entityFields['PAY_SYSTEM_NAME']) ? $entityFields['PAY_SYSTEM_NAME'] : GetMessage('CRM_ENTITY_QPV_PAY_SYSTEM_NOT_ASSIGNED'))
			);
		}
		elseif($k === 'COMMENTS')
		{
			$entityData[$k] = array(
				'type' => 'html',
				'editable'=> $enableInstantEdit,
				'data' => array(
					'html' => $entityFields[$k],
					'serviceUrl' => $arResult['SERVICE_URL']
				)
			);
		}
		else
		{
			$entityData[$k] = array(
				'type' => 'text',
				'editable'=> $enableInstantEdit,
				'data' => array('text' => $entityFields[$k])
			);
		}

		if($k === 'PAY_VOUCHER_DATE' || $k === 'PAY_VOUCHER_NUM' || $k == 'REASON_MARKED_SUCCESS')
		{
			$entityData[$k]['visible'] = $isSuccessfullStatus;
		}
		elseif($k === 'DATE_MARKED' || $k === 'REASON_MARKED')
		{
			$entityData[$k]['visible'] = $isFailedStatus;
		}

		$caption = isset($formFieldNames[$k]) ? $formFieldNames[$k] : '';
		if($caption === '')
		{
			$caption = CCrmInvoice::GetFieldCaption($k);
		}
		$entityData[$k]['caption'] = $caption;
	}

	$arResult['HEAD_TITLE'] = isset($entityFields['ORDER_TOPIC']) ? htmlspecialcharsbx($entityFields['ORDER_TOPIC']) : '';
	$arResult['HEAD_TITLE_FIELD_ID'] = 'ORDER_TOPIC';
}
else
{
	ShowError(GetMessage('CRM_ENTITY_QPV_ENTITY_TYPE_NAME_NOT_SUPPORTED'));
	return;
}

if($entityTypeID !== CCrmOwnerType::Deal && $entityTypeID !== CCrmOwnerType::Invoice && $entityTypeID !== CCrmOwnerType::Quote)
{
	if(!(isset($entityFields['FM']) && is_array($entityFields['FM'])))
	{
		$entityFields['FM'] = __CrmQuickPanelViewLoadMultiFields($entityTypeName, $entityID);
	}
	if(!isset($entityFields['FM']['PHONE']) || empty($entityFields['FM']['PHONE']))
	{
		if(!isset($entityFields['FM']['PHONE']))
		{
			$entityFields['FM']['PHONE'] = array();
		}
		$entityFields['FM']['PHONE']['n0'] = array('VALUE' => '', 'VALUE_TYPE' => 'OTHER');
	}
	if(!isset($entityFields['FM']['EMAIL']) || empty($entityFields['FM']['EMAIL']))
	{
		if(!isset($entityFields['FM']['EMAIL']))
		{
			$entityFields['FM']['EMAIL'] = array();
		}
		$entityFields['FM']['EMAIL']['n0'] = array('VALUE' => '', 'VALUE_TYPE' => 'OTHER');
	}
	if(!isset($entityFields['FM']['WEB']) || empty($entityFields['FM']['WEB']))
	{
		if(!isset($entityFields['FM']['WEB']))
		{
			$entityFields['FM']['WEB'] = array();
		}
		$entityFields['FM']['WEB']['n0'] = array('VALUE' => '', 'VALUE_TYPE' => 'OTHER');
	}
	if(!isset($entityFields['FM']['IM']) || empty($entityFields['FM']['IM']))
	{
		if(!isset($entityFields['FM']['IM']))
		{
			$entityFields['FM']['IM'] = array();
		}
		$entityFields['FM']['IM']['n0'] = array('VALUE' => '', 'VALUE_TYPE' => 'OTHER');
	}
}

if(isset($entityFields['FM']))
{
	$entityContext['MULTI_FIELDS_OPTIONS'] = array(
		'STUB' => GetMessage('CRM_ENTITY_QPV_MULTI_FIELD_NOT_ASSIGNED'),
		'ENABLE_SIP' => true,
		'SIP_PARAMS' => array(
			'ENTITY_TYPE' => 'CRM_'.$entityTypeName,
			'ENTITY_ID' => $entityID)
	);
	foreach($entityFields['FM'] as $typeID => $multiFields)
	{
		$entityData[$typeID] = __CrmQuickPanelViewPrepareMultiFields($multiFields, $entityTypeName, $entityID, $typeID, $formFieldNames);
	}
}

if($ufEntityID !== '')
{
	$arUserFields = $USER_FIELD_MANAGER->GetUserFields($ufEntityID, $entityID, LANGUAGE_ID);

	// remove invoice reserved fields
	if ($ufEntityID === CCrmInvoice::GetUserFieldEntityID())
		foreach (CCrmInvoice::GetUserFieldsReserved() as $ufId)
			if (isset($arUserFields[$ufId]))
				unset($arUserFields[$ufId]);

	foreach($arUserFields as $fieldName => &$arUserField)
	{
		$editable = $enableInstantEdit && isset($arUserField['EDIT_IN_LIST']) && $arUserField['EDIT_IN_LIST'] === 'Y';
		if($arUserField['MULTIPLE'] === 'Y')
		{
			continue;
		}

		$userTypeID = $arUserField['USER_TYPE']['USER_TYPE_ID'];
		$value = isset($arUserField['VALUE']) ? $arUserField['VALUE'] : '';
		$caption = isset($formFieldNames[$fieldName]) ? $formFieldNames[$fieldName] : '';
		if($caption === '')
		{
			$caption = isset($arUserField['EDIT_FORM_LABEL']) ? $arUserField['EDIT_FORM_LABEL'] : $fieldName;
		}

		if($userTypeID === 'string' || $userTypeID === 'integer' || $userTypeID === 'double' || $userTypeID === 'datetime' || $userTypeID === 'date')
		{
			if($userTypeID === 'datetime')
			{
				$fieldTypeName = 'datetime';
				$isMultiline = false;
			}
			elseif($userTypeID === 'date')
			{
				$fieldTypeName = 'date';
				$isMultiline = false;
			}
			else
			{
				$fieldTypeName = 'text';
				$isMultiline = true;
			}

			$entityData[$fieldName] = array(
				'type' => $fieldTypeName,
				'editable'=> $editable,
				'caption' => $caption,
				'data' => array('text' => $value, 'multiline' => $isMultiline)
			);
		}
		elseif($userTypeID === 'enumeration')
		{
			$text = "";
			$enums = array();
			$enumEntity = new CUserFieldEnum();
			$dbResultEnum = $enumEntity->GetList(array('SORT'=>'ASC'), array('USER_FIELD_ID' => $arUserField['ID']));
			while ($enum = $dbResultEnum->Fetch())
			{
				$enums[] = array('ID' => $enum['ID'], 'VALUE' => $enum['VALUE']);

				if($text === '' && $value !== '' && $value === $enum['ID'])
				{
					$text = $enum['VALUE'];
				}
			}

			$entityData[$fieldName] = array(
				'type' => 'enumeration',
				'editable'=> $editable,
				'caption' => $caption,
				'data' => array(
					'value' => $value,
					'text' => $text,
					'items' => $enums
				)
			);
		}
		elseif($userTypeID === 'boolean')
		{
			$entityData[$fieldName] = array(
				'type' => 'boolean',
				'editable'=> $editable,
				'caption' => $caption,
				'data' => array('baseType' => 'int', 'value' => $value)
			);
		}
	}
	unset($arUserField);
}


$arResult['ENTITY_DATA'] = $entityData;
$arResult['ENTITY_FIELDS'] = $entityFields;
$arResult['CAN_EDIT_OTHER_SETTINGS'] = CCrmAuthorizationHelper::CanEditOtherSettings();
$arResult['ENTITY_CONTEXT'] = $entityContext;
$arResult['CONFIG'] = $config;

$this->IncludeComponentTemplate();