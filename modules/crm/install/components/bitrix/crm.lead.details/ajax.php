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
 * 'GET_FORMATTED_SUM'
 */
global $DB, $APPLICATION;
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
if(!function_exists('__CrmLeadDetailsEndJsonResponse'))
{
	function __CrmLeadDetailsEndJsonResponse($result)
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
	__CrmLeadDetailsEndJsonResponse(array('ERROR'=>'ACTION IS NOT DEFINED!'));
}
if($action === 'GET_FORMATTED_SUM')
{
	$sum = isset($_POST['SUM']) ? $_POST['SUM'] : 0.0;
	$currencyID = isset($_POST['CURRENCY_ID']) ? $_POST['CURRENCY_ID'] : '';
	if($currencyID === '')
	{
		$currencyID = \CCrmCurrency::GetBaseCurrencyID();
	}

	__CrmLeadDetailsEndJsonResponse(
		array(
			'FORMATTED_SUM' => \CCrmCurrency::MoneyToString($sum, $currencyID, '#'),
			'FORMATTED_SUM_WITH_CURRENCY' => \CCrmCurrency::MoneyToString($sum, $currencyID, '')
		)
	);
}
elseif($action === 'SAVE')
{
	$ID = isset($_POST['ACTION_ENTITY_ID']) ? max((int)$_POST['ACTION_ENTITY_ID'], 0) : 0;
	if(($ID > 0 && !\CCrmLead::CheckUpdatePermission($ID, $currentUserPermissions))
		|| ($ID === 0 && !\CCrmLead::CheckCreatePermission($currentUserPermissions))
	)
	{
		__CrmLeadDetailsEndJsonResponse(array('ERROR'=>'PERMISSION DENIED!'));
	}

	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();
	$sourceEntityID =  isset($params['LEAD_ID']) ? (int)$params['LEAD_ID'] : 0;

	$enableRequiredUserFieldCheck = !isset($_POST['ENABLE_REQUIRED_USER_FIELD_CHECK'])
		|| strtoupper($_POST['ENABLE_REQUIRED_USER_FIELD_CHECK']) === 'Y';

	$isNew = $ID === 0;
	$isCopyMode = $isNew && $sourceEntityID > 0;
	//TODO: Implement external mode
	$isExternal = false;

	$previousFields = !$isNew ? \CCrmLead::GetByID($ID, false) : null;

	$fields = array();
	$fieldsInfo = \CCrmLead::GetFieldsInfo();
	$userType = new \CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], \CCrmLead::GetUserFieldEntityID());
	$userType->PrepareFieldsInfo($fieldsInfo);
	\CCrmFieldMulti::PrepareFieldsInfo($fieldsInfo);

	$sourceFields = array();
	if($sourceEntityID > 0)
	{
		$dbResult = \CCrmLead::GetListEx(
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

		$sourceFields['FM'] = array();
		$multiFieldDbResult = \CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => CCrmOwnerType::LeadName,
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

	foreach(array_keys($fieldsInfo) as $fieldName)
	{
		if(\CCrmFieldMulti::IsSupportedType($fieldName) && is_array($_POST[$fieldName]))
		{
			if(!isset($fields['FM']))
			{
				$fields['FM'] = array();
			}

			$fields['FM'][$fieldName] = $_POST[$fieldName];
		}
		elseif(isset($_POST[$fieldName]))
		{
			$fields[$fieldName] = $_POST[$fieldName];
		}
	}

	if(isset($_POST['OBSERVER_IDS']))
	{
		$fields['OBSERVER_IDS'] = is_array($_POST['OBSERVER_IDS']) ? $_POST['OBSERVER_IDS'] : array();
	}

	//region Check params
	$fieldCheckOptions = array();
	if(isset($_POST['TARGET_STATUS_ID']))
	{
		$fieldCheckOptions['STATUS_ID'] = $_POST['TARGET_STATUS_ID'];
	}
	//endregion

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

	$companyID = 0;
	$companyEntity = new \CCrmCompany(false);
	if(isset($clientData['COMPANY_DATA']) && is_array($clientData['COMPANY_DATA']))
	{
		$companyData = $clientData['COMPANY_DATA'];
		if(!empty($companyData))
		{
			$companyItem = $companyData[0];
			$companyID = isset($companyItem['id']) ? (int)$companyItem['id'] : 0;
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
					$createdEntities[\CCrmOwnerType::Company] = array($companyID);
				}
			}
			elseif($companyItem['title'] || (isset($companyItem['multifields']) && is_array($companyItem['multifields'])))
			{
				if(!isset($updateEntityInfos[CCrmOwnerType::Company]))
				{
					$updateEntityInfos[CCrmOwnerType::Company] = array();
				}
				$updateEntityInfos[CCrmOwnerType::Company][$companyID] = $companyItem;
			}
		}
		$fields['COMPANY_ID'] = $companyID;
		if($fields['COMPANY_ID'] > 0)
		{
			Crm\Controller\Entity::addLastRecentlyUsedItems(
				'crm.lead.details',
				'company',
				array(
					array(
						'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
						'ENTITY_ID' => $fields['COMPANY_ID']
					)
				)
			);
		}
	}

	$contactIDs = null;
	$bindContactIDs = null;
	$contactEntity = new \CCrmContact(false);
	if(isset($clientData['CONTACT_DATA']) && is_array($clientData['CONTACT_DATA']))
	{
		$contactIDs = array();
		$contactData = $clientData['CONTACT_DATA'];
		foreach($contactData as $contactItem)
		{
			if(!is_array($contactItem))
			{
				continue;
			}

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
					if(!is_array($bindContactIDs))
					{
						$bindContactIDs = array();
					}
					$bindContactIDs[] = $contactID;

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

		$fields['CONTACT_IDS'] = $contactIDs;
		if(!empty($fields['CONTACT_IDS']))
		{
			$contactBindings = array();
			foreach($fields['CONTACT_IDS'] as $contactID)
			{
				$contactBindings[] = array('ENTITY_TYPE_ID' => CCrmOwnerType::Contact, 'ENTITY_ID' => $contactID);
			}
			Crm\Controller\Entity::addLastRecentlyUsedItems(
				'crm.lead.details',
				'contact',
				$contactBindings
			);
		}
	}
	//endregion

	//region PRODUCT ROWS
	$enableProductRows = false;
	$originalProductRows = !$isNew ? \CCrmLead::LoadProductRows($ID) : array();

	$productRows = array();
	$productRowSettings = array();
	if(isset($_POST['LEAD_PRODUCT_DATA'])
		&& !(isset($_POST['SKIP_PRODUCT_DATA']) && strcasecmp($_POST['SKIP_PRODUCT_DATA'], 'Y') === 0)
	)
	{
		$productRows = \CUtil::JsObjectToPhp($_POST['LEAD_PRODUCT_DATA']);
		if(!is_array($productRows))
		{
			$productRows = array();
		}

		$enableProductRows = true;
		if(!$isNew
			&& !$isCopyMode
			&& \CCrmProductRow::AreEquals($productRows, $originalProductRows)
		)
		{
			$enableProductRows = false;
		}
	}

	if($enableProductRows)
	{
		if(!empty($productRows))
		{
			if($isCopyMode)
			{
				for($index = 0, $qty = count($productRows); $index < $qty; $index++)
				{
					unset($productRows[$index]['ID']);
				}
			}

			$calculationParams = $fields;
			if(!isset($calculationParams['CURRENCY_ID']))
			{
				if(is_array($previousFields) && isset($previousFields['CURRENCY_ID']))
				{
					$calculationParams['CURRENCY_ID'] = $previousFields['CURRENCY_ID'];
				}
				elseif(isset($sourceFields['CURRENCY_ID']))
				{
					$calculationParams['CURRENCY_ID'] = $sourceFields['CURRENCY_ID'];
				}
				else
				{
					$calculationParams['CURRENCY_ID'] = CCrmCurrency::GetBaseCurrencyID();
				}
			}

			$totals = \CCrmProductRow::CalculateTotalInfo('L', 0, false, $calculationParams, $productRows);
			$fields['OPPORTUNITY'] = isset($totals['OPPORTUNITY']) ? $totals['OPPORTUNITY'] : 0.0;
			$fields['TAX_VALUE'] = isset($totals['TAX_VALUE']) ? $totals['TAX_VALUE'] : 0.0;
		}
		else
		{
			$fields['TAX_VALUE'] = 0.0;
			if(!isset($fields['OPPORTUNITY']) && ($isNew || !empty($originalProductRows)))
			{
				$fields['OPPORTUNITY'] = 0.0;
			}
		}

		if(isset($_POST['LEAD_PRODUCT_DATA_SETTINGS']) && $_POST['LEAD_PRODUCT_DATA_SETTINGS'] !== '')
		{
			$settings = \CUtil::JsObjectToPhp($_POST['LEAD_PRODUCT_DATA_SETTINGS']);
			if(is_array($settings))
			{
				$productRowSettings['ENABLE_DISCOUNT'] = isset($settings['ENABLE_DISCOUNT'])
					? $settings['ENABLE_DISCOUNT'] === 'Y' : false;
				$productRowSettings['ENABLE_TAX'] = isset($settings['ENABLE_TAX'])
					? $settings['ENABLE_TAX'] === 'Y' : false;
			}
		}
	}

	//endregion

	$enableReload = false;
	$checkExceptions = null;
	$errorMessage = '';

	if(!empty($fields) || $enableProductRows || !empty($updateEntityInfos))
	{
		if(isset($fields['ASSIGNED_BY_ID']) && $fields['ASSIGNED_BY_ID'] > 0)
		{
			\Bitrix\Crm\Entity\EntityEditor::registerSelectedUser($fields['ASSIGNED_BY_ID']);
		}

		if(!empty($fields))
		{
			if($isCopyMode)
			{
				if(!isset($fields['ASSIGNED_BY_ID']))
				{
					$fields['ASSIGNED_BY_ID'] = $currentUserID;
				}

				\Bitrix\Crm\Entity\EntityEditor::prepareForCopy($fields, $userType);
				$merger = new \Bitrix\Crm\Merger\LeadMerger($currentUserID, false);
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

			$entity = new \CCrmLead(!CCrmPerms::IsAdmin());
			if($isNew)
			{
				/*
				if(!isset($fields['TITLE']) || $fields['TITLE'] === '')
				{
					if((isset($fields['NAME']) && $fields['NAME'] !== '')
						|| (isset($fields['LAST_NAME']) && $fields['LAST_NAME'] !== ''))
					{
						$fields['TITLE'] = CCrmLead::PrepareFormattedName(
							array(
								'HONORIFIC' => isset($fields['HONORIFIC']) ? $fields['HONORIFIC'] : '',
								'NAME' => isset($fields['NAME']) ? $fields['NAME'] : '',
								'SECOND_NAME' => isset($fields['SECOND_NAME']) ? $fields['SECOND_NAME'] : '',
								'LAST_NAME' => isset($fields['LAST_NAME']) ? $fields['LAST_NAME'] : ''
							)
						);
					}
				}
				*/

				if(!isset($fields['SOURCE_ID']))
				{
					$fields['SOURCE_ID'] = \CCrmStatus::GetFirstStatusID('SOURCE');
				}

				if(!isset($fields['OPENED']))
				{
					$fields['OPENED'] = \Bitrix\Crm\Settings\LeadSettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';
				}

				if(!isset($fields['CURRENCY_ID']))
				{
					$fields['CURRENCY_ID'] = CCrmCurrency::GetBaseCurrencyID();
				}

				$fields['EXCH_RATE'] = CCrmCurrency::GetExchangeRate($fields['CURRENCY_ID']);

				$options = array('REGISTER_SONET_EVENT' => true, 'FIELD_CHECK_OPTIONS' => $fieldCheckOptions);
				if(!$enableRequiredUserFieldCheck)
				{
					$options['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
				}
				$ID = $entity->Add($fields, true, $options);
				if($ID <= 0)
				{
					$checkExceptions = $entity->GetCheckExceptions();
					$errorMessage = $entity->LAST_ERROR;
				}
			}
			else
			{
				if(isset($fields['OPPORTUNITY']) || isset($fields['CURRENCY_ID']))
				{
					if(!isset($fields['OPPORTUNITY']))
					{
						if(is_array($previousFields) && isset($previousFields['OPPORTUNITY']))
						{
							$fields['OPPORTUNITY'] = $previousFields['OPPORTUNITY'];
						}
						elseif(isset($sourceFields['OPPORTUNITY']))
						{
							$fields['OPPORTUNITY'] = $sourceFields['OPPORTUNITY'];
						}
					}

					if(!isset($fields['CURRENCY_ID']))
					{
						if(is_array($previousFields) && isset($previousFields['CURRENCY_ID']))
						{
							$fields['CURRENCY_ID'] = $previousFields['CURRENCY_ID'];
						}
						elseif(isset($sourceFields['CURRENCY_ID']))
						{
							$fields['CURRENCY_ID'] = $sourceFields['CURRENCY_ID'];
						}
						else
						{
							$fields['CURRENCY_ID'] = CCrmCurrency::GetBaseCurrencyID();
						}
					}

					$fields['EXCH_RATE'] = CCrmCurrency::GetExchangeRate($fields['CURRENCY_ID']);
				}

				$options = array('REGISTER_SONET_EVENT' => true, 'FIELD_CHECK_OPTIONS' => $fieldCheckOptions);
				if(!$enableRequiredUserFieldCheck)
				{
					$options['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
				}
				if(!$entity->Update($ID, $fields, true, true, $options))
				{
					$checkExceptions = $entity->GetCheckExceptions();
					$errorMessage = $entity->LAST_ERROR;
				}

				//HACK: Handling of Customer type change
				if(isset($fields['IS_RETURN_CUSTOMER']))
				{
					$enableReload = true;
				}
			}
		}

		if(!empty($checkExceptions) || $errorMessage)
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
			__CrmLeadDetailsEndJsonResponse($responseData);
		}

		if(!$isExternal && $enableProductRows && (!$isNew || !empty($productRows)))
		{
			if(!\CCrmLead::SaveProductRows($ID, $productRows, true, true, false))
			{
				__CrmLeadDetailsEndJsonResponse(array('ERROR' => GetMessage('CRM_LEAD_PRODUCT_ROWS_SAVING_ERROR')));
			}
		}

		if(!empty($productRowSettings))
		{
			if(!$isNew)
			{
				$productRowSettings = array_merge(
					\CCrmProductRow::LoadSettings('L', $ID),
					$productRowSettings
				);
			}
			\CCrmProductRow::SaveSettings('L', $ID, $productRowSettings);
		}

		\Bitrix\Crm\Tracking\UI\Details::saveEntityData(
			\CCrmOwnerType::Lead,
			$ID,
			$_POST,
			$isNew
		);

		$editorSettings = new \Bitrix\Crm\Settings\EntityEditSettings(
			isset($_POST['EDITOR_CONFIG_ID']) ? $_POST['EDITOR_CONFIG_ID'] : 'lead_details'
		);
		if($editorSettings->isClientCompanyEnabled() &&
			$editorSettings->isClientContactEnabled() &&
			$companyID > 0 &&
			is_array($bindContactIDs) &&
			!empty($bindContactIDs)
		)
		{
			\Bitrix\Crm\Binding\ContactCompanyTable::bindContactIDs($companyID, $bindContactIDs);
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

		$arErrors = array();
		\CCrmBizProcHelper::AutoStartWorkflows(
			\CCrmOwnerType::Lead,
			$ID,
			$isNew ? \CCrmBizProcEventType::Create : \CCrmBizProcEventType::Edit,
			$arErrors,
			isset($_POST['bizproc_parameters']) ? $_POST['bizproc_parameters'] : null
		);

		if($isNew)
		{
			\Bitrix\Crm\Automation\Factory::runOnAdd(\CCrmOwnerType::Lead, $ID);
		}
		else if(is_array($previousFields)
			&& isset($fields['STATUS_ID'])
			&& isset($previousFields['STATUS_ID'])
			&& $fields['STATUS_ID'] !== $previousFields['STATUS_ID']
		)
		{
			\Bitrix\Crm\Automation\Factory::runOnStatusChanged(\CCrmOwnerType::Lead, $ID);
		}
	}

	CBitrixComponent::includeComponentClass('bitrix:crm.lead.details');
	$component = new CCrmLeadDetailsComponent();
	$component->initializeParams($params);
	$component->setEntityID($ID);
	$result = array('ENTITY_ID' => $ID, 'ENTITY_DATA' => $component->prepareEntityData());

	if($isNew)
	{
		$result['EVENT_PARAMS'] = array(
			'entityInfo' => \CCrmEntitySelectorHelper::PrepareEntityInfo(
				CCrmOwnerType::LeadName,
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
	}

	if($isNew || $enableReload)
	{
		$result['REDIRECT_URL'] = \CCrmOwnerType::GetDetailsUrl(
			\CCrmOwnerType::Lead,
			$ID,
			false,
			array('ENABLE_SLIDER' => true)
		);
	}

	__CrmLeadDetailsEndJsonResponse($result);
}
elseif($action === 'DELETE')
{
	$ID = isset($_POST['ACTION_ENTITY_ID']) ? max((int)$_POST['ACTION_ENTITY_ID'], 0) : 0;
	if($ID <= 0)
	{
		__CrmLeadDetailsEndJsonResponse(array('ERROR' => GetMessage('CRM_LEAD_NOT_FOUND')));
	}

	if(!\CCrmLead::CheckDeletePermission($ID, $currentUserPermissions))
	{
		__CrmLeadDetailsEndJsonResponse(array('ERROR' => GetMessage('CRM_LEAD_ACCESS_DENIED')));
	}

	$bizProc = new CCrmBizProc('LEAD');
	if (!$bizProc->Delete($ID, \CCrmLead::GetPermissionAttributes(array($ID))))
	{
		__CrmLeadDetailsEndJsonResponse(array('ERROR' => $bizProc->LAST_ERROR));
	}

	$entity = new \CCrmLead(false);
	if (!$entity->Delete($ID, array('PROCESS_BIZPROC' => false)))
	{
		/** @var CApplicationException $ex */
		$ex = $APPLICATION->GetException();
		__CrmLeadDetailsEndJsonResponse(
			array(
				'ERROR' => ($ex instanceof CApplicationException) ? $ex->GetString() : GetMessage('CRM_LEAD_DELETION_ERROR')
			)
		);
	}
	__CrmLeadDetailsEndJsonResponse(array('ENTITY_ID' => $ID));
}
elseif($action === 'EXCLUDE')
{
	$ID = isset($_POST['ACTION_ENTITY_ID']) ? max((int)$_POST['ACTION_ENTITY_ID'], 0) : 0;
	if($ID <= 0)
	{
		__CrmLeadDetailsEndJsonResponse(array('ERROR' => GetMessage('CRM_LEAD_NOT_FOUND')));
	}

	if(!(\Bitrix\Crm\Exclusion\Access::current()->canWrite()))
	{
		__CrmLeadDetailsEndJsonResponse(array('ERROR' => GetMessage('CRM_LEAD_ACCESS_DENIED')));
	}

	\Bitrix\Crm\Exclusion\Store::addFromEntity(CCrmOwnerType::Lead, $ID);

	if(\CCrmLead::CheckDeletePermission($ID, $currentUserPermissions))
	{
		$bizProc = new CCrmBizProc('LEAD');
		if (!$bizProc->Delete($ID, \CCrmLead::GetPermissionAttributes(array($ID))))
		{
			__CrmLeadDetailsEndJsonResponse(array('ERROR' => $bizProc->LAST_ERROR));
		}

		$entity = new \CCrmLead(false);
		if (!$entity->Delete($ID, array('PROCESS_BIZPROC' => false)))
		{
			/** @var CApplicationException $ex */
			$ex = $APPLICATION->GetException();
			__CrmLeadDetailsEndJsonResponse(
				array(
					'ERROR' => ($ex instanceof CApplicationException) ? $ex->GetString() : GetMessage('CRM_LEAD_DELETION_ERROR')
				)
			);
		}
	}
	__CrmLeadDetailsEndJsonResponse(array('ENTITY_ID' => $ID));
}
elseif($action === 'PREPARE_EDITOR_HTML')
{
	$ID = isset($_POST['ACTION_ENTITY_ID']) ? max((int)$_POST['ACTION_ENTITY_ID'], 0) : 0;
	$guid = isset($_POST['GUID']) ? $_POST['GUID'] : "lead_{$ID}_custom_editor";
	$configID = isset($_POST['CONFIG_ID']) ? $_POST['CONFIG_ID'] : '';
	$forceDefaultConfig = !isset($_POST['FORCE_DEFAULT_CONFIG']) || strtoupper($_POST['FORCE_DEFAULT_CONFIG']) === 'Y';
	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();
	$context = isset($_POST['CONTEXT']) && is_array($_POST['CONTEXT']) ? $_POST['CONTEXT'] : array();
	$fieldNames = isset($_POST['FIELDS']) && is_array($_POST['FIELDS']) ? $_POST['FIELDS'] : array();
	$title = isset($_POST['TITLE']) ? $_POST['TITLE'] : '';

	$enableConfigScopeToggle = !isset($_POST['ENABLE_CONFIG_SCOPE_TOGGLE'])
		|| strtoupper($_POST['ENABLE_CONFIG_SCOPE_TOGGLE']) === 'Y';
	$enableConfigurationUpdate = !isset($_POST['ENABLE_CONFIGURATION_UPDATE'])
		|| strtoupper($_POST['ENABLE_CONFIGURATION_UPDATE']) === 'Y';
	$enableFieldsContextMenu = !isset($_POST['ENABLE_FIELDS_CONTEXT_MENU'])
		|| strtoupper($_POST['ENABLE_FIELDS_CONTEXT_MENU']) === 'Y';
	$isEmbedded = isset($_POST['IS_EMBEDDED']) && strtoupper($_POST['IS_EMBEDDED']) === 'Y';
	$enableRequiredUserFieldCheck = !isset($_POST['ENABLE_REQUIRED_USER_FIELD_CHECK'])
		|| strtoupper($_POST['ENABLE_REQUIRED_USER_FIELD_CHECK']) === 'Y';
	$enableSearchHistory = !isset($_POST['ENABLE_SEARCH_HISTORY'])
		|| strtoupper($_POST['ENABLE_SEARCH_HISTORY']) === 'Y';
	$enableCommunicationControls = !isset($_POST['ENABLE_COMMUNICATION_CONTROLS'])
		|| strtoupper($_POST['ENABLE_COMMUNICATION_CONTROLS']) === 'Y';

	$enableAvailableFieldsInjection = isset($_POST['ENABLE_AVAILABLE_FIELDS_INJECTION'])
		&& strtoupper($_POST['ENABLE_AVAILABLE_FIELDS_INJECTION']) === 'Y';
	$enableExternalLayoutResolvers = isset($_POST['ENABLE_EXTERNAL_LAYOUT_RESOLVERS'])
		&& strtoupper($_POST['ENABLE_EXTERNAL_LAYOUT_RESOLVERS']) === 'Y';

	$enableConfigVariability = !isset($_POST['ENABLE_CONFIG_VARIABILITY'])
		|| strtoupper($_POST['ENABLE_CONFIG_VARIABILITY']) === 'Y';

	$isReadOnly = isset($_POST['READ_ONLY']) && strtoupper($_POST['READ_ONLY']) === 'Y';
	$showEmptyFields = isset($_POST['SHOW_EMPTY_FIELDS']) && strtoupper($_POST['SHOW_EMPTY_FIELDS']) === 'Y';
	$initialMode = isset($_POST['INITIAL_MODE']) ? $_POST['INITIAL_MODE'] : '';

	CBitrixComponent::includeComponentClass('bitrix:crm.lead.details');
	$component = new CCrmLeadDetailsComponent();

	if(!isset($params['NAME_TEMPLATE']))
	{
		$params['NAME_TEMPLATE'] = CSite::GetNameFormat(false);
	}
	$component->initializeParams($params);
	$component->enableConfigVariability($enableConfigVariability);
	$component->enableSearchHistory($enableSearchHistory);

	$component->setEntityID($ID);

	$context['SKIP_PRODUCT_DATA'] = 'Y';

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

	$scope = \Bitrix\Crm\Entity\EntityEditorConfigScope::UNDEFINED;
	if(isset($_POST['SCOPE']) && \Bitrix\Crm\Entity\EntityEditorConfigScope::isDefined($_POST['SCOPE']))
	{
		$scope = $_POST['SCOPE'];
	}

	$scopePrefix = '';
	if(isset($_POST['FORCE_DEFAULT_SCOPE']) && strtoupper($_POST['FORCE_DEFAULT_SCOPE']) === 'Y')
	{
		$scopePrefix = $component->getDefaultConfigID();
	}

	$optionPrefix = '';
	if(isset($_POST['FORCE_DEFAULT_OPTIONS']) && strtoupper($_POST['FORCE_DEFAULT_OPTIONS']) === 'Y')
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
			'SCOPE' => $scope,
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
			'ENABLE_COMMUNICATION_CONTROLS' => $enableCommunicationControls,
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
			'USER_FIELD_ENTITY_ID' => \CCrmLead::GetUserFieldEntityID(),
			'SERVICE_URL' => '/bitrix/components/bitrix/crm.lead.details/ajax.php?'.bitrix_sessid_get(),
			'CONTEXT_ID' => \CCrmOwnerType::LeadName.'_'.$ID,
			'ENTITY_TYPE_ID' => \CCrmOwnerType::Lead,
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
