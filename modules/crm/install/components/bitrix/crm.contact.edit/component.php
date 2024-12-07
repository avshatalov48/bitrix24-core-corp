<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\EntityBankDetail;
use Bitrix\Crm\Integrity\DuplicateControl;
use Bitrix\Crm\Settings\ContactSettings;

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
global $USER_FIELD_MANAGER, $DB, $USER;
$CCrmContact = new CCrmContact();
$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmContact::$sUFEntityID);
$userPermissions = CCrmPerms::GetCurrentUserPermissions();
$enableOutmodedFields = ContactSettings::getCurrent()->areOutmodedRequisitesEnabled();

$arParams['PATH_TO_CONTACT_LIST'] = CrmCheckPath('PATH_TO_CONTACT_LIST', $arParams['PATH_TO_CONTACT_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
$arParams['PATH_TO_CONTACT_EDIT'] = CrmCheckPath('PATH_TO_CONTACT_EDIT', $arParams['PATH_TO_CONTACT_EDIT'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&edit');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);
$arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? (int)$arParams['ELEMENT_ID'] : 0;
$arParams['REDIRECT_AFTER_SAVE'] = (!isset($arParams['REDIRECT_AFTER_SAVE']) || mb_strtoupper($arParams['REDIRECT_AFTER_SAVE']) === 'Y') ? 'Y' : 'N';

$isNew = $arParams['ELEMENT_ID'] <= 0;
$isEditMode = false;
$isCopyMode = false;
$bVarsFromForm = false;
if ($arParams['ELEMENT_ID'] > 0)
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

if($isEditMode)
{
	$isPermitted = CCrmContact::CheckUpdatePermission($arParams['ELEMENT_ID'], $userPermissions);
}
elseif($isCopyMode)
{
	$isPermitted = CCrmContact::CheckReadPermission($arParams['ELEMENT_ID'], $userPermissions);
}
else
{
	$isPermitted = CCrmContact::CheckCreatePermission($userPermissions);
}

if(!$isPermitted)
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arEntityAttr = $arParams['ELEMENT_ID'] > 0
	? $userPermissions->GetEntityAttr('CONTACT', array($arParams['ELEMENT_ID']))
	: array();

$isInternal = false;
if (isset($arParams['INTERNAL_FILTER']) && !empty($arParams['INTERNAL_FILTER']))
	$isInternal = true;
$arResult['INTERNAL'] = $isInternal;

if ($conversionWizard !== null && $_SERVER['REQUEST_METHOD'] === 'GET')
{
	$arFields = array('ID' => 0);
	if($_SERVER['REQUEST_METHOD'] === 'GET')
	{
		$conversionWizard->prepareDataForEdit(CCrmOwnerType::Contact, $arFields, true);
	}
	$arResult['CONVERSION_LEGEND'] = $conversionWizard->getEditFormLegend();
}
elseif ($isEditMode || $isCopyMode)
{
	$obFields = CCrmContact::GetListEx(
		array(),
		array('=ID' => $arParams['ELEMENT_ID'], 'CHECK_PERMISSIONS'=> 'N')
	);
	$arFields = is_object($obFields) ? $obFields->GetNext() : false;
	if ($arFields === false)
	{
		$isEditMode = false;
		$isCopyMode = false;
	}

	if ($isCopyMode)
	{
		$res = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => $arParams['ELEMENT_ID'])
		);
		$arResult['ELEMENT']['FM'] = array();
		while($ar = $res->Fetch())
		{
			$arFields['FM'][$ar['TYPE_ID']]['n0'.$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);
			$arFields['FM'][$ar['TYPE_ID']]['n0'.$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);
		}
		unset($arFields['PHOTO']);
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
			CCrmContact::GetFields()
		);
		// hack for UF
		$_REQUEST = $_REQUEST + $arParams['~VALUES'];
	}

	if (isset($_GET['company_id']))
	{
		$companyIDs = is_array($_GET['company_id']) ? $_GET['company_id'] : explode(',', $_GET['company_id']);
		$effectiveCompanyIDs = array();
		foreach($companyIDs as $companyID)
		{
			$companyID = (int)$companyID;
			if($companyID > 0 && CCrmCompany::CheckReadPermission($companyID, $userPermissions))
			{
				$effectiveCompanyIDs[] = $companyID;
			}
		}
		$arFields['COMPANY_BINDINGS'] = \Bitrix\Crm\Binding\EntityBinding::prepareEntityBindings(
			\CCrmOwnerType::Company,
			$effectiveCompanyIDs
		);
	}
	if (isset($_GET['honorific']))
	{
		$arFields['~HONORIFIC'] = $_GET['honorific'];
		$arFields['HONORIFIC'] = htmlspecialcharsbx($arFields['~HONORIFIC']);
	}
	if (isset($_GET['name']))
	{
		$arFields['~NAME'] = $_GET['name'];
		$arFields['NAME'] = htmlspecialcharsbx($arFields['~NAME']);
	}
	if (isset($_GET['second_name']))
	{
		$arFields['~SECOND_NAME'] = $_GET['second_name'];
		$arFields['SECOND_NAME'] = htmlspecialcharsbx($arFields['~SECOND_NAME']);
	}
	if (isset($_GET['last_name']))
	{
		$arFields['~LAST_NAME'] = $_GET['last_name'];
		$arFields['LAST_NAME'] = htmlspecialcharsbx($arFields['~LAST_NAME']);
	}
	if (isset($_GET['address']))
	{
		$arFields['~ADDRESS'] = $_GET['address'];
		$arFields['ADDRESS'] = htmlspecialcharsbx($arFields['~ADDRESS']);
	}
	if (isset($_GET['address_2']))
	{
		$arFields['~ADDRESS_2'] = $_GET['address_2'];
		$arFields['ADDRESS_2'] = htmlspecialcharsbx($arFields['~ADDRESS_2']);
	}
	if (isset($_GET['address_city']))
	{
		$arFields['~ADDRESS_CITY'] = $_GET['address_city'];
		$arFields['ADDRESS_CITY'] = htmlspecialcharsbx($arFields['~ADDRESS_CITY']);
	}
	if (isset($_GET['address_postal_code']))
	{
		$arFields['~ADDRESS_POSTAL_CODE'] = $_GET['address_postal_code'];
		$arFields['ADDRESS_POSTAL_CODE'] = htmlspecialcharsbx($arFields['~ADDRESS_POSTAL_CODE']);
	}
	if (isset($_GET['address_region']))
	{
		$arFields['~ADDRESS_REGION'] = $_GET['address_region'];
		$arFields['ADDRESS_REGION'] = htmlspecialcharsbx($arFields['~ADDRESS_REGION']);
	}
	if (isset($_GET['address_province']))
	{
		$arFields['~ADDRESS_PROVINCE'] = $_GET['address_province'];
		$arFields['ADDRESS_PROVINCE'] = htmlspecialcharsbx($arFields['~ADDRESS_PROVINCE']);
	}
	if (isset($_GET['address_country']))
	{
		$arFields['~ADDRESS_COUNTRY'] = $_GET['address_country'];
		$arFields['ADDRESS_COUNTRY'] = htmlspecialcharsbx($arFields['~ADDRESS_COUNTRY']);
	}
	if (isset($_GET['email']) || isset($_GET['phone']) || isset($_GET['tel']))
	{
		if(isset($_GET['email']))
		{
			$email = trim($_GET['email']);
		}
		else
		{
			$email = '';
		}

		if(isset($_GET['phone']) || isset($_GET['tel']))
		{
			$phone = isset($_GET['phone']) ? $_GET['phone'] : $_GET['tel'];
			$phone = trim($phone);
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

$CCrmBizProc = new CCrmBizProc('CONTACT');

$requisite = new EntityRequisite();
$bankDetail = new EntityBankDetail();

//region Request Processing
if($isConverting)
{
	$bVarsFromForm = true;
}
else
{
	if($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
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
						'entityTypeName' => CCrmOwnerType::ContactName
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
						: CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_LIST'], array())
				);
			}
		}
		elseif(isset($_POST['save']) || isset($_POST['saveAndView']) || isset($_POST['saveAndAdd']) || isset($_POST['apply']) || isset($_POST['continue']))
		{
			$arFields = array();

			if(isset($_POST['NAME']))
			{
				$arFields['NAME'] = trim($_POST['NAME']);
			}

			if(isset($_POST['LAST_NAME']))
			{
				$arFields['LAST_NAME'] = trim($_POST['LAST_NAME']);
			}

			if(isset($_POST['SECOND_NAME']))
			{
				$arFields['SECOND_NAME'] = trim($_POST['SECOND_NAME']);
			}

			if(isset($_POST['HONORIFIC']))
			{
				$arFields['HONORIFIC'] = trim($_POST['HONORIFIC']);
			}

			if(isset($_POST['POST']))
			{
				$arFields['POST'] = trim($_POST['POST']);
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

			if(isset($_POST['SOURCE_DESCRIPTION']))
			{
				$arFields['SOURCE_DESCRIPTION'] = trim($_POST['SOURCE_DESCRIPTION']);
			}

			if(isset($_POST['SOURCE_ID']))
			{
				$arFields['SOURCE_ID'] = trim($_POST['SOURCE_ID']);
			}

			if(isset($_POST['TYPE_ID']))
			{
				$arFields['TYPE_ID'] = trim($_POST['TYPE_ID']);
			}

			$companyIDs = null;
			if(isset($_POST['COMPANY_IDS']))
			{
				$companyIDs = explode(',', $_POST['COMPANY_IDS']);
				$effectiveCompanyIDs = array();
				foreach($companyIDs as $companyID)
				{
					$companyID = (int)$companyID;
					if($companyID > 0 && CCrmCompany::Exists($companyID))
					{
						$effectiveCompanyIDs[] = $companyID;
					}
				}

				$arFields['COMPANY_BINDINGS'] = \Bitrix\Crm\Binding\EntityBinding::prepareEntityBindings(
					\CCrmOwnerType::Company,
					$effectiveCompanyIDs
				);
				\Bitrix\Crm\Binding\EntityBinding::markFirstAsPrimary($arFields['COMPANY_BINDINGS']);
			}

			if(isset($_POST['COMMENTS']))
			{
				$comments = trim($_POST['COMMENTS']);
				if($comments !== '' && mb_strpos($comments, '<') !== false)
				{
					$comments = \Bitrix\Crm\Format\TextHelper::sanitizeHtml($comments);
				}
				$arFields['COMMENTS'] = $comments;
			}

			if(isset($_FILES['PHOTO']))
			{
				$arFields['PHOTO'] = $_FILES['PHOTO'];
			}

			if(isset($_POST['PHOTO_del']))
			{
				$arFields['PHOTO_del'] = $_POST['PHOTO_del'];
			}

			if(isset($_POST['EXPORT']))
			{
				$arFields['EXPORT'] = isset($_POST['EXPORT']) && $_POST['EXPORT'] == 'Y' ? 'Y' : 'N';
			}
			elseif(!$isEditMode)
			{
				$arFields['EXPORT'] = 'N';
			}

			if(isset($_POST['OPENED']))
			{
				$arFields['OPENED'] = $_POST['OPENED'] == 'Y' ? 'Y' : 'N';
			}
			elseif(!$isEditMode)
			{
				$arFields['OPENED'] = \Bitrix\Crm\Settings\ContactSettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';
			}

			if(isset($_POST['ASSIGNED_BY_ID']))
			{
				$arFields['ASSIGNED_BY_ID'] = intval(is_array($_POST['ASSIGNED_BY_ID']) ? $_POST['ASSIGNED_BY_ID'][0] : $_POST['ASSIGNED_BY_ID']);
			}

			if(isset($_POST['CONFM']))
			{
				$arFields['FM'] = $_POST['CONFM'];
			}

			if(isset($_POST['BIRTHDATE']))
			{
				$arFields['BIRTHDATE'] = $_POST['BIRTHDATE'];
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

			$USER_FIELD_MANAGER->EditFormAddFields(CCrmContact::$sUFEntityID, $arFields);
			if($conversionWizard !== null)
			{
				$conversionWizard->prepareDataForSave(CCrmOwnerType::Contact, $arFields);
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
						$requisiteFields['ENTITY_TYPE_ID'] = CCrmOwnerType::Contact;
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
			if (!$CCrmContact->CheckFields($arFields, $isEditMode ? $arResult['ELEMENT']['ID'] : false))
			{
				if (!empty($CCrmContact->LAST_ERROR))
					$arResult['ERROR_MESSAGE'] .= $CCrmContact->LAST_ERROR;
				else
					$arResult['ERROR_MESSAGE'] .= GetMessage('UNKNOWN_ERROR');
			}

			$arBizProcParametersValues = $CCrmBizProc->CheckFields(
				$isEditMode ? $arResult['ELEMENT']['ID'] : false,
				false,
				$arResult['ELEMENT']['ASSIGNED_BY'],
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
							$arResult['ERROR_MESSAGE'] .= $errMsg;
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
				$DB->StartTransaction();

				$success = false;
				if ($isEditMode)
				{
					$success = $CCrmContact->Update(
						$arResult['ELEMENT']['ID'],
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

					$ID = $CCrmContact->Add($arFields, true, array('REGISTER_SONET_EVENT' => true));
					$success = $ID !== false;
					if($success)
					{
						$arResult['ELEMENT']['ID'] = $ID;
					}
				}

				if($success)
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
					CCrmContact::GetFields()
				);
			}
			else
			{
				if (isset($_POST['apply']))
				{
					if (CCrmContact::CheckUpdatePermission($ID))
					{
						LocalRedirect(
							CComponentEngine::MakePathFromTemplate(
								$arParams['PATH_TO_CONTACT_EDIT'],
								array('contact_id' => $ID)
							)
						);
					}
				}
				elseif (isset($_POST['saveAndAdd']))
				{
					$redirectUrl = CComponentEngine::MakePathFromTemplate(
						$arParams['PATH_TO_CONTACT_EDIT'],
							array('contact_id' => 0)
					);
					if(is_array($companyIDs) && !empty($companyIDs))
					{
						$redirectUrl = CCrmUrlUtil::AddUrlParams($redirectUrl, array('company_id' => $companyIDs));
					}
					LocalRedirect($redirectUrl);
				}
				elseif (isset($_POST['saveAndView']))
				{
					if(CCrmContact::CheckReadPermission($ID))
					{
						LocalRedirect(
							CComponentEngine::MakePathFromTemplate(
								$arParams['PATH_TO_CONTACT_SHOW'],
								array('contact_id' => $ID)
							)
						);
					}
				}
				elseif (isset($_POST['continue']) && $conversionWizard !== null)
				{
					$conversionWizard->attachNewlyCreatedEntity(\CCrmOwnerType::ContactName, $ID);
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
						CCrmOwnerType::ContactName,
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
							'entityTypeName' => CCrmOwnerType::ContactName,
							'entityInfo' => $info
						)
					);
					$this->IncludeComponentTemplate('event');
					return;
				}
				elseif($arParams['REDIRECT_AFTER_SAVE'] === 'Y')
				{
					LocalRedirect(
						isset($_REQUEST['backurl']) && $_REQUEST['backurl'] !== ''
							? $_REQUEST['backurl']
							: CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_LIST'], array())
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

			if (!CCrmAuthorizationHelper::CheckDeletePermission(CCrmOwnerType::ContactName, $entityID, $userPermissions, $arEntityAttr))
			{
				$arResult['ERROR_MESSAGE'] .= GetMessage('CRM_PERMISSION_DENIED').'<br />';
			}
			elseif (!$CCrmBizProc->Delete($entityID, $arEntityAttr))
			{
				$arResult['ERROR_MESSAGE'] .= $CCrmBizProc->LAST_ERROR;
			}

			if (empty($arResult['ERROR_MESSAGE'])
				&& !$CCrmContact->Delete($arResult['ELEMENT']['ID'], array('PROCESS_BIZPROC' => false)))
			{
				/** @var CApplicationException $ex */
				$ex = $APPLICATION->GetException();
				$arResult['ERROR_MESSAGE'] = ($ex instanceof CApplicationException)
					? $ex->GetString() : GetMessage('CRM_DELETE_ERROR');
			}

			if (empty($arResult['ERROR_MESSAGE']))
				LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_LIST']));
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

$arResult['FORM_ID'] = !empty($arParams['FORM_ID']) ? $arParams['FORM_ID'] : 'CRM_CONTACT_EDIT_V12';
$arResult['GRID_ID'] = 'CRM_CONTACT_LIST_V12';
$arResult['BACK_URL'] = $conversionWizard !== null && $conversionWizard->hasOriginUrl()
	? $conversionWizard->getOriginUrl() : $arParams['PATH_TO_CONTACT_LIST'];

$arResult['SOURCE_LIST'] = CCrmStatus::GetStatusList('SOURCE');
$arResult['TYPE_LIST'] = CCrmStatus::GetStatusList('CONTACT_TYPE');
$arResult['HONORIFIC_LIST'] = CCrmStatus::GetStatusList('HONORIFIC');
$arResult['EDIT'] = $isEditMode;
$arResult['IS_COPY'] = $isCopyMode;
$arResult['DUPLICATE_CONTROL'] = array();
$enableDupControl = $arResult['DUPLICATE_CONTROL']['ENABLED'] =
	DuplicateControl::isControlEnabledFor(CCrmOwnerType::Contact)
;

$arResult['FIELDS'] = array();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_contact_info',
	'name' => GetMessage('CRM_SECTION_CONTACT_INFO2'),
	'type' => 'section'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'HONORIFIC',
	'name' => GetMessage('CRM_FIELD_HONORIFIC'),
	'type' => 'list',
	'items' => array('0' => GetMessage('CRM_HONORIFIC_NOT_SELECTED')) + $arResult['HONORIFIC_LIST'],
	'value' => isset($arResult['ELEMENT']['~HONORIFIC']) ? $arResult['ELEMENT']['~HONORIFIC'] : ''
);

$lastNameID = $arResult['FORM_ID'].'_LAST_NAME';
$lastNameCaptionID = $arResult['FORM_ID'].'_LAST_NAME_CAP';
if($enableDupControl)
{
	$arResult['DUPLICATE_CONTROL']['LAST_NAME_ID'] = $lastNameID;
	$arResult['DUPLICATE_CONTROL']['LAST_NAME_CAPTION_ID'] = $lastNameCaptionID;

	$countriesInfo = Bitrix\Crm\EntityPreset::getCountriesInfo();

	$arResult['DUPLICATE_CONTROL']['REQUISITE_DUP_FIELDS_MAP'] = EntityRequisite::getDuplicateCriterionFieldsMap();
	$arResult['DUPLICATE_CONTROL']['REQUISITE_DUP_FIELDS_DESCR'] =
		$requisite->getDuplicateCriterionFieldsDescriptions(false);
	$requisiteDupCountriesInfo = array();
	foreach (array_keys($arResult['DUPLICATE_CONTROL']['REQUISITE_DUP_FIELDS_MAP']) as $countryId)
		$requisiteDupCountriesInfo[$countryId] = $countriesInfo[$countryId];
	$arResult['DUPLICATE_CONTROL']['REQUISITE_DUP_COUNTRIES_INFO'] = $requisiteDupCountriesInfo;

	$arResult['DUPLICATE_CONTROL']['BANK_DETAIL_DUP_FIELDS_MAP'] = EntityBankDetail::getDuplicateCriterionFieldsMap();
	$arResult['DUPLICATE_CONTROL']['BANK_DETAIL_DUP_FIELDS_DESCR'] =
		$bankDetail->getDuplicateCriterionFieldsDescriptions(false);
	$bankDetailDupCountriesInfo = array();
	foreach (array_keys($arResult['DUPLICATE_CONTROL']['BANK_DETAIL_DUP_FIELDS_MAP']) as $countryId)
		$bankDetailDupCountriesInfo[$countryId] = $countriesInfo[$countryId];
	$arResult['DUPLICATE_CONTROL']['BANK_DETAIL_DUP_COUNTRIES_INFO'] = $bankDetailDupCountriesInfo;
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'LAST_NAME',
	'name' => GetMessage('CRM_FIELD_LAST_NAME'),
	'nameWrapper' => $lastNameCaptionID,
	'params' => array('id' => $lastNameID, 'size' => 50),
	'type' => 'text',
	'value' => isset($arResult['ELEMENT']['~LAST_NAME']) ? $arResult['ELEMENT']['~LAST_NAME'] : '',
	'required' => true
);

$nameID = $arResult['FORM_ID'].'_NAME';
$nameCaptionID = $arResult['FORM_ID'].'_NAME_CAP';
if($enableDupControl)
{
	$arResult['DUPLICATE_CONTROL']['NAME_ID'] = $nameID;
	$arResult['DUPLICATE_CONTROL']['NAME_CAPTION_ID'] = $nameCaptionID;
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'NAME',
	'name' => GetMessage('CRM_FIELD_NAME'),
	'nameWrapper' => $nameCaptionID,
	'params' => array('id' => $nameID, 'size' => 50),
	'type' => 'text',
	'value' => isset($arResult['ELEMENT']['~NAME']) ? $arResult['ELEMENT']['~NAME'] : '',
	'required' => true
);

$secondNameID = $arResult['FORM_ID'].'_SECOND_NAME';
$secondNameCaptionID = $arResult['FORM_ID'].'_SECOND_NAME_CAP';
if($enableDupControl)
{
	$arResult['DUPLICATE_CONTROL']['SECOND_NAME_ID'] = $secondNameID;
	$arResult['DUPLICATE_CONTROL']['SECOND_NAME_CAPTION_ID'] = $secondNameCaptionID;
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SECOND_NAME',
	'name' => GetMessage('CRM_FIELD_SECOND_NAME'),
	'nameWrapper' => $secondNameCaptionID,
	'params' => array('id'=> $secondNameID, 'size' => 50),
	'type' => 'text',
	'value' => isset($arResult['ELEMENT']['~SECOND_NAME']) ? $arResult['ELEMENT']['~SECOND_NAME'] : '',
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'PHOTO',
	'name' => GetMessage('CRM_FIELD_PHOTO'),
	'params' => array(),
	'type' => 'file',
	'value' => isset($arResult['ELEMENT']['PHOTO']) ? $arResult['ELEMENT']['PHOTO'] : '',
);

$birthDate = isset($arResult['ELEMENT']['BIRTHDATE']) ? $arResult['ELEMENT']['BIRTHDATE'] : '';
if($birthDate !== '')
{
	//To preserve user value if failed to get timestamp
	$birthDateTimestamp = MakeTimeStamp($birthDate);
	if($birthDateTimestamp !== false)
	{
		$birthDate = ConvertTimeStamp($birthDateTimestamp, 'SHORT', SITE_ID);
	}
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'BIRTHDATE',
	'name' => GetMessage('CRM_CONTACT_EDIT_FIELD_BIRTHDATE'),
	'type' => 'date_short',
	'value' => $birthDate
);

$emailEditorID = uniqid('CONFM_EMAIL_');
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
		'FM_MNEMONIC' => 'CONFM',
		'ENTITY_ID' => 'CONTACT',
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
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'EMAIL',
	'name' => GetMessage('CRM_FIELD_EMAIL'),
	'nameWrapper' => $emailEditorCaptionID,
	'type' => 'custom',
	'value' => $sVal
);

$phoneEditorID = uniqid('CONFM_PHONE_');
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
		'FM_MNEMONIC' => 'CONFM',
		'ENTITY_ID' => 'CONTACT',
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
		'FM_MNEMONIC' => 'CONFM',
		'ENTITY_ID' => 'CONTACT',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'WEB',
		'VALUES' => isset($arResult['ELEMENT']['FM']) ? $arResult['ELEMENT']['FM'] : ''
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
		'FM_MNEMONIC' => 'CONFM',
		'ENTITY_ID' => 'CONTACT',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'IM',
		'VALUES' => isset($arResult['ELEMENT']['FM']) ? $arResult['ELEMENT']['FM'] : ''
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
if (CCrmCompany::CheckReadPermission(0, $userPermissions))
{
	if(isset($arResult['ELEMENT']['COMPANY_BINDINGS']))
	{
		$companyBindings = $arResult['ELEMENT']['COMPANY_BINDINGS'];
	}
	elseif($arParams['ELEMENT_ID'] > 0)
	{
		$companyBindings = \Bitrix\Crm\Binding\ContactCompanyTable::getContactBindings($arParams['ELEMENT_ID']);
	}
	elseif(isset($arResult['ELEMENT']['COMPANY_ID']))
	{
		//For backward compatibility
		$companyBindings = \Bitrix\Crm\Binding\EntityBinding::prepareEntityBindings(
			CCrmOwnerType::Company,
			array($arResult['ELEMENT']['COMPANY_ID'])
		);
	}
	else
	{
		$companyBindings = array();
	}

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'COMPANY_ID',
		'name' => GetMessage('CRM_FIELD_COMPANY_ID'),
		'type' => 'crm_multiple_client_selector',
		'componentParams' => array(
			'CONTEXT' => $arParams['ELEMENT_ID'] > 0 ? "CONTACT_{$arParams['ELEMENT_ID']}" : 'NEWCONTACT',
			'ENTITY_TYPE' => CCrmOwnerType::CompanyName,
			'ENTITY_IDS' => \Bitrix\Crm\Binding\EntityBinding::prepareEntityIDs(CCrmOwnerType::Company, $companyBindings),
			'ENTITIES_INPUT_NAME' => 'COMPANY_IDS',
			'ENABLE_REQUISITES'=> false,
			'ENABLE_ENTITY_CREATION'=> CCrmCompany::CheckCreatePermission($userPermissions),
			'FORM_NAME' => $arResult['FORM_ID'],
			'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
			'ENTITY_SELECTOR_SEARCH_OPTIONS' => array(
				'NOT_MY_COMPANIES' => 'Y'
			)
		)
	);
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'POST',
	'name' => GetMessage('CRM_FIELD_POST'),
	'params' => array('size' => 50),
	'type' => 'text',
	'value' => isset($arResult['ELEMENT']['~POST']) ? $arResult['ELEMENT']['~POST'] : ''
);

if($enableOutmodedFields)
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'ADDRESS',
		'name' => GetMessage('CRM_FIELD_ADDRESS'),
		'type' => 'address',
		'componentParams' => array(
			'SERVICE_URL' => '/bitrix/components/bitrix/crm.contact.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
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
}
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
		? $arResult['ELEMENT']['OPENED'] : (\Bitrix\Crm\Settings\ContactSettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N'),
	'title' => GetMessage('CRM_FIELD_OPENED_TITLE')
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'EXPORT',
	'name' => GetMessage('CRM_FIELD_EXPORT'),
	'type' => 'vertical_checkbox',
	'params' => array(),
	'value' => isset($arResult['ELEMENT']['EXPORT']) ? $arResult['ELEMENT']['EXPORT'] : 'Y'
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
		'REQUISITE_ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
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

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'TYPE_ID',
	'name' => GetMessage('CRM_FIELD_TYPE_ID'),
	'type' => 'list',
	'items' => $arResult['TYPE_LIST'],
	'value' => (isset($arResult['ELEMENT']['TYPE_ID']) ? $arResult['ELEMENT']['TYPE_ID'] : '')
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ASSIGNED_BY_ID',
	'componentParams' => array(
		'NAME' => 'crm_contact_edit_resonsible',
		'INPUT_NAME' => 'ASSIGNED_BY_ID',
		'SEARCH_INPUT_NAME' => 'ASSIGNED_BY_NAME',
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
	),
	'name' => GetMessage('CRM_FIELD_ASSIGNED_BY_ID'),
	'type' => 'intranet_user_search',
	'value' => isset($arResult['ELEMENT']['ASSIGNED_BY_ID']) ? $arResult['ELEMENT']['ASSIGNED_BY_ID'] : $USER->GetID()
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SOURCE_ID',
	'name' => GetMessage('CRM_FIELD_SOURCE_ID'),
	'type' => 'list',
	'items' => $arResult['SOURCE_LIST'],
	'value' => isset($arResult['ELEMENT']['~SOURCE_ID']) ? $arResult['ELEMENT']['~SOURCE_ID'] : ''
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SOURCE_DESCRIPTION',
	'name' => GetMessage('CRM_FIELD_SOURCE_DESCRIPTION'),
	'type' => 'textarea',
	'params' => array(),
	'value' => isset($arResult['ELEMENT']['SOURCE_DESCRIPTION']) ? $arResult['ELEMENT']['SOURCE_DESCRIPTION'] : ''
);

if($conversionWizard !== null)
{
	$useUserFieldsFromForm = true;
	$fileViewer = new \Bitrix\Crm\Conversion\EntityConversionFileViewer(
		CCrmOwnerType::Contact,
		CCrmOwnerType::Lead,
		$arResult['LEAD_ID']
	);
}
else
{
	$useUserFieldsFromForm = $isConverting ? (isset($arParams['~VARS_FROM_FORM']) && $arParams['~VARS_FROM_FORM'] === true) : $bVarsFromForm;
	$fileViewer = new \Bitrix\Crm\UserField\FileViewer(CCrmOwnerType::Contact, $arResult['ELEMENT']['ID']);
}

$CCrmUserType->AddFields(
	$arResult['FIELDS']['tab_1'],
	$arResult['ELEMENT']['ID'],
	$arResult['FORM_ID'],
	$useUserFieldsFromForm,
	false,
	false,
	array(
		'FILE_VIEWER' => $fileViewer,
		'DEFAULT_VALUES' => isset($arParams['~VALUES']) && is_array($arParams['~VALUES']) ? $arParams['~VALUES'] : null
	)
);

if (IsModuleInstalled('bizproc') && CBPRuntime::isFeatureEnabled())
{
	CBPDocument::AddShowParameterInit('crm', 'only_users', 'CONTACT');

	$bizProcIndex = 0;
	if (!isset($arDocumentStates))
	{
		$arDocumentStates = CBPDocument::GetDocumentStates(
			array('crm', 'CCrmDocumentContact', 'CONTACT'),
			$bEdit ? array('crm', 'CCrmDocumentContact', 'CONTACT_'.$arResult['ELEMENT']['ID']) : null
		);
	}

	foreach ($arDocumentStates as $arDocumentState)
	{
		$bizProcIndex++;
		$canViewWorkflow = CBPDocument::CanUserOperateDocument(
			CBPCanUserOperateOperation::ViewWorkflow,
			$USER->GetID(),
			array('crm', 'CCrmDocumentContact', $bEdit ? 'CONTACT_'.$arResult['ELEMENT']['ID'] : 'CONTACT_0'),
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

include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.contact/include/nav.php');

?>
