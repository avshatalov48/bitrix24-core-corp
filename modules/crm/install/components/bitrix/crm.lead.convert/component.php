<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$arParams['PATH_TO_LEAD_LIST'] = CrmCheckPath('PATH_TO_LEAD_LIST', $arParams['PATH_TO_LEAD_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_LEAD_CONVERT'] = CrmCheckPath('PATH_TO_LEAD_CONVERT', $arParams['PATH_TO_LEAD_CONVERT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&convert');
$arParams['ELEMENT_ID'] = (int) $arParams['ELEMENT_ID'];

global $USER_FIELD_MANAGER, $USER;
$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmLead::$sUFEntityID);

$obFields = CCrmLead::GetListEx(array(), array('=ID' => $arParams['ELEMENT_ID'], 'CHECK_PERMISSIONS'=> 'N'), false, false, array('*', 'UF_*'));
$arLead = $arFields = is_object($obFields) ? $obFields->GetNext() : false;
if (!is_array($arLead))
{
	LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_LIST'], array()));
}

$userPermissions = new CCrmPerms($USER->GetID());
if(!CCrmLead::CheckConvertPermission($arParams['ELEMENT_ID'], CCrmOwnerType::Undefined, $userPermissions))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

foreach($arFields as $key => $value)
	if (isset($arFields['~'.$key]))
		$arFields[$key] = $arFields['~'.$key];

$arFieldsReplace = array();
$CCrmUserType->ListAddEnumFieldsValue($arFields, $arFieldsReplace, $arFields['ID']);
$CCrmUserType->PrepareUpdate($arFields);

$arResult['ELEMENT']['ID'] = $arFields['ID'];
$arResult['ELEMENT']['TITLE'] = $arFields['TITLE'];
unset($arFields['ID']);

if ($arFields['STATUS_ID'] == 'CONVERTED')
	LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_LIST'], array()));

$arResult['ELEMENT']['CONTACT'] = $arFields;
// associate custom fields leads and contacts
$arUFLead = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields('CRM_LEAD', 0, LANGUAGE_ID);
$arUFContact = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields('CRM_CONTACT', 0, LANGUAGE_ID);
foreach ($arUFLead as $_arUfLead)
{
	foreach ($arUFContact as $_arUFContact)
	{
		if ($_arUfLead['USER_TYPE_ID'] == $_arUFContact['USER_TYPE_ID'] &&
			strtolower(trim($_arUfLead['EDIT_FORM_LABEL'])) == strtolower(trim($_arUFContact['EDIT_FORM_LABEL'])))
		{
			$arResult['ELEMENT']['CONTACT'][$_arUFContact['FIELD_NAME']] = $arFields[$_arUfLead['FIELD_NAME']];
			break;
		}
	}
}

unset($arResult['ELEMENT']['CONTACT']['ASSIGNED_BY_ID'], $arResult['ELEMENT']['CONTACT']['ASSIGNED_BY']);
$arResult['ELEMENT']['COMPANY'] = array(
	'TITLE' => !empty($arFields['COMPANY_TITLE']) ? $arFields['COMPANY_TITLE'] : $arFields['TITLE'],
	'OPENED' => !empty($arFields['OPENED']) ? $arFields['OPENED'] : 'N',
	'ADDRESS' => !empty($arFields['ADDRESS']) ? $arFields['ADDRESS'] : '',
	'ADDRESS_2' => !empty($arFields['ADDRESS_2']) ? $arFields['ADDRESS_2'] : '',
	'ADDRESS_CITY' => !empty($arFields['ADDRESS_CITY']) ? $arFields['ADDRESS_CITY'] : '',
	'ADDRESS_REGION' => !empty($arFields['ADDRESS_REGION']) ? $arFields['ADDRESS_REGION'] : '',
	'ADDRESS_PROVINCE' => !empty($arFields['ADDRESS_PROVINCE']) ? $arFields['ADDRESS_PROVINCE'] : '',
	'ADDRESS_POSTAL_CODE' => !empty($arFields['ADDRESS_POSTAL_CODE']) ? $arFields['ADDRESS_POSTAL_CODE'] : '',
	'ADDRESS_COUNTRY' => !empty($arFields['ADDRESS_COUNTRY']) ? $arFields['ADDRESS_COUNTRY'] : '',
	'ADDRESS_COUNTRY_CODE' => !empty($arFields['ADDRESS_COUNTRY_CODE']) ? $arFields['ADDRESS_COUNTRY_CODE'] : ''

);
$arResult['ELEMENT']['DEAL'] = array(
	'TITLE' => $arFields['TITLE'],
	'BEGINDATE' => ConvertTimeStamp(),
	'OPPORTUNITY' => $arFields['OPPORTUNITY'],
	'CURRENCY_ID' => $arFields['CURRENCY_ID'],
	//'PRODUCT_ID' => $arFields['PRODUCT_ID'],
	'OPENED' => !empty($arFields['OPENED']) ? $arFields['OPENED'] : 'N',
	'COMMENTS' => isset($arFields['COMMENTS']) ? $arFields['COMMENTS'] : ''
);
unset($arFields);

if (empty($arResult['ELEMENT']['CONTACT']))
	LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_LIST'], array()));

$res = CCrmFieldMulti::GetList(
	array('ID' => 'asc'),
	array('ENTITY_ID' => 'LEAD', 'ELEMENT_ID' => $arParams['ELEMENT_ID'])
);
$arResult['ELEMENT']['FM'] = array();
while($ar = $res->Fetch())
{
	$arResult['ELEMENT']['FM'][$ar['TYPE_ID']][$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);
	$arResult['ELEMENT']['CONTACT']['FM'][$ar['TYPE_ID']]['n0'.$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);
	$arResult['ELEMENT']['COMPANY']['FM'][$ar['TYPE_ID']]['n0'.$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);
}

$bVarsFromForm = false;
if($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
{
	if(isset($_POST['save']) || isset($_POST['apply']))
	{
		$bConvertDeal = (isset($_POST['CONVERT_DEAL']) && $_POST['CONVERT_DEAL'] == 'Y');
		$bConvertCompany = (isset($_POST['CONVERT_COMPANY']) && $_POST['CONVERT_COMPANY'] == 'Y');
		$bConvertContact = (isset($_POST['CONVERT_CONTACT']) && $_POST['CONVERT_CONTACT'] == 'Y');

		$arFields = array();
		$arEntity = array('CONTACT', 'COMPANY', 'DEAL');
		foreach ($arEntity as $sEntity)
		{
			if($sEntity === 'CONTACT')
			{
				$entityFields = CAllCrmContact::GetFields();
			}
			elseif($sEntity === 'COMPANY')
			{
				$entityFields = CAllCrmCompany::GetFields();
			}
			elseif($sEntity === 'DEAL')
			{
				$entityFields = CAllCrmDeal::GetFields();
			}
			else
			{
				$entityFields = array();
			}

			$entityPrefix = $sEntity.'_';
			foreach ($_POST as $k => $v)
			{
				if (strpos($k, $entityPrefix) !== 0)
				{
					continue;
				}

				$fieldKey = substr($k, strlen($entityPrefix));
				// Make an exception for CONTACT_ID and COMPANY_ID - special fields.
				if(isset($entityFields[$fieldKey])
					||($sEntity === 'CONTACT' && $fieldKey === 'CONTACT_ID')
					|| ($sEntity === 'COMPANY' && $fieldKey === 'COMPANY_ID'))
				{
					$arFields[$sEntity][$fieldKey] = $v;
				}
			}
			$USER_FIELD_MANAGER->EditFormAddFields('CRM_'.$sEntity, $arFields[$sEntity]);
		}

		$arFields['CONTACT']['PHOTO'] = $_FILES['CONTACT_PHOTO'];
		$arFields['COMPANY']['LOGO'] = $_FILES['COMPANY_LOGO'];
		$arFields['CONTACT']['LEAD_ID'] = $arParams['ELEMENT_ID'];
		$arFields['COMPANY']['LEAD_ID'] = $arParams['ELEMENT_ID'];
		$arFields['DEAL']['LEAD_ID'] = $arParams['ELEMENT_ID'];
		$arFields['CONTACT']['FM'] = $_POST['CONFM'];
		$arFields['COMPANY']['FM'] = $_POST['COMFM'];

		if(isset($_POST['CONTACT_ADDRESS']))
		{
			$arFields['CONTACT']['ADDRESS'] = trim($_POST['CONTACT_ADDRESS']);
		}

		if(isset($_POST['CONTACT_ADDRESS_2']))
		{
			$arFields['CONTACT']['ADDRESS_2'] = trim($_POST['CONTACT_ADDRESS_2']);
		}

		if(isset($_POST['CONTACT_ADDRESS_CITY']))
		{
			$arFields['CONTACT']['ADDRESS_CITY'] = trim($_POST['CONTACT_ADDRESS_CITY']);
		}

		if(isset($_POST['CONTACT_ADDRESS_POSTAL_CODE']))
		{
			$arFields['CONTACT']['ADDRESS_POSTAL_CODE'] = trim($_POST['CONTACT_ADDRESS_POSTAL_CODE']);
		}

		if(isset($_POST['CONTACT_ADDRESS_REGION']))
		{
			$arFields['CONTACT']['ADDRESS_REGION'] = trim($_POST['CONTACT_ADDRESS_REGION']);
		}

		if(isset($_POST['CONTACT_ADDRESS_PROVINCE']))
		{
			$arFields['CONTACT']['ADDRESS_PROVINCE'] = trim($_POST['CONTACT_ADDRESS_PROVINCE']);
		}

		if(isset($_POST['CONTACT_ADDRESS_COUNTRY']))
		{
			$arFields['CONTACT']['ADDRESS_COUNTRY'] = trim($_POST['CONTACT_ADDRESS_COUNTRY']);
		}

		if(isset($_POST['CONTACT_ADDRESS_COUNTRY_CODE']))
		{
			$arFields['CONTACT']['ADDRESS_COUNTRY_CODE'] = trim($_POST['CONTACT_ADDRESS_COUNTRY_CODE']);
		}

		if(isset($_POST['COMPANY_ADDRESS']))
		{
			$arFields['COMPANY']['ADDRESS'] = trim($_POST['COMPANY_ADDRESS']);
		}

		if(isset($_POST['COMPANY_ADDRESS_2']))
		{
			$arFields['COMPANY']['ADDRESS_2'] = trim($_POST['COMPANY_ADDRESS_2']);
		}

		if(isset($_POST['COMPANY_ADDRESS_CITY']))
		{
			$arFields['COMPANY']['ADDRESS_CITY'] = trim($_POST['COMPANY_ADDRESS_CITY']);
		}

		if(isset($_POST['COMPANY_ADDRESS_POSTAL_CODE']))
		{
			$arFields['COMPANY']['ADDRESS_POSTAL_CODE'] = trim($_POST['COMPANY_ADDRESS_POSTAL_CODE']);
		}

		if(isset($_POST['COMPANY_ADDRESS_REGION']))
		{
			$arFields['COMPANY']['ADDRESS_REGION'] = trim($_POST['COMPANY_ADDRESS_REGION']);
		}

		if(isset($_POST['COMPANY_ADDRESS_PROVINCE']))
		{
			$arFields['COMPANY']['ADDRESS_PROVINCE'] = trim($_POST['COMPANY_ADDRESS_PROVINCE']);
		}

		if(isset($_POST['COMPANY_ADDRESS_COUNTRY']))
		{
			$arFields['COMPANY']['ADDRESS_COUNTRY'] = trim($_POST['COMPANY_ADDRESS_COUNTRY']);
		}

		if(isset($_POST['COMPANY_ADDRESS_COUNTRY_CODE']))
		{
			$arFields['COMPANY']['ADDRESS_COUNTRY_CODE'] = trim($_POST['COMPANY_ADDRESS_COUNTRY_CODE']);
		}

		if(isset($_POST['COMPANY_REG_ADDRESS']))
		{
			$arFields['COMPANY']['REG_ADDRESS'] = trim($_POST['COMPANY_REG_ADDRESS']);
		}

		if(isset($_POST['COMPANY_REG_ADDRESS_2']))
		{
			$arFields['COMPANY']['REG_ADDRESS_2'] = trim($_POST['COMPANY_REG_ADDRESS_2']);
		}

		if(isset($_POST['COMPANY_REG_ADDRESS_CITY']))
		{
			$arFields['COMPANY']['REG_ADDRESS_CITY'] = trim($_POST['COMPANY_REG_ADDRESS_CITY']);
		}

		if(isset($_POST['COMPANY_REG_ADDRESS_POSTAL_CODE']))
		{
			$arFields['COMPANY']['REG_ADDRESS_POSTAL_CODE'] = trim($_POST['COMPANY_REG_ADDRESS_POSTAL_CODE']);
		}

		if(isset($_POST['COMPANY_REG_ADDRESS_REGION']))
		{
			$arFields['COMPANY']['REG_ADDRESS_REGION'] = trim($_POST['COMPANY_REG_ADDRESS_REGION']);
		}

		if(isset($_POST['COMPANY_REG_ADDRESS_PROVINCE']))
		{
			$arFields['COMPANY']['REG_ADDRESS_PROVINCE'] = trim($_POST['COMPANY_REG_ADDRESS_PROVINCE']);
		}

		if(isset($_POST['COMPANY_REG_ADDRESS_COUNTRY']))
		{
			$arFields['COMPANY']['REG_ADDRESS_COUNTRY'] = trim($_POST['COMPANY_REG_ADDRESS_COUNTRY']);
		}

		if(isset($_POST['COMPANY_REG_ADDRESS_COUNTRY_CODE']))
		{
			$arFields['COMPANY']['REG_ADDRESS_COUNTRY_CODE'] = trim($_POST['COMPANY_REG_ADDRESS_COUNTRY_CODE']);
		}

		$iCompanyId = (int)(is_array($arFields['COMPANY']['COMPANY_ID']) ? $arFields['COMPANY']['COMPANY_ID'][0] : (!empty($arFields['COMPANY']['COMPANY_ID']) ? $arFields['COMPANY']['COMPANY_ID'] : 0));
		$iContactId = (int)(is_array($arFields['CONTACT']['CONTACT_ID']) ? $arFields['CONTACT']['CONTACT_ID'][0] : (!empty($arFields['CONTACT']['CONTACT_ID']) ? $arFields['CONTACT']['CONTACT_ID'] : 0));
		$arResult['ERROR_MESSAGE'] = '';

		$CCrmContact = new CCrmContact(false);
		if ($bConvertContact
			&& $CCrmContact->CheckFields($arFields['CONTACT']) == false)
		{
			$bVarsFromForm = true;
			if (!empty($CCrmContact->LAST_ERROR))
				$arResult['ERROR_MESSAGE'] .= $CCrmContact->LAST_ERROR;
			else
				$arResult['ERROR_MESSAGE'] .= GetMessage('UNKNOWN_ERROR').'<br />';
		} else if (!$bConvertContact && $iContactId <= 0)
			$arResult['ERROR_MESSAGE'] .= GetMessage('CRM_CONTACT_ERROR').'<br />';

		if (!$userPermissions->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'ADD'))
		{
			$CCrmCompany = new CCrmCompany(false);
			if ($bConvertCompany
				&& empty($arResult['ERROR_MESSAGE']) && $CCrmCompany->CheckFields($arFields['COMPANY']) == false)
			{
				$bVarsFromForm = true;
				if (!empty($CCrmCompany->LAST_ERROR))
					$arResult['ERROR_MESSAGE'] .= $CCrmCompany->LAST_ERROR;
				else
					$arResult['ERROR_MESSAGE'] .= GetMessage('UNKNOWN_ERROR').'<br />';
			}
/*			else if (!$bConvertCompany && $iCompanyId <= 0)
				$arResult['ERROR_MESSAGE'] .= GetMessage('CRM_COMPANY_ERROR').'<br />';*/
		}

		if (CCrmDeal::CheckCreatePermission($userPermissions, 0))
		{
			$CCrmDeal = new CCrmDeal(false);
			if ($bConvertDeal)
			{
				$prodJson = isset($_POST['DEAL_PRODUCT_DATA']) ? strval($_POST['DEAL_PRODUCT_DATA']) : '';
				$arFields['DEAL']['PRODUCT_ROWS'] = isset($prodJson[0]) ? CUtil::JsObjectToPhp($prodJson) : array();
				if ($CCrmDeal->CheckFields($arFields['DEAL']) == false)
				{
					$bVarsFromForm = true;
					if (!empty($CCrmDeal->LAST_ERROR))
						$arResult['ERROR_MESSAGE'] .= $CCrmDeal->LAST_ERROR;
					else
						$arResult['ERROR_MESSAGE'] .= GetMessage('UNKNOWN_ERROR').'<br />';
				}
			}
		}

		/*
		if (!$bConvertCompany && !$bConvertContact && !$bConvertDeal)
		{
			$arResult['ERROR_MESSAGE'] .= GetMessage('CRM_DEAL_ERROR').'<br />';
			$_POST['CONVERT_DEAL'] = 'Y';
		}
		*/

		if (empty($arResult['ERROR_MESSAGE']))
		{
			if ($bConvertCompany)
			{
				$iCompanyId = $CCrmCompany->Add($arFields['COMPANY'], true, array('REGISTER_SONET_EVENT' => true));
				if($iCompanyId > 0)
				{
					$CCrmCompanyBizProc = new CCrmBizProc('COMPANY');
					$arCompanyBizProcParams = $CCrmCompanyBizProc->CheckFields(
						false,
						false,
						$arFields['COMPANY']['ASSIGNED_BY_ID'],
						null
					);

					if($arCompanyBizProcParams !== false)
					{
						$CCrmCompanyBizProc->StartWorkflow($iCompanyId, $arCompanyBizProcParams);
					}
				}
			}

			$arFields['CONTACT']['COMPANY_ID'] = $iCompanyId;
			$arFields['DEAL']['COMPANY_ID'] = $iCompanyId;

			if ($bConvertContact)
			{
				$iContactId = $CCrmContact->Add($arFields['CONTACT'], true, array('REGISTER_SONET_EVENT' => true));
				if($iContactId > 0)
				{
					$CCrmEvent = new CCrmEvent();
					$CCrmEvent->Share(
						array(
							'ENTITY_TYPE' => 'LEAD',
							'ENTITY_ID' => $arParams['ELEMENT_ID']
						),
						array(
							array(
								'ENTITY_TYPE' => 'CONTACT',
								'ENTITY_ID' => $iContactId
							)
						),
						'MESSAGE'
					);

					$CCrmContactBizProc = new CCrmBizProc('CONTACT');
					$arContactBizProcParams = $CCrmContactBizProc->CheckFields(
						false,
						false,
						$arFields['CONTACT']['ASSIGNED_BY_ID'],
						null
					);

					if($arContactBizProcParams !== false)
					{
						$CCrmContactBizProc->StartWorkflow($iContactId, $arContactBizProcParams);
					}
				}
			}

			$arFields['DEAL']['CONTACT_ID'] = $iContactId;

			if (CCrmDeal::CheckCreatePermission($userPermissions, 0))
			{
				if ($bConvertDeal)
				{
					$arDealFields =  $arFields['DEAL'];
					$iDealId = $CCrmDeal->Add($arDealFields, true, array('REGISTER_SONET_EVENT' => true));
					if($iDealId > 0)
					{
						if(!empty($arDealFields['PRODUCT_ROWS']))
						{
							CCrmDeal::SaveProductRows($iDealId, $arDealFields['PRODUCT_ROWS']);
						}

						$CCrmDealBizProc = new CCrmBizProc('DEAL');
						$arDealBizProcParams = $CCrmDealBizProc->CheckFields(
							false,
							false,
							$arDealFields['ASSIGNED_BY_ID'],
							null
						);

						if($arDealBizProcParams !== false)
						{
							$CCrmDealBizProc->StartWorkflow($iDealId, $arDealBizProcParams);
						}

						//Region automation
						\Bitrix\Crm\Automation\Factory::runOnAdd(\CCrmOwnerType::Deal, $iDealId);
						//end region
					}
				}
			}

			$CCrmLead = new CCrmLead();
			$arFields['LEAD'] = array('STATUS_ID' => 'CONVERTED', 'CONTACT_ID' => $iContactId, 'COMPANY_ID' => $iCompanyId);
			if($CCrmLead->Update($arParams['ELEMENT_ID'], $arFields['LEAD'], true, true, array('REGISTER_SONET_EVENT' => true)))
			{
				$arErrors = array();
				CCrmBizProcHelper::AutoStartWorkflows(
					CCrmOwnerType::Lead,
					$arParams['ELEMENT_ID'],
					CCrmBizProcEventType::Edit,
					$arErrors
				);
				//Region automation
				\Bitrix\Crm\Automation\Factory::runOnStatusChanged(\CCrmOwnerType::Lead, $arParams['ELEMENT_ID']);
				//end region
			}

			if (isset($_POST['apply']))
			{
				LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_CONVERT'],
					array(
						'lead_id' => $arParams['ELEMENT_ID']
					))
				);
			}
			else
				LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_LIST'], array()));
		}
		else
			ShowError($arResult['ERROR_MESSAGE']);

		$arResult['ELEMENT'] = $arFields;
	}
}

unset($_POST['save'], $_POST['apply']);
$arResult['FORM_ID'] = 'CRM_LEAD_CONVERT';
$arResult['GRID_ID'] = 'CRM_LEAD_LIST';
$arResult['BACK_URL'] = $arParams['PATH_TO_LEAD_LIST'];

$arResult['FIELDS'] = array();

$arResult['FIELDS']['tab_convert'][] = array(
	'id' => 'TITLE',
	'name' => GetMessage('CRM_FIELD_TITLE'),
	'params' => array('size' => 50),
	'value' => isset($arLead['TITLE']) ? $arLead['TITLE'] : '',
	'type' => 'label'
);

ob_start();
$APPLICATION->IncludeComponent(
	'bitrix:crm.contact.edit',
	'convert',
	array(
		'ELEMENT_ID' => 0,
		'FORM_ID' => $arResult['FORM_ID'],
		'INTERNAL_FILTER' => true,
		'CONVERT' => true,
		'VARS_FROM_FORM' => $bVarsFromForm,
		'VALUES' => $arResult['ELEMENT']['CONTACT']
	),
	false
);
$sVal = ob_get_contents();
ob_end_clean();

$arResult['FIELDS']['tab_convert'][] = array(
	'id' => 'LEAD_CONTACT_CONVERT',
	'name' => GetMessage('CRM_FIELD_LEAD_CONTACT'),
	'colspan' => true,
	'type' => 'custom',
	'value' => $sVal
);

if (!$userPermissions->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'ADD'))
{
	ob_start();
	$APPLICATION->IncludeComponent(
		'bitrix:crm.company.edit',
		'convert',
		array(
			'ELEMENT_ID' => 0,
			'FORM_ID' => $arResult['FORM_ID'],
			'INTERNAL_FILTER' => true,
			'CONVERT' => true,
			'VALUES' => $arResult['ELEMENT']['COMPANY']
		),
		false
	);
	$sVal = ob_get_contents();
	ob_end_clean();

	$arResult['FIELDS']['tab_convert'][] = array(
		'id' => 'LEAD_COMPANY_CONVERT',
		'name' => GetMessage('CRM_FIELD_LEAD_COMPANY'),
		'colspan' => true,
		'type' => 'custom',
		'value' => $sVal
	);
}

if (CCrmDeal::CheckCreatePermission($userPermissions, 0))
{
	$arDealVals = $arResult['ELEMENT']['DEAL'];

	if(!isset($arDealVals['PRODUCT_ROWS']))
	{
		$arProductRows = CCrmLead::LoadProductRows($arParams['ELEMENT_ID']);
		if(count($arProductRows) > 0)
		{
			$arDealVals['PRODUCT_ROWS'] = $arProductRows;
		}
	}

	$componentSettings = array(
		'ELEMENT_ID' => 0,
		'FORM_ID' => $arResult['FORM_ID'],
		'INTERNAL_FILTER' => true,
		'CONVERT' => true,
		'VALUES' => $arDealVals,
		'VARS_FROM_FORM' => $bVarsFromForm,
		'PATH_TO_PRODUCT_EDIT' => $arResult['PATH_TO_PRODUCT_EDIT'],
		'PATH_TO_PRODUCT_SHOW' => $arResult['PATH_TO_PRODUCT_SHOW'],
	);

	// load product row settings
	$productRowSettings = CCrmProductRow::LoadSettings('L', $arParams['ELEMENT_ID']);
	if (isset($productRowSettings['ENABLE_TAX']))
		$componentSettings['ENABLE_TAX'] = ((bool)$productRowSettings['ENABLE_TAX']) ? 'Y' : 'N';
	if (isset($productRowSettings['ENABLE_DISCOUNT']))
		$componentSettings['ENABLE_DISCOUNT'] = ((bool)$productRowSettings['ENABLE_DISCOUNT']) ? 'Y' : 'N';
	unset($productRowSettings);

	ob_start();
	$APPLICATION->IncludeComponent(
		'bitrix:crm.deal.edit',
		'convert',
		$componentSettings,
		false
	);
	$sVal = ob_get_contents();
	ob_end_clean();

	$arResult['FIELDS']['tab_convert'][] = array(
		'id' => 'LEAD_DEAL_CONVERT',
		'name' => GetMessage('CRM_FIELD_LEAD_DEAL'),
		'colspan' => true,
		'type' => 'custom',
		'value' => $sVal
	);
}


$this->IncludeComponentTemplate();

include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.lead/include/nav.php');

?>
