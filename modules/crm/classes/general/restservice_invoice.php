<?php
if(!CModule::IncludeModule('rest'))
{
	return;
}

use Bitrix\Rest\RestException;
use Bitrix\Crm\Integration\DiskManager;
use Bitrix\Crm\Integration\StorageFileType;
use Bitrix\Crm\Settings\RestSettings;
use Bitrix\Crm\Requisite;

class CCrmInvoiceRestUtil
{
	public static function convertValue($method, $valueType, $value)
	{
		$result = null;
		$convert = 'no';
		if ($valueType === 'datetime' || $valueType === 'date')
		{
			$postfix = '';
			switch ($valueType)
			{
				case 'datetime':
					$postfix = '_dt';
					break;
				case 'date':
					$postfix = '_d';
					break;
			}
			switch ($method)
			{
				case 'add':
				case 'update':
					$convert = 'in'.$postfix;
					break;
				case 'list':
				case 'get':
					$convert = 'out'.$postfix;
					break;
			}
		}
		switch ($convert)
		{
			case 'no':
				$result = $value;
				break;
			case 'in_dt':
				$result = CRestUtil::unConvertDateTime($value);
				break;
			case 'in_d':
				$result = CRestUtil::unConvertDate($value);
				break;
			case 'out_dt':
				$result = CRestUtil::ConvertDateTime($value);
				break;
			case 'out_d':
				$result = CRestUtil::ConvertDate($value);
				break;
		}

		return $result;
	}

	public static function getParamScalar(&$params, $name, $defaultValue = null)
	{
		$result = $defaultValue;

		if (is_array($params))
		{
			$index = mb_strtolower($name);
			if (array_key_exists($index, $params))
			{
				$result = $params[$index];
			}
			else
			{
				$index = mb_strtoupper($index);
				if (array_key_exists($index, $params))
					$result = $params[$index];
			}
		}

		return $result;
	}

	public static function getParamArray(&$params, $name, $defaultValue = array())
	{
		$result = $defaultValue;

		if (is_array($params))
		{
			$index = mb_strtolower($name);
			if (is_array($params[$index]) && count($params[$index]) > 0)
			{
				$result = $params[$index];
			}
			else
			{
				$index = mb_strtoupper($index);
				if (is_array($params[$index]) && count($params[$index]) > 0)
					$result = $params[$index];
			}
		}

		return $result;
	}
}

class CCrmInvoiceRestService extends IRestService
{
	/**
	 * @var CRestServer $server
	 */
	private static $server = null;

	private static $currentUser = null;

	private static $webdavSettings = null;
	private static $webdavIBlock = null;

	private static $arAllowedFilterOperations = null;

	public static function OnRestServiceBuildDescription()
	{
		$callback = array(__CLASS__, 'processEvent');
		return array(
			'crm' => array(
				'crm.invoice.fields' => array('CCrmInvoiceRestService', 'fields'),
				'crm.invoice.list' => array('CCrmInvoiceRestService', 'getList'),
				'crm.invoice.get' => array('CCrmInvoiceRestService', 'get'),
				'crm.invoice.add' => array('CCrmInvoiceRestService', 'add'),
				'crm.invoice.update' => array('CCrmInvoiceRestService', 'update'),
				'crm.invoice.delete' => array('CCrmInvoiceRestService', 'delete'),
				'crm.invoice.getexternallink' => array('CCrmInvoiceRestService', 'getExternalLink'),
				'crm.vat.fields' => array('CCrmRestVat', 'fields'),
				'crm.vat.list' => array('CCrmRestVat', 'getList'),
				'crm.vat.get' => array('CCrmRestVat', 'get'),
				'crm.vat.add' => array('CCrmRestVat', 'add'),
				'crm.vat.update' => array('CCrmRestVat', 'update'),
				'crm.vat.delete' => array('CCrmRestVat', 'delete'),
				CRestUtil::EVENTS => array(
					'onCrmInvoiceAdd' => array(
						'crm',
						'OnAfterCrmInvoiceAdd',
						$callback,
						array('category' => \Bitrix\Rest\Sqs::CATEGORY_CRM)
					),
					'onCrmInvoiceUpdate' => array(
						'crm',
						'OnAfterCrmInvoiceUpdate',
						$callback,
						array('category' => \Bitrix\Rest\Sqs::CATEGORY_CRM)
					),
					'onCrmInvoiceDelete' => array(
						'crm',
						'OnAfterCrmInvoiceDelete',
						$callback,
						array('category' => \Bitrix\Rest\Sqs::CATEGORY_CRM)
					),
					'onCrmInvoiceSetStatus' => array(
						'crm',
						'OnAfterCrmInvoiceSetStatus',
						$callback,
						array('category' => \Bitrix\Rest\Sqs::CATEGORY_CRM)
					)
				)
			)
		);
	}

	public static function getList($params, $nav = 0, CRestServer $server)
	{
		if(!CCrmInvoice::CheckReadPermission(0))
			throw new RestException('Access denied.');

		self::$server = $server;

		$order = CCrmInvoiceRestUtil::getParamArray($params, 'order', array('ID' => 'DESC'));
		$filter = CCrmInvoiceRestUtil::getParamArray($params, 'filter');
		$select = CCrmInvoiceRestUtil::getParamArray($params, 'select');

		$filter = self::prepareFilter($filter);
		$select = self::prepareSelect($select);
		$order = self::prepareOrder($order);

		if (!is_array($select) || count($select) === 0)
			throw new RestException('Inadmissible fields for selection');

		$idInSelect = in_array('ID', $select, true);
		if (!$idInSelect)
			$select[] = 'ID';

		$dbResult = CCrmInvoice::GetList($order, $filter, false, self::getNavData($nav), $select);
		if (!is_object($dbResult))
		{
			$dbResult = new CDBResult();
			$dbResult->InitFromArray(array());
		}
		$dbResult->NavStart(IRestService::LIST_LIMIT, false);

		$result = array();
		while($arRow = $dbResult->NavNext(false))
		{
			$resultItem = self::filterFields($arRow, 'list');
			self::externalizeUserFields($resultItem);
			if (!$idInSelect && array_key_exists('ID', $resultItem))
				unset($resultItem['ID']);
			$result[] = $resultItem;
		}

		return self::setNavData($result, $dbResult);
	}

	public static function fields()
	{
		$fieldsInfo = self::getFieldsInfo();

		$fields = array();
		foreach ($fieldsInfo as $fName => $fInfo)
		{
			if (mb_substr($fName, 0, 19) === 'INVOICE_PROPERTIES.')
			{
				if (mb_substr($fName, 18) === '.{}')
				{
					$definition = array('key' => self::makeFieldInfo($fInfo));
					$fields['INVOICE_PROPERTIES']['definition'] = $definition;
				}
				elseif (mb_substr($fName, 18) === '.{}.')
					$fields['INVOICE_PROPERTIES']['definition']['value'] = self::makeFieldInfo($fInfo);
			}
			elseif (mb_substr($fName, 0, 13) === 'PRODUCT_ROWS.')
			{
				if (mb_substr($fName, 12) === '.[]')
				{
					$definition = array('row' => array());
					$fields['PRODUCT_ROWS']['definition'] = $definition;
				}
				elseif (mb_substr($fName, 12, 4) === '.[].')
				{
					$subName = mb_substr($fName, 16);
					$fieldInfo = self::makeFieldInfo($fInfo);
					$name = \CCrmProductRow::GetFieldCaption($subName);
					$fieldInfo['title'] = !empty($name) ? $name : $subName;
					$fields['PRODUCT_ROWS']['definition']['row'][$subName] = $fieldInfo;
				}
			}
			else
			{
				$fields[$fName] = self::makeFieldInfo($fInfo);
				$name = \Bitrix\Crm\InvoiceTable::getFieldCaption($fName);
				$fields[$fName]['title'] = !empty($name) ? $name : $fName;
			}
		}

		// user fields
		$ufInfos = array();
		self::prepareUserFieldsInfo($ufInfos, CCrmInvoice::$sUFEntityID);
		$fields = array_merge($fields, CCrmRestHelper::prepareFieldInfos($ufInfos));

		return $fields;
	}
	
	public static function get($params)
	{
		$ID = CCrmInvoiceRestUtil::getParamScalar($params, 'id', 0);
		if(!CCrmInvoice::CheckReadPermission($ID))
			throw new RestException('Access denied.');

		$arResult = self::getInvoiceDataByID($ID);
		$arResult = self::filterFields($arResult, 'get');
		self::externalizeUserFields($arResult);

		return $arResult;
	}

	public static function add($params)
	{
		/** @global CMain $APPLICATION*/
		global $APPLICATION, $DB;

		$invoice = new CCrmInvoice();
		if(!CCrmInvoice::CheckCreatePermission())
			throw new RestException('Access denied.');

		$fields = CCrmInvoiceRestUtil::getParamArray($params, 'fields');

		$fields = self::filterFields($fields, 'add');

		self::internalizeUserFields($fields, array());

		if (!is_array($fields) || count($fields) === 0)
			throw new RestException('Invalid parameters.');

		// sanitize
		$comments = isset($fields['COMMENTS']) ? trim($fields['COMMENTS']) : '';
		$userDescription = isset($fields['USER_DESCRIPTION']) ? trim($fields['USER_DESCRIPTION']) : '';
		$bSanitizeComments = ($comments !== '' && mb_strpos($comments, '<') !== false);
		$bSanitizeUserDescription = ($userDescription !== '' && mb_strpos($userDescription, '<') !== false);
		if ($bSanitizeComments || $bSanitizeUserDescription)
		{
			$sanitizer = new CBXSanitizer();
			$sanitizer->ApplyDoubleEncode(false);
			$sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_MIDDLE);
			//Crutch for for Chrome line break behaviour in HTML editor.
			$sanitizer->AddTags(array('div' => array()));
			if ($bSanitizeComments)
				$fields['COMMENTS'] = $sanitizer->SanitizeHtml($fields['COMMENTS']);
			if ($bSanitizeUserDescription)
				$fields['USER_DESCRIPTION'] = $sanitizer->SanitizeHtml($fields['USER_DESCRIPTION']);
			unset($sanitizer);
		}
		unset($bSanitizeComments, $bSanitizeUserDescription);
		$fields['COMMENTS'] = $comments;
		$fields['USER_DESCRIPTION'] = $userDescription;
		unset($comments, $userDescription);

		$bStatusSuccess = CCrmStatusInvoice::isStatusSuccess($fields['STATUS_ID']);
		if ($bStatusSuccess)
			$bStatusFailed = false;
		else
			$bStatusFailed = CCrmStatusInvoice::isStatusFailed($fields['STATUS_ID']);

		$options = array();
		if(!self::isRequiredUserFieldCheckEnabled())
		{
			$options['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
		}

		if (!$invoice->CheckFields($fields, false, $bStatusSuccess, $bStatusFailed, $options))
		{
			if (!empty($invoice->LAST_ERROR))
				throw new RestException($invoice->LAST_ERROR);
			else
				throw new RestException('Error on check fields.');
		}

		// person type
		$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
		if (!isset($arPersonTypes['COMPANY']) || !isset($arPersonTypes['CONTACT']))
			throw new RestException('Incorrect values in the peson type settings.');
		$personTypeId = isset($fields['PERSON_TYPE_ID']) ? (int)$fields['PERSON_TYPE_ID'] : 0;
		if (isset($fields['UF_COMPANY_ID']) && intval($fields['UF_COMPANY_ID']) > 0)
			$personTypeId = (int)$arPersonTypes['COMPANY'];
		else if (isset($fields['UF_CONTACT_ID']) && intval($fields['UF_CONTACT_ID']) > 0)
			$personTypeId = (int)$arPersonTypes['CONTACT'];
		if ($personTypeId !== intval($arPersonTypes['COMPANY']) && $personTypeId !== intval($arPersonTypes['CONTACT']))
		{
			throw new RestException('Incorrect value of PERSON_TYPE_ID field ('.
				$arPersonTypes['CONTACT'].' - Contact, '.$arPersonTypes['CONTACT'].' - Company)');
		}
		$fields['PERSON_TYPE_ID'] = $personTypeId;

		if (!is_array($fields['INVOICE_PROPERTIES']))
		{
			$fields['INVOICE_PROPERTIES'] = array();
		}
		if (isset($fields['PR_LOCATION']))
		{
			$fields['INVOICE_PROPERTIES']['LOCATION'] = $fields['PR_LOCATION'];
		}
		$propsInfo = CCrmInvoice::GetPropertiesInfo($fields['PERSON_TYPE_ID']);
		$propsInfo = is_array($propsInfo[$fields['PERSON_TYPE_ID']]) ? $propsInfo[$fields['PERSON_TYPE_ID']] : array();
		$invoiceProperties = array();
		foreach ($fields['INVOICE_PROPERTIES'] as $code => $value)
		{
			if (array_key_exists($code, $propsInfo))
			{
				$invoiceProperties[$propsInfo[$code]['ID']] = $value;
			}
			else if ($code === 'COMPANY' && array_key_exists('COMPANY_NAME', $propsInfo))    // ua company name hack
			{
				$invoiceProperties[$propsInfo['COMPANY_NAME']['ID']] = $value;
			}
		}
		$fields['INVOICE_PROPERTIES'] = $invoiceProperties;
		unset($propsInfo, $invoiceProperties, $code, $value);

		$defRqLinkParams = Requisite\EntityLink::determineRequisiteLinkBeforeSave(
			CCrmOwnerType::Invoice, 0, Requisite\EntityLink::ENTITY_OPERATION_ADD, $fields
		);

		//region Autocomplete property values
		$companyId = 0;
		$contactId = 0;
		$requisiteIdLinked = 0;

		if (isset($defRqLinkParams['CLIENT_ENTITY_TYPE_ID']) && isset($defRqLinkParams['CLIENT_ENTITY_ID'])
			&& $defRqLinkParams['CLIENT_ENTITY_ID'] > 0)
		{
			if ($defRqLinkParams['CLIENT_ENTITY_TYPE_ID'] === CCrmOwnerType::Company)
			{
				$companyId = (int)$defRqLinkParams['CLIENT_ENTITY_ID'];
			}
			else if ($defRqLinkParams['CLIENT_ENTITY_TYPE_ID'] === CCrmOwnerType::Contact)
			{
				$contactId = (int)$defRqLinkParams['CLIENT_ENTITY_ID'];
			}
		}
		if ($contactId <= 0 && isset($fields['UF_CONTACT_ID']) && $fields['UF_CONTACT_ID'] > 0)
		{
			$contactId = (int)$fields['UF_CONTACT_ID'];
		}
		if (isset($defRqLinkParams['REQUISITE_ID']) && $defRqLinkParams['REQUISITE_ID'] > 0)
		{
			$requisiteIdLinked = $defRqLinkParams['REQUISITE_ID'];
		}
		$props = $invoice->GetProperties(0, $personTypeId);
		CCrmInvoice::__RewritePayerInfo($companyId, $contactId, $props);
		CCrmInvoice::rewritePropsFromRequisite($personTypeId, $requisiteIdLinked, $props);
		$formProps = array();
		$propsValues = $invoice->ParsePropertiesValuesFromPost($personTypeId, $formProps, $props);
		if (isset($propsValues['PROPS_VALUES']) && is_array($propsValues['PROPS_VALUES']))
		{
			foreach($propsValues['PROPS_VALUES'] as $propertyId => $propertyValue)
			{
				if (!isset($fields['INVOICE_PROPERTIES'][$propertyId])
					|| $fields['INVOICE_PROPERTIES'][$propertyId] === '')
				{
					$fields['INVOICE_PROPERTIES'][$propertyId] = $propertyValue;
				}
			}
			unset($propertyId, $propertyValue);
		}
		unset($companyId, $contactId, $requisiteIdLinked, $props, $propsValues, $formProps);
		//endregion Autocomplete property values

		$DB->StartTransaction();
		$recalculate = false;
		$ID = $invoice->Add($fields, $recalculate, SITE_ID, array('UPDATE_SEARCH' => true));
		if(!is_int($ID) || $ID <= 0)
		{
			$DB->Rollback();

			$errMsg = '';
			if (!empty($invoice->LAST_ERROR))
			{
				$errMsg = $invoice->LAST_ERROR;
			}
			else
			{
				$ex = $APPLICATION->GetException();
				if ($ex)
				{
					$APPLICATION->ResetException();
					if ($errMsg == '')
						$errMsg = $ex->GetString();
				}
			}
			throw new RestException((!empty($errMsg) ? $errMsg : 'Unknown error during invoice creation.')."<br />\n");
		}
		else
		{
			Requisite\EntityLink::register(
				CCrmOwnerType::Invoice, $ID,
				$defRqLinkParams['REQUISITE_ID'],
				$defRqLinkParams['BANK_DETAIL_ID'],
				$defRqLinkParams['MC_REQUISITE_ID'],
				$defRqLinkParams['MC_BANK_DETAIL_ID']
			);

			$DB->Commit();
		}

		return $ID;
	}

	public static function update($params)
	{
		/** @global CMain $APPLICATION*/
		global $APPLICATION, $DB;

		$ID = CCrmInvoiceRestUtil::getParamScalar($params, 'id', 0);
		if($ID <= 0)
			throw new RestException('Invalid identifier.');

		$invoice = new CCrmInvoice();
		if(!CCrmInvoice::CheckUpdatePermission($ID))
			throw new RestException('Access denied.');

		$fields = CCrmInvoiceRestUtil::getParamArray($params, 'fields');
		$fields = self::filterFields($fields, 'update');

		// sanitize
		$updateComments = isset($fields['COMMENTS']);
		$updateUserDescription = isset($fields['USER_DESCRIPTION']);
		$comments = $updateComments ? trim($fields['COMMENTS']) : '';
		$userDescription = $updateUserDescription ? trim($fields['USER_DESCRIPTION']) : '';
		$bSanitizeComments = ($comments !== '' && mb_strpos($comments, '<') !== false);
		$bSanitizeUserDescription = ($userDescription !== '' && mb_strpos($userDescription, '<') !== false);
		if ($bSanitizeComments || $bSanitizeUserDescription)
		{
			$sanitizer = new CBXSanitizer();
			$sanitizer->ApplyDoubleEncode(false);
			$sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_MIDDLE);
			//Crutch for for Chrome line break behaviour in HTML editor.
			$sanitizer->AddTags(array('div' => array()));
			if ($bSanitizeComments)
				$fields['COMMENTS'] = $sanitizer->SanitizeHtml($fields['COMMENTS']);
			if ($bSanitizeUserDescription)
				$fields['USER_DESCRIPTION'] = $sanitizer->SanitizeHtml($fields['USER_DESCRIPTION']);
			unset($sanitizer);
		}
		unset($bSanitizeComments, $bSanitizeUserDescription);
		if ($updateComments)
			$fields['COMMENTS'] = $comments;
		if ($updateUserDescription)
			$fields['USER_DESCRIPTION'] = $userDescription;
		unset($updateComments, $updateUserDescription, $comments, $userDescription);

		if (!is_array($fields) || count($fields) === 0)
			throw new RestException('Invalid parameters.');

		$updateProps = is_array($fields['INVOICE_PROPERTIES']) ? $fields['INVOICE_PROPERTIES'] : array();

		$origFields = self::getInvoiceDataByID($ID);
		$origFields = self::filterFields($origFields, 'update', false);
		foreach ($origFields as $fName => $fValue)
		{
			if (!array_key_exists($fName, $fields) && $fName !== 'DATE_INSERT' && $fName !== 'DATE_UPDATE')
				$fields[$fName] = $fValue;
		}

		self::internalizeUserFields(
			$fields,
			array(
				'IGNORED_ATTRS' => array(
					CCrmFieldInfoAttr::Immutable,
					CCrmFieldInfoAttr::UserPKey
				)
			)
		);

		$bStatusSuccess = CCrmStatusInvoice::isStatusSuccess($fields['STATUS_ID']);
		if ($bStatusSuccess)
			$bStatusFailed = false;
		else
			$bStatusFailed = CCrmStatusInvoice::isStatusFailed($fields['STATUS_ID']);

		// person type
		$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
		if (!isset($arPersonTypes['COMPANY']) || !isset($arPersonTypes['CONTACT']))
			throw new RestException('Incorrect values in the peson type settings.');
		$personTypeId = isset($fields['PERSON_TYPE_ID']) ? (int)$fields['PERSON_TYPE_ID'] : 0;
		if (isset($fields['UF_COMPANY_ID']) && intval($fields['UF_COMPANY_ID']) > 0)
			$personTypeId = (int)$arPersonTypes['COMPANY'];
		else if (isset($fields['UF_CONTACT_ID']) && intval($fields['UF_CONTACT_ID']) > 0)
			$personTypeId = (int)$arPersonTypes['CONTACT'];
		if ($personTypeId !== intval($arPersonTypes['COMPANY']) && $personTypeId !== intval($arPersonTypes['CONTACT']))
		{
			throw new RestException('Incorrect value of PERSON_TYPE_ID field ('.
				$arPersonTypes['CONTACT'].' - Contact, '.$arPersonTypes['CONTACT'].' - Company)');
		}
		$fields['PERSON_TYPE_ID'] = $personTypeId;

		if (!is_array($fields['INVOICE_PROPERTIES']))
		{
			$fields['INVOICE_PROPERTIES'] = array();
		}
		if (isset($fields['PR_LOCATION']) && is_array($fields['INVOICE_PROPERTIES']))
		{
			$fields['INVOICE_PROPERTIES']['LOCATION'] = $updateProps['LOCATION'] = $fields['PR_LOCATION'];
		}

		$options = array();
		if(!self::isRequiredUserFieldCheckEnabled())
		{
			$options['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
		}

		if (!$invoice->CheckFields($fields, $ID, $bStatusSuccess, $bStatusFailed, $options))
		{
			if (!empty($invoice->LAST_ERROR))
				throw new RestException($invoice->LAST_ERROR);
			else
				throw new RestException('Error on check fields.');
		}

		$propsInfo = CCrmInvoice::GetPropertiesInfo($fields['PERSON_TYPE_ID']);
		$propsInfo = is_array($propsInfo[$fields['PERSON_TYPE_ID']]) ? $propsInfo[$fields['PERSON_TYPE_ID']] : array();
		$invoiceProperties = array();
		foreach ($propsInfo as $propCode => $arProp)
		{
			if (array_key_exists($propCode, $fields['INVOICE_PROPERTIES']))
			{
				$invoiceProperties[$arProp['ID']] = $fields['INVOICE_PROPERTIES'][$propCode];
			}
			else if ($propCode === 'COMPANY_NAME' && array_key_exists('COMPANY', $fields['INVOICE_PROPERTIES']))    // ua company name hack
			{
				$invoiceProperties[$arProp['ID']] = $fields['INVOICE_PROPERTIES']['COMPANY'];
			}
			else if (is_array($origFields['INVOICE_PROPERTIES']))
			{
				if (array_key_exists($propCode, $origFields['INVOICE_PROPERTIES']))
				{
					$invoiceProperties[$arProp['ID']] = $origFields['INVOICE_PROPERTIES'][$propCode];
				}
				else if ($propCode === 'COMPANY_NAME'
					&& array_key_exists('COMPANY', $fields['INVOICE_PROPERTIES']))    // ua company name hack
				{
					$invoiceProperties[$arProp['ID']] = $origFields['INVOICE_PROPERTIES']['COMPANY'];
				}
			}
		}
		$fields['INVOICE_PROPERTIES'] = $invoiceProperties;
		unset($propCode, $arProp);
		$invoiceProperties = array();
		foreach ($updateProps as $code => $value)
		{
			if (array_key_exists($code, $propsInfo))
			{
				$invoiceProperties[$propsInfo[$code]['ID']] = $value;
			}
			else if ($code === 'COMPANY' && array_key_exists('COMPANY_NAME', $propsInfo))    // ua company name hack
			{
				$invoiceProperties[$propsInfo['COMPANY_NAME']['ID']] = $value;
			}
		}
		$updateProps = $invoiceProperties;
		unset($propsInfo, $invoiceProperties, $code, $value);

		$defRqLinkParams = Requisite\EntityLink::determineRequisiteLinkBeforeSave(
			CCrmOwnerType::Invoice, $ID, Requisite\EntityLink::ENTITY_OPERATION_UPDATE, $fields
		);

		//region Autocomplete property values
		$companyId = 0;
		$contactId = 0;
		$requisiteIdLinked = 0;

		if (isset($defRqLinkParams['CLIENT_ENTITY_TYPE_ID']) && isset($defRqLinkParams['CLIENT_ENTITY_ID'])
			&& $defRqLinkParams['CLIENT_ENTITY_ID'] > 0)
		{
			if ($defRqLinkParams['CLIENT_ENTITY_TYPE_ID'] === CCrmOwnerType::Company)
			{
				$companyId = (int)$defRqLinkParams['CLIENT_ENTITY_ID'];
			}
			else if ($defRqLinkParams['CLIENT_ENTITY_TYPE_ID'] === CCrmOwnerType::Contact)
			{
				$contactId = (int)$defRqLinkParams['CLIENT_ENTITY_ID'];
			}
		}
		if ($contactId <= 0 && isset($fields['UF_CONTACT_ID']) && $fields['UF_CONTACT_ID'] > 0)
		{
			$contactId = (int)$fields['UF_CONTACT_ID'];
		}
		if (isset($defRqLinkParams['REQUISITE_ID']) && $defRqLinkParams['REQUISITE_ID'] > 0)
		{
			$requisiteIdLinked = $defRqLinkParams['REQUISITE_ID'];
		}
		$props = $invoice->GetProperties($ID, $personTypeId);
		CCrmInvoice::__RewritePayerInfo($companyId, $contactId, $props);
		CCrmInvoice::rewritePropsFromRequisite($personTypeId, $requisiteIdLinked, $props);
		$formProps = array();
		$propsValues = $invoice->ParsePropertiesValuesFromPost($personTypeId, $formProps, $props);
		if (isset($propsValues['PROPS_VALUES']) && is_array($propsValues['PROPS_VALUES']))
		{
			foreach($propsValues['PROPS_VALUES'] as $propertyId => $propertyValue)
			{
				if (!isset($updateProps[$propertyId]) || $updateProps[$propertyId] === '')
				{
					$fields['INVOICE_PROPERTIES'][$propertyId] = $propertyValue;
				}
			}
			unset($propertyId, $propertyValue);
		}
		unset($companyId, $contactId, $requisiteIdLinked, $props, $propsValues, $formProps);
		//endregion Autocomplete property values

		$DB->StartTransaction();
		$ID = $invoice->Update($ID, $fields, array('UPDATE_SEARCH' => true));
		if(!is_int($ID) || $ID <= 0)
		{
			$DB->Rollback();

			$errMsg = '';
			if (!empty($invoice->LAST_ERROR))
			{
				$errMsg = $invoice->LAST_ERROR;
			}
			else
			{
				$ex = $APPLICATION->GetException();
				if ($ex)
				{
					$APPLICATION->ResetException();
					if ($errMsg == '')
						$errMsg = $ex->GetString();
				}
			}
			throw new RestException((!empty($errMsg) ? $errMsg : 'Unknown error during invoice updating.')."<br />\n");
		}
		else
		{
			Requisite\EntityLink::register(
				CCrmOwnerType::Invoice, $ID,
				$defRqLinkParams['REQUISITE_ID'],
				$defRqLinkParams['BANK_DETAIL_ID'],
				$defRqLinkParams['MC_REQUISITE_ID'],
				$defRqLinkParams['MC_BANK_DETAIL_ID']
			);

			$DB->Commit();
		}

		return $ID;
	}

	public static function delete($params)
	{
		/** @global CMain $APPLICATION*/
		global $APPLICATION, $DB;

		$ID = CCrmInvoiceRestUtil::getParamScalar($params, 'id', 0);
		if($ID <= 0)
			throw new RestException('Invalid identifier.');

		$invoice = new CCrmInvoice();
		if(!CCrmInvoice::CheckUpdatePermission($ID))
			throw new RestException('Access denied.');

		$DB->StartTransaction();
		if(!$invoice->Delete($ID))
		{
			$DB->Rollback();

			$errMsg = '';
			if (!empty($invoice->LAST_ERROR))
			{
				$errMsg = $invoice->LAST_ERROR;
			}
			else
			{
				$ex = $APPLICATION->GetException();
				if ($ex)
				{
					$APPLICATION->ResetException();
					if ($errMsg == '')
						$errMsg = $ex->GetString();
				}
			}
			throw new RestException((!empty($errMsg) ? $errMsg : 'Unknown error during invoice deleting.')."<br />\n");
		}
		else
		{
			$DB->Commit();
		}

		return $ID;
	}

	public static function getExternalLink($params)
	{
		$id = CCrmInvoiceRestUtil::getParamScalar($params, 'id', 0);
		if(!CCrmInvoice::CheckReadPermission($id))
			throw new RestException('Access denied.');

		return \CCrmInvoice::getPublicLink($id);
	}

	public static function processEvent(array $arParams, array $arHandler)
	{
		$eventName = $arHandler['EVENT_NAME'];
		if(mb_strpos(mb_strtoupper($eventName), 'ONCRMINVOICE') !== 0)
		{
			throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}

		$action = mb_substr($eventName, 12);
		if($action === false || $action === '')
		{
			throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}

		$action = mb_strtoupper($action);
		switch($action)
		{
			case 'ADD':
			case 'UPDATE':
			{
				$fields = isset($arParams[0]) ? $arParams[0] : null;
				$ID = is_array($fields) && isset($fields['ID']) ? (int)$fields['ID'] : 0;
			}
				break;
			case 'DELETE':
			{
				$ID = isset($arParams[0]) ? (int)$arParams[0] : 0;
			}
				break;
			case 'SETSTATUS':
			{
				$fields = isset($arParams[0]) ? $arParams[0] : null;
				$ID = is_array($fields) && isset($fields['ID']) ? (int)$fields['ID'] : 0;
			}
				break;
			default:
				throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}

		if($ID <= 0)
		{
			throw new RestException("Could not find entity ID in fields of event \"{$eventName}\"");
		}

		return array('FIELDS' => array('ID' => $ID));
	}

	private static function getFieldsInfo()
	{
		$fieldsInfo = array(
			"ACCOUNT_NUMBER" => array(
				"type" => "string",
				"size" => "100",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"COMMENTS" => array(
				"type" => "text",
				"level" => 0,
				"required" => false,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"CURRENCY" => array(
				"type" => "string",
				"size" => "3",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"DATE_BILL" => array(
				"type" => "date",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"DATE_INSERT" => array(
				"type" => "datetime",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"DATE_MARKED" => array(
				"type" => "datetime",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"DATE_PAY_BEFORE" => array(
				"type" => "date",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"DATE_PAYED" => array(
				"type" => "datetime",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"DATE_STATUS" => array(
				"type" => "datetime",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"DATE_UPDATE" => array(
				"type" => "datetime",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"CREATED_BY" => array(
				"type" => "integer",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"EMP_PAYED_ID" => array(
				"type" => "integer",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"EMP_STATUS_ID" => array(
				"type" => "integer",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"ID" => array(
				"type" => "integer",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"LID" => array(
				"type" => "string",
				"size" => "2",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"XML_ID" => array(
				"type" => "string",
				"size" => "255",
				"level" => 0,
				"required" => false,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"ORDER_TOPIC" => array(
				"type" => "string",
				"size" => "255",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"PAY_SYSTEM_ID" => array(
				"type" => "integer",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"PAY_VOUCHER_DATE" => array(
				"type" => "date",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"PAY_VOUCHER_NUM" => array(
				"type" => "string",
				"size" => "20",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"PAYED" => array(
				"type" => "string",
				"size" => "1",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"PERSON_TYPE_ID" => array(
				"type" => "integer",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"PRICE" => array(
				"type" => "double",
				"size" => "18,2",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"REASON_MARKED" => array(
				"type" => "string",
				"size" => "255",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"RESPONSIBLE_EMAIL" => array(
				"type" => "string",
				"size" => "255",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"RESPONSIBLE_ID" => array(
				"type" => "integer",
				"size" => "18",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"RESPONSIBLE_LAST_NAME" => array(
				"type" => "string",
				"size" => "50",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"RESPONSIBLE_LOGIN" => array(
				"type" => "string",
				"size" => "50",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"RESPONSIBLE_NAME" => array(
				"type" => "string",
				"size" => "50",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"RESPONSIBLE_PERSONAL_PHOTO" => array(
				"type" => "integer",
				"size" => "18",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"RESPONSIBLE_SECOND_NAME" => array(
				"type" => "string",
				"size" => "50",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"RESPONSIBLE_WORK_POSITION" => array(
				"type" => "string",
				"size" => "255",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"STATUS_ID" => array(
				"type" => "string",
				"size" => "1",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"TAX_VALUE" => array(
				"type" => "double",
				"size" => "18,2",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"IS_RECURRING" => array(
				"type" => "string",
				"size" => "1",
				"level" => 0,
				"required" => false,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"UF_COMPANY_ID" => array(
				"type" => "integer",
				"size" => "20",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"UF_CONTACT_ID" => array(
				"type" => "integer",
				"size" => "20",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"UF_MYCOMPANY_ID" => array(
				"type" => "integer",
				"size" => "20",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"UF_DEAL_ID" => array(
				"type" => "integer",
				"size" => "20",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"UF_QUOTE_ID" => array(
				"type" => "integer",
				"size" => "20",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"USER_DESCRIPTION" => array(
				"type" => "string",
				"size" => "2000",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"PR_LOCATION" => array(
				"type" => "integer",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => false,
				"filter" => false,
				"order" => false
			),
			"INVOICE_PROPERTIES" => array(
				"type" => "aarray",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => false,
				"filter" => false,
				"order" => false
			),
			"INVOICE_PROPERTIES.{}" => array(
				"type" => "integer",
				"level" => 1,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => false,
				"filter" => false,
				"order" => false
			),
			"INVOICE_PROPERTIES.{}." => array(
				"type" => "variable",
				"level" => 2,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => false,
				"filter" => false,
				"order" => false
			),
			"PRODUCT_ROWS" => array(
				"type" => "iarray",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => false,
				"filter" => false,
				"order" => false
			),
			"PRODUCT_ROWS.[]" => array(
				"type" => "integer",
				"level" => 1,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => false,
				"filter" => false,
				"order" => false
			),
			"PRODUCT_ROWS.[].ID" => array(
				"type" => "integer",
				"level" => 2,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => false,
				"filter" => false,
				"order" => false
			),
			"PRODUCT_ROWS.[].PRICE" => array(
				"type" => "double",
				"size" => "18,2",
				"level" => 2,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => false,
				"filter" => false,
				"order" => false
			),
			"PRODUCT_ROWS.[].DISCOUNT_PRICE" => array(
				"type" => "double",
				"size" => "18,2",
				"level" => 2,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => false,
				"filter" => false,
				"order" => false
			),
			"PRODUCT_ROWS.[].PRODUCT_ID" => array(
				"type" => "integer",
				"level" => 2,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => false,
				"filter" => false,
				"order" => false
			),
			"PRODUCT_ROWS.[].PRODUCT_NAME" => array(
				"type" => "string",
				"size" => "255",
				"level" => 2,
				"required" => false,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => false,
				"filter" => false,
				"order" => false
			),
			"PRODUCT_ROWS.[].QUANTITY" => array(
				"type" => "double",
				"size" => "18,2",
				"level" => 2,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => false,
				"filter" => false,
				"order" => false
			),
			"PRODUCT_ROWS.[].VAT_RATE" => array(
				"type" => "double",
				"size" => "18,2",
				"level" => 2,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => false,
				"filter" => false,
				"order" => false
			),
			"PRODUCT_ROWS.[].VAT_INCLUDED" => array(
				"type" => "string",
				"size" => "1",
				"level" => 2,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => false,
				"filter" => false,
				"order" => false
			),
			"PRODUCT_ROWS.[].MEASURE_CODE" => array(
				"type" => "integer",
				"level" => 2,
				"required" => false,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"PRODUCT_ROWS.[].MEASURE_NAME" => array(
				"type" => "string",
				"level" => 2,
				"required" => false,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"PRODUCT_ROWS.[].MODULE" => array(
				"type" => "string",
				"level" => 2,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"PRODUCT_ROWS.[].CATALOG_XML_ID" => array(
				"type" => "string",
				"level" => 2,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"PRODUCT_ROWS.[].PRODUCT_XML_ID" => array(
				"type" => "string",
				"level" => 2,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			)
		);

		return $fieldsInfo;
	}

	private static function makeFieldInfo($fInfo)
	{
		$result = array();
		$result['type'] = $fInfo['type'];
		if (isset($fInfo['size']))
			$result['size'] = $fInfo['size'];
		$result['isRequired'] = $fInfo['required'];
		$result['isReadOnly'] = $fInfo['readonly'];

		return $result;
	}

	private static function prepareUserFieldsInfo(&$fieldsInfo, $entityTypeID)
	{
		$userType = new CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], $entityTypeID);
		$userType->PrepareFieldsInfo($fieldsInfo);
	}

	private static function filterFields($fields, $method, $keepUserFields = true)
	{
		$result = array();

		if (!is_array($fields) || count($fields) === 0)
			return $result;

		if (!in_array($method, array('get', 'add', 'update', 'list'), true))
			return $result;

		$bTaxMode = CCrmTax::isTaxMode();

		$fieldsInfo = self::getFieldsInfo();
		$allowedFields = array();
		foreach ($fieldsInfo as $fName => $fInfo)
		{
			if ($fInfo[$method] === true)
			{
				if ($fName !== 'PR_LOCATION')
				{
					$allowedFields[] = $fName;
				}
				else
				{
					if ($bTaxMode)
						$allowedFields[] = $fName;
				}
			}
		}
		unset($fName, $fInfo);

		// user fields
		if ($keepUserFields)
		{
			$userFields = CCrmInvoice::GetUserFields();
			foreach(array_keys($userFields) as $fieldName)
				$allowedFields[] = $fieldName;
			unset($userFields);
		}

		foreach ($fields as $fName => $fValue)
		{
			if ($fName !== 'INVOICE_PROPERTIES' && $fName !== 'PRODUCT_ROWS' && in_array($fName, $allowedFields))
				$result[$fName] = CCrmInvoiceRestUtil::convertValue($method, $fieldsInfo[$fName]['type'], $fValue);
		}

		if (isset($fields['INVOICE_PROPERTIES']) && is_array($fields['INVOICE_PROPERTIES'])
			&& in_array('INVOICE_PROPERTIES', $allowedFields, true))
		{
			$props = array();
			foreach ($fields['INVOICE_PROPERTIES'] as $k => $v)
			{
				if (!is_array($v) /*&& preg_match('/^[A-Za-z0-9_\-]+$/',strval($k))*/)
				{
					$props[$k] = $v;
				}
			}
			if (count($props) > 0)
				$result['INVOICE_PROPERTIES'] = $props;
			unset($props, $k, $v);
		}

		if (isset($fields['PRODUCT_ROWS']) && is_array($fields['PRODUCT_ROWS'])
			&& in_array('PRODUCT_ROWS', $allowedFields))
		{
			$products = array();
			foreach ($fields['PRODUCT_ROWS'] as $productRow)
			{
				$row = array();
				foreach ($productRow as $k => $v)
				{
					if (in_array('PRODUCT_ROWS.[].'.$k, $allowedFields, true))
						$row[$k] = CCrmInvoiceRestUtil::convertValue($method, $fieldsInfo['PRODUCT_ROWS.[].'.$k]['type'], $v);
				}
				if (count($row) > 0)
				{
					$row['CUSTOMIZED'] = 'Y';    // don't update price from catalog
					$products[] = $row;
				}
			}
			if (count($products) > 0)
				$result['PRODUCT_ROWS'] = $products;
			unset($products, $productRow, $k, $v, $row);
		}
		unset($fieldsInfo);

		return $result;
	}

	private static function getInvoiceDataByID($ID)
	{
		$arInvoice = CCrmInvoice::GetByID($ID);
		if(!is_array($arInvoice))
			throw new RestException('Not found.');

		$arProperties = CCrmInvoice::GetProperties($ID, $arInvoice['PERSON_TYPE_ID']);
		$arAllowedProperties = CCrmInvoice::GetPropertiesInfo($arInvoice['PERSON_TYPE_ID'], true);
		$arAllowedProperties = is_array($arAllowedProperties[$arInvoice['PERSON_TYPE_ID']]) ?
			array_keys($arAllowedProperties[$arInvoice['PERSON_TYPE_ID']]) : array();
		$arPropertiesResult = array();
		foreach ($arProperties as $k => $v)
		{
			if ($k !== 'PR_LOCATION')
			{
				if (in_array($v['FIELDS']['CODE'], $arAllowedProperties))
					$arPropertiesResult[$v['FIELDS']['CODE']] = $v['VALUE'];
			}
			else
				$arInvoice['PR_LOCATION'] = $v['VALUE'];
		}

		$arProducts = CCrmInvoice::GetProductRows($ID);

		$result = $arInvoice;
		if (count($arPropertiesResult) > 0)
		{
			// ua company name hack
			if (!isset($arPropertiesResult['COMPANY']) && isset($arPropertiesResult['COMPANY_NAME']))
			{
				$arPropertiesResult['COMPANY'] = $arPropertiesResult['COMPANY_NAME'];
				unset($arPropertiesResult['COMPANY_NAME']);
			}

			$result['INVOICE_PROPERTIES'] = $arPropertiesResult;
		}
		if (count($arProducts) > 0)
			$result['PRODUCT_ROWS'] = $arProducts;

		return $result;
	}

	private static function isRequiredUserFieldCheckEnabled()
	{
		return RestSettings::getCurrent()->isRequiredUserFieldCheckEnabled();
	}

	private static function getAllowedFilterOperations()
	{
		if (self::$arAllowedFilterOperations === null)
		{
			$isOrderConverted = \Bitrix\Main\Config\Option::get("main", "~sale_converted_15", 'Y');
			if ($isOrderConverted != 'N')
			{
				self::$arAllowedFilterOperations = array(
					'', '!><', '!=%', '!%=', '!==', '!=', '!%', '><', '>=', '<=', '=%', '%=', '!@', '==', '=', '%', '?',
					'>', '<', '!', '@', '*', '*=', '*%'
				);
			}
			else
			{
				self::$arAllowedFilterOperations = array('', '!', '+', '>=', '>', '<=', '<', '@', '~', '=%', '%');
			}
		}

		return self::$arAllowedFilterOperations;
	}

	private static function prepareFilter($arFilter)
	{
		if(!is_array($arFilter))
		{
			$arFilter = array();
		}
		else
		{
			$fieldsInfo = self::getFieldsInfo();
			$arAllowedFilterFields = array();
			foreach ($fieldsInfo as $fieldName => $fieldInfo)
			{
				if ($fieldInfo['filter'] === true)
					$arAllowedFilterFields[] = $fieldName;
			}
			$userFields = CCrmInvoice::GetUserFields();
			if (!is_array($userFields))
				$userFields = array();
			foreach (array_keys($userFields) as $fieldName)
				$arAllowedFilterFields[] = $fieldName;

			if (count($arFilter) > 0)
			{
				$arFilter = array_change_key_case($arFilter, CASE_UPPER);
				foreach ($arFilter as $key => $value)
				{
					$matches = array();
					if(preg_match('/^([^a-zA-Z]*)(.*)/', $key, $matches))
					{
						$operation = $matches[1];
						$field = $matches[2];

						if(!in_array($field, $arAllowedFilterFields, true)
							|| !in_array($operation, self::getAllowedFilterOperations(), true))
						{
							unset($arFilter[$key]);
						}
						else
						{
							switch ($fieldsInfo[$field]['type'])
							{
								case 'datetime':
									if ($value === '')
									{
										$arFilter[$key] = '';
									}
									else
									{
										$datetimeValue = CRestUtil::unConvertDateTime($value, true);
										if (is_string($datetimeValue))
										{
											$arFilter[$key] = $datetimeValue;
										}
										else
										{
											unset($arFilter[$key]);
										}
									}
									break;

								case 'date':
									if ($value === '')
									{
										$arFilter[$key] = '';
									}
									else
									{
										$dateValue = CRestUtil::unConvertDate($value);
										if (is_string($dateValue))
										{
											$arFilter[$key] = $dateValue;
										}
										else
										{
											unset($arFilter[$key]);
										}
									}
									break;

								default:
									break;
							}

							switch($field)
							{
								case 'CHECK_PERMISSIONS':
									unset($arFilter[$key]);
									break;

								default:
									break;
							}
						}
					}
					else
					{
						unset($arFilter[$key]);
					}
				}
			}
		}

		$userType = new CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], CCrmInvoice::$sUFEntityID);
		$userType->ListPrepareFilter($arFilter);

		return $arFilter;
	}
	
	private static function prepareSelect($arSelect)
	{
		$arResult = array();

		if (is_array($arSelect))
		{
			$bAllFields = false;
			if (count($arSelect) === 0 || in_array('*', $arSelect, true))
				$bAllFields = true;

			$fieldsInfo = self::getFieldsInfo();
			foreach ($fieldsInfo as $fieldName => $fieldInfo)
			{
				if (isset($fieldInfo['list']) && $fieldInfo['list'] === true)
				{
					if ($bAllFields || in_array($fieldName, $arSelect, true))
						$arResult[] = $fieldName;
				}
			}
		}

		// user fields
		if(is_array($arSelect))
		{
			$userFields = CCrmInvoice::GetUserFields();

			if(in_array("UF_*", $arSelect))
			{
				foreach($userFields as $fieldName => $arField)
					$arResult[] = $fieldName;
			}
			else
			{
				foreach($arSelect as $fieldName)
				{
					if(array_key_exists($fieldName, $userFields))
						$arResult[] = $fieldName;
				}
			}
		}

		return $arResult;
	}

	private static function prepareOrder($arOrder)
	{
		$arResult = array();

		if (is_array($arOrder))
		{
			$fieldsInfo = self::getFieldsInfo();
			$userFields = CCrmInvoice::GetUserFields();
			foreach ($arOrder as $fieldName => $sortName)
			{
				$sortName = mb_strtoupper($sortName);
				if (isset($fieldsInfo[$fieldName])
					&& $fieldsInfo[$fieldName]['order'] === true
					&& ($sortName === 'ASC' || $sortName === 'DESC'))
				{
					$arResult[$fieldName] = $arOrder[$fieldName];
				}
				else
				{
					if(array_key_exists($fieldName, $userFields))
						$arResult[$fieldName] = ($sortName != 'ASC') ? 'DESC': 'ASC';
				}
			}
		}

		return $arResult;
	}

	private static function getCurrentUser()
	{
		return self::$currentUser !== null
			? self::$currentUser
			: (self::$currentUser = CCrmSecurityHelper::GetCurrentUser());
	}

	private static function getCurrentUserID()
	{
		return self::getCurrentUser()->GetID();
	}

	private static function getAuthToken()
	{
		if(is_object(self::$server) || !(self::$server instanceof CRestServer))
		{
			return '';
		}

		$auth = self::$server->getAuth();
		return is_array($auth) && isset($auth['auth']) ? $auth['auth'] : '';
	}

	private static function isAssociativeArray($ary)
	{
		if(!is_array($ary))
		{
			return false;
		}

		$keys = array_keys($ary);
		foreach($keys as $k)
		{
			if (!is_int($k))
			{
				return true;
			}
		}
		return false;
	}

	private static function isIndexedArray($ary)
	{
		if(!is_array($ary))
		{
			return false;
		}

		$keys = array_keys($ary);
		foreach($keys as $k)
		{
			if (!is_int($k))
			{
				return false;
			}
		}
		return true;
	}

	private static function prepareWebDavIBlock($settings = null)
	{
		if(self::$webdavIBlock !== null)
		{
			return self::$webdavIBlock;
		}

		if(!CModule::IncludeModule('webdav'))
		{
			throw new RestException('Could not load webdav module.');
		}

		if(!is_array($settings) || empty($settings))
		{
			$settings = self::getWebDavSettings();
		}

		$iblockID = isset($settings['IBLOCK_ID']) ? $settings['IBLOCK_ID'] : 0;
		if($iblockID <= 0)
		{
			throw new RestException('Could not find webdav iblock.');
		}

		$sectionId = isset($settings['IBLOCK_SECTION_ID']) ? $settings['IBLOCK_SECTION_ID'] : 0;
		if($sectionId <= 0)
		{
			throw new RestException('Could not find webdav section.');
		}

		$user = CCrmSecurityHelper::GetCurrentUser();
		self::$webdavIBlock = new CWebDavIblock(
			$iblockID,
			'',
			array(
				'ROOT_SECTION_ID' => $sectionId,
				'DOCUMENT_TYPE' => array('webdav', 'CIBlockDocumentWebdavSocnet', 'iblock_'.$sectionId.'_user_'.$user->GetID())
			)
		);

		return self::$webdavIBlock;
	}

	private static function getWebDavSettings()
	{
		if(self::$webdavSettings !== null)
		{
			return self::$webdavSettings;
		}

		if(!CModule::IncludeModule('webdav'))
		{
			throw new RestException('Could not load webdav module.');
		}

		$opt = COption::GetOptionString('webdav', 'user_files', null);
		if($opt == null)
		{
			throw new RestException('Could not find webdav settings.');
		}

		$user = CCrmSecurityHelper::GetCurrentUser();

		$opt = unserialize($opt, ['allowed_classes' => false]);
		$iblockID = intval($opt[CSite::GetDefSite()]['id']);
		$userSectionID = CWebDavIblock::getRootSectionIdForUser($iblockID, $user->GetID());
		if(!is_numeric($userSectionID) || $userSectionID <= 0)
		{
			throw new RestException('Could not find webdav section for user '.$user->GetLastName().'.');
		}

		return (self::$webdavSettings =
			array(
				'IBLOCK_ID' => $iblockID,
				'IBLOCK_SECTION_ID' => intval($userSectionID),
			)
		);
	}

	private static function internalizeUserFields(&$fields, $options)
	{
		if(!is_array($fields))
		{
			return;
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$fieldsInfo = array();
		$userType = new CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], CCrmInvoice::$sUFEntityID);
		$userType->PrepareFieldsInfo($fieldsInfo);

		$ignoredAttrs = isset($options['IGNORED_ATTRS']) ? $options['IGNORED_ATTRS'] : array();
		if(!in_array(CCrmFieldInfoAttr::Hidden, $ignoredAttrs, true))
		{
			$ignoredAttrs[] = CCrmFieldInfoAttr::Hidden;
		}
		if(!in_array(CCrmFieldInfoAttr::ReadOnly, $ignoredAttrs, true))
		{
			$ignoredAttrs[] = CCrmFieldInfoAttr::ReadOnly;
		}

		foreach($fields as $k => $v)
		{
			$info = isset($fieldsInfo[$k]) ? $fieldsInfo[$k] : null;
			if(!$info)
			{
				continue;
			}

			$attrs = isset($info['ATTRIBUTES']) ? $info['ATTRIBUTES'] : array();
			$isMultiple = in_array(CCrmFieldInfoAttr::Multiple, $attrs, true);

			$ary = array_intersect($ignoredAttrs, $attrs);
			if(!empty($ary))
			{
				unset($fields[$k]);
				continue;
			}

			$fieldType = isset($info['TYPE']) ? $info['TYPE'] : '';
			if($fieldType === 'date' || $fieldType === 'datetime')
			{
				$date = $fieldType === 'date' ? CRestUtil::unConvertDate($v) : CRestUtil::unConvertDateTime($v);
				if($isMultiple)
				{
					if(!is_array($date))
					{
						$date = array($date);
					}

					$dates = array();
					foreach($date as $item)
					{
						if(is_string($item))
						{
							$dates[] = $item;
						}
					}

					if(!empty($dates))
					{
						$fields[$k] = $dates;
					}
					else
					{
						unset($fields[$k]);
					}
				}
				elseif(is_string($date))
				{
					$fields[$k] = $date;
				}
				else
				{
					unset($fields[$k]);
				}
			}
			elseif($fieldType === 'file')
			{
				self::tryInternalizeFileField($fields, $k, $isMultiple);
			}
			elseif($fieldType === 'webdav')
			{
				self::tryInternalizeWebDavElementField($fields, $k, $isMultiple);
			}
			elseif($fieldType === 'diskfile')
			{
				self::tryInternalizeDiskFileField($fields, $k, $isMultiple);
			}
		}
	}

	private static function externalizeUserFields(&$fields)
	{
		if(!is_array($fields))
		{
			return;
		}

		$fieldsInfo = array();
		$userType = new CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], CCrmInvoice::$sUFEntityID);
		$userType->PrepareFieldsInfo($fieldsInfo);

		if (empty($fieldsInfo))
			return;

		foreach($fields as $k => $v)
		{
			$info = isset($fieldsInfo[$k]) ? $fieldsInfo[$k] : null;
			if(!$info)
			{
				continue;
			}

			$attrs = isset($info['ATTRIBUTES']) ? $info['ATTRIBUTES'] : array();
			$isMultiple = in_array(CCrmFieldInfoAttr::Multiple, $attrs, true);
			$isHidden = in_array(CCrmFieldInfoAttr::Hidden, $attrs, true);
			$isDynamic = in_array(CCrmFieldInfoAttr::Dynamic, $attrs, true);

			if($isHidden)
			{
				unset($fields[$k]);
				continue;
			}

			$fieldType = isset($info['TYPE']) ? $info['TYPE'] : '';
			if($fieldType === 'date')
			{
				if(!is_array($v))
				{
					$fields[$k] = CRestUtil::ConvertDate($v);
				}
				else
				{
					$fields[$k] = array();
					foreach($v as &$value)
					{
						$fields[$k][] = CRestUtil::ConvertDate($value);
					}
					unset($value);
				}
			}
			elseif($fieldType === 'datetime')
			{
				if(!is_array($v))
				{
					$fields[$k] = CRestUtil::ConvertDateTime($v);
				}
				else
				{
					$fields[$k] = array();
					foreach($v as &$value)
					{
						$fields[$k][] = CRestUtil::ConvertDateTime($value);
					}
					unset($value);
				}
			}
			elseif($fieldType === 'file')
			{
				self::tryExternalizeFileField($fields, $k, $isMultiple, $isDynamic);
			}
			elseif($fieldType === 'webdav')
			{
				self::tryExternalizeWebDavElementField($fields, $k, $isMultiple);
			}
			elseif($fieldType === 'diskfile')
			{
				self::tryExternalizeDiskFileField($fields, $k, $isMultiple);
			}
		}
	}

	private static function tryInternalizeFileField(&$fields, $fieldName, $multiple = false)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		$result = array();

		$values = $multiple && self::isIndexedArray($fields[$fieldName]) ? $fields[$fieldName] : array($fields[$fieldName]);
		foreach($values as &$v)
		{
			if(!self::isAssociativeArray($v))
			{
				continue;
			}

			$fileID = isset($v['id']) ? intval($v['id']) : 0;
			$removeFile = isset($v['remove']) && is_string($v['remove']) && mb_strtoupper($v['remove']) === 'Y';
			$fileData = isset($v['fileData']) ? $v['fileData'] : '';

			if(!self::isIndexedArray($fileData))
			{
				$fileName = '';
				$fileContent = $fileData;
			}
			else
			{
				$fileDataLength = count($fileData);

				if($fileDataLength > 1)
				{
					$fileName = $fileData[0];
					$fileContent = $fileData[1];
				}
				elseif($fileDataLength === 1)
				{
					$fileName = '';
					$fileContent = $fileData[0];
				}
				else
				{
					$fileName = '';
					$fileContent = '';
				}
			}

			if(is_string($fileContent) && $fileContent !== '')
			{
				// Add/replace file
				$fileInfo = CRestUtil::saveFile($fileContent, $fileName);
				if(is_array($fileInfo))
				{
					if($fileID > 0)
					{
						$fileInfo['old_id'] = $fileID;
					}

					//In this case 'del' flag does not make sense - old file will be replaced by new one.
					/*if($removeFile)
					{
						$fileInfo['del'] = true;
					}*/

					$result[] = &$fileInfo;
					unset($fileInfo);
				}
			}
			elseif($fileID > 0 && $removeFile)
			{
				// Remove file
				$result[] = array(
					'old_id' => $fileID,
					'del' => true
				);
			}
		}
		unset($v);

		if($multiple)
		{
			$fields[$fieldName] = $result;
			return true;
		}
		elseif(!empty($result))
		{
			$fields[$fieldName] = $result[0];
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}

	private static function tryExternalizeFileField(&$fields, $fieldName, $multiple = false, $dynamic = true)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		$ownerTypeID = CCrmOwnerType::Invoice;
		$ownerID = isset($fields['ID']) ? intval($fields['ID']) : 0;
		if(!$multiple)
		{
			$fileID = intval($fields[$fieldName]);
			if($fileID <= 0)
			{
				unset($fields[$fieldName]);
				return false;
			}

			$fields[$fieldName] = self::externalizeFile($ownerTypeID, $ownerID, $fieldName, $fileID, $dynamic);
		}
		else
		{
			$result = array();
			$filesID = $fields[$fieldName];
			if(!is_array($filesID))
			{
				$filesID = array($filesID);
			}

			foreach($filesID as $fileID)
			{
				$fileID = intval($fileID);
				if($fileID > 0)
				{
					$result[] = self::externalizeFile($ownerTypeID, $ownerID, $fieldName, $fileID, $dynamic);
				}
			}
			$fields[$fieldName] = &$result;
			unset($result);
		}

		return true;
	}

	private static function externalizeFile($ownerTypeID, $ownerID, $fieldName, $fileID, $dynamic = true)
	{
		$ownerTypeName = mb_strtolower(CCrmOwnerType::ResolveName($ownerTypeID));
		if($ownerTypeName === '')
		{
			return '';
		}

		$handlerUrl = "/bitrix/components/bitrix/crm.{$ownerTypeName}.show/show_file.php";
		$showUrl = CComponentEngine::makePathFromTemplate(
			"{$handlerUrl}?ownerId=#owner_id#&fieldName=#field_name#&dynamic=#dynamic#&fileId=#file_id#",
			array(
				'field_name' => $fieldName,
				'file_id' => $fileID,
				'owner_id' => $ownerID,
				'dynamic' => $dynamic ? 'Y' : 'N'
			)
		);

		$downloadUrl = CComponentEngine::makePathFromTemplate(
			"{$handlerUrl}?auth=#auth#&ownerId=#owner_id#&fieldName=#field_name#&dynamic=#dynamic#&fileId=#file_id#",
			array(
				'auth' => self::getAuthToken(),
				'field_name' => $fieldName,
				'file_id' => $fileID,
				'owner_id' => $ownerID,
				'dynamic' => $dynamic ? 'Y' : 'N'
			)
		);

		return array(
			'id' => $fileID,
			'showUrl' => $showUrl,
			'downloadUrl' => $downloadUrl
		);
	}

	private static function tryInternalizeWebDavElementField(&$fields, $fieldName, $multiple = false)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		$result = array();

		$values = $multiple && self::isIndexedArray($fields[$fieldName]) ? $fields[$fieldName] : array($fields[$fieldName]);
		foreach($values as &$v)
		{
			if(!self::isAssociativeArray($v))
			{
				continue;
			}

			$elementID = isset($v['id']) ? intval($v['id']) : 0;
			$removeElement = isset($v['remove']) && is_string($v['remove']) && mb_strtoupper($v['remove']) === 'Y';
			$fileData = isset($v['fileData']) ? $v['fileData'] : '';

			if(!self::isIndexedArray($fileData))
			{
				continue;
			}

			$fileDataLength = count($fileData);
			if($fileDataLength === 0)
			{
				continue;
			}

			if($fileDataLength === 1)
			{
				$fileName = '';
				$fileContent = $fileData[0];
			}
			else
			{
				$fileName = $fileData[0];
				$fileContent = $fileData[1];
			}

			if(is_string($fileContent) && $fileContent !== '')
			{
				$fileInfo = CRestUtil::saveFile($fileContent, $fileName);

				$settings = self::getWebDavSettings();
				$iblock = self::prepareWebDavIBlock($settings);
				$fileName = $iblock->CorrectName($fileName);

				$filePath = $fileInfo['tmp_name'];
				$options = array(
					'new' => true,
					'dropped' => false,
					'arDocumentStates' => array(),
					'arUserGroups' => $iblock->USER['GROUPS'],
					'TMP_FILE' => $filePath,
					'FILE_NAME' => $fileName,
					'IBLOCK_ID' => $settings['IBLOCK_ID'],
					'IBLOCK_SECTION_ID' => $settings['IBLOCK_SECTION_ID'],
					'WF_STATUS_ID' => 1
				);
				$options['arUserGroups'][] = 'Author';

				global $DB;
				$DB->StartTransaction();
				if (!$iblock->put_commit($options))
				{
					$DB->Rollback();
					unlink($filePath);
					throw new RestException($iblock->LAST_ERROR);
				}
				$DB->Commit();
				unlink($filePath);

				if(!isset($options['ELEMENT_ID']))
				{
					throw new RestException('Could not save webdav element.');
				}

				$elementData = array(
					'ELEMENT_ID' => $options['ELEMENT_ID']
				);

				if($elementID > 0)
				{
					$elementData['OLD_ELEMENT_ID'] = $elementID;
				}

				$result[] = &$elementData;
				unset($elementData);
			}
			elseif($elementID > 0 && $removeElement)
			{
				$result[] = array(
					'OLD_ELEMENT_ID' => $elementID,
					'DELETE' => true
				);
			}
		}
		unset($v);

		if($multiple)
		{
			$fields[$fieldName] = $result;
			return true;
		}
		elseif(!empty($result))
		{
			$fields[$fieldName] = $result[0];
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}

	private static function tryExternalizeWebDavElementField(&$fields, $fieldName, $multiple = false)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		if(!$multiple)
		{
			$elementID = intval($fields[$fieldName]);
			$info = CCrmWebDavHelper::GetElementInfo($elementID, false);
			if(empty($info))
			{
				unset($fields[$fieldName]);
				return false;
			}
			else
			{
				$fields[$fieldName] = array(
					'id' => $elementID,
					'url' => isset($info['SHOW_URL']) ? $info['SHOW_URL'] : ''
				);

				return true;
			}
		}

		$result = array();
		$elementsID = $fields[$fieldName];
		if(is_array($elementsID))
		{
			foreach($elementsID as $elementID)
			{
				$elementID = intval($elementID);
				$info = CCrmWebDavHelper::GetElementInfo($elementID, false);
				if(empty($info))
				{
					continue;
				}

				$result[] = array(
					'id' => $elementID,
					'url' => isset($info['SHOW_URL']) ? $info['SHOW_URL'] : ''
				);
			}
		}

		if(!empty($result))
		{
			$fields[$fieldName] = &$result;
			unset($result);
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}

	private static function tryInternalizeDiskFileField(&$fields, $fieldName, $multiple = false)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		$result = array();

		$values = $multiple && self::isIndexedArray($fields[$fieldName]) ? $fields[$fieldName] : array($fields[$fieldName]);
		foreach($values as &$v)
		{
			if(!self::isAssociativeArray($v))
			{
				continue;
			}

			$fileID = isset($v['id']) ? intval($v['id']) : 0;
			$removeElement = isset($v['remove']) && is_string($v['remove']) && mb_strtoupper($v['remove']) === 'Y';
			$fileData = isset($v['fileData']) ? $v['fileData'] : '';

			if(!self::isIndexedArray($fileData))
			{
				continue;
			}

			$fileDataLength = count($fileData);
			if($fileDataLength === 0)
			{
				continue;
			}

			if($fileDataLength === 1)
			{
				$fileName = '';
				$fileContent = $fileData[0];
			}
			else
			{
				$fileName = $fileData[0];
				$fileContent = $fileData[1];
			}

			if(is_string($fileContent) && $fileContent !== '')
			{
				$folder = DiskManager::ensureFolderCreated(StorageFileType::Rest);
				if(!$folder)
				{
					throw new RestException('Could not create disk folder for rest files.');
				}

				$fileInfo = CRestUtil::saveFile($fileContent, $fileName);
				if(is_array($fileInfo))
				{
					$file = $folder->uploadFile(
						$fileInfo,
						array('NAME' => $fileName, 'CREATED_BY' => self::getCurrentUserID()),
						array(),
						true
					);
					unlink($fileInfo['tmp_name']);

					if(!$file)
					{
						throw new RestException('Could not create disk file.');
					}

					$result[] = array('FILE_ID' => $file->getId());
				}
			}
			elseif($fileID > 0 && $removeElement)
			{
				$result[] = array('OLD_FILE_ID' => $fileID, 'DELETE' => true);
			}
		}
		unset($v);

		if($multiple)
		{
			$fields[$fieldName] = $result;
			return true;
		}
		elseif(!empty($result))
		{
			$fields[$fieldName] = $result[0];
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}

	private static function tryExternalizeDiskFileField(&$fields, $fieldName, $multiple = false)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		$options = array(
			'OWNER_TYPE_ID' => CCrmOwnerType::Invoice,
			'OWNER_ID' => $fields['ID'],
			'VIEW_PARAMS' => array('auth' => self::getAuthToken()),
			'USE_ABSOLUTE_PATH' => true
		);

		if(!$multiple)
		{
			$fileID = intval($fields[$fieldName]);
			$info = DiskManager::getFileInfo($fileID, false, $options);
			if(empty($info))
			{
				unset($fields[$fieldName]);
				return false;
			}
			else
			{
				$fields[$fieldName] = array(
					'id' => $fileID,
					'url' => isset($info['VIEW_URL']) ? $info['VIEW_URL'] : ''
				);

				return true;
			}
		}

		$result = array();
		$fileIDs = $fields[$fieldName];
		if(is_array($fileIDs))
		{
			foreach($fileIDs as $fileID)
			{
				$info = DiskManager::getFileInfo($fileID, false, $options);
				if(empty($info))
				{
					continue;
				}

				$result[] = array(
					'id' => $fileID,
					'url' => isset($info['VIEW_URL']) ? $info['VIEW_URL'] : ''
				);
			}
		}

		if(!empty($result))
		{
			$fields[$fieldName] = &$result;
			unset($result);
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}
}

class CCrmRestVat extends IRestService
{
	private static $arAllowedFilterOperations =
		array('', '=', '!', '@', '~', '%', '!+', '+', '+!', '>=', '<=', '>', '<');

	public static function getList($params, $nav = 0)
	{
		if (!CModule::IncludeModule('catalog'))
			throw new RestException('The Commercial Catalog module is not installed.');

		global $USER;

		$CrmPerms = new CCrmPerms($USER->GetID());
		if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
			throw new RestException('Access denied.');

		$order =  CCrmInvoiceRestUtil::getParamArray($params, 'order', array('SORT' => 'ASC'));
		$filter = CCrmInvoiceRestUtil::getParamArray($params, 'filter', array());
		$select = CCrmInvoiceRestUtil::getParamArray($params, 'select', array());

		$result = array();
		$catalogVat = new CCatalogVat();

		$filter = self::prepareFilter($filter);
		$select = self::prepareSelect($select);
		$order = self::prepareOrder($order);

		if (!is_array($select) || count($select) === 0)
			throw new RestException('Inadmissible fields for selection');

		$dbResult = $catalogVat->GetListEx($order, $filter, false, self::getNavData($nav), $select);
		while($arRow = $dbResult->NavNext(false))
			$result[] = self::filterFields($arRow, 'list');

		return self::setNavData($result, $dbResult);
	}

	public static function fields()
	{
		$fieldsInfo = self::getFieldsInfo();

		$fields = array();
		foreach ($fieldsInfo as $fName => $fInfo)
		{
			$fields[$fName] = self::makeFieldInfo($fInfo);
			$name = \CCrmVat::GetFieldCaption($fName);
			$fields[$fName]['title'] = !empty($name) ? $name : $fName;
		}

		return $fields;
	}

	public static function get($params)
	{
		if (!CModule::IncludeModule('catalog'))
			throw new RestException('The Commercial Catalog module is not installed.');

		global $USER;

		$CrmPerms = new CCrmPerms($USER->GetID());
		if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
			throw new RestException('Access denied.');

		$ID = CCrmInvoiceRestUtil::getParamScalar($params, 'id', 0);
		$arResult = CCrmVat::GetByID($ID);
		if ($arResult === false)
			throw new RestException('VAT rate not found.');
		$arResult = self::filterFields($arResult, 'get');

		return $arResult;
	}

	public static function add($params)
	{
		if (!CModule::IncludeModule('catalog'))
			throw new RestException('The Commercial Catalog module is not installed.');

		global $DB, $USER;

		$CrmPerms = new CCrmPerms($USER->GetID());
		if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
			throw new RestException('Access denied.');

		$fields = CCrmInvoiceRestUtil::getParamArray($params, 'fields');

		$fields = self::filterFields($fields, 'add');

		if (!is_array($fields) || count($fields) === 0)
			throw new RestException('Invalid parameters.');

		$DB->StartTransaction();
		$ID = false;
		if (isset($fields['ID']))
			unset($fields['ID']);
		if (count($fields) > 0)
		{
			$catalogVat = new CCatalogVat();
			$ID = $catalogVat->Add($fields);
		}
		if($ID)
		{
			$DB->Commit();
		}
		else
		{
			$DB->Rollback();
			throw new RestException('Error on creating VAT rate.');
		}

		return $ID;
	}

	public static function update($params)
	{
		if (!CModule::IncludeModule('catalog'))
			throw new RestException('The Commercial Catalog module is not installed.');

		global $DB, $USER;

		$CrmPerms = new CCrmPerms($USER->GetID());
		if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
			throw new RestException('Access denied.');

		$ID = intval(CCrmInvoiceRestUtil::getParamScalar($params, 'id', 0));
		if($ID <= 0)
			throw new RestException('Invalid identifier.');

		$fields = CCrmInvoiceRestUtil::getParamArray($params, 'fields');

		$fields = self::filterFields($fields, 'update');

		if (!is_array($fields) || count($fields) === 0)
			throw new RestException('Invalid parameters.');

		$DB->StartTransaction();
		$updatedID = false;
		if (count($fields) > 0)
		{
			$catalogVat = new CCatalogVat();
			$updatedID = $catalogVat->Update($ID, $fields);
		}
		if($updatedID)
		{
			$DB->Commit();
		}
		else
		{
			$DB->Rollback();
			throw new RestException('Error on updating VAT rate.');
		}

		return $updatedID;
	}

	public static function delete($params)
	{
		if (!CModule::IncludeModule('catalog'))
			throw new RestException('The Commercial Catalog module is not installed.');

		global $DB, $USER;

		$CrmPerms = new CCrmPerms($USER->GetID());
		if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
			throw new RestException('Access denied.');

		$ID = CCrmInvoiceRestUtil::getParamScalar($params, 'id', 0);
		if($ID <= 0)
			throw new RestException('Invalid identifier.');

		$DB->StartTransaction();
		$catalogVat = new CCatalogVat();
		$bDeleted = $catalogVat->Delete($ID);
		if($bDeleted)
		{
			$DB->Commit();
		}
		else
		{
			$DB->Rollback();
			throw new RestException('Error on deleting VAT rate.');
		}

		return $bDeleted;
	}

	private static function getFieldsInfo()
	{
		$fieldsInfo = array(
			"ID" => array(
				"type" => "integer",
				"size" => "11",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"TIMESTAMP_X" => array(
				"type" => "datetime",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"ACTIVE" => array(
				"type" => "string",
				"size" => "1",
				"level" => 0,
				"required" => false,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"C_SORT" => array(
				"type" => "integer",
				"size" => "18",
				"level" => 0,
				"required" => false,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"NAME" => array(
				"type" => "string",
				"size" => "50",
				"level" => 0,
				"required" => false,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"RATE" => array(
				"type" => "double",
				"size" => "18,2",
				"level" => 0,
				"required" => false,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
		);

		return $fieldsInfo;
	}

	private static function makeFieldInfo($fInfo)
	{
		$result = array();
		$result['type'] = $fInfo['type'];
		if (isset($fInfo['size']))
			$result['size'] = $fInfo['size'];
		$result['isRequired'] = $fInfo['required'];
		$result['isReadOnly'] = $fInfo['readonly'];

		return $result;
	}

	private static function filterFields($fields, $method)
	{
		$result = array();

		if (!is_array($fields) || count($fields) === 0)
			return $result;

		if (!in_array($method, array('get', 'add', 'update', 'list'), true))
			return $result;

		$fieldsInfo = self::getFieldsInfo();
		$allowedFields = array();
		foreach ($fieldsInfo as $fName => $fInfo)
		{
			if ($fInfo[$method] === true)
				$allowedFields[] = $fName;
		}
		unset($fName, $fInfo);

		foreach ($fields as $fName => $fValue)
		{
			if (in_array($fName, $allowedFields))
				$result[$fName] = CCrmInvoiceRestUtil::convertValue($method, $fieldsInfo[$fName]['type'], $fValue);
		}
		unset($fieldsInfo);

		return $result;
	}

	private static function prepareFilter($arFilter)
	{
		if(!is_array($arFilter))
		{
			$arFilter = array();
		}
		else
		{
			$fieldsInfo = self::getFieldsInfo();
			$arAllowedFilterFields = array();
			foreach ($fieldsInfo as $fieldName => $fieldInfo)
			{
				if ($fieldInfo['filter'] === true)
					$arAllowedFilterFields[] = $fieldName;
			}

			if (count($arFilter) > 0)
			{
				$arFilter = array_change_key_case($arFilter, CASE_UPPER);
				foreach ($arFilter as $key => $value)
				{
					$matches = array();
					if(preg_match('/^([^a-zA-Z]*)(.*)/', $key, $matches))
					{
						$operation = $matches[1];
						$field = $matches[2];

						if(!in_array($field, $arAllowedFilterFields, true)
							|| !in_array($operation, self::$arAllowedFilterOperations, true))
						{
							unset($arFilter[$key]);
						}
						else
						{
							switch ($fieldsInfo[$field]['type'])
							{
								case 'datetime':
									$arFilter[$key] = CRestUtil::unConvertDateTime($value, true);
									break;

								case 'date':
									$arFilter[$key] = CRestUtil::unConvertDate($value);
									break;

								default:
									break;
							}
						}
					}
					else
					{
						unset($arFilter[$key]);
					}
				}
			}
		}

		return $arFilter;
	}

	private static function prepareSelect($arSelect)
	{
		$arResult = array();

		if (is_array($arSelect))
		{
			$bAllFields = false;
			if (count($arSelect) === 0 || in_array('*', $arSelect, true))
				$bAllFields = true;

			$fieldsInfo = self::getFieldsInfo();
			foreach ($fieldsInfo as $fieldName => $fieldInfo)
			{
				if (isset($fieldInfo['list']) && $fieldInfo['list'] === true)
				{
					if ($bAllFields || in_array($fieldName, $arSelect, true))
						$arResult[] = $fieldName;
				}
			}
		}

		return $arResult;
	}

	private static function prepareOrder($arOrder)
	{
		$arResult = array();

		if (is_array($arOrder))
		{
			$fieldsInfo = self::getFieldsInfo();
			foreach ($arOrder as $fieldName => $sortName)
			{
				if (isset($fieldsInfo[$fieldName])
					&& $fieldsInfo[$fieldName]['order'] === true
					&& ($sortName === 'ASC' || $sortName === 'DESC'))
				{
					$arResult[$fieldName] = $arOrder[$fieldName];
				}
			}
		}

		return $arResult;
	}
}
