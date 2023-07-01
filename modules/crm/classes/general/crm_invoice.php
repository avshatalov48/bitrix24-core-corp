<?php

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\CompanyAddress;
use Bitrix\Crm\ContactAddress;
use Bitrix\Crm\EntityAddressType;
use Bitrix\Crm\Format\AddressFormatter;
use Bitrix\Crm\Invoice\Invoice;
use Bitrix\Crm\Invoice\Compatible;
use Bitrix\Crm\Settings;
use Bitrix\Iblock;
use Bitrix\Catalog;

if (!Loader::includeModule('sale'))
	return;

Loc::loadMessages(__FILE__);

class CAllCrmInvoice
{
	static public $sUFEntityID = 'CRM_INVOICE';

	const USER_FIELD_ENTITY_ID = 'CRM_INVOICE';
	const SUSPENDED_USER_FIELD_ENTITY_ID = 'CRM_INVOICE_SPD';
	const TOTAL_COUNT_CACHE_ID = 'crm_invoice_total_count';
	const CACHE_TTL = 3600;

	public $LAST_ERROR = '';
	public $cPerms = null;

	protected $bCheckPermission = true;
	protected static $TYPE_NAME = 'INVOICE';
	protected static $FIELD_INFOS = null;

	private static $INVOICE_STATUSES = null;
	private static $INVOICE_PROPERTY_INFOS = null;
	private static $INVOICE_PAY_SYSTEM_TYPES = null;
	private static $arCurrentPermType = null;
	private static $arinvoicePropertiesAllowed = [];
	private static $LIST_CALLBACK_PARAMS = null;

	function __construct($bCheckPermission = true)
	{
		$this->bCheckPermission = $bCheckPermission;
		$this->cPerms = CCrmPerms::GetCurrentUserPermissions();
	}

	public function CheckFields(&$arFields, $ID = false, $bStatusSuccess = true, $bStatusFailed = true,
		$options = array())
	{
		/** @global CUserTypeManager $USER_FIELD_MANAGER */
		/** @global CMain $APPLICATION */
		global $APPLICATION, $USER_FIELD_MANAGER;

		$this->LAST_ERROR = '';

		$bTaxMode = CCrmTax::isTaxMode();

		if (!isset($arFields['PRODUCT_ROWS']) || !is_array($arFields['PRODUCT_ROWS']) || count($arFields['PRODUCT_ROWS']) === 0)
		{
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_EMPTY_INVOICE_SPEC')."<br />\n";
		}
		else
		{
			$invalidQuantityExists = false;
			foreach ($arFields['PRODUCT_ROWS'] as $productRow)
			{
				if (!isset($productRow['QUANTITY']) || round(doubleval($productRow['QUANTITY']), 4) <= 0.0)
				{
					$invalidQuantityExists = true;
					break;
				}
			}
			unset($productRow);

			if ($invalidQuantityExists)
				$this->LAST_ERROR .= GetMessage('CRM_ERROR_INVOICE_SPEC_INVALID_QUANTITY')."<br />\n";

			unset($invalidQuantityExists);
		}

		if ($ID !== false && isset($arFields['ACCOUNT_NUMBER']))
		{
			if ($arFields['ACCOUNT_NUMBER'] == '')
				$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_IS_MISSING', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_ACCOUNT_NUMBER')))."<br />\n";
		}

		if (($ID == false || isset($arFields['ORDER_TOPIC'])) && $arFields['ORDER_TOPIC'] == '')
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_IS_MISSING', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_ORDER_TOPIC')))."<br />\n";

		if (!empty($arFields['ORDER_TOPIC']) && mb_strlen($arFields['ORDER_TOPIC']) > 255)
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_ORDER_TOPIC')))."<br />\n";

		if (!empty($arFields['COMMENTS']) && mb_strlen($arFields['COMMENTS']) > 2000)
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_COMMENTS')))
				.' ('.GetMessage('CRM_FIELD_COMMENTS_INCORRECT_INFO').").<br />\n";

		if (!empty($arFields['USER_DESCRIPTION']) && mb_strlen($arFields['USER_DESCRIPTION']) > 2000)
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_USER_DESCRIPTION')))
				.' ('.GetMessage('CRM_FIELD_USER_DESCRIPTION_INCORRECT_INFO').").<br />\n";

		$crmStatus = new CCrmStatus('INVOICE_STATUS');
		if (empty($arFields['STATUS_ID']) || !$crmStatus->CheckStatusId($arFields['STATUS_ID']))
		{
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_STATUS_ID')))."<br />\n";
		}
		unset($crmStatus);

		if ($bStatusSuccess)
		{
			if (!empty($arFields['PAY_VOUCHER_NUM']) && mb_strlen($arFields['PAY_VOUCHER_NUM']) > 20)
				$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_PAY_VOUCHER_NUM')))."<br />\n";
			if (!empty($arFields['PAY_VOUCHER_DATE']) && !CheckDateTime($arFields['PAY_VOUCHER_DATE']))
				$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_PAY_VOUCHER_DATE')))."<br />\n";
			if (!empty($arFields['REASON_MARKED']) && mb_strlen($arFields['REASON_MARKED']) > 255)
				$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_REASON_MARKED_SUCCESS')))."<br />\n";
		}
		elseif ($bStatusFailed)
		{
			if (!empty($arFields['DATE_MARKED']) && !CheckDateTime($arFields['DATE_MARKED']))
				$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_DATE_MARKED')))."<br />\n";
			if (!empty($arFields['REASON_MARKED']) && mb_strlen($arFields['REASON_MARKED']) > 255)
				$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_REASON_MARKED')))."<br />\n";
		}

		if (!isset($arFields['PERSON_TYPE_ID']) || intval($arFields['PERSON_TYPE_ID']) <= 0
			|| (intval($arFields['UF_COMPANY_ID']) <= 0 && intval($arFields['UF_CONTACT_ID']) <= 0))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_PAYER_IS_MISSING')."<br />\n";

		if ($bTaxMode)
		{
			if (!isset($arFields['PR_LOCATION']) || intval($arFields['PR_LOCATION']) <= 0)
				$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_IS_MISSING', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_PR_LOCATION')))."<br />\n";
		}

		if (!isset($arFields['PAY_SYSTEM_ID']) || intval($arFields['PAY_SYSTEM_ID']) <= 0)
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_IS_MISSING', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_PAY_SYSTEM_ID')))."<br />\n";

		if (!empty($arFields['DATE_INSERT']) && !CheckDateTime($arFields['DATE_INSERT']))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_DATE_INSERT')))."<br />\n";

		if (!empty($arFields['DATE_BILL']) && !CheckDateTime($arFields['DATE_BILL']))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_DATE_BILL')))."<br />\n";

		if (!empty($arFields['DATE_PAY_BEFORE']) && !CheckDateTime($arFields['DATE_PAY_BEFORE']))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_DATE_PAY_BEFORE')))."<br />\n";

		if(!is_array($options))
		{
			$options = array();
		}

		$enableUserFieldCheck = !(isset($options['DISABLE_USER_FIELD_CHECK'])
			&& $options['DISABLE_USER_FIELD_CHECK'] === true);

		if ($enableUserFieldCheck)
		{
			CCrmEntityHelper::NormalizeUserFields(
				$arFields, self::$sUFEntityID, $USER_FIELD_MANAGER, array('IS_NEW' => ($ID == false))
			);

			$enableRequiredUserFieldCheck = !(isset($options['DISABLE_REQUIRED_USER_FIELD_CHECK'])
				&& $options['DISABLE_REQUIRED_USER_FIELD_CHECK'] === true);

			if(!$USER_FIELD_MANAGER->CheckFields(
				self::$sUFEntityID, $ID, $arFields, false, $enableRequiredUserFieldCheck))
			{
				$e = $APPLICATION->GetException();
				$this->LAST_ERROR .= $e->GetString();
			}
		}

		if ($this->LAST_ERROR <> '')
			return false;

		return true;
	}

	public function CheckFieldsUpdate(&$arFields, $ID = false)
	{
		/** @global CUserTypeManager $USER_FIELD_MANAGER */
		/** @global CMain $APPLICATION */
		global $APPLICATION, $USER_FIELD_MANAGER;

		$this->LAST_ERROR = '';

		if (isset($arFields['ORDER_TOPIC']) && empty($arFields['ORDER_TOPIC']))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_ORDER_TOPIC')))."<br />\n";

		CCrmEntityHelper::NormalizeUserFields($arFields, self::$sUFEntityID, $USER_FIELD_MANAGER, array('IS_NEW' => ($ID == false)));
		if(!$USER_FIELD_MANAGER->CheckFields(self::$sUFEntityID, $ID, $arFields))
		{
			$e = $APPLICATION->GetException();
			$this->LAST_ERROR .= $e->GetString();
		}

		if ($this->LAST_ERROR <> '')
			return false;

		return true;
	}

	// Get Fields Metadata
	public static function GetFieldsInfo()
	{
		if(!self::$FIELD_INFOS)
		{
			self::$FIELD_INFOS = array(
				"ID" => array(
					"TYPE" => "integer",
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				"LID" => array(
					"TYPE" => "string",
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				"ACCOUNT_NUMBER" => array(
					"TYPE" => "string",
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				),
				"CURRENCY" => array(
					"TYPE" => "string",
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				"ORDER_TOPIC" => array(
					"TYPE" => "string",
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				),
				"DATE_BILL" => array(
					"TYPE" => "date",
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				),
				"DATE_INSERT" => array(
					"TYPE" => "datetime",
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				),
				"DATE_MARKED" => array(
					"TYPE" => "datetime"
				),
				"DATE_PAY_BEFORE" => array(
					"TYPE" => "date"
				),
				"DATE_PAYED" => array(
					"TYPE" => "datetime",
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				"DATE_STATUS" => array(
					"TYPE" => "datetime",
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				"DATE_UPDATE" => array(
					"TYPE" => "datetime",
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				"EMP_PAYED_ID" => array(
					"TYPE" => "integer",
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				"EMP_STATUS_ID" => array(
					"TYPE" => "integer",
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				"PAY_SYSTEM_ID" => array(
					"TYPE" => "integer",
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				),
				"STATUS_ID" => array(
					"TYPE" => "string",
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				),
				"PAY_VOUCHER_DATE" => array(
					"TYPE" => "date"
				),
				"PAY_VOUCHER_NUM" => array(
					"TYPE" => "string"
				),
				"PAYED" => array(
					"TYPE" => "char",
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				"PERSON_TYPE_ID" => array(
					"TYPE" => "integer",
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				),
				"PRICE" => array(
					"TYPE" => "double",
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				"REASON_MARKED" => array(
					"TYPE" => "string"
				),
				"RESPONSIBLE_ID" => array(
					"TYPE" => "integer"
				),
				"RESPONSIBLE_EMAIL" => array(
					"TYPE" => "string",
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				"RESPONSIBLE_LOGIN" => array(
					"TYPE" => "string",
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				"RESPONSIBLE_NAME" => array(
					"TYPE" => "string",
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				"RESPONSIBLE_LAST_NAME" => array(
					"TYPE" => "string",
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				"RESPONSIBLE_SECOND_NAME" => array(
					"TYPE" => "string",
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				"RESPONSIBLE_WORK_POSITION" => array(
					"TYPE" => "string",
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				"RESPONSIBLE_PERSONAL_PHOTO" => array(
					"TYPE" => "integer",
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				"UF_COMPANY_ID" => array(
					"TYPE" => "integer",
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				),
				"UF_CONTACT_ID" => array(
					"TYPE" => "integer",
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				),
				"UF_MYCOMPANY_ID" => array(
					"TYPE" => "integer",
				),
				"UF_DEAL_ID" => array(
					"TYPE" => "integer",
				),
				"UF_QUOTE_ID" => array(
					"TYPE" => "integer",
				),
				"COMMENTS" => array(
					"TYPE" => "string",
					'VALUE_TYPE' => 'html',
				),
				"USER_DESCRIPTION" => array(
					"TYPE" => "string"
				),
				"TAX_VALUE" => array(
					"TYPE" => "double",
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				"PR_LOCATION" => array(
					"TYPE" => "integer"
				)
			);
		}

		return self::$FIELD_INFOS;
	}

	public static function GetUserFieldEntityID()
	{
		return self::$sUFEntityID;
	}
	public static function GetUserFields($langID = false)
	{
		global $USER_FIELD_MANAGER;
		$result = $USER_FIELD_MANAGER->GetUserFields(self::$sUFEntityID, 0, $langID);

		// remove invoice reserved fields
		foreach (self::GetUserFieldsReserved() as $ufId)
			if (isset($result[$ufId]))
				unset($result[$ufId]);

		return $result;
	}
	public static function GetUserFieldsReserved()
	{
		return array(
			'UF_DEAL_ID',
			'UF_CONTACT_ID',
			'UF_COMPANY_ID',
			'UF_QUOTE_ID',
			'UF_MYCOMPANY_ID'
		);
	}
	public static function GetFieldCaption($fieldName)
	{
		if($fieldName === 'CURRENCY_ID')
		{
			$fieldName = 'CURRENCY';
		}
		elseif($fieldName === 'LOCATION_ID')
		{
			$fieldName = 'PR_LOCATION';
		}

		$result = GetMessage("CRM_INVOICE_FIELD_{$fieldName}");
		return is_string($result) ? $result : '';
	}
	public static function GetList($arOrder = Array("ID"=>"DESC"), $arFilter = Array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array(), $arOptions = array())
	{
		global $USER;
		if(!CModule::IncludeModule('sale'))
		{
			return false;
		}

		//Reset callback params
		self::$LIST_CALLBACK_PARAMS = null;

		if (isset($arFilter['STATUS_ID']) && is_array($arFilter['STATUS_ID']))
			$arFilter['STATUS_ID'] = array_values($arFilter['STATUS_ID']);

		if (!is_array($arGroupBy))
		{
			if (is_array($arSelectFields) && (count($arSelectFields) === 0 || in_array('*', $arSelectFields)))
			{
				if (count($arSelectFields) === 0)
					$arSelectFields[] = '*';
				if (!in_array('UF_QUOTE_ID', $arSelectFields))
					$arSelectFields[] = 'UF_QUOTE_ID';
				if (!in_array('UF_DEAL_ID', $arSelectFields))
					$arSelectFields[] = 'UF_DEAL_ID';
				if (!in_array('UF_COMPANY_ID', $arSelectFields))
					$arSelectFields[] = 'UF_COMPANY_ID';
				if (!in_array('UF_CONTACT_ID', $arSelectFields))
					$arSelectFields[] = 'UF_CONTACT_ID';
				if (!in_array('UF_MYCOMPANY_ID', $arSelectFields))
					$arSelectFields[] = 'UF_MYCOMPANY_ID';
				$arSelectedUserFields = array_keys(self::GetUserFields());
				if (is_array($arSelectedUserFields) && !empty($arSelectedUserFields))
					$arSelectFields = array_merge($arSelectFields, $arSelectedUserFields);
				unset($arSelectedUserFields);
			}
		}

		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		if (isset($arFilter['CUSTOM_SUBQUERY']))
		{
			unset($arFilter['CUSTOM_SUBQUERY']);
		}

		if(isset($arFilter['__CONDITIONS']))
		{
			if(is_array($arFilter['__CONDITIONS']) && !empty($arFilter['__CONDITIONS']))
			{
				self::$LIST_CALLBACK_PARAMS = array('SQL' => $arFilter['__CONDITIONS'][0]['SQL']);
				$arFilter['CUSTOM_SUBQUERY'] = array('CCrmInvoice', '__callbackWhereCondition');
			}
			unset($arFilter['__CONDITIONS']);
		}

		if (!(is_object($USER) && $USER->IsAdmin())
			&& (!array_key_exists('CHECK_PERMISSIONS', $arFilter) || $arFilter['CHECK_PERMISSIONS'] !== 'N')
		)
		{
			$arFilter['CUSTOM_SUBQUERY'] = array('CCrmInvoice', '__callbackPermissionsWhereCondition');
			self::$arCurrentPermType = isset($arFilter['PERMISSION'])
				? (is_array($arFilter['PERMISSION']) ? $arFilter['PERMISSION'] : array($arFilter['PERMISSION']))
				: array('READ');
		}

		$userType = new CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], self::$sUFEntityID);
		$userType->ListPrepareFilter($arFilter);

		if (
			$arNavStartParams === false
			&& isset($arOptions['QUERY_OPTIONS'])
			&& is_array($arOptions['QUERY_OPTIONS'])
		)
		{
			$queryOptions = $arOptions['QUERY_OPTIONS'];
			$limit = isset($queryOptions['LIMIT']) ? (int)$queryOptions['LIMIT'] : 0;
			$offset = isset($queryOptions['OFFSET']) ? (int)$queryOptions['OFFSET'] : 0;
			if ($limit > 0)
			{
				$arNavStartParams = [
					'nPageSize' => $limit,
					'iNumPage' => (int)(floor($offset / $limit) + 1),
				];
			}
		}

		$result = Compatible\Helper::getList(
			$arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields, $arOptions
		);
		self::$arCurrentPermType = null;

		return $result;
	}
	static public function BuildEntityAttr($userID, $arAttr = array())
	{
		$userID = (int)$userID;
		$arResult = array("U{$userID}");
		if(isset($arAttr['OPENED']) && $arAttr['OPENED'] == 'Y')
		{
			$arResult[] = 'O';
		}

		$arUserAttr = Bitrix\Crm\Service\Container::getInstance()
			->getUserPermissions($userID)
			->getAttributesProvider()
			->getEntityAttributes()
		;

		return array_merge($arResult, $arUserAttr['INTRANET']);
	}
	static public function RebuildEntityAccessAttrs($IDs)
	{
		if(!is_array($IDs))
		{
			$IDs = array($IDs);
		}

		$dbResult = self::GetList(
			array(),
			array('@ID' => $IDs, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'RESPONSIBLE_ID')
		);

		if(!is_object($dbResult))
		{
			return;
		}

		while($fields = $dbResult->Fetch())
		{
			$ID = intval($fields['ID']);
			$assignedByID = isset($fields['RESPONSIBLE_ID']) ? intval($fields['RESPONSIBLE_ID']) : 0;
			if($assignedByID <= 0)
			{
				continue;
			}

			$entityAttrs = self::BuildEntityAttr($assignedByID);
			$securityRegisterOptions = (new \Bitrix\Crm\Security\Controller\RegisterOptions())
				->setEntityAttributes($entityAttrs)
				->setEntityFields($fields)
			;
			\Bitrix\Crm\Security\Manager::getEntityController(CCrmOwnerType::Invoice)
				->register(
					self::$TYPE_NAME,
					$ID,
					$securityRegisterOptions
				)
			;
		}
	}
	private function PrepareEntityAttrs(&$arEntityAttr, $entityPermType)
	{
		// Ensure that entity accessable for user restricted by BX_CRM_PERM_OPEN
		if($entityPermType === BX_CRM_PERM_OPEN && !in_array('O', $arEntityAttr, true))
		{
			$arEntityAttr[] = 'O';
		}
	}

	public static function BuildPermSql($sAliasPrefix = 'O', $mPermType = 'READ', $arOptions = [])
	{
		$userId = null;
		if (isset($arOptions['PERMS']) && is_object($arOptions['PERMS']))
		{
			/** @var \CCrmPerms $arOptions['PERMS'] */
			$userId = $arOptions['PERMS']->GetUserID();
		}
		$builderOptions =
			\Bitrix\Crm\Security\QueryBuilder\Options::createFromArray((array)$arOptions)
				->setOperations((array)$mPermType)
				->setAliasPrefix((string)$sAliasPrefix)
		;

		$queryBuilder = \Bitrix\Crm\Service\Container::getInstance()
			->getUserPermissions($userId)
			->createListQueryBuilder(self::$TYPE_NAME, $builderOptions)
		;

		$result = $queryBuilder->build();

		if (!$result->hasAccess())
		{
			return '(1=0)';
		}

		if (!$result->hasRestrictions())
		{
			return '(1=1)';
		}

		return '(' . $result->getSql() . ')';
	}

	public static function __callbackWhereCondition($arFields = array())
	{
		return is_array(self::$LIST_CALLBACK_PARAMS) && isset(self::$LIST_CALLBACK_PARAMS['SQL'])
			? self::$LIST_CALLBACK_PARAMS['SQL']
			: '(1=1)';
	}

	public static function __callbackPermissionsWhereCondition($arFields = array())
	{
		$sql = self::BuildPermSql('crm_invoice_internals_invoice', self::$arCurrentPermType);
		if(is_array(self::$LIST_CALLBACK_PARAMS) && isset(self::$LIST_CALLBACK_PARAMS['SQL']))
		{
			$sql .= 'AND ('.self::$LIST_CALLBACK_PARAMS['SQL'].')';
		}
		return $sql;
	}

	public static function GetStatusList()
	{
		$result = array();

		$dbRes = Bitrix\Crm\Invoice\InvoiceStatus::getList(['order' => ['SORT' => 'ASC']]);
		while ($status = $dbRes->fetch())
		{
			$result[$status['STATUS_ID']] = $status;
		}

		return $result;
	}

	public static function GetNeutralStatusIds()
	{
		$arResult = array();

		$arStatus = self::GetStatusList();
		$successSort = $arStatus['P']['SORT'];
		foreach ($arStatus as $fields)
		{
			if ($fields['STATUS_ID'] !== 'P' && $fields['SORT'] <= $successSort)
				$arResult[] = $fields['STATUS_ID'];
		}

		return $arResult;
	}

	public static function GetDefaultStatusId()
	{
		$list = self::GetStatusList();
		if(is_array($list) && !empty($list))
		{
			reset($list);
			return key($list);
		}

		return 'N';
	}

	public static function GetByID($ID, $bCheckPerms = true)
	{
		$arFilter = array('ID' => intval($ID));
		if (!$bCheckPerms)
		{
			$arFilter['CHECK_PERMISSIONS'] = 'N';
		}

		$dbRes = self::GetList(array(/*'ID' => 'ASC'*/), $arFilter);
		if(!is_object($dbRes))
		{
			return false;
		}
		return $dbRes->Fetch();
	}

	public static function Exists($ID)
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			return false;
		}

		$dbRes = self::GetList(
			array(),
			array('ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID')
		);

		return is_array($dbRes->Fetch());
	}

	private static function __fGetUserShoppingCart($arProduct, $LID, $recalcOrder)
	{
		if(!CModule::IncludeModule('sale'))
		{
			return false;
		}

		$arOrderProductPrice = array();

		foreach($arProduct as $key => $val)
		{
			//$arSortNum[] = $val['PRICE_DEFAULT'];
			$arProduct[$key]["PRODUCT_ID"] = intval($val["PRODUCT_ID"]);
			$arProduct[$key]["TABLE_ROW_ID"] = $key;
		}
		//if (count($arProduct) > 0 && count($arSortNum) > 0)
		//	array_multisort($arSortNum, SORT_DESC, $arProduct);

		$i = 0;
		foreach($arProduct as $key => $val)
		{
			$val["QUANTITY"] = abs(str_replace(",", ".", $val["QUANTITY"]));
			$val["QUANTITY_DEFAULT"] = $val["QUANTITY"];
			$val["PRICE"] = str_replace(",", ".", $val["PRICE"]);

			// Y is used when custom price was set in the admin form
			if ($val["CALLBACK_FUNC"] == "Y")
			{
				$val["CALLBACK_FUNC"] = false;
				$val["CUSTOM_PRICE"] = "Y";

				if (isset($val["BASKET_ID"]) || intval($val["BASKET_ID"]) > 0)
				{
					Compatible\BasketHelper::update($val["BASKET_ID"], array("CUSTOM_PRICE" => "Y"));
				}

				//$val["DISCOUNT_PRICE"] = $val["PRICE_DEFAULT"] - $val["PRICE"];
			}

			$arOrderProductPrice[$i] = $val;
			$arOrderProductPrice[$i]["TABLE_ROW_ID"] = $val["TABLE_ROW_ID"];
			$arOrderProductPrice[$i]["PRODUCT_ID"] = intval($val["PRODUCT_ID"]);
			$arOrderProductPrice[$i]["NAME"] = htmlspecialcharsback($val["NAME"]);
			$arOrderProductPrice[$i]["LID"] = $LID;
			$arOrderProductPrice[$i]["CAN_BUY"] = "Y";
			$arOrderProductPrice[$i]["DUPLICATE"] = "Y";

			if (!isset($val["BASKET_ID"]) || $val["BASKET_ID"] == "")
			{
				/*if ($val["CALLBACK_FUNC"] == "Y")
				{
					$arOrderProductPrice[$i]["CALLBACK_FUNC"] = '';
					$arOrderProductPrice[$i]["DISCOUNT_PRICE"] = 0;
				}*/
			}
			else
			{
				$arOrderProductPrice[$i]["ID"] = intval($val["BASKET_ID"]);

				if ($recalcOrder != "Y" && $arOrderProductPrice[$i]["CALLBACK_FUNC"] != false)
				{
					unset($arOrderProductPrice[$i]["CALLBACK_FUNC"]);
				}

				$arNewProps = array();
				if (is_array($val["PROPS"]))
				{
					foreach($val["PROPS"] as $k => $v)
					{
						if ($v["NAME"] != "" AND $v["VALUE"] != "")
							$arNewProps[$k] = $v;
					}
				}
				else
				{
					$arNewProps = array("NAME" => "", "VALUE" => "", "CODE" => "", "SORT" => "");
				}

				$arOrderProductPrice[$i]["PROPS"] = $arNewProps;
			}
			$i++;
		}//endforeach $arProduct

		return $arOrderProductPrice;
	}

	private static function __fGetLocationPropertyId($personTypeId)
	{
		if(!CModule::IncludeModule('sale'))
		{
			return false;
		}

		$locationPropertyId = null;
		$dbRes = \Bitrix\Crm\Invoice\Property::getList([
			'select' => ["ID"],
			'filter' => [
				"=PERSON_TYPE_ID" => $personTypeId,
				"=ACTIVE" => "Y",
				"=TYPE" => "LOCATION",
				"=IS_LOCATION" => "Y",
				"=IS_LOCATION4TAX" => "Y"
			],
			'order' => ["SORT" => "ASC"]
		]);
		if ($arOrderProp = $dbRes->fetch())
		{
			$locationPropertyId = $arOrderProp['ID'];
		}
		else
		{
			return false;
		}
		$locationPropertyId = intval($locationPropertyId);
		if ($locationPropertyId <= 0)
			return false;
		return $locationPropertyId;
	}

	public static function QuickRecalculate($arFields, $siteId = SITE_ID)
	{
		if(!CModule::IncludeModule('sale'))
		{
			return array('err'=> '1');
		}

		$tmpOrderId = isset($arFields['ID']) ? intval($arFields['ID']) : 0;
		if($tmpOrderId < 0)
		{
			$tmpOrderId = 0;
		}

		$saleUserId = intval(CSaleUser::GetAnonymousUserID());
		if ($saleUserId <= 0)
		{
			return array('err'=> '2');
		}

		$arProduct = isset($arFields['PRODUCT_ROWS']) && is_array($arFields['PRODUCT_ROWS'])
			? $arFields['PRODUCT_ROWS'] : array();
		if(empty($arProduct))
		{
			return array('err'=> '3');
		}

		$currencyId = CCrmInvoice::GetCurrencyID($siteId);
		foreach ($arProduct as &$productRow)
		{
			if (isset($productRow['PRODUCT_NAME']))
			{
				$productRow['NAME'] = $productRow['PRODUCT_NAME'];
				unset($productRow['PRODUCT_NAME']);
			}
			if (isset($productRow['PRICE']))
			{
				$productRow['PRICE_DEFAULT'] = $productRow['PRICE'];
			}
			if (!isset($productRow['CURRENCY']))
			{
				$productRow['CURRENCY'] = $currencyId;
			}
			$productRow['MODULE'] = 'catalog';
			$productRow['PRODUCT_PROVIDER_CLASS'] = 'CCatalogProductProvider';
			$productRow['CALLBACK_FUNC'] = 'Y';
		}
		unset($productRow);

		$arOrderProductPrice = self::__fGetUserShoppingCart($arProduct, $siteId, 'N');

		foreach ($arOrderProductPrice as &$arItem) // tmp hack not to update basket quantity data from catalog
		{
			$arItem['ID_TMP'] = $arItem['ID'];
			unset($arItem['ID']);
		}
		unset($arItem);

		$arErrors = array();
		$arShoppingCart = Compatible\BasketHelper::doGetUserShoppingCart(
			$siteId, $saleUserId, $arOrderProductPrice, $arErrors, $tmpOrderId
		);

		foreach ($arShoppingCart as $key => &$arItem)
		{
			$arItem['ID'] = $arItem['ID_TMP'];
			unset($arItem['ID_TMP']);
		}
		unset($arItem);

		$personTypeId = isset($arFields['PERSON_TYPE_ID']) ? intval($arFields['PERSON_TYPE_ID']) : 0;
		if($personTypeId <= 0)
		{
			$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
			if (isset($arPersonTypes['CONTACT']))
				$personTypeId = intval($arPersonTypes['CONTACT']);
		}
		if ($personTypeId <= 0)
		{
			return array('err'=> '4');
		}

		$arOrderPropsValues = array();
		if (isset($arFields['INVOICE_PROPERTIES']) && is_array($arFields['INVOICE_PROPERTIES']) && count($arFields['INVOICE_PROPERTIES']) > 0)
		{
			$arOrderPropsValues = $arFields['INVOICE_PROPERTIES'];
		}
		if (isset($arFields['INVOICE_PROPERTIES']))
		{
			unset($arFields['INVOICE_PROPERTIES']);
		}
		if (count($arOrderPropsValues) <= 0)
		{
			return array('err'=> '5');
		}

		$deliveryId = null;
		$paySystemId = isset($arFields['PAY_SYSTEM_ID']) ? intval($arFields['PAY_SYSTEM_ID']) : 0;
		$arOptions = array(
			'LOCATION_IN_CODES' => true,    // let DoCalculateOrder know we send location in CODEs
			'CART_FIX' => 'Y',
			'CURRENCY' => $currencyId
		);
		$arErrors = array();
		$arWarnings = array();

		$invoiceCompatible = Compatible\Invoice::create($arFields);
		$arOptions['ORDER'] = $invoiceCompatible->getOrder();

		return CSaleOrder::DoCalculateOrder(
			$siteId,
			$saleUserId,
			$arShoppingCart,
			$personTypeId,
			$arOrderPropsValues,
			$deliveryId,
			$paySystemId,
			$arOptions,
			$arErrors,
			$arWarnings
		);
	}

	public function Add($arFields, &$arRecalculated = false, $siteId = SITE_ID, $options = array())
	{
		/** @global \CDatabase $DB */
		/** @global \CMain $APPLICATION */
		/** @var CApplicationException $ex */
		global $DB, $APPLICATION;

		if(!CModule::IncludeModule('sale'))
		{
			$this->LAST_ERROR = GetMessage('CRM_MODULE_SALE_NOT_INSTALLED');
			$GLOBALS['APPLICATION']->ThrowException($this->LAST_ERROR);
			return false;
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$bRecalculate = is_array($arRecalculated);
		$orderID = false;
		$tmpOrderId = (intval($arFields['ID']) <= 0) ? 0 : $arFields['ID'];
		if (isset($arFields['ID']))
		{
			unset($arFields['ID']);
		}

		$arPrevOrder = ($tmpOrderId !== 0) ? CCrmInvoice::GetByID($tmpOrderId, $this->bCheckPermission) : null;

		$userId = isset($options['CURRENT_USER'])
			? (int)$options['CURRENT_USER'] : CCrmSecurityHelper::GetCurrentUserID();

		if (!isset($arFields['RESPONSIBLE_ID']) || (int)$arFields['RESPONSIBLE_ID'] <= 0)
		{
			if (is_array($arPrevOrder) && isset($arPrevOrder['RESPONSIBLE_ID']) && intval($arPrevOrder['RESPONSIBLE_ID']) > 0)
				$arFields['RESPONSIBLE_ID'] = $arPrevOrder['RESPONSIBLE_ID'];
			else
				$arFields['RESPONSIBLE_ID'] = $userId;
		}

		$orderStatus = '';
		if (isset($arFields['STATUS_ID']))
		{
			$orderStatus = $arFields['STATUS_ID'];
			unset($arFields['STATUS_ID']);
		}
		else
		{
			$arFields['STATUS_ID'] = self::GetDefaultStatusId();
		}

		// prepare entity permissions
		$arAttr = array();
		if (!empty($arFields['OPENED']))
			$arAttr['OPENED'] = $arFields['OPENED'];
			$arAttr['OPENED'] = $arFields['OPENED'];
		$sPermission = ($tmpOrderId > 0) ? 'WRITE' : 'ADD';
		if($this->bCheckPermission)
		{
			$arEntityAttr = self::BuildEntityAttr($userId, $arAttr);
			$userPerms = ($userId == CCrmPerms::GetCurrentUserID()) ? $this->cPerms : CCrmPerms::GetUserPermissions($userId);
			$sEntityPerm = $userPerms->GetPermType(self::$TYPE_NAME, $sPermission, $arEntityAttr);
			if ($sEntityPerm == BX_CRM_PERM_NONE)
			{
				$this->LAST_ERROR = GetMessage('CRM_PERMISSION_DENIED');
				$GLOBALS['APPLICATION']->ThrowException($this->LAST_ERROR);
				return false;
			}

			$responsibleID = intval($arFields['RESPONSIBLE_ID']);
			if ($tmpOrderId === 0 && $sEntityPerm == BX_CRM_PERM_SELF && $responsibleID != $userId)
			{
				$arFields['RESPONSIBLE_ID'] = $userId;
			}
			if ($sEntityPerm == BX_CRM_PERM_OPEN && $userId == $responsibleID)
			{
				$arFields['OPENED'] = 'Y';
			}
		}
		$responsibleID = intval($arFields['RESPONSIBLE_ID']);
		$arEntityAttr = self::BuildEntityAttr($responsibleID, $arAttr);
		$userPerms = ($responsibleID == CCrmPerms::GetCurrentUserID()) ? $this->cPerms : CCrmPerms::GetUserPermissions($responsibleID);
		$sEntityPerm = $userPerms->GetPermType(self::$TYPE_NAME, $sPermission, $arEntityAttr);
		$this->PrepareEntityAttrs($arEntityAttr, $sEntityPerm);

		// date fields
		if ($tmpOrderId === 0)
		{
			$arFields['~DATE_BILL'] = $DB->CharToDateFunction(
				isset($arFields['DATE_BILL']) && $arFields['DATE_BILL'] !== '' ?
					$arFields['DATE_BILL'] : ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'SHORT', SITE_ID),
				'SHORT',
				false
			);
		}
		else if(isset($arFields['DATE_BILL']) && $arFields['DATE_BILL'] !== '')
		{
			$arFields['~DATE_BILL'] = $DB->CharToDateFunction($arFields['DATE_BILL'], 'SHORT', false);
		}
		unset($arFields['DATE_BILL']);
		if(array_key_exists('DATE_PAY_BEFORE', $arFields))
		{
			$dateValue = $arFields['DATE_PAY_BEFORE'];
			if ($dateValue instanceof Main\Type\DateTime || $dateValue instanceof Main\Type\Date)
			{
				$dateValue = (string)$dateValue;
			}
			if (is_string($dateValue) && $dateValue !== '')
			{
				$arFields['~DATE_PAY_BEFORE'] = $DB->CharToDateFunction($dateValue, 'SHORT', false);
				unset($arFields['DATE_PAY_BEFORE']);
			}
			else
			{
				$arFields['DATE_PAY_BEFORE'] = '';
			}
			unset($dateValue);
		}

		$paidStateCanceled = false;
		if ($tmpOrderId > 0 && is_array($arPrevOrder) && !$bRecalculate
			&& isset($arPrevOrder['STATUS_ID']) && $arPrevOrder['STATUS_ID'] === 'P'
			&& isset($arPrevOrder['PAYED']) && $arPrevOrder['PAYED'] === 'Y')
		{
			if (!Compatible\Helper::payOrder($tmpOrderId, false))
			{
				$this->LAST_ERROR = GetMessage('CRM_INVOICE_ERR_CANCEL_PAID_STATE');
				$GLOBALS['APPLICATION']->ThrowException($this->LAST_ERROR);
				return false;
			}
			$paidStateCanceled = true;
		}

		if ($tmpOrderId !== 0 && !isset($arFields['PRODUCT_ROWS']) && !isset($arFields['INVOICE_PROPERTIES']))
		{
			if (!isset($arFields['ID']))
				$arFields['ID'] = $tmpOrderId;
			if (!empty($orderStatus))
				$arFields['STATUS_ID'] = $orderStatus;
			foreach (GetModuleEvents('crm', 'OnBeforeCrmInvoiceUpdate', true) as $arEvent)
			{
				if(ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
				{
					$errMsg = '';
					if(isset($arFields['RESULT_MESSAGE']))
					{
						$errMsg = trim(strval($arFields['RESULT_MESSAGE']));
					}
					else if ($ex = $APPLICATION->GetException())
					{
						$errMsg = trim(strval($ex->GetString()));
					}

					if ($errMsg === '')
					{
						$this->LAST_ERROR = GetMessage('CRM_INVOICE_UPDATE_CANCELED', array('#NAME#' => $arEvent['TO_NAME']));
					}
					else
					{
						$this->LAST_ERROR = $errMsg;
					}
					unset($errMsg);

					$GLOBALS['APPLICATION']->ThrowException($this->LAST_ERROR);
					if ($paidStateCanceled)
					{
						Compatible\Helper::payOrder($tmpOrderId, true);
					}
					return false;
				}
			}
			unset($arFields['ID'], $arFields['STATUS_ID']);

			if(!is_array($arPrevOrder))
			{
				$this->LAST_ERROR = GetMessage('CRM_INVOICE_ERR_NOT_FOUND', array('#INVOICE_ID#' => $tmpOrderId));
				$GLOBALS['APPLICATION']->ThrowException($this->LAST_ERROR);
				if ($paidStateCanceled)
				{
					Compatible\Helper::payOrder($tmpOrderId, true);
				}
				return false;
			}

			$prevResponsibleID = isset($arPrevOrder['RESPONSIBLE_ID']) ? intval($arPrevOrder['RESPONSIBLE_ID']) : 0;
			$responsibleID = isset($arFields['RESPONSIBLE_ID']) ? intval($arFields['RESPONSIBLE_ID']) : 0;

			// simple update order fields
			$simpleFields = $arFields;
			$userFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(self::$sUFEntityID);
			if (is_array($userFields))
			{
				foreach(array_keys($userFields) as $fieldName)
				{
					if (array_key_exists($fieldName, $simpleFields))
						unset($simpleFields[$fieldName]);
				}
			}
			if (is_array($simpleFields) && !empty($simpleFields))
				$orderID = Compatible\Helper::Update($tmpOrderId, $simpleFields);
			unset($simpleFields, $userFields);

			// update user fields
			CCrmEntityHelper::NormalizeUserFields($arFields, self::$sUFEntityID, $GLOBALS['USER_FIELD_MANAGER'], array('IS_NEW' => false));
			$GLOBALS['USER_FIELD_MANAGER']->Update(self::$sUFEntityID, $tmpOrderId, $arFields);

			$registerSonetEvent = isset($options['REGISTER_SONET_EVENT']) && $options['REGISTER_SONET_EVENT'] === true;

			if(is_int($orderID) && $orderID > 0)
			{
				if($registerSonetEvent)
				{
					$newDealID = isset($arFields['UF_DEAL_ID']) ? intval($arFields['UF_DEAL_ID']) : 0;
					$oldDealID = isset($arPrevOrder['UF_DEAL_ID']) ? intval($arPrevOrder['UF_DEAL_ID']) : 0;

					$newCompanyID = isset($arFields['UF_COMPANY_ID']) ? intval($arFields['UF_COMPANY_ID']) : 0;
					$oldCompanyID = isset($arPrevOrder['UF_COMPANY_ID']) ? intval($arPrevOrder['UF_COMPANY_ID']) : 0;

					$newContactID = isset($arFields['UF_CONTACT_ID']) ? intval($arFields['UF_CONTACT_ID']) : 0;
					$oldContactID = isset($arPrevOrder['UF_CONTACT_ID']) ? intval($arPrevOrder['UF_CONTACT_ID']) : 0;

					$parents = array();
					$parentsChanged = $newDealID !== $oldDealID || $newCompanyID !== $oldCompanyID || $newContactID !== $oldContactID;
					if($parentsChanged)
					{
						if($newDealID > 0)
						{
							$parents[] = array(
								'ENTITY_TYPE_ID' => CCrmOwnerType::Deal,
								'ENTITY_ID' => $newDealID
							);
						}

						if($newCompanyID > 0)
						{
							$parents[] = array(
								'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
								'ENTITY_ID' => $newCompanyID
							);
						}

						if($newContactID > 0)
						{
							$parents[] = array(
								'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
								'ENTITY_ID' => $newContactID
							);
						}
					}

					$oldOrderStatus = isset($arPrevOrder['STATUS_ID']) ? $arPrevOrder['STATUS_ID'] : '';
					self::SynchronizeLiveFeedEvent(
						$orderID,
						array(
							'PROCESS_PARENTS' => $parentsChanged,
							'PARENTS' => $parents,
							'REFRESH_DATE' => $orderStatus !== $oldOrderStatus,
							'START_RESPONSIBLE_ID' => $prevResponsibleID,
							'FINAL_RESPONSIBLE_ID' => $responsibleID,
							'TOPIC' => isset($arPrevOrder['ORDER_TOPIC']) ? $arPrevOrder['ORDER_TOPIC'] : $orderID
						)
					);
				}

				if (
					$responsibleID !== $prevResponsibleID
					&& Settings\Crm::isLiveFeedRecordsGenerationEnabled()
				)
				{
					CCrmSonetSubscription::ReplaceSubscriptionByEntity(
						CCrmOwnerType::Invoice,
						$orderID,
						CCrmSonetSubscriptionType::Responsibility,
						$responsibleID,
						$prevResponsibleID,
						$registerSonetEvent
					);
				}
			}
		}
		else
		{
			if ($tmpOrderId === 0 && !$bRecalculate)
			{
				if (!empty($orderStatus))
					$arFields['STATUS_ID'] = $orderStatus;
				foreach (GetModuleEvents('crm', 'OnBeforeCrmInvoiceAdd', true) as $arEvent)
				{
					if(ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
					{
						$errMsg = '';
						if(isset($arFields['RESULT_MESSAGE']))
						{
							$errMsg = trim(strval($arFields['RESULT_MESSAGE']));
						}
						else if ($ex = $APPLICATION->GetException())
						{
							$errMsg = trim(strval($ex->GetString()));
						}

						if ($errMsg === '')
						{
							$this->LAST_ERROR = GetMessage('CRM_INVOICE_CREATION_CANCELED', array('#NAME#' => $arEvent['TO_NAME']));
						}
						else
						{
							$this->LAST_ERROR = $errMsg;
						}
						unset($errMsg);

						$GLOBALS['APPLICATION']->ThrowException($this->LAST_ERROR);
						if ($paidStateCanceled)
						{
							Compatible\Helper::payOrder($tmpOrderId, true);
						}
						return false;
					}
				}
				unset($arFields['STATUS_ID']);
			}
			else if ($tmpOrderId !== 0 && !$bRecalculate)
			{
				if (!isset($arFields['ID']))
					$arFields['ID'] = $tmpOrderId;
				if (!empty($orderStatus))
					$arFields['STATUS_ID'] = $orderStatus;
				foreach (GetModuleEvents('crm', 'OnBeforeCrmInvoiceUpdate', true) as $arEvent)
				{
					if(ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
					{
						$errMsg = '';
						if(isset($arFields['RESULT_MESSAGE']))
						{
							$errMsg = trim(strval($arFields['RESULT_MESSAGE']));
						}
						else if ($ex = $APPLICATION->GetException())
						{
							$errMsg = trim(strval($ex->GetString()));
						}

						if ($errMsg === '')
						{
							$this->LAST_ERROR = GetMessage('CRM_INVOICE_UPDATE_CANCELED', array('#NAME#' => $arEvent['TO_NAME']));
						}
						else
						{
							$this->LAST_ERROR = $errMsg;
						}
						unset($errMsg);

						$GLOBALS['APPLICATION']->ThrowException($this->LAST_ERROR);
						if ($paidStateCanceled)
						{
							Compatible\Helper::payOrder($tmpOrderId, true);
						}
						return false;
					}
				}
				unset($arFields['ID'], $arFields['STATUS_ID']);
			}

			// check product rows
			if (!isset($arFields['PRODUCT_ROWS']) ||
				!is_array($arFields['PRODUCT_ROWS']) ||
				count($arFields['PRODUCT_ROWS']) <= 0)
			{
				$this->LAST_ERROR = GetMessage('CRM_ERROR_EMPTY_INVOICE_SPEC');
				$GLOBALS['APPLICATION']->ThrowException($this->LAST_ERROR);
				if ($paidStateCanceled)
				{
					Compatible\Helper::payOrder($tmpOrderId, true);
				}
				return false;
			}
			$arProduct = $arFields['PRODUCT_ROWS'];

			// prepare shopping cart data
			// <editor-fold defaultstate="collapsed" desc="prepare shopping cart data ...">

			// get xml_id fields
			$catalogXmlId = CCrmCatalog::GetDefaultCatalogXmlId();
			$arNewProducts = array();
			$bGetBasketXmlIds = false;
			foreach ($arProduct as &$productRow)
			{
				if (!isset($productRow['ID']))
				{
					$productRow['ID'] = 0;
				}
				if (intval($productRow['ID']) === 0 && isset($productRow['PRODUCT_ID']))
				{
					$arNewProducts[] = $productRow['PRODUCT_ID'];
				}
				else
				{
					$bGetBasketXmlIds = true;
				}
			}
			unset($productRow);
			$arXmlIds = array();
			$oldProductRows = null;
			if ($bGetBasketXmlIds && intval($tmpOrderId) > 0)
			{
				$oldProductRows = CCrmInvoice::GetProductRows($tmpOrderId);
				if (count($oldProductRows) > 0)
				{
					foreach ($oldProductRows as $row)
					{
						$arXmlIds[intval($row['ID'])][$row['PRODUCT_ID']] = array(
							'CATALOG_XML_ID' => $row['CATALOG_XML_ID'],
							'PRODUCT_XML_ID' => $row['PRODUCT_XML_ID']
						);
					}
					unset($row);
				}
			}
			unset($bGetBasketXmlIds);
			if (count($arNewProducts) > 0)
			{
				$dbRes = CCrmProduct::GetList(array(), array('ID' => $arNewProducts), array('ID', 'XML_ID'));
				while ($row = $dbRes->Fetch())
				{
					$arXmlIds[0][$row['ID']] = array(
						'CATALOG_XML_ID' => $catalogXmlId,
						'PRODUCT_XML_ID' => $row['XML_ID']
					);
				}
				unset($dbRes, $row);
			}
			unset($arNewProducts, $arOldProducts);

			// products without measures
			$productMeasures = array();
			$productId = 0;
			$productIds = array();
			foreach ($arProduct as $productRow)
			{
				$productId = intval($productRow['PRODUCT_ID']);
				if ($productId > 0
					&& (!array_key_exists('MEASURE_CODE', $productRow) || intval($productRow['MEASURE_CODE']) <= 0))
				{
					$productIds[] = $productId;
				}
			}
			unset($productId, $productRow);
			if (count($productIds) > 0)
				$productMeasures = \Bitrix\Crm\Measure::getProductMeasures($productIds);
			unset($productIds);

			$currencyId = CCrmInvoice::GetCurrencyID($siteId);
			$i = 0;
			$defaultMeasure = null;
			$oldProductRowsById = null;
			foreach ($arProduct as &$productRow)
			{
				$productXmlId = $catalogXmlId = null;
				$rowIndex = intval($productRow['ID']);
				$productId = $productRow['PRODUCT_ID'];
				$isCustomized = (isset($productRow['CUSTOMIZED']) && $productRow['CUSTOMIZED'] === 'Y');
				$productRow['MODULE'] = $productRow['PRODUCT_PROVIDER_CLASS'] = '';
				if($productId > 0)
				{
					if (!$isCustomized)
					{
						$productRow['MODULE'] = 'catalog';
						$productRow['PRODUCT_PROVIDER_CLASS'] = 'CCatalogProductProvider';
					}
					if (is_array($arXmlIds[$rowIndex])
						&& isset($arXmlIds[$rowIndex][$productId]))
					{
						$catalogXmlId = $arXmlIds[$rowIndex][$productId]['CATALOG_XML_ID'];
						$productXmlId = $arXmlIds[$rowIndex][$productId]['PRODUCT_XML_ID'];
					}
					$productRow['CATALOG_XML_ID'] = $catalogXmlId;
					$productRow['PRODUCT_XML_ID'] = $productXmlId;
				}
				else
				{
					$productRow["PRODUCT_XML_ID"] = "CRM-".\Bitrix\Main\Security\Random::getString(8, true);
					$ri = new \Bitrix\Main\Type\RandomSequence($productRow["PRODUCT_XML_ID"]);
					$productRow["PRODUCT_ID"] = $ri->rand(1000000, 9999999);
					$productRow['CATALOG_XML_ID'] = '';
				}
				if($isCustomized)
					$productRow['CUSTOM_PRICE'] = 'Y';
				if (isset($productRow['PRODUCT_NAME']))
				{
					$productRow['NAME'] = $productRow['PRODUCT_NAME'];
					unset($productRow['PRODUCT_NAME']);
				}
				if (isset($productRow['PRICE']))
					$productRow['PRICE_DEFAULT'] = $productRow['PRICE'];
				if (!isset($productRow['CURRENCY']))
					$productRow['CURRENCY'] = $currencyId;

				// measures
				$bRefreshMeasureName = false;
				if (!array_key_exists('MEASURE_CODE', $productRow) || intval($productRow['MEASURE_CODE'] <= 0))
				{
					if ($oldProductRows === null && $tmpOrderId > 0)
					{
						$oldProductRows = CCrmInvoice::GetProductRows($tmpOrderId);
					}
					if (is_array($oldProductRows) && count($oldProductRows) > 0 && $oldProductRowsById === null)
					{
						$oldProductRowsById = array();
						foreach ($oldProductRows as $row)
							$oldProductRowsById[intval($row['ID'])] = $row;
						unset($row);
					}
					if (is_array($oldProductRowsById) && isset($oldProductRowsById[$rowIndex]))
					{
						$row = $oldProductRowsById[$rowIndex];
						if (intval($productId) === intval($row['PRODUCT_ID']))
						{
							if (isset($row['MEASURE_CODE']))
								$productRow['MEASURE_CODE'] = $row['MEASURE_CODE'];
							if (isset($row['MEASURE_NAME']))
								$productRow['MEASURE_NAME'] = $row['MEASURE_NAME'];
							else
								$bRefreshMeasureName = true;
							unset($row);
						}
					}
				}
				if (!isset($productRow['MEASURE_CODE']) || intval($productRow['MEASURE_CODE']) <= 0)
				{
					if ($productId > 0 && isset($productMeasures[$productId]))
					{
						$measure = is_array($productMeasures[$productId][0]) ? $productMeasures[$productId][0] : null;
						if (is_array($measure))
						{
							if (isset($measure['CODE']))
								$productRow['MEASURE_CODE'] = $measure['CODE'];
							if (isset($measure['SYMBOL']))
								$productRow['MEASURE_NAME'] = $measure['SYMBOL'];
						}
						unset($measure);
					}
				}
				if (!isset($productRow['MEASURE_CODE']) || intval($productRow['MEASURE_CODE']) <= 0)
				{
					if ($defaultMeasure === null)
						$defaultMeasure = \Bitrix\Crm\Measure::getDefaultMeasure();

					if (is_array($defaultMeasure))
					{
						$productRow['MEASURE_CODE'] = $defaultMeasure['CODE'];
						$productRow['MEASURE_NAME'] = $defaultMeasure['SYMBOL'];
					}
				}
				if (isset($productRow['MEASURE_CODE'])
					&& intval($productRow['MEASURE_CODE']) > 0
					&& (
						$bRefreshMeasureName ||
						!array_key_exists('MEASURE_NAME', $productRow)
						|| empty($productRow['MEASURE_NAME'])
					)
				)
				{
					$measure = \Bitrix\Crm\Measure::getMeasureByCode($productRow['MEASURE_CODE']);
					if (is_array($measure) && isset($measure['SYMBOL']))
						$productRow['MEASURE_NAME'] = $measure['SYMBOL'];
					unset($measure);
				}

				$i++;
			}
			unset($productRow, $productMeasures, $catalogXmlId, $productXmlId);

			$arOrderProductPrice = self::__fGetUserShoppingCart($arProduct, $siteId, 'N');

			foreach ($arOrderProductPrice as &$arItem) // tmp hack not to update basket quantity data from catalog
			{
				$arItem["ID_TMP"] = $arItem["ID"];
				$arItem["NAME_TMP"] = $arItem["NAME"];
				unset($arItem["ID"]);
			}
			unset($arItem);

			// user id for order
			$saleUserId = intval(CSaleUser::GetAnonymousUserID());
			if ($saleUserId <= 0)
			{
				$this->LAST_ERROR = GetMessage('CRM_INVOICE_ERR_GET_ANONYMOUS_ID');
				$GLOBALS['APPLICATION']->ThrowException($this->LAST_ERROR);
				return false;
			}

			$arErrors = array();

			$arShoppingCart = Compatible\BasketHelper::doGetUserShoppingCart(
				$siteId, $saleUserId, $arOrderProductPrice, $arErrors, $tmpOrderId, true
			);
			if (!is_array($arShoppingCart) || count($arShoppingCart) === 0)
			{
				$this->LAST_ERROR = GetMessage('CRM_ERROR_EMPTY_INVOICE_SPEC');
				$GLOBALS['APPLICATION']->ThrowException($this->LAST_ERROR);
				return false;
			}

			foreach ($arShoppingCart as $key => &$arItem)
			{
				$arItem["ID"] = $arItem["ID_TMP"];
				$arItem["NAME"] = $arItem["NAME_TMP"];
				unset($arItem["NAME_TMP"], $arItem["ID_TMP"]);

				//$arShoppingCart[$key]["ID"] = $arItem["ID"];
			}
			unset($key, $arItem);
			// </editor-fold>

			// person type
			$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
			if (!isset($arPersonTypes['COMPANY']) || !isset($arPersonTypes['CONTACT']))
			{
				$this->LAST_ERROR = GetMessage('CRM_INVOICE_ERR_PERSON_TYPES_INCORRECT');
				$GLOBALS['APPLICATION']->ThrowException($this->LAST_ERROR);
				if ($paidStateCanceled)
				{
					Compatible\Helper::payOrder($tmpOrderId, true);
				}
				return false;
			}

			$personTypeId = isset($arFields['PERSON_TYPE_ID']) ? (int)$arFields['PERSON_TYPE_ID'] : 0;
			if (isset($arFields['UF_COMPANY_ID']) && intval($arFields['UF_COMPANY_ID']) > 0)
				$personTypeId = (int)$arPersonTypes['COMPANY'];
			else if (isset($arFields['UF_CONTACT_ID']) && intval($arFields['UF_CONTACT_ID']) > 0)
				$personTypeId = (int)$arPersonTypes['CONTACT'];
			if ($personTypeId !== intval($arPersonTypes['COMPANY']) && $personTypeId !== intval($arPersonTypes['CONTACT']))
			{
				$this->LAST_ERROR = GetMessage(
					'CRM_INVOICE_ERR_INVALID_PERSON_TYPE_ID',
					array(
						'#CONTACT#' => $arPersonTypes['CONTACT'],
						'#COMPANY#' => $arPersonTypes['COMPANY']
					)
				);
				$GLOBALS['APPLICATION']->ThrowException($this->LAST_ERROR);
				if ($paidStateCanceled)
				{
					Compatible\Helper::payOrder($tmpOrderId, true);
				}
				return false;
			}
			$arFields['PERSON_TYPE_ID'] = $personTypeId;

			// preparing order to save
			// <editor-fold defaultstate="collapsed" desc="preparing order to save ...">
			$arOrderPropsValues = array();
			if (isset($arFields['INVOICE_PROPERTIES']) && is_array($arFields['INVOICE_PROPERTIES']) && count($arFields['INVOICE_PROPERTIES']) > 0)
				$arOrderPropsValues = $arFields['INVOICE_PROPERTIES'];
			if (isset($arFields['INVOICE_PROPERTIES']))
				unset($arFields['INVOICE_PROPERTIES']);
			if (count($arOrderPropsValues) <= 0)
			{
				$this->LAST_ERROR = GetMessage('CRM_INVOICE_ERR_EMPTY_INVOICE_PROPS');
				$GLOBALS['APPLICATION']->ThrowException($this->LAST_ERROR);
				return false;
			}

			$deliveryId = null;
			$paySystemId = $arFields['PAY_SYSTEM_ID'];

			// let DoCalculateOrder know we send location in CODEs
			$arOptions = array('LOCATION_IN_CODES' => true, 'CART_FIX' => 'Y', 'CURRENCY' => $currencyId);

			$arErrors = $arWarnings = array();

			$invoiceCompatible = Compatible\Invoice::create($arFields);
			$arOptions['ORDER'] = $invoiceCompatible->getOrder();

			$arOrder = CSaleOrder::DoCalculateOrder(
				$siteId, $saleUserId, $arShoppingCart, $personTypeId, $arOrderPropsValues,
				$deliveryId, $paySystemId, $arOptions, $arErrors, $arWarnings
			);

			if (!is_array($arOrder) || empty($arOrder))
			{
				if (is_array($arErrors) && isset($arErrors[0]['TEXT']))
				{
					$this->LAST_ERROR = $arErrors[0]['TEXT'];
					$GLOBALS['APPLICATION']->ThrowException($this->LAST_ERROR);
				}
				return false;
			}
			// </editor-fold>

			if ($bRecalculate)
			{
				foreach ($arOrder as $k => $v)
					$arRecalculated[$k] = $v;
				return true;
			}

			// merge order fields
			$arAdditionalFields = array();
			foreach ($arFields as $k => $v)
			{
				if ($k === 'PRODUCT_ROWS') continue;
				$arAdditionalFields[$k] = $v;
			}

			// skip user fields
			$userFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(self::$sUFEntityID);
			if (is_array($userFields))
			{
				foreach(array_keys($userFields) as $fieldName)
				{
					if (array_key_exists($fieldName, $arAdditionalFields))
						unset($arAdditionalFields[$fieldName]);
				}
			}
			unset($userFields);

			$arOrder['LOCATION_IN_CODES'] = true; // let DoSaveOrder know we send location in IDs

			// saving order
			$arErrors = array();
			$arAdditionalFields['CUSTOM_DISCOUNT_PRICE'] = true;
			$orderID = Compatible\Helper::doSaveOrder($arOrder, $arAdditionalFields, $tmpOrderId, $arErrors);

			if(!(is_int($orderID) && $orderID > 0))
			{
				if (is_array($arErrors) && isset($arErrors[0]))
					$this->LAST_ERROR = $arErrors[0];
				else
					$this->LAST_ERROR = GetMessage('CRM_INVOICE_ERR_UNK_ON_SAVE_ORDER');
				$GLOBALS['APPLICATION']->ThrowException($this->LAST_ERROR);
			}

			// update user fields
			if(is_int($orderID) && $orderID > 0)
			{
				CCrmEntityHelper::NormalizeUserFields($arFields, self::$sUFEntityID, $GLOBALS['USER_FIELD_MANAGER'], array('IS_NEW' => ($tmpOrderId === 0)));
				$GLOBALS['USER_FIELD_MANAGER']->Update(self::$sUFEntityID, $orderID, $arFields);
			}

			if(is_int($orderID) && $orderID > 0 && isset($options['REGISTER_SONET_EVENT']) && $options['REGISTER_SONET_EVENT'] === true)
			{
				$prevResponsibleID = is_array($arPrevOrder) && isset($arPrevOrder['RESPONSIBLE_ID'])
						? intval($arPrevOrder['RESPONSIBLE_ID']) : 0;
				$responsibleID = isset($arFields['RESPONSIBLE_ID']) ? intval($arFields['RESPONSIBLE_ID']) : 0;

				if($tmpOrderId <= 0)
				{
					if (!empty($orderStatus))
						$arFields['STATUS_ID'] = $orderStatus;
					self::RegisterLiveFeedEvent($arFields, $orderID, $userId);
					unset($arFields['STATUS_ID']);
					if ($responsibleID > 0 && Settings\Crm::isLiveFeedRecordsGenerationEnabled())
					{
						CCrmSonetSubscription::RegisterSubscription(
							CCrmOwnerType::Invoice,
							$orderID,
							CCrmSonetSubscriptionType::Responsibility,
							$responsibleID
						);
					}
				}
				else
				{
					$newDealID = isset($arFields['UF_DEAL_ID']) ? intval($arFields['UF_DEAL_ID']) : 0;
					$oldDealID = isset($arPrevOrder['UF_DEAL_ID']) ? intval($arPrevOrder['UF_DEAL_ID']) : 0;

					$newCompanyID = isset($arFields['UF_COMPANY_ID']) ? intval($arFields['UF_COMPANY_ID']) : 0;
					$oldCompanyID = isset($arPrevOrder['UF_COMPANY_ID']) ? intval($arPrevOrder['UF_COMPANY_ID']) : 0;

					$newContactID = isset($arFields['UF_CONTACT_ID']) ? intval($arFields['UF_CONTACT_ID']) : 0;
					$oldContactID = isset($arPrevOrder['UF_CONTACT_ID']) ? intval($arPrevOrder['UF_CONTACT_ID']) : 0;

					$parents = array();
					$parentsChanged = $newDealID !== $oldDealID || $newCompanyID !== $oldCompanyID || $newContactID !== $oldContactID;
					if($parentsChanged)
					{
						if($newDealID > 0)
						{
							$parents[] = array(
								'ENTITY_TYPE_ID' => CCrmOwnerType::Deal,
								'ENTITY_ID' => $newDealID
							);
						}

						if($newCompanyID > 0)
						{
							$parents[] = array(
								'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
								'ENTITY_ID' => $newCompanyID
							);
						}

						if($newContactID > 0)
						{
							$parents[] = array(
								'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
								'ENTITY_ID' => $newContactID
							);
						}
					}

					$oldOrderStatus = isset($arPrevOrder['STATUS_ID']) ? $arPrevOrder['STATUS_ID'] : '';
					self::SynchronizeLiveFeedEvent(
						$orderID,
						array(
							'PROCESS_PARENTS' => $parentsChanged,
							'PARENTS' => $parents,
							'REFRESH_DATE' => $orderStatus !== $oldOrderStatus,
							'START_RESPONSIBLE_ID' => $prevResponsibleID,
							'FINAL_RESPONSIBLE_ID' => $responsibleID,
							'TOPIC' => isset($arPrevOrder['ORDER_TOPIC']) ? $arPrevOrder['ORDER_TOPIC'] : $orderID
						)
					);

					if (
						$responsibleID !== $prevResponsibleID
						&& Settings\Crm::isLiveFeedRecordsGenerationEnabled()
					)
					{
						CCrmSonetSubscription::ReplaceSubscriptionByEntity(
							CCrmOwnerType::Invoice,
							$orderID,
							CCrmSonetSubscriptionType::Responsibility,
							$responsibleID,
							$prevResponsibleID,
							true
						);
					}
				}
			}
		}

		if (intval($orderID) > 0)
		{
			if (!empty($orderStatus) && !(isset($options['SKIP_STATUS']) && $options['SKIP_STATUS']))
			{
				// set status
				$this->SetStatus($orderID, $orderStatus, false, array('SKIP_UPDATE' => true));
			}
			else if ($paidStateCanceled)
			{
				Compatible\Helper::payOrder($tmpOrderId, true);
			}

			$securityRegisterOptions = (new \Bitrix\Crm\Security\Controller\RegisterOptions())
				->setEntityAttributes($arEntityAttr)
			;
			\Bitrix\Crm\Security\Manager::getEntityController(CCrmOwnerType::Invoice)
				->register(self::$TYPE_NAME, $orderID, $securityRegisterOptions)
			;

			$newDealID = isset($arFields['UF_DEAL_ID']) ? (int)$arFields['UF_DEAL_ID'] : 0;
			$oldDealID = is_array($arPrevOrder) && isset($arPrevOrder['UF_DEAL_ID']) ? (int)$arPrevOrder['UF_DEAL_ID'] : 0;

			if($newDealID)
			{
				Bitrix\Crm\Statistics\DealInvoiceStatisticEntry::register($newDealID);
			}
			if($oldDealID > 0 && $oldDealID !== $newDealID)
			{
				Bitrix\Crm\Statistics\DealInvoiceStatisticEntry::register($oldDealID);
			}

			if(!isset($options['UPDATE_SEARCH']) ||  $options['UPDATE_SEARCH'] === true)
			{
				$arFilterTmp = Array('ID' => $orderID);
				if (!$this->bCheckPermission)
					$arFilterTmp["CHECK_PERMISSIONS"] = "N";
				CCrmSearch::UpdateSearch($arFilterTmp, 'INVOICE', true);
			}
		}

		if ($orderID > 0)
		{
			$isNew = $tmpOrderId <= 0;

			if(defined('BX_COMP_MANAGED_CACHE'))
			{
				$GLOBALS['CACHE_MANAGER']->CleanDir('b_crm_invoice');
			}

			//Statistics & History -->
			if((!isset($options['REGISTER_STATISTICS']) || $options['REGISTER_STATISTICS'] === true) && $arFields['IS_RECURRING'] !== 'Y')
			{
				Bitrix\Crm\History\InvoiceStatusHistoryEntry::register($orderID, null, array('IS_NEW' => $isNew));
				Bitrix\Crm\Statistics\InvoiceSumStatisticEntry::register($orderID, null);
			}
			//<-- Statistics & History

			//region Search content index
			Bitrix\Crm\Search\SearchContentBuilderFactory::create(CCrmOwnerType::Invoice)->build($orderID);
			//endregion Search content index

			if (!isset($arFields['ID']))
				$arFields['ID'] = $orderID;
			if (!empty($orderStatus))
				$arFields['STATUS_ID'] = $orderStatus;
			foreach (GetModuleEvents('crm', (($tmpOrderId <= 0) ? 'OnAfterCrmInvoiceAdd' : 'OnAfterCrmInvoiceUpdate'), true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array(&$arFields));
		}

		return $orderID;
	}

	public function Update($ID, $arFields, $arOptions = array())
	{
		$arFields['ID'] = $ID;
		$recalculate = false;
		return $this->Add($arFields, $recalculate, SITE_ID, $arOptions);
	}

	public function Delete($ID)
	{
		/** @global CUserTypeManager $USER_FIELD_MANAGER */
		/** @global CMain $APPLICATION */
		/** @var CApplicationException $ex */
		global $USER_FIELD_MANAGER, $APPLICATION;

		$APPLICATION->ResetException();
		foreach (GetModuleEvents('crm', 'OnBeforeCrmInvoiceDelete', true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($ID)) === false)
			{
				$errMsg = '';
				if ($ex = $APPLICATION->GetException())
				{
					$errMsg = trim(strval($ex->GetString()));
				}

				if ($errMsg === '')
				{
					$this->LAST_ERROR = GetMessage(
						'CRM_INVOICE_DELETE_CANCELED', array('#NAME#' => $arEvent['TO_NAME'])
					);
				}
				else
				{
					$this->LAST_ERROR = $errMsg;
				}
				unset($errMsg);

				$APPLICATION->throwException($this->LAST_ERROR);
				return false;
			}
		}

		if(!CModule::IncludeModule('sale'))
		{
			$this->LAST_ERROR = GetMessage('CRM_MODULE_SALE_NOT_INSTALLED');
			$APPLICATION->throwException($this->LAST_ERROR);
			return false;
		}

		$dealID = 0;
		$dbResult = CCrmInvoice::GetList(array(), array('ID' => $ID, 'CHECK_PERMISSIONS' => 'N'), false, false, array('ID', 'UF_DEAL_ID', 'IS_RECURRING'));
		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(is_array($fields))
		{
			$dealID = isset($fields['UF_DEAL_ID']) ? $fields['UF_DEAL_ID'] : 0;
		}

		$result = Compatible\Helper::delete($ID);
		if($result)
		{
			if(defined('BX_COMP_MANAGED_CACHE'))
			{
				$GLOBALS['CACHE_MANAGER']->CleanDir('b_crm_invoice');
			}

			Bitrix\Crm\Kanban\SortTable::clearEntity($ID, \CCrmOwnerType::InvoiceName);

			$USER_FIELD_MANAGER->Delete(self::$sUFEntityID, $ID);
			CCrmProductRow::DeleteSettings('I', $ID);
			self::UnregisterLiveFeedEvent($ID);
			CCrmSonetSubscription::UnRegisterSubscriptionByEntity(CCrmOwnerType::Invoice, $ID);
			if($dealID > 0)
			{
				Bitrix\Crm\Statistics\DealInvoiceStatisticEntry::register($dealID);
			}

			\Bitrix\Crm\Security\Manager::getEntityController(CCrmOwnerType::Invoice)
				->unregister(self::$TYPE_NAME, $ID)
			;

			CCrmSearch::DeleteSearch('INVOICE', $ID);

			\Bitrix\Crm\Requisite\EntityLink::unregister(CCrmOwnerType::Invoice, $ID);

			Bitrix\Crm\History\InvoiceStatusHistoryEntry::unregister($ID);
			Bitrix\Crm\Statistics\InvoiceSumStatisticEntry::unregister($ID);

			foreach (GetModuleEvents('crm', 'OnAfterCrmInvoiceDelete', true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($ID));

			if ($fields['IS_RECURRING'] === "Y")
			{
				$invoiceRecurringItem = \Bitrix\Crm\Recurring\Entity\Item\InvoiceExist::loadByInvoiceId($ID);
				if ($invoiceRecurringItem)
				{
					$invoiceRecurringItem->delete();
				}
			}
		}

		return $result;
	}

	public function Recalculate($arFields)
	{
		$result = false;

		$arRecalculated = array();
		if ($this->Add($arFields, $arRecalculated))
			$result = $arRecalculated;

		return $result;
	}

	public static function GetTotalCount()
	{
		if(defined('BX_COMP_MANAGED_CACHE') && $GLOBALS['CACHE_MANAGER']->Read(self::CACHE_TTL, self::TOTAL_COUNT_CACHE_ID, 'b_crm_invoice'))
		{
			return $GLOBALS['CACHE_MANAGER']->Get(self::TOTAL_COUNT_CACHE_ID);
		}

		$result = (int)self::GetList(
			array(),
			array('CHECK_PERMISSIONS' => 'N'),
			array(),
			false,
			array(),
			array('ENABLE_ROW_COUNT_THRESHOLD' => false)
		);

		if(defined('BX_COMP_MANAGED_CACHE'))
		{
			$GLOBALS['CACHE_MANAGER']->Set(self::TOTAL_COUNT_CACHE_ID, $result);
		}
		return $result;
	}

	public function SetStatus($ID, $statusID, $statusParams = false, $options = array())
	{
		global $USER;

		$beforeEvents = GetModuleEvents('crm', 'OnBeforeCrmInvoiceSetStatus');
		while ($arEvent = $beforeEvents->Fetch())
		{
			ExecuteModuleEventEx(
				$arEvent,
				array(
					array(
						'ID' => $ID,
						'STATUS_ID' => $statusID,
						'STATUS_PARAMS' => $statusParams,
						'OPTIONS' => $options
					)
				)
			);
		}

		if(!CModule::IncludeModule('sale'))
		{
			$beforeEvents = GetModuleEvents('crm', 'OnAfterCrmInvoiceSetStatus');
			while ($arEvent = $beforeEvents->Fetch())
			{
				ExecuteModuleEventEx(
					$arEvent,
					array(
						array(
							'ID' => $ID,
							'STATUS_ID' => $statusID,
							'STATUS_PARAMS' => $statusParams,
							'OPTIONS' => $options,
							'RESULT' => false
						)
					)
				);
			}

			return false;
		}

		$currentUserId = 0;
		if(isset($USER) && ((get_class($USER) === 'CUser') || ($USER instanceof CUser)))
		{
			$currentUserId = $USER->GetID();
		}

		$result = true;
		self::EnsureStatusesLoaded();
		if (!is_array(self::$INVOICE_STATUSES) || count(self::$INVOICE_STATUSES) <= 2 ||
			!array_key_exists('P', self::$INVOICE_STATUSES) || !array_key_exists('D', self::$INVOICE_STATUSES) ||
			self::$INVOICE_STATUSES['P']['SORT'] >= self::$INVOICE_STATUSES['D']['SORT']) $result = false;

		if ($result)
		{
			// get current state
			if (!($arOrder = Compatible\Helper::getById($ID))) $result = false;
			if ($result)
			{
				$curPay = $arOrder['PAYED'];
				$curCancel = $arOrder['CANCELED'];
				$curStatusID = $arOrder['STATUS_ID'];


				$pay = $cancel = 'N';
				$marked = (isset($statusParams['REASON_MARKED']) || isset($statusParams['DATE_MARKED'])) ? 'Y' : 'N';
				if (self::$INVOICE_STATUSES[$statusID]['SORT'] >= self::$INVOICE_STATUSES['P']['SORT'])
				{
					$pay = 'Y';
				}
				if (self::$INVOICE_STATUSES[$statusID]['SORT'] >= self::$INVOICE_STATUSES['D']['SORT'])
				{
					$pay = 'N';
					$cancel = 'Y';
				}
				if ($curPay != $pay)
				{
					$result = Compatible\Helper::payOrder($ID, $pay);
					$curCancel = 'N';
				}
				if ($result && $curCancel != $cancel)
				{
					$result = Compatible\Helper::cancelOrder($ID, $cancel);
				}
				if ($result && $curStatusID != $statusID)
				{
					Compatible\Helper::statusOrder($ID, $statusID);
				}
				if ($result && $marked === 'Y')
				{
					$result = Compatible\Helper::setMark(
						$ID,
						isset($statusParams['REASON_MARKED']) ? $statusParams['REASON_MARKED'] : '',
						$currentUserId
					);
				}
				if ($result && !(isset($options['SKIP_UPDATE']) && $options['SKIP_UPDATE']))
				{
					$arUpdate = array();
					if (isset($statusParams['DATE_MARKED']))
						$arUpdate['DATE_MARKED'] = $statusParams['DATE_MARKED'];
					if ($pay === 'Y')
					{
						if (isset($statusParams['PAY_VOUCHER_NUM']))
							$arUpdate['PAY_VOUCHER_NUM'] = $statusParams['PAY_VOUCHER_NUM'];
						if (isset($statusParams['PAY_VOUCHER_DATE']))
							$arUpdate['PAY_VOUCHER_DATE'] = $statusParams['PAY_VOUCHER_DATE'];
					}
					if (count($arUpdate) > 0)
					{
						$this->Update($ID, $arUpdate, array('REGISTER_STATISTICS' => false, 'SKIP_STATUS' => true));
					}
					unset($arUpdate);
				}
				if ($result && $curStatusID != $statusID)
				{
					Bitrix\Crm\History\InvoiceStatusHistoryEntry::register($ID);
					Bitrix\Crm\Statistics\InvoiceSumStatisticEntry::register($ID, null);
				}
			}
		}

		if($result
			&& is_array($options)
			&& isset($options['SYNCHRONIZE_LIVE_FEED'])
			&& $options['SYNCHRONIZE_LIVE_FEED'])
		{
			self::SynchronizeLiveFeedEvent(
				$ID,
				array(
					'PROCESS_PARENTS' => false,
					'REFRESH_DATE' => true
				)
			);
		}

		//region Search content index
		if ($result)
		{
			Bitrix\Crm\Search\SearchContentBuilderFactory::create(CCrmOwnerType::Invoice)->build($ID);
		}
		//endregion Search content index

		$beforeEvents = GetModuleEvents('crm', 'OnAfterCrmInvoiceSetStatus');
		while ($arEvent = $beforeEvents->Fetch())
		{
			ExecuteModuleEventEx(
				$arEvent,
				array(
					array(
						'ID' => $ID,
						'STATUS_ID' => $statusID,
						'STATUS_PARAMS' => $statusParams,
						'OPTIONS' => $options,
						'RESULT' => (bool)$result
					)
				)
			);
		}

		return $result;
	}

	public static function GetCurrencyID($siteId = SITE_ID)
	{
		return CCrmCurrency::getInvoiceDefault();
	}

	public static function IsAccessEnabled(CCrmPerms $userPermissions = null)
	{
		return self::CheckReadPermission(0, $userPermissions);
	}

	public static function CheckCreatePermission($userPermissions = null)
	{
		return CCrmAuthorizationHelper::CheckCreatePermission(self::$TYPE_NAME, $userPermissions);
	}

	public static function CheckUpdatePermission($ID, $userPermissions = null)
	{
		return CCrmAuthorizationHelper::CheckUpdatePermission(self::$TYPE_NAME, $ID, $userPermissions);
	}

	public static function CheckDeletePermission($ID, $userPermissions = null)
	{
		return CCrmAuthorizationHelper::CheckDeletePermission(self::$TYPE_NAME, $ID, $userPermissions);
	}

	public static function CheckReadPermission($ID = 0, $userPermissions = null)
	{
		return CCrmAuthorizationHelper::CheckReadPermission(self::$TYPE_NAME, $ID, $userPermissions);
	}

	public static function GetProductRows($ID)
	{
		if(!CModule::IncludeModule('sale'))
		{
			return false;
		}

		$result = array();
		if (is_array($ID) || (int)$ID > 0)
		{
			$dbRes = Compatible\BasketHelper::getList(
				array('ORDER_ID' => 'ASC', 'SORT' => 'ASC', 'ID' => 'ASC'), array('ORDER_ID' => $ID), false, false,
				array(
					'ID',
					'ORDER_ID',
					'PRODUCT_ID',
					'NAME',
					'QUANTITY',
					'PRICE',
					'CUSTOM_PRICE',
					'DISCOUNT_PRICE',
					'VAT_RATE',
					'VAT_INCLUDED',
					'MEASURE_CODE',
					'MEASURE_NAME',
					'MODULE',
					'CATALOG_XML_ID',
					'PRODUCT_XML_ID'
				)
			);
			while ($row = $dbRes->Fetch())
			{
				if (isset($row['NAME']))
				{
					$row['PRODUCT_NAME'] = $row['NAME'];
					unset($row['NAME']);
				}

				//HACK Reset Product ID if product is not from catalog
				if (empty($row['MODULE']) && empty($row['CATALOG_XML_ID']))
				{
					$row['PRODUCT_ID'] = 0;
				}
				$result[] = $row;
			}
			unset($row);
		}

		return $result;
	}

	public static function HasProductRows($productID)
	{
		if(!CModule::IncludeModule('sale'))
		{
			return false;
		}

		$result = false;

		$saleUserId = CSaleUser::GetAnonymousUserID();
		$dbRes = Compatible\BasketHelper::getList(
			array(),
			array(
				'PRODUCT_ID' => $productID,
				'>ORDER_ID' => 0,
				'USER_ID' => $saleUserId,
				'=MODULE' => 'catalog',
			),
			false,
			array('nTopCount' => 1),
			array('ID')
		);
		if (is_object($dbRes))
		{
			$arRes = $dbRes->Fetch();
			if (is_array($arRes) && isset($arRes['ID']) && intval($arRes['ID']) > 0)
				$result = true;
		}

		return $result;
	}

	public static function getTaxList($ID)
	{
		if(!CModule::IncludeModule('sale'))
		{
			return false;
		}

		$arResult = array();

		$dbTaxList = CCrmInvoiceTax::GetList(
			array("APPLY_ORDER" => "ASC"),
			array("ORDER_ID" => $ID)
		);

		while ($arTaxList = $dbTaxList->Fetch())
		{
			$arResult[] = array(
				'IS_IN_PRICE' => $arTaxList['IS_IN_PRICE'],
				'TAX_NAME' => $arTaxList['TAX_NAME'],
				'IS_PERCENT' => $arTaxList['IS_PERCENT'],
				'VALUE' => $arTaxList['VALUE'],
				'VALUE_MONEY' => $arTaxList['VALUE_MONEY']
			);
		}

		return $arResult;
	}

	private static function _getAllowedPropertiesInfo(?string $siteId = null)
	{
		if ($siteId === null)
		{
			$siteId = CCrmPaySystem::getDefaultSiteId();
		}

		if (isset(self::$arinvoicePropertiesAllowed[$siteId]))
		{
			return self::$arinvoicePropertiesAllowed[$siteId];
		}

		$arPersonTypes = CCrmPaySystem::getPersonTypeIDs($siteId);
		if ($arPersonTypes['COMPANY'] != "" && $arPersonTypes['CONTACT'] != "")
		{
			$personTypeCompany = $arPersonTypes['COMPANY'];
			$personTypeContact = $arPersonTypes['CONTACT'];
		}
		else
		{
			return [];
		}

		self::$arinvoicePropertiesAllowed[$siteId] = array(
			$personTypeCompany => array(
				'COMPANY' => GetMessage('CRM_INVOICE_PROPERTY_COMPANY_TITLE'),
				'COMPANY_NAME' => GetMessage('CRM_INVOICE_PROPERTY_COMPANY_TITLE'),
				'COMPANY_ADR' => GetMessage('CRM_INVOICE_PROPERTY_COMPANY_ADR'),
				'CONTACT_PERSON' => GetMessage('CRM_INVOICE_PROPERTY_COMPANY_CONTACT_PERSON'),
				'EMAIL' => GetMessage('CRM_INVOICE_PROPERTY_COMPANY_EMAIL'),
				'PHONE' => GetMessage('CRM_INVOICE_PROPERTY_COMPANY_PHONE'),
				'INN' => GetMessage('CRM_INVOICE_PROPERTY_COMPANY_INN'),
				'KPP' => GetMessage('CRM_INVOICE_PROPERTY_COMPANY_KPP')
			),
			$personTypeContact => array(
				'FIO' => GetMessage('CRM_INVOICE_PROPERTY_CONTACT_FIO'),
				'ADDRESS' => GetMessage('CRM_INVOICE_PROPERTY_CONTACT_ADDRESS'),
				'EMAIL' => GetMessage('CRM_INVOICE_PROPERTY_CONTACT_EMAIL'),
				'PHONE' => GetMessage('CRM_INVOICE_PROPERTY_CONTACT_PHONE')
			)
		);

		return self::$arinvoicePropertiesAllowed[$siteId];
	}

	public static function GetPropertiesInfo($personTypeId = 0, $onlyEditable = false)
	{
		if(!CModule::IncludeModule('sale'))
		{
			return false;
		}

		$result = false;

		$personTypeId = intval($personTypeId);

		$allowedProperties = self::_getAllowedPropertiesInfo();
		$arFilter = array("ACTIVE" => "Y");
		if ($personTypeId > 0)
		{
			$arFilter["PERSON_TYPE_ID"] = $personTypeId;
		}

		$dbRes = \Bitrix\Crm\Invoice\Property::getList([
			'select' => ["*", 'GROUP_SORT' => 'GROUP.SORT'],
			'filter' => $arFilter,
			'order' => [
				"GROUP_SORT" => "ASC",
				"PROPS_GROUP_ID" => "ASC",
				"SORT" => "ASC",
				"NAME" => "ASC"
			]
		]);

		$arResult = array();
		while ($arProperty = $dbRes->Fetch())
		{
			$arProperty = CSaleOrderPropsAdapter::convertNewToOld($arProperty);

			if (array_key_exists($arProperty["CODE"], $allowedProperties[$arProperty["PERSON_TYPE_ID"]]))
			{
				$arProperty["NAME"] = $allowedProperties[$arProperty["PERSON_TYPE_ID"]][$arProperty["CODE"]];
				if ($onlyEditable)
					$arResult[$arProperty["PERSON_TYPE_ID"]][$arProperty["CODE"]] = $arProperty;
			}
			if (!$onlyEditable)
				$arResult[$arProperty["PERSON_TYPE_ID"]][$arProperty["CODE"]] = $arProperty;
		}

		if (count($arResult) > 0)
			$result = $arResult;

		return $result;
	}

	public static function GetProperties($ID, $personTypeId)
	{
		if(!CModule::IncludeModule('sale'))
		{
			return false;
		}

		$result = false;

		$bTaxMode = CCrmTax::isTaxMode();

		$ID = intval($ID);
		$personTypeId = intval($personTypeId);
		// if ($ID <= 0 || $personTypeId <= 0) return false;

		$locationId = null;

		$arPropValues = array();

		if ($ID > 0)
		{
			$query = new Bitrix\Main\Entity\Query(Bitrix\Crm\Invoice\Internals\InvoicePropsValueTable::getEntity());
			$query->registerRuntimeField(
				'',
				new Bitrix\Main\Entity\ReferenceField('LOCATION',
					Bitrix\Sale\Location\LocationTable::getEntity(),
					[
						'=this.PROPERTY.TYPE' => ['?', 'LOCATION'],
						'=this.VALUE' => 'ref.CODE'
					],
					['join_type' => 'LEFT']
				)
			);
			$query->setSelect(
				[
					'ID',
					'ORDER_PROPS_ID',
					'VALUE',
					'PROPERTY_TYPE' => 'PROPERTY.TYPE',
					'LOCATION_ID' => 'LOCATION.ID'
				]
			);
			$query->setFilter(['=ORDER_ID' => $ID, '=PROPERTY.ACTIVE' => 'Y']);
			$query->setOrder(['PROPERTY.SORT' => 'ASC']);
			$res = $query->exec();
			while ($row = $res->fetch())
			{
				$arPropValues[intval($row['ORDER_PROPS_ID'])] =
					$row['PROPERTY_TYPE'] === 'LOCATION' ? $row['LOCATION_ID'] : $row['VALUE'];
			}
		}

		$arFilter = array("ACTIVE" => "Y");
		if ($personTypeId > 0)
		{
			$arFilter["PERSON_TYPE_ID"] = $personTypeId;
		}

		$dbRes = \Bitrix\Crm\Invoice\Property::getList([
			'select' => ["*", 'GROUP_SORT' => 'GROUP.SORT'],
			'filter' => $arFilter,
			'order' => [
				"GROUP_SORT" => "ASC",
				"PROPS_GROUP_ID" => "ASC",
				"SORT" => "ASC",
				"NAME" => "ASC"
			]
		]);

		$propertyGroupId = -1;

		$arResult = array();
		while ($arProperties = $dbRes->fetch())
		{
			$arProperties = CSaleOrderPropsAdapter::convertNewToOld($arProperties);

			if (intval($arProperties["PROPS_GROUP_ID"]) != $propertyGroupId)
				$propertyGroupId = intval($arProperties["PROPS_GROUP_ID"]);

			$curVal = $arPropValues[intval($arProperties["ID"])];

			if ($arProperties["CODE"] == "LOCATION" && $bTaxMode)    // required field
			{
				$arResult['PR_LOCATION'] = array(
					'FIELDS' => $arProperties,
					'VALUE' => $curVal == '0' ? '' : $curVal
				);
			}

			$arResult['PR_INVOICE_'.$arProperties['ID']] = array(
				'FIELDS' => $arProperties,
				'VALUE' => $curVal
			);
		}

		if (count($arResult) > 0)
			$result = $arResult;

		return $result;
	}

	/**
	 * @param $idInvoices
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function getPropertiesList($idInvoices)
	{
		$result = array();
		$propsIdList = array();
		$valueList = array();
		$propertyList = array();

		if(!\Bitrix\Main\Loader::includeModule('sale'))
		{
			return array();
		}

		$idInvoices = !is_array($idInvoices) ? array($idInvoices) : $idInvoices;
		$valueData = \Bitrix\Crm\Invoice\Internals\InvoicePropsValueTable::getList(
			array(
				"filter" => array("ORDER_ID" => $idInvoices)
			)
		);

		while ($value = $valueData->fetch())
		{
			$propsIdList[] = $value['ORDER_PROPS_ID'];
			$valueList[$value['ORDER_ID']][] = $value;
		}

		$propsIdList = array_unique($propsIdList);

		$propsData = \Bitrix\Crm\Invoice\Property::getList(
			array(
				"filter" => array(
					"ID" => $propsIdList,
					"ACTIVE" => "Y"
				)
			)
		);
		while ($property = $propsData->fetch())
		{
			$propertyList[$property['ID']] = $property;
		}

		foreach($idInvoices as $id)
		{
			if (!is_array($valueList[$id]))
			{
				continue;
			}
			foreach ($valueList[$id] as $value)
			{
				$result[$id]['PR_INVOICE_'.$value['ORDER_PROPS_ID']] = array(
					'FIELDS' => $propertyList[$value['ORDER_PROPS_ID']],
					'VALUE' => $value
				);

				if ($value['CODE'] === "LOCATION")
				{
					$arResult['PR_LOCATION'] = array(
						'FIELDS' => $propertyList[$value['ORDER_PROPS_ID']],
						'VALUE' => $value == '0' ? '' : $value
					);
				}

			}
		}

		return $result;
	}

	public static function ParsePropertiesValuesFromPost($personTypeId, $post, &$arInvoiceProps)
	{
		if(!is_array($arInvoiceProps) || count($arInvoiceProps) <= 0)
		{
			return false;
		}

		$result = false;

		$bTaxMode = CCrmTax::isTaxMode();

		$arPropsValues = array();
		$arPropsIndexes = array();
		$error = 0;
		foreach ($arInvoiceProps as $propertyKey => $property)
		{
			if ((!isset($property['VALUE']) && $property['VALUE'] !== null) ||
				!isset($property['FIELDS']) || !is_array($property['FIELDS']) ||
				count($property['FIELDS']) <= 0)
			{
				$error = 1;
				break;
			}
			$arPropertyFields = &$property['FIELDS'];

			if ($arPropertyFields["CODE"] === "LOCATION" && isset($post['LOC_CITY']) && $bTaxMode)
			{
				// location
				$locationId = trim($post['LOC_CITY']);
				if ($locationId > 0)
					$arInvoiceProps['PR_LOCATION']['VALUE'] = $locationId;
				elseif (isset($arInvoiceProps['PR_LOCATION']))
					$locationId = $arInvoiceProps['PR_LOCATION']['VALUE'];
				if ($locationId > 0 && ($personTypeId === 0 || $arPropertyFields["PERSON_TYPE_ID"] == $personTypeId))
				{
					$arPropsValues[$arPropertyFields["ID"]] = $locationId;
					$arPropsIndexes['PR_LOCATION'] = $arPropertyFields["ID"];
					//rewrite invoice property
					$arInvoiceProps['PR_LOCATION']['VALUE'] = $locationId;
				}
				unset($locationId);
			}

			$curVal = $arInvoiceProps['PR_INVOICE_'.$arPropertyFields["ID"]]['VALUE'];

			if (isset($post["PR_INVOICE_".$arPropertyFields["ID"]]))
			{
				if(!is_array($post["PR_INVOICE_".$arPropertyFields["ID"]]))
				{
					$curVal = trim($post["PR_INVOICE_".$arPropertyFields["ID"]]);
					if ($arPropertyFields["TYPE"] == "CHECKBOX"
						&& $curVal == ''
						&& $arPropertyFields["REQUIED"] != "Y")
					{
						$curVal = "N";
					}
				}
				else
				{
					if ($arPropertyFields["TYPE"] == "MULTISELECT")
					{
						$curVal = "";
						$countOrderProp = count($post["PR_INVOICE_".$arPropertyFields["ID"]]);
						for ($i = 0; $i < $countOrderProp; $i++)
						{
							if ($i > 0)
								$curVal .= ",";

							$curVal .= $post["PR_INVOICE_".$arPropertyFields["ID"]][$i];
						}
					}
					else
					{
						$curVal = $post["PR_INVOICE_".$arPropertyFields["ID"]];
					}
				}
			}

			if (!isset($arPropsValues[$arPropertyFields["ID"]])
				&& ($personTypeId === 0 || $arPropertyFields["PERSON_TYPE_ID"] == $personTypeId))
			{
				$arPropsValues[$arPropertyFields["ID"]] = $curVal;
				//rewrite invoice property
				$arInvoiceProps['PR_INVOICE_'.$arPropertyFields["ID"]]['VALUE'] = $curVal;
			}
			if (!isset($arPropsIndexes['PR_INVOICE_'.$arPropertyFields["ID"]]))
				$arPropsIndexes['PR_INVOICE_'.$arPropertyFields["ID"]] = $arPropertyFields["ID"];
		}

		if ($error > 0) return false;

		if (count($arPropsValues) > 0)
			$result = array(
				'PROPS_VALUES' => $arPropsValues,
				'PROPS_INDEXES' => $arPropsIndexes
			);

		return $result;
	}

	public static function __MakePropsHtmlInputs($arInvoiceProperties)
	{
		$htmlInputs = '';
		foreach ($arInvoiceProperties as $propertyKey => $property)
			$htmlInputs .= '<input type="hidden" name="'.htmlspecialcharsbx($propertyKey).'" value="'.htmlspecialcharsbx($arInvoiceProperties[$propertyKey]['VALUE']).'"/>'.PHP_EOL;

		return $htmlInputs;
	}

	public static function __RewritePayerInfo($companyId, $contactId, &$arInvoiceProperties)
	{
		$arCompany = $companyEMail = $companyPhone = null;
		$arContact = $contactEMail = $contactPhone = null;

		if ($companyId > 0)
		{
			$arCompany = CCrmCompany::GetByID($companyId);

			// Get multifields values (EMAIL and PHONE)
			$arFieldsMulti = CCrmFieldMulti::GetEntityFields('COMPANY', $companyId, 'EMAIL', true, false);
			if (is_array($arFieldsMulti) && isset($arFieldsMulti[0]['VALUE']))
				$companyEMail = $arFieldsMulti[0]['VALUE'];
			$arFieldsMulti = CCrmFieldMulti::GetEntityFields('COMPANY', $companyId, 'PHONE', true, false);
			if (is_array($arFieldsMulti) && isset($arFieldsMulti[0]['VALUE']))
				$companyPhone = $arFieldsMulti[0]['VALUE'];
			unset($arFieldsMulti);
		}

		if ($contactId > 0)
		{
			$arContact = CCrmContact::GetByID($contactId);

			// Get multifields values (EMAIL and PHONE)
			$arFieldsMulti = CCrmFieldMulti::GetEntityFields('CONTACT', $contactId, 'EMAIL', true, false);
			if (is_array($arFieldsMulti) && isset($arFieldsMulti[0]['VALUE']))
				$contactEMail = $arFieldsMulti[0]['VALUE'];
			$arFieldsMulti = CCrmFieldMulti::GetEntityFields('CONTACT', $contactId, 'PHONE', true, false);
			if (is_array($arFieldsMulti) && isset($arFieldsMulti[0]['VALUE']))
				$contactPhone = $arFieldsMulti[0]['VALUE'];
			unset($arFieldsMulti);
		}

		if ($companyId > 0)
		{
			if (is_array($arCompany) && count($arCompany) >0)
			{
				foreach ($arInvoiceProperties as $propertyKey => $property)
				{
					$curVal = '';
					if ($property['FIELDS']['CODE'] === 'COMPANY' || $property['FIELDS']['CODE'] === 'COMPANY_NAME')
					{
						if (isset($arCompany['TITLE']))
							$curVal = $arCompany['TITLE'];
					}
					elseif ($property['FIELDS']['CODE'] === 'CONTACT_PERSON' && $contactId > 0)
					{
						if (isset($arContact['FULL_NAME']))
							$curVal = $arContact['FULL_NAME'];
					}
					elseif ($property['FIELDS']['CODE'] === 'COMPANY_ADR')
					{
						$curVal = AddressFormatter::getSingleInstance()->formatTextComma(
							CompanyAddress::mapEntityFields(
								$arCompany,
								['TYPE_ID' => EntityAddressType::Registered]
							)
						);
					}
					elseif ($property['FIELDS']['CODE'] === 'INN')
					{
						$todo = 'todo'; // TODO:
					}
					elseif ($property['FIELDS']['CODE'] === 'KPP')
					{
						$todo = 'todo'; // TODO:
					}
					elseif ($property['FIELDS']['CODE'] === 'EMAIL')
					{
						$curVal = ($contactEMail != '') ? $contactEMail : $companyEMail;
					}
					elseif ($property['FIELDS']['CODE'] === 'PHONE')
					{
						$curVal = ($contactPhone != '') ? $contactPhone : $companyPhone;
					}

					$arInvoiceProperties[$propertyKey]['VALUE'] = $curVal;
				}
			}
		}
		elseif ($contactId > 0)
		{
			if (is_array($arContact) && count($arContact) >0)
			{
				foreach ($arInvoiceProperties as $propertyKey => $property)
				{
					$curVal = '';
					if ($property['FIELDS']['CODE'] === 'FIO')
					{
						if (isset($arContact['FULL_NAME']))
							$curVal = $arContact['FULL_NAME'];
					}
					elseif ($property['FIELDS']['CODE'] === 'EMAIL')
					{
						$curVal = $contactEMail;
					}
					elseif ($property['FIELDS']['CODE'] === 'PHONE')
					{
						$curVal = $contactPhone;
					}
					elseif ($property['FIELDS']['CODE'] === 'ADDRESS')
					{
						$curVal = AddressFormatter::getSingleInstance()->formatTextComma(
							ContactAddress::mapEntityFields($arContact)
						);
					}

					$arInvoiceProperties[$propertyKey]['VALUE'] = $curVal;
				}
			}
		}
	}

	public static function rewritePropsFromRequisite($personTypeId, $requisiteId, &$arInvoiceProperties)
	{
		$personTypeId = (int)$personTypeId;
		$requisiteId = (int)$requisiteId;

		if ($requisiteId > 0)
		{
			$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
			if ($arPersonTypes['COMPANY'] != "" && $arPersonTypes['CONTACT'] != ""
				&& ($personTypeId == $arPersonTypes['CONTACT'] || $personTypeId == $arPersonTypes['COMPANY'])
			)
			{
				$personTypeCompany = $arPersonTypes['COMPANY'];
				$personTypeContact = $arPersonTypes['CONTACT'];

				// requisite values
				$requisiteValues = array();
				$requisite = new \Bitrix\Crm\EntityRequisite();
				$preset = new \Bitrix\Crm\EntityPreset();
				$row = $requisite->getById($requisiteId);
				if (is_array($row))
				{
					if (isset($row['PRESET_ID']) && $row['PRESET_ID'] > 0)
					{
						$presetFields = array();
						$res = $preset->getList(array(
							'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
							'filter' => array(
								'=ENTITY_TYPE_ID' => \Bitrix\Crm\EntityPreset::Requisite,
								'=ID' => (int)$row['PRESET_ID']
							),
							'select' => array('ID', 'COUNTRY_ID', 'SETTINGS'),
							'limit' => 1
						));
						if ($presetData = $res->fetch())
						{
							if (is_array($presetData['SETTINGS']))
							{
								$presetFieldsInfo = $preset->settingsGetFields($presetData['SETTINGS']);
								foreach ($presetFieldsInfo as $fieldInfo)
								{
									if (isset($fieldInfo['FIELD_NAME']))
										$presetFields[$fieldInfo['FIELD_NAME']] = true;
								}
								unset($presetFieldsInfo, $fieldInfo);
							}
						}
						unset($res, $presetData);

						foreach ($row as $fieldName => $fieldValue)
						{
							if (isset($presetFields[$fieldName]))
							{
								$requisiteValues[$fieldName] = $fieldValue;
							}
						}
						unset($fieldName, $fieldValue, $valueKey);

						// addresses
						foreach ($requisite->getAddresses($requisiteId) as $addrTypeId => $addrFields)
						{
							$valueKey = Bitrix\Crm\EntityRequisite::ADDRESS.'_'.$addrTypeId;
							$requisiteValues[$valueKey] =
								AddressFormatter::getSingleInstance()->formatTextComma($addrFields);
						}
					}
				}

				// full name
				$fullName = isset($requisiteValues['RQ_NAME']) ?
					trim(strval($requisiteValues['RQ_NAME'])) : '';
				if (empty($fullName))
				{
					$firstName = isset($requisiteValues['RQ_FIRST_NAME'])
						? trim(strval($requisiteValues['RQ_FIRST_NAME'])) : '';
					$lastName = isset($requisiteValues['RQ_LAST_NAME']) ?
						trim(strval($requisiteValues['RQ_LAST_NAME'])) : '';
					$secondName = isset($requisiteValues['RQ_SECOND_NAME']) ?
						trim(strval($requisiteValues['RQ_SECOND_NAME'])) : '';
					if (!empty($firstName) || !empty($lastName) || !empty($secondName))
					{
						$fullName = CUser::FormatName(
							\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
							array(
								'LOGIN' => '[]',
								'NAME' => $firstName,
								'LAST_NAME' => $lastName,
								'SECOND_NAME' => $secondName
							),
							true, false
						);
						if (!empty($fullName) && $fullName !== '[]')
						{
							$requisiteValues['RQ_NAME'] = $fullName;
						}
					}
				}

				$requisiteToPropsMap = array(
					$personTypeCompany => array(
						'RQ_COMPANY_NAME' => array('COMPANY_NAME', 'COMPANY'),
						'RQ_ADDR_'.EntityAddressType::Registered => 'COMPANY_ADR',
						'RQ_INN' => 'INN',
						'RQ_KPP' => 'KPP',
						'RQ_CONTACT' => 'CONTACT_PERSON',
						'RQ_EMAIL' => 'EMAIL',
						'RQ_PHONE' => 'PHONE'
					),
					$personTypeContact => array(
						'RQ_NAME' => 'FIO',
						'RQ_EMAIL' => 'EMAIL',
						'RQ_PHONE' => 'PHONE',
						'RQ_ADDR_'.EntityAddressType::Primary => 'ADDRESS'
					),
				);

				$propsIndex = array();
				foreach ($arInvoiceProperties as $propertyKey => $property)
					$propsIndex[$property['FIELDS']['CODE']] = $propertyKey;
				if (is_array($requisiteValues) && !empty($requisiteValues))
				{
					foreach ($requisiteToPropsMap[$personTypeId] as $rqIndex => $propertyCodes)
					{
						if (isset($requisiteValues[$rqIndex])
							&& !empty($requisiteValues[$rqIndex])
						)
						{
							if (!is_array($propertyCodes))
								$propertyCodes = array($propertyCodes);
							foreach ($propertyCodes as $propertyCode)
							{
								if (isset($propsIndex[$propertyCode]))
									$arInvoiceProperties[$propsIndex[$propertyCode]]['VALUE'] = $requisiteValues[$rqIndex];
							}
						}
					}
				}
			}
		}
	}

	public static function __MakePayerInfoString($arInvoiceProperties)
	{
		$strPayerInfo = '';

		if(!self::$INVOICE_PROPERTY_INFOS)
		{
			self::$INVOICE_PROPERTY_INFOS = CCrmInvoice::GetPropertiesInfo(0, true);
		}

		$i = 0;
		foreach (self::$INVOICE_PROPERTY_INFOS as $person => $props)
		{
			$index = 0;
			foreach ($props as $code => $fields)
			{
				if ($fields['TYPE'] === 'TEXT' || $fields['TYPE'] === 'TEXTAREA')
				{
					$value = trim($arInvoiceProperties['PR_INVOICE_'.$fields['ID']]['VALUE']);
					if ($value != '')
					{
						if ($i > 0)
							$strPayerInfo .= ', ';
						$strPayerInfo .= $value;
						$i++;
					}
				}
			}
		}

		return $strPayerInfo;
	}

	public static function __GetCompanyAndContactFromPost(&$post)
	{
		//TODO: Move this method to crm.invoice edit

		$result = array('COMPANY' => 0, 'CONTACT' => 0, 'COMPANY_ISSET' => false, 'CONTACT_ISSET' => false);

		$primaryEntityTypeName = isset($post['PRIMARY_ENTITY_TYPE']) ? $post['PRIMARY_ENTITY_TYPE'] : '';
		$primaryEntityTypeID = CCrmOwnerType::ResolveID($primaryEntityTypeName);
		$primaryEntityIsSet = isset($post['PRIMARY_ENTITY_ID']);
		$primaryEntityID = $primaryEntityIsSet ? (int)$post['PRIMARY_ENTITY_ID'] : 0;

		if($primaryEntityTypeID === CCrmOwnerType::Contact)
		{
			$result['CONTACT'] = $primaryEntityID;
			$result['CONTACT_ISSET'] = $primaryEntityIsSet;
		}
		elseif($primaryEntityTypeID === CCrmOwnerType::Company)
		{
			$result['COMPANY'] = $primaryEntityID;
			$result['COMPANY_ISSET'] = $primaryEntityIsSet;
			if(isset($post['SECONDARY_ENTITY_IDS']))
			{
				$secondaryEntityIDs = explode(',', $post['SECONDARY_ENTITY_IDS']);
				if(!empty($secondaryEntityIDs))
				{
					$result['CONTACT'] = (int)$secondaryEntityIDs[0];
					$result['CONTACT_ISSET'] = true;
				}
			}
		}

		return $result;
	}

	/**
	* <p>
	* CREATE SALE AND CATALOG MODULES ENTITIES FOR INVOICES IN CRM VERSION 12.5.7
	* <br>UPDATE ORDER OPTION IN CRM VERSION 12.5.14
	* <br>CREATE 1C EXCHANGE OPTIONS DEFAULTS AND DEFAULT INVOICE EXPORT PROFILES IN CRM VERSION 12.5.17
	* <br>...
	* </p>
	*/
	public static function installExternalEntities()
	{
		global $DB;
		$errMsg = array();
		$bError = false;

		$clearCountableFromCallListsOption = '~CRM_CLEAR_COUNTABLE_FROM_CALLISTS';
		if ((string)COption::GetOptionString('crm', $clearCountableFromCallListsOption, 'N') === 'N')
		{
			COption::SetOptionString('crm', $clearCountableFromCallListsOption, 'Y');
			$wrongRecordsExist = (bool)($DB->Query('select * from b_crm_entity_countable_act where ENTITY_TYPE_ID=12 LIMIT 1')->Fetch());
			if ($wrongRecordsExist)
			{
				$DB->Query('delete from b_crm_entity_countable_act where ENTITY_TYPE_ID=12');
				\Bitrix\Crm\Settings\CounterSettings::getInstance()->cleanCounterLimitCache();
			}
		}

		$catalogNormalizeOption = '~CRM_CATALOG_NORMALIZE_18_5_0';
		$catalogNormalizeStep = (string)Main\Config\Option::get('crm', $catalogNormalizeOption, 'N');
		if ($catalogNormalizeStep === 'Y')
			return true;

		// at first, check last update version
		if (COption::GetOptionString('crm', '~CRM_INVOICE_INST_ORDER_DATA_18_5_0', 'N') === 'Y')
		{
			if ($catalogNormalizeStep !== 'Y')
			{
				if (self::setCatalogAdminRights() && self::createOfferIBlocks())
					Main\Config\Option::set('crm', $catalogNormalizeOption, 'Y', '');
			}
			return true;
		}

		// at first, check last update version
		if (COption::GetOptionString('crm', '~CRM_INVOICE_INST_PROP_LOCATION_UA_17_0_9', 'N') === 'Y')
		{
			if (COption::GetOptionString('crm', '~CRM_INVOICE_INST_ORDER_DATA_18_5_0', 'N') !== 'Y')
			{
				try
				{
					COption::SetOptionString('crm', '~CRM_INVOICE_INST_ORDER_DATA_18_5_0', 'Y');
					require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/crm/install/modules/data.php");
				}
				catch (Exception $e)
				{
					COption::SetOptionString('crm', '~CRM_INVOICE_INST_ORDER_DATA_18_5_0', 'N');
					$errMsg[] = $e->getMessage();
					$bError = true;
				}

				if ($bError)
				{
					$errString = implode('<br>', $errMsg);
					ShowError($errString);
					return false;
				}

				if ($catalogNormalizeStep !== 'Y')
				{
					if (self::setCatalogAdminRights() && self::createOfferIBlocks())
						Main\Config\Option::set('crm', $catalogNormalizeOption, 'Y', '');
				}
			}

			return true;
		}

		if (COption::GetOptionString('crm', '~CRM_INVOICE_DISABLE_SALE_EVENTS_16_5_10', 'N') === 'Y')
		{
			if (COption::GetOptionString('crm', '~CRM_INVOICE_INST_PROP_LOCATION_UA_17_0_9', 'N') !== 'Y')
			{
				self::installOrderPropertyLocationUa();

				COption::SetOptionString('crm', '~CRM_INVOICE_INST_PROP_LOCATION_UA_17_0_9', 'Y');

				if (COption::GetOptionString('crm', '~CRM_INVOICE_INST_ORDER_DATA_18_5_0', 'N') !== 'Y')
				{
					try
					{
						COption::SetOptionString('crm', '~CRM_INVOICE_INST_ORDER_DATA_18_5_0', 'Y');
						require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/crm/install/modules/data.php");
					}
					catch (Exception $e)
					{
						COption::SetOptionString('crm', '~CRM_INVOICE_INST_ORDER_DATA_18_5_0', 'N');
						$errMsg[] = $e->getMessage();
						$bError = true;
					}

					if ($bError)
					{
						$errString = implode('<br>', $errMsg);
						ShowError($errString);
						return false;
					}

					if ($catalogNormalizeStep !== 'Y')
					{
						if (self::setCatalogAdminRights() && self::createOfferIBlocks())
							Main\Config\Option::set('crm', $catalogNormalizeOption, 'Y', '');
					}
				}
			}

			return true;
		}

		if (COption::GetOptionString('crm', '~CRM_INVOICE_UF_MYCOMPANY_ID_16_2_1', 'N') === 'Y')
		{
			if (COption::GetOptionString('crm', '~CRM_INVOICE_DISABLE_SALE_EVENTS_16_5_10', 'N') !== 'Y')
			{
				// disable after the separation invoices and orders
				//self::installDisableSaleEvents();

				COption::SetOptionString('crm', '~CRM_INVOICE_DISABLE_SALE_EVENTS_16_5_10', 'Y');

				if (COption::GetOptionString('crm', '~CRM_INVOICE_INST_PROP_LOCATION_UA_17_0_9', 'N') !== 'Y')
				{
					self::installOrderPropertyLocationUa();

					COption::SetOptionString('crm', '~CRM_INVOICE_INST_PROP_LOCATION_UA_17_0_9', 'Y');

					if (COption::GetOptionString('crm', '~CRM_INVOICE_INST_ORDER_DATA_18_5_0', 'N') !== 'Y')
					{
						try
						{
							COption::SetOptionString('crm', '~CRM_INVOICE_INST_ORDER_DATA_18_5_0', 'Y');
							require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/crm/install/modules/data.php");
						}
						catch (Exception $e)
						{
							COption::SetOptionString('crm', '~CRM_INVOICE_INST_ORDER_DATA_18_5_0', 'N');
							$errMsg[] = $e->getMessage();
							$bError = true;
						}

						if ($bError)
						{
							$errString = implode('<br>', $errMsg);
							ShowError($errString);
							return false;
						}

						if ($catalogNormalizeStep !== 'Y')
						{
							if (self::setCatalogAdminRights() && self::createOfferIBlocks())
								Main\Config\Option::set('crm', $catalogNormalizeOption, 'Y', '');
						}
					}
				}
			}

			return true;
		}

		if (COption::GetOptionString('crm', '~CRM_INVOICE_EXCH1C_UPDATE_16_1_8', 'N') === 'Y')
		{
			if (COption::GetOptionString('crm', '~CRM_INVOICE_UF_MYCOMPANY_ID_16_2_1', 'N') !== 'Y')
			{
				$result = self::installOrderIntUserField('UF_MYCOMPANY_ID');
				if (!$result->isSuccess())
				{
					$errString = implode('<br>', $result->getErrorMessages());
					ShowError($errString);
					return false;
				}

				COption::SetOptionString('crm', '~CRM_INVOICE_UF_MYCOMPANY_ID_16_2_1', 'Y');

				if (COption::GetOptionString('crm', '~CRM_INVOICE_DISABLE_SALE_EVENTS_16_5_10', 'N') !== 'Y')
				{
					// disable after the separation invoices and orders
					//self::installDisableSaleEvents();

					COption::SetOptionString('crm', '~CRM_INVOICE_DISABLE_SALE_EVENTS_16_5_10', 'Y');

					if (COption::GetOptionString('crm', '~CRM_INVOICE_INST_PROP_LOCATION_UA_17_0_9', 'N') !== 'Y')
					{
						self::installOrderPropertyLocationUa();

						COption::SetOptionString('crm', '~CRM_INVOICE_INST_PROP_LOCATION_UA_17_0_9', 'Y');

						if (COption::GetOptionString('crm', '~CRM_INVOICE_INST_ORDER_DATA_18_5_0', 'N') !== 'Y')
						{
							try
							{
								COption::SetOptionString('crm', '~CRM_INVOICE_INST_ORDER_DATA_18_5_0', 'Y');
								require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/crm/install/modules/data.php");
							}
							catch (Exception $e)
							{
								COption::SetOptionString('crm', '~CRM_INVOICE_INST_ORDER_DATA_18_5_0', 'N');
								$errMsg[] = $e->getMessage();
								$bError = true;
							}

							if ($bError)
							{
								$errString = implode('<br>', $errMsg);
								ShowError($errString);
								return false;
							}

							if ($catalogNormalizeStep !== 'Y')
							{
								if (self::setCatalogAdminRights() && self::createOfferIBlocks())
									Main\Config\Option::set('crm', $catalogNormalizeOption, 'Y', '');
							}
						}
					}
				}
			}

			return true;
		}

		if (COption::GetOptionString('crm', '~CRM_SALE_STATUS_UPDATE_15_5_8', 'N') === 'Y')
		{
			if (COption::GetOptionString('crm', '~CRM_INVOICE_EXCH1C_UPDATE_16_1_8', 'N') !== 'Y')
			{
				if (CModule::IncludeModule('sale')
					&& COption::GetOptionString('crm', '~CRM_INVOICE_EXCH1C_UPDATE_12_5_17', 'N') === 'Y')
				{
					try
					{
						require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/crm/install/exch1c.php");
					}
					catch (Exception $e)
					{
						$errMsg[] = $e->getMessage();
						$bError = true;
					}

					if ($bError)
					{
						$errString = implode('<br>', $errMsg);
						ShowError($errString);
						return false;
					}

					COption::SetOptionString('crm', '~CRM_INVOICE_EXCH1C_UPDATE_16_1_8', 'Y');

					if (COption::GetOptionString('crm', '~CRM_INVOICE_UF_MYCOMPANY_ID_16_2_1', 'N') !== 'Y')
					{
						$result = self::installOrderIntUserField('UF_MYCOMPANY_ID');
						if (!$result->isSuccess())
						{
							$errString = implode('<br>', $result->getErrorMessages());
							ShowError($errString);
							return false;
						}

						COption::SetOptionString('crm', '~CRM_INVOICE_UF_MYCOMPANY_ID_16_2_1', 'Y');

						if (COption::GetOptionString('crm', '~CRM_INVOICE_DISABLE_SALE_EVENTS_16_5_10', 'N') !== 'Y')
						{
							// disable after the separation invoices and orders
							//self::installDisableSaleEvents();

							COption::SetOptionString('crm', '~CRM_INVOICE_DISABLE_SALE_EVENTS_16_5_10', 'Y');

							if (COption::GetOptionString('crm', '~CRM_INVOICE_INST_PROP_LOCATION_UA_17_0_9', 'N') !== 'Y')
							{
								self::installOrderPropertyLocationUa();

								COption::SetOptionString('crm', '~CRM_INVOICE_INST_PROP_LOCATION_UA_17_0_9', 'Y');

								if (COption::GetOptionString('crm', '~CRM_INVOICE_INST_ORDER_DATA_18_5_0', 'N') !== 'Y')
								{
									try
									{
										COption::SetOptionString('crm', '~CRM_INVOICE_INST_ORDER_DATA_18_5_0', 'Y');
										require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/crm/install/modules/data.php");
									}
									catch (Exception $e)
									{
										COption::SetOptionString('crm', '~CRM_INVOICE_INST_ORDER_DATA_18_5_0', 'N');
										$errMsg[] = $e->getMessage();
										$bError = true;
									}

									if ($bError)
									{
										$errString = implode('<br>', $errMsg);
										ShowError($errString);
										return false;
									}

									if ($catalogNormalizeStep !== 'Y')
									{
										if (self::setCatalogAdminRights() && self::createOfferIBlocks())
											Main\Config\Option::set('crm', $catalogNormalizeOption, 'Y', '');
									}
								}
							}
						}
					}
				}
			}

			return true;
		}

		if (COption::GetOptionString('crm', '~CRM_INVOICE_UF_QUOTE_ID_14_1_13', 'N') === 'Y')
		{
			\Bitrix\Main\Config\Option::set('sale', 'format_quantity', '4');

			$statusList = \CCrmStatus::GetStatusList('INVOICE_STATUS');
			$existStatuses = array_keys($statusList);

			$bNeutral = true;
			$neutralStatuses = array();
			$failedStatuses = array();
			foreach ($existStatuses as $id)
			{
				if ($id !== 'N' && $id !== 'P' && $id !== 'D')
				{
					if ($bNeutral)
						$neutralStatuses[] = $id;
					else
						$failedStatuses[] = $id;
				}
				else if ($id === 'P' || $id === 'D')
				{
					$bNeutral = false;
				}
			}

			$statuses = array();
			$sort = 100;
			foreach (array_merge(array('N'), $neutralStatuses, array('P', 'D'), $failedStatuses) as $id)
			{
				$statuses[$id] = $sort;
				$sort += 10;
			}
			unset($sort);

			$crmStatus = new CCrmStatus('INVOICE_STATUS');
			if (!empty($statuses))
			{
				$defaultStatuses = [];
				foreach (CCrmStatus::GetDefaultInvoiceStatuses() as $statusInfo)
				{
					$defaultStatuses[$statusInfo['STATUS_ID']] = $statusInfo;
				}
				unset($statusInfo);

				foreach ($statuses as $statusId => $statusSort)
				{
					if (!in_array($statusId, $existStatuses, true))
					{
						if(!isset($defaultStatuses[$statusId]))
						{
							continue;
						}

						$status = array(
							'STATUS_ID' => $statusId,
							'SORT' => $statusSort,
							'NAME' => $defaultStatuses[$statusId]['NAME'],
							'SEMANTICS' => $defaultStatuses[$statusId]['SEMANTICS'] ?? null,
							'COLOR' => $defaultStatuses[$statusId]['COLOR'] ?? null,
						);

						if ($statusId === 'N'
							|| $statusId === 'P'
							|| $statusId === 'D'
						)
						{
							$status['SYSTEM'] = 'Y';
							$status['NAME_INIT'] = $defaultStatuses[$statusId];
						}
						else
						{
							$status['SYSTEM'] = 'N';
						}

						$crmStatus->Add($status);
					}
					else
					{
						$data = $crmStatus->GetStatusByStatusId($statusId);
						$crmStatus->Update(
							$data['ID'],
							array('SORT' => $statusSort)
						);
					}
				}
				unset($statusLangFiles);
			}
			COption::SetOptionString('crm', '~CRM_SALE_STATUS_UPDATE_15_5_8', 'Y');

			if (COption::GetOptionString('crm', '~CRM_INVOICE_EXCH1C_UPDATE_16_1_8', 'N') !== 'Y')
			{
				if (CModule::IncludeModule('sale')
					&& COption::GetOptionString('crm', '~CRM_INVOICE_EXCH1C_UPDATE_12_5_17', 'N') === 'Y')
				{
					try
					{
						require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/crm/install/exch1c.php");
					}
					catch (Exception $e)
					{
						$errMsg[] = $e->getMessage();
						$bError = true;
					}

					if ($bError)
					{
						$errString = implode('<br>', $errMsg);
						ShowError($errString);
						return false;
					}

					COption::SetOptionString('crm', '~CRM_INVOICE_EXCH1C_UPDATE_16_1_8', 'Y');

					if (COption::GetOptionString('crm', '~CRM_INVOICE_UF_MYCOMPANY_ID_16_2_1', 'N') !== 'Y')
					{
						$result = self::installOrderIntUserField('UF_MYCOMPANY_ID');
						if (!$result->isSuccess())
						{
							$errString = implode('<br>', $result->getErrorMessages());
							ShowError($errString);
							return false;
						}

						COption::SetOptionString('crm', '~CRM_INVOICE_UF_MYCOMPANY_ID_16_2_1', 'Y');

						if (COption::GetOptionString('crm', '~CRM_INVOICE_DISABLE_SALE_EVENTS_16_5_10', 'N') !== 'Y')
						{
							// disable after the separation invoices and orders
							//self::installDisableSaleEvents();

							COption::SetOptionString('crm', '~CRM_INVOICE_DISABLE_SALE_EVENTS_16_5_10', 'Y');

							if (COption::GetOptionString('crm', '~CRM_INVOICE_INST_PROP_LOCATION_UA_17_0_9', 'N') !== 'Y')
							{
								self::installOrderPropertyLocationUa();

								COption::SetOptionString('crm', '~CRM_INVOICE_INST_PROP_LOCATION_UA_17_0_9', 'Y');

								if (COption::GetOptionString('crm', '~CRM_INVOICE_INST_ORDER_DATA_18_5_0', 'N') !== 'Y')
								{
									try
									{
										COption::SetOptionString('crm', '~CRM_INVOICE_INST_ORDER_DATA_18_5_0', 'Y');
										require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/crm/install/modules/data.php");
									}
									catch (Exception $e)
									{
										COption::SetOptionString('crm', '~CRM_INVOICE_INST_ORDER_DATA_18_5_0', 'N');
										$errMsg[] = $e->getMessage();
										$bError = true;
									}

									if ($bError)
									{
										$errString = implode('<br>', $errMsg);
										ShowError($errString);
										return false;
									}

								}
							}
						}
					}
				}
			}

			return true;
		}

		if (COption::GetOptionString('crm', '~CRM_EXCH1C_BASKET_XML_IDS_14_1_9', 'N') === 'Y')
		{
			$result = self::installOrderIntUserField('UF_QUOTE_ID');
			if (!$result->isSuccess())
			{
				$errString = implode('<br>', $result->getErrorMessages());
				ShowError($errString);
				return false;
			}
			COption::SetOptionString('crm', '~CRM_INVOICE_UF_QUOTE_ID_14_1_13', 'Y');
			LocalRedirect($GLOBALS['APPLICATION']->GetCurPageParam());
			return true;
		}

		if (COption::GetOptionString('crm', '~CRM_EXCH1C_REWRITEDEFCATGRP_12_5_20', 'N') === 'Y')
		{
			// update basket xml_id fields
			if($DB->TableExists('b_crm_invoice')
				&& $DB->TableExists('b_crm_invoice_basket')
				&& $DB->TableExists('b_iblock')
				&& $DB->TableExists('b_iblock_element'))
			{
				if($DB->Query("SELECT RESPONSIBLE_ID FROM b_crm_invoice WHERE 1=0", true)
					&& $DB->Query("SELECT CATALOG_XML_ID, PRODUCT_XML_ID FROM b_crm_invoice_basket WHERE 1=0", true)
					&& $DB->Query("SELECT XML_ID FROM b_iblock WHERE 1=0", true)
					&& $DB->Query("SELECT XML_ID FROM b_iblock_element WHERE 1=0", true))
				{
					$catalogId = 0;
					$tmpCatalogId = intval(COption::GetOptionString('crm', 'default_product_catalog_id', '0'));
					if ($dbRes = $DB->Query("SELECT ID FROM b_iblock I WHERE I.ID = $tmpCatalogId", true))
					{
						if ($arRes = $dbRes->Fetch())
						{
							if ($tmpCatalogId === intval($arRes['ID']))
								$catalogId = $tmpCatalogId;
						}
						unset($arRes);
					}
					unset($tmpCatalogId, $dbRes);
					if ($catalogId > 0)
					{
						$strSql =
							"UPDATE b_crm_invoice_basket B".PHP_EOL.
							"  INNER JOIN b_crm_invoice O ON B.ORDER_ID = O.ID".PHP_EOL.
							"  INNER JOIN b_iblock_element IE ON B.PRODUCT_ID = IE.ID".PHP_EOL.
							"  INNER JOIN b_iblock I ON IE.IBLOCK_ID = I.ID".PHP_EOL.
							"SET".PHP_EOL.
							"  B.CATALOG_XML_ID = I.XML_ID,".PHP_EOL.
							"  B.PRODUCT_XML_ID = IE.XML_ID".PHP_EOL.
							"WHERE".PHP_EOL.
							"  IE.IBLOCK_ID = $catalogId".PHP_EOL.
							"  AND (".PHP_EOL.
							"    B.PRODUCT_XML_ID IS NULL OR B.PRODUCT_XML_ID = ''".PHP_EOL.
							"    OR B.CATALOG_XML_ID IS NULL OR B.CATALOG_XML_ID = ''".PHP_EOL.
							"  )".PHP_EOL.
							"  AND O.RESPONSIBLE_ID IS NOT NULL";
						$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
						unset($strSql);
					}
				}
			}
			COption::SetOptionString('crm', '~CRM_EXCH1C_BASKET_XML_IDS_14_1_9', 'Y');

			$result = self::installOrderIntUserField('UF_QUOTE_ID');
			if (!$result->isSuccess())
			{
				$errString = implode('<br>', $result->getErrorMessages());
				ShowError($errString);
				return false;
			}
			COption::SetOptionString('crm', '~CRM_INVOICE_UF_QUOTE_ID_14_1_13', 'Y');
			LocalRedirect($GLOBALS['APPLICATION']->GetCurPageParam());
			return true;
		}

		if (COption::GetOptionString('crm', '~CRM_INVOICE_DISABLE_SALE_EVENTS_12_5_19', 'N') === 'Y')
		{
			if (!CModule::IncludeModule('catalog'))
				return false;
			$arBaseCatalogGroup = CCatalogGroup::GetBaseGroup();
			$priceTypeId = intval($arBaseCatalogGroup['ID']);
			COption::SetOptionInt('crm', 'selected_catalog_group_id', $priceTypeId);
			unset($arBaseCatalogGroup, $priceTypeId);
			COption::SetOptionString('crm', '~CRM_EXCH1C_REWRITEDEFCATGRP_12_5_20', 'Y');
			LocalRedirect($GLOBALS['APPLICATION']->GetCurPageParam());
			return true;
		}

		if (COption::GetOptionString('crm', '~CRM_INVOICE_EXCH1C_UPDATE_12_5_17', 'N') === 'Y')
		{
			$pref = COption::GetOptionString('sale', '1C_SALE_ACCOUNT_NUMBER_SHOP_PREFIX', '');
			if (mb_strlen(strval($pref)) < 1)
				COption::SetOptionString('sale', '1C_SALE_ACCOUNT_NUMBER_SHOP_PREFIX', 'CRM_');
			COption::SetOptionString('crm', '~CRM_INVOICE_EXCH1C_UPDATE_12_5_19', 'Y');
			// disable after the separation invoices and orders
			//self::installDisableSaleEvents();
			COption::SetOptionString('crm', '~CRM_INVOICE_DISABLE_SALE_EVENTS_12_5_19', 'Y');
			if (!CModule::IncludeModule('catalog'))
				return false;
			$arBaseCatalogGroup = CCatalogGroup::GetBaseGroup();
			$priceTypeId = intval($arBaseCatalogGroup['ID']);
			COption::SetOptionInt('crm', 'selected_catalog_group_id', $priceTypeId);
			unset($arBaseCatalogGroup, $priceTypeId);
			COption::SetOptionString('crm', '~CRM_EXCH1C_REWRITEDEFCATGRP_12_5_20', 'Y');

			LocalRedirect($GLOBALS['APPLICATION']->GetCurPageParam());
			return true;
		}

		if (COption::GetOptionString('crm', '~CRM_INVOICE_INSTALL_12_5_7', 'N') === 'Y')
		{
			// fix 40279
			if (COption::GetOptionString('crm', '~CRM_INVOICE_UPDATE_12_5_14', 'N') !== 'Y')
			{
				try
				{
					if (CModule::IncludeModule('sale'))
					{
						global $DB;

						if ($DB->TableExists('b_sale_order_props') && class_exists('CSaleOrderProps'))
						{
							$arPropsFilter = [
								'=TYPE' => 'LOCATION',
								'=REQUIRED' => 'Y',
								'=USER_PROPS' => 'Y',
								'=IS_LOCATION' => 'Y',
								'=IS_EMAIL' => 'N',
								'=IS_PROFILE_NAME' => 'N',
								'=IS_PAYER' => 'N',
								'=CODE' => 'LOCATION'
							];

							$dbRes = \Bitrix\Crm\Order\Property::getList([
								'select' => ['ID', 'IS_LOCATION4TAX', 'SORT'],
								'filter' => $arPropsFilter,
								'order' => ['SORT' => 'ASC', 'ID' => 'ASC']
							]);

							while ($arOrderProp = $dbRes->fetch())
							{
								if ($arOrderProp['IS_LOCATION4TAX'] !== 'Y')
								{
									\Bitrix\Sale\Internals\OrderPropsTable::update($arOrderProp['ID'], ['IS_LOCATION4TAX' => 'Y']);
								}
							}

							COption::SetOptionString('crm', '~CRM_INVOICE_UPDATE_12_5_14', 'Y');
						}
					}
				}
				catch(Exception $e)
				{}
			}
			if (COption::GetOptionString('crm', '~CRM_INVOICE_UPDATE_12_5_14', 'N') === 'Y')
			{
				if (COption::GetOptionString('crm', '~CRM_INVOICE_EXCH1C_UPDATE_12_5_17', 'N') !== 'Y')
				{
					if (CModule::IncludeModule('catalog') && CModule::IncludeModule('sale') && CModule::IncludeModule('iblock'))
					{
						try
						{
							require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/crm/install/exch1c.php");
						}
						catch(Exception $e)
						{
							$errMsg[] = $e->getMessage();
							$bError = true;
						}

						if (!$bError)
						{
							COption::SetOptionString('crm', '~CRM_INVOICE_EXCH1C_UPDATE_12_5_17', 'Y');
							COption::SetOptionString('sale', '1C_SALE_ACCOUNT_NUMBER_SHOP_PREFIX', 'CRM_');
							COption::SetOptionString('crm', '~CRM_INVOICE_EXCH1C_UPDATE_12_5_19', 'Y');
							// disable after the separation invoices and orders
							//self::installDisableSaleEvents();
							COption::SetOptionString('crm', '~CRM_INVOICE_DISABLE_SALE_EVENTS_12_5_19', 'Y');
							if (!CModule::IncludeModule('catalog'))
								return false;
							$arBaseCatalogGroup = CCatalogGroup::GetBaseGroup();
							$priceTypeId = intval($arBaseCatalogGroup['ID']);
							COption::SetOptionInt('crm', 'selected_catalog_group_id', $priceTypeId);
							unset($arBaseCatalogGroup, $priceTypeId);
							COption::SetOptionString('crm', '~CRM_EXCH1C_REWRITEDEFCATGRP_12_5_20', 'Y');
							LocalRedirect($GLOBALS['APPLICATION']->GetCurPageParam());
							return true;
						}
						else
						{
							$errString = implode('<br>', $errMsg);
							ShowError($errString);
							return false;
						}
					}
				}
				else
					return true;
			}
			return false;
		}

		if (COption::GetOptionString('crm', '~CRM_INVOICE_INSTALL_12_5_7', 'N') !== 'Y')
		{
			COption::SetOptionString('crm', '~CRM_INVOICE_INSTALL_12_5_7', 'Y');
			try
			{
				require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/crm/install/sale_link.php");
			}
			catch(Exception $e)
			{
				$errMsg[] = $e->getMessage();
				$bError = true;
			}

			if (!$bError)
			{
				LocalRedirect($GLOBALS['APPLICATION']->GetCurPageParam());
				return true;
			}
			else
			{
				$errString = implode('<br>', $errMsg);
				ShowError($errString);
				COption::SetOptionString('crm', '~CRM_INVOICE_INSTALL_12_5_7', 'N');
				return false;
			}
		}

		return false;
	}

	private static function installOrderPropertyLocationUa()    // Fix 0081306.
	{
		// localization
		$languageId = '';
		if (IsModuleInstalled('bitrix24')
			&& CModule::IncludeModule('bitrix24')
			&& method_exists('CBitrix24', 'getLicensePrefix'))
		{
			$languageId = CBitrix24::getLicensePrefix();
		}
		if ($languageId == '')
		{
			$siteIterator = \Bitrix\Main\SiteTable::getList(array(
				'select' => array('LID', 'LANGUAGE_ID'),
				'filter' => array('=DEF' => 'Y', '=ACTIVE' => 'Y')
			));
			if ($site = $siteIterator->fetch())
				$languageId = (string)$site['LANGUAGE_ID'];
			unset($site, $siteIterator);
		}
		if ($languageId !== 'ua')
			return;

		$shopLocalization = $languageId;

		// site id
		$currentSiteID = SITE_ID;
		if (defined("ADMIN_SECTION"))
		{
			$siteIterator = Bitrix\Main\SiteTable::getList(array(
				'select' => array('LID', 'LANGUAGE_ID'),
				'filter' => array('=DEF' => 'Y', '=ACTIVE' => 'Y')
			));
			if ($defaultSite = $siteIterator->fetch())
			{
				$currentSiteID = $defaultSite['LID'];
			}
			unset($defaultSite, $siteIterator);
		}

		// person type
		$companyPTID  = 0;
		$dbPerson = \Bitrix\Crm\Invoice\PersonType::getList([
			'filter' => [
				"=PERSON_TYPE_SITE.SITE_ID" => $currentSiteID,
				"=CODE" => 'CRM_COMPANY'
			]
		]);
		while($arPerson = $dbPerson->fetch())
		{
			if($arPerson["CODE"] == 'CRM_COMPANY')
				$companyPTID = (int)$arPerson["ID"];
		}
		if ($companyPTID <= 0)
			return;

		// property group id
		$propGroupId = 0;
		$dbSaleOrderPropsGroup = CSaleOrderPropsGroup::GetList(
			array(),
			array(
				"PERSON_TYPE_ID" => $companyPTID,
				"NAME" => Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_GROUP_UR2")
			),
			false, false, array("ID")
		);
		if ($arSaleOrderPropsGroup = $dbSaleOrderPropsGroup->GetNext())
			$propGroupId = (int)$arSaleOrderPropsGroup["ID"];
		if ($propGroupId <= 0)
			return;

		$arProps = array();
		$arProps[] = array(
			"PERSON_TYPE_ID" => $companyPTID,
			"NAME" => Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_2"),
			"TYPE" => "LOCATION",
			"REQUIRED" => "Y",
			"DEFAULT_VALUE" => "",
			"SORT" => ($shopLocalization == "ua") ? 185 : 290,
			"USER_PROPS" => "Y",
			"IS_LOCATION" => "Y",
			"PROPS_GROUP_ID" => $propGroupId,
			"DESCRIPTION" => "",
			"IS_EMAIL" => "N",
			"IS_PROFILE_NAME" => "N",
			"IS_PAYER" => "N",
			"IS_LOCATION4TAX" => "Y",
			"CODE" => "LOCATION",
			"IS_FILTERED" => "N",
			"ENTITY_REGISTRY_TYPE" => REGISTRY_TYPE_CRM_INVOICE
		);
		foreach($arProps as $prop)
		{
			$dbRes = \Bitrix\Crm\Invoice\Property::getList([
				'select' => ['ID'],
				'filter' => [
					"PERSON_TYPE_ID" => $prop["PERSON_TYPE_ID"],
					"CODE" =>  $prop["CODE"]
				]
			]);
			if (!$dbRes->fetch())
			{
				\Bitrix\Sale\Internals\OrderPropsTable::add($prop);
			}
		}
	}

	private static function installOrderIntUserField($fieldName)
	{
		$result = new Bitrix\Main\Result();

		$bFieldExists = false;
		$obUserField  = new CUserTypeEntity;
		$dbRes = $obUserField->GetList(array('SORT' => 'DESC'), array('ENTITY_ID' => 'CRM_INVOICE'));
		$maxUFSort = 0;
		$i = 0;
		while ($arUF = $dbRes->Fetch())
		{
			if ($i++ === 0)
				$maxUFSort = intval($arUF['SORT']);
			if ($arUF['FIELD_NAME'] === $fieldName)
			{
				$bFieldExists = true;
				break;
			}
		}
		unset($dbRes, $arUF, $i);
		if (!$bFieldExists)
		{
			$arOrderUserField = array(
				'ENTITY_ID' => 'CRM_INVOICE',
				'FIELD_NAME' => $fieldName,
				'USER_TYPE_ID' => 'integer',
				'XML_ID' => mb_strtolower($fieldName),
				'SORT' => strval($maxUFSort + 10),
				'MULTIPLE' => null,
				'MANDATORY' => null,
				'SHOW_FILTER' => 'N',
				'SHOW_IN_LIST' => 'N',
				'EDIT_IN_LIST' => 'N',
				'IS_SEARCHABLE' => null,
				'SETTINGS' => array(
					'DEFAULT_VALUE' => null,
					'SIZE' => '',
					'ROWS' => '1',
					'MIN_LENGTH' => '0',
					'MAX_LENGTH' => '0',
					'REGEXP' => ''
				),
				'EDIT_FORM_LABEL' => array('ru' => '', 'en' => ''),
				'LIST_COLUMN_LABEL' => array('ru' => '', 'en' => ''),
				'LIST_FILTER_LABEL' => array('ru' => '', 'en' => ''),
				'ERROR_MESSAGE' => array('ru' => '', 'en' => ''),
				'HELP_MESSAGE' => array('ru' => '', 'en' => '')
			);
			$userFieldId = $obUserField->Add($arOrderUserField);
			if ($userFieldId <= 0)
			{
				$result->addError(
					new Bitrix\Main\Error(
						GetMessage(
							'CRM_CANT_ADD_USER_FIELD1',
							[
								"#FIELD_NAME#" => $arOrderUserField['FIELD_NAME'],
								"#ENTITY_TYPE#" => CCrmInvoice::GetUserFieldEntityID()
							]
						)
					)
				);
			}
		}

		return $result;
	}

	/**
	 * Update catalog rights for admin (fixed old catalogs without group 1 rights).
	 *
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private static function setCatalogAdminRights()
	{
		if (!Loader::includeModule('iblock'))
			return false;
		if (!Loader::includeModule('catalog'))
			return true;

		$groupId = 1; // admin group

		$catalogs = [];
		$iterator = Catalog\CatalogIblockTable::getList([
			'select' => ['IBLOCK_ID', 'PRODUCT_IBLOCK_ID']
		]);
		while ($row = $iterator->fetch())
		{
			$row['IBLOCK_ID'] = (int)$row['IBLOCK_ID'];
			$catalogs[$row['IBLOCK_ID']] = $row['IBLOCK_ID'];
			$row['PRODUCT_IBLOCK_ID'] = (int)$row['PRODUCT_IBLOCK_ID'];
			if ($row['PRODUCT_IBLOCK_ID'] > 0)
				$catalogs[$row['PRODUCT_IBLOCK_ID']] = $row['PRODUCT_IBLOCK_ID'];
		}
		unset($row, $iterator);

		if (!empty($catalogs))
		{
			$iblockObject = new \CIBlock();

			$rightsId = null;
			$row = Main\TaskTable::getList(array(
				'select' => array('ID'),
				'filter' => array('=LETTER' => 'X', '=MODULE_ID' => 'iblock', '=SYS' => 'Y')
			))->fetch();
			if (!empty($row))
				$rightsId = $row['ID'];
			unset($row);
			$groupCode = 'G'.$groupId;

			foreach ($catalogs as $id)
			{
				$rightsMode = \CIBlock::GetArrayByID($id, 'RIGHTS_MODE');
				if ($rightsMode == Iblock\IblockTable::RIGHTS_SIMPLE)
				{
					$rights = \CIBlock::GetGroupPermissions($id);
					$rights[$groupId] = 'X';
					\CIBlock::SetPermission($id, $rights);
				}
				elseif ($rightsMode == Iblock\IblockTable::RIGHTS_EXTENDED && $rightsId !== null)
				{
					$rightsObject = new \CIBlockRights($id);
					$rights = $rightsObject->GetRights();
					$rights['n0'] = array(
						'GROUP_CODE'  => $groupCode,
						'DO_INHERIT' => 'Y',
						'IS_INHERITED' => 'N',
						'OVERWRITED' => 0,
						'TASK_ID' => $rightsId,
						'XML_ID' => null,
						'ENTITY_TYPE' => 'iblock',
						'ENTITY_ID' => $id
					);
					$rightsObject->SetRights($rights);
				}
			}
			unset($rights, $id, $groupCode);
			unset($iblockObject);
		}
		unset($catalogs);
		unset($groupId);

		return true;
	}

	/**
	 * Create offers iblock for old crm catalogs.
	 *
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private static function createOfferIBlocks()
	{
		if (!Loader::includeModule('iblock'))
			return false;
		$quota = new \CDiskQuota();
		if(!$quota->checkDiskQuota([]))
			return false;
		if (!Loader::includeModule('catalog'))
			return true;

		$simpleCatalogs = [];
		$parentIblocks = [];
		$iterator = Catalog\CatalogIblockTable::getList([
			'select' => ['IBLOCK_ID', 'PRODUCT_IBLOCK_ID']
		]);
		while ($row = $iterator->fetch())
		{
			$row['IBLOCK_ID'] = (int)$row['IBLOCK_ID'];
			$row['PRODUCT_IBLOCK_ID'] = (int)$row['PRODUCT_IBLOCK_ID'];
			if ($row['PRODUCT_IBLOCK_ID'] > 0)
			{
				$parentIblocks[$row['PRODUCT_IBLOCK_ID']] = $row['PRODUCT_IBLOCK_ID'];
				continue;
			}
			$simpleCatalogs[$row['IBLOCK_ID']] = $row['IBLOCK_ID'];
		}
		unset($row, $iterator);

		foreach ($parentIblocks as $id)
		{
			if (isset($simpleCatalogs[$id]))
				unset($simpleCatalogs[$id]);
		}
		unset($id, $parentIblocks);

		if (empty($simpleCatalogs))
			return true;

		$iblockList = [];
		$iterator = Iblock\IblockTable::getList([
			'select' => ['ID', 'NAME', 'XML_ID', 'IBLOCK_TYPE_ID', 'SORT'],
			'filter' => ['@ID' => $simpleCatalogs],
			'order' => ['ID' => 'ASC']
		]);
		while ($row = $iterator->fetch())
		{
			$id = (int)$row['ID'];
			$row['LID'] = [];
			$iblockList[$id] = $row;
		}
		unset($row, $iterator);
		unset($simpleCatalogs);

		if (empty($iblockList))
			return true;

		$iterator = Iblock\IblockSiteTable::getList([
			'select' => ['IBLOCK_ID', 'SITE_ID'],
			'filter' => ['@IBLOCK_ID' => array_keys($iblockList)]
		]);
		while ($row = $iterator->fetch())
		{
			$id = (int)$row['IBLOCK_ID'];
			$iblockList[$id]['LID'][] = $row['SITE_ID'];
		}
		unset($row, $iterator);

		// get default vat
		$iterator = Catalog\VatTable::getList([
			'select' => ['ID', 'SORT'],
			'order' => ['SORT' => 'ASC'],
			'limit' => 1
		]);
		$vat = $iterator->fetch();
		unset($iterator);

		foreach ($iblockList as $id => $data)
		{
			$iblock = new \CIBlock();

			$fields = [
				'NAME' => Loc::getMessage('CRM_CATALOG_OFFERS_TITLE_FORMAT', ['#CATALOG#' => $data['NAME']]),
				'ACTIVE' => 'Y',
				'IBLOCK_TYPE_ID' => $data['IBLOCK_TYPE_ID'],
				'LID' => $data['LID'],
				'SORT' => $data['SORT'] + 10,
				'INDEX_ELEMENT' => 'N',
				'WORKFLOW' => 'N',
				'BIZPROC' => 'N',
				'VERSION' => 1,
				'GROUP_ID' => array(1 => 'X', 2 => 'R'),
				'LIST_MODE' => 'S'
			];
			$xmlId = $data['XML_ID'];
			if (strncmp($xmlId, 'crm_external_', 13) == 0)
				$xmlId = str_replace('crm_external_', 'crm_external_offers_', $xmlId);
			elseif ($xmlId = 'FUTURE-1C-CATALOG')
				$xmlId = 'FUTURE-1C-CATALOG-OFFERS';
			else
				$xmlId .= '_offers';
			$fields['XML_ID'] = $xmlId;
			unset($xmlId);

			$offersId = $iblock->Add($fields);
			if ($offersId === false)
				return false;
			$propertyId = \CIBlockPropertyTools::createProperty(
				$offersId,
				\CIBlockPropertyTools::CODE_SKU_LINK,
				['LINK_IBLOCK_ID' => $id]
			);
			if (!$propertyId)
				return false;
			$offersFields = [
				'IBLOCK_ID' => $offersId,
				'PRODUCT_IBLOCK_ID' => $id,
				'SKU_PROPERTY_ID' => $propertyId,
			];
			if (!empty($vat))
				$offersFields['VAT_ID'] = (int)$vat['ID'];

			if (!\CCatalog::Add($offersFields))
				return false;
		}
		unset($id, $data);
		unset($iblockList);

		return true;
	}

	public static function installDisableSaleEvents()
	{
		$fmodule = new CModule();
		if($module = $fmodule->CreateModuleObject("sale"))
			$module->UnInstallEvents();
	}

	public static function GetCounterValue()
	{
		$result = 0;

		global $USER;
		$userId = is_object($USER) ? intval($USER->GetID()) : 0;
		if ($userId > 0)
		{
			$arNeutralStatuses = self::GetNeutralStatusIds();
			if (!is_array($arNeutralStatuses) || count($arNeutralStatuses) === 0)
				return $result;

			$filter = array(
				"RESPONSIBLE_ID" => $userId,
				"<=DATE_PAY_BEFORE" => FormatDate('FULL', strtotime(date('Y-m-d').' 23:59:59')),
				"STATUS_ID" => $arNeutralStatuses
			);
			if ($dbRes = CCrmInvoice::GetList(array(), $filter, false, false, array("ID", "STATUS_ID", "DATE_PAY_BEFORE")))
			{
				$cnt = 0;
				while ($arResult = $dbRes->Fetch())
				//{
					//if (isset($arResult['STATUS_ID']) && CCrmStatusInvoice::isStatusNeutral($arResult['STATUS_ID']))
						$cnt++;
				//}
				$result = $cnt;
			}
		}

		return $result;
	}

	public static function GetPaidSum($filter, $currencyId = '')
	{
		$totalPaidNumber = 0;
		$totalPaidSum = 0;

		if ($currencyId == '')
			$currencyId = CCrmCurrency::GetBaseCurrencyID();

		$filter['CHECK_PERMISSIONS'] = 'N';
		$statusSemanticInfo = CCrmStatus::GetInvoiceStatusSemanticInfo();
		$filter['STATUS_ID'] = $statusSemanticInfo['FINAL_SUCCESS_FIELD'];
		$dbRes = CCrmInvoice::GetList(array('ID' => 'ASC'), $filter, false, false, array('PRICE', 'CURRENCY', 'STATUS_ID'));
		while ($arValues = $dbRes->Fetch())
		{
			$totalPaidNumber++;
			$totalPaidSum += CCrmCurrency::ConvertMoney($arValues['PRICE'], $arValues['CURRENCY'], $currencyId);
		}

		$result = array(
			'num' => $totalPaidNumber,
			'sum' => round($totalPaidSum, 2)
		);

		return $result;
	}

	public static function GetCompanyPersonTypeID()
	{
		$all = self::GetPersonTypeIDs();
		return isset($all['COMPANY']) ? $all['COMPANY'] : '0';
	}

	public static function GetContactPersonTypeID()
	{
		$all = self::GetPersonTypeIDs();
		return isset($all['CONTACT']) ? $all['CONTACT'] : '0';
	}

	public static function GetPersonTypeIDs()
	{
		if(self::$INVOICE_PAY_SYSTEM_TYPES === null)
		{
			self::$INVOICE_PAY_SYSTEM_TYPES = CCrmPaySystem::getPersonTypeIDs();
		}
		return self::$INVOICE_PAY_SYSTEM_TYPES;
	}

	public static function ResolvePersonTypeID($companyID, $contactID)
	{
		$companyID = intval($companyID);
		$contactID = intval($contactID);

		$all = self::GetPersonTypeIDs();
		if($companyID > 0 && isset($all['COMPANY']))
		{
			return $all['COMPANY'];
		}
		elseif($contactID > 0 && isset($all['CONTACT']))
		{
			return $all['CONTACT'];
		}
		return 0;
	}

	public static function ResolveLocationName($ID, $fields = null)
	{
		if(!(is_array($fields) && !empty($fields)))
		{
			$ID = intval($ID);
			if($ID <= 0)
			{
				return '';
			}

			if(!CModule::IncludeModule('sale'))
			{
				return $ID;
			}
			$dbLocations = CSaleLocation::GetList(
				array(),
				array('ID' => $ID, 'LID' => LANGUAGE_ID),
				false,
				false,
				array('ID', 'CITY_ID', 'CITY_NAME', 'COUNTRY_NAME_LANG', 'REGION_NAME_LANG')
			);

			$fields = $dbLocations->Fetch();
			if(!is_array($fields))
			{
				return $ID;
			}
		}

		$name = isset($fields['CITY_NAME']) ? $fields['CITY_NAME'] : '';
		if(isset($fields['REGION_NAME_LANG']))
		{
			if($name !== '')
			{
				$name .= ', ';
			}
			$name .= $fields['REGION_NAME_LANG'];
		}
		if(isset($fields['COUNTRY_NAME_LANG']))
		{
			if($name !== '')
			{
				$name .= ', ';
			}
			$name .= $fields['COUNTRY_NAME_LANG'];
		}

		return $name;
	}

	private static function OnCreate()
	{
	}
	private static function RegisterLiveFeedEvent(&$arFields, $invoiceID, $userID)
	{
		$invoiceID = intval($invoiceID);
		if($invoiceID <= 0)
		{
			$arFields['ERROR'] = 'Could not find invoice invoice ID.';
			return false;
		}

		$userID = intval($userID);
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		// Params are not assigned - we will use current invoice only.
		$liveFeeedFields = array(
			'ENTITY_TYPE_ID' => CCrmOwnerType::Invoice,
			'ENTITY_ID' => $invoiceID,
			'USER_ID' => $userID,
			'MESSAGE' => '',
			'TITLE' => ''
			//'PARAMS' => array()
		);

		$dealID = isset($arFields['UF_DEAL_ID']) ? intval($arFields['UF_DEAL_ID']) : 0;
		$companyID = isset($arFields['UF_COMPANY_ID']) ? intval($arFields['UF_COMPANY_ID']) : 0;
		$contactID = isset($arFields['UF_CONTACT_ID']) ? intval($arFields['UF_CONTACT_ID']) : 0;
		$responsibleID = isset($arFields['RESPONSIBLE_ID']) ? intval($arFields['RESPONSIBLE_ID']) : 0;

		$parents = array();

		if($dealID > 0)
		{
			CCrmLiveFeed::PrepareOwnershipRelations(CCrmOwnerType::Deal, array($dealID), $parents);
		}

		if($companyID > 0)
		{
			CCrmLiveFeed::PrepareOwnershipRelations(CCrmOwnerType::Company, array($companyID), $parents);
		}

		if($contactID > 0)
		{
			CCrmLiveFeed::PrepareOwnershipRelations(CCrmOwnerType::Contact, array($contactID), $parents);
		}

		if(!empty($parents))
		{
			$liveFeeedFields['PARENTS'] = array_values($parents);
		}

		$eventID = CCrmLiveFeed::CreateLogEvent($liveFeeedFields, CCrmLiveFeedEvent::Add);
		if ($eventID === false)
		{
			if (isset($liveFeeedFields['ERROR']))
			{
				$arFields['ERROR'] = $liveFeeedFields['ERROR'];
			}
		}
		elseif (
			$responsibleID > 0
			&& $responsibleID !== $userID
			&& IsModuleInstalled('im')
			&& CModule::IncludeModule('im')
		)
		{
			$url = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Invoice, $invoiceID);
			$topic = $arFields['ORDER_TOPIC'] ?? $invoiceID;

			CIMNotify::Add(
				array(
					'MESSAGE_TYPE' => IM_MESSAGE_SYSTEM,
					'FROM_USER_ID' => $userID,
					'NOTIFY_TYPE' => IM_NOTIFY_FROM,
					'NOTIFY_MODULE' => 'crm',
					'LOG_ID' => $eventID,
					//'NOTIFY_EVENT' => 'invoice_responsible_changed',
					'NOTIFY_EVENT' => 'changeAssignedBy',
					'NOTIFY_TAG' => "CRM|INVOICE|{$invoiceID}",
					'TO_USER_ID' => $responsibleID,
					'NOTIFY_MESSAGE' => GetMessage('CRM_INVOICE_RESPONSIBLE_IM_NOTIFY', array('#title#' => '<a href="'.htmlspecialcharsbx($url).'">'.htmlspecialcharsbx($topic).'</a>')),
					'NOTIFY_MESSAGE_OUT' => GetMessage('CRM_INVOICE_RESPONSIBLE_IM_NOTIFY', array('#title#' => htmlspecialcharsbx($topic)))." (".CCrmUrlUtil::ToAbsoluteUrl($url).")"
				)
			);
		}

		return $eventID;
	}
	private static function SynchronizeLiveFeedEvent($invoiceID, $params)
	{
		$invoiceID = intval($invoiceID);
		if($invoiceID <= 0)
		{
			return;
		}

		if(!is_array($params))
		{
			$params = array();
		}

		$processParents = isset($params['PROCESS_PARENTS']) ? (bool)$params['PROCESS_PARENTS'] : false;
		$parents = isset($params['PARENTS']) && is_array($params['PARENTS']) ? $params['PARENTS'] : array();
		$hasParents = !empty($parents);

		if($processParents)
		{
			CCrmSonetRelation::UnRegisterRelationsByEntity(CCrmOwnerType::Invoice, $invoiceID, array('QUICK' => $hasParents));
		}

		$userID = CCrmSecurityHelper::GetCurrentUserID();
		$startResponsibleID = isset($params['START_RESPONSIBLE_ID']) ? intval($params['START_RESPONSIBLE_ID']) : 0;
		$finalResponsibleID = isset($params['FINAL_RESPONSIBLE_ID']) ? intval($params['FINAL_RESPONSIBLE_ID']) : 0;
		$enableMessages = $startResponsibleID !== $finalResponsibleID
			&& IsModuleInstalled('im') && CModule::IncludeModule('im');
		$topic = isset($params['TOPIC']) ? $params['TOPIC'] : $invoiceID;

		$slEntities = CCrmLiveFeed::GetLogEvents(
			array(),
			array(
				'ENTITY_TYPE_ID' => CCrmOwnerType::Invoice,
				'ENTITY_ID' => $invoiceID
			),
			array('ID', 'EVENT_ID')
		);

		foreach($slEntities as &$slEntity)
		{
			$slID = intval($slEntity['ID']);
			$slEventType = $slEntity['EVENT_ID'];

			if(isset($params['REFRESH_DATE']) ? (bool)$params['REFRESH_DATE'] : false)
			{
				//Update LOG_UPDATE for force event to rise in global feed
				//Update LOG_DATE for force event to rise in entity feed
				global $DB;
				CCrmLiveFeed::UpdateLogEvent(
					$slID,
					array(
						'=LOG_UPDATE' => $DB->CurrentTimeFunction(),
						'=LOG_DATE' => $DB->CurrentTimeFunction()
					)
				);
			}
			else
			{
				//HACK: FAKE UPDATE FOR INVALIDATE CACHE
				CCrmLiveFeed::UpdateLogEvent(
					$slID,
					array(
						'ENTITY_TYPE_ID' => CCrmOwnerType::Invoice,
						'ENTITY_ID' => $invoiceID,
					)
				);
			}

			if ($processParents && $hasParents && Settings\Crm::isLiveFeedRecordsGenerationEnabled())
			{
				CCrmSonetRelation::RegisterRelationBundle(
					$slID,
					$slEventType,
					CCrmOwnerType::Invoice,
					$invoiceID,
					$parents,
					array('TYPE_ID' => CCrmSonetRelationType::Ownership)
				);
			}

			if($enableMessages)
			{
				$messageFields = array(
					'MESSAGE_TYPE' => IM_MESSAGE_SYSTEM,
					'FROM_USER_ID' => $userID,
					'NOTIFY_TYPE' => IM_NOTIFY_FROM,
					'NOTIFY_MODULE' => 'crm',
					'LOG_ID' => $slID,
					//'NOTIFY_EVENT' => 'invoice_responsible_changed',
					'NOTIFY_EVENT' => 'changeAssignedBy',
					'NOTIFY_TAG' => "CRM|INVOICE|{$invoiceID}"
				);

				$url = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Invoice, $invoiceID);

				if($startResponsibleID > 0 && $startResponsibleID !== $userID)
				{
					$messageFields['TO_USER_ID'] = $startResponsibleID;
					$messageFields['NOTIFY_MESSAGE'] = GetMessage('CRM_INVOICE_NOT_RESPONSIBLE_IM_NOTIFY', array('#title#' => '<a href="'.htmlspecialcharsbx($url).'">'.htmlspecialcharsbx($topic).'</a>'));
					$messageFields['NOTIFY_MESSAGE_OUT'] = GetMessage('CRM_INVOICE_NOT_RESPONSIBLE_IM_NOTIFY', array('#title#' => htmlspecialcharsbx($topic)))." (".CCrmUrlUtil::ToAbsoluteUrl($url).")";

					CIMNotify::Add($messageFields);
				}

				if($finalResponsibleID > 0 && $finalResponsibleID !== $userID)
				{
					$messageFields['TO_USER_ID'] = $finalResponsibleID;
					$messageFields['NOTIFY_MESSAGE'] = GetMessage('CRM_INVOICE_RESPONSIBLE_IM_NOTIFY', array('#title#' => '<a href="'.htmlspecialcharsbx($url).'">'.htmlspecialcharsbx($topic).'</a>'));
					$messageFields['NOTIFY_MESSAGE_OUT'] = GetMessage('CRM_INVOICE_RESPONSIBLE_IM_NOTIFY', array('#title#' => htmlspecialcharsbx($topic)))." (".CCrmUrlUtil::ToAbsoluteUrl($url).")";

					CIMNotify::Add($messageFields);
				}
			}
		}
		unset($slEntity);
	}
	private static function UnregisterLiveFeedEvent($invoiceID)
	{
		$invoiceID = intval($invoiceID);
		if($invoiceID <= 0)
		{
			return;
		}

		$slEntities = CCrmLiveFeed::GetLogEvents(
			array(),
			array(
				'ENTITY_TYPE_ID' => CCrmOwnerType::Invoice,
				'ENTITY_ID' => $invoiceID
			),
			array('ID')
		);

		$options = array('UNREGISTER_RELATION' => false);
		foreach($slEntities as &$slEntity)
		{
			CCrmLiveFeed::DeleteLogEvent($slEntity['ID'], $options);
		}
		unset($slEntity);
		CCrmSonetRelation::UnRegisterRelationsByEntity(CCrmOwnerType::Invoice, $invoiceID);
	}

	public static function BuildSearchCard($arInvoice, $bReindex = false)
	{
		$arStatuses = array();
		$arSite = array();
		$sEntityType = 'INVOICE';
		$sTitle = 'ORDER_TOPIC';
		$sNumber = 'ACCOUNT_NUMBER';
		$arSearchableFields = array(
			/*'ACCOUNT_NUMBER' => GetMessage('CRM_INVOICE_SEARCH_FIELD_ACCOUNT_NUMBER'),*/
			/*'ORDER_TOPIC' => GetMessage('CRM_INVOICE_SEARCH_FIELD_ORDER_TOPIC'),*/
			'STATUS_ID' => GetMessage('CRM_INVOICE_SEARCH_FIELD_STATUS_ID'),
			'DATE_BILL' => GetMessage('CRM_INVOICE_SEARCH_FIELD_DATE_BILL'),
			'DATE_PAY_BEFORE' => GetMessage('CRM_INVOICE_SEARCH_FIELD_DATE_PAY_BEFORE'),
			'PRICE' => GetMessage('CRM_INVOICE_SEARCH_FIELD_PRICE'),
			'PAY_VOUCHER_NUM' => GetMessage('CRM_INVOICE_SEARCH_FIELD_PAY_VOUCHER_NUM'),
			'USER_DESCRIPTION' => GetMessage('CRM_INVOICE_SEARCH_FIELD_USER_DESCRIPTION'),
			'COMMENTS' => GetMessage('CRM_INVOICE_SEARCH_FIELD_COMMENTS'),
			'REASON_MARKED' => GetMessage('CRM_INVOICE_SEARCH_FIELD_REASON_MARKED')
		);

		$sBody = $arInvoice[$sNumber].', '.$arInvoice[$sTitle]."\n";
		$arField2status = array(
			'STATUS_ID' => 'INVOICE_STATUS'
		);
		$site = new CSite();

		foreach (array_keys($arSearchableFields) as $k)
		{
			if (!isset($arInvoice[$k]))
				continue;

			$v = $arInvoice[$k];

			if($k === 'COMMENTS' || $k === 'USER_DESCRIPTION')
			{
				$v = CSearch::KillTags($v);
			}

			$v = trim($v);

			if ($k === 'DATE_BILL' || $k === 'DATE_PAY_BEFORE')
			{
				$dateFormatShort = $site->GetDateFormat('SHORT');
				if (!CheckDateTime($v, $dateFormatShort))
				{
					$v = ConvertTimeStamp(strtotime($v), 'SHORT');
				}
				if (CheckDateTime($v, $dateFormatShort))
				{
					$v = FormatDate('SHORT', MakeTimeStamp($v, $dateFormatShort));
				}
				else
				{
					$v = null;
				}
			}

			if (isset($arField2status[$k]))
			{
				if (!isset($arStatuses[$k]))
					$arStatuses[$k] = CCrmStatus::GetStatusList($arField2status[$k]);
				$v = $arStatuses[$k][$v];
			}

			if (!empty($v) && (!is_numeric($v) || $k === 'PRICE') && $v != 'N' && $v != 'Y')
				$sBody .= $arSearchableFields[$k].": $v\n";
		}

		if ((isset($arInvoice['RESPONSIBLE_NAME']) && !empty($arInvoice['RESPONSIBLE_NAME']))
			|| (isset($arInvoice['RESPONSIBLE_LAST_NAME']) && !empty($arInvoice['RESPONSIBLE_LAST_NAME']))
			|| (isset($arInvoice['RESPONSIBLE_SECOND_NAME']) && !empty($arInvoice['RESPONSIBLE_SECOND_NAME'])))
		{
			$responsibleInfo = CUser::FormatName(
				$site->GetNameFormat(null, $arInvoice['LID']),
				array(
					'LOGIN' => '',
					'NAME' => isset($arInvoice['RESPONSIBLE_NAME']) ? $arInvoice['RESPONSIBLE_NAME'] : '',
					'LAST_NAME' => isset($arInvoice['RESPONSIBLE_LAST_NAME']) ? $arInvoice['RESPONSIBLE_LAST_NAME'] : '',
					'SECOND_NAME' => isset($arInvoice['RESPONSIBLE_SECOND_NAME']) ? $arInvoice['RESPONSIBLE_SECOND_NAME'] : ''
				),
				false, false
			);
			if (isset($arInvoice['RESPONSIBLE_EMAIL']) && !empty($arInvoice['RESPONSIBLE_EMAIL']))
				$responsibleInfo .= ', '.$arInvoice['RESPONSIBLE_EMAIL'];
			if (isset($arInvoice['RESPONSIBLE_WORK_POSITION']) && !empty($arInvoice['RESPONSIBLE_WORK_POSITION']))
				$responsibleInfo .= ', '.$arInvoice['RESPONSIBLE_WORK_POSITION'];
			if (!empty($responsibleInfo) && !is_numeric($responsibleInfo) && $responsibleInfo != 'N' && $responsibleInfo != 'Y')
				$sBody .= GetMessage('CRM_INVOICE_SEARCH_FIELD_RESPONSIBLE_INFO').": $responsibleInfo\n";
		}

		if (intval($arInvoice['PERSON_TYPE_ID']) > 0)
		{
			$arSearchableProperties = self::_getAllowedPropertiesInfo($arInvoice['LID']);
			$arSearchableProperties = $arSearchableProperties[$arInvoice['PERSON_TYPE_ID']];
			if (is_array($arSearchableProperties))
			{
				$arInvoiceProps = self::GetProperties($arInvoice['ID'], $arInvoice['PERSON_TYPE_ID']);
				foreach ($arInvoiceProps as $prop)
				{
					$propCode = $prop['FIELDS']['CODE'];
					if (array_key_exists($propCode, $arSearchableProperties))
					{
						$v = $prop['VALUE'];
						if (!empty($v) && !is_numeric($v) && $v != 'N' && $v != 'Y')
							$sBody .= $arSearchableProperties[$propCode].": $v\n";
					}
				}
			}
		}

		$sDetailURL = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_'.mb_strtolower($sEntityType).'_show'),
			array(
				mb_strtolower($sEntityType).'_id' => $arInvoice['ID']
			)
		);

		$_arAttr = \Bitrix\Crm\Security\Manager::resolveController($sEntityType)
			->getPermissionAttributes($sEntityType, [$arInvoice['ID']])
		;

		if (empty($arSite))
		{
			$rsSite = $site->GetList();
			while ($_arSite = $rsSite->Fetch())
				$arSite[] = $_arSite['ID'];
		}
		unset($site);

		$sattr_d = '';
		$sattr_s = '';
		$sattr_u = '';
		$sattr_o = '';
		$sattr2 = '';
		$arAttr = array();
		if (!isset($_arAttr[$arInvoice['ID']]))
			$_arAttr[$arInvoice['ID']] = array();

		$arAttr[] = $sEntityType; // for perm X
		foreach ($_arAttr[$arInvoice['ID']] as $_s)
		{
			if ($_s[0] == 'U')
				$sattr_u = $_s;
			else if ($_s[0] == 'D')
				$sattr_d = $_s;
			else if ($_s[0] == 'S')
				$sattr_s = $_s;
			else if ($_s[0] == 'O')
				$sattr_o = $_s;
			$arAttr[] = $sEntityType.'_'.$_s;
		}
		$sattr = $sEntityType.'_'.$sattr_u;
		if (!empty($sattr_d))
		{
			$sattr .= '_'.$sattr_d;
			$arAttr[] = $sattr;
		}
		if (!empty($sattr_s))
		{
			$sattr2 = $sattr.'_'.$sattr_s;
			$arAttr[] = $sattr2;
			$arAttr[] = $sEntityType.'_'.$sattr_s;  // for perm X in status
		}
		if (!empty($sattr_o))
		{
			$sattr  .= '_'.$sattr_o;
			$sattr3 = $sattr2.'_'.$sattr_o;
			$arAttr[] = $sattr3;
			$arAttr[] = $sattr;
		}

		$arSitePath = array();
		foreach ($arSite as $sSite)
			$arSitePath[$sSite] = $sDetailURL;

		$arResult = Array(
			'LAST_MODIFIED' => $arInvoice['DATE_UPDATE'],
			'DATE_FROM' => $arInvoice['DATE_INSERT'],
			'TITLE' => GetMessage('CRM_'.$sEntityType).': '.$arInvoice[$sNumber].', '.$arInvoice[$sTitle],
			'PARAM1' => $sEntityType,
			'PARAM2' => $arInvoice['ID'],
			'SITE_ID' => $arSitePath,
			'PERMISSIONS' => $arAttr,
			'BODY' => $sBody,
			'TAGS' => 'crm,'.mb_strtolower($sEntityType).','.GetMessage('CRM_'.$sEntityType)
		);

		if ($bReindex)
			$arResult['ID'] = $sEntityType.'.'.$arInvoice['ID'];

		return $arResult;
	}

	public static function ProductRows2BasketItems($arProductRows, $srcCurrencyID = '', $dstCurrencyID = '')
	{
		$basketItems = array();

		$srcCurrencyID = strval($srcCurrencyID);
		$dstCurrencyID = strval($dstCurrencyID);
		if ($srcCurrencyID == '' || $dstCurrencyID == '')
			$srcCurrencyID = $dstCurrencyID = '';

		foreach ($arProductRows as $row)
		{
			$freshRow = array();
			$freshRow['ID'] = isset($row['ID']) ? intval($row['ID']) : 0;
			$freshRow['PRODUCT_ID'] = isset($row['PRODUCT_ID']) ? intval($row['PRODUCT_ID']) : 0;
			$freshRow['PRODUCT_NAME'] = isset($row['PRODUCT_NAME']) ? strval($row['PRODUCT_NAME']) : '';
			$freshRow['QUANTITY'] = isset($row['QUANTITY']) ? round(doubleval($row['QUANTITY']), 4) : 0.0;

			$freshRow['PRICE'] = isset($row['PRICE']) ? round(doubleval($row['PRICE']), 2) : 0.0;
			$freshRow['VAT_INCLUDED'] = isset($row['TAX_INCLUDED']) && $row['TAX_INCLUDED'] === 'Y' ? 'Y' : 'N';

			$taxRate = isset($row['TAX_RATE']) ? round(doubleval($row['TAX_RATE']), 2) : 0.0;
			$inclusivePrice = isset($row['PRICE']) ? round(doubleval($row['PRICE']), 2) : 0.0;
			$exclusivePrice = round(
				isset($row['PRICE_EXCLUSIVE'])
					? doubleval($row['PRICE_EXCLUSIVE'])
					: CCrmProductRow::CalculateExclusivePrice($inclusivePrice, $taxRate),
				2
			);
			$discountSum = isset($row['DISCOUNT_SUM']) ? round(doubleval($row['DISCOUNT_SUM']), 2) : 0.0;

			$price = $freshRow['VAT_INCLUDED'] === 'Y' ? $inclusivePrice : $exclusivePrice;
			if ($dstCurrencyID != $srcCurrencyID)
			{
				$price = CCrmCurrency::ConvertMoney($freshRow['PRICE'], $srcCurrencyID, $dstCurrencyID);
				$inclusivePrice = CCrmCurrency::ConvertMoney($inclusivePrice, $srcCurrencyID, $dstCurrencyID);
				$exclusivePrice = CCrmCurrency::ConvertMoney($exclusivePrice, $srcCurrencyID, $dstCurrencyID);
				$discountSum = CCrmCurrency::ConvertMoney($discountSum, $srcCurrencyID, $dstCurrencyID);
			}
			$freshRow['PRICE'] = $price;
			$freshRow['VAT_RATE'] = $taxRate / 100;

			$discountTypeID = isset($row['DISCOUNT_TYPE_ID']) ? intval($row['DISCOUNT_TYPE_ID']) : \Bitrix\Crm\Discount::UNDEFINED;
			if ($discountTypeID !== \Bitrix\Crm\Discount::PERCENTAGE && $discountTypeID !== \Bitrix\Crm\Discount::MONETARY)
				$discountTypeID = \Bitrix\Crm\Discount::PERCENTAGE;
			if ($discountTypeID === \Bitrix\Crm\Discount::PERCENTAGE)
			{
				$discountRate = isset($row['DISCOUNT_RATE']) ? round(doubleval($row['DISCOUNT_RATE']), 2) : 0.0;
				if ($discountRate < 100.00)
					$freshRow['DISCOUNT_PRICE'] = round(\Bitrix\Crm\Discount::calculateDiscountSum($exclusivePrice, $discountRate), 2);
				else
					$freshRow['DISCOUNT_PRICE'] = $discountSum;
			}
			else
			{
				$freshRow['DISCOUNT_PRICE'] = $discountSum;
			}
			$freshRow['MEASURE_CODE'] = isset($row['MEASURE_CODE']) ? intval($row['MEASURE_CODE']) : 0;
			$freshRow['MEASURE_NAME'] = isset($row['MEASURE_NAME']) ? strval($row['MEASURE_NAME']) : '';
			$freshRow['CUSTOMIZED'] = isset($row['CUSTOMIZED']) ? ($row['CUSTOMIZED'] === 'Y' ? 'Y' : 'N') : 'Y';
			$freshRow['SORT'] = isset($row['SORT']) ? intval($row['SORT']) : 0;

			$basketItems[] = $freshRow;
		}

		return $basketItems;
	}

	public static function EnsureStatusesLoaded()
	{
		if (self::$INVOICE_STATUSES === null)
		{
			self::$INVOICE_STATUSES = CCrmStatus::GetStatus('INVOICE_STATUS');
		}
	}

	public static function GetFinalStatusSort()
	{
		return self::GetStatusSort('P');
	}

	public static function GetStatusSort($statusID)
	{
		$statusID = strval($statusID);
		if($statusID === '')
		{
			return -1;
		}

		self::EnsureStatusesLoaded();
		$info = isset(self::$INVOICE_STATUSES[$statusID]) ? self::$INVOICE_STATUSES[$statusID] : null;
		return is_array($info) && isset($info['SORT']) ? (int)($info['SORT']) : -1;
	}

	public static function GetSemanticID($statusID)
	{
		if($statusID === 'P')
		{
			return Bitrix\Crm\PhaseSemantics::SUCCESS;
		}

		if($statusID === 'D')
		{
			return Bitrix\Crm\PhaseSemantics::FAILURE;
		}

		return (self::GetStatusSort($statusID) > self::GetFinalStatusSort())
			? Bitrix\Crm\PhaseSemantics::FAILURE : Bitrix\Crm\PhaseSemantics::PROCESS;
	}

	public static function RebuildStatistics(array $IDs, array $options = null)
	{
		$dbResult = self::GetList(
			array(),
			array('@ID' => $IDs, 'CHECK_PERMISSIONS' => 'N')
		);

		if(!is_object($dbResult))
		{
			return;
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$forced = isset($options['FORCED']) ? $options['FORCED'] : false;
		$enableHistory = isset($options['ENABLE_HISTORY']) ? $options['ENABLE_HISTORY'] : true;
		$enableSumStatistics = isset($options['ENABLE_SUM_STATISTICS']) ? $options['ENABLE_SUM_STATISTICS'] : true;

		while($fields = $dbResult->Fetch())
		{
			$ID = (int)$fields['ID'];
			//--> History
			if($enableHistory && ($forced || !Bitrix\Crm\History\InvoiceStatusHistoryEntry::isRegistered($ID)))
			{
				$created = isset($fields['DATE_INSERT']) ? $fields['DATE_INSERT'] : '';
				$createdTime = null;
				try
				{
					$createdTime = new Bitrix\Main\Type\DateTime(
						$created,
						Bitrix\Main\Type\DateTime::convertFormatToPhp(FORMAT_DATETIME));
				}
				catch(Bitrix\Main\ObjectException $e)
				{
				}

				$modified = isset($fields['DATE_UPDATE']) ? $fields['DATE_UPDATE'] : '';
				$modifiedTime = null;
				if($modified !== '')
				{
					try
					{
						$modifiedTime = new Bitrix\Main\Type\DateTime(
							$modified,
							Bitrix\Main\Type\DateTime::convertFormatToPhp(FORMAT_DATETIME));
					}
					catch(Bitrix\Main\ObjectException $e)
					{
					}
				}

				if($createdTime && $modifiedTime && $createdTime->getTimestamp() !== $modifiedTime->getTimestamp())
				{
					Bitrix\Crm\History\InvoiceStatusHistoryEntry::register(
						$ID,
						$fields,
						array('IS_NEW' => false, 'TIME' => $modifiedTime)
					);
				}
				elseif($createdTime)
				{
					Bitrix\Crm\History\InvoiceStatusHistoryEntry::register(
						$ID,
						$fields,
						array('IS_NEW' => true, 'TIME' => $createdTime)
					);
				}
			}
			//<-- History

			//--> Statistics
			if($enableSumStatistics && ($forced || !Bitrix\Crm\Statistics\InvoiceSumStatisticEntry::isRegistered($ID)))
			{
				Bitrix\Crm\Statistics\InvoiceSumStatisticEntry::register($ID, $fields, array('FORCED' => $forced));
			}
			//<-- Statistics
		}
	}

	public static function PrepareFilter(&$arFilter, $arFilter2Logic = null)
	{
		//shameless copy-paste from the crm.invoice.list component
		global $USER_FIELD_MANAGER;

		if(!is_array($arFilter))
			return;

		if(!is_array($arFilter2Logic))
		{
			$arFilter2Logic = array();
		}

		$arUserFields = $USER_FIELD_MANAGER->GetUserFields(CCrmInvoice::$sUFEntityID);
		foreach($arUserFields as $fieldName => $arUserField)
		{
			if (isset($arUserField['USER_TYPE_ID']) && $arUserField['USER_TYPE_ID'] === 'enumeration')
			{
				if (is_array($arFilter[$fieldName]) && !empty($arFilter[$fieldName]))
					$arFilter[$fieldName] = array_values($arFilter[$fieldName]);
			}
		}

		$USER_FIELD_MANAGER->AdminListAddFilter(CCrmInvoice::$sUFEntityID, $arFilter);

		// converts data from filter
		if(isset($arFilter['FIND']))
		{
			if($arFilter['FIND'] !== '')
			{
				$arFilter['%ORDER_TOPIC'] = trim($arFilter['FIND']);
			}
			unset($arFilter['FIND']);
		}

		foreach ($arFilter as $k => $v)
		{
			$arMatch = array();

			if (preg_match('/(.*)_from$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
			{
				if($v <> '')
				{
					$arFilter['>='.$arMatch[1]] = $v;
				}
				unset($arFilter[$k]);
			}
			elseif (preg_match('/(.*)_to$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
			{
				if($v <> '')
				{
					if (($arMatch[1] == 'DATE_PAY_BEFORE' || $arMatch[1] == 'DATE_INSERT') && !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/'.BX_UTF_PCRE_MODIFIER, $v))
					{
						$v = CCrmDateTimeHelper::SetMaxDayTime($v);
					}
					$arFilter['<='.$arMatch[1]] = $v;
				}
				unset($arFilter[$k]);
			}
			elseif ($k === 'ORDER_TOPIC')
			{
				$arFilter['~ORDER_TOPIC'] = "%$v%";
				unset($arFilter['ORDER_TOPIC']);
			}
			elseif ($k === 'ACCOUNT_NUMBER')
			{
				$arFilter['~ACCOUNT_NUMBER'] = "%$v%";
				unset($arFilter['ACCOUNT_NUMBER']);
			}
			elseif ($k === 'ENTITIES_LINKS')
			{
				$v = Bitrix\Main\Web\Json::decode($v);
				if(count($v) > 0)
				{
					foreach ($v as $entityType => $entityValues)
					{
						foreach ($entityValues as $value)
						{
							$arFilter['UF_'.$entityType.'_ID'][] = $value;
						}
					}
				}
				unset($arFilter[$k]);
			}
			elseif (in_array($k, $arFilter2Logic))
			{
				// Bugfix #26956 - skip empty values in logical filter
				$v = trim($v);
				if($v !== '')
				{
					$arFilter['%'.$k] = $v;
				}
				unset($arFilter[$k]);
			}
		}
	}

	public static function GetPropertyValues($invoiceId, $personTypeId)
	{
		$invoiceId = (int)$invoiceId;
		$personTypeId = (int)$personTypeId;
		$result = [];

		if (!Bitrix\Main\Loader::includeModule('sale'))
		{
			return $result;
		}

		$query = new Bitrix\Main\Entity\Query(Bitrix\Crm\Invoice\Internals\InvoicePropsValueTable::getEntity());
		$query->registerRuntimeField(
			'',
			new Bitrix\Main\Entity\ReferenceField('LOCATION',
				Bitrix\Sale\Location\LocationTable::getEntity(),
				[
					'=this.PROPERTY.TYPE' => ['?', 'LOCATION'],
					'=this.VALUE' => 'ref.CODE'
				],
				['join_type' => 'LEFT']
			)
		);
		$query->setSelect(
			[
				'ID',
				'CODE',
				'ORDER_PROPS_ID',
				'VALUE',
				'PROPERTY_TYPE' => 'PROPERTY.TYPE',
				'LOCATION_ID' => 'LOCATION.ID'
			]
		);
		$query->setFilter(['=ORDER_ID' => $invoiceId]);
		$res = $query->exec();
		$propertyIds = [];
		$propertyValues = [];
		while ($row = $res->fetch())
		{
			if ($row['PROPERTY_TYPE'] === 'LOCATION')
			{
				$row['VALUE'] = $row['LOCATION_ID'];
			}
			unset($row['PROPERTY_TYPE'], $row['LOCATION_ID']);
			$propertyValues[] = $row;
			$propertyIds[] = $row['ORDER_PROPS_ID'];
		}

		$dbRes = \Bitrix\Crm\Invoice\Property::getList([
			'select' => ['ID', 'TYPE'],
			'filter' => ['ID' => $propertyIds],
		]);
		$propertyTypes = [];
		while ($row = $dbRes->Fetch())
		{
			$row = CSaleOrderPropsAdapter::convertNewToOld($row);
			$propertyTypes[$row['ID']] = $row['TYPE'];
		}
		$orderProps = new CSaleOrderProps();
		$allowedPropertiesInfo = CCrmInvoice::_getAllowedPropertiesInfo();
		foreach ($propertyValues as $propertyValue)
		{
			if (isset($propertyTypes[$propertyValue['ORDER_PROPS_ID']]))
			{
				$curOrderProps = $orderProps->GetRealValue(
					$propertyValue['ORDER_PROPS_ID'],
					$propertyValue['CODE'],
					$propertyTypes[$propertyValue['ORDER_PROPS_ID']],
					$propertyValue['VALUE'],
					LANGUAGE_ID
				);

				foreach ($curOrderProps as $key => $value)
				{
					if (isset($allowedPropertiesInfo[$personTypeId][$key]))
						$result[$key] = $value;
				}
			}
		}

		return $result;
	}

	public static function PrepareSalePaymentData(array &$arOrder, $options = array())
	{
		$ID = isset($arOrder['ID']) ? intval($arOrder['ID']) : 0;
		if($ID <= 0)
		{
			return null;
		}

		$isPublicLinkMode = (is_array($options) && isset($options['PUBLIC_LINK_MODE'])
			&& $options['PUBLIC_LINK_MODE'] === 'Y');

		$requisiteValues = array();
		$requisiteUserFieldsInfo = null;

		// requisite identifiers
		$requisiteId = 0;
		$bankDetailId = 0;
		$mcRequisiteId = 0;
		$mcBankDetailId = 0;
		if ($row = \Bitrix\Crm\Requisite\EntityLink::getList(
			array(
				'filter' => array(
					'=ENTITY_TYPE_ID' => CCrmOwnerType::Invoice,
					'=ENTITY_ID' => $ID
				),
				'select' => array('REQUISITE_ID', 'BANK_DETAIL_ID', 'MC_REQUISITE_ID', 'MC_BANK_DETAIL_ID'),
				'limit' => 1
			)
		)->fetch())
		{
			if (isset($row['REQUISITE_ID']) && $row['REQUISITE_ID'] > 0)
				$requisiteId = (int)$row['REQUISITE_ID'];
			if (isset($row['BANK_DETAIL_ID']) && $row['BANK_DETAIL_ID'] > 0)
				$bankDetailId = (int)$row['BANK_DETAIL_ID'];
			if (isset($row['MC_REQUISITE_ID']) && $row['MC_REQUISITE_ID'] > 0)
				$mcRequisiteId = (int)$row['MC_REQUISITE_ID'];
			if (isset($row['MC_BANK_DETAIL_ID']) && $row['MC_BANK_DETAIL_ID'] > 0)
				$mcBankDetailId = (int)$row['MC_BANK_DETAIL_ID'];
		}

		if (!isset($arOrder['UF_MYCOMPANY_ID']) || $arOrder['UF_MYCOMPANY_ID'] <= 0)
		{
			$defLink = Bitrix\Crm\Requisite\EntityLink::getDefaultMyCompanyRequisiteLink();
			if (is_array($defLink))
			{
				$arOrder['UF_MYCOMPANY_ID'] = isset($defLink['MYCOMPANY_ID']) ? (int)$defLink['MYCOMPANY_ID'] : 0;
				$mcRequisiteId = isset($defLink['MC_REQUISITE_ID']) ? (int)$defLink['MC_REQUISITE_ID'] : 0;
				$mcBankDetailId = isset($defLink['MC_BANK_DETAIL_ID']) ? (int)$defLink['MC_BANK_DETAIL_ID'] : 0;
			}
			unset($defLink);
		}

		// requisite values
		$requisiteValues = array();
		$presetCountryId = 0;
		if ($requisiteId > 0)
		{
			$requisite = new \Bitrix\Crm\EntityRequisite();
			$preset = new \Bitrix\Crm\EntityPreset();
			if ($requisiteUserFieldsInfo === null)
				$requisiteUserFieldsInfo = $requisite->getFormUserFieldsInfo();
			$row = $requisite->getList(
				array('select' => array('*', 'UF_*'), 'filter' => array('=ID' => $requisiteId), 'limit' => 1)
			)->fetch();
			if (is_array($row))
			{
				if (isset($row['PRESET_ID']) && $row['PRESET_ID'] > 0)
				{
					$presetFields = array();
					$res = $preset->getList(array(
						'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
						'filter' => array(
							'=ENTITY_TYPE_ID' => \Bitrix\Crm\EntityPreset::Requisite,
							'=ID' => (int)$row['PRESET_ID']
						),
						'select' => array('ID', 'COUNTRY_ID', 'SETTINGS'),
						'limit' => 1
					));
					if ($presetData = $res->fetch())
					{
						if (is_array($presetData['SETTINGS']))
						{
							$presetFieldsInfo = $preset->settingsGetFields($presetData['SETTINGS']);
							foreach ($presetFieldsInfo as $fieldInfo)
							{
								if (isset($fieldInfo['FIELD_NAME']))
									$presetFields[$fieldInfo['FIELD_NAME']] = true;
							}
							unset($presetFieldsInfo, $fieldInfo);
						}

						$presetCountryId = (int)$presetData['COUNTRY_ID'];
					}
					unset($res, $presetData);

					if ($presetCountryId > 0)
					{
						foreach ($row as $fieldName => $fieldValue)
						{
							if (isset($presetFields[$fieldName]))
							{
								if (is_array($requisiteUserFieldsInfo[$fieldName])
									&& $requisiteUserFieldsInfo[$fieldName]['type'] === 'boolean')
								{
									$requisiteValues[$fieldName.'|'.$presetCountryId] = $fieldValue ?
										GetMessage('MAIN_YES') : GetMessage('MAIN_NO');
								}
								elseif ($requisite->isRqListField($fieldName))
								{
									$requisiteValues[$fieldName.'|'.$presetCountryId] =
										$requisite->getRqListFieldValueTitle(
											$fieldName,
											$presetCountryId,
											$fieldValue
										)
									;
								}
								else
								{
									$requisiteValues[$fieldName.'|'.$presetCountryId] = $fieldValue;
								}
							}
						}
						unset($fieldName, $fieldValue, $valueKey);

						// addresses
						foreach ($requisite->getAddresses($requisiteId) as $addrTypeId => $addrFields)
						{
							$valueKey = Bitrix\Crm\EntityRequisite::ADDRESS.'_'.$addrTypeId.'|'.$presetCountryId;
							$addressLines = explode(
								"\n",
								str_replace(
									["\r\n", "\n", "\r"], "\n",
									AddressFormatter::getSingleInstance()->formatTextMultiline($addrFields)
								)
							);
							$requisiteValues[$valueKey] = is_array($addressLines) ? $addressLines : [];
							unset($valueKey, $addressLines);
						}
					}
				}
			}
		}

		// full name
		if ($presetCountryId > 0)
		{
			$fullName = isset($requisiteValues['RQ_NAME|'.$presetCountryId]) ?
				trim(strval($requisiteValues['RQ_NAME|'.$presetCountryId])) : '';
			if (empty($fullName))
			{
				$firstName = isset($requisiteValues['RQ_FIRST_NAME|'.$presetCountryId])
					? trim(strval($requisiteValues['RQ_FIRST_NAME|'.$presetCountryId])) : '';
				$lastName = isset($requisiteValues['RQ_LAST_NAME|'.$presetCountryId]) ?
					trim(strval($requisiteValues['RQ_LAST_NAME|'.$presetCountryId])) : '';
				$secondName = isset($requisiteValues['RQ_SECOND_NAME|'.$presetCountryId]) ?
					trim(strval($requisiteValues['RQ_SECOND_NAME|'.$presetCountryId])) : '';
				if (!empty($firstName) || !empty($lastName) || !empty($secondName))
				{
					$fullName = CUser::FormatName(
						\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
						array(
							'LOGIN' => '[]',
							'NAME' => $firstName,
							'LAST_NAME' => $lastName,
							'SECOND_NAME' => $secondName
						),
						true, false
					);
					if (!empty($fullName) && $fullName !== '[]')
					{
						$requisiteValues['RQ_NAME|'.$presetCountryId] = $fullName;
					}
				}
			}
		}

		// bank detail values
		$bankDetailValues = array();
		if ($bankDetailId > 0)
		{
			$bankDetail = new \Bitrix\Crm\EntityBankDetail();
			$row = $bankDetail->getById($bankDetailId);
			if (is_array($row))
			{
				$countryId = isset($row['COUNTRY_ID']) ? (int)$row['COUNTRY_ID'] : 0;
				if ($countryId > 0)
				{
					foreach ($row as $fieldName => $fieldValue)
					{
						$bankDetailValues[$fieldName.'|'.$countryId] = $fieldValue;
					}
					unset($fieldName, $fieldValue, $valueKey);
				}
			}
		}

		// company values
		$companyValues = array();
		$companyId = isset($arOrder['UF_COMPANY_ID']) ? intval($arOrder['UF_COMPANY_ID']) : 0;
		if ($companyId > 0)
		{
			$filter = array('ID' => $companyId);
			if ($isPublicLinkMode)
				$filter['CHECK_PERMISSIONS'] = 'N';
			$res = CCrmCompany::GetListEx(
				array(),
				$filter,
				false,
				array('nTopCount' => 1),
				array('ID', 'TITLE')
			);
			$row = $res->Fetch();
			if (is_array($row))
			{
				$companyValues = $row;
				$res = CCrmFieldMulti::GetList(
					array('ID' => 'asc'),
					array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $companyId)
				);
				$skip = array();
				while($row = $res->Fetch())
				{
					if (($row['TYPE_ID'] === 'PHONE' || $row['TYPE_ID'] === 'EMAIL')
						&& !isset($skip[$row['COMPLEX_ID']]))
					{
						$companyValues[$row['COMPLEX_ID']] = $row['VALUE'];
						$skip[$row['COMPLEX_ID']] = true;
					}
				}
			}
		}

		// contact values
		$contactValues = array();
		$contactId = isset($arOrder['UF_CONTACT_ID']) ? intval($arOrder['UF_CONTACT_ID']) : 0;
		if ($contactId > 0)
		{
			$filter = array('ID' => $contactId);
			if ($isPublicLinkMode)
				$filter['CHECK_PERMISSIONS'] = 'N';
			$res = CCrmContact::GetListEx(
				array(),
				$filter,
				false,
				array('nTopCount' => 1),
				array('ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'HONORIFIC')
			);
			$row = $res->Fetch();
			if (is_array($row))
			{
				$contactValues['ID'] = $row['ID'];
				$contactValues['FULL_NAME'] = CCrmContact::PrepareFormattedName($row);
				$res = CCrmFieldMulti::GetList(
					array('ID' => 'asc'),
					array('ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => $contactId)
				);
				$skip = array();
				while($row = $res->Fetch())
				{
					if (($row['TYPE_ID'] === 'PHONE' || $row['TYPE_ID'] === 'EMAIL')
						&& !isset($skip[$row['COMPLEX_ID']]))
					{
						$contactValues[$row['COMPLEX_ID']] = $row['VALUE'];
						$skip[$row['COMPLEX_ID']] = true;
					}
				}
			}
		}

		// backward compatibility
		$countryIds = array();
		if ($presetCountryId > 0)
			$countryIds[] = $presetCountryId;
		else
			$countryIds = \Bitrix\Crm\EntityRequisite::getAllowedRqFieldCountries();
		$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
		if ($arPersonTypes['COMPANY'] != "" && $arPersonTypes['CONTACT'] != "")
		{
			$personTypeCompany = $arPersonTypes['COMPANY'];
			$personTypeContact = $arPersonTypes['CONTACT'];

			$personTypeId = $personTypeContact;
			$personTypeCode = 'CONTACT';
			if ($companyId > 0)
			{
				$personTypeId = $personTypeCompany;
				$personTypeCode = 'COMPANY';
			}
			$requisiteConverted = (COption::GetOptionString('crm', '~CRM_TRANSFER_REQUISITES_TO_'.$personTypeCode, 'N') !== 'Y');
			if ($requisiteConverted)
			{
				$invoicePropertyValues = self::GetPropertyValues($ID, $personTypeId);

				$propsToRequisiteMap = array(
					$personTypeCompany => array(
						'COMPANY_NAME' => 'RQ_COMPANY_NAME',
						'COMPANY' => 'RQ_COMPANY_NAME',
						'COMPANY_ADR' => 'RQ_ADDR_'.EntityAddressType::Registered,
						'INN' => 'RQ_INN',
						'KPP' => 'RQ_KPP',
						'CONTACT_PERSON' => 'RQ_CONTACT',
						'EMAIL' => 'RQ_EMAIL',
						'PHONE' => 'RQ_PHONE'
					),
					$personTypeContact => array(
						'FIO' => 'RQ_NAME',
						'EMAIL' => 'RQ_EMAIL',
						'PHONE' => 'RQ_PHONE',
						'ADDRESS' => 'RQ_ADDR_'.EntityAddressType::Primary,
					),
				);

				if (is_array($invoicePropertyValues) && !empty($invoicePropertyValues))
				{
					foreach ($countryIds as $countryId)
					{
						foreach ($propsToRequisiteMap[$personTypeId] as $propertyCode => $rqIndex)
						{
							$rqIndex .= '|'.$countryId;
							if (isset($invoicePropertyValues[$propertyCode])
								&& !empty($invoicePropertyValues[$propertyCode])
								&& !isset($requisiteValues[$rqIndex]))
							{
								$requisiteValues[$rqIndex] = $invoicePropertyValues[$propertyCode];
							}
						}
					}
				}
			}
		}

		// company full name
		$companyFullName = '';
		if ($presetCountryId > 0)
		{
			$companyFullName = isset($requisiteValues['RQ_COMPANY_FULL_NAME|'.$presetCountryId]) ?
				trim(strval($requisiteValues['RQ_COMPANY_FULL_NAME|'.$presetCountryId])) : '';

			if (empty($companyFullName))
			{
				$companyShortName = isset($requisiteValues['RQ_COMPANY_NAME|'.$presetCountryId]) ?
					trim(strval($requisiteValues['RQ_COMPANY_NAME|'.$presetCountryId])) : '';

				if (!empty($companyShortName))
				{
					$companyFullName = $companyShortName;
				}
			}
		}
		if (empty($companyFullName))
		{
			$companyName = isset($companyValues['TITLE']) ? trim(strval($companyValues['TITLE'])) : '';

			if (!empty($companyName))
				$companyFullName = $companyName;
		}
		if (!empty($companyFullName))
		{
			foreach ($countryIds as $countryId)
				$requisiteValues['RQ_COMPANY_FULL_NAME|'.$countryId] = $companyFullName;
		}
		unset($companyFullName, $companyShortName, $companyName);

		// my company requisite values
		$mcRequisiteValues = array();
		$mcPresetCountryId = 0;
		if ($mcRequisiteId > 0)
		{
			$requisite = new \Bitrix\Crm\EntityRequisite();
			$preset = new \Bitrix\Crm\EntityPreset();
			if ($requisiteUserFieldsInfo === null)
				$requisiteUserFieldsInfo = $requisite->getFormUserFieldsInfo();
			$row = $requisite->getList(
				array('select' => array('*', 'UF_*'), 'filter' => array('=ID' => $mcRequisiteId), 'limit' => 1)
			)->fetch();
			if (is_array($row))
			{
				if (isset($row['PRESET_ID']) && $row['PRESET_ID'] > 0)
				{
					$presetFields = array();
					$res = $preset->getList(array(
						'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
						'filter' => array(
							'=ENTITY_TYPE_ID' => \Bitrix\Crm\EntityPreset::Requisite,
							'=ID' => (int)$row['PRESET_ID']
						),
						'select' => array('ID', 'COUNTRY_ID', 'SETTINGS'),
						'limit' => 1
					));
					if ($presetData = $res->fetch())
					{
						if (is_array($presetData['SETTINGS']))
						{
							$presetFieldsInfo = $preset->settingsGetFields($presetData['SETTINGS']);
							foreach ($presetFieldsInfo as $fieldInfo)
							{
								if (isset($fieldInfo['FIELD_NAME']))
									$presetFields[$fieldInfo['FIELD_NAME']] = true;
							}
							unset($presetFieldsInfo, $fieldInfo);
						}

						$mcPresetCountryId = (int)$presetData['COUNTRY_ID'];
					}
					unset($res, $presetData);

					if ($mcPresetCountryId > 0)
					{
						foreach ($row as $fieldName => $fieldValue)
						{
							if (isset($presetFields[$fieldName]))
							{
								if (is_array($requisiteUserFieldsInfo[$fieldName])
									&& $requisiteUserFieldsInfo[$fieldName]['type'] === 'boolean')
								{
									$mcRequisiteValues[$fieldName.'|'.$mcPresetCountryId] = $fieldValue ?
										GetMessage('MAIN_YES') : GetMessage('MAIN_NO');
								}
								elseif ($requisite->isRqListField($fieldName))
								{
									$mcRequisiteValues[$fieldName.'|'.$mcPresetCountryId] =
										$requisite->getRqListFieldValueTitle(
											$fieldName,
											$mcPresetCountryId,
											$fieldValue
										)
									;
								}
								else
								{
									$mcRequisiteValues[$fieldName.'|'.$mcPresetCountryId] = $fieldValue;
								}
							}
						}
						unset($fieldName, $fieldValue, $valueKey);

						// addresses
						foreach ($requisite->getAddresses($mcRequisiteId) as $addrTypeId => $addrFields)
						{
							$valueKey = Bitrix\Crm\EntityRequisite::ADDRESS.'_'.$addrTypeId.'|'.$mcPresetCountryId;
							$addressLines = explode(
								"\n",
								str_replace(
									["\r\n", "\n", "\r"], "\n",
									AddressFormatter::getSingleInstance()->formatTextMultiline($addrFields)
								)
							);
							$mcRequisiteValues[$valueKey] = is_array($addressLines) ? $addressLines : [];
							unset($valueKey, $addressLines);
						}
					}
				}
			}
		}

		// full name
		if ($mcPresetCountryId > 0)
		{
			$fullName = isset($mcRequisiteValues['RQ_NAME|'.$mcPresetCountryId]) ?
				trim(strval($mcRequisiteValues['RQ_NAME|'.$mcPresetCountryId])) : '';
			if (empty($fullName))
			{
				$firstName = isset($mcRequisiteValues['RQ_FIRST_NAME|'.$mcPresetCountryId])
					? trim(strval($mcRequisiteValues['RQ_FIRST_NAME|'.$mcPresetCountryId])) : '';
				$lastName = isset($mcRequisiteValues['RQ_LAST_NAME|'.$mcPresetCountryId]) ?
					trim(strval($mcRequisiteValues['RQ_LAST_NAME|'.$mcPresetCountryId])) : '';
				$secondName = isset($mcRequisiteValues['RQ_SECOND_NAME|'.$mcPresetCountryId]) ?
					trim(strval($mcRequisiteValues['RQ_SECOND_NAME|'.$mcPresetCountryId])) : '';
				if (!empty($firstName) || !empty($lastName) || !empty($secondName))
				{
					$fullName = CUser::FormatName(
						\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
						array(
							'LOGIN' => '[]',
							'NAME' => $firstName,
							'LAST_NAME' => $lastName,
							'SECOND_NAME' => $secondName
						),
						true, false
					);
					if (!empty($fullName) && $fullName !== '[]')
					{
						$mcRequisiteValues['RQ_NAME|'.$mcPresetCountryId] = $fullName;
					}
				}
			}
		}

		// my company bank detail values
		$mcBankDetailValues = array();
		if ($mcBankDetailId > 0)
		{
			$bankDetail = new \Bitrix\Crm\EntityBankDetail();
			$row = $bankDetail->getById($mcBankDetailId);
			if (is_array($row))
			{
				$countryId = isset($row['COUNTRY_ID']) ? (int)$row['COUNTRY_ID'] : 0;
				if ($countryId > 0)
				{
					foreach ($row as $fieldName => $fieldValue)
					{
						$mcBankDetailValues[$fieldName.'|'.$countryId] = $fieldValue;
					}
					unset($fieldName, $fieldValue, $valueKey);
				}
			}
		}

		// my company values
		$myCompanyValues = array();
		$myCompanyId = isset($arOrder['UF_MYCOMPANY_ID']) ? intval($arOrder['UF_MYCOMPANY_ID']) : 0;
		if ($myCompanyId > 0)
		{
			$filter = array('ID' => $myCompanyId);
			if ($isPublicLinkMode)
				$filter['CHECK_PERMISSIONS'] = 'N';
			$res = CCrmCompany::GetListEx(
				array(),
				$filter,
				false,
				array('nTopCount' => 1),
				array('ID', 'TITLE')
			);
			$row = $res->Fetch();
			if (is_array($row))
			{
				$myCompanyValues = $row;
				$res = CCrmFieldMulti::GetList(
					array('ID' => 'asc'),
					array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $myCompanyId)
				);
				$skip = array();
				while($row = $res->Fetch())
				{
					if (($row['TYPE_ID'] === 'PHONE' || $row['TYPE_ID'] === 'EMAIL')
						&& !isset($skip[$row['COMPLEX_ID']]))
					{
						$myCompanyValues[$row['COMPLEX_ID']] = $row['VALUE'];
						$skip[$row['COMPLEX_ID']] = true;
					}
				}
			}
		}

		// my company full name
		$myCompanyFullName = '';
		if ($mcPresetCountryId > 0)
		{
			$myCompanyFullName = isset($mcRequisiteValues['RQ_COMPANY_FULL_NAME|'.$mcPresetCountryId]) ?
				trim(strval($mcRequisiteValues['RQ_COMPANY_FULL_NAME|'.$mcPresetCountryId])) : '';

			if (empty($myCompanyFullName))
			{
				$myCompanyShortName = isset($mcRequisiteValues['RQ_COMPANY_NAME|'.$mcPresetCountryId]) ?
					trim(strval($mcRequisiteValues['RQ_COMPANY_NAME|'.$mcPresetCountryId])) : '';

				if (!empty($myCompanyShortName))
				{
					$myCompanyFullName = $myCompanyShortName;
				}
			}
		}
		if (empty($myCompanyFullName))
		{
			$myCompanyName = isset($myCompanyValues['TITLE']) ? trim(strval($myCompanyValues['TITLE'])) : '';
			if (!empty($myCompanyName))
				$myCompanyFullName = $myCompanyName;
		}
		if (!empty($myCompanyFullName))
		{
			foreach ($countryIds as $countryId)
				$mcRequisiteValues['RQ_COMPANY_FULL_NAME|'.$countryId] = $myCompanyFullName;
		}
		unset($myCompanyFullName, $myCompanyShortName, $myCompanyName);

		$userFields = array();
		global $USER_FIELD_MANAGER;
		$fields = $USER_FIELD_MANAGER->GetUserFields(CCrmInvoice::$sUFEntityID, null, LANGUAGE_ID);
		foreach ($fields as $key => $field)
		{
			$addressLines = $USER_FIELD_MANAGER->GetUserFieldValue(CCrmInvoice::$sUFEntityID, $key, $ID);
			$userFields[$key] = $addressLines;
		}

		return array(
			'REQUISITE' => $requisiteValues,
			'BANK_DETAIL' => $bankDetailValues,
			'CRM_COMPANY' => $companyValues,
			'CRM_CONTACT' => $contactValues,
			'MC_REQUISITE' => $mcRequisiteValues,
			'MC_BANK_DETAIL' => $mcBankDetailValues,
			'CRM_MYCOMPANY' => $myCompanyValues,
			'USER_FIELDS' => $userFields,
		);
	}

	public static function Rebind($ownerTypeID, $oldID, $newID)
	{
		global $DB;

		$ownerTypeID = intval($ownerTypeID);
		$oldID = intval($oldID);
		$newID = intval($newID);
		$tableName = 'b_uts_crm_invoice';

		if($ownerTypeID === CCrmOwnerType::Contact)
		{
			$DB->Query(
				"UPDATE {$tableName} SET UF_CONTACT_ID = {$newID} WHERE UF_CONTACT_ID = {$oldID}",
				false,
				'File: '.__FILE__.'<br>Line: '.__LINE__
			);
		}
		elseif($ownerTypeID === CCrmOwnerType::Company)
		{
			$DB->Query(
				"UPDATE {$tableName} SET UF_COMPANY_ID = {$newID} WHERE UF_COMPANY_ID = {$oldID}",
				false,
				'File: '.__FILE__.'<br>Line: '.__LINE__
			);
		}
	}

	/**
	 * @param $invoiceId
	 * @return string
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 */
	public static function getPublicLink($invoiceId)
	{
		if ($invoiceId > 0)
		{
			$order = Invoice::load($invoiceId);
			if ($order)
			{
				$paymentCollection = $order->getPaymentCollection();
				if ($paymentCollection)
				{
					/** @var \Bitrix\Sale\Payment $payment */
					foreach ($paymentCollection as $payment)
					{
						if (!$payment->isInner())
						{
							$context = Bitrix\Main\Application::getInstance()->getContext();
							$scheme = $context->getRequest()->isHttps() ? 'https' : 'http';
							$server = $context->getServer();
							$domain = $server->getServerName() ?: \COption::getOptionString('main', 'server_name', '');

							if (preg_match('/^(?<domain>.+):(?<port>\d+)$/', $domain, $matches))
							{
								$domain = $matches['domain'];
								$port   = $matches['port'];
							}
							else
							{
								$port = $server->getServerPort();
							}

							$port = in_array($port, array(80, 443)) ? '' : ':'.$port;

							return $scheme.'://'.$domain.$port.'/pub/pay/'.self::base64UrlEncode($order->getField('ACCOUNT_NUMBER')).'/'.$payment->getHash().'/';
						}
					}
				}
			}
		}

		return '';
	}

	/**
	 * @param string $value
	 * @return string
	 */
	protected static function base64UrlEncode(string $value) : string
	{
		return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
	}

	public static function savePdf($invoice_id, &$error = null)
	{
		$error = false;

		if (!CModule::IncludeModule('sale'))
		{
			$error = 'MODULE SALE NOT INCLUDED!';
			return false;
		}

		$invoice_id = (int) $invoice_id;
		if ($invoice_id <= 0)
		{
			$error = 'INVOICE_ID NOT DEFINED!';
			return false;
		}

		if (!\CCrmInvoice::checkReadPermission($invoice_id))
		{
			$error = 'PERMISSION DENIED!';
			return false;
		}

		$pdfContent = '';

		$dbOrder = Compatible\Helper::getList(
			array("ID"=>"DESC"),
			array("ID" => $invoice_id),
			false,
			false,
			array('*', 'UF_DEAL_ID', 'UF_QUOTE_ID', 'UF_COMPANY_ID', 'UF_CONTACT_ID', 'UF_MYCOMPANY_ID')
		);

		$arOrder = $dbOrder->GetNext();
		$paymentData = is_array($arOrder) ? CCrmInvoice::PrepareSalePaymentData($arOrder) : null;
		if(!$arOrder)
		{
			$error = 'COULD NOT FIND ORDER!';
			return false;
		}

		if ($arOrder["SUM_PAID"] <> '')
			$arOrder["PRICE"] -= $arOrder["SUM_PAID"];

		$service = \Bitrix\Sale\PaySystem\Manager::getObjectById($arOrder["PAY_SYSTEM_ID"]);
		if ($service !== null)
		{
			$psa = new CSalePaySystemAction();
			$psa->InitParamArrays(
				$arOrder,
				0,
				"",
				array(
					'REQUISITE' => is_array($paymentData['REQUISITE']) ? $paymentData['REQUISITE'] : null,
					'BANK_DETAIL' => is_array($paymentData['BANK_DETAIL']) ? $paymentData['BANK_DETAIL'] : null,
					'CRM_COMPANY' => is_array($paymentData['CRM_COMPANY']) ? $paymentData['CRM_COMPANY'] : null,
					'CRM_CONTACT' => is_array($paymentData['CRM_CONTACT']) ? $paymentData['CRM_CONTACT'] : null,
					'MC_REQUISITE' => is_array($paymentData['MC_REQUISITE']) ? $paymentData['MC_REQUISITE'] : null,
					'MC_BANK_DETAIL' => is_array($paymentData['MC_BANK_DETAIL']) ? $paymentData['MC_BANK_DETAIL'] : null,
					'CRM_MYCOMPANY' => is_array($paymentData['CRM_MYCOMPANY']) ? $paymentData['CRM_MYCOMPANY'] : null
				),
				array(),
				array(),
				REGISTRY_TYPE_CRM_INVOICE
			);
			unset($psa);

			\Bitrix\Sale\BusinessValue::redefineProviderField(
				array(
					'PROPERTY' => $paymentData['USER_FIELDS']
				)
			);

			$order = Invoice::load($invoice_id);
			if ($order)
			{
				$collection = $order->getPaymentCollection();
				if ($collection)
				{
					/** @var \Bitrix\Sale\Payment $payment */
					foreach ($collection as $payment)
					{
						if (!$payment->isInner() && $service->isAffordPdf())
						{
							$file = $service->getPdf($payment);
							if ($file !== null)
							{
								return $file['ID'];
							}
						}
					}

				}
			}
		}

		return false;
	}

	public static function Search($query, $topCount = 5, $minWordLength = 2)
	{
		$result = array();

		$query = ltrim(strval($query));

		if( !empty($query) && CModule::IncludeModule("search"))
		{
			$filter = array(
				array(
					'=MODULE_ID' => 'crm',
					'=PARAM1' => CCrmOwnerType::InvoiceName
				),
				'LOGIC' => 'OR'
			);

			$j = 0;
			$obTitle = new CSearchTitle;
			$obTitle->setMinWordLength($minWordLength);
			if($obTitle->Search($query, $topCount ,$filter))
			{
				while($ar = $obTitle->Fetch())
				{
					if (isset($ar['PARAM2']) && $ar['PARAM2'] <> '')
					{
						$result[] = (int)$ar['PARAM2'];
					}

					if(++$j >= $topCount)
					{
						break;
					}
				}
			}
		}

		return $result;
	}

	public static function existsEntityWithStatus($statusId)
	{
		$queryObject = self::getList(
			['ID' => 'DESC'],
			['STATUS_ID' => $statusId, 'CHECK_PERMISSIONS' => 'N'],
			false,
			false,
			['ID']
		);

		return (bool) $queryObject->fetch();
	}
}
