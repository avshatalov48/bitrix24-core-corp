<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Crm;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Tracking;
use Bitrix\Main;

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
Container::getInstance()->getLocalization()->loadMessages();

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
	$diskQuotaRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getDiskQuotaRestriction();
	if (!$diskQuotaRestriction->hasPermission())
	{
		__CrmContactDetailsEndJsonResonse([
			'ERROR' => $diskQuotaRestriction->getErrorMessage(),
			'RESTRICTION' => true,
			'RESTRICTION_ACTION' => $diskQuotaRestriction->prepareInfoHelperScript()
		]);
	}

	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();
	$categoryID =  isset($params['CATEGORY_ID']) ? (int)$params['CATEGORY_ID'] : 0;
	$sourceEntityID =  isset($params['CONTACT_ID']) ? (int)$params['CONTACT_ID'] : 0;

	$ID = isset($_POST['ACTION_ENTITY_ID']) ? max((int)$_POST['ACTION_ENTITY_ID'], 0) : 0;

	if(($ID > 0 && !\CCrmContact::CheckUpdatePermission($ID, $currentUserPermissions, $categoryID))
		|| ($ID === 0 && !\CCrmContact::CheckCreatePermission($currentUserPermissions, $categoryID))
	)
	{
		__CrmContactDetailsEndJsonResonse(['ERROR'=> \Bitrix\Main\Localization\Loc::getMessage('CRM_TYPE_ITEM_PERMISSIONS_UPDATE_DENIED')]);
	}

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

		$sourceFields['ORIGINATOR_ID'] = '';
		$sourceFields['ORIGIN_ID'] = '';
		$sourceFields['ORIGIN_VERSION'] = '';
	}

	Crm\Service\EditorAdapter::fillParentFieldFromContextEnrichedData($_POST);
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

			if($name === 'PHOTO')
			{
				if(!(isset($presentFields[$name]) && $presentFields[$name] == $fields[$name]))
				{
					$fileID = $fields[$name];
					$allowedFileIDs = \Bitrix\Main\UI\FileInputUtility::instance()->checkFiles(
						mb_strtolower($name).'_uploader',
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
					$removedFilesKey = mb_strtolower($name).'_uploader_deleted';

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
	/** @global $USER_FIELD_MANAGER CUserTypeManager */
	global $USER_FIELD_MANAGER;
	$USER_FIELD_MANAGER->EditFormAddFields(\CCrmContact::USER_FIELD_ENTITY_ID, $fields, [
		'FORM' => $fields,
		'FILES' => [],
	]);

	if($isNew)
	{
		$fields['CATEGORY_ID'] = $categoryID;
	}
	else
	{
		unset($fields['CATEGORY_ID']);
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

	$createdEntities = [];
	$updateEntityInfos = [];
	$categoryParams = CCrmComponentHelper::getEntityClientFieldCategoryParams(
		CCrmOwnerType::Contact,
		$categoryID
	);

	if(isset($clientData['COMPANY_DATA']) && is_array($clientData['COMPANY_DATA']))
	{
		$companyIDs = [];
		$companyData = $clientData['COMPANY_DATA'];
		foreach($companyData as $companyItem)
		{
			$companyID = isset($companyItem['id']) ? (int)$companyItem['id'] : 0;
			$categoryId = isset($companyItem['categoryId']) ? (int)$companyItem['categoryId'] : 0;

			// unlikely situation but check in case of mismatch
			if($categoryId !== $categoryParams[CCrmOwnerType::Company]['categoryId'])
			{
				__CrmContactDetailsEndJsonResonse(['ERROR' => 'INVALID CLIENT COMPANY CATEGORY ID!']);
			}

			if($companyID <= 0)
			{
				$companyID = \Bitrix\Crm\Component\EntityDetails\BaseComponent::createEntity(
					\CCrmOwnerType::Company,
					$companyItem,
					array(
						'userPermissions' => $currentUserPermissions,
						'startWorkFlows' => true
					)
				);

				if($companyID > 0)
				{
					if(!isset($createdEntities[CCrmOwnerType::Company]))
					{
						$createdEntities[CCrmOwnerType::Company] = array();
					}
					$createdEntities[CCrmOwnerType::Company][] = $companyID;
				}
			}
			elseif(
				$companyItem['title']
				|| (isset($companyItem['multifields']) && is_array($companyItem['multifields']))
				|| (isset($companyItem['requisites']) && is_array($companyItem['requisites']))
			)
			{
				if(!isset($updateEntityInfos[CCrmOwnerType::Company]))
				{
					$updateEntityInfos[CCrmOwnerType::Company] = array();
				}
				$updateEntityInfos[CCrmOwnerType::Company][$companyID] = $companyItem;
			}

			if($companyID > 0)
			{
				$companyIDs[] = $companyID;
			}
		}

		if(!empty($companyIDs))
		{
			$companyIDs = array_unique($companyIDs);
		}

		$fields['COMPANY_IDS'] = $companyIDs;
		if(!empty($fields['COMPANY_IDS']))
		{
			$companyBindings = [];
			foreach ($fields['COMPANY_IDS'] as $companyID)
			{
				$companyBindings[] = [
					'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
					'ENTITY_ID' => $companyID,
					'CATEGORY_ID' => $categoryParams[CCrmOwnerType::Company]['categoryId']
				];
			}
			Crm\Controller\Entity::addLastRecentlyUsedItems(
				'crm.contact.details',
				'company',
				$companyBindings
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

	$errorMessage = '';
	$checkExceptions = null;
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
				$merger = new \Bitrix\Crm\Merger\ContactMerger($currentUserID, false);
				//Merge with disabling of multiple user fields (SKIP_MULTIPLE_USER_FIELDS = TRUE)
				$merger->mergeFields(
					$sourceFields,
					$fields,
					true,
					array('SKIP_MULTIPLE_USER_FIELDS' => true)
				);
			}

			$fields = Crm\Entity\FieldContentType::prepareFieldsFromDetailsToSave(\CCrmOwnerType::Contact, $ID, $fields);

			Tracking\UI\Details::appendEntityFieldValue($fields, $_POST);

			$entity = new \CCrmContact(false);
			$saveOptions = array_merge(
				Crm\Entity\FieldContentType::prepareSaveOptionsForDetails(\CCrmOwnerType::Contact, $ID),
				['REGISTER_SONET_EVENT' => true],
			);
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

				$ID = $entity->Add($fields, true, $saveOptions);
				if($ID <= 0)
				{
					$checkExceptions = $entity->GetCheckExceptions();
					$errorMessage = $entity->LAST_ERROR;
				}
			}
			else
			{
				if(!$entity->Update($ID, $fields, true, true,  $saveOptions))
				{
					$checkExceptions = $entity->GetCheckExceptions();
					$errorMessage = $entity->LAST_ERROR;
				}
			}
		}

		$hasErrors = (!empty($checkExceptions) || $errorMessage);
		if($hasErrors)
		{
			//Deletion early created entities
			foreach($createdEntities as $entityTypeID => $entityIDs)
			{
				foreach($entityIDs as $entityID)
				{
					\Bitrix\Crm\Component\EntityDetails\BaseComponent::deleteEntity($entityTypeID, $entityID);
				}
			}

			$responseData = array();
			if(!empty($checkExceptions))
			{
				$checkErrors = array();
				foreach($checkExceptions as $exception)
				{
					if($exception instanceof \CAdminException)
					{
						foreach($exception->GetMessages() as $message)
						{
							$checkErrors[$message['id']] = $message['text'];
						}
					}
				}
				$responseData['CHECK_ERRORS'] = $checkErrors;
			}

			if($errorMessage !== '')
			{
				$responseData['ERROR'] = $errorMessage;
			}
			__CrmContactDetailsEndJsonResonse($responseData);
		}

		if(!$hasErrors)
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
		\Bitrix\Crm\EntityRequisite::saveFormData(
			CCrmOwnerType::Contact,
			$ID,
			$entityRequisites,
			$entityBankDetails
		);
		//endregion

		Tracking\UI\Details::saveEntityData(
			\CCrmOwnerType::Contact,
			$ID,
			$_POST,
			$isNew
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
	if($ID > 0)
	{
		$component->setCategoryID((int)Container::getInstance()->getFactory(CCrmOwnerType::Contact)->getItemCategoryId($ID));
	}
	elseif(isset($context['PARAMS']) && isset($context['PARAMS']['CATEGORY_ID']))
	{
		$component->setCategoryID((int)$context['PARAMS']['CATEGORY_ID']);
	}
	$component->initializeData();
	$result = $component->getEntityEditorData();

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
elseif($action === 'LOAD')
{
	$ID = isset($_POST['ACTION_ENTITY_ID']) ? max((int)$_POST['ACTION_ENTITY_ID'], 0) : 0;
	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : [];

	if ($ID <=0)
	{
		__CrmContactDetailsEndJsonResonse(['ERROR'=>'ENTITY ID IS NOT FOUND!']);
	}
	if(!\CCrmCompany::CheckReadPermission($ID, $currentUserPermissions))
	{
		__CrmContactDetailsEndJsonResonse(['ERROR'=> \Bitrix\Main\Localization\Loc::getMessage('CRM_TYPE_ITEM_PERMISSIONS_UPDATE_DENIED')]);
	}

	CBitrixComponent::includeComponentClass('bitrix:crm.contact.details');
	$component = new CCrmContactDetailsComponent();
	$component->initializeParams($params);
	$component->setEntityID($ID);
	$component->initializeData();
	$result = $component->getEntityEditorData();

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
		__CrmContactDetailsEndJsonResonse(['ERROR'=> \Bitrix\Main\Localization\Loc::getMessage('CRM_COMMON_ERROR_ACCESS_DENIED')]);
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
	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Controller\Action\Entity\RenderImageInputAction
	 */

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
				'CONTROL_ID' => mb_strtolower($fieldName).'_uploader',
				'INPUT_NAME' => $fieldName,
				'INPUT_VALUE' => $value
			),
			null,
			array('HIDE_ICONS' => 'Y')
		);
	}
	__CrmContactDetailsEndHtmlResonse();

}
elseif($action === 'PREPARE_EDITOR_HTML')
{
	$ID = isset($_POST['ACTION_ENTITY_ID']) ? max((int)$_POST['ACTION_ENTITY_ID'], 0) : 0;
	$guid = isset($_POST['GUID']) ? $_POST['GUID'] : "contact_{$ID}_custom_editor";
	$configID = isset($_POST['CONFIG_ID']) ? $_POST['CONFIG_ID'] : '';
	$forceDefaultConfig = !isset($_POST['FORCE_DEFAULT_CONFIG']) || mb_strtoupper($_POST['FORCE_DEFAULT_CONFIG']) === 'Y';
	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();
	$context = isset($_POST['CONTEXT']) && is_array($_POST['CONTEXT']) ? $_POST['CONTEXT'] : array();
	$fieldNames = isset($_POST['FIELDS']) && is_array($_POST['FIELDS']) ? $_POST['FIELDS'] : array();
	$title = isset($_POST['TITLE']) ? $_POST['TITLE'] : '';

	if (!\CCrmContact::CheckReadPermission($ID))
	{
		__CrmContactDetailsEndJsonResonse(['ERROR' => 'Access denied.']);
	}
	if($ID > 0 && !\CCrmContact::Exists($ID))
	{
		__CrmContactDetailsEndJsonResonse(['ERROR' => Main\Localization\Loc::getMessage('CRM_CONTACT_NOT_FOUND')]);
	}

	$enableConfigScopeToggle = !isset($_POST['ENABLE_CONFIG_SCOPE_TOGGLE'])
		|| mb_strtoupper($_POST['ENABLE_CONFIG_SCOPE_TOGGLE']) === 'Y';
	$enableConfigurationUpdate = !isset($_POST['ENABLE_CONFIGURATION_UPDATE'])
		|| mb_strtoupper($_POST['ENABLE_CONFIGURATION_UPDATE']) === 'Y';
	$enableFieldsContextMenu = !isset($_POST['ENABLE_FIELDS_CONTEXT_MENU'])
		|| mb_strtoupper($_POST['ENABLE_FIELDS_CONTEXT_MENU']) === 'Y';
	$isEmbedded = isset($_POST['IS_EMBEDDED']) && mb_strtoupper($_POST['IS_EMBEDDED']) === 'Y';
	$enableRequiredUserFieldCheck = !isset($_POST['ENABLE_REQUIRED_USER_FIELD_CHECK'])
		|| mb_strtoupper($_POST['ENABLE_REQUIRED_USER_FIELD_CHECK']) === 'Y';
	$enableSearchHistory = !isset($_POST['ENABLE_SEARCH_HISTORY'])
		|| mb_strtoupper($_POST['ENABLE_SEARCH_HISTORY']) === 'Y';

	$enableAvailableFieldsInjection = isset($_POST['ENABLE_AVAILABLE_FIELDS_INJECTION'])
		&& mb_strtoupper($_POST['ENABLE_AVAILABLE_FIELDS_INJECTION']) === 'Y';
	$enableExternalLayoutResolvers = isset($_POST['ENABLE_EXTERNAL_LAYOUT_RESOLVERS'])
		&& mb_strtoupper($_POST['ENABLE_EXTERNAL_LAYOUT_RESOLVERS']) === 'Y';

	$isReadOnly = isset($_POST['READ_ONLY']) && mb_strtoupper($_POST['READ_ONLY']) === 'Y';
	$showEmptyFields = isset($_POST['SHOW_EMPTY_FIELDS']) && mb_strtoupper($_POST['SHOW_EMPTY_FIELDS']) === 'Y';
	$initialMode = isset($_POST['INITIAL_MODE']) ? $_POST['INITIAL_MODE'] : '';

	CBitrixComponent::includeComponentClass('bitrix:crm.contact.details');
	$component = new CCrmContactDetailsComponent();

	if(!isset($params['NAME_TEMPLATE']))
	{
		$params['NAME_TEMPLATE'] = CSite::GetNameFormat(false);
	}
	$component->initializeParams($params);
	$component->setEntityID($ID);
	$component->enableSearchHistory($enableSearchHistory);

	if(!isset($context['PARAMS']))
	{
		$context['PARAMS'] = array();
	}
	$context['PARAMS'] = array_merge($params, $context['PARAMS']);

	$component->initializeData();

	if(empty($fieldNames))
	{
		$entityConfig = $component->prepareConfiguration();
	}
	else
	{
		$fieldMap = array_fill_keys($fieldNames, true);
		$fieldInfos = $component->prepareFieldInfos();
		$entityConfigElements = array();
		foreach ($fieldInfos as $fieldInfo)
		{
			if(isset($fieldMap[$fieldInfo['name']]))
			{
				$entityConfigElements[] = array('name' => $fieldInfo['name']);
			}
		}

		$sectionConfig = array(
			'name' => 'main',
			'type' => 'section',
			'elements' => $entityConfigElements,
			'data' => array('isChangeable' => true, 'isRemovable' => false),
		);

		if($title !== '')
		{
			$sectionConfig['title'] = $title;
		}
		else
		{
			$sectionConfig['data']['enableTitle'] = false;
		}

		$entityConfig = array($sectionConfig);
	}

	$scopePrefix = '';
	if(isset($_POST['FORCE_DEFAULT_SCOPE']) && mb_strtoupper($_POST['FORCE_DEFAULT_SCOPE']) === 'Y')
	{
		$scopePrefix = $component->getDefaultConfigID();
	}

	$optionPrefix = '';
	if(isset($_POST['FORCE_DEFAULT_OPTIONS']) && mb_strtoupper($_POST['FORCE_DEFAULT_OPTIONS']) === 'Y')
	{
		$optionPrefix = $component->getDefaultConfigID();
	}

	$GLOBALS['APPLICATION']->RestartBuffer();
	Header('Content-Type: text/html; charset='.LANG_CHARSET);
	$APPLICATION->ShowAjaxHead();
	$APPLICATION->IncludeComponent(
		'bitrix:crm.entity.editor',
		'',
		array(
			'GUID' => $guid,
			'CONFIG_ID' => $configID !== '' ? $configID : $component->getDefaultConfigID(),
			'SCOPE' => Crm\Entity\EntityEditorConfigScope::COMMON,
			'SCOPE_PREFIX' => $scopePrefix,
			'OPTION_PREFIX' => $optionPrefix,
			'FORCE_DEFAULT_CONFIG' => $forceDefaultConfig,
			'ENTITY_CONFIG' => $entityConfig,
			'ENTITY_FIELDS' => $component->prepareFieldInfos(),
			'ENTITY_DATA' => $component->prepareEntityData(),
			'ENABLE_CONFIG_SCOPE_TOGGLE' => $enableConfigScopeToggle,
			'ENABLE_CONFIGURATION_UPDATE' => $enableConfigurationUpdate,
			'ENABLE_REQUIRED_FIELDS_INJECTION' => false,
			'ENABLE_AVAILABLE_FIELDS_INJECTION' => $enableAvailableFieldsInjection,
			'ENABLE_EXTERNAL_LAYOUT_RESOLVERS' => $enableExternalLayoutResolvers,
			'ENABLE_SECTION_EDIT' => false,
			'ENABLE_SECTION_CREATION' => false,
			'ENABLE_USER_FIELD_CREATION' => false,
			'ENABLE_MODE_TOGGLE' => false,
			'ENABLE_VISIBILITY_POLICY' => false,
			'ENABLE_TOOL_PANEL' => false,
			'ENABLE_BOTTOM_PANEL' => false,
			'ENABLE_PAGE_TITLE_CONTROLS' => false,
			'ENABLE_FIELDS_CONTEXT_MENU' => $enableFieldsContextMenu,
			'ENABLE_REQUIRED_USER_FIELD_CHECK' => $enableRequiredUserFieldCheck,
			'USER_FIELD_ENTITY_ID' => \CCrmContact::GetUserFieldEntityID(),
			'SERVICE_URL' => '/bitrix/components/bitrix/crm.contact.details/ajax.php?'.bitrix_sessid_get(),
			'CONTEXT_ID' => \CCrmOwnerType::ContactName.'_'.$ID,
			'ENTITY_TYPE_ID' => \CCrmOwnerType::Contact,
			'ENTITY_ID' => $ID,
			'READ_ONLY' => $isReadOnly,
			'INITIAL_MODE' => $initialMode !== '' ? $initialMode : 'edit',
			'SHOW_EMPTY_FIELDS' => $showEmptyFields,
			'IS_EMBEDDED' =>$isEmbedded,
			'CONTEXT' => $context
		)
	);

	if(!defined('PUBLIC_AJAX_MODE'))
	{
		define('PUBLIC_AJAX_MODE', true);
	}
	require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
	die();
}
