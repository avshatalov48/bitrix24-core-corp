<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
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

use Bitrix\Main;
use Bitrix\Crm\Integration\StorageType;
use Bitrix\Crm\Integration\StorageManager;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Crm\Synchronization\UserFieldSynchronizer;
use Bitrix\Crm\Conversion\QuoteConversionConfig;
use Bitrix\Crm\Conversion\QuoteConversionWizard;
use Bitrix\Crm\Tracking;

Main\Localization\Loc::loadMessages(__FILE__);
if(!function_exists('__CrmQuoteDetailsEndJsonResonse'))
{
	function __CrmQuoteDetailsEndJsonResonse($result)
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
	__CrmQuoteDetailsEndJsonResonse(array('ERROR'=>'ACTION IS NOT DEFINED!'));
}
if($action === 'GET_FORMATTED_SUM')
{
	$sum = isset($_POST['SUM']) ? $_POST['SUM'] : 0.0;
	$currencyID = isset($_POST['CURRENCY_ID']) ? $_POST['CURRENCY_ID'] : '';
	if($currencyID === '')
	{
		$currencyID = \CCrmCurrency::GetBaseCurrencyID();
	}

	__CrmQuoteDetailsEndJsonResonse(
		array(
			'FORMATTED_SUM' => \CCrmCurrency::MoneyToString($sum, $currencyID, '#'),
			'FORMATTED_SUM_WITH_CURRENCY' => \CCrmCurrency::MoneyToString($sum, $currencyID, '')
		)
	);
}
elseif($action === 'SAVE')
{
	$ID = isset($_POST['ACTION_ENTITY_ID']) ? max((int)$_POST['ACTION_ENTITY_ID'], 0) : 0;
	if(($ID > 0 && !\CCrmQuote::CheckUpdatePermission($ID, $currentUserPermissions))
		|| ($ID === 0 && !\CCrmQuote::CheckCreatePermission($currentUserPermissions))
	)
	{
		__CrmQuoteDetailsEndJsonResonse(array('ERROR'=>'PERMISSION DENIED!'));
	}

	$diskQuotaRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getDiskQuotaRestriction();
	if (!$diskQuotaRestriction->hasPermission())
	{
		__CrmQuoteDetailsEndJsonResonse([
			'ERROR' => $diskQuotaRestriction->getErrorMessage(),
			'RESTRICTION' => true,
			'RESTRICTION_ACTION' => $diskQuotaRestriction->prepareInfoHelperScript()
		]);
	}

	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();
	$sourceEntityID =  isset($params['LEAD_ID']) ? (int)$params['LEAD_ID'] : 0;

	$isNew = $ID === 0;
	$isCopyMode = $isNew && $sourceEntityID > 0;
	$isExternal = false;

	$previousFields = !$isNew ? \CCrmQuote::GetByID($ID, false) : null;

	$fields = array();
	$fieldsInfo = \CCrmQuote::GetFieldsInfo();
	$userType = new \CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], \CCrmQuote::GetUserFieldEntityID());
	$userType->PrepareFieldsInfo($fieldsInfo);

	$sourceFields = array();
	if($sourceEntityID > 0)
	{
		$dbResult = \CCrmQuote::GetList(
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
	}

	foreach(array_keys($fieldsInfo) as $fieldName)
	{
		if(isset($_POST[$fieldName]))
		{
			$fields[$fieldName] = $_POST[$fieldName];
		}
	}
	/** @global $USER_FIELD_MANAGER CUserTypeManager */
	global $USER_FIELD_MANAGER;
	$USER_FIELD_MANAGER->EditFormAddFields(\CCrmQuote::USER_FIELD_ENTITY_ID, $fields, [
		'FORM' => $fields,
		'FILES' => [],
	]);

	//region CLIENT
	$primaryClientTypeName = isset($_POST['CLIENT_PRIMARY_ENTITY_TYPE']) ? $_POST['CLIENT_PRIMARY_ENTITY_TYPE'] : '';
	$primaryClientTypeID = \CCrmOwnerType::ResolveID($primaryClientTypeName);

	$secondaryClientTypeName = isset($_POST['CLIENT_SECONDARY_ENTITY_TYPE']) ? $_POST['CLIENT_SECONDARY_ENTITY_TYPE'] : '';
	$secondaryClientTypeID = \CCrmOwnerType::ResolveID($secondaryClientTypeName);

	$primaryClientID = 0;

	$boundSecondaryClientIDs = array();
	$unboundSecondaryClientIDs = array();
	if($primaryClientTypeID !== \CCrmOwnerType::Undefined && $secondaryClientTypeID !== \CCrmOwnerType::Undefined)
	{
		$companyID = 0;
		$contactIDs = array();

		$primaryClientID = isset($_POST['CLIENT_PRIMARY_ENTITY_ID']) ? (int)$_POST['CLIENT_PRIMARY_ENTITY_ID'] : 0;
		if($primaryClientID < 0)
		{
			$primaryClientID = 0;
		}

		if($primaryClientID > 0)
		{
			if($primaryClientTypeID === \CCrmOwnerType::Company)
			{
				$companyID = $primaryClientID;
			}
			elseif($primaryClientTypeID === \CCrmOwnerType::Contact)
			{
				$contactIDs[$primaryClientID] = true;
			}
		}

		$secondaryClientIDs = isset($_POST['CLIENT_SECONDARY_ENTITY_IDS']) ? $_POST['CLIENT_SECONDARY_ENTITY_IDS'] : '';
		$secondaryClientIDs = $secondaryClientIDs !== '' ? explode(',', $secondaryClientIDs) : array();

		foreach($secondaryClientIDs as $clientID)
		{
			$contactIDs[$clientID] = true;
		}
		$contactIDs = array_keys($contactIDs);

		$fields['COMPANY_ID'] = $companyID;
		$fields['CONTACT_IDS'] = $contactIDs;

		$s = isset($_POST['CLIENT_UBOUND_SECONDARY_ENTITY_IDS']) ? $_POST['CLIENT_UBOUND_SECONDARY_ENTITY_IDS'] : '';
		$unboundSecondaryClientIDs = $s !== '' ? explode(',', $s) : array();

		$s = isset($_POST['CLIENT_BOUND_SECONDARY_ENTITY_IDS']) ? $_POST['CLIENT_BOUND_SECONDARY_ENTITY_IDS'] : '';
		$boundSecondaryClientIDs = $s !== '' ? explode(',', $s) : array();
	}
	//endregion

	//region PRODUCT ROWS
	$enableProductRows = false;
	$originalProductRows = !$isNew ? \CCrmQuote::LoadProductRows($ID) : array();

	$productRows = array();
	$productRowSettings = array();
	if(isset($_POST['QUOTE_PRODUCT_DATA'])
		&& !(isset($_POST['SKIP_PRODUCT_DATA']) && strcasecmp($_POST['SKIP_PRODUCT_DATA'], 'Y') === 0)
	)
	{
		$productRows = \CUtil::JsObjectToPhp($_POST['QUOTE_PRODUCT_DATA']);
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

			$totals = \CCrmProductRow::CalculateTotalInfo(\CCrmQuote::OWNER_TYPE, 0, false, $calculationParams, $productRows);
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

		if(isset($_POST['QUOTE_PRODUCT_DATA_SETTINGS']) && $_POST['QUOTE_PRODUCT_DATA_SETTINGS'] !== '')
		{
			$settings = \CUtil::JsObjectToPhp($_POST['QUOTE_PRODUCT_DATA_SETTINGS']);
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

	$storageTypeID = isset($_POST['storageTypeId']) ? (int)$_POST['storageTypeId'] : StorageType::Undefined;
	if(!StorageType::isDefined($storageTypeID))
	{
		if($isNew)
		{
			$storageTypeID = \CCrmQuote::GetDefaultStorageTypeID();
		}
		else
		{
			$storageTypeID = isset($previousFields['STORAGE_TYPE_ID'])
				? (int)$previousFields['STORAGE_TYPE_ID'] : StorageType::Undefined;
			if(!StorageType::isDefined($storageTypeID))
			{
				$storageTypeID = \CCrmQuote::GetDefaultStorageTypeID();
			}
		}
	}
	$fields['STORAGE_TYPE_ID'] = $storageTypeID;

	if(isset($_POST['STORAGE_ELEMENT_IDS']))
	{
		$storageElementIDs = isset($_POST['STORAGE_ELEMENT_IDS']) && is_array($_POST['STORAGE_ELEMENT_IDS'])
			? $_POST['STORAGE_ELEMENT_IDS'] : array();

		if(!$isNew && isset($previousFields['STORAGE_ELEMENT_IDS']) && is_array($previousFields['STORAGE_ELEMENT_IDS']))
		{
			$previousStorageElementIDs = $previousFields['STORAGE_ELEMENT_IDS'];

			$persistentStorageElementIDs = array_intersect($previousStorageElementIDs, $storageElementIDs);
			$newStorageElementIDs = StorageManager::filterFiles(
				array_diff($storageElementIDs, $previousStorageElementIDs),
				$storageTypeID
			);

			$storageElementIDs = array_merge($persistentStorageElementIDs, $newStorageElementIDs);
		}
		$fields['STORAGE_ELEMENT_IDS'] = $storageElementIDs;
	}

	if(!empty($fields) || $enableProductRows)
	{
		if(!empty($fields))
		{
			if($isCopyMode)
			{
				if(!isset($fields['ASSIGNED_BY_ID']))
				{
					$fields['ASSIGNED_BY_ID'] = $currentUserID;
				}

				//TODO: Create Quote Merger
				//$merger = new \Bitrix\Crm\Merger\QuoteMerger($currentUserID, false);
				//$merger->mergeFields($sourceFields, $fields, true);
			}

			if(isset($fields['CONTENT']))
			{
				$fields['CONTENT'] = \Bitrix\Crm\Format\TextHelper::sanitizeHtml($fields['CONTENT']);
			}

			if(isset($fields['TERMS']))
			{
				$fields['TERMS'] = \Bitrix\Crm\Format\TextHelper::sanitizeHtml($fields['TERMS']);
			}

			if(isset($fields['COMMENTS']))
			{
				$fields['COMMENTS'] = \Bitrix\Crm\Format\TextHelper::sanitizeHtml($fields['COMMENTS']);
			}

			$entity = new \CCrmQuote(false);
			if($isNew)
			{
				if(!isset($fields['TITLE']) || $fields['TITLE'] === '')
				{
					$fields['TITLE'] = GetMessage('CRM_QUOTE_DEAULT_TITLE');
				}

				if(!isset($fields['OPENED']))
				{
					$fields['OPENED'] = \Bitrix\Crm\Settings\QuoteSettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';
				}

				if(!isset($fields['CURRENCY_ID']))
				{
					$fields['CURRENCY_ID'] = CCrmCurrency::GetBaseCurrencyID();
				}

				$fields['EXCH_RATE'] = CCrmCurrency::GetExchangeRate($fields['CURRENCY_ID']);

				$ID = $entity->Add($fields);
				if($ID <= 0)
				{
					__CrmQuoteDetailsEndJsonResonse(array('ERROR' => $entity->LAST_ERROR));
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

				if(!$entity->Update($ID, $fields))
				{
					__CrmQuoteDetailsEndJsonResonse(array('ERROR' => $entity->LAST_ERROR));
				}
			}
		}

		if(!$isExternal && $enableProductRows && (!$isNew || !empty($productRows)))
		{
			if(!\CCrmQuote::SaveProductRows($ID, $productRows, true, true, false))
			{
				__CrmQuoteDetailsEndJsonResonse(array('ERROR' => GetMessage('CRM_QUOTE_PRODUCT_ROWS_SAVING_ERROR')));
			}
		}

		if(!empty($productRowSettings))
		{
			if(!$isNew)
			{
				$productRowSettings = array_merge(
					\CCrmProductRow::LoadSettings(\CCrmQuote::OWNER_TYPE, $ID),
					$productRowSettings
				);
			}
			\CCrmProductRow::SaveSettings(\CCrmQuote::OWNER_TYPE, $ID, $productRowSettings);
		}

		Tracking\UI\Details::saveEntityData(
			\CCrmOwnerType::Quote,
			$ID,
			$_POST,
			$isNew
		);

		if($primaryClientID > 0 && $primaryClientTypeID === \CCrmOwnerType::Company)
		{
			if(!empty($unboundSecondaryClientIDs))
			{
				\Bitrix\Crm\Binding\ContactCompanyTable::unbindContactIDs($primaryClientID, $unboundSecondaryClientIDs);
			}
			if(!empty($boundSecondaryClientIDs))
			{
				\Bitrix\Crm\Binding\ContactCompanyTable::bindContactIDs($primaryClientID, $boundSecondaryClientIDs);
			}
		}
	}

	CBitrixComponent::includeComponentClass('bitrix:crm.quote.details');
	$component = new CCrmQuoteDetailsComponent();
	$component->initializeParams(
		isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array()
	);
	$component->setEntityID($ID);
	$result = array('ENTITY_ID' => $ID, 'ENTITY_DATA' => $component->prepareEntityData());
	if($isNew)
	{
		$result['REDIRECT_URL'] = \CCrmOwnerType::GetDetailsUrl(
			\CCrmOwnerType::Quote,
			$ID,
			false,
			array('ENABLE_SLIDER' => true)
		);
	}

	__CrmQuoteDetailsEndJsonResonse($result);
}
elseif($action === 'CONVERT')
{
	if(!\Bitrix\Crm\Restriction\RestrictionManager::isConversionPermitted())
	{
		__CrmQuoteDetailsEndJsonResonse(array('ERROR' => array('MESSAGE' => GetMessage('CRM_QUOTE_CONVERSION_ACCESS_DENIED'))));
	}

	$entityID = isset($_POST['ENTITY_ID']) ? (int)$_POST['ENTITY_ID'] : 0;
	if($entityID <= 0)
	{
		__CrmQuoteDetailsEndJsonResonse(array('ERROR' => array('MESSAGE' => GetMessage('CRM_QUOTE_CONVERSION_ID_NOT_DEFINED'))));
	}

	if(!CCrmQuote::Exists($entityID))
	{
		__CrmQuoteDetailsEndJsonResonse(array('ERROR' => array('MESSAGE' => GetMessage('CRM_QUOTE_CONVERSION_NOT_FOUND'))));
	}

	if(!CCrmQuote::CheckReadPermission($entityID, $currentUserPermissions))
	{
		__CrmQuoteDetailsEndJsonResonse(array('ERROR' => array('MESSAGE' => GetMessage('CRM_QUOTE_CONVERSION_ACCESS_DENIED'))));
	}

	$configParams = isset($_POST['CONFIG']) && is_array($_POST['CONFIG']) ? $_POST['CONFIG'] : null;
	if(is_array($configParams))
	{
		$config = new QuoteConversionConfig();
		$config->fromJavaScript($configParams);
		$config->save();
	}
	else
	{
		$config = QuoteConversionConfig::load();
		if($config === null)
		{
			$config = QuoteConversionConfig::getDefault();
		}
	}

	if(!isset($_POST['ENABLE_SYNCHRONIZATION']) || $_POST['ENABLE_SYNCHRONIZATION'] !== 'Y')
	{
		$needForSync = false;
		$entityConfigs = $config->getItems();
		$syncFieldNames = array();
		foreach($entityConfigs as $entityTypeID => $entityConfig)
		{
			if(!EntityAuthorization::checkCreatePermission($entityTypeID, $currentUserPermissions)
				&& !EntityAuthorization::checkUpdatePermission($entityTypeID, 0, $currentUserPermissions))
			{
				continue;
			}

			$enableSync = $entityConfig->isActive();
			if($enableSync)
			{
				$syncFields = UserFieldSynchronizer::getSynchronizationFields(CCrmOwnerType::Quote, $entityTypeID);
				$enableSync = !empty($syncFields);
				foreach($syncFields as $field)
				{
					$syncFieldNames[$field['ID']] = UserFieldSynchronizer::getFieldLabel($field);
				}
			}

			if($enableSync && !$needForSync)
			{
				$needForSync = true;
			}
			$entityConfig->enableSynchronization($enableSync);
		}

		if($needForSync)
		{
			__CrmQuoteDetailsEndJsonResonse(
				array(
					'REQUIRED_ACTION' => array(
						'NAME' => 'SYNCHRONIZE',
						'DATA' => array(
							'CONFIG' => $config->toJavaScript(),
							'FIELD_NAMES' => array_values($syncFieldNames)
						)
					)
				)
			);
		}
	}
	else
	{
		$entityConfigs = $config->getItems();
		foreach($entityConfigs as $entityTypeID => $entityConfig)
		{
			if(!EntityAuthorization::checkCreatePermission($entityTypeID, $currentUserPermissions)
				&& !EntityAuthorization::checkUpdatePermission($entityTypeID, 0, $currentUserPermissions))
			{
				continue;
			}

			if(!$entityConfig->isActive())
			{
				continue;
			}

			if(!UserFieldSynchronizer::needForSynchronization(CCrmOwnerType::Quote, $entityTypeID))
			{
				continue;
			}

			if($entityConfig->isSynchronizationEnabled())
			{
				UserFieldSynchronizer::synchronize(\CCrmOwnerType::Quote, $entityTypeID);
			}
			else
			{
				UserFieldSynchronizer::markAsSynchronized(\CCrmOwnerType::Quote, $entityTypeID);
			}
		}
	}

	QuoteConversionWizard::remove($entityID);
	$wizard = new QuoteConversionWizard($entityID, $config);
	$wizard->setOriginUrl(isset($_POST['ORIGIN_URL']) ? $_POST['ORIGIN_URL'] : '');

	$wizard->setSliderEnabled(true);

	if($wizard->execute())
	{
		__CrmQuoteDetailsEndJsonResonse(
			array(
				'DATA' => array(
					'URL' => $wizard->getRedirectUrl(),
					'IS_FINISHED' => $wizard->isFinished() ? 'Y' : 'N'
				)
			)
		);
	}
	else
	{
		$url = $wizard->getRedirectUrl();
		if($url !== '')
		{
			__CrmQuoteDetailsEndJsonResonse(
				array(
					'DATA' => array(
						'URL' => $url,
						'IS_FINISHED' => $wizard->isFinished() ? 'Y' : 'N'
					)
				)
			);
		}
		else
		{
			__CrmQuoteDetailsEndJsonResonse(array('ERROR' => array('MESSAGE' => $wizard->getErrorText())));
		}
	}
}
elseif($action === 'DELETE')
{
	$ID = isset($_POST['ACTION_ENTITY_ID']) ? max((int)$_POST['ACTION_ENTITY_ID'], 0) : 0;
	if($ID <= 0)
	{
		__CrmQuoteDetailsEndJsonResonse(array('ERROR' => GetMessage('CRM_QUOTE_NOT_FOUND')));
	}

	if(!\CCrmQuote::CheckDeletePermission($ID, $currentUserPermissions))
	{
		__CrmQuoteDetailsEndJsonResonse(array('ERROR' => GetMessage('CRM_QUOTE_ACCESS_DENIED')));
	}

	$entity = new \CCrmQuote(false);
	if (!$entity->Delete($ID))
	{
		/** @var CApplicationException $ex */
		$ex = $APPLICATION->GetException();
		__CrmQuoteDetailsEndJsonResonse(
			array(
				'ERROR' => ($ex instanceof CApplicationException) ? $ex->GetString() : GetMessage('CRM_QUOTE_DELETION_ERROR')
			)
		);
	}
	__CrmQuoteDetailsEndJsonResonse(array('ENTITY_ID' => $ID));
}
