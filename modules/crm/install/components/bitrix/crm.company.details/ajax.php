<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main;
use Bitrix\Crm;

if (!CModule::IncludeModule('crm'))
{
	return;
}
/*
 * ONLY 'POST' METHOD SUPPORTED
 * SUPPORTED ACTIONS:
 * 'SAVE'
 * 'RENDER_IMAGE_INPUT'
 * 'GET_FORMATTED_SUM'
 */
global $DB, $APPLICATION;
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

if(!function_exists('__CrmCompanyDetailsEndHtmlResonse'))
{
	function __CrmCompanyDetailsEndHtmlResonse()
	{
		if(!defined('PUBLIC_AJAX_MODE'))
		{
			define('PUBLIC_AJAX_MODE', true);
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}

if(!function_exists('__CrmCompanyDetailsEndJsonResonse'))
{
	function __CrmCompanyDetailsEndJsonResonse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		if(!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}
		if(!defined('PUBLIC_AJAX_MODE'))
		{
			define('PUBLIC_AJAX_MODE', true);
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}

if (!CCrmSecurityHelper::IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	return;
}

CUtil::JSPostUnescape();
$APPLICATION->RestartBuffer();
Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$currentUserPermissions =  CCrmPerms::GetCurrentUserPermissions();

$action = isset($_POST['ACTION']) ? $_POST['ACTION'] : '';
if($action === '' && isset($_POST['MODE']))
{
	$action = $_POST['MODE'];
}
if($action === '')
{
	__CrmCompanyDetailsEndJsonResonse(array('ERROR'=>'ACTION IS NOT DEFINED!'));
}
if($action === 'SAVE')
{
	$ID = isset($_POST['ACTION_ENTITY_ID']) ? max((int)$_POST['ACTION_ENTITY_ID'], 0) : 0;
	if(($ID > 0 && !\CCrmCompany::CheckUpdatePermission($ID, $currentUserPermissions))
		|| ($ID === 0 && !\CCrmCompany::CheckCreatePermission($currentUserPermissions))
	)
	{
		__CrmCompanyDetailsEndJsonResonse(array('ERROR'=>'PERMISSION DENIED!'));
	}

	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();
	$sourceEntityID =  isset($params['COMPANY_ID']) ? (int)$params['COMPANY_ID'] : 0;

	$isNew = $ID === 0;
	$isCopyMode = $isNew && $sourceEntityID > 0;

	$fields = array();
	$fieldsInfo = \CCrmCompany::GetFieldsInfo();
	$userType = new \CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], \CCrmCompany::GetUserFieldEntityID());
	if(isset($params['IS_MY_COMPANY']) && $params['IS_MY_COMPANY'] == 'Y')
	{
		$userType->setOptions(['isMyCompany' => true]);
	}
	$userType->PrepareFieldsInfo($fieldsInfo);
	\CCrmFieldMulti::PrepareFieldsInfo($fieldsInfo);

	$presentFields = array();
	if($ID > 0)
	{
		$dbResult = CCrmCompany::GetListEx(
			array(),
			array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('*')
		);
		$presentFields = $dbResult->Fetch();
		if(!is_array($presentFields))
		{
			$presentFields = array();
		}
	}

	$sourceFields = array();
	if($sourceEntityID > 0)
	{
		$dbResult = \CCrmCompany::GetListEx(
			array(),
			array('=ID' => $sourceEntityID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('*', 'UF_*')
		);
		$sourceFields = $dbResult->Fetch();
		if(!is_array($sourceFields))
		{
			$sourceFields = array();
		}
		unset($sourceFields['PHOTO']);

		$sourceFields['FM'] = array();
		$multiFieldDbResult = \CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => CCrmOwnerType::CompanyName,
				'ELEMENT_ID' => $sourceEntityID
			)
		);

		while($multiField = $multiFieldDbResult->Fetch())
		{
			$typeID = $multiField['TYPE_ID'];
			if(!isset($sourceFields['FM'][$typeID]))
			{
				$sourceFields['FM'][$typeID] = array();
			}
			$sourceFields['FM'][$typeID][$multiField['ID']] = array(
				'VALUE' => $multiField['VALUE'],
				'VALUE_TYPE' => $multiField['VALUE_TYPE']
			);
		}
	}

	foreach($fieldsInfo as $name => $info)
	{
		if(\CCrmFieldMulti::IsSupportedType($name) && is_array($_POST[$name]))
		{
			if(!isset($fields['FM']))
			{
				$fields['FM'] = array();
			}

			$fields['FM'][$name] = $_POST[$name];
		}
		else if(isset($_POST[$name]))
		{
			$fields[$name] = $_POST[$name];

			/*
			if($name === 'CONTACT_ID')
			{
				$entityIDs = $fields[$name] !== '' ? explode(',', $fields[$name]) : array();
				$fields[$name] = !empty($entityIDs)
					? array_values(array_unique($entityIDs, SORT_NUMERIC))
					: $entityIDs;
			}
			*/
			if($name === 'LOGO')
			{
				if(!(isset($presentFields[$name]) && $presentFields[$name] == $fields[$name]))
				{
					$fileID = $fields[$name];
					$allowedFileIDs = \Bitrix\Main\UI\FileInputUtility::instance()->checkFiles(
						strtolower($name).'_uploader',
						array($fileID)
					);
					if(!in_array($fileID, $allowedFileIDs))
					{
						unset($fields[$name]);
					}
				}

				if(isset($presentFields[$name]))
				{
					$removeFlag = "{$name}_del";
					$removedFilesKey = strtolower($name).'_uploader_deleted';

					$removedFileIDs = null;
					if(isset($_POST[$removedFilesKey]) && is_array($_POST[$removedFilesKey]))
					{
						$removedFileIDs = $_POST[$removedFilesKey];
					}
					elseif(isset($_POST[$removeFlag]))
					{
						$removedFileIDs[] = array($_POST[$removeFlag]);
					}

					if(is_array($removedFileIDs) && !empty($removedFileIDs))
					{
						foreach($removedFileIDs as $fileID)
						{
							if($fileID == $presentFields[$name])
							{
								$fields[$removeFlag] = $fileID;
								break;
							}
						}
					}
				}
			}
		}
	}

	if($isNew && isset($params['IS_MY_COMPANY']) && $params['IS_MY_COMPANY'] === 'Y')
	{
		$fields['IS_MY_COMPANY'] = 'Y';
	}

	//region CLIENT
	$clientData = null;
	if(isset($_POST['CLIENT_DATA']) && $_POST['CLIENT_DATA'] !== '')
	{
		try
		{
			$clientData = Main\Web\Json::decode(
				Main\Text\Encoding::convertEncoding($_POST['CLIENT_DATA'], LANG_CHARSET, 'UTF-8')
			);
		}
		catch (Main\SystemException $e)
		{
		}
	}

	if(!is_array($clientData))
	{
		$clientData = array();
	}

	$createdEntities = array();
	$updateEntityInfos = array();

	if(isset($clientData['CONTACT_DATA']) && is_array($clientData['CONTACT_DATA']))
	{
		$contactIDs = array();
		$contactData = $clientData['CONTACT_DATA'];
		foreach($contactData as $contactItem)
		{
			$contactID = isset($contactItem['id']) ? (int)$contactItem['id'] : 0;
			if($contactID <= 0)
			{
				$contactID = \Bitrix\Crm\Component\EntityDetails\BaseComponent::createEntity(
					\CCrmOwnerType::Contact,
					$contactItem,
					array(
						'userPermissions' => $currentUserPermissions,
						'startWorkFlows' => true
					)
				);

				if($contactID > 0)
				{
					if(!isset($createdEntities[CCrmOwnerType::Contact]))
					{
						$createdEntities[CCrmOwnerType::Contact] = array();
					}
					$createdEntities[CCrmOwnerType::Contact][] = $contactID;
				}
			}
			elseif($contactItem['title'] || (isset($contactItem['multifields']) && is_array($contactItem['multifields'])))
			{
				if(!isset($updateEntityInfos[CCrmOwnerType::Contact]))
				{
					$updateEntityInfos[CCrmOwnerType::Contact] = array();
				}
				$updateEntityInfos[CCrmOwnerType::Contact][$contactID] = $contactItem;
			}

			if($contactID > 0)
			{
				$contactIDs[] = $contactID;
			}
		}

		if(!empty($contactIDs))
		{
			$contactIDs = array_unique($contactIDs);
		}

		$fields['CONTACT_ID'] = $contactIDs;
		if(!empty($fields['CONTACT_ID']))
		{
			$contactBindings = array();
			foreach($fields['CONTACT_ID'] as $contactID)
			{
				$contactBindings[] = array('ENTITY_TYPE_ID' => CCrmOwnerType::Contact, 'ENTITY_ID' => $contactID);
			}
			Crm\Controller\Entity::addLastRecentlyUsedItems(
				'crm.company.details',
				'contact',
				$contactBindings
			);
		}
	}
	//endregion

	//region Requisites
	$entityRequisites = array();
	$entityBankDetails = array();
	if(isset($_POST['REQUISITES']) && is_array($_POST['REQUISITES']))
	{
		\Bitrix\Crm\EntityRequisite::intertalizeFormData(
			$_POST['REQUISITES'],
			CCrmOwnerType::Company,
			$entityRequisites,
			$entityBankDetails
		);
	}
	//endregion

	$conversionWizard = null;
	if(isset($params['LEAD_ID']) && $params['LEAD_ID'] > 0)
	{
		$leadID = (int)$params['LEAD_ID'];
		$fields['LEAD_ID'] = $leadID;
		$conversionWizard = \Bitrix\Crm\Conversion\LeadConversionWizard::load($leadID);
	}

	if($conversionWizard !== null)
	{
		$conversionWizard->setSliderEnabled(true);
		$conversionWizard->prepareDataForSave(CCrmOwnerType::Company, $fields);
	}

	$errorMessage = '';
	if(!empty($fields) || !empty($updateEntityInfos) || !empty($entityRequisites) || !empty($entityBankDetails))
	{
		if(!empty($fields))
		{
			if(isset($fields['ASSIGNED_BY_ID']) && $fields['ASSIGNED_BY_ID'] > 0)
			{
				\Bitrix\Crm\Entity\EntityEditor::registerSelectedUser($fields['ASSIGNED_BY_ID']);
			}

			if($isCopyMode)
			{
				if(!isset($fields['ASSIGNED_BY_ID']))
				{
					$fields['ASSIGNED_BY_ID'] = $currentUserID;
				}

				\Bitrix\Crm\Entity\EntityEditor::prepareForCopy($fields, $userType);
				$merger = new \Bitrix\Crm\Merger\CompanyMerger($currentUserID, false);
				//Merge with disabling of multiple user fields (SKIP_MULTIPLE_USER_FIELDS = TRUE)
				$merger->mergeFields(
					$sourceFields,
					$fields,
					true,
					array('SKIP_MULTIPLE_USER_FIELDS' => true)
				);
			}

			if(isset($fields['COMMENTS']))
			{
				$fields['COMMENTS'] = \Bitrix\Crm\Format\TextHelper::sanitizeHtml($fields['COMMENTS']);
			}

			$entity = new \CCrmCompany(false);
			if($isNew)
			{
				if(!isset($fields['COMPANY_TYPE']))
				{
					$fields['COMPANY_TYPE'] = \CCrmStatus::GetFirstStatusID('COMPANY_TYPE');
				}

				if(!isset($fields['INDUSTRY']))
				{
					$fields['INDUSTRY'] = \CCrmStatus::GetFirstStatusID('INDUSTRY');
				}

				if(!isset($fields['EMPLOYEES']))
				{
					$fields['EMPLOYEES'] = \CCrmStatus::GetFirstStatusID('EMPLOYEES');
				}

				if(!isset($fields['OPENED']))
				{
					$fields['OPENED'] = \Bitrix\Crm\Settings\CompanySettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';
				}

				$ID = $entity->Add($fields, true, array('REGISTER_SONET_EVENT' => true));
				if($ID <= 0)
				{
					$errorMessage = $entity->LAST_ERROR;
				}
			}
			else
			{
				if(!$entity->Update($ID, $fields, true, true,  array('REGISTER_SONET_EVENT' => true)))
				{
					$errorMessage = $entity->LAST_ERROR;
				}
			}
		}

		if($errorMessage !== '')
		{
			//Deletion early created entities
			foreach($createdEntities as $entityTypeID => $entityIDs)
			{
				foreach($entityIDs as $entityID)
				{
					\Bitrix\Crm\Component\EntityDetails\BaseComponent::deleteEntity($entityTypeID, $entityID);
				}
			}
			__CrmCompanyDetailsEndJsonResonse(array('ERROR' => $errorMessage));
		}

		if(!empty($updateEntityInfos))
		{
			foreach($updateEntityInfos as $entityTypeID => $entityInfos)
			{
				foreach($entityInfos as $entityID => $entityInfo)
				{
					\Bitrix\Crm\Component\EntityDetails\BaseComponent::updateEntity(
						$entityTypeID,
						$entityID,
						$entityInfo,
						array(
							'userPermissions' => $currentUserPermissions,
							'startWorkFlows' => true
						)
					);
				}
			}
		}

		//region Requisites
		$addedRequisites = array();
		if(!empty($entityRequisites))
		{
			$requisite = new \Bitrix\Crm\EntityRequisite();
			foreach($entityRequisites as $requisiteID => $requisiteData)
			{
				if(isset($requisiteData['isDeleted']) && $requisiteData['isDeleted'] === true)
				{
					$requisite->delete($requisiteID);
					continue;
				}

				$requisiteFields = $requisiteData['fields'];
				$requisiteFields['ENTITY_TYPE_ID'] = CCrmOwnerType::Company;
				$requisiteFields['ENTITY_ID'] = $ID;

				if($requisiteID > 0)
				{
					$requisite->update($requisiteID, $requisiteFields);
				}
				else
				{
					$result = $requisite->add($requisiteFields);
					if($result->isSuccess())
					{
						$addedRequisites[$requisiteID] = $result->getId();
					}
				}
			}
		}
		if(!empty($entityBankDetails))
		{
			$bankDetail = new \Bitrix\Crm\EntityBankDetail();
			foreach($entityBankDetails as $requisiteID => $bankDetails)
			{
				foreach($bankDetails as $pseudoID => $bankDetailFields)
				{
					if(isset($bankDetailFields['isDeleted']) && $bankDetailFields['isDeleted'] === true)
					{
						if($pseudoID > 0)
						{
							$bankDetail->delete($pseudoID);
						}
						continue;
					}

					if($pseudoID > 0)
					{
						$bankDetail->update($pseudoID, $bankDetailFields);
					}
					else
					{
						if($requisiteID <= 0 && isset($addedRequisites[$requisiteID]))
						{
							$requisiteID = $addedRequisites[$requisiteID];
						}

						if($requisiteID > 0)
						{
							$bankDetailFields['ENTITY_ID'] = $requisiteID;
							$bankDetailFields['ENTITY_TYPE_ID'] = \CCrmOwnerType::Requisite;
							$bankDetail->add($bankDetailFields);
						}
					}
				}
			}
		}
		//endregion

		\Bitrix\Crm\Tracking\UI\Details::saveEntityData(
			\CCrmOwnerType::Company,
			$ID,
			$_POST,
			$isNew
		);

		$arErrors = array();
		\CCrmBizProcHelper::AutoStartWorkflows(
			\CCrmOwnerType::Company,
			$ID,
			$isNew ? \CCrmBizProcEventType::Create : \CCrmBizProcEventType::Edit,
			$arErrors,
			isset($_POST['bizproc_parameters']) ? $_POST['bizproc_parameters'] : null
		);

		if($conversionWizard !== null)
		{
			$conversionWizard->attachNewlyCreatedEntity(\CCrmOwnerType::CompanyName, $ID);
			$url = $conversionWizard->getRedirectUrl();
			if($url !== '')
			{
				$responseData = array('ENTITY_ID' => $ID, 'REDIRECT_URL' => $url);
				$eventParams = $conversionWizard->getClientEventParams();
				if(is_array($eventParams))
				{
					$responseData['EVENT_PARAMS'] = $eventParams;
				}

				__CrmCompanyDetailsEndJsonResonse($responseData);
			}
		}
	}

	CBitrixComponent::includeComponentClass('bitrix:crm.company.details');
	$component = new CCrmCompanyDetailsComponent();
	$component->initializeParams($params);
	$component->setEntityID($ID);
	$result = array(
		'ENTITY_ID' => $ID,
		'ENTITY_DATA' => $component->prepareEntityData(),
		'ENTITY_INFO' => $component->prepareEntityInfo()
	);

	if($isNew)
	{
		$result['EVENT_PARAMS'] = array(
			'entityInfo' => \CCrmEntitySelectorHelper::PrepareEntityInfo(
				CCrmOwnerType::CompanyName,
				$ID,
				array(
					'ENTITY_EDITOR_FORMAT' => true,
					'NAME_TEMPLATE' =>
						isset($params['NAME_TEMPLATE'])
							? $params['NAME_TEMPLATE']
							: \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
				)
			)
		);

		$result['REDIRECT_URL'] = \CCrmOwnerType::GetDetailsUrl(
			\CCrmOwnerType::Company,
			$ID,
			false,
			array('ENABLE_SLIDER' => true)
		);
	}

	__CrmCompanyDetailsEndJsonResonse($result);
}
elseif($action === 'DELETE')
{
	$ID = isset($_POST['ACTION_ENTITY_ID']) ? max((int)$_POST['ACTION_ENTITY_ID'], 0) : 0;
	if($ID <= 0)
	{
		__CrmCompanyDetailsEndJsonResonse(array('ERROR' => GetMessage('CRM_COMPANY_NOT_FOUND')));
	}

	if(!\CCrmCompany::CheckDeletePermission($ID, $currentUserPermissions))
	{
		__CrmCompanyDetailsEndJsonResonse(array('ERROR' => GetMessage('CRM_COMPANY_ACCESS_DENIED')));
	}

	$bizProc = new CCrmBizProc('COMPANY');
	if (!$bizProc->Delete($ID, \CCrmCompany::GetPermissionAttributes(array($ID))))
	{
		__CrmCompanyDetailsEndJsonResonse(array('ERROR' => $bizProc->LAST_ERROR));
	}

	$entity = new \CCrmCompany(false);
	if (!$entity->Delete($ID, array('PROCESS_BIZPROC' => false)))
	{
		/** @var CApplicationException $ex */
		$ex = $APPLICATION->GetException();
		__CrmCompanyDetailsEndJsonResonse(
			array(
				'ERROR' => ($ex instanceof CApplicationException) ? $ex->GetString() : GetMessage('CRM_COMPANY_DELETION_ERROR')
			)
		);
	}
	__CrmCompanyDetailsEndJsonResonse(array('ENTITY_ID' => $ID));
}
elseif($action === 'RENDER_IMAGE_INPUT')
{
	$ID = isset($_POST['ACTION_ENTITY_ID']) ? max((int)$_POST['ACTION_ENTITY_ID'], 0) : 0;
	if(($ID > 0 && !\CCrmCompany::CheckUpdatePermission($ID, $currentUserPermissions))
		|| ($ID === 0 && !\CCrmCompany::CheckCreatePermission($currentUserPermissions))
	)
	{
		__CrmCompanyDetailsEndHtmlResonse();
	}

	$fieldName = isset($_POST['FIELD_NAME']) ? $_POST['FIELD_NAME'] : '';
	if($fieldName !== '')
	{
		$value = 0;
		if($ID > 0)
		{
			$dbResult = \CCrmCompany::GetListEx(
				array(),
				array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array($fieldName)
			);
			$fields = $dbResult->Fetch();
			if(is_array($fields) && isset($fields[$fieldName]))
			{
				$value = (int)$fields[$fieldName];
			}
		}

		Header('Content-Type: text/html; charset='.LANG_CHARSET);
		$APPLICATION->ShowAjaxHead();
		$APPLICATION->IncludeComponent(
			'bitrix:main.file.input',
			'',
			array(
				'MODULE_ID' => 'crm',
				'MAX_FILE_SIZE' => 3145728,
				'MULTIPLE'=> 'N',
				'ALLOW_UPLOAD' => 'I',
				'SHOW_AVATAR_EDITOR' => 'Y',
				'ENABLE_CAMERA' => 'N',
				'CONTROL_ID' => strtolower($fieldName).'_uploader',
				'INPUT_NAME' => $fieldName,
				'INPUT_VALUE' => $value
			),
			null,
			array('HIDE_ICONS' => 'Y')
		);
	}
	__CrmCompanyDetailsEndHtmlResonse();

}
elseif($action === 'GET_FORMATTED_SUM')
{
	$sum = isset($_POST['SUM']) ? $_POST['SUM'] : 0.0;
	$currencyID = isset($_POST['CURRENCY_ID']) ? $_POST['CURRENCY_ID'] : '';
	if($currencyID === '')
	{
		$currencyID = \CCrmCurrency::GetBaseCurrencyID();
	}

	__CrmCompanyDetailsEndJsonResonse(
		array(
			'FORMATTED_SUM' => \CCrmCurrency::MoneyToString($sum, $currencyID, '#'),
			'FORMATTED_SUM_WITH_CURRENCY' => \CCrmCurrency::MoneyToString($sum, $currencyID, '')
		)
	);
}
