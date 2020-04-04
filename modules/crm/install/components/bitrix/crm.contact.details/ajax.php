<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Crm\Synchronization\UserFieldSynchronizer;
use Bitrix\Crm\Conversion\DealConversionConfig;
use Bitrix\Crm\Conversion\DealConversionWizard;
use Bitrix\Crm\Recurring;

if (!CModule::IncludeModule('crm'))
{
	return;
}
/*
 * ONLY 'POST' METHOD SUPPORTED
 * SUPPORTED ACTIONS:
 * 'SAVE'
 * 'RENDER_IMAGE_INPUT'
 * 'GET_DEFAULT_SECONDARY_ENTITIES'
 */
global $DB, $APPLICATION;
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

if(!function_exists('__CrmContactDetailsEndHtmlResonse'))
{
	function __CrmContactDetailsEndHtmlResonse()
	{
		if(!defined('PUBLIC_AJAX_MODE'))
		{
			define('PUBLIC_AJAX_MODE', true);
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}

if(!function_exists('__CrmContactDetailsEndJsonResonse'))
{
	function __CrmContactDetailsEndJsonResonse($result)
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
	__CrmContactDetailsEndJsonResonse(array('ERROR'=>'ACTION IS NOT DEFINED!'));
}
elseif($action === 'SAVE')
{
	$ID = isset($_POST['ACTION_ENTITY_ID']) ? max((int)$_POST['ACTION_ENTITY_ID'], 0) : 0;
	if(($ID > 0 && !\CCrmContact::CheckUpdatePermission($ID, $currentUserPermissions))
		|| ($ID === 0 && !\CCrmContact::CheckCreatePermission($currentUserPermissions))
	)
	{
		__CrmContactDetailsEndJsonResonse(array('ERROR'=>'PERMISSION DENIED!'));
	}

	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();
	$sourceEntityID =  isset($params['CONTACT_ID']) ? (int)$params['CONTACT_ID'] : 0;

	$isNew = $ID === 0;
	$isCopyMode = $isNew && $sourceEntityID > 0;

	$fields = array();
	$fieldsInfo = \CCrmContact::GetFieldsInfo();
	$userType = new \CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], \CCrmContact::GetUserFieldEntityID());
	$userType->PrepareFieldsInfo($fieldsInfo);
	\CCrmFieldMulti::PrepareFieldsInfo($fieldsInfo);

	$presentFields = array();
	if($ID > 0)
	{
		$dbResult = \CCrmContact::GetListEx(
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
		$dbResult = \CCrmContact::GetListEx(
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
				'ENTITY_ID' => CCrmOwnerType::ContactName,
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

			if($name === 'COMPANY_IDS')
			{
				$entityIDs = $fields[$name] !== '' ? explode(',', $fields[$name]) : array();
				$fields[$name] = !empty($entityIDs)
					? array_values(array_unique($entityIDs, SORT_NUMERIC))
					: $entityIDs;
			}
			else if($name === 'PHOTO')
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

	//region Requisites
	$entityRequisites = array();
	$entityBankDetails = array();
	if(isset($_POST['REQUISITES']) && is_array($_POST['REQUISITES']))
	{
		\Bitrix\Crm\EntityRequisite::intertalizeFormData(
			$_POST['REQUISITES'],
			CCrmOwnerType::Contact,
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
		$conversionWizard->prepareDataForSave(CCrmOwnerType::Contact, $fields);
	}

	if(!empty($fields) || !empty($entityRequisites) || !empty($entityBankDetails))
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

			$merger = new \Bitrix\Crm\Merger\ContactMerger($currentUserID, false);
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

		$entity = new \CCrmContact(false);
		if($isNew)
		{
			if(!isset($fields['TYPE_ID']))
			{
				$fields['TYPE_ID'] = \CCrmStatus::GetFirstStatusID('CONTACT_TYPE');
			}

			if(!isset($fields['SOURCE_ID']))
			{
				$fields['SOURCE_ID'] = \CCrmStatus::GetFirstStatusID('SOURCE');
			}

			if(!isset($fields['OPENED']))
			{
				$fields['OPENED'] = \Bitrix\Crm\Settings\ContactSettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';
			}

			if(!isset($fields['EXPORT']))
			{
				$fields['EXPORT'] = 'Y';
			}

			$ID = $entity->Add($fields, true, array('REGISTER_SONET_EVENT' => true));
			if($ID <= 0)
			{
				__CrmContactDetailsEndJsonResonse(array('ERROR' => $entity->LAST_ERROR));
			}
		}
		else
		{
			if(!$entity->Update($ID, $fields, true, true,  array('REGISTER_SONET_EVENT' => true)))
			{
				__CrmContactDetailsEndJsonResonse(array('ERROR' => $entity->LAST_ERROR));
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
				$requisiteFields['ENTITY_TYPE_ID'] = CCrmOwnerType::Contact;
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
			\CCrmOwnerType::Contact,
			$ID,
			$_POST
		);

		$arErrors = array();
		\CCrmBizProcHelper::AutoStartWorkflows(
			\CCrmOwnerType::Contact,
			$ID,
			$isNew ? \CCrmBizProcEventType::Create : \CCrmBizProcEventType::Edit,
			$arErrors,
			isset($_POST['bizproc_parameters']) ? $_POST['bizproc_parameters'] : null
		);

		if($conversionWizard !== null)
		{
			$conversionWizard->attachNewlyCreatedEntity(\CCrmOwnerType::ContactName, $ID);
			$url = $conversionWizard->getRedirectUrl();
			if($url !== '')
			{
				$responseData = array('ENTITY_ID' => $ID, 'REDIRECT_URL' => $url);
				$eventParams = $conversionWizard->getClientEventParams();
				if(is_array($eventParams))
				{
					$responseData['EVENT_PARAMS'] = $eventParams;
				}
				__CrmContactDetailsEndJsonResonse($responseData);
			}
		}
	}

	CBitrixComponent::includeComponentClass('bitrix:crm.contact.details');
	$component = new CCrmContactDetailsComponent();
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
				CCrmOwnerType::ContactName,
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
			\CCrmOwnerType::Contact,
			$ID,
			false,
			array('ENABLE_SLIDER' => true)
		);
	}

	__CrmContactDetailsEndJsonResonse($result);
}
elseif($action === 'DELETE')
{
	$ID = isset($_POST['ACTION_ENTITY_ID']) ? max((int)$_POST['ACTION_ENTITY_ID'], 0) : 0;
	if($ID <= 0)
	{
		__CrmContactDetailsEndJsonResonse(array('ERROR' => GetMessage('CRM_CONTACT_NOT_FOUND')));
	}

	if(!\CCrmContact::CheckDeletePermission($ID, $currentUserPermissions))
	{
		__CrmContactDetailsEndJsonResonse(array('ERROR' => GetMessage('CRM_CONTACT_ACCESS_DENIED')));
	}

	$bizProc = new CCrmBizProc('CONTACT');
	if (!$bizProc->Delete($ID, \CCrmContact::GetPermissionAttributes(array($ID))))
	{
		__CrmContactDetailsEndJsonResonse(array('ERROR' => $bizProc->LAST_ERROR));
	}

	$entity = new \CCrmContact(false);
	if (!$entity->Delete($ID, array('PROCESS_BIZPROC' => false)))
	{
		/** @var CApplicationException $ex */
		$ex = $APPLICATION->GetException();
		__CrmContactDetailsEndJsonResonse(
			array(
				'ERROR' => ($ex instanceof CApplicationException) ? $ex->GetString() : GetMessage('CRM_CONTACT_DELETION_ERROR')
			)
		);
	}
	__CrmContactDetailsEndJsonResonse(array('ENTITY_ID' => $ID));
}
elseif($action === 'RENDER_IMAGE_INPUT')
{
	$ID = isset($_POST['ACTION_ENTITY_ID']) ? max((int)$_POST['ACTION_ENTITY_ID'], 0) : 0;
	if(($ID > 0 && !\CCrmContact::CheckUpdatePermission($ID, $currentUserPermissions))
		|| ($ID === 0 && !\CCrmContact::CheckCreatePermission($currentUserPermissions))
	)
	{
		__CrmContactDetailsEndHtmlResonse();
	}

	$fieldName = isset($_POST['FIELD_NAME']) ? $_POST['FIELD_NAME'] : '';
	if($fieldName !== '')
	{
		$value = 0;
		if($ID > 0)
		{
			$dbResult = \CCrmContact::GetListEx(
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
				'CONTROL_ID' => strtolower($fieldName).'_uploader',
				'INPUT_NAME' => $fieldName,
				'INPUT_VALUE' => $value
			),
			null,
			array('HIDE_ICONS' => 'Y')
		);
	}
	__CrmContactDetailsEndHtmlResonse();

}

