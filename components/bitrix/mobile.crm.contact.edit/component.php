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

if (IsModuleInstalled('bizproc'))
{
	if (!CModule::IncludeModule('bizproc'))
	{
		ShowError(GetMessage('BIZPROC_MODULE_NOT_INSTALLED'));
		return;
	}
}

use Bitrix\Crm\ContactAddress;
use Bitrix\Crm\Format\AddressFormatter;

global $USER_FIELD_MANAGER, $DB, $USER;
$CCrmContact = new CCrmContact();
$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmContact::$sUFEntityID);
$userPermissions = CCrmPerms::GetCurrentUserPermissions();
$enableOutmodedFields = COption::GetOptionString('crm', '~CRM_ENABLE_CONTACT_OUTMODED_FIELDS', 'N') === 'Y';

$arParams['PATH_TO_CONTACT_LIST'] = CrmCheckPath('PATH_TO_CONTACT_LIST', $arParams['PATH_TO_CONTACT_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
$arParams['PATH_TO_CONTACT_EDIT'] = CrmCheckPath('PATH_TO_CONTACT_EDIT', $arParams['PATH_TO_CONTACT_EDIT'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&edit');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);
$arParams['REDIRECT_AFTER_SAVE'] = (!isset($arParams['REDIRECT_AFTER_SAVE']) || mb_strtoupper($arParams['REDIRECT_AFTER_SAVE']) === 'Y') ? 'Y' : 'N';

$isEditMode = false;
$isCopyMode = false;
$bVarsFromForm = false;

$entityID = $arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? intval($arParams['ELEMENT_ID']) : 0;
if($entityID <= 0 && isset($_GET['contact_id']))
{
	$entityID = $arParams['ELEMENT_ID'] = intval($_GET['contact_id']);
}
$arResult['ELEMENT_ID'] = $entityID;

if ($arParams['ELEMENT_ID'] > 0)
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
$bizCardId = isset($_REQUEST['bizcard_id']) ? (int)$_REQUEST['bizcard_id'] : 0;
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
$arResult["IS_DELETE_PERMITTED"] = CCrmContact::CheckDeletePermission($arParams['ELEMENT_ID'], $userPermissions);

if($isEditMode)
{
	$arResult["IS_EDIT_PERMITTED"] = CCrmContact::CheckUpdatePermission($arParams['ELEMENT_ID'], $userPermissions);
	if (!$arResult["IS_EDIT_PERMITTED"] && $arParams["RESTRICTED_MODE"])
	{
		$arResult["IS_VIEW_PERMITTED"] = CCrmContact::CheckReadPermission($arParams['ELEMENT_ID'], $userPermissions);
	}
}
elseif($isCopyMode)
{
	$arResult["IS_VIEW_PERMITTED"] = CCrmContact::CheckReadPermission($arParams['ELEMENT_ID'], $userPermissions);
}
else
{
	$arResult["IS_EDIT_PERMITTED"] = CCrmContact::CheckCreatePermission($userPermissions);
}

if(!$arResult["IS_EDIT_PERMITTED"] && !$arResult["IS_VIEW_PERMITTED"])
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
$arResult["ELEMENT_COMPANIES"] = array();

$arResult['ELEMENT'] = array();
if ($conversionWizard !== null)
{
	$arResult['MODE'] = 'CONVERT';

	$arFields = array('ID' => 0);
	$conversionWizard->prepareDataForEdit(CCrmOwnerType::Contact, $arFields, true);
	$arResult['CONVERSION_LEGEND'] = $conversionWizard->getEditFormLegend(CCrmOwnerType::Contact);
}
elseif ($isEditMode || $isCopyMode)
{
	$arResult['MODE'] = $arParams["RESTRICTED_MODE"] ? 'VIEW' : 'EDIT';

	$obFields = CCrmContact::GetListEx(
		array(),
		array('=ID' => $arParams['ELEMENT_ID'], 'CHECK_PERMISSIONS'=> 'N')
	);
	$arFields = is_object($obFields) ? $obFields->GetNext() : false;

	if(!is_array($arFields))
	{
		ShowError(GetMessage('CRM_CONTACT_EDIT_NOT_FOUND', array("#ID#" => $arParams['ELEMENT_ID'])));
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
	$arResult['MODE'] = 'CREATE';

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

	if($bizCardId)
	{
		if(CModule::IncludeModule("bizcard"))
		{
			$dbBizCard = Bitrix\Bizcard\Model\CardTable::getById($bizCardId);
			if($card = $dbBizCard->fetch())
			{
				$data = $card["DATA"];
				if($data["N"] && count($data["N"])>0)
				{
					$names = $data["N"][0];
					$arFields['~NAME']  = $arFields["NAME"] = $names["FIRST_NAME"];
					$arFields['~LAST_NAME']  = $arFields["LAST_NAME"] = $names["LAST_NAME"];
					$arFields["~SECOND_NAME"] = $arFields["SECOND_NAME"] = $names["SECOND_NAME"];
					$arFields["~SECOND_NAME"] = $arFields["SECOND_NAME"] = $names["SECOND_NAME"];
				}

				if($data["ADR"] && count($data["ADR"])>0)
				{
					$address = $data["ADR"][0];

					$arFields['~ADDRESS_CITY'] = $arFields['ADDRESS_CITY'] = $address["CITY"];
					$arFields['~ADDRESS_POSTAL_CODE'] = $arFields['ADDRESS_POSTAL_CODE'] = $address["POSTAL_CODE"];
					$arFields['~ADDRESS_PROVINCE']  = $arFields["ADDRESS_PROVINCE"] = $address["REGION"];
					$arFields['~ADDRESS_COUNTRY']  = $arFields["ADDRESS_COUNTRY"] = $address["COUNTRY"];
					if($address["STREET"])
					{
						$arFields['~ADDRESS']  = $arFields["ADDRESS"] = $address["STREET"];
					}
					else if($data["LABEL"])
					{

					}


				}

				if($data["TEL"] && count($data["TEL"]) > 0)
				{
					$i = 0;
					foreach($data["TEL"] as $item)
					{
						$tel = array(
							"VALUE"=>$item["VALUE"],
							"VALUE_TYPE"=>"WORK",
						);

						if($item["TYPE"] && is_array($item["TYPE"]))
						{
							$type =  $item["TYPE"][0];

							if(toUpper($type) == "CELL")
							{
								$type = "MOBILE";
							}
							else
							{
								if(count($item["TYPE"]) == 2)
								{
									$type = $item["TYPE"][1];
								}
							}

							if(in_array(toUpper($type), array("WORK", "PAGER","FAX", "HOME", "MOBILE")))
							{
								$tel["VALUE_TYPE"] = toUpper($type);
							}
						}
						$arFields["FM"]["PHONE"]["n".$i++] = $tel;
					}
				}

				if($data["EMAIL"] && count($data["EMAIL"])>0)
				{
					$i = 0;
					foreach($data["EMAIL"] as $email)
					{
						$arFields["FM"]["EMAIL"]["n".$i++] = array(
							"VALUE"=>$email,
							"VALUE_TYPE"=>"WORK",
						);
					}

				}

				if($data["URL"] && count($data["URL"])>0)
				{
					$i = 0;
					foreach($data["URL"] as $webSite)
					{
						$arFields["FM"]["WEB"]["n".$i++] = array(
							"VALUE"=>$webSite,
							"VALUE_TYPE"=>"WORK",
						);
					}

				}


				if($data["TITLE"] && count($data["TITLE"])>0)
				{
					$arFields["~POST"] = $arFields["POST"] = $data["TITLE"][0];
				}

				if($data["ORG"])
				{
					foreach($data["ORG"] as $org)
					{
						if($org["NAME"])
						{
							$arResult['ELEMENT_COMPANIES'][]= array(
								"id" => 0,
								"name" =>$org["NAME"],
								"entityType" => "company"
							);
						}
					}

				}
			}
		}
	}
	else
	{
		if (isset($_GET['company_id']))
		{
			$arFields['COMPANY_ID'] = intval($_GET['company_id']);
		}
		if (isset($_GET['honorific']))
		{
			$arFields['~HONORIFIC'] = $_GET['honorific'];
			CUtil::decodeURIComponent($arFields['~HONORIFIC']);
			$arFields['HONORIFIC'] = htmlspecialcharsbx($arFields['~HONORIFIC']);
		}
		if (isset($_GET['name']))
		{
			$arFields['~NAME'] = $_GET['name'];
			CUtil::decodeURIComponent($arFields['~NAME']);
			$arFields['NAME'] = htmlspecialcharsbx($arFields['~NAME']);
		}
		if (isset($_GET['second_name']))
		{
			$arFields['~SECOND_NAME'] = $_GET['second_name'];
			CUtil::decodeURIComponent($arFields['~SECOND_NAME']);
			$arFields['SECOND_NAME'] = htmlspecialcharsbx($arFields['~SECOND_NAME']);
		}
		if (isset($_GET['last_name']))
		{
			$arFields['~LAST_NAME'] = $_GET['last_name'];
			CUtil::decodeURIComponent($arFields['~LAST_NAME']);
			$arFields['LAST_NAME'] = htmlspecialcharsbx($arFields['~LAST_NAME']);
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

}

$arResult['ELEMENT'] = $arFields;
unset($arFields);

$requisiteJsonData = array();
$requisiteFormData = array();
$bankDetailFormData = array();
$deletedRequisiteIDs = array();
$deletedBankDetailIDs = array();

$CCrmBizProc = new CCrmBizProc('CONTACT');

//region Request Processing
if($isConverting)
{
	$bVarsFromForm = true;
}
else
{
	if($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid() && $arResult["IS_EDIT_PERMITTED"])
	{
		CUtil::JSPostUnescape();

		$bVarsFromForm = true;
		if(isset($_POST['save']) || isset($_POST['saveAndView']) || isset($_POST['saveAndAdd']) || isset($_POST['apply']) || isset($_POST['continue']))
		{
			$arFields = array(
				'NAME' => trim($_POST['NAME']),
				'LAST_NAME' => trim($_POST['LAST_NAME'])
			);

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

			$newCompanyFields = false;
			if($_POST["NEW_COMPANY_TITLE"])
			{
				$newCompanyFields= array(
					"TITLE"=>htmlspecialcharsEx($_POST["NEW_COMPANY_TITLE"]),
				);
			}

			$companyIDs = null;
			if(isset($_POST['COMPANY_IDS']))
			{
				$companyIDs = explode(',', $_POST['COMPANY_IDS']);
				$effectiveCompanyIDs = array();
				foreach ($companyIDs as $companyID)
				{
					$companyID = (int)$companyID;
					if ($companyID > 0 && CCrmCompany::CheckReadPermission($companyID, $userPermissions))
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
				$comments = \Bitrix\Crm\Format\TextHelper::sanitizeHtml($comments);

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

			$USER_FIELD_MANAGER->EditFormAddFields(CCrmContact::$sUFEntityID, $arFields, array('FORM' => $_POST));
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

			if (!$CCrmContact->CheckFields($arFields, $isEditMode ? $arResult['ELEMENT']['ID'] : false, array('DISABLE_USER_FIELD_CHECK' => true)))
			{
				if (!empty($CCrmContact->LAST_ERROR))
					$arResult['ERROR_MESSAGE'] .= $CCrmContact->LAST_ERROR;
				else
					$arResult['ERROR_MESSAGE'] .= GetMessage('UNKNOWN_ERROR');
			}

			if (($arBizProcParametersValues = $CCrmBizProc->CheckFields($isEditMode ? $arResult['ELEMENT']['ID']: false, false, $arResult['ELEMENT']['ASSIGNED_BY'], $isEditMode ? $arEntityAttr : null) === false))
				$arResult['ERROR_MESSAGE'] .= $CCrmBizProc->LAST_ERROR;

			//region Preliminary check for requisites
			if(!empty($requisiteFormData))
			{
				$requisite = new \Bitrix\Crm\EntityRequisite();
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

					$ID = $CCrmContact->Add($arFields, true, array('REGISTER_SONET_EVENT' => true, 'DISABLE_USER_FIELD_CHECK' => true));
					$success = $ID !== false;
					if($success)
					{
						if($newCompanyFields)
						{
							//Add new company for the new contact
							$newCompanyFields["CONTACT_ID"] = array($ID);
							$CCrmCompany = new CAllCrmCompany();
							$CCrmCompany->Add($newCompanyFields, true, array('REGISTER_SONET_EVENT' => true, 'DISABLE_USER_FIELD_CHECK' => true));
						}

						$arResult['ELEMENT']['ID'] = $ID;
					}
				}

				if($success)
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
				$conversionWizard->execute(array(CCrmOwnerType::ContactName => $ID));
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
	!$isEditMode && \Bitrix\Crm\Integrity\DuplicateControl::isControlEnabledFor(CCrmOwnerType::Contact);

$arResult['CONTACT_EDIT_PATH'] = CComponentEngine::MakePathFromTemplate($arParams['CONTACT_EDIT_URL_TEMPLATE'],
	array('contact_id' => $entityID)
);

/*============= fields for main.interface.form =========*/
$arResult['FIELDS'] = array();

if ($arParams["RESTRICTED_MODE"])
{
	$formattedName = htmlspecialcharsbx(CCrmContact::PrepareFormattedName(
		array(
			'HONORIFIC' => isset($arResult['ELEMENT']['~HONORIFIC']) ? $arResult['ELEMENT']['~HONORIFIC'] : '',
			'NAME' => isset($arResult['ELEMENT']['~NAME']) ? $arResult['ELEMENT']['~NAME'] : '',
			'LAST_NAME' => isset($arResult['ELEMENT']['~LAST_NAME']) ? $arResult['ELEMENT']['~LAST_NAME'] : '',
			'SECOND_NAME' => isset($arResult['ELEMENT']['~SECOND_NAME']) ? $arResult['ELEMENT']['~SECOND_NAME'] : ''
		)
	));

	$photo = CCrmMobileHelper::PrepareImageUrl($arResult['ELEMENT'], "PHOTO", 50);
	$photo = $photo ? "<div class='avatar' style='background-image:url(".$photo.")'></div>" : "<div class='avatar'></div>";

	$arResult['FIELDS'][] = array(
		'id' => 'CONTACT_NAME_PHOTO',
		'name' => GetMessage('CRM_FIELD_CONTACT'),
		'type' => 'custom',
		'value' => '<div class="mobile-grid-field-select-user-item">'.$photo.$formattedName.'</div>
					<input type="hidden" name="LAST_NAME" value="'.(isset($arResult['ELEMENT']['~LAST_NAME']) ? htmlspecialcharsbx($arResult['ELEMENT']['~LAST_NAME']) : '').'"/>
					<input type="hidden" name="NAME" value="'.(isset($arResult['ELEMENT']['~NAME']) ? htmlspecialcharsbx($arResult['ELEMENT']['~NAME']) : '').'"/>'
	);
}
else
{
	$arResult['FIELDS'][] = array(
		'id' => 'LAST_NAME',
		'name' => GetMessage('CRM_FIELD_LAST_NAME'),
		'type' => 'text',
		'value' => isset($arResult['ELEMENT']['~LAST_NAME']) ? $arResult['ELEMENT']['~LAST_NAME'] : '',
		'required' => true
	);

	$arResult['FIELDS'][] = array(
		'id' => 'NAME',
		'name' => GetMessage('CRM_FIELD_NAME'),
		'type' => 'text',
		'value' => isset($arResult['ELEMENT']['~NAME']) ? $arResult['ELEMENT']['~NAME'] : '',
		'required' => true
	);

	$arResult['FIELDS'][] = array(
		'id' => 'SECOND_NAME',
		'name' => GetMessage('CRM_FIELD_SECOND_NAME'),
		'type' => 'text',
		'value' => isset($arResult['ELEMENT']['~SECOND_NAME']) ? $arResult['ELEMENT']['~SECOND_NAME'] : '',
	);

	$arResult['FIELDS'][] = array(
		'id' => 'PHOTO',
		'name' => GetMessage('CRM_FIELD_PHOTO'),
		'params' => array(),
		'type' => 'file',
		'maxCount' => 1,
		'value' => isset($arResult['ELEMENT']['PHOTO']) ? $arResult['ELEMENT']['PHOTO'] : '',
	);
}

$arResult['HONORIFIC_LIST'] = CCrmStatus::GetStatusList('HONORIFIC');

if ($arResult["IS_EDIT_PERMITTED"])
	$value = (isset($arResult['ELEMENT']['~HONORIFIC']) ? $arResult['ELEMENT']['~HONORIFIC'] : '');
else
	$value = (isset($arResult['ELEMENT']['~HONORIFIC']) ? $arResult['HONORIFIC_LIST'][$arResult['ELEMENT']['~HONORIFIC']] : '');

$arResult['FIELDS'][] = array(
	'id' => 'HONORIFIC',
	'name' => GetMessage('CRM_FIELD_HONORIFIC'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'list' : 'label',
	'items' => array('0' => GetMessage('CRM_HONORIFIC_NOT_SELECTED')) + $arResult['HONORIFIC_LIST'],
	'value' => $value
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
$arResult['FIELDS'][] = array(
	'id' => 'BIRTHDATE',
	'name' => GetMessage('CRM_CONTACT_EDIT_FIELD_BIRTHDATE'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'date_short' : 'label',
	'value' => $birthDate
);

// multi fields
$phoneEditorID = uniqid('COMFM_PHONE_');
ob_start(); //phone
$APPLICATION->IncludeComponent($arParams["RESTRICTED_MODE"] ? 'bitrix:crm.field_multi.view' : 'bitrix:crm.field_multi.edit', 'mobile',
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
$arResult['FIELDS'][] = array(
	'id' => 'EMAIL',
	'name' => $arParams["RESTRICTED_MODE"] ? "" : GetMessage('CRM_FIELD_EMAIL'),
	'type' => 'custom',
	'value' => $sVal
);

ob_start(); //web
$APPLICATION->IncludeComponent($arParams["RESTRICTED_MODE"] ? 'bitrix:crm.field_multi.view' : 'bitrix:crm.field_multi.edit', 'mobile',
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
$arResult['FIELDS'][] = array(
	'id' => 'WEB',
	'name' => $arParams["RESTRICTED_MODE"] ? "" : GetMessage('CRM_FIELD_WEB'),
	'type' => 'custom',
	'value' => $sVal
);
//-- multifields

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
	else
	{
		$companyBindings = array();
	}

	$companyIds = \Bitrix\Crm\Binding\EntityBinding::prepareEntityIDs(CCrmOwnerType::Company, $companyBindings);

	//multi companies

	if(!empty($companyIds))
	{
		$dbCompany = CCrmCompany::GetListEx(array(), array("ID" => $companyIds), false, false, array('ID', 'TITLE', 'COMPANY_TYPE', 'LOGO'));

		while($arCompany = $dbCompany->Fetch())
		{
			$companyInfo = array(
				"id" => $arCompany["ID"],
				"name" => htmlspecialcharsbx($arCompany['TITLE']),
				"addTitle" => $arResult['COMPANY_TYPE_LIST'][$arCompany["COMPANY_TYPE"]],
				"entityType" => "company"
			);

			$photoD = isset($arCompany["LOGO"]) ? $arCompany["LOGO"] : 0;
			if($photoD > 0)
			{
				$listImageInfo = CFile::ResizeImageGet(
					$photoD, array('width' => 43, 'height' => 43), BX_RESIZE_IMAGE_PROPORTIONAL );
				$companyInfo["image"] = $listImageInfo["src"];
			}
			$companyInfo["multi"] = CCrmMobileHelper::PrepareMultiFieldsData($arCompany["ID"], CCrmOwnerType::CompanyName);

			$companyInfo['url'] = CComponentEngine::MakePathFromTemplate($arParams['COMPANY_SHOW_URL_TEMPLATE'],
				array('company_id' => $arCompany["ID"])
			);

			$arResult['ELEMENT_COMPANIES'][] = $companyInfo;
		}
	}

	$arResult["ON_SELECT_COMPANY_EVENT_NAME"] = "onCrmCompanySelectForContact_".$arParams['ELEMENT_ID'];

	$companyPath = CHTTP::urlAddParams($arParams['COMPANY_SELECTOR_URL_TEMPLATE'], array(
		"event" => $arResult["ON_SELECT_COMPANY_EVENT_NAME"]
	));

	if (!$arParams["RESTRICTED_MODE"] || !empty($arResult["ELEMENT_COMPANIES"]))
	{
		$arResult['FIELDS'][] = array(
			'id' => 'COMPANY_ID',
			'name' => GetMessage('CRM_FIELD_COMPANY_ID'),
			'type' => 'custom',
			'wrap' => true,
			'value' => '<div class="mobile-grid-field-select-user">
							<div id="mobile-crm-contact-edit-company" data-role="mobile-crm-contact-edit-company">' .
							//Company html is generated on javascript, object BX.Mobile.Crm.ClientEditor
							'</div>' . ($arParams["RESTRICTED_MODE"] ? '' : '<a class="mobile-grid-button select-user" href="javascript:void(0)" onclick="BX.Mobile.Crm.loadPageModal(\'' . CUtil::JSEscape($companyPath) . '\')">' . GetMessage("CRM_BUTTON_SELECT") . '</a>') .
						'</div>'
		);
	}
	//--multi companies
}

$arResult['FIELDS'][] = array(
	'id' => 'POST',
	'name' => GetMessage('CRM_FIELD_POST'),
	'params' => array('size' => 50),
	'type' => 'text',
	'value' => isset($arResult['ELEMENT']['~POST']) ? $arResult['ELEMENT']['~POST'] : ''
);

//address
if ($arParams["RESTRICTED_MODE"])
{
	if (class_exists('Bitrix\Crm\Format\AddressFormatter'))
	{
		$addressHtml = AddressFormatter::getSingleInstance()->formatHtmlMultiline(
			ContactAddress::mapEntityFields($arResult['ELEMENT'])
		);
	}
	else
	{
		$addressHtml =  Bitrix\Crm\Format\ContactAddressFormatter::format(
			$arResult['ELEMENT'],
			array('SEPARATOR' => Bitrix\Crm\Format\AddressSeparator::HtmlLineBreak, 'NL2BR' => true)
		);
	}
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

if ($arResult["IS_EDIT_PERMITTED"])
	$fieldType = $arParams['RESTRICTED_MODE'] ? 'custom' : 'textarea';
else
	$fieldType = 'label';

$arResult['FIELDS'][] = array(
	'id' => 'COMMENTS',
	'name' => GetMessage('CRM_FIELD_COMMENTS'),
	'params' => array(),
	'type' => $fieldType,
	'value' =>  isset($arResult['ELEMENT']['~COMMENTS']) ? htmlspecialcharsback($arResult['ELEMENT']['~COMMENTS']) : ''
);

$arResult['FIELDS'][] = array(
	'id' => 'OPENED',
	'type' => 'checkbox',
	"items" => array(
		"Y" => GetMessage('CRM_FIELD_OPENED')
	),
	'params' => $arResult["IS_EDIT_PERMITTED"] ? array() : array('disabled' => true),
	'value' => isset($arResult['ELEMENT']['OPENED'])
		? $arResult['ELEMENT']['OPENED'] : \Bitrix\Crm\Settings\ContactSettings::getCurrent()->getOpenedFlag()
);

$arResult['FIELDS'][] = array(
	'id' => 'EXPORT',
	'type' => 'checkbox',
	"items" => array(
		"Y" => GetMessage('CRM_FIELD_EXPORT_NEW')
	),
	'params' => $arResult["IS_EDIT_PERMITTED"] ? array() : array('disabled' => true),
	'value' => isset($arResult['ELEMENT']['EXPORT']) ? $arResult['ELEMENT']['EXPORT'] : 'Y'
);

$arResult['TYPE_LIST'] = CCrmStatus::GetStatusList('CONTACT_TYPE');

if ($arResult["IS_EDIT_PERMITTED"])
	$value = (isset($arResult['ELEMENT']['TYPE_ID']) ? $arResult['ELEMENT']['TYPE_ID'] : key($arResult['TYPE_LIST']));
else
	$value = (isset($arResult['ELEMENT']['TYPE_ID']) ? $arResult['TYPE_LIST'][$arResult['ELEMENT']['TYPE_ID']] : '');

$arResult['FIELDS'][] = array(
	'id' => 'TYPE_ID',
	'name' => GetMessage('CRM_FIELD_TYPE_ID'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'list' : 'label',
	'items' => $arResult['TYPE_LIST'],
	'value' => $value
);

$arResult['FIELDS'][] = array(
	'id' => 'ASSIGNED_BY_ID',
	'item' => CMobileHelper::getUserInfo(isset($arResult['ELEMENT']['ASSIGNED_BY_ID']) ? $arResult['ELEMENT']['ASSIGNED_BY_ID'] : $USER->GetID()),
	'name' => GetMessage('CRM_FIELD_ASSIGNED_BY_ID'),
	'canDrop' => false,
	'type' =>  $arResult["IS_EDIT_PERMITTED"] ? 'select-user' : 'user',
	'value' => isset($arResult['ELEMENT']['ASSIGNED_BY_ID']) ? $arResult['ELEMENT']['ASSIGNED_BY_ID'] : $USER->GetID()
);

$arResult['SOURCE_LIST'] = CCrmStatus::GetStatusList('SOURCE');

if ($arResult["IS_EDIT_PERMITTED"])
	$value = (isset($arResult['ELEMENT']['~SOURCE_ID']) ? $arResult['ELEMENT']['~SOURCE_ID'] : key($arResult['SOURCE_LIST']));
else
	$value = (isset($arResult['ELEMENT']['~SOURCE_ID']) ? $arResult['SOURCE_LIST'][$arResult['ELEMENT']['~SOURCE_ID']] : '');

$arResult['FIELDS'][] = array(
	'id' => 'SOURCE_ID',
	'name' => GetMessage('CRM_FIELD_SOURCE_ID'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'list' : 'label',
	'items' => $arResult['SOURCE_LIST'],
	'value' => $value
);
$arResult['FIELDS'][] = array(
	'id' => 'SOURCE_DESCRIPTION',
	'name' => GetMessage('CRM_FIELD_SOURCE_DESCRIPTION'),
	'type' => 'textarea',
	'params' => array(),
	'value' => isset($arResult['ELEMENT']['SOURCE_DESCRIPTION']) ? htmlspecialcharsback($arResult['ELEMENT']['SOURCE_DESCRIPTION']) : ''
);

//user fields
$CCrmUserType = new CCrmMobileHelper();
$CCrmUserType->prepareUserFields(
	$arResult['FIELDS'],
	CCrmContact::$sUFEntityID,
	$arResult['ELEMENT']['ID'],
	false,
	'contact_details',
	$USER->GetID()
);

if ($arParams['RESTRICTED_MODE'])
{
	$arResult['ACTIVITY_LIST_URL'] =  $arParams['ACTIVITY_LIST_URL_TEMPLATE'] !== ''
		? CComponentEngine::MakePathFromTemplate(
			$arParams['ACTIVITY_LIST_URL_TEMPLATE'],
			array('entity_type_id' => CCrmOwnerType::Contact, 'entity_id' => $arResult['ELEMENT_ID'])
		) : '';

	$arResult['EVENT_LIST_URL'] =  $arParams['EVENT_LIST_URL_TEMPLATE'] !== ''
		? CComponentEngine::MakePathFromTemplate(
			$arParams['EVENT_LIST_URL_TEMPLATE'],
			array('entity_type_id' => CCrmOwnerType::Contact, 'entity_id' => $arResult['ELEMENT_ID'])
		) : '';

	$arResult['DEAL_LIST_URL'] =  $arParams['DEAL_LIST_URL_TEMPLATE'] !== ''
		? CComponentEngine::MakePathFromTemplate(
			$arParams['DEAL_LIST_URL_TEMPLATE'],
			array('contact_id' => $arResult['ELEMENT_ID'])
		) : '';
}


$this->IncludeComponentTemplate();
