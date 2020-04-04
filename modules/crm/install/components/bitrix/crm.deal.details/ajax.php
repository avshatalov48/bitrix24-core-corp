<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Crm\Synchronization\UserFieldSynchronizer;
use Bitrix\Crm\Conversion\DealConversionConfig;
use Bitrix\Crm\Conversion\DealConversionWizard;
use Bitrix\Crm\Recurring;
use Bitrix\Crm;

if (!CModule::IncludeModule('crm'))
{
	return;
}
/*
 * ONLY 'POST' METHOD SUPPORTED
 * SUPPORTED ACTIONS:
 * 'GET_DEFAULT_SECONDARY_ENTITIES'
 */
global $DB, $APPLICATION, $USER_FIELD_MANAGER;
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
if(!function_exists('__CrmDealDetailsEndJsonResonse'))
{
	function __CrmDealDetailsEndJsonResonse($result)
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
	__CrmDealDetailsEndJsonResonse(array('ERROR'=>'ACTION IS NOT DEFINED!'));
}
if($action === 'GET_FORMATTED_SUM')
{
	$sum = isset($_POST['SUM']) ? $_POST['SUM'] : 0.0;
	$currencyID = isset($_POST['CURRENCY_ID']) ? $_POST['CURRENCY_ID'] : '';
	if($currencyID === '')
	{
		$currencyID = CCrmCurrency::GetBaseCurrencyID();
	}

	__CrmDealDetailsEndJsonResonse(
		array(
			'FORMATTED_SUM' => CCrmCurrency::MoneyToString($sum, $currencyID, '#'),
			'FORMATTED_SUM_WITH_CURRENCY' => CCrmCurrency::MoneyToString($sum, $currencyID, '')
		)
	);
}
elseif($action === 'MOVE_TO_CATEGORY')
{
	$ID = isset($_POST['ACTION_ENTITY_ID']) ? max((int)$_POST['ACTION_ENTITY_ID'], 0) : 0;
	if($ID <= 0)
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR'=>'ENTITY ID IS NOT FOUND!'));
	}

	if(!\CCrmDeal::CheckUpdatePermission($ID, $currentUserPermissions))
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR'=>'PERMISSION DENIED!'));
	}

	$newCategoryID =  isset($_POST['CATEGORY_ID']) ? max((int)$_POST['CATEGORY_ID'], 0) : 0;
	if($newCategoryID < 0)
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR'=>'CATEGORY ID IS NOT FOUND!'));
	}

	if(!\CCrmDeal::CheckCreatePermission($currentUserPermissions, $newCategoryID))
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR'=>'PERMISSION DENIED!'));
	}

	$DB->StartTransaction();
	try
	{
		$recurringData = \Bitrix\Crm\Recurring\Manager::getList(
			array(
				'filter' => array('DEAL_ID' => $ID),
				'limit' => 1
			),
			\Bitrix\Crm\Recurring\Manager::DEAL
		);
		$options = null;
		if ($recurringData->fetch())
			$options = array('REGISTER_STATISTICS' => false);

		$error = \CCrmDeal::MoveToCategory($ID, $newCategoryID, $options);
		if($error !== \Bitrix\Crm\Category\DealCategoryChangeError::NONE)
		{
			__CrmDealDetailsEndJsonResonse(
				array('ERROR' => GetMessage('CRM_DEAL_MOVE_TO_CATEGORY_ERROR'))
			);
		}

		\CCrmBizProcHelper::AutoStartWorkflows(
			\CCrmOwnerType::Deal,
			$ID,
			\CCrmBizProcEventType::Edit,
			$errors
		);
		Bitrix\Crm\Automation\Factory::runOnStatusChanged(CCrmOwnerType::Deal, $ID);

		$DB->Commit();
	}
	catch(Exception $e)
	{
		$DB->Rollback();
	}

	__CrmDealDetailsEndJsonResonse(array('CATEGORY_ID' => $newCategoryID));
}
elseif($action === 'SAVE')
{
	$ID = isset($_POST['ACTION_ENTITY_ID']) ? max((int)$_POST['ACTION_ENTITY_ID'], 0) : 0;

	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();
	$categoryID =  isset($params['CATEGORY_ID']) ? (int)$params['CATEGORY_ID'] : 0;

	if(($ID > 0 && !\CCrmDeal::CheckUpdatePermission($ID, $currentUserPermissions))
		|| ($ID === 0 && !\CCrmDeal::CheckCreatePermission($currentUserPermissions, $categoryID))
	)
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR'=>'PERMISSION DENIED!'));
	}

	$sourceEntityID =  isset($params['DEAL_ID']) ? (int)$params['DEAL_ID'] : 0;
	$enableRequiredUserFieldCheck = !isset($_POST['ENABLE_REQUIRED_USER_FIELD_CHECK'])
		|| strtoupper($_POST['ENABLE_REQUIRED_USER_FIELD_CHECK']) === 'Y';

	$isNew = $ID === 0;
	$isCopyMode = $isNew && $sourceEntityID > 0;
	//TODO: Implement external mode
	$isExternal = false;

	$previousFields = !$isNew ? \CCrmDeal::GetByID($ID, false) : null;

	$fields = array();
	$fieldsInfo = \CCrmDeal::GetFieldsInfo();
	$userType = new \CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], \CCrmDeal::GetUserFieldEntityID());
	$userType->PrepareFieldsInfo($fieldsInfo);

	$sourceFields = array();
	if($sourceEntityID > 0)
	{
		$dbResult = \CCrmDeal::GetListEx(
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

	$initialData = isset($_POST['INITIAL_DATA']) && is_array($_POST['INITIAL_DATA'])
		? $_POST['INITIAL_DATA'] : array();
	foreach(array_keys($fieldsInfo) as $fieldName)
	{
		if(isset($_POST[$fieldName]))
		{
			$fields[$fieldName] = $_POST[$fieldName];
		}
		elseif(isset($initialData[$fieldName]))
		{
			$fields[$fieldName] = $initialData[$fieldName];
		}
	}

	if(isset($_POST['OBSERVER_IDS']))
	{
		$fields['OBSERVER_IDS'] = is_array($_POST['OBSERVER_IDS']) ? $_POST['OBSERVER_IDS'] : array();
	}

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

	$createdEntities = array();

	$companyID = 0;
	$companyEntity = new \CCrmCompany(false);
	$enableCompanyCreation = \CCrmCompany::CheckCreatePermission($currentUserPermissions);
	if(isset($clientData['COMPANY_DATA']) && is_array($clientData['COMPANY_DATA']))
	{
		$companyData = $clientData['COMPANY_DATA'];
		$companyID = isset($companyData['id']) ? (int)$companyData['id'] : 0;
		$companyTitle = isset($companyData['title']) ? trim($companyData['title']) : '';
		if($companyID <= 0 && $companyTitle !== '' && $enableCompanyCreation)
		{
			$companyFields = array('TITLE' => $companyTitle);
			$multifieldData =  isset($companyData['multifields']) && is_array($companyData['multifields'])
				? $companyData['multifields']  : array();

			if(!empty($multifieldData))
			{
				\Bitrix\Crm\Component\EntityDetails\BaseComponent::prepareMultifieldsForSave(
					$multifieldData,
					$companyFields
				);
			}
			$companyID = $companyEntity->Add($companyFields, true, array('DISABLE_USER_FIELD_CHECK' => true));
			if($companyID > 0)
			{
				$arErrors = array();
				\CCrmBizProcHelper::AutoStartWorkflows(
					\CCrmOwnerType::Company,
					$companyID,
					\CCrmBizProcEventType::Create,
					$arErrors
				);

				$createdEntities[CCrmOwnerType::Company] = array($companyID);
			}
		}

		$fields['COMPANY_ID'] = $companyID;
		if($companyID > 0)
		{
			Crm\Controller\Entity::addLastRecentlyUsedItems(
				'crm.deal.details',
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
	$enableContactCreation = \CCrmContact::CheckCreatePermission($currentUserPermissions);
	if(isset($clientData['CONTACT_DATA']) && is_array($clientData['CONTACT_DATA']))
	{
		$contactIDs = array();
		$contactData = $clientData['CONTACT_DATA'];
		$defaultContactName = \CCrmContact::GetDefaultName();
		foreach($contactData as $contactItem)
		{
			$contactID = isset($contactItem['id']) ? (int)$contactItem['id'] : 0;
			$contactTitle = isset($contactItem['title']) ? trim($contactItem['title']) : '';
			if($contactID <= 0 && $contactTitle !== '' && $enableContactCreation)
			{
				$contactFields = array();
				if($contactTitle === $defaultContactName)
				{
					$contactFields['NAME'] = $contactTitle;
				}
				else
				{
					\Bitrix\Crm\Format\PersonNameFormatter::tryParseName(
						$contactTitle,
						\Bitrix\Crm\Format\PersonNameFormatter::getFormatID(),
						$contactFields
					);
				}
				
				$multifieldData =  isset($contactItem['multifields']) && is_array($contactItem['multifields'])
					? $contactItem['multifields']  : array();

				if(!empty($multifieldData))
				{
					\Bitrix\Crm\Component\EntityDetails\BaseComponent::prepareMultifieldsForSave(
						$multifieldData,
						$contactFields
					);
				}
				$contactID = $contactEntity->Add($contactFields, true, array('DISABLE_USER_FIELD_CHECK' => true));
				if($contactID > 0)
				{
					if(!is_array($bindContactIDs))
					{
						$bindContactIDs = array();
					}
					$bindContactIDs[] = $contactID;

					$arErrors = array();
					\CCrmBizProcHelper::AutoStartWorkflows(
						\CCrmOwnerType::Contact,
						$contactID,
						\CCrmBizProcEventType::Create,
						$arErrors
					);

					if(!isset($createdEntities[CCrmOwnerType::Contact]))
					{
						$createdEntities[CCrmOwnerType::Contact] = array();
					}
					$createdEntities[CCrmOwnerType::Contact][] = $contactID;
				}
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
				'crm.deal.details',
				'contact',
				$contactBindings
			);
		}
	}
	//endregion

	//region REQUISITE_ID & BANK_DETAIL_ID
	$requisiteID = isset($_POST['REQUISITE_ID']) ? max((int)$_POST['REQUISITE_ID'], 0) : 0;
	$bankDetailID = isset($_POST['BANK_DETAIL_ID']) ? max((int)$_POST['BANK_DETAIL_ID'], 0) : 0;
	//endregion

	//region PRODUCT ROWS
	$enableProductRows = !array_key_exists('DEAL_PRODUCT_DATA_INVALIDATE', $_POST) && array_key_exists('DEAL_PRODUCT_DATA', $_POST);
	$productRows = array();
	$productRowSettings = array();
	if($enableProductRows)
	{
		if(isset($_POST['DEAL_PRODUCT_DATA']) && $_POST['DEAL_PRODUCT_DATA'] !== '')
		{
			$productRows = \CUtil::JsObjectToPhp($_POST['DEAL_PRODUCT_DATA']);
		}

		if(!is_array($productRows))
		{
			$productRows = array();
		}

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

			$totals = \CCrmProductRow::CalculateTotalInfo('D', 0, false, $calculationParams, $productRows);
			$fields['OPPORTUNITY'] = isset($totals['OPPORTUNITY']) ? $totals['OPPORTUNITY'] : 0.0;
			$fields['TAX_VALUE'] = isset($totals['TAX_VALUE']) ? $totals['TAX_VALUE'] : 0.0;
		}
		else
		{
			$fields['TAX_VALUE'] = 0.0;
			if(!isset($fields['OPPORTUNITY']))
			{
				if($isNew)
				{
					$fields['OPPORTUNITY'] = 0.0;
				}
				else
				{
					$originalProductRows = \CCrmDeal::LoadProductRows($ID);
					if(!empty($originalProductRows))
					{
						$fields['OPPORTUNITY'] = 0.0;
					}
				}
			}
		}

		if(isset($_POST['DEAL_PRODUCT_DATA_SETTINGS']) && $_POST['DEAL_PRODUCT_DATA_SETTINGS'] !== '')
		{
			$settings = \CUtil::JsObjectToPhp($_POST['DEAL_PRODUCT_DATA_SETTINGS']);
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

	//region RECURRING
	if (
		(
			$_POST['IS_RECURRING'] === 'N'
			&& isset($_POST['RECURRING']['MODE'])
			&& $_POST['RECURRING']['MODE'] !== Recurring\Calculator::SALE_TYPE_NON_ACTIVE_DATE
		)
		|| ($_POST['IS_RECURRING'] === 'Y')
	)
	{
		if (!Recurring\Manager::isAllowedExpose(Recurring\Manager::DEAL))
			__CrmDealDetailsEndJsonResonse(array('ERROR' => "RECURRING DEALS IS RESTRICTED"));

		$fields['RECURRING'] = $_POST['RECURRING'];

		if (isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = \Bitrix\Crm\Format\TextHelper::sanitizeHtml($fields['COMMENTS']);
		}

		$limit = Recurring\Entity\Deal::NO_LIMITED;
		$limitDate = null;

		$categoryId = 0;
		if (isset($fields['RECURRING']['CATEGORY_ID']))
		{
			$categoryId = (int)$fields['RECURRING']['CATEGORY_ID'];
		}
		elseif ($categoryID > 0)
		{
			$categoryId = (int)$categoryID;
		}
		$categoryId = max($categoryId, 0);

		if (
			$fields['RECURRING']['MODE'] === Recurring\Calculator::SALE_TYPE_NON_ACTIVE_DATE
			|| (
				(int)$fields['RECURRING']['MODE'] === Recurring\Manager::MULTIPLY_EXECUTION
				&& (int)$fields['RECURRING']['MULTIPLE_TYPE'] === Recurring\Calculator::SALE_TYPE_CUSTOM_OFFSET
				&& (int)$fields['RECURRING']['MULTIPLE_CUSTOM_INTERVAL_VALUE'] <= 0
			)
		)
		{
			$recurringFields = array(
				"ACTIVE" => "N",
				"NEXT_EXECUTION" => null,
				"PARAMS" => $fields['RECURRING']
			);
		}
		else
		{
			$today = new \Bitrix\Main\Type\Date();
			$startDate = null;
			$recurringFields = [
				"CATEGORY_ID" => $categoryId,
				"PARAMS" => $fields['RECURRING']
			];
			if ((int)$fields['RECURRING']['MODE'] === Recurring\Manager::SINGLE_EXECUTION)
			{
				$singleCalculationFields = [
					Recurring\Entity\ParameterMapper\SecondFormDeal::FIELD_MODE_NAME,
					Recurring\Entity\ParameterMapper\SecondFormDeal::FIELD_SINGLE_TYPE_NAME,
					Recurring\Entity\ParameterMapper\SecondFormDeal::FIELD_SINGLE_INTERVAL_NAME,
				];
				$limitParams = array_intersect_key($fields['RECURRING'], array_flip($singleCalculationFields));
				$limitMapper = Recurring\Entity\ParameterMapper\SecondFormDeal::getInstance();
				$limitMapper->fillMap($limitParams);
				$startDateValue = null;
				if (CheckDateTime($_POST['RECURRING']['SINGLE_DATE_BEFORE']))
				{
					$startDateValue = $fields['RECURRING']['SINGLE_DATE_BEFORE'];
				}
				$startDate = new \Bitrix\Main\Type\Date($startDateValue);
				$recurringFields["START_DATE"] = $startDate;
				$recurringFields["IS_LIMIT"] = Recurring\Entity\Deal::LIMITED_BY_DATE;
				$instance = Recurring\Calculator::getInstance();
				$instance->setStartDate($startDate);
				$instance->setParams($limitMapper->getPreparedMap());
				$recurringFields["LIMIT_DATE"] = $instance->calculateDate();
			}
			elseif ((int)$_POST['RECURRING']['MODE'] === Recurring\Manager::MULTIPLY_EXECUTION)
			{
				$startDateValue = null;
				if (CheckDateTime($_POST['RECURRING']['MULTIPLE_DATE_START']))
				{
					$startDateValue = $_POST['RECURRING']['MULTIPLE_DATE_START'];
				}
				$startDate = new \Bitrix\Main\Type\Date($startDateValue);
				$recurringFields["START_DATE"] = $startDate;
				$recurringFields["IS_LIMIT"] = Recurring\Entity\Base::NO_LIMITED;
				if ($_POST['RECURRING']['MULTIPLE_TYPE_LIMIT'] === Recurring\Entity\Base::LIMITED_BY_TIMES)
				{
					$recurringFields["IS_LIMIT"] = Recurring\Entity\Base::LIMITED_BY_TIMES;
					$recurringFields["LIMIT_REPEAT"] = (int)$_POST['RECURRING']['MULTIPLE_TIMES_LIMIT'];
				}
				elseif ($_POST['RECURRING']['MULTIPLE_TYPE_LIMIT'] === Recurring\Entity\Base::LIMITED_BY_DATE)
				{
					$recurringFields["IS_LIMIT"] = Recurring\Entity\Base::LIMITED_BY_DATE;
					$recurringFields["LIMIT_DATE"] = new \Bitrix\Main\Type\Date($_POST['RECURRING']['MULTIPLE_DATE_LIMIT']);
				}
			}

			$today = new Main\Type\Date();
			$nextDate = Recurring\Entity\Deal::getNextDate($fields['RECURRING'], clone $startDate);
			if (!($nextDate instanceof Main\Type\Date) || $nextDate->getTimestamp() < $today->getTimestamp())
			{
				__CrmDealDetailsEndJsonResonse(array('ERROR' => GetMessage('CRM_DEAL_RECURRING_DATE_START_ERROR')));
			}
		}

		// RECURRING_SWITCHER is used for old deal edit template
		$fields['RECURRING']['RECURRING_SWITCHER'] = 'Y';

		$recurringRow = Recurring\Manager::getList(
			array(
				'filter' => array("=DEAL_ID" => $ID),
				'select' => array('ID')
			),
			Recurring\Manager::DEAL
		);
		$recurring = $recurringRow->fetch();
		if (is_array($recurring) && !$isNew)
		{
			Recurring\Manager::update($recurring['ID'],$recurringFields,Recurring\Manager::DEAL);
			unset($fields['RECURRING']);
		}
		else
		{
			if ($isNew)
			{
				unset($fields['RECURRING']);
				$dealFields = $fields;
			}
			else
			{
				$dealFields = \CCrmDeal::GetByID($ID);
				$dealFields['CONTACT_IDS'] = \Bitrix\Crm\Binding\DealContactTable::getDealContactIDs($ID);
				$userType = new \CCrmUserType($USER_FIELD_MANAGER, \CCrmDeal::GetUserFieldEntityID());
				$userFields = $userType->GetEntityFields($ID);

				foreach($userFields as $key => $field)
				{
					if ($field["USER_TYPE"]["BASE_TYPE"] == "file" && !empty($field['VALUE']))
					{
						if (is_array($field['VALUE']))
						{
							$dealFields[$key] = array();
							foreach ($field['VALUE'] as $value)
							{
								$fileData = \CFile::MakeFileArray($value);
								if (is_array($fileData))
								{
									$dealFields[$key][] = $fileData;
								}
							}
						}
						else
						{
							$fileData = \CFile::MakeFileArray($field['VALUE']);
							if (is_array($fileData))
							{
								$dealFields[$key] = $fileData;
							}
							else
							{
								$dealFields[$key] = $field['VALUE'];
							}
						}
					}
					else
					{
						$dealFields[$key] = $field['VALUE'];
					}
				}

				$isNew = true;
			}
			$result = Recurring\Manager::createEntity($dealFields,	$recurringFields, Recurring\Manager::DEAL);

			if (is_array($productRows) && !empty($productRows))
			{
				foreach ($productRows as &$product)
				{
					unset($product['ID'], $product['OWNER_ID']);
				}
			}

			if($isNew)
			{
				if ($result->isSuccess())
				{
					$resultData = $result->getData();
					$ID = $resultData['DEAL_ID'];
				}
				else
				{
					$messages = $result->getErrorMessages();
					__CrmDealDetailsEndJsonResonse(array('ERROR' => end($messages)));
				}
			}
			$isRecurringSaving = true;
		}
	}
	//endregion

	$conversionWizard = null;
	if(isset($params['LEAD_ID']) && $params['LEAD_ID'] > 0)
	{
		$leadID = (int)$params['LEAD_ID'];
		$fields['LEAD_ID'] = $leadID;
		$conversionWizard = \Bitrix\Crm\Conversion\LeadConversionWizard::load($leadID);
	}
	elseif(isset($params['QUOTE_ID']) && $params['QUOTE_ID'] > 0)
	{
		$quoteID = (int)$params['QUOTE_ID'];
		$fields['QUOTE_ID'] = $quoteID;
		$conversionWizard = \Bitrix\Crm\Conversion\QuoteConversionWizard::load($quoteID);
	}

	if($conversionWizard !== null)
	{
		$conversionWizard->setSliderEnabled(true);
		$conversionWizard->prepareDataForSave(CCrmOwnerType::Deal, $fields);
	}

	$checkExceptions = null;
	$errorMessage = '';

	if(!empty($fields) || $enableProductRows || $requisiteID > 0)
	{
		$requisiteInfo = null;
		if (!empty($fields) && !isset($isRecurringSaving))
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

				$merger = new \Bitrix\Crm\Merger\DealMerger($currentUserID, false);
				//Merge with disabling of multiple user fields (SKIP_MULTIPLE_USER_FIELDS = TRUE)
				$merger->mergeFields(
					$sourceFields,
					$fields,
					true,
					array('SKIP_MULTIPLE_USER_FIELDS' => true)
				);
			}

			if (isset($fields['COMMENTS']))
			{
				$fields['COMMENTS'] = \Bitrix\Crm\Format\TextHelper::sanitizeHtml($fields['COMMENTS']);
			}

			$entity = new \CCrmDeal(false);
			if($isNew)
			{
				$now = time() + CTimeZone::GetOffset();
				if(!isset($fields['TYPE_ID']))
				{
					$fields['TYPE_ID'] = \CCrmStatus::GetFirstStatusID('DEAL_TYPE');
				}

				if(!isset($fields['BEGINDATE']))
				{
					$fields['BEGINDATE'] = ConvertTimeStamp($now, 'SHORT', SITE_ID);
				}

				if(!isset($fields['CLOSEDATE']))
				{
					$fields['CLOSEDATE'] = ConvertTimeStamp($now + (7 * 86400), 'SHORT', SITE_ID);
				}

				if(!isset($fields['OPENED']))
				{
					$fields['OPENED'] = \Bitrix\Crm\Settings\DealSettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';
				}

				if(!isset($fields['CURRENCY_ID']))
				{
					$fields['CURRENCY_ID'] = CCrmCurrency::GetBaseCurrencyID();
				}

				$fields['EXCH_RATE'] = CCrmCurrency::GetExchangeRate($fields['CURRENCY_ID']);

				$options = array('REGISTER_SONET_EVENT' => true);
				if(!$enableRequiredUserFieldCheck)
				{
					$options['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
				}

				$requisiteInfo = \Bitrix\Crm\Requisite\EntityLink::determineRequisiteLinkBeforeSave(
					CCrmOwnerType::Deal,
					0,
					\Bitrix\Crm\Requisite\EntityLink::ENTITY_OPERATION_ADD,
					$fields,
					false,
					$requisiteID > 0 ? $requisiteID : null,
					$bankDetailID > 0 ? $bankDetailID : null
				);

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

				$options = array('REGISTER_SONET_EVENT' => true);
				if(!$enableRequiredUserFieldCheck)
				{
					$options['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
				}
				if($isRecurringSaving || $previousFields['IS_RECURRING'] === 'Y')
				{
					$options['REGISTER_STATISTICS'] = false;
				}

				$requisiteInfo = \Bitrix\Crm\Requisite\EntityLink::determineRequisiteLinkBeforeSave(
					CCrmOwnerType::Deal,
					$ID,
					\Bitrix\Crm\Requisite\EntityLink::ENTITY_OPERATION_UPDATE,
					$fields,
					false,
					$requisiteID > 0 ? $requisiteID : null,
					$bankDetailID > 0 ? $bankDetailID : null
				);

				if(!$entity->Update($ID, $fields, true, true, $options))
				{
					$checkExceptions = $entity->GetCheckExceptions();
					$errorMessage = $entity->LAST_ERROR;
				}
			}
		}

		if(!empty($checkExceptions) || $errorMessage)
		{
			//Deletion early created entities
			foreach($createdEntities as $entityTypeID => $entityIDs)
			{
				if($entityTypeID === CCrmOwnerType::Company)
				{
					$entity = new CCrmCompany(false);
				}
				elseif($entityTypeID === CCrmOwnerType::Contact)
				{
					$entity = new CCrmContact(false);
				}
				else
				{
					continue;
				}

				foreach($entityIDs as $entityID)
				{
					$entity->Delete($entityID);
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
			__CrmDealDetailsEndJsonResonse($responseData);
		}

		if(!$isExternal && $enableProductRows && (!$isNew || !empty($productRows)))
		{
			if(!\CCrmDeal::SaveProductRows($ID, $productRows, true, true, false))
			{
				__CrmDealDetailsEndJsonResonse(array('ERROR' => GetMessage('CRM_DEAL_PRODUCT_ROWS_SAVING_ERROR')));
			}
		}

		if(!empty($productRowSettings))
		{
			if(!$isNew)
			{
				$productRowSettings = array_merge(
					\CCrmProductRow::LoadSettings('D', $ID),
					$productRowSettings
				);
			}
			\CCrmProductRow::SaveSettings('D', $ID, $productRowSettings);
		}


		\Bitrix\Crm\Tracking\UI\Details::saveEntityData(
			\CCrmOwnerType::Deal,
			$ID,
			$_POST
		);


		$editorSettings = new \Bitrix\Crm\Settings\EntityEditSettings(
			isset($_POST['EDITOR_CONFIG_ID']) ? $_POST['EDITOR_CONFIG_ID'] : 'deal_details'
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

		if(is_array($requisiteInfo))
		{
			if(isset($requisiteInfo['REQUISITE_ID']))
			{
				$requisiteID = (int)$requisiteInfo['REQUISITE_ID'];
			}
			if(isset($requisiteInfo['BANK_DETAIL_ID']))
			{
				$bankDetailID = (int)$requisiteInfo['BANK_DETAIL_ID'];
			}
		}

		if($requisiteID > 0)
		{
			\Bitrix\Crm\Requisite\EntityLink::register(
				CCrmOwnerType::Deal,
				$ID,
				$requisiteID,
				$bankDetailID
			);
		}

		$arErrors = array();
		if (!isset($isRecurringSaving) && $previousFields['IS_RECURRING'] !== 'Y')
		{
			\CCrmBizProcHelper::AutoStartWorkflows(
				\CCrmOwnerType::Deal,
				$ID,
				$isNew ? \CCrmBizProcEventType::Create : \CCrmBizProcEventType::Edit,
				$arErrors,
				isset($_POST['bizproc_parameters']) ? $_POST['bizproc_parameters'] : null
			);

			if($isNew)
			{
				\Bitrix\Crm\Automation\Factory::runOnAdd(\CCrmOwnerType::Deal, $ID);
			}
			else if(is_array($previousFields)
				&& isset($fields['STAGE_ID'])
				&& isset($previousFields['STAGE_ID'])
				&& $fields['STAGE_ID'] !== $previousFields['STAGE_ID']
			)
			{
				\Bitrix\Crm\Automation\Factory::runOnStatusChanged(\CCrmOwnerType::Deal, $ID);
			}
		}

		if($conversionWizard !== null)
		{
			$conversionWizard->attachNewlyCreatedEntity(\CCrmOwnerType::DealName, $ID);
			$url = $conversionWizard->getRedirectUrl();
			if($url !== '')
			{
				$responseData = array('ENTITY_ID' => $ID, 'REDIRECT_URL' => $url);
				$eventParams = $conversionWizard->getClientEventParams();
				if(is_array($eventParams))
				{
					$responseData['EVENT_PARAMS'] = $eventParams;
				}

				__CrmDealDetailsEndJsonResonse($responseData);
			}
		}
	}

	CBitrixComponent::includeComponentClass('bitrix:crm.deal.details');
	$component = new CCrmDealDetailsComponent();
	$component->initializeParams($params);
	$component->setEntityID($ID);
	$result = array('ENTITY_ID' => $ID, 'ENTITY_DATA' => $component->prepareEntityData());

	if($isNew)
	{
		$result['EVENT_PARAMS'] = array(
			'entityInfo' => \CCrmEntitySelectorHelper::PrepareEntityInfo(
				CCrmOwnerType::DealName,
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
			\CCrmOwnerType::Deal,
			$ID,
			false,
			array('ENABLE_SLIDER' => true)
		);
	}

	__CrmDealDetailsEndJsonResonse($result);
}
elseif($action === 'CONVERT')
{
	$entityID = isset($_POST['ENTITY_ID']) ? (int)$_POST['ENTITY_ID'] : 0;
	if($entityID <= 0)
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => array('MESSAGE' => GetMessage('CRM_DEAL_CONVERSION_ID_NOT_DEFINED'))));
	}

	if(!\CCrmDeal::Exists($entityID))
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => array('MESSAGE' => GetMessage('CRM_DEAL_CONVERSION_NOT_FOUND'))));
	}

	if(!\CCrmDeal::CheckReadPermission($entityID, $currentUserPermissions))
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => array('MESSAGE' => GetMessage('CRM_DEAL_CONVERSION_ACCESS_DENIED'))));
	}

	$configParams = isset($_POST['CONFIG']) && is_array($_POST['CONFIG']) ? $_POST['CONFIG'] : null;
	if(is_array($configParams))
	{
		$config = new DealConversionConfig();
		$config->fromJavaScript($configParams);
		$config->save();
	}
	else
	{
		$config = DealConversionConfig::load();
		if($config === null)
		{
			$config = DealConversionConfig::getDefault();
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
				$syncFields = UserFieldSynchronizer::getSynchronizationFields(CCrmOwnerType::Deal, $entityTypeID);
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
			__CrmDealDetailsEndJsonResonse(
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

			if(!UserFieldSynchronizer::needForSynchronization(CCrmOwnerType::Deal, $entityTypeID))
			{
				continue;
			}

			if($entityConfig->isSynchronizationEnabled())
			{
				UserFieldSynchronizer::synchronize(\CCrmOwnerType::Deal, $entityTypeID);
			}
			else
			{
				UserFieldSynchronizer::markAsSynchronized(\CCrmOwnerType::Deal, $entityTypeID);
			}
		}
	}

	DealConversionWizard::remove($entityID);
	$wizard = new DealConversionWizard($entityID, $config);
	$wizard->setOriginUrl(isset($_POST['ORIGIN_URL']) ? $_POST['ORIGIN_URL'] : '');

	$wizard->setSliderEnabled(true);

	if($wizard->execute())
	{
		__CrmDealDetailsEndJsonResonse(
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
			__CrmDealDetailsEndJsonResonse(
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
			__CrmDealDetailsEndJsonResonse(array('ERROR' => array('MESSAGE' => $wizard->getErrorText())));
		}
	}
}
elseif($action === 'DELETE')
{
	$ID = isset($_POST['ACTION_ENTITY_ID']) ? max((int)$_POST['ACTION_ENTITY_ID'], 0) : 0;
	if($ID <= 0)
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => GetMessage('CRM_DEAL_CONVERSION_NOT_FOUND')));
	}

	$categoryID = CCrmDeal::GetCategoryID($ID);
	$permissionAttrs = \CCrmDeal::GetPermissionAttributes(array($ID), $categoryID);

	if(!\CCrmDeal::CheckDeletePermission(
		$ID,
		$currentUserPermissions,
		$categoryID,
		array('ENTITY_ATTRS' => $permissionAttrs))
	)
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => GetMessage('CRM_DEAL_CONVERSION_ACCESS_DENIED')));
	}

	$bizProc = new CCrmBizProc('DEAL');
	if (!$bizProc->Delete($ID, $permissionAttrs, array('DealCategoryId' => $categoryID)))
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => $bizProc->LAST_ERROR));
	}

	$entity = new \CCrmDeal(false);
	if (!$entity->Delete($ID, array('PROCESS_BIZPROC' => false)))
	{
		/** @var CApplicationException $ex */
		$ex = $APPLICATION->GetException();
		__CrmDealDetailsEndJsonResonse(
			array(
				'ERROR' => ($ex instanceof CApplicationException) ? $ex->GetString() : GetMessage('CRM_DEAL_DELETION_ERROR')
			)
		);
	}
	__CrmDealDetailsEndJsonResonse(array('ENTITY_ID' => $ID));
}
elseif($action === 'GET_BINDING_INFOS')
{
	$userPermissions = CCrmPerms::GetCurrentUserPermissions();
	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();
	$entityTypeName = isset($params['ENTITY_TYPE_NAME']) ? $params['ENTITY_TYPE_NAME'] : '';
	if($entityTypeName === '')
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => 'Entity type is not specified.'));
	}

	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if($entityTypeID !== CCrmOwnerType::Contact)
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => 'Entity type is not supported in current context.'));
	}

	$ownerTypeName = isset($params['OWNER_TYPE_NAME']) ? $params['OWNER_TYPE_NAME'] : '';
	if($ownerTypeName === '')
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => 'Owner type is not specified.'));
	}

	$ownerTypeID = CCrmOwnerType::ResolveID($ownerTypeName);
	if($ownerTypeID === CCrmOwnerType::Undefined)
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => 'Undefined owner type is specified.'));
	}

	$ownerID = isset($params['OWNER_ID']) ? (int)$params['OWNER_ID'] : 0;
	if($ownerID <= 0)
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => 'Owner ID is not specified.'));
	}

	if(!CCrmAuthorizationHelper::CheckReadPermission($ownerTypeID, $ownerID, $userPermissions))
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => 'Access denied.'));
	}

	$entityIDs = null;
	if($ownerTypeID === CCrmOwnerType::Company)
	{
		$entityIDs = \Bitrix\Crm\Binding\ContactCompanyTable::getCompanyContactIDs($ownerID);
	}

	$nameTemplate = isset($params['NAME_TEMPLATE'])
		? $params['NAME_TEMPLATE'] : \Bitrix\Crm\Format\PersonNameFormatter::getFormat();

	$data = array();
	foreach($entityIDs as $entityID)
	{
		$isReadPermitted = CCrmContact::CheckReadPermission($entityID, $userPermissions);
		$data[] = CCrmEntitySelectorHelper::PrepareEntityInfo(
			CCrmOwnerType::ContactName,
			$entityID,
			array(
				'ENTITY_EDITOR_FORMAT' => true,
				'REQUIRE_REQUISITE_DATA' => $isReadPermitted,
				'REQUIRE_MULTIFIELDS' => $isReadPermitted,
				'NAME_TEMPLATE' => $nameTemplate
			)
		);
	}
	__CrmDealDetailsEndJsonResonse(array('DATA' => $data));
}
elseif($action === 'ADD_BINDING')
{
	$userPermissions = CCrmPerms::GetCurrentUserPermissions();
	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();
	$entityTypeName = isset($params['ENTITY_TYPE_NAME']) ? $params['ENTITY_TYPE_NAME'] : '';
	if($entityTypeName === '')
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => 'Entity type is not specified.'));
	}

	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if($entityTypeID !== CCrmOwnerType::Contact)
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => 'Entity type is not supported in current context.'));
	}

	$entityID = isset($params['ENTITY_ID']) ? (int)$params['ENTITY_ID'] : 0;
	if($entityID <= 0)
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => 'Entity ID is not specified.'));
	}

	$ownerTypeName = isset($params['OWNER_TYPE_NAME']) ? $params['OWNER_TYPE_NAME'] : '';
	if($ownerTypeName === '')
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => 'Owner type is not specified.'));
	}

	$ownerTypeID = CCrmOwnerType::ResolveID($ownerTypeName);
	if($ownerTypeID === CCrmOwnerType::Undefined)
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => 'Undefined owner type is specified.'));
	}

	$ownerID = isset($params['OWNER_ID']) ? (int)$params['OWNER_ID'] : 0;
	if($ownerID <= 0)
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => 'Owner ID is not specified.'));
	}

	if(!CCrmAuthorizationHelper::CheckUpdatePermission($ownerTypeID, $ownerID, $userPermissions))
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => 'Access denied.'));
	}

	$entityIDs = null;
	if($ownerTypeID !== CCrmOwnerType::Company)
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => 'Owner type is not supported.'));
	}

	\Bitrix\Crm\Binding\ContactCompanyTable::bindContactIDs($ownerID, array($entityID));
	$entityIDs = \Bitrix\Crm\Binding\ContactCompanyTable::getCompanyContactIDs($ownerID);

	$nameTemplate = isset($params['NAME_TEMPLATE'])
		? $params['NAME_TEMPLATE'] : \Bitrix\Crm\Format\PersonNameFormatter::getFormat();

	$data = array();
	foreach($entityIDs as $entityID)
	{
		$isReadPermitted = CCrmContact::CheckReadPermission($entityID, $userPermissions);
		$data[] = CCrmEntitySelectorHelper::PrepareEntityInfo(
			CCrmOwnerType::ContactName,
			$entityID,
			array(
				'ENTITY_EDITOR_FORMAT' => true,
				'REQUIRE_REQUISITE_DATA' => $isReadPermitted,
				'REQUIRE_MULTIFIELDS' => $isReadPermitted,
				'NAME_TEMPLATE' => $nameTemplate
			)
		);
	}
	__CrmDealDetailsEndJsonResonse(array('DATA' => $data));
}
elseif($action === 'DELETE_BINDING')
{
	$userPermissions = CCrmPerms::GetCurrentUserPermissions();
	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();
	$entityTypeName = isset($params['ENTITY_TYPE_NAME']) ? $params['ENTITY_TYPE_NAME'] : '';
	if($entityTypeName === '')
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => 'Entity type is not specified.'));
	}

	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if($entityTypeID !== CCrmOwnerType::Contact)
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => 'Entity type is not supported in current context.'));
	}

	$entityID = isset($params['ENTITY_ID']) ? (int)$params['ENTITY_ID'] : 0;
	if($entityID <= 0)
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => 'Entity ID is not specified.'));
	}

	$ownerTypeName = isset($params['OWNER_TYPE_NAME']) ? $params['OWNER_TYPE_NAME'] : '';
	if($ownerTypeName === '')
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => 'Owner type is not specified.'));
	}

	$ownerTypeID = CCrmOwnerType::ResolveID($ownerTypeName);
	if($ownerTypeID === CCrmOwnerType::Undefined)
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => 'Undefined owner type is specified.'));
	}

	$ownerID = isset($params['OWNER_ID']) ? (int)$params['OWNER_ID'] : 0;
	if($ownerID <= 0)
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => 'Owner ID is not specified.'));
	}

	if(!CCrmAuthorizationHelper::CheckUpdatePermission($ownerTypeID, $ownerID, $userPermissions))
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => 'Access denied.'));
	}

	$entityIDs = null;
	if($ownerTypeID !== CCrmOwnerType::Company)
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => 'Owner type is not supported.'));
	}

	\Bitrix\Crm\Binding\ContactCompanyTable::unbindContactIDs($ownerID, array($entityID));
	__CrmDealDetailsEndJsonResonse(
		array(
			'DATA' => array(
				'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName($entityTypeID),
				'ENTITY_ID' => $entityID
			)
		)
	);
}
elseif($action === 'EXCLUDE')
{
	$ID = isset($_POST['ACTION_ENTITY_ID']) ? max((int)$_POST['ACTION_ENTITY_ID'], 0) : 0;
	if($ID <= 0)
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => 'Deal is not specified.'));
	}

	try
	{
		\Bitrix\Crm\Exclusion\Manager::excludeEntity(\CCrmOwnerType::Deal, $ID, true);
	}
	catch(\Bitrix\Main\SystemException $ex)
	{
		__CrmDealDetailsEndJsonResonse(array('ERROR' => $ex->getMessage()));
	}
	__CrmDealDetailsEndJsonResonse(array('ENTITY_ID' => $ID));
}
elseif($action === 'PREPARE_EDITOR_HTML')
{
	$ID = isset($_POST['ACTION_ENTITY_ID']) ? max((int)$_POST['ACTION_ENTITY_ID'], 0) : 0;
	$guid = isset($_POST['GUID']) ? $_POST['GUID'] : "deal_{$ID}_custom_editor";
	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();
	$context = isset($_POST['CONTEXT']) && is_array($_POST['CONTEXT']) ? $_POST['CONTEXT'] : array();
	$fieldNames = isset($_POST['FIELDS']) && is_array($_POST['FIELDS']) ? $_POST['FIELDS'] : array();
	$title = isset($_POST['TITLE']) ? $_POST['TITLE'] : '';
	$isEmbedded = isset($_POST['IS_EMBEDDED']) && strtoupper($_POST['IS_EMBEDDED']) === 'Y';
	$enableRequiredUserFieldCheck = !isset($_POST['ENABLE_REQUIRED_USER_FIELD_CHECK'])
		|| strtoupper($_POST['ENABLE_REQUIRED_USER_FIELD_CHECK']) === 'Y';
	$enableSearchHistory = !isset($_POST['ENABLE_SEARCH_HISTORY'])
		|| strtoupper($_POST['ENABLE_SEARCH_HISTORY']) === 'Y';

	CBitrixComponent::includeComponentClass('bitrix:crm.deal.details');
	$component = new CCrmDealDetailsComponent();
	$component->initializeParams($params);
	$component->setEntityID($ID);
	if($ID > 0)
	{
		$component->setCategoryID(\CCrmDeal::GetCategoryID($ID));
	}
	elseif(isset($context['PARAMS']) && isset($context['PARAMS']['CATEGORY_ID']))
	{
		$component->setCategoryID($context['PARAMS']['CATEGORY_ID']);
	}
	$component->enableSearchHistory($enableSearchHistory);

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
		'elements' => $entityConfigElements
	);

	if($title !== '')
	{
		$sectionConfig['title'] = $title;
	}
	else
	{
		$sectionConfig['enableTitle'] = false;
	}

	$GLOBALS['APPLICATION']->RestartBuffer();
	Header('Content-Type: text/html; charset='.LANG_CHARSET);
	$APPLICATION->ShowAjaxHead();
	$APPLICATION->IncludeComponent(
		'bitrix:crm.entity.editor',
		'',
		array(
			'GUID' => $guid,
			'CONFIG_ID' => $component->getDefaultConfigID(),
			'FORCE_DEFAULT_CONFIG' => true,
			'ENTITY_CONFIG' => array($sectionConfig),
			'ENTITY_FIELDS' => $component->prepareFieldInfos(),
			'ENTITY_DATA' => $component->prepareEntityData(),
			'ENABLE_REQUIRED_FIELDS_INJECTION' => false,
			'ENABLE_CONFIGURATION_UPDATE' => false,
			'ENABLE_SECTION_EDIT' => false,
			'ENABLE_SECTION_CREATION' => false,
			'ENABLE_USER_FIELD_CREATION' => false,
			'ENABLE_MODE_TOGGLE' => false,
			'ENABLE_TOOL_PANEL' => false,
			'ENABLE_BOTTOM_PANEL' => false,
			'ENABLE_PAGE_TITLE_CONTROLS' => false,
			'ENABLE_REQUIRED_USER_FIELD_CHECK' => $enableRequiredUserFieldCheck,
			'USER_FIELD_ENTITY_ID' => \CCrmDeal::GetUserFieldEntityID(),
			'SERVICE_URL' => '/bitrix/components/bitrix/crm.deal.details/ajax.php?'.bitrix_sessid_get(),
			'CONTEXT_ID' => \CCrmOwnerType::DealName.'_'.$ID,
			'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
			'ENTITY_ID' => $ID,
			'READ_ONLY' => false,
			'INITIAL_MODE' => 'edit',
			'IS_EMBEDDED' => $isEmbedded,
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