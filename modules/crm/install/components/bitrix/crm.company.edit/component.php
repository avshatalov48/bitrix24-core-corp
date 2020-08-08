<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\EntityBankDetail;

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

CModule::IncludeModule('fileman');

if (IsModuleInstalled('bizproc'))
{
	if (!CModule::IncludeModule('bizproc'))
	{
		ShowError(GetMessage('BIZPROC_MODULE_NOT_INSTALLED'));
		return;
	}
}

use Bitrix\Crm\Settings\CompanySettings;
/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $this
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @global CUserTypeManager $USER_FIELD_MANAGER
 * @global CUser $USER
 */
global $USER_FIELD_MANAGER;

$arResult['MYCOMPANY_MODE'] = (isset($arParams['MYCOMPANY_MODE']) && $arParams['MYCOMPANY_MODE'] === 'Y') ? 'Y' : 'N';
$isMyCompanyMode = ($arResult['MYCOMPANY_MODE'] === 'Y');

//region force IS_MY_COMPANY flag
$arResult['IS_MY_COMPANY'] = 'N';
if (isset($_REQUEST['mycompany']) && $_REQUEST['mycompany'] === 'y')
	$arResult['IS_MY_COMPANY'] = 'Y';
//endregion

$CCrmCompany = new CCrmCompany();
$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmCompany::$sUFEntityID, ['isMyCompany' => ($isMyCompanyMode || (isset($arResult['IS_MY_COMPANY']) && $arResult['IS_MY_COMPANY'] === 'Y'))]);
$CCrmBizProc = new CCrmBizProc('COMPANY');
$userPermissions = CCrmPerms::GetCurrentUserPermissions();
$enableOutmodedFields = CompanySettings::getCurrent()->areOutmodedRequisitesEnabled();

$arParams['PATH_TO_COMPANY_LIST'] = CrmCheckPath('PATH_TO_COMPANY_LIST', $arParams['PATH_TO_COMPANY_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
$arParams['PATH_TO_COMPANY_EDIT'] = CrmCheckPath('PATH_TO_COMPANY_EDIT', $arParams['PATH_TO_COMPANY_EDIT'], $APPLICATION->GetCurPage().'?company_id=#company_id#&edit');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);
$arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? (int)$arParams['ELEMENT_ID'] : 0;

$isEditMode = false;
$isCopyMode = false;
$bVarsFromForm = false;
if (!empty($arParams['ELEMENT_ID']))
	$isEditMode = true;
if (!empty($_REQUEST['copy']))
{
	$isCopyMode = true;
	$isEditMode = false;
}

$isConverting = isset($arParams['CONVERT']) && $arParams['CONVERT'];
//region New Conversion Scheme
/** @var \Bitrix\Crm\Conversion\EntityConversionWizard */
$conversionWizard = null;
$leadID = isset($_REQUEST['lead_id']) ? (int)$_REQUEST['lead_id'] : 0;
if($leadID > 0)
{
	$conversionWizard = \Bitrix\Crm\Conversion\LeadConversionWizard::load($leadID);
	if($conversionWizard !== null)
	{
		$arResult['LEAD_ID'] = $leadID;
	}
}
//endregion

//region external context ID
$arResult['EXTERNAL_CONTEXT'] = isset($_REQUEST['external_context']) ? $_REQUEST['external_context'] : '';
//endregion

if ($isMyCompanyMode || (isset($arResult['IS_MY_COMPANY']) && $arResult['IS_MY_COMPANY'] === 'Y'))
{
	$CrmPerms = new CCrmPerms($USER->GetID());
	if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
	{
		ShowError(GetMessage('CRM_PERMISSION_DENIED'));
		return;
	}
	unset($CrmPerms);
}

if($isEditMode)
{
	$isPermitted = CCrmCompany::CheckUpdatePermission($arParams['ELEMENT_ID'], $userPermissions);
}
elseif($isCopyMode)
{
	$isPermitted = CCrmCompany::CheckReadPermission($arParams['ELEMENT_ID'], $userPermissions);
}
else
{
	$isPermitted = CCrmCompany::CheckCreatePermission($userPermissions);
}

if(!$isPermitted)
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arEntityAttr = $arParams['ELEMENT_ID'] > 0
	? $userPermissions->GetEntityAttr('COMPANY', array($arParams['ELEMENT_ID']))
	: array();

$isInternal = false;
if (isset($arParams['INTERNAL_FILTER']) && !empty($arParams['INTERNAL_FILTER']))
	$isInternal = true;
$arResult['INTERNAL'] = $isInternal;

if ($conversionWizard !== null)
{
	$arFields = array('ID' => 0);
	if($_SERVER['REQUEST_METHOD'] === 'GET')
	{
		$conversionWizard->prepareDataForEdit(CCrmOwnerType::Company, $arFields, true);
	}
	$arResult['CONVERSION_LEGEND'] = $conversionWizard->getEditFormLegend();
}
elseif ($isEditMode || $isCopyMode)
{
	$arFilter = array(
		'ID' => $arParams['ELEMENT_ID'],
		'PERMISSION' => 'WRITE'
	);
	$obFields = CCrmCompany::GetListEx(array(), $arFilter);
	$arFields = $obFields->GetNext();

	if ($arFields === false)
	{
		$isEditMode = false;
		$isCopyMode = false;
	}

	if ($isCopyMode)
	{
		$res = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $arParams['ELEMENT_ID'])
		);
		$arResult['ELEMENT']['FM'] = array();
		while($ar = $res->Fetch())
		{
			$arFields['FM'][$ar['TYPE_ID']]['n0'.$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);
			$arFields['FM'][$ar['TYPE_ID']]['n0'.$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);
		}
		unset($arFields['LOGO']);
	}
}
else
{
	$arFields = array('ID' => 0);
	if (isset($arParams['~VALUES']) && is_array($arParams['~VALUES']))
	{
		$arFields = array_merge($arFields, $arParams['~VALUES']);
		$arFields = CCrmComponentHelper::PrepareEntityFields(
			$arFields,
			CCrmCompany::GetFields()
		);

		// hack for UF
		$_REQUEST = $_REQUEST + $arParams['~VALUES'];
	}

	if (isset($_GET['contact_id']))
	{
		$contactIDs = is_array($_GET['contact_id']) ? $_GET['contact_id'] : explode(',', $_GET['contact_id']);
		foreach($contactIDs as $contactID)
		{
			if($contactID <= 0 || !CCrmContact::CheckReadPermission($contactID, $userPermissions))
			{
				continue;
			}

			if(!isset($arResult['CONTACT_ID']))
			{
				$arResult['CONTACT_ID'] = array();
			}

			$arResult['CONTACT_ID'][] = (int)$contactID;
		}
	}
	if (isset($_GET['title']))
	{
		$arFields['~TITLE'] = $_GET['title'];
		CUtil::decodeURIComponent($arFields['~TITLE']);
		$arFields['TITLE'] = htmlspecialcharsbx($arFields['~TITLE']);
	}
	if (isset($_GET['address']))
	{
		$arFields['~ADDRESS'] = $_GET['address'];
		CUtil::decodeURIComponent($arFields['~ADDRESS']);
		$arFields['ADDRESS'] = htmlspecialcharsbx($arFields['~ADDRESS']);
	}
	if (isset($_GET['address_2']))
	{
		$arFields['~ADDRESS_2'] = $_GET['address_2'];
		CUtil::decodeURIComponent($arFields['~ADDRESS_2']);
		$arFields['ADDRESS_2'] = htmlspecialcharsbx($arFields['~ADDRESS_2']);
	}
	if (isset($_GET['address_city']))
	{
		$arFields['~ADDRESS_CITY'] = $_GET['address_city'];
		CUtil::decodeURIComponent($arFields['~ADDRESS_CITY']);
		$arFields['ADDRESS_CITY'] = htmlspecialcharsbx($arFields['~ADDRESS_CITY']);
	}
	if (isset($_GET['address_postal_code']))
	{
		$arFields['~ADDRESS_POSTAL_CODE'] = $_GET['address_postal_code'];
		CUtil::decodeURIComponent($arFields['~ADDRESS_POSTAL_CODE']);
		$arFields['ADDRESS_POSTAL_CODE'] = htmlspecialcharsbx($arFields['~ADDRESS_POSTAL_CODE']);
	}
	if (isset($_GET['address_region']))
	{
		$arFields['~ADDRESS_REGION'] = $_GET['address_region'];
		CUtil::decodeURIComponent($arFields['~ADDRESS_REGION']);
		$arFields['ADDRESS_REGION'] = htmlspecialcharsbx($arFields['~ADDRESS_REGION']);
	}
	if (isset($_GET['address_province']))
	{
		$arFields['~ADDRESS_PROVINCE'] = $_GET['address_province'];
		CUtil::decodeURIComponent($arFields['~ADDRESS_PROVINCE']);
		$arFields['ADDRESS_PROVINCE'] = htmlspecialcharsbx($arFields['~ADDRESS_PROVINCE']);
	}
	if (isset($_GET['address_country']))
	{
		$arFields['~ADDRESS_COUNTRY'] = $_GET['address_country'];
		CUtil::decodeURIComponent($arFields['~ADDRESS_COUNTRY']);
		$arFields['ADDRESS_COUNTRY'] = htmlspecialcharsbx($arFields['~ADDRESS_COUNTRY']);
	}
	if (isset($_GET['email']) || isset($_GET['phone']) || isset($_GET['tel']))
	{
		if(isset($_GET['email']))
		{
			$email = $_GET['email'];
			CUtil::decodeURIComponent($email);
			trim($email);
		}
		else
		{
			$email = '';
		}

		if(isset($_GET['phone']) || isset($_GET['tel']))
		{
			$phone = isset($_GET['phone']) ? $_GET['phone'] : $_GET['tel'];
			CUtil::decodeURIComponent($phone);
			trim($phone);
		}
		else
		{
			$phone = '';
		}

		$arFields['FM'] = array();
		if($email !== '')
		{
			$arFields['FM']['EMAIL'] = array(
				'n0' => array('VALUE' => $email, 'VALUE_TYPE' => 'WORK')
			);
		}
		if($phone !== '')
		{
			$arFields['FM']['PHONE'] = array(
				'n0' => array('VALUE' => $phone, 'VALUE_TYPE' => 'WORK'));
		}
	}
}

if ($arResult['IS_MY_COMPANY'] === 'Y' || ($isMyCompanyMode && ($isEditMode || $isCopyMode)))
	$arFields['IS_MY_COMPANY'] = 'Y';

$arResult['ELEMENT'] = $arFields;
unset($arFields);

$requisiteJsonData = array();
$requisiteFormData = array();
$bankDetailFormData = array();
$deletedRequisiteIDs = array();
$deletedBankDetailIDs = array();

$requisite = new EntityRequisite();
$bankDetail = new EntityBankDetail();

//region Request Processing
if($isConverting)
{
	$bVarsFromForm = true;
}
else
{
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
	{
		$bVarsFromForm = true;
		if(isset($_POST['cancel']))
		{
			if(isset($arResult['EXTERNAL_CONTEXT']) && $arResult['EXTERNAL_CONTEXT'] !== '')
			{
				$arResult['EXTERNAL_EVENT'] = array(
					'NAME' => 'onCrmEntityCreate',
					'IS_CANCELED' => true,
					'PARAMS' => array(
						'isCanceled' => true,
						'context' => $arResult['EXTERNAL_CONTEXT'],
						'entityTypeName' => CCrmOwnerType::CompanyName
					)
				);
				$this->IncludeComponentTemplate('event');
				return;
			}
			else
			{
				LocalRedirect(
					isset($_REQUEST['backurl']) && $_REQUEST['backurl'] !== ''
						? $_REQUEST['backurl']
						: CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_LIST'], array())
				);
			}
		}
		elseif(isset($_POST['save']) || isset($_POST['saveAndView']) || isset($_POST['saveAndAdd']) || isset($_POST['apply']) || isset($_POST['continue']))
		{
			$arFields = array('TITLE' => trim($_POST['TITLE']));

			if(isset($_POST['COMMENTS']))
			{
				$comments = isset($_POST['COMMENTS']) ? trim($_POST['COMMENTS']) : '';
				if($comments !== '' && mb_strpos($comments, '<') !== false)
				{
					$comments = \Bitrix\Crm\Format\TextHelper::sanitizeHtml($comments);
				}
				$arFields['COMMENTS'] = $comments;
			}

			$addressFieldNames = array();

			if(isset($_POST['ADDRESS']))
			{
				$arFields['ADDRESS'] = trim($_POST['ADDRESS']);
				$addressFieldNames[] = 'ADDRESS';
			}

			if(isset($_POST['ADDRESS_2']))
			{
				$arFields['ADDRESS_2'] = trim($_POST['ADDRESS_2']);
				$addressFieldNames[] = 'ADDRESS_2';
			}

			if(isset($_POST['ADDRESS_CITY']))
			{
				$arFields['ADDRESS_CITY'] = trim($_POST['ADDRESS_CITY']);
				$addressFieldNames[] = 'ADDRESS_CITY';
			}

			if(isset($_POST['ADDRESS_POSTAL_CODE']))
			{
				$arFields['ADDRESS_POSTAL_CODE'] = trim($_POST['ADDRESS_POSTAL_CODE']);
				$addressFieldNames[] = 'ADDRESS_POSTAL_CODE';
			}

			if(isset($_POST['ADDRESS_REGION']))
			{
				$arFields['ADDRESS_REGION'] = trim($_POST['ADDRESS_REGION']);
				$addressFieldNames[] = 'ADDRESS_REGION';
			}

			if(isset($_POST['ADDRESS_PROVINCE']))
			{
				$arFields['ADDRESS_PROVINCE'] = trim($_POST['ADDRESS_PROVINCE']);
				$addressFieldNames[] = 'ADDRESS_PROVINCE';
			}

			if(isset($_POST['ADDRESS_COUNTRY']))
			{
				$arFields['ADDRESS_COUNTRY'] = trim($_POST['ADDRESS_COUNTRY']);
				$addressFieldNames[] = 'ADDRESS_COUNTRY';
			}

			if(isset($_POST['ADDRESS_COUNTRY_CODE']))
			{
				$arFields['ADDRESS_COUNTRY_CODE'] = trim($_POST['ADDRESS_COUNTRY_CODE']);
				$addressFieldNames[] = 'ADDRESS_COUNTRY_CODE';
			}

			if(isset($_POST['REG_ADDRESS']))
			{
				$arFields['REG_ADDRESS'] = trim($_POST['REG_ADDRESS']);
				$addressFieldNames[] = 'REG_ADDRESS';
			}

			if(isset($_POST['REG_ADDRESS_2']))
			{
				$arFields['REG_ADDRESS_2'] = trim($_POST['REG_ADDRESS_2']);
				$addressFieldNames[] = 'REG_ADDRESS_2';
			}

			if(isset($_POST['REG_ADDRESS_CITY']))
			{
				$arFields['REG_ADDRESS_CITY'] = trim($_POST['REG_ADDRESS_CITY']);
				$addressFieldNames[] = 'REG_ADDRESS_CITY';
			}

			if(isset($_POST['REG_ADDRESS_POSTAL_CODE']))
			{
				$arFields['REG_ADDRESS_POSTAL_CODE'] = trim($_POST['REG_ADDRESS_POSTAL_CODE']);
				$addressFieldNames[] = 'REG_ADDRESS_POSTAL_CODE';
			}

			if(isset($_POST['REG_ADDRESS_REGION']))
			{
				$arFields['REG_ADDRESS_REGION'] = trim($_POST['REG_ADDRESS_REGION']);
				$addressFieldNames[] = 'REG_ADDRESS_REGION';
			}

			if(isset($_POST['REG_ADDRESS_PROVINCE']))
			{
				$arFields['REG_ADDRESS_PROVINCE'] = trim($_POST['REG_ADDRESS_PROVINCE']);
				$addressFieldNames[] = 'REG_ADDRESS_PROVINCE';
			}

			if(isset($_POST['REG_ADDRESS_COUNTRY']))
			{
				$arFields['REG_ADDRESS_COUNTRY'] = trim($_POST['REG_ADDRESS_COUNTRY']);
				$addressFieldNames[] = 'REG_ADDRESS_COUNTRY';
			}

			if(isset($_POST['REG_ADDRESS_COUNTRY_CODE']))
			{
				$arFields['REG_ADDRESS_COUNTRY_CODE'] = trim($_POST['REG_ADDRESS_COUNTRY_CODE']);
				$addressFieldNames[] = 'REG_ADDRESS_COUNTRY_CODE';
			}

			if(isset($_POST['BANKING_DETAILS']))
			{
				$arFields['BANKING_DETAILS'] = trim($_POST['BANKING_DETAILS']);
			}

			if(isset($_POST['COMPANY_TYPE']))
			{
				$arFields['COMPANY_TYPE'] = trim($_POST['COMPANY_TYPE']);
			}

			if(isset($_POST['INDUSTRY']))
			{
				$arFields['INDUSTRY'] = trim($_POST['INDUSTRY']);
			}

			if(isset($_POST['REVENUE']))
			{
				$arFields['REVENUE'] = trim($_POST['REVENUE']);
			}

			if(isset($_POST['CURRENCY_ID']))
			{
				$arFields['CURRENCY_ID'] = trim($_POST['CURRENCY_ID']);
			}

			if(isset($_POST['EMPLOYEES']))
			{
				$arFields['EMPLOYEES'] = trim($_POST['EMPLOYEES']);
			}

			if(isset($_FILES['LOGO']))
			{
				$arFields['LOGO'] = $_FILES['LOGO'];
			}

			if(isset($_POST['LOGO_del']))
			{
				$arFields['LOGO_del'] = $_POST['LOGO_del'];
			}

			if(isset($_POST['OPENED']))
			{
				$arFields['OPENED'] = mb_strtoupper($_POST['OPENED']) === 'Y' ? 'Y' : 'N';
			}
			elseif(!$isEditMode)
			{
				$arFields['OPENED'] = CompanySettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';
			}

			if ($arResult['IS_MY_COMPANY'] === 'Y')
			{
				$arFields['IS_MY_COMPANY'] = 'Y';
			}
			else if(isset($_POST['IS_MY_COMPANY']))
			{
				$arFields['IS_MY_COMPANY'] = ($_POST['IS_MY_COMPANY'] === 'Y') ? 'Y' : 'N';
			}

			if(isset($_POST['ASSIGNED_BY_ID']))
			{
				$arFields['ASSIGNED_BY_ID'] = (int)(is_array($_POST['ASSIGNED_BY_ID']) ? $_POST['ASSIGNED_BY_ID'][0] : $_POST['ASSIGNED_BY_ID']);
			}

			if(isset($_POST['COMFM']))
			{
				$arFields['FM'] = $_POST['COMFM'];
			}

			if(isset($_POST['CONTACT_ID']))
			{
				if(is_array($_POST['CONTACT_ID']))
				{
					$contactIDs = $_POST['CONTACT_ID'];
				}
				else
				{
					$contactIDs = explode(',', $_POST['CONTACT_ID']);
				}

				$currentContactIDs = isset($arResult['ELEMENT']['ID']) && $arResult['ELEMENT']['ID'] > 0
					?\Bitrix\Crm\Binding\ContactCompanyTable::getCompanyContactIDs($arResult['ELEMENT']['ID'])
					: array();

				foreach($contactIDs as $k => $v)
				{
					if(!in_array($v, $currentContactIDs) && !CCrmContact::CheckReadPermission($v, $userPermissions))
					{
						unset($contactIDs[$k]);
					}
				}
				$arFields['CONTACT_ID'] = $contactIDs;
			}

			if ($isCopyMode || intval($arResult['ELEMENT']['ID']) <= 0)
			{
				if (is_array($_POST['REQUISITE_DATA']) && is_array($_POST['REQUISITE_DATA_SIGN']))
				{
					$requisiteQty = count($_POST['REQUISITE_DATA']);
					for($index = 0; $index < $requisiteQty; $index++)
					{
						if (isset($_POST['REQUISITE_DATA_SIGN'][$index]))
						{
							$requisiteJsonData[] = array(
								'REQUISITE_ID' => 0,
								'REQUISITE_DATA' => strval($_POST['REQUISITE_DATA'][$index]),
								'REQUISITE_DATA_SIGN' => strval($_POST['REQUISITE_DATA_SIGN'][$index])
							);
						}
					}
				}
			}

			$USER_FIELD_MANAGER->EditFormAddFields(CCrmCompany::$sUFEntityID, $arFields);
			if($conversionWizard !== null)
			{
				$conversionWizard->prepareDataForSave(CCrmOwnerType::Company, $arFields);
			}
			elseif($isCopyMode)
			{
				$CCrmUserType->CopyFileFields($arFields);
			}

			$originID = isset($_REQUEST['origin_id']) ? $_REQUEST['origin_id'] : '';
			if($originID !== '')
			{
				$arFields['ORIGIN_ID'] = $originID;
			}

			if(isset($_POST['REQUISITE']) && is_array($_POST['REQUISITE']))
			{
				foreach($_POST['REQUISITE'] as $requisiteID => $requisiteForm)
				{
					$requisiteFields = \Bitrix\Crm\EntityRequisite::parseFormData($requisiteForm);
					if(is_array($requisiteFields))
					{
						$requisiteFields['ENTITY_TYPE_ID'] = CCrmOwnerType::Company;
						$requisiteFields['ENTITY_ID'] = $arResult['ELEMENT']['ID'];

						$requisiteFormData[$requisiteID] = $requisiteFields;

						if(isset($requisiteForm['DELETED']) && $requisiteForm['DELETED'] === 'Y')
						{
							$deletedRequisiteIDs[$requisiteID] = true;
						}

						// bank details
						if (is_array($requisiteForm['BANK_DETAILS']) && !empty($requisiteForm['BANK_DETAILS']))
						{
							$bankDetailFormData[$requisiteID] = array();
							$formBankDetails = array_reverse($requisiteForm['BANK_DETAILS'], true);
							foreach ($formBankDetails as $pseudoId => $bankDetailForm)
							{
								$bankDetailFields = EntityBankDetail::parseFormData($bankDetailForm);
								if (is_array($bankDetailFields) && !empty($bankDetailFields))
								{
									$bankDetailFormData[$requisiteID][$pseudoId] = $bankDetailFields;
								}

								if(isset($bankDetailForm['DELETED']) && $bankDetailForm['DELETED'] === 'Y')
								{
									$deletedBankDetailIDs[$requisiteID][$pseudoId] = true;
								}
							}
							unset($formBankDetails, $pseudoId, $bankDetailForm, $bankDetailFields);
						}
					}
				}
			}

			$arResult['ERROR_MESSAGE'] = '';
			if (!$CCrmCompany->CheckFields($arFields, $isEditMode ? $arResult['ELEMENT']['ID'] : false))
			{
				if (!empty($CCrmCompany->LAST_ERROR))
					$arResult['ERROR_MESSAGE'] .= $CCrmCompany->LAST_ERROR;
				else
					$arResult['ERROR_MESSAGE'] .= GetMessage('UNKNOWN_ERROR');
			}

			$arBizProcParametersValues = $CCrmBizProc->CheckFields(
				$isEditMode ? $arResult['ELEMENT']['ID'] : false,
				false, $arResult['ELEMENT']['ASSIGNED_BY'],
				$isEditMode ? $arEntityAttr : null
			);

			if ($arBizProcParametersValues === false)
			{
				$arResult['ERROR_MESSAGE'] .= $CCrmBizProc->LAST_ERROR;
			}

			//region Preliminary check for requisites
			if(!empty($requisiteFormData))
			{
				foreach($requisiteFormData as $requisiteID => $requisiteFields)
				{
					if(isset($deletedRequisiteIDs[$requisiteID]))
					{
						continue;
					}

					$result = $requisiteID > 0
						? $requisite->checkBeforeUpdate($requisiteID, $requisiteFields)
						: $requisite->checkBeforeAdd($requisiteFields);

					if($result !== null && !$result->isSuccess())
					{
						foreach ($result->getErrorMessages() as $errMsg)
						{
							$arResult['ERROR_MESSAGE'].= $errMsg;
						}
					}

					if (is_array($bankDetailFormData[$requisiteID]) && !empty($bankDetailFormData[$requisiteID]))
					{
						foreach($bankDetailFormData[$requisiteID] as $bankDetailID => $bankDetailFields)
						{
							if(isset($deletedBankDetailIDs[$requisiteID][$bankDetailID]))
							{
								continue;
							}

							$result = $bankDetailID > 0
								? $bankDetail->checkBeforeUpdate($bankDetailID, $bankDetailFields)
								: $bankDetail->checkBeforeAdd($bankDetailFields);

							if($result !== null && !$result->isSuccess())
							{
								foreach ($result->getErrorMessages() as $errMsg)
								{
									$arResult['ERROR_MESSAGE'].= $errMsg;
								}
							}
						}
					}
				}
			}
			//endregion

			if (empty($arResult['ERROR_MESSAGE']))
			{
				$ID = isset($arResult['ELEMENT']['ID']) ? $arResult['ELEMENT']['ID'] : 0;
				$DB->StartTransaction();
				$bSuccess = false;
				if ($isEditMode)
				{
					$bSuccess = $CCrmCompany->Update(
						$ID,
						$arFields,
						true,
						true,
						array(
							'REGISTER_SONET_EVENT' => true,
							'ADDRESS_FIELDS' => $addressFieldNames
						)
					);
				}
				else
				{
					//region Process Creation on base of lead. We need to set parent entity ID for bizproc
					if(isset($arResult['LEAD_ID']) && $arResult['LEAD_ID'] > 0)
					{
						$arFields['LEAD_ID'] = $arResult['LEAD_ID'];
					}
					//endregion

					$ID = $CCrmCompany->Add($arFields, true, array('REGISTER_SONET_EVENT' => true));
					$bSuccess = $ID !== false;
					if($bSuccess)
					{
						$arResult['ELEMENT']['ID'] = $ID;
					}
				}

				if($bSuccess)
				{
					$DB->Commit();
					if(!empty($requisiteFormData))
					{
						foreach($requisiteFormData as $requisiteID => &$requisiteFields)
						{
							//Refresh ID if entity has been created.
							if(!$isEditMode)
							{
								$requisiteFields['ENTITY_ID'] = $arResult['ELEMENT']['ID'];
							}

							$result = null;
							$operation = '';
							if(isset($deletedRequisiteIDs[$requisiteID]))
							{
								if($requisiteID > 0)
								{
									$result = $requisite->delete($requisiteID);
								}
								$operation = 'delete';
							}
							elseif($requisiteID > 0)
							{
								$result = $requisite->update($requisiteID, $requisiteFields);
								$operation = 'update';
							}
							else
							{
								$result = $requisite->add($requisiteFields);
								$operation = 'add';
							}

							// bank details
							if ($result !== null && $result->isSuccess()
								&& is_array($bankDetailFormData[$requisiteID])
								&& ($operation === 'add' || $operation === 'update' || $operation === 'delete'))
							{
								if ($operation === 'delete')
								{
									unset($bankDetailFormData[$requisiteID]);
								}
								else
								{
									foreach($bankDetailFormData[$requisiteID] as $pseudoId => &$bankDetailFields)
									{
										$bankDetailResult = null;
										if(isset($deletedBankDetailIDs[$requisiteID][$pseudoId]))
										{
											if($pseudoId > 0)
											{
												$bankDetailResult = $bankDetail->delete($pseudoId);
											}
										}
										elseif($pseudoId > 0)
										{
											$bankDetailResult = $bankDetail->update($pseudoId, $bankDetailFields);
										}
										else
										{
											$bankDetailFields['ENTITY_TYPE_ID'] = \CCrmOwnerType::Requisite;
											$bankDetailFields['ENTITY_ID'] = ($operation === 'add') ?
												$result->getId() : $requisiteID;
											$bankDetailResult = $bankDetail->add($bankDetailFields);
											if($bankDetailResult && $bankDetailResult->isSuccess())
												$bankDetailFields['ID'] = $bankDetailResult->getId();
										}

										if($bankDetailResult !== null && !$bankDetailResult->isSuccess())
										{
											$result->addErrors($bankDetailResult->getErrors());
										}
									}
									unset($bankDetailFields);
								}
							}

							if($result !== null && !$result->isSuccess())
							{
								foreach ($result->getErrorMessages() as $errMsg)
								{
									$arResult['ERROR_MESSAGE'].= $errMsg;
								}
							}
						}
						unset($requisiteFields);
					}
				}
				else
				{
					$DB->Rollback();
					$arResult['ERROR_MESSAGE'] = !empty($arFields['RESULT_MESSAGE']) ? $arFields['RESULT_MESSAGE'] : GetMessage('UNKNOWN_ERROR');
				}

				if ($bSuccess && !$isEditMode)
				{
					$companyId = (int)$ID;
					if ($companyId > 0)
					{
						// save requisites
						$requisite->addFromData(CCrmOwnerType::Company, $companyId, $requisiteJsonData);

						// remove form data after save
						$requisiteJsonData = array();
					}
					unset($companyId);
				}
			}

			if (empty($arResult['ERROR_MESSAGE'])
				&& !$CCrmBizProc->StartWorkflow($arResult['ELEMENT']['ID'], $arBizProcParametersValues))
			{
				$arResult['ERROR_MESSAGE'] = $CCrmBizProc->LAST_ERROR;
			}

			$ID = isset($arResult['ELEMENT']['ID']) ? $arResult['ELEMENT']['ID'] : 0;

			if (!empty($arResult['ERROR_MESSAGE']))
			{
				ShowError($arResult['ERROR_MESSAGE']);
				$arResult['ELEMENT'] = CCrmComponentHelper::PrepareEntityFields(
					array_merge(array('ID' => $ID), $arFields),
					CCrmCompany::GetFields()
				);
			}
			else
			{
				if (isset($_POST['apply']))
				{
					if (CCrmCompany::CheckUpdatePermission($ID))
					{
						LocalRedirect(
							CComponentEngine::MakePathFromTemplate(
								$arParams['PATH_TO_COMPANY_EDIT'],
								array('company_id' => $ID)
							)
						);
					}
				}
				elseif (isset($_POST['saveAndAdd']))
				{
					LocalRedirect(
						CComponentEngine::MakePathFromTemplate(
							$arParams['PATH_TO_COMPANY_EDIT'],
							array('company_id' => 0)
						)
					);
				}
				elseif (isset($_POST['saveAndView']))
				{
					if(CCrmCompany::CheckReadPermission($ID))
					{
						LocalRedirect(
							CComponentEngine::MakePathFromTemplate(
								$arParams['PATH_TO_COMPANY_SHOW'],
								array('company_id' => $ID)
							)
						);
					}
				}
				elseif (isset($_POST['continue']) && $conversionWizard !== null)
				{
					$conversionWizard->attachNewlyCreatedEntity(\CCrmOwnerType::CompanyName, $ID);
					$url = $conversionWizard->getRedirectUrl();
					if($url !== '')
					{
						LocalRedirect($url);
					}
				}

				//save
				if(isset($arResult['EXTERNAL_CONTEXT']) && $arResult['EXTERNAL_CONTEXT'] !== '')
				{
					$info = $arResult['INFO'] = CCrmEntitySelectorHelper::PrepareEntityInfo(
						CCrmOwnerType::CompanyName,
						$ID,
						array(
							'ENTITY_EDITOR_FORMAT' => true,
							'REQUIRE_REQUISITE_DATA' => true,
							'NAME_TEMPLATE' =>
								isset($arParams['NAME_TEMPLATE'])
									? $arParams['NAME_TEMPLATE']
									: \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
						)

					);

					$arResult['EXTERNAL_EVENT'] = array(
						'NAME' => 'onCrmEntityCreate',
						'IS_CANCELED' => false,
						'PARAMS' => array(
							'isCanceled' => false,
							'context' => $arResult['EXTERNAL_CONTEXT'],
							'entityTypeName' => CCrmOwnerType::CompanyName,
							'entityInfo' => $info
						)
					);
					$this->IncludeComponentTemplate('event');
					return;
				}
				else
				{
					LocalRedirect(
						isset($_REQUEST['backurl']) && $_REQUEST['backurl'] !== ''
							? $_REQUEST['backurl']
							: CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_LIST'], array())
					);
				}
			}
		}
	}
	else if (isset($_GET['delete']) && check_bitrix_sessid())
	{
		if ($isEditMode)
		{
			$entityID = $arParams['ELEMENT_ID'];
			$arResult['ERROR_MESSAGE'] = '';

			if (!CCrmAuthorizationHelper::CheckDeletePermission(CCrmOwnerType::CompanyName, $entityID, $userPermissions, $arEntityAttr))
			{
				$arResult['ERROR_MESSAGE'] .= GetMessage('CRM_PERMISSION_DENIED').'<br />';
			}
			elseif (!$CCrmBizProc->Delete($entityID, $arEntityAttr))
			{
				$arResult['ERROR_MESSAGE'] .= $CCrmBizProc->LAST_ERROR;
			}

			if (empty($arResult['ERROR_MESSAGE'])
				&& !$CCrmCompany->Delete($arResult['ELEMENT']['ID'], array('PROCESS_BIZPROC' => false)))
			{
				/** @var CApplicationException $ex */
				$ex = $APPLICATION->GetException();
				$arResult['ERROR_MESSAGE'] = ($ex instanceof CApplicationException)
					? $ex->GetString() : GetMessage('CRM_DELETE_ERROR');
			}

			if (empty($arResult['ERROR_MESSAGE']))
				LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_LIST']));
			else
			{
				ShowError($arResult['ERROR_MESSAGE']);
				return;
			}
		}
		else
		{
			ShowError(GetMessage('CRM_DELETE_ERROR'));
			return;
		}
	}
}
//endregion

$formId = $isMyCompanyMode ? 'CRM_MYCOMPANY_EDIT_V12' : 'CRM_COMPANY_EDIT_V12';
$arResult['FORM_ID'] = !empty($arParams['FORM_ID']) ? $arParams['FORM_ID'] : $formId;
$arResult['GRID_ID'] = $isMyCompanyMode ? 'CRM_MYCOMPANY_LIST_V12' : 'CRM_COMPANY_LIST_V12';
$arResult['BACK_URL'] = $conversionWizard !== null && $conversionWizard->hasOriginUrl()
	? $conversionWizard->getOriginUrl() : $arParams['PATH_TO_COMPANY_LIST'];

$arResult['COMPANY_TYPE_LIST'] = CCrmStatus::GetStatusList('COMPANY_TYPE');
$arResult['INDUSTRY_LIST'] = CCrmStatus::GetStatusList('INDUSTRY');
$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();
$arResult['EMPLOYEES_LIST'] = CCrmStatus::GetStatusList('EMPLOYEES');
$arResult['EDIT'] = $isEditMode;
$arResult['IS_COPY'] = $isCopyMode;
$arResult['DUPLICATE_CONTROL'] = array();
$enableDupControl = $arResult['DUPLICATE_CONTROL']['ENABLED'] =
	!$isEditMode && \Bitrix\Crm\Integrity\DuplicateControl::isControlEnabledFor(CCrmOwnerType::Company);

// Fix for #26945. Suppress binding of contacts to new compnany. Contacts will be binded to source company.
if($isEditMode)
{
	$arResult['CONTACT_ID'] = \Bitrix\Crm\Binding\ContactCompanyTable::getCompanyContactIDs($arResult['ELEMENT']['ID']);
}

$arResult['FIELDS'] = array();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_company_info',
	'name' => GetMessage('CRM_SECTION_COMPANY_INFO2'),
	'type' => 'section'
);

$titleID = $arResult['FORM_ID'].'_TITLE';
$titleCaptionID = $arResult['FORM_ID'].'_TITLE_CAP';
if($enableDupControl)
{
	$arResult['DUPLICATE_CONTROL']['TITLE_ID'] = $titleID;
	$arResult['DUPLICATE_CONTROL']['TITLE_CAPTION_ID'] = $titleCaptionID;

	$countriesInfo = Bitrix\Crm\EntityPreset::getCountriesInfo();
	
	$arResult['DUPLICATE_CONTROL']['REQUISITE_DUP_FIELDS_MAP'] = EntityRequisite::getDuplicateCriterionFieldsMap();
	$arResult['DUPLICATE_CONTROL']['REQUISITE_DUP_FIELDS_DESCR'] =
		$requisite->getDuplicateCriterionFieldsDescriptions(false);
	$requisiteDupCountriesInfo = array();
	foreach (array_keys($arResult['DUPLICATE_CONTROL']['REQUISITE_DUP_FIELDS_MAP']) as $countryId)
		$requisiteDupCountriesInfo[$countryId] = $countriesInfo[$countryId];
	$arResult['DUPLICATE_CONTROL']['REQUISITE_DUP_COUNTRIES_INFO'] = $requisiteDupCountriesInfo;

	$arResult['DUPLICATE_CONTROL']['BANK_DETAIL_DUP_FIELDS_MAP'] = EntityBankDetail::getDuplicateCriterionFieldsMap();
	$arResult['DUPLICATE_CONTROL']['BANK_DETAIL_DUP_FIELDS_DESCR'] = $bankDetail->getDuplicateCriterionFieldsDescriptions(false);
	$bankDetailDupCountriesInfo = array();
	foreach (array_keys($arResult['DUPLICATE_CONTROL']['BANK_DETAIL_DUP_FIELDS_MAP']) as $countryId)
		$bankDetailDupCountriesInfo[$countryId] = $countriesInfo[$countryId];
	$arResult['DUPLICATE_CONTROL']['BANK_DETAIL_DUP_COUNTRIES_INFO'] = $bankDetailDupCountriesInfo;
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'TITLE',
	'name' => GetMessage('CRM_FIELD_TITLE'),
	'nameWrapper' => $titleCaptionID,
	'params' => array('id'=> $titleID, 'size' => 50),
	'value' => isset($arResult['ELEMENT']['~TITLE']) ? $arResult['ELEMENT']['~TITLE'] : '',
	'type' => 'text',
	'required' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ASSIGNED_BY_ID',
	'componentParams' => array(
		'NAME' => 'crm_company_edit_resonsible',
		'INPUT_NAME' => 'ASSIGNED_BY_ID',
		'SEARCH_INPUT_NAME' => 'ASSIGNED_BY_NAME',
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
	),
	'name' => GetMessage('CRM_FIELD_ASSIGNED_BY_ID'),
	'type' => 'intranet_user_search',
	'value' => isset($arResult['ELEMENT']['~ASSIGNED_BY_ID']) ? $arResult['ELEMENT']['~ASSIGNED_BY_ID'] : $USER->GetID()
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'LOGO',
	'name' => GetMessage('CRM_FIELD_LOGO'),
	'params' => array(),
	'type' => 'file',
	'value' => isset($arResult['ELEMENT']['LOGO']) ? $arResult['ELEMENT']['LOGO'] : '',
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMPANY_TYPE',
	'name' => GetMessage('CRM_FIELD_COMPANY_TYPE'),
	'type' => 'list',
	'items' => $arResult['COMPANY_TYPE_LIST'],
	'value' => isset($arResult['ELEMENT']['COMPANY_TYPE']) ? $arResult['ELEMENT']['COMPANY_TYPE'] : ''
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'INDUSTRY',
	'name' => GetMessage('CRM_FIELD_INDUSTRY'),
	'type' => 'list',
	'items' => $arResult['INDUSTRY_LIST'],
	'value' => isset($arResult['ELEMENT']['INDUSTRY']) ? $arResult['ELEMENT']['INDUSTRY'] : ''
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'EMPLOYEES',
	'name' => GetMessage('CRM_FIELD_EMPLOYEES'),
	'type' => 'list',
	'items' => $arResult['EMPLOYEES_LIST'],
	'value' => isset($arResult['ELEMENT']['EMPLOYEES']) ? $arResult['ELEMENT']['EMPLOYEES'] : ''
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'REVENUE',
	'name' => GetMessage('CRM_FIELD_REVENUE'),
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['REVENUE']) ? $arResult['ELEMENT']['REVENUE'] : '',
	'type' => 'text',
	'required' => false
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CURRENCY_ID',
	'name' => GetMessage('CRM_FIELD_CURRENCY_ID'),
	'items' => $arResult['CURRENCY_LIST'],
	'type' => 'list',
	'value' => isset($arResult['ELEMENT']['CURRENCY_ID']) ? $arResult['ELEMENT']['CURRENCY_ID'] : ''
);
ob_start();
$ar = array(
	'inputName' => 'COMMENTS',
	'inputId' => 'COMMENTS',
	'height' => '180',
	'content' => isset($arResult['ELEMENT']['~COMMENTS']) ? $arResult['ELEMENT']['~COMMENTS'] : '',
	'bUseFileDialogs' => false,
	'bFloatingToolbar' => false,
	'bArisingToolbar' => false,
	'bResizable' => true,
	'bSaveOnBlur' => true,
	'toolbarConfig' => array(
		'Bold', 'Italic', 'Underline', 'Strike',
		'BackColor', 'ForeColor',
		'CreateLink', 'DeleteLink',
		'InsertOrderedList', 'InsertUnorderedList', 'Outdent', 'Indent'
	)
);
$LHE = new CLightHTMLEditor;
$LHE->Show($ar);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMMENTS',
	'name' => GetMessage('CRM_FIELD_COMMENTS'),
	'params' => array(),
	'type' => 'vertical_container',
	'value' => $sVal
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'OPENED',
	'name' => GetMessage('CRM_FIELD_OPENED'),
	'type' => 'vertical_checkbox',
	'params' => array(),
	'value' => isset($arResult['ELEMENT']['OPENED'])
		? $arResult['ELEMENT']['OPENED'] : (CompanySettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N'),
	'title' => GetMessage('CRM_FIELD_OPENED_TITLE')
);

$params = array();
if ($arResult['IS_MY_COMPANY'] === 'Y')
	$params['disabled'] = 'disabled';
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IS_MY_COMPANY',
	'name' => GetMessage('CRM_FIELD_IS_MY_COMPANY'),
	'type' => 'vertical_checkbox',
	'params' => $params,
	'value' => isset($arResult['ELEMENT']['IS_MY_COMPANY']) ? $arResult['ELEMENT']['IS_MY_COMPANY'] : 'N',
	'title' => GetMessage('CRM_FIELD_IS_MY_COMPANY_TITLE'),
	'visible' => false
);
unset($params);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_contact_info',
	'name' => GetMessage('CRM_SECTION_CONTACT_INFO'),
	'type' => 'section'
);

$emailEditorID = uniqid('COMFM_EMAIL_');
$emailEditorCaptionID =$emailEditorID.'_CAPTION';
if($enableDupControl)
{
	$arResult['DUPLICATE_CONTROL']['EMAIL_EDITOR_ID'] = $emailEditorID;
	$arResult['DUPLICATE_CONTROL']['EMAIL_EDITOR_CAPTION_ID'] = $emailEditorCaptionID;
}
ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.edit',
	'new',
	array(
		'FM_MNEMONIC' => 'COMFM',
		'ENTITY_ID' => 'COMPANY',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'EMAIL',
		'EDITOR_ID' => $emailEditorID,
		'VALUES' => isset($arResult['ELEMENT']['FM'])? $arResult['ELEMENT']['FM']: array(),
		'SKIP_VALUES' => array('HOME')
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'EMAIL',
	'name' => GetMessage('CRM_FIELD_EMAIL'),
	'nameWrapper' => $emailEditorCaptionID,
	'type' => 'custom',
	'value' => $sVal
);

$phoneEditorID = uniqid('COMFM_PHONE_');
$phoneEditorCaptionID =$phoneEditorID.'_CAPTION';
if($enableDupControl)
{
	$arResult['DUPLICATE_CONTROL']['PHONE_EDITOR_ID'] = $phoneEditorID;
	$arResult['DUPLICATE_CONTROL']['PHONE_EDITOR_CAPTION_ID'] = $phoneEditorCaptionID;
}
ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.edit',
	'new',
	array(
		'FM_MNEMONIC' => 'COMFM',
		'ENTITY_ID' => 'COMPANY',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'PHONE',
		'EDITOR_ID' => $phoneEditorID,
		'VALUES' => isset($arResult['ELEMENT']['FM'])? $arResult['ELEMENT']['FM']: array(),
		'SKIP_VALUES' => array('HOME')
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'PHONE',
	'name' => GetMessage('CRM_FIELD_PHONE'),
	'nameWrapper' => $phoneEditorCaptionID,
	'type' => 'custom',
	'value' => $sVal
);

ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.edit',
	'new',
	array(
		'FM_MNEMONIC' => 'COMFM',
		'ENTITY_ID' => 'COMPANY',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'WEB',
		'VALUES' => isset($arResult['ELEMENT']['FM'])? $arResult['ELEMENT']['FM']: array(),
		'SKIP_VALUES' => array('HOME')
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'WEB',
	'name' => GetMessage('CRM_FIELD_WEB'),
	'type' => 'custom',
	'value' => $sVal
);

ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.edit',
	'new',
	array(
		'FM_MNEMONIC' => 'COMFM',
		'ENTITY_ID' => 'COMPANY',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'IM',
		'VALUES' => isset($arResult['ELEMENT']['FM'])? $arResult['ELEMENT']['FM']: array()
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IM',
	'name' => GetMessage('CRM_FIELD_MESSENGER'),
	'type' => 'custom',
	'value' => $sVal
);

if($enableOutmodedFields)
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'ADDRESS',
		'name' => GetMessage('CRM_FIELD_ADDRESS'),
		'type' => 'address',
		'componentParams' => array(
			'SERVICE_URL' => '/bitrix/components/bitrix/crm.company.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
			'DATA' => array(
				'ADDRESS' => array('NAME' => 'ADDRESS', 'IS_MULTILINE' => true, 'VALUE' => isset($arResult['ELEMENT']['~ADDRESS']) ? $arResult['ELEMENT']['~ADDRESS'] : ''),
				'ADDRESS_2' => array('NAME' => 'ADDRESS_2', 'VALUE' => isset($arResult['ELEMENT']['~ADDRESS_2']) ? $arResult['ELEMENT']['~ADDRESS_2'] : ''),
				'CITY' => array('NAME' => 'ADDRESS_CITY','VALUE' => isset($arResult['ELEMENT']['~ADDRESS_CITY']) ? $arResult['ELEMENT']['~ADDRESS_CITY'] : ''),
				'REGION' => array('NAME' => 'ADDRESS_REGION','VALUE' => isset($arResult['ELEMENT']['~ADDRESS_REGION']) ? $arResult['ELEMENT']['~ADDRESS_REGION'] : ''),
				'PROVINCE' => array('NAME' => 'ADDRESS_PROVINCE', 'VALUE' => isset($arResult['ELEMENT']['~ADDRESS_PROVINCE']) ? $arResult['ELEMENT']['~ADDRESS_PROVINCE'] : ''),
				'POSTAL_CODE' => array('NAME' => 'ADDRESS_POSTAL_CODE', 'VALUE' => isset($arResult['ELEMENT']['~ADDRESS_POSTAL_CODE']) ? $arResult['ELEMENT']['~ADDRESS_POSTAL_CODE'] : ''),
				'COUNTRY' => array(
					'NAME' => 'ADDRESS_COUNTRY',
					'VALUE' => isset($arResult['ELEMENT']['~ADDRESS_COUNTRY']) ? $arResult['ELEMENT']['~ADDRESS_COUNTRY'] : '',
					'LOCALITY' => array(
						'TYPE' => 'COUNTRY',
						'NAME' => 'ADDRESS_COUNTRY_CODE',
						'VALUE' => isset($arResult['ELEMENT']['~ADDRESS_COUNTRY_CODE']) ? $arResult['ELEMENT']['~ADDRESS_COUNTRY_CODE'] : ''
					)
				)
			)
		)
	);

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'ADDRESS_LEGAL',
		'name' => GetMessage('CRM_FIELD_ADDRESS_LEGAL'),
		'type' => 'address',
		'componentParams' => array(
			'SERVICE_URL' => '/bitrix/components/bitrix/crm.company.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
			'DATA' => array(
				'ADDRESS' => array('NAME' => 'REG_ADDRESS', 'IS_MULTILINE' => true, 'VALUE' => isset($arResult['ELEMENT']['~REG_ADDRESS']) ? $arResult['ELEMENT']['~REG_ADDRESS'] : ''),
				'ADDRESS_2' => array('NAME' => 'REG_ADDRESS_2', 'VALUE' => isset($arResult['ELEMENT']['~REG_ADDRESS_2']) ? $arResult['ELEMENT']['~REG_ADDRESS_2'] : ''),
				'CITY' => array('NAME' => 'REG_ADDRESS_CITY','VALUE' => isset($arResult['ELEMENT']['~REG_ADDRESS_CITY']) ? $arResult['ELEMENT']['~REG_ADDRESS_CITY'] : ''),
				'REGION' => array('NAME' => 'REG_ADDRESS_REGION','VALUE' => isset($arResult['ELEMENT']['~REG_ADDRESS_REGION']) ? $arResult['ELEMENT']['~REG_ADDRESS_REGION'] : ''),
				'PROVINCE' => array('NAME' => 'REG_ADDRESS_PROVINCE', 'VALUE' => isset($arResult['ELEMENT']['~REG_ADDRESS_PROVINCE']) ? $arResult['ELEMENT']['~REG_ADDRESS_PROVINCE'] : ''),
				'POSTAL_CODE' => array('NAME' => 'REG_ADDRESS_POSTAL_CODE', 'VALUE' => isset($arResult['ELEMENT']['~REG_ADDRESS_POSTAL_CODE']) ? $arResult['ELEMENT']['~REG_ADDRESS_POSTAL_CODE'] : ''),
				'COUNTRY' => array(
					'NAME' => 'REG_ADDRESS_COUNTRY',
					'VALUE' => isset($arResult['ELEMENT']['~REG_ADDRESS_COUNTRY']) ? $arResult['ELEMENT']['~REG_ADDRESS_COUNTRY'] : '',
					'LOCALITY' => array(
						'TYPE' => 'COUNTRY',
						'NAME' => 'REG_ADDRESS_COUNTRY_CODE',
						'VALUE' => isset($arResult['ELEMENT']['~REG_ADDRESS_COUNTRY_CODE']) ? $arResult['ELEMENT']['~REG_ADDRESS_COUNTRY_CODE'] : ''
					)
				)
			)
		)
	);
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'BANKING_DETAILS',
	'name' => GetMessage('CRM_FIELD_BANKING_DETAILS'),
	'type' => 'textarea',
	'params' => array(),
	'value' => isset($arResult['ELEMENT']['BANKING_DETAILS']) ? $arResult['ELEMENT']['BANKING_DETAILS'] : ''
);

// bank details
// append to requisite form data
foreach ($bankDetailFormData as $requisiteId => $requisiteBankDetails)
{
	if (is_array($requisiteBankDetails) && !empty($requisiteBankDetails) && isset($requisiteFormData[$requisiteId]))
	{
		$requisiteFormData[$requisiteId]['BANK_DETAILS'] = array();
		$n = 0;
		foreach ($requisiteBankDetails as $pseudoId => $bankDetailFields)
		{
			foreach ($bankDetailFields as $fName => $fValue)
			{
				if ($fValue instanceof \Bitrix\Main\Type\DateTime)
					$bankDetailFields[$fName] = $fValue->toString();
			}
			$bankDetailId = (isset($bankDetailFields['ID']) && $bankDetailFields['ID'] > 0) ?
				(int)$bankDetailFields['ID'] : ( $pseudoId > 0 ? (int)$pseudoId : 'n'.$n++);
			if ($bankDetailId > 0 && !isset($bankDetailFields['ID']))
			{
				$bankDetailFields['ID'] = $bankDetailId;
			}
			$requisiteFormData[$requisiteId]['BANK_DETAILS'][$bankDetailId] = $bankDetailFields;
		}
	}
}

// Contacts selector
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_contacts',
	'name' => GetMessage('CRM_SECTION_CONTACTS'),
	'type' => 'section'
);
if (CCrmContact::CheckReadPermission(0, $userPermissions))
{
	if(isset($arResult['CONTACT_ID']))
	{
		$contactIDs = $arResult['CONTACT_ID'];
		if(!is_array($contactIDs))
		{
			$contactIDs = array($contactIDs);
		}
	}
	else if($arResult['ELEMENT']['ID'] > 0)
	{
		$contactIDs = \Bitrix\Crm\Binding\ContactCompanyTable::getCompanyContactIDs($arResult['ELEMENT']['ID']);
	}
	else
	{
		$contactIDs = array();
	}

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'CONTACT_ID',
		'name' => GetMessage('CRM_FIELD_CONTACT_ID'),
		'type' => 'crm_multiple_client_selector',
		'componentParams' => array(
			'CONTEXT' => $arParams['ELEMENT_ID'] > 0 ? "COMPANY_{$arParams['ELEMENT_ID']}" : 'NEWCOMPANY',
			'ENTITY_TYPE' => CCrmOwnerType::ContactName,
			'ENTITY_IDS' => $contactIDs,
			'ENTITIES_INPUT_NAME' => 'CONTACT_ID',
			'ENABLE_REQUISITES'=> false,
			'ENABLE_ENTITY_CREATION'=> CCrmContact::CheckCreatePermission($userPermissions),
			'FORM_NAME' => $arResult['FORM_ID'],
			'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
		)
	);
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_requisite',
	'name' => GetMessage('CRM_SECTION_REQUISITE'),
	'type' => 'section'
);

ob_start();
$GLOBALS['APPLICATION']->IncludeComponent('bitrix:crm.requisite.form.editor',
	'',
	array(
		'PRESET_ENTITY_TYPE_ID' => \Bitrix\Crm\EntityPreset::Requisite,
		'REQUISITE_ENTITY_TYPE_ID' => CCrmOwnerType::Company,
		'REQUISITE_ENTITY_ID' => isset($arResult['ELEMENT']['ID']) ? (int)$arResult['ELEMENT']['ID'] : 0,
		'REQUISITE_DATA' => $requisiteJsonData,
		'REQUISITE_FORM_DATA' => $requisiteFormData,
		'COPY_MODE' => $isCopyMode ? 'Y' : 'N',
		'FORM_ID' => $arResult['FORM_ID'],
		'FORM_FIELD_NAME_TEMPLATE' => 'REQUISITE[#ELEMENT_ID#][#FIELD_NAME#]'
	),
	false,
	array('HIDE_ICONS' => 'Y')
);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'REQUISITE_EDITOR',
	'name' => GetMessage('CRM_FIELD_REQUISITE_EDITOR').':',
	'params' => array(),
	'type' => 'vertical_container',
	'options' => array('nohover' => true),
	'value' => $sVal
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_additional',
	'name' => GetMessage('CRM_SECTION_ADDITIONAL'),
	'type' => 'section'
);

if($conversionWizard !== null)
{
	$useUserFieldsFromForm = true;
	$fileViewer = new \Bitrix\Crm\Conversion\EntityConversionFileViewer(
		CCrmOwnerType::Company,
		CCrmOwnerType::Lead,
		$arResult['LEAD_ID']
	);
}
else
{
	$useUserFieldsFromForm = $isConverting ? (isset($arParams['~VARS_FROM_FORM']) && $arParams['~VARS_FROM_FORM'] === true) : $bVarsFromForm;
	$fileViewer = new \Bitrix\Crm\UserField\FileViewer(CCrmOwnerType::Company, $arResult['ELEMENT']['ID']);
}

$CCrmUserType->AddFields(
	$arResult['FIELDS']['tab_1'],
	$arResult['ELEMENT']['ID'],
	$arResult['FORM_ID'],
	$useUserFieldsFromForm,
	false,
	false,
	array('FILE_VIEWER' => $fileViewer)
);

if (IsModuleInstalled('bizproc') && CBPRuntime::isFeatureEnabled())
{
	CBPDocument::AddShowParameterInit('crm', 'only_users', 'COMPANY');

	$bizProcIndex = 0;
	if (!isset($arDocumentStates))
	{
		$arDocumentStates = CBPDocument::GetDocumentStates(
			array('crm', 'CCrmDocumentCompany', 'COMPANY'),
			$isEditMode ? array('crm', 'CCrmDocumentCompany', 'COMPANY_'.$arResult['ELEMENT']['ID']) : null
		);
	}

	foreach ($arDocumentStates as $arDocumentState)
	{
		$bizProcIndex++;
		$canViewWorkflow = CBPDocument::CanUserOperateDocument(
			CBPCanUserOperateOperation::ViewWorkflow,
			$USER->GetID(),
			array('crm', 'CCrmDocumentCompany', $isEditMode ? 'COMPANY_'.$arResult['ELEMENT']['ID'] : 'COMPANY_0'),
			array(
				'UserGroups' => $CCrmBizProc->arCurrentUserGroups,
				'DocumentStates' => $arDocumentStates,
				'WorkflowId' => $arDocumentState['ID'] > 0 ? $arDocumentState['ID'] : $arDocumentState['TEMPLATE_ID'],
				'CreatedBy' => $arResult['ELEMENT']['ASSIGNED_BY'],
				'UserIsAdmin' => $USER->IsAdmin()
			)
		);

		if (!$canViewWorkflow)
			continue;

		$arResult['FIELDS']['tab_1'][] = array(
			'id' => 'section_bp_name_'.$bizProcIndex,
			'name' => $arDocumentState['TEMPLATE_NAME'],
			'type' => 'section'
		);
		if ($arDocumentState['TEMPLATE_DESCRIPTION'] != '')
		{
			$arResult['FIELDS']['tab_1'][] = array(
				'id' => 'BP_DESC_'.$bizProcIndex,
				'name' => GetMessage('CRM_FIELD_BP_TEMPLATE_DESC'),
				'type' => 'label',
				'colspan' => true,
				'value' => $arDocumentState['TEMPLATE_DESCRIPTION']
			);
		}
		if (!empty($arDocumentState['STATE_MODIFIED']))
		{
			$arResult['FIELDS']['tab_1'][] = array(
				'id' => 'BP_STATE_MODIFIED_'.$bizProcIndex,
				'name' => GetMessage('CRM_FIELD_BP_STATE_MODIFIED'),
				'type' => 'label',
				'value' => $arDocumentState['STATE_MODIFIED']
			);
		}
		if (!empty($arDocumentState['STATE_NAME']))
		{
			$arResult['FIELDS']['tab_1'][] = array(
				'id' => 'BP_STATE_NAME_'.$bizProcIndex,
				'name' => GetMessage('CRM_FIELD_BP_STATE_NAME'),
				'type' => 'label',
				'value' => $arDocumentState['STATE_TITLE'] <> '' ? $arDocumentState['STATE_TITLE'] : $arDocumentState['STATE_NAME']
			);
		}
		if ($arDocumentState['ID'] == '')
		{
			ob_start();
			CBPDocument::StartWorkflowParametersShow(
				$arDocumentState['TEMPLATE_ID'],
				$arDocumentState['TEMPLATE_PARAMETERS'],
				'form_'.$arResult['FORM_ID'],
				$bVarsFromForm
			);
			$sVal = ob_get_contents();
			ob_end_clean();
			if($sVal !== '')
			{
				$arResult['FIELDS']['tab_1'][] = array(
					'id' => 'BP_PARAMETERS',
					'name' => GetMessage('CRM_FIELD_BP_PARAMETERS'),
					'colspan' => true,
					'type' => 'custom',
					'value' => "<table>$sVal</table>"
				);
			}
		}

		$_arEvents = CBPDocument::GetAllowableEvents($USER->GetID(), $CCrmBizProc->arCurrentUserGroups, $arDocumentState);
		if (count($_arEvents) > 0)
		{
			$arEvent = array('' => GetMessage('CRM_FIELD_BP_EMPTY_EVENT'));
			foreach ($_arEvents as $_arEvent)
				$arEvent[$_arEvent['NAME']] = $_arEvent['TITLE'];

			$arResult['FIELDS']['tab_1'][] = array(
				'id' => 'BP_EVENTS',
				'name' => GetMessage('CRM_FIELD_BP_EVENTS'),
				'params' => array(),
				'items' => $arEvent,
				'type' => 'list',
				'value' => (isset($_REQUEST['bizproc_event_'.$bizProcIndex]) ? $_REQUEST['bizproc_event_'.$bizProcIndex] : '')
			);

			$arResult['FORM_CUSTOM_HTML'] = '
					<input type="hidden" name="bizproc_id_'.$bizProcIndex.'" value="'.$arDocumentState["ID"].'">
					<input type="hidden" name="bizproc_template_id_'.$bizProcIndex.'" value="'.$arDocumentState["TEMPLATE_ID"].'">
			';
		}

	}

	if ($bizProcIndex > 0)
		$arResult['BIZPROC'] = true;
}

if ($isCopyMode)
{
	$arParams['ELEMENT_ID'] = 0;
	$arFields['ID'] = 0;
	$arResult['ELEMENT']['ID'] = 0;
}

$this->IncludeComponentTemplate();

include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.company/include/nav.php');

?>
