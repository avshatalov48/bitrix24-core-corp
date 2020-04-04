<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
global $DB, $APPLICATION;
if(!function_exists('__CrmQuickPanelViewEndResponse'))
{
	function __CrmQuickPanelViewEndResponse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		if(!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}

if (!CModule::IncludeModule('crm'))
{
	__CrmQuickPanelViewEndResponse(array('ERROR' => 'Could not include crm module.'));
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

/*
 * ONLY 'POST' METHOD SUPPORTED
 * SUPPORTED ACTIONS:
 *  'GET_CLIENT_INFOS'
 */
if (!CCrmSecurityHelper::IsAuthorized() || !check_bitrix_sessid())
{
	__CrmQuickPanelViewEndResponse(array('ERROR' => 'Access denied.'));
}
if ($_SERVER['REQUEST_METHOD'] != 'POST')
{
	__CrmQuickPanelViewEndResponse(array('ERROR' => 'Request method is not allowed.'));
}

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
CUtil::JSPostUnescape();
$GLOBALS['APPLICATION']->RestartBuffer();
Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

$action = isset($_POST['ACTION']) ? $_POST['ACTION'] : '';
if($action === 'GET_CLIENT_INFOS')
{
	$userPermissions = CCrmPerms::GetCurrentUserPermissions();
	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();
	$entityTypeName = isset($params['ENTITY_TYPE_NAME']) ? $params['ENTITY_TYPE_NAME'] : '';
	if($entityTypeName === '')
	{
		__CrmQuickPanelViewEndResponse(array('ERROR' => 'Entity type is not specified.'));
	}

	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if($entityTypeID === CCrmOwnerType::Undefined)
	{
		__CrmQuickPanelViewEndResponse(array('ERROR' => 'Undefined entity type is specified.'));
	}

	$ownerTypeName = isset($params['OWNER_TYPE_NAME']) ? $params['OWNER_TYPE_NAME'] : '';
	if($ownerTypeName === '')
	{
		__CrmQuickPanelViewEndResponse(array('ERROR' => 'Owner type is not specified.'));
	}

	$ownerTypeID = CCrmOwnerType::ResolveID($ownerTypeName);
	if($ownerTypeID === CCrmOwnerType::Undefined)
	{
		__CrmQuickPanelViewEndResponse(array('ERROR' => 'Undefined owner type is specified.'));
	}

	$ownerID = isset($params['OWNER_ID']) ? (int)$params['OWNER_ID'] : 0;
	if($ownerID <= 0)
	{
		__CrmQuickPanelViewEndResponse(array('ERROR' => 'Owner ID is not specified.'));
	}

	if(!CCrmAuthorizationHelper::CheckReadPermission($ownerTypeID, $ownerID, $userPermissions))
	{
		__CrmQuickPanelViewEndResponse(array('ERROR' => 'Access denied.'));
	}

	$formID = isset($params['FORM_ID']) ? $params['FORM_ID'] : '';
	$formFieldNames = CCrmViewHelper::getFormFieldNames($formID);

	$entityIDs = null;
	if($ownerTypeID === CCrmOwnerType::Contact && $entityTypeID === CCrmOwnerType::Company)
	{
		$entityIDs = \Bitrix\Crm\Binding\ContactCompanyTable::getContactCompanyIDs($ownerID);
	}
	elseif($ownerTypeID === CCrmOwnerType::Deal && $entityTypeID === CCrmOwnerType::Contact)
	{
		$entityIDs = \Bitrix\Crm\Binding\DealContactTable::getDealContactIDs($ownerID);
	}
	elseif($ownerTypeID === CCrmOwnerType::Quote && $entityTypeID === CCrmOwnerType::Contact)
	{
		$entityIDs = \Bitrix\Crm\Binding\QuoteContactTable::getQuoteContactIDs($ownerID);
	}

	$data = array();
	if(!empty($entityIDs))
	{
		if($entityTypeID === CCrmOwnerType::Company)
		{
			$dbRes = CCrmCompany::GetListEx(
				array(),
				array('@ID' => $entityIDs, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID', 'TITLE', 'LOGO')
			);

			if(is_object($dbRes))
			{
				$file = new CFile();
				while($fields = $dbRes->Fetch())
				{
					$entityID = (int)$fields['ID'];
					$info = array(
						'ENTITY_TYPE_NAME' => CCrmOwnerType::CompanyName,
						'ENTITY_ID' => $entityID,
						'NAME' => isset($fields['TITLE']) ? $fields['TITLE'] : '',
						'DESCRIPTION' => '',
						'SHOW_URL' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Company, $fields['ID'], false),
					);
					$imageID = isset($fields['LOGO']) ? (int)$fields['LOGO'] : 0;
					if($imageID <= 0)
					{
						$info['IMAGE_URL'] = '';
					}
					else
					{
						$fileInfo = $file->ResizeImageGet(
							$imageID,
							array('width' => 48, 'height' => 31),
							BX_RESIZE_IMAGE_PROPORTIONAL
						);
						$info['IMAGE_URL'] = is_array($fileInfo) && isset($fileInfo['src']) ? $fileInfo['src'] : '';
					}

					if(!CCrmAuthorizationHelper::CheckReadPermission(CCrmOwnerType::Company, $entityID, $userPermissions))
					{
						$info['ENABLE_MULTIFIELDS'] = false;
					}
					else
					{
						$multiFieldDbRes = CCrmFieldMulti::GetList(
							array('ID' => 'asc'),
							array('ENTITY_ID' => CCrmOwnerType::CompanyName, 'ELEMENT_ID' => $entityID)
						);


						$multiFieldData = array();
						while($multiFields = $multiFieldDbRes->Fetch())
						{
							$multiFieldData[$multiFields['TYPE_ID']][$multiFields['ID']] = array('VALUE' => $multiFields['VALUE'], 'VALUE_TYPE' => $multiFields['VALUE_TYPE']);
						}

						if(isset($multiFieldData['PHONE']))
						{
							$info['PHONE'] = __CrmQuickPanelViewPrepareMultiFields(
								$multiFieldData['PHONE'],
								CCrmOwnerType::CompanyName,
								$entityID,
								'PHONE',
								$formFieldNames
							);
						}

						if(isset($multiFieldData['EMAIL']))
						{
							$info['EMAIL'] = __CrmQuickPanelViewPrepareMultiFields(
								$multiFieldData['EMAIL'],
								CCrmOwnerType::CompanyName,
								$entityID,
								'EMAIL',
								$formFieldNames
							);
						}
					}

					$data[] = array('type' => 'client', 'data' => $info);
				}
			}
		}
		elseif($entityTypeID === CCrmOwnerType::Contact)
		{
			$dbRes = CCrmContact::GetListEx(
				array(),
				array('@ID' => $entityIDs, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'POST', 'PHOTO')
			);

			if(is_object($dbRes))
			{
				$file = new CFile();
				while($fields = $dbRes->Fetch())
				{
					$entityID = (int)$fields['ID'];
					$info = array(
						'ENTITY_TYPE_NAME' => CCrmOwnerType::ContactName,
						'ENTITY_ID' => $entityID,
						'NAME' => CCrmContact::PrepareFormattedName($fields),
						'DESCRIPTION' => isset($fields['POST']) ? $fields['POST'] : '',
						'SHOW_URL' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Contact, $fields['ID'], false),
					);
					$imageID = isset($fields['PHOTO']) ? (int)$fields['PHOTO'] : 0;
					if($imageID <= 0)
					{
						$info['IMAGE_URL'] = '';
					}
					else
					{
						$fileInfo = $file->ResizeImageGet(
							$imageID,
							array('width' => 38, 'height' => 38),
							BX_RESIZE_IMAGE_EXACT
						);
						$info['IMAGE_URL'] = is_array($fileInfo) && isset($fileInfo['src']) ? $fileInfo['src'] : '';
					}

					if(!CCrmAuthorizationHelper::CheckReadPermission(CCrmOwnerType::Contact, $entityID, $userPermissions))
					{
						$info['ENABLE_MULTIFIELDS'] = false;
					}
					else
					{
						$multiFieldDbRes = CCrmFieldMulti::GetList(
							array('ID' => 'asc'),
							array('ENTITY_ID' => CCrmOwnerType::ContactName, 'ELEMENT_ID' => $entityID)
						);


						$multiFieldData = array();
						while($multiFields = $multiFieldDbRes->Fetch())
						{
							$multiFieldData[$multiFields['TYPE_ID']][$multiFields['ID']] = array('VALUE' => $multiFields['VALUE'], 'VALUE_TYPE' => $multiFields['VALUE_TYPE']);
						}

						if(isset($multiFieldData['PHONE']))
						{
							$info['PHONE'] = __CrmQuickPanelViewPrepareMultiFields(
								$multiFieldData['PHONE'],
								CCrmOwnerType::ContactName,
								$entityID,
								'PHONE',
								$formFieldNames
							);
						}

						if(isset($multiFieldData['EMAIL']))
						{
							$info['EMAIL'] = __CrmQuickPanelViewPrepareMultiFields(
								$multiFieldData['EMAIL'],
								CCrmOwnerType::ContactName,
								$entityID,
								'EMAIL',
								$formFieldNames
							);
						}
					}

					$data[] = array('type' => 'client', 'data' => $info);
				}
			}
		}
	}

	__CrmQuickPanelViewEndResponse(array('DATA' => $data));
}
else
{
	__CrmQuickPanelViewEndResponse(array('ERROR' => "Action '{$action}' is not supported in current context."));
}
