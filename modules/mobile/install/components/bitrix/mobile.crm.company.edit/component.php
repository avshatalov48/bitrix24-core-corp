<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('mobile'))
{
	ShowError(GetMessage('CRM_MOBILE_MODULE_NOT_INSTALLED'));
	return;
}

CModule::IncludeModule('fileman');
use Bitrix\Crm\Format\CompanyAddressFormatter;
use Bitrix\Crm\Format\AddressSeparator;
use Bitrix\Crm\EntityAddress;

if (IsModuleInstalled('bizproc'))
{
	if (!CModule::IncludeModule('bizproc'))
	{
		ShowError(GetMessage('BIZPROC_MODULE_NOT_INSTALLED'));
		return;
	}
}

global $USER_FIELD_MANAGER, $DB, $USER;
$CCrmCompany = new CCrmCompany();
$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmCompany::$sUFEntityID);
$CCrmBizProc = new CCrmBizProc('COMPANY');
$userPermissions = CCrmPerms::GetCurrentUserPermissions();
$enableOutmodedFields = COption::GetOptionString('crm', '~CRM_ENABLE_COMPANY_OUTMODED_FIELDS', 'N') === 'Y';

$arParams['PATH_TO_COMPANY_LIST'] = CrmCheckPath('PATH_TO_COMPANY_LIST', $arParams['PATH_TO_COMPANY_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
$arParams['PATH_TO_COMPANY_EDIT'] = CrmCheckPath('PATH_TO_COMPANY_EDIT', $arParams['PATH_TO_COMPANY_EDIT'], $APPLICATION->GetCurPage().'?company_id=#company_id#&edit');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$isEditMode = false;
$isCopyMode = false;
$bVarsFromForm = false;

$entityID = $arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? intval($arParams['ELEMENT_ID']) : 0;
if($entityID <= 0 && isset($_GET['company_id']))
{
	$entityID = $arParams['ELEMENT_ID'] = intval($_GET['company_id']);
}
$arResult['ELEMENT_ID'] = $entityID;

if (!empty($arParams['ELEMENT_ID']))
	$isEditMode = true;
if (!empty($_REQUEST['copy']))
{
	$isCopyMode = true;
	$isEditMode = false;
}

$isConverting = isset($arParams['CONVERT']) && $arParams['CONVERT'];
//region New Conversion Scheme
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

$arResult["IS_EDIT_PERMITTED"] = false;
$arResult["IS_VIEW_PERMITTED"] = false;
$arResult["IS_DELETE_PERMITTED"] = CCrmCompany::CheckDeletePermission($arParams['ELEMENT_ID'], $userPermissions);

if($isEditMode)
{
	$arResult["IS_EDIT_PERMITTED"] = CCrmCompany::CheckUpdatePermission($arParams['ELEMENT_ID'], $userPermissions);
	if (!$arResult["IS_EDIT_PERMITTED"] && $arParams["RESTRICTED_MODE"])
	{
		$arResult["IS_VIEW_PERMITTED"] = CCrmCompany::CheckReadPermission($arParams['ELEMENT_ID'], $userPermissions);
	}
}
elseif($isCopyMode)
{
	$arResult["IS_VIEW_PERMITTED"] = CCrmCompany::CheckReadPermission($arParams['ELEMENT_ID'], $userPermissions);
}
else
{
	$arResult["IS_EDIT_PERMITTED"] = CCrmCompany::CheckCreatePermission($userPermissions);
}

if(!$arResult["IS_EDIT_PERMITTED"] && !$arResult["IS_VIEW_PERMITTED"])
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

$arResult['ELEMENT'] = array();

if ($conversionWizard !== null)
{
	$arResult['MODE'] = 'CONVERT';

	$arFields = array('ID' => 0);
	$conversionWizard->prepareDataForEdit(CCrmOwnerType::Company, $arFields, true);
	$arResult['CONVERSION_LEGEND'] = $conversionWizard->getEditFormLegend(CCrmOwnerType::Company);
}
elseif ($isEditMode || $isCopyMode)
{
	$arResult['MODE'] = $arParams["RESTRICTED_MODE"] ? 'VIEW' : 'EDIT';

	$arFilter = array(
		'ID' => $arParams['ELEMENT_ID'],
		'PERMISSION' => $arParams["RESTRICTED_MODE"] ? 'READ' : 'WRITE'
	);
	$obFields = CCrmCompany::GetListEx(array(), $arFilter);
	$arFields = $obFields->GetNext();

	if(!is_array($arFields))
	{
		ShowError(GetMessage('CRM_COMPANY_EDIT_NOT_FOUND', array("#ID#" => $arParams['ELEMENT_ID'])));
		return;
	}

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
	$arResult['MODE'] = 'CREATE';

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
		$arResult['CONTACT_ID'] = array(intval($_GET['contact_id']));
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

$arResult['ELEMENT'] = $arFields;
unset($arFields);

$requisiteJsonData = array();
$requisiteFormData = array();
$bankDetailFormData = array();
$deletedRequisiteIDs = array();
$deletedBankDetailIDs = array();

//region Request Processing
if($isConverting)
{
	$bVarsFromForm = true;
}
else
{
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid() && $arResult["IS_EDIT_PERMITTED"])
	{
		CUtil::JSPostUnescape();

		$bVarsFromForm = true;
		if(isset($_POST['save']) || isset($_POST['saveAndView']) || isset($_POST['saveAndAdd']) || isset($_POST['apply']) || isset($_POST['continue']))
		{
			$arFields = array('TITLE' => trim($_POST['TITLE']));

			if(isset($_POST['COMMENTS']))
			{
				$comments = isset($_POST['COMMENTS']) ? trim($_POST['COMMENTS']) : '';
				if($comments !== '' && strpos($comments, '<') !== false)
				{
					$sanitizer = new CBXSanitizer();
					$sanitizer->ApplyDoubleEncode(false);
					$sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_MIDDLE);
					//Crutch for for Chrome line break behaviour in HTML editor.
					$sanitizer->AddTags(array('div' => array()));
					$sanitizer->AddTags(array('a' => array('href', 'title', 'name', 'style', 'alt', 'target')));
					$comments = $sanitizer->SanitizeHtml($comments);
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
				$arFields['OPENED'] = strtoupper($_POST['OPENED']) === 'Y' ? 'Y' : 'N';
			}
			elseif(!$isEditMode)
			{
				$arFields['OPENED'] = \Bitrix\Crm\Settings\CompanySettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';
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

				foreach($contactIDs as $k => $v)
				{
					if(!CCrmContact::CheckReadPermission($v))
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

			$USER_FIELD_MANAGER->EditFormAddFields(CCrmCompany::$sUFEntityID, $arFields, array('FORM' => $_POST));
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
							foreach ($requisiteForm['BANK_DETAILS'] as $pseudoId => $bankDetailForm)
							{
								$bankDetailFields = \Bitrix\Crm\EntityBankDetail::parseFormData($bankDetailForm);
								if (is_array($bankDetailFields) && !empty($bankDetailFields))
									$bankDetailFormData[$requisiteID][$pseudoId] = $bankDetailFields;

								if(isset($bankDetailForm['DELETED']) && $bankDetailForm['DELETED'] === 'Y')
								{
									$deletedBankDetailIDs[$requisiteID][$pseudoId] = true;
								}
							}
						}
					}
				}
			}

			$arResult['ERROR_MESSAGE'] = '';
			if (!$CCrmCompany->CheckFields($arFields, $isEditMode ? $arResult['ELEMENT']['ID'] : false, array('DISABLE_USER_FIELD_CHECK' => true)))
			{
				if (!empty($CCrmCompany->LAST_ERROR))
					$arResult['ERROR_MESSAGE'] .= $CCrmCompany->LAST_ERROR;
				else
					$arResult['ERROR_MESSAGE'] .= GetMessage('UNKNOWN_ERROR');
			}

			if (($arBizProcParametersValues = $CCrmBizProc->CheckFields($isEditMode ? $arResult['ELEMENT']['ID']: false, false, $arResult['ELEMENT']['ASSIGNED_BY'], $isEditMode ? $arEntityAttr : null)) === false)
				$arResult['ERROR_MESSAGE'] .= $CCrmBizProc->LAST_ERROR;

			//region Preliminary check for requisites
			if(!empty($requisiteFormData))
			{
				$requisite = new \Bitrix\Crm\EntityRequisite();
				$bankDetail = null;
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
						if ($bankDetail === null)
							$bankDetail = new \Bitrix\Crm\EntityBankDetail();

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
				unset($bankDetail);
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
							'ADDRESS_FIELDS' => $addressFieldNames,
							'DISABLE_USER_FIELD_CHECK' => true
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

					$ID = $CCrmCompany->Add($arFields, true, array('REGISTER_SONET_EVENT' => true, 'DISABLE_USER_FIELD_CHECK' => true));
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
						$requisite = new \Bitrix\Crm\EntityRequisite();
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
									$bankDetail = new \Bitrix\Crm\EntityBankDetail();
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
						$requisite = new \Bitrix\Crm\EntityRequisite();
						$requisite->addFromData(CCrmOwnerType::Company, $companyId, $requisiteJsonData);
						unset($requisite);

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

			$arJsonData = array();
			if (!empty($arResult['ERROR_MESSAGE']))
			{
				$arJsonData = array("error" => str_replace("<br>", "\n", preg_replace("/<br( )?(\/)?>/i", "\n", $arResult['ERROR_MESSAGE'])));
			}
			else
			{
				$arJsonData = array("success" => "Y", "itemId" => $ID);
			}

			if (isset($_POST['continue']) && $conversionWizard !== null)
			{
				$conversionWizard->execute(array(CCrmOwnerType::CompanyName => $ID));
				$url = $conversionWizard->getRedirectUrl();
				if($url !== '')
				{
					$arJsonData["url"] = $url;
				}
			}

			$APPLICATION->RestartBuffer();
			echo \Bitrix\Main\Web\Json::encode($arJsonData);
			CMain::FinalActions();
			die();
		}
	}
}
//endregion

$arResult['FORM_ID'] = !empty($arParams['FORM_ID']) ? $arParams['FORM_ID'] : 'CRM_COMPANY_EDIT_V12';
$arResult['GRID_ID'] = 'CRM_COMPANY_LIST_V12';
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

$arResult['COMPANY_EDIT_PATH'] = CComponentEngine::MakePathFromTemplate($arParams['COMPANY_EDIT_URL_TEMPLATE'],
	array('company_id' => $entityID)
);

/*============= fields for main.interface.form =========*/
$arResult['FIELDS'] = array();

if ($arParams["RESTRICTED_MODE"])
{
	$photo = CCrmMobileHelper::PrepareImageUrl($arResult['ELEMENT'], "LOGO", 50);
	$photo = $photo ? "<div class='avatar' style='background-image:url(".$photo.")'></div>" : "<div class='avatar'></div>";

	$arResult['FIELDS'][] = array(
		'id' => 'TITLE',
		'name' => GetMessage('CRM_FIELD_TITLE'),
		'type' => 'custom',
		'value' => '<div class="mobile-grid-field-select-user-item">'.$photo.(isset($arResult['ELEMENT']['~TITLE']) ? htmlspecialcharsbx($arResult['ELEMENT']['~TITLE']) : '').'</div>
					<input type="hidden" name="TITLE" value="'.(isset($arResult['ELEMENT']['~TITLE']) ? htmlspecialcharsbx($arResult['ELEMENT']['~TITLE']) : '').'"/>'
	);
}
else
{
	$arResult['FIELDS'][] = array(
		'id' => 'TITLE',
		'name' => GetMessage('CRM_FIELD_TITLE'),
		'value' => isset($arResult['ELEMENT']['~TITLE']) ? $arResult['ELEMENT']['~TITLE'] : '',
		'type' => $arResult["IS_EDIT_PERMITTED"] ? 'text' : 'label',
		'required' => true
	);

	$arResult['FIELDS'][] = array(
		'id' => 'LOGO',
		'name' => GetMessage('CRM_FIELD_LOGO'),
		'params' => array(),
		'type' => 'file',
		'maxCount' => 1,
		'value' => isset($arResult['ELEMENT']['LOGO']) ? $arResult['ELEMENT']['LOGO'] : '',
	);
}

$arResult['FIELDS'][] = array(
	'id' => 'ASSIGNED_BY_ID',
	'name' => GetMessage('CRM_FIELD_ASSIGNED_BY_ID'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'select-user' : 'user',
	'canDrop' => false,
	'item' => CMobileHelper::getUserInfo(isset($arResult['ELEMENT']['ASSIGNED_BY_ID']) ? $arResult['ELEMENT']['ASSIGNED_BY_ID'] : $USER->GetID()),
	'value' => isset($arResult['ELEMENT']['~ASSIGNED_BY_ID']) ? $arResult['ELEMENT']['~ASSIGNED_BY_ID'] : $USER->GetID()
);

$arResult['COMPANY_TYPE_LIST'] = CCrmStatus::GetStatusList('COMPANY_TYPE');

if ($arResult["IS_EDIT_PERMITTED"])
	$value = (isset($arResult['ELEMENT']['COMPANY_TYPE']) ? $arResult['ELEMENT']['COMPANY_TYPE'] : '');
else
	$value = (isset($arResult['ELEMENT']['COMPANY_TYPE']) ? $arResult['COMPANY_TYPE_LIST'][$arResult['ELEMENT']['COMPANY_TYPE']] : '');

$arResult['FIELDS'][] = array(
	'id' => 'COMPANY_TYPE',
	'name' => GetMessage('CRM_FIELD_COMPANY_TYPE'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'list' : 'label',
	'items' => $arResult['COMPANY_TYPE_LIST'],
	'value' => $value
);

$arResult['INDUSTRY_LIST'] = CCrmStatus::GetStatusList('INDUSTRY');

if ($arResult["IS_EDIT_PERMITTED"])
	$value = (isset($arResult['ELEMENT']['INDUSTRY']) ? $arResult['ELEMENT']['INDUSTRY'] : '');
else
	$value = (isset($arResult['ELEMENT']['INDUSTRY']) ? $arResult['INDUSTRY_LIST'][$arResult['ELEMENT']['INDUSTRY']] : '');

$arResult['FIELDS'][] = array(
	'id' => 'INDUSTRY',
	'name' => GetMessage('CRM_FIELD_INDUSTRY'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'list' : 'label',
	'items' => $arResult['INDUSTRY_LIST'],
	'value' => $value
);

$arResult['EMPLOYEES_LIST'] = CCrmStatus::GetStatusList('EMPLOYEES');

if ($arResult["IS_EDIT_PERMITTED"])
	$value = (isset($arResult['ELEMENT']['EMPLOYEES']) ? $arResult['ELEMENT']['EMPLOYEES'] : '');
else
	$value = (isset($arResult['ELEMENT']['EMPLOYEES']) ? $arResult['EMPLOYEES_LIST'][$arResult['ELEMENT']['EMPLOYEES']] : '');

$arResult['FIELDS'][] = array(
	'id' => 'EMPLOYEES',
	'name' => GetMessage('CRM_FIELD_EMPLOYEES'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'list' : 'label',
	'items' => $arResult['EMPLOYEES_LIST'],
	'value' => $value
);

$arResult['FIELDS'][] = array(
	'id' => 'REVENUE',
	'name' => GetMessage('CRM_FIELD_REVENUE'),
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['REVENUE']) ? $arResult['ELEMENT']['REVENUE'] : '',
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'text' : 'label',
	'required' => false
);

$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();

if ($arResult["IS_EDIT_PERMITTED"])
	$value = (isset($arResult['ELEMENT']['CURRENCY_ID']) ? $arResult['ELEMENT']['CURRENCY_ID'] : '');
else
	$value = (isset($arResult['ELEMENT']['CURRENCY_ID']) ? $arResult['CURRENCY_LIST'][$arResult['ELEMENT']['CURRENCY_ID']] : '');

$arResult['FIELDS'][] = array(
	'id' => 'CURRENCY_ID',
	'name' => GetMessage('CRM_FIELD_CURRENCY_ID'),
	'items' => $arResult['CURRENCY_LIST'],
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'list' : 'label',
	'value' => $value
);

if ($arResult["IS_EDIT_PERMITTED"])
	$fieldType = $arParams['RESTRICTED_MODE'] ? 'custom' : 'textarea';
else
	$fieldType = 'label';

$value = "";
if (isset($arResult['ELEMENT']['~COMMENTS']))
	$value = ($fieldType == "textarea") ? htmlspecialcharsback($arResult['ELEMENT']['~COMMENTS']) : $arResult['ELEMENT']['~COMMENTS'];
$arResult['FIELDS'][] = array(
	'id' => 'COMMENTS',
	'name' => GetMessage('CRM_FIELD_COMMENTS'),
	'params' => array(),
	'type' => $fieldType,
	'value' => $value
);

$arResult['FIELDS'][] = array(
	'id' => 'OPENED',
	'type' => 'checkbox',
	"items" => array(
		"Y" => GetMessage('CRM_FIELD_OPENED')
	),
	'params' => $arResult["IS_EDIT_PERMITTED"] ? array() : array('disabled' => true),
	'value' => isset($arResult['ELEMENT']['OPENED'])
		? $arResult['ELEMENT']['OPENED'] : \Bitrix\Crm\Settings\CompanySettings::getCurrent()->getOpenedFlag(),
);

// multi fields
$phoneEditorID = uniqid('COMFM_PHONE_');
ob_start(); //phone
$APPLICATION->IncludeComponent($arParams["RESTRICTED_MODE"] ? 'bitrix:crm.field_multi.view' : 'bitrix:crm.field_multi.edit', 'mobile',
	array(
		'FM_MNEMONIC' => 'COMFM',
		'ENTITY_ID' => 'COMPANY',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'PHONE',
		'EDITOR_ID' => $phoneEditorID,
		'VALUES' => isset($arResult['ELEMENT']['FM']) ? $arResult['ELEMENT']['FM'] : ''
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS'][] = array(
	'id' => 'PHONE',
	'name' => $arParams["RESTRICTED_MODE"] ? "" : GetMessage('CRM_FIELD_PHONE'),
	'type' => 'custom',
	'value' => $sVal
);

$emailEditorID = uniqid('COMFM_EMAIL_');
ob_start(); //email
$APPLICATION->IncludeComponent($arParams["RESTRICTED_MODE"] ? 'bitrix:crm.field_multi.view' : 'bitrix:crm.field_multi.edit', 'mobile',
	array(
		'FM_MNEMONIC' => 'COMFM',
		'ENTITY_ID' => 'COMPANY',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'EMAIL',
		'EDITOR_ID' => $emailEditorID,
		'VALUES' => isset($arResult['ELEMENT']['FM']) ? $arResult['ELEMENT']['FM'] : ''
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS'][] = array(
	'id' => 'EMAIL',
	'name' => $arParams["RESTRICTED_MODE"] ? "" : GetMessage('CRM_FIELD_EMAIL'),
	'type' => 'custom',
	'value' => $sVal
);

ob_start(); //web
$APPLICATION->IncludeComponent($arParams["RESTRICTED_MODE"] ? 'bitrix:crm.field_multi.view' : 'bitrix:crm.field_multi.edit', 'mobile',
	array(
		'FM_MNEMONIC' => 'COMFM',
		'ENTITY_ID' => 'COMPANY',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'WEB',
		'VALUES' => isset($arResult['ELEMENT']['FM']) ? $arResult['ELEMENT']['FM'] : ''
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS'][] = array(
	'id' => 'WEB',
	'name' => $arParams["RESTRICTED_MODE"] ? "" : GetMessage('CRM_FIELD_WEB'),
	'type' => 'custom',
	'value' => $sVal
);
//-- multifields

//address
if ($arParams["RESTRICTED_MODE"])
{
	$addressHtml = Bitrix\Crm\Format\CompanyAddressFormatter::format(
		$arResult['ELEMENT'],
		array('SEPARATOR' => AddressSeparator::HtmlLineBreak, 'TYPE_ID' => EntityAddress::Primary, 'NL2BR' => true)
	);
}
else
{
	$addressFields = array(
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
	);
	$addressHtml = CCrmMobileHelper::PrepareAddressFormFields($addressFields);
}
$arResult['FIELDS'][] = array(
	'id' => 'ADDRESS',
	'name' => GetMessage('CRM_FIELD_ADDRESS'),
	'type' => 'custom',
	'value' => $addressHtml
);
//--address

//address legal
if ($arParams["RESTRICTED_MODE"])
{
	$addressHtml = CompanyAddressFormatter::format(
		$arResult['ELEMENT'],
		array('SEPARATOR' => AddressSeparator::HtmlLineBreak, 'TYPE_ID' => EntityAddress::Registered, 'NL2BR' => true)
	);
}
else
{
	$addressFields = array(
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
	);
	$addressHtml = CCrmMobileHelper::PrepareAddressFormFields($addressFields);
}
$arResult['FIELDS'][] = array(
	'id' => 'ADDRESS_LEGAL',
	'name' => GetMessage('CRM_FIELD_ADDRESS_LEGAL'),
	'type' => 'custom',
	'value' => $addressHtml
);
//--address legal

$arResult['FIELDS'][] = array(
	'id' => 'BANKING_DETAILS',
	'name' => GetMessage('CRM_FIELD_BANKING_DETAILS'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'textarea': 'label',
	'params' => array(),
	'value' => isset($arResult['ELEMENT']['BANKING_DETAILS']) ? htmlspecialcharsback($arResult['ELEMENT']['BANKING_DETAILS']) : ''
);

//multi contacts
if (CCrmContact::CheckReadPermission(0, $userPermissions))
{
	$arResult["ELEMENT_CONTACTS"] = array();
	if(isset($arResult["CONTACT_ID"]) && is_array($arResult["CONTACT_ID"]) && !empty($arResult["CONTACT_ID"]))
	{
		$dbContact = CCrmContact::GetListEx(array(), array("ID" => $arResult["CONTACT_ID"]), false, false, array('HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'PHOTO'));
		while($arContact = $dbContact->Fetch())
		{
			$contact = array();

			$contactShowUrl = CComponentEngine::MakePathFromTemplate($arParams['CONTACT_SHOW_URL_TEMPLATE'],
				array('contact_id' => $arContact['ID'])
			);

			$photoD = isset($arContact["PHOTO"]) ? $arContact["PHOTO"] : 0;
			if($photoD > 0)
			{
				$listImageInfo = CFile::ResizeImageGet(
					$photoD, array('width' => 43, 'height' => 43), BX_RESIZE_IMAGE_PROPORTIONAL );
				$contact["CONTACT_PHOTO"] = $listImageInfo["src"];
			}
			$contact["CONTACT_MULTI_FIELDS"] = CCrmMobileHelper::PrepareMultiFieldsData($arContact["ID"], CCrmOwnerType::ContactName);

			$contact["FULL_NAME"] = CUser::FormatName(
				CSite::GetNameFormat(false),
				array(
					'LOGIN' => isset($arContact['LOGIN']) ? $arContact['LOGIN'] : '',
					'NAME' => isset($arContact['NAME']) ? $arContact['NAME'] : '',
					'LAST_NAME' => isset($arContact['LAST_NAME']) ? $arContact['LAST_NAME'] : '',
					'SECOND_NAME' => isset($arContact['SECOND_NAME']) ? $arContact['SECOND_NAME'] : ''
				),
				true, false
			);

			$arResult["ELEMENT_CONTACTS"][] = array(
				"id" => $arContact["ID"],
				"name" => htmlspecialcharsbx($contact["FULL_NAME"]),
				"image" => $contact["CONTACT_PHOTO"],
				"url" => $contactShowUrl,
				"multi" => $contact["CONTACT_MULTI_FIELDS"],
				'entityType' => 'contact'
			);
		}
	}

	if (!$arParams["RESTRICTED_MODE"] || !empty($arResult["ELEMENT_CONTACTS"]))
	{
		$arResult['FIELDS'][] = array(
			'id' => 'CONTACT_ID',
			'name' => GetMessage('CRM_FIELD_CONTACT_ID'),
			'type' => 'custom',
			'wrap' => true,
			'value' => '<div class="mobile-grid-field-select-user">
							<div id="mobile-crm-company-edit-contact" data-role="mobile-crm-company-edit-contact">' .
							//Contact html is generated on javascript, object BX.Mobile.Crm.ClientEditor
							'</div>' . ($arParams["RESTRICTED_MODE"] ? '' : '<a class="mobile-grid-button select-user" href="javascript:void(0)" onclick="BX.Mobile.Crm.loadPageModal(\'' . CUtil::JSEscape($arParams['CONTACT_SELECTOR_URL_TEMPLATE']) . '\')">' . GetMessage("CRM_BUTTON_SELECT") . '</a>') .
						'</div>'
		);
	}
}
//--multi contacts

//user fields
$CCrmUserType = new CCrmMobileHelper();
$CCrmUserType->PrepareUserFields(
	$arResult['FIELDS'],
	CCrmCompany::$sUFEntityID,
	$arResult['ELEMENT']['ID']
);

if ($arParams['RESTRICTED_MODE'])
{
	$arResult['ACTIVITY_LIST_URL'] =  $arParams['ACTIVITY_LIST_URL_TEMPLATE'] !== ''
		? CComponentEngine::MakePathFromTemplate(
			$arParams['ACTIVITY_LIST_URL_TEMPLATE'],
			array('entity_type_id' => CCrmOwnerType::Company, 'entity_id' => $arResult['ELEMENT_ID'])
		) : '';

	$arResult['EVENT_LIST_URL'] =  $arParams['EVENT_LIST_URL_TEMPLATE'] !== ''
		? CComponentEngine::MakePathFromTemplate(
			$arParams['EVENT_LIST_URL_TEMPLATE'],
			array('entity_type_id' => CCrmOwnerType::Company, 'entity_id' => $arResult['ELEMENT_ID'])
		) : '';

	$arResult['DEAL_LIST_URL'] =  $arParams['DEAL_LIST_URL_TEMPLATE'] !== ''
		? CComponentEngine::MakePathFromTemplate(
			$arParams['DEAL_LIST_URL_TEMPLATE'],
			array('company_id' => $arResult['ELEMENT_ID'])
		) : '';
}

$this->IncludeComponentTemplate();

