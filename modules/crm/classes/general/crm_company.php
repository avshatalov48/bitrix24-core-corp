<?php
IncludeModuleLangFile(__FILE__);

use Bitrix\Crm\Entity\Traits\UserFieldPreparer;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\UtmTable;
use Bitrix\Crm\Tracking;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\EntityAddressType;
use Bitrix\Crm\CompanyAddress;
use Bitrix\Crm\Binding\ContactCompanyTable;
use Bitrix\Crm\Integrity\DuplicateBankDetailCriterion;
use Bitrix\Crm\Integrity\DuplicateRequisiteCriterion;
use Bitrix\Crm\Integrity\DuplicateCommunicationCriterion;
use Bitrix\Crm\Integrity\DuplicateOrganizationCriterion;
use Bitrix\Crm\Integrity\DuplicateEntityRanking;
use Bitrix\Crm\Integrity\DuplicateIndexMismatch;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\Counter\EntityCounterManager;

class CAllCrmCompany
{
	use UserFieldPreparer;

	static public $sUFEntityID = 'CRM_COMPANY';
	const USER_FIELD_ENTITY_ID = 'CRM_COMPANY';
	const SUSPENDED_USER_FIELD_ENTITY_ID = 'CRM_COMPANY_SPD';
	const TOTAL_COUNT_CACHE_ID =  'crm_company_total_count';

	public $LAST_ERROR = '';
	protected $checkExceptions = array();

	public $cPerms = null;
	protected $bCheckPermission = true;
	const TABLE_ALIAS = 'L';
	protected static $TYPE_NAME = 'COMPANY';
	private static $FIELD_INFOS = null;
	const DEFAULT_FORM_ID = 'CRM_COMPANY_SHOW_V12';

	function __construct($bCheckPermission = true)
	{
		$this->bCheckPermission = $bCheckPermission;
		$this->cPerms = CCrmPerms::GetCurrentUserPermissions();
	}

	// Service -->
	public static function GetFieldCaption($fieldName)
	{
		if(\CCrmFieldMulti::IsSupportedType($fieldName))
		{
			return \CCrmFieldMulti::GetEntityTypeCaption($fieldName);
		}

		$result = GetMessage("CRM_COMPANY_FIELD_{$fieldName}");

		if (!(is_string($result) && $result !== '')
			&& Crm\Tracking\UI\Details::isTrackingField($fieldName))
		{
			$result = Crm\Tracking\UI\Details::getFieldCaption($fieldName);
		}

		return is_string($result) ? $result : '';
	}
	// Get Fields Metadata
	public static function GetFieldsInfo()
	{
		if(!self::$FIELD_INFOS)
		{
			self::$FIELD_INFOS = array(
				'ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'TITLE' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				),
				'COMPANY_TYPE' => array(
					'TYPE' => 'crm_status',
					'CRM_STATUS_TYPE' => 'COMPANY_TYPE',
					'ATTRIBUTES' => [CCrmFieldInfoAttr::HasDefaultValue]
				),
				'LOGO' => array(
					'TYPE' => 'file'
				),
				'ADDRESS' => array(
					'TYPE' => 'string'
				),
				'ADDRESS_2' => array(
					'TYPE' => 'string'
				),
				'ADDRESS_CITY' => array(
					'TYPE' => 'string'
				),
				'ADDRESS_POSTAL_CODE' => array(
					'TYPE' => 'string'
				),
				'ADDRESS_REGION' => array(
					'TYPE' => 'string'
				),
				'ADDRESS_PROVINCE' => array(
					'TYPE' => 'string'
				),
				'ADDRESS_COUNTRY' => array(
					'TYPE' => 'string'
				),
				'ADDRESS_COUNTRY_CODE' => array(
					'TYPE' => 'string'
				),
				'ADDRESS_LOC_ADDR_ID' => array(
					'TYPE' => 'integer'
				),
				'ADDRESS_LEGAL' => array(
					'TYPE' => 'string'
				),
				'REG_ADDRESS' => array(
					'TYPE' => 'string'
				),
				'REG_ADDRESS_2' => array(
					'TYPE' => 'string'
				),
				'REG_ADDRESS_CITY' => array(
					'TYPE' => 'string'
				),
				'REG_ADDRESS_POSTAL_CODE' => array(
					'TYPE' => 'string'
				),
				'REG_ADDRESS_REGION' => array(
					'TYPE' => 'string'
				),
				'REG_ADDRESS_PROVINCE' => array(
					'TYPE' => 'string'
				),
				'REG_ADDRESS_COUNTRY' => array(
					'TYPE' => 'string'
				),
				'REG_ADDRESS_COUNTRY_CODE' => array(
					'TYPE' => 'string'
				),
				'REG_ADDRESS_LOC_ADDR_ID' => array(
					'TYPE' => 'integer'
				),
				'BANKING_DETAILS' => array(
					'TYPE' => 'string'
				),
				'INDUSTRY' => array(
					'TYPE' => 'crm_status',
					'CRM_STATUS_TYPE' => 'INDUSTRY',
					'ATTRIBUTES' => [CCrmFieldInfoAttr::HasDefaultValue]
				),
				'EMPLOYEES' => array(
					'TYPE' => 'crm_status',
					'CRM_STATUS_TYPE' => 'EMPLOYEES',
					'ATTRIBUTES' => [CCrmFieldInfoAttr::HasDefaultValue]
				),
				'CURRENCY_ID' => array(
					'TYPE' => 'crm_currency'
				),
				'REVENUE' => array(
					'TYPE' => 'double'
				),
				'OPENED' => array(
					'TYPE' => 'char'
				),
				'COMMENTS' => array(
					'TYPE' => 'string'
				),
				'HAS_PHONE' => array(
					'TYPE' => 'char',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'HAS_EMAIL' => array(
					'TYPE' => 'char',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'HAS_IMOL' => array(
					'TYPE' => 'char',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'IS_MY_COMPANY' => array(
					'TYPE' => 'char'
				),
				'ASSIGNED_BY_ID' => array(
					'TYPE' => 'user'
				),
				'CREATED_BY_ID' => array(
					'TYPE' => 'user',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'MODIFY_BY_ID' => array(
					'TYPE' => 'user',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'DATE_CREATE' => array(
					'TYPE' => 'datetime',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'DATE_MODIFY' => array(
					'TYPE' => 'datetime',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'CONTACT_ID' => array(
					'TYPE' => 'crm_contact',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple)
				),
				'LEAD_ID' => array(
					'TYPE' => 'crm_lead',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'ORIGINATOR_ID' => array(
					'TYPE' => 'string'
				),
				'ORIGIN_ID' => array(
					'TYPE' => 'string'
				),
				'ORIGIN_VERSION' => array(
					'TYPE' => 'string'
				),
			);

			// add utm fields
			self::$FIELD_INFOS = self::$FIELD_INFOS + UtmTable::getUtmFieldsInfo();
		}

		return self::$FIELD_INFOS;
	}
	public static function GetFields($arOptions = null)
	{
		$assignedByJoin = 'LEFT JOIN b_user U ON L.ASSIGNED_BY_ID = U.ID';
		$createdByJoin = 'LEFT JOIN b_user U2 ON L.CREATED_BY_ID = U2.ID';
		$modifyByJoin = 'LEFT JOIN b_user U3 ON L.MODIFY_BY_ID = U3.ID';

		$result = array(
			'ID' => array('FIELD' => 'L.ID', 'TYPE' => 'int'),
			'COMPANY_TYPE' => array('FIELD' => 'L.COMPANY_TYPE', 'TYPE' => 'string'),
			'TITLE' => array('FIELD' => 'L.TITLE', 'TYPE' => 'string'),
			'LOGO' => array('FIELD' => 'L.LOGO', 'TYPE' => 'string'),
			'LEAD_ID' => array('FIELD' => 'L.LEAD_ID', 'TYPE' => 'int'),

			'HAS_PHONE' => array('FIELD' => 'L.HAS_PHONE', 'TYPE' => 'char'),
			'HAS_EMAIL' => array('FIELD' => 'L.HAS_EMAIL', 'TYPE' => 'char'),
			'HAS_IMOL' => array('FIELD' => 'L.HAS_IMOL', 'TYPE' => 'char'),

			'ASSIGNED_BY_ID' => array('FIELD' => 'L.ASSIGNED_BY_ID', 'TYPE' => 'int'),
			'ASSIGNED_BY_LOGIN' => array('FIELD' => 'U.LOGIN', 'TYPE' => 'string', 'FROM' => $assignedByJoin),
			'ASSIGNED_BY_NAME' => array('FIELD' => 'U.NAME', 'TYPE' => 'string', 'FROM' => $assignedByJoin),
			'ASSIGNED_BY_LAST_NAME' => array('FIELD' => 'U.LAST_NAME', 'TYPE' => 'string', 'FROM' => $assignedByJoin),
			'ASSIGNED_BY_SECOND_NAME' => array('FIELD' => 'U.SECOND_NAME', 'TYPE' => 'string', 'FROM' => $assignedByJoin),
			'ASSIGNED_BY_WORK_POSITION' => array('FIELD' => 'U.WORK_POSITION', 'TYPE' => 'string', 'FROM' => $assignedByJoin),
			'ASSIGNED_BY_PERSONAL_PHOTO' => array('FIELD' => 'U.PERSONAL_PHOTO', 'TYPE' => 'string', 'FROM' => $assignedByJoin),

			'CREATED_BY_ID' => array('FIELD' => 'L.CREATED_BY_ID', 'TYPE' => 'int'),
			'CREATED_BY_LOGIN' => array('FIELD' => 'U2.LOGIN', 'TYPE' => 'string', 'FROM' => $createdByJoin),
			'CREATED_BY_NAME' => array('FIELD' => 'U2.NAME', 'TYPE' => 'string', 'FROM' => $createdByJoin),
			'CREATED_BY_LAST_NAME' => array('FIELD' => 'U2.LAST_NAME', 'TYPE' => 'string', 'FROM' => $createdByJoin),
			'CREATED_BY_SECOND_NAME' => array('FIELD' => 'U2.SECOND_NAME', 'TYPE' => 'string', 'FROM' => $createdByJoin),

			'MODIFY_BY_ID' => array('FIELD' => 'L.MODIFY_BY_ID', 'TYPE' => 'int'),
			'MODIFY_BY_LOGIN' => array('FIELD' => 'U3.LOGIN', 'TYPE' => 'string', 'FROM' => $modifyByJoin),
			'MODIFY_BY_NAME' => array('FIELD' => 'U3.NAME', 'TYPE' => 'string', 'FROM' => $modifyByJoin),
			'MODIFY_BY_LAST_NAME' => array('FIELD' => 'U3.LAST_NAME', 'TYPE' => 'string', 'FROM' => $modifyByJoin),
			'MODIFY_BY_SECOND_NAME' => array('FIELD' => 'U3.SECOND_NAME', 'TYPE' => 'string', 'FROM' => $modifyByJoin),

			'BANKING_DETAILS' => array('FIELD' => 'L.BANKING_DETAILS', 'TYPE' => 'string'),

			'INDUSTRY' => array('FIELD' => 'L.INDUSTRY', 'TYPE' => 'string'),
			'REVENUE' => array('FIELD' => 'L.REVENUE', 'TYPE' => 'string'),
			'CURRENCY_ID' => array('FIELD' => 'L.CURRENCY_ID', 'TYPE' => 'string'),
			'EMPLOYEES' => array('FIELD' => 'L.EMPLOYEES', 'TYPE' => 'string'),
			'COMMENTS' => array('FIELD' => 'L.COMMENTS', 'TYPE' => 'string'),

			'DATE_CREATE' => array('FIELD' => 'L.DATE_CREATE', 'TYPE' => 'datetime'),
			'DATE_MODIFY' => array('FIELD' => 'L.DATE_MODIFY', 'TYPE' => 'datetime'),

			'OPENED' => array('FIELD' => 'L.OPENED', 'TYPE' => 'char'),
			'IS_MY_COMPANY' => array('FIELD' => 'L.IS_MY_COMPANY', 'TYPE' => 'char'),
			'WEBFORM_ID' => array('FIELD' => 'L.WEBFORM_ID', 'TYPE' => 'int'),
			'ORIGINATOR_ID' => array('FIELD' => 'L.ORIGINATOR_ID', 'TYPE' => 'string'), //EXTERNAL SYSTEM THAT OWNS THIS ITEM
			'ORIGIN_ID' => array('FIELD' => 'L.ORIGIN_ID', 'TYPE' => 'string'), //ITEM ID IN EXTERNAL SYSTEM
			'ORIGIN_VERSION' => array('FIELD' => 'L.ORIGIN_VERSION', 'TYPE' => 'string') //ITEM VERSION IN EXTERNAL SYSTEM
		);

		if(!(is_array($arOptions) && isset($arOptions['DISABLE_ADDRESS']) && $arOptions['DISABLE_ADDRESS']))
		{
			if (COption::GetOptionString('crm', '~CRM_CONVERT_COMPANY_ADDRESSES', 'N') === 'Y')
			{
				$addrJoin = 'LEFT JOIN b_crm_addr ADDR ON L.ID = ADDR.ENTITY_ID AND ADDR.TYPE_ID = '
					.EntityAddressType::Primary.' AND ADDR.ENTITY_TYPE_ID = '.CCrmOwnerType::Company;
				$regAddrJoin = 'LEFT JOIN b_crm_addr R_ADDR ON L.ID = R_ADDR.ENTITY_ID AND R_ADDR.TYPE_ID = '
					.EntityAddressType::Registered.' AND R_ADDR.ENTITY_TYPE_ID = '.CCrmOwnerType::Company;
			}
			else
			{
				$addrJoin = 'LEFT JOIN b_crm_addr ADDR ON L.ID = ADDR.ANCHOR_ID AND ADDR.TYPE_ID = '
					.EntityAddressType::Primary.' AND ADDR.ANCHOR_TYPE_ID = '.CCrmOwnerType::Company.
					' AND ADDR.IS_DEF = 1';
				$regAddrJoin = 'LEFT JOIN b_crm_addr R_ADDR ON L.ID = R_ADDR.ANCHOR_ID AND R_ADDR.TYPE_ID = '
					.EntityAddressType::Registered.' AND R_ADDR.ANCHOR_TYPE_ID = '.CCrmOwnerType::Company.
					' AND R_ADDR.IS_DEF = 1';
			}

			$result['ADDRESS'] = array('FIELD' => 'ADDR.ADDRESS_1', 'TYPE' => 'string', 'FROM' => $addrJoin);
			$result['ADDRESS_2'] = array('FIELD' => 'ADDR.ADDRESS_2', 'TYPE' => 'string', 'FROM' => $addrJoin);
			$result['ADDRESS_CITY'] = array('FIELD' => 'ADDR.CITY', 'TYPE' => 'string', 'FROM' => $addrJoin);
			$result['ADDRESS_POSTAL_CODE'] = array('FIELD' => 'ADDR.POSTAL_CODE', 'TYPE' => 'string', 'FROM' => $addrJoin);
			$result['ADDRESS_REGION'] = array('FIELD' => 'ADDR.REGION', 'TYPE' => 'string', 'FROM' => $addrJoin);
			$result['ADDRESS_PROVINCE'] = array('FIELD' => 'ADDR.PROVINCE', 'TYPE' => 'string', 'FROM' => $addrJoin);
			$result['ADDRESS_COUNTRY'] = array('FIELD' => 'ADDR.COUNTRY', 'TYPE' => 'string', 'FROM' => $addrJoin);
			$result['ADDRESS_COUNTRY_CODE'] = array('FIELD' => 'ADDR.COUNTRY_CODE', 'TYPE' => 'string', 'FROM' => $addrJoin);
			$result['ADDRESS_LOC_ADDR_ID'] = array('FIELD' => 'ADDR.LOC_ADDR_ID', 'TYPE' => 'integer', 'FROM' => $addrJoin);

			$result['ADDRESS_LEGAL'] = array('FIELD' => 'R_ADDR.ADDRESS_1', 'TYPE' => 'string', 'FROM' => $regAddrJoin);
			$result['REG_ADDRESS'] = array('FIELD' => 'R_ADDR.ADDRESS_1', 'TYPE' => 'string', 'FROM' => $regAddrJoin);
			$result['REG_ADDRESS_2'] = array('FIELD' => 'R_ADDR.ADDRESS_2', 'TYPE' => 'string', 'FROM' => $regAddrJoin);
			$result['REG_ADDRESS_CITY'] = array('FIELD' => 'R_ADDR.CITY', 'TYPE' => 'string', 'FROM' => $regAddrJoin);
			$result['REG_ADDRESS_POSTAL_CODE'] = array('FIELD' => 'R_ADDR.POSTAL_CODE', 'TYPE' => 'string', 'FROM' => $regAddrJoin);
			$result['REG_ADDRESS_REGION'] = array('FIELD' => 'R_ADDR.REGION', 'TYPE' => 'string', 'FROM' => $regAddrJoin);
			$result['REG_ADDRESS_PROVINCE'] = array('FIELD' => 'R_ADDR.PROVINCE', 'TYPE' => 'string', 'FROM' => $regAddrJoin);
			$result['REG_ADDRESS_COUNTRY'] = array('FIELD' => 'R_ADDR.COUNTRY', 'TYPE' => 'string', 'FROM' => $regAddrJoin);
			$result['REG_ADDRESS_COUNTRY_CODE'] = array('FIELD' => 'R_ADDR.COUNTRY_CODE', 'TYPE' => 'string', 'FROM' => $regAddrJoin);
			$result['REG_ADDRESS_LOC_ADDR_ID'] = array('FIELD' => 'R_ADDR.LOC_ADDR_ID', 'TYPE' => 'integer', 'FROM' => $regAddrJoin);
		}

		// Creation of field aliases
		$result['ASSIGNED_BY'] = $result['ASSIGNED_BY_ID'];
		$result['CREATED_BY'] = $result['CREATED_BY_ID'];
		$result['MODIFY_BY'] = $result['MODIFY_BY_ID'];

		$additionalFields = is_array($arOptions) && isset($arOptions['ADDITIONAL_FIELDS'])
			? $arOptions['ADDITIONAL_FIELDS'] : null;

		if(is_array($additionalFields))
		{
			if(in_array('ACTIVITY', $additionalFields, true))
			{
				$commonActivityJoin = CCrmActivity::PrepareJoin(0, CCrmOwnerType::Company, 'L', 'AC', 'UAC', 'ACUSR');

				$result['C_ACTIVITY_ID'] = array('FIELD' => 'UAC.ACTIVITY_ID', 'TYPE' => 'int', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_TIME'] = array('FIELD' => 'UAC.ACTIVITY_TIME', 'TYPE' => 'datetime', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_SUBJECT'] = array('FIELD' => 'AC.SUBJECT', 'TYPE' => 'string', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_RESP_ID'] = array('FIELD' => 'AC.RESPONSIBLE_ID', 'TYPE' => 'int', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_RESP_LOGIN'] = array('FIELD' => 'ACUSR.LOGIN', 'TYPE' => 'string', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_RESP_NAME'] = array('FIELD' => 'ACUSR.NAME', 'TYPE' => 'string', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_RESP_LAST_NAME'] = array('FIELD' => 'ACUSR.LAST_NAME', 'TYPE' => 'string', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_RESP_SECOND_NAME'] = array('FIELD' => 'ACUSR.SECOND_NAME', 'TYPE' => 'string', 'FROM' => $commonActivityJoin);

				$userID = CCrmPerms::GetCurrentUserID();
				if($userID > 0)
				{
					$activityJoin = CCrmActivity::PrepareJoin($userID, CCrmOwnerType::Company, 'L', 'A', 'UA', '');

					$result['ACTIVITY_ID'] = array('FIELD' => 'UA.ACTIVITY_ID', 'TYPE' => 'int', 'FROM' => $activityJoin);
					$result['ACTIVITY_TIME'] = array('FIELD' => 'UA.ACTIVITY_TIME', 'TYPE' => 'datetime', 'FROM' => $activityJoin);
					$result['ACTIVITY_SUBJECT'] = array('FIELD' => 'A.SUBJECT', 'TYPE' => 'string', 'FROM' => $activityJoin);
				}
			}
		}

		// add utm fields
		$result = array_merge($result, UtmTable::getFieldsDescriptionByEntityTypeId(CCrmOwnerType::Company));

		return $result;
	}
	// <-- Service

	public static function GetUserFieldEntityID()
	{
		return self::$sUFEntityID;
	}

	public static function GetUserFields()
	{
		global $USER_FIELD_MANAGER;
		return $USER_FIELD_MANAGER->GetUserFields(self::$sUFEntityID);
	}

	// GetList with navigation support
	public static function GetListEx($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array(), $arOptions = array())
	{
		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		if(!isset($arOptions['PERMISSION_SQL_TYPE']))
		{
			$arOptions['PERMISSION_SQL_TYPE'] = 'FROM';
			$arOptions['PERMISSION_SQL_UNION'] = 'DISTINCT';
		}

		$lb = new CCrmEntityListBuilder(
			CCrmCompany::DB_TYPE,
			CCrmCompany::TABLE_NAME,
			self::TABLE_ALIAS,
			self::GetFields(isset($arOptions['FIELD_OPTIONS']) ? $arOptions['FIELD_OPTIONS'] : null),
			self::$sUFEntityID,
			'COMPANY',
			array('CCrmCompany', 'BuildPermSql'),
			array('CCrmCompany', '__AfterPrepareSql')
		);
		return $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields, $arOptions);
	}

	public static function CreateListBuilder(array $arFieldOptions = null)
	{
		return new CCrmEntityListBuilder(
			CCrmCompany::DB_TYPE,
			CCrmCompany::TABLE_NAME,
			self::TABLE_ALIAS,
			self::GetFields($arFieldOptions),
			self::$sUFEntityID,
			'COMPANY',
			array('CCrmCompany', 'BuildPermSql'),
			array('CCrmCompany', '__AfterPrepareSql')
		);
	}

	public static function Exists($ID)
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			return false;
		}

		$dbRes = self::GetListEx(
			array(),
			array('ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID')
		);

		return is_array($dbRes->Fetch());
	}

	public static function GetTopIDs($top, $sortType = 'ASC', $userPermissions = null)
	{
		$top = (int)$top;
		if ($top <= 0)
		{
			return [];
		}

		$sortType = mb_strtoupper($sortType) !== 'DESC' ? 'ASC' : 'DESC';

		return \Bitrix\Crm\Entity\Company::getInstance()->getTopIDs([
			'order' => ['ID' => $sortType],
			'limit' => $top,
			'userPermissions' => $userPermissions
		]);
	}

	public static function GetTotalCount()
	{
		if(defined('BX_COMP_MANAGED_CACHE') && $GLOBALS['CACHE_MANAGER']->Read(600, self::TOTAL_COUNT_CACHE_ID, 'b_crm_company'))
		{
			return $GLOBALS['CACHE_MANAGER']->Get(self::TOTAL_COUNT_CACHE_ID);
		}

		$result = (int)self::GetListEx(
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

	public static function GetRightSiblingID($ID)
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			return 0;
		}

		$dbRes = self::GetListEx(
			array('ID' => 'ASC'),
			array('>ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			array('nTopCount' => 1),
			array('ID')
		);

		$arRes =  $dbRes->Fetch();
		if(!is_array($arRes))
		{
			return 0;
		}

		return intval($arRes['ID']);
	}

	public static function GetLeftSiblingID($ID)
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			return 0;
		}

		$dbRes = self::GetListEx(
			array('ID' => 'DESC'),
			array('<ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			array('nTopCount' => 1),
			array('ID')
		);

		$arRes =  $dbRes->Fetch();
		if(!is_array($arRes))
		{
			return 0;
		}

		return intval($arRes['ID']);
	}

	/**
	 *
	 * @param array $arOrder
	 * @param array $arFilter
	 * @param array $arSelect
	 * @return CDBResult
	 */
	public static function GetList($arOrder = Array('DATE_CREATE' => 'DESC'), $arFilter = Array(), $arSelect = Array(), $nPageTop = false)
	{
		global $DB, $USER_FIELD_MANAGER;

		// fields
		$arFields = array(
			'ID' => 'L.ID',
			'LEAD_ID' => 'L.LEAD_ID',
			'ADDRESS' => 'L.ADDRESS',
			'ADDRESS_LEGAL' => 'L.ADDRESS_LEGAL',
			'BANKING_DETAILS' => 'L.BANKING_DETAILS',
			'TITLE' => 'L.TITLE',
			'LOGO' => 'L.LOGO',
			'COMPANY_TYPE' => 'L.COMPANY_TYPE',
			'INDUSTRY' => 'L.INDUSTRY',
			'REVENUE' => 'L.REVENUE',
			'CURRENCY_ID' => 'L.CURRENCY_ID',
			'EMPLOYEES' => 'L.EMPLOYEES',
			'COMMENTS' => 'L.COMMENTS',
			'CREATED_BY' => 'L.CREATED_BY_ID',
			'CREATED_BY_ID' => 'L.CREATED_BY_ID',
			'MODIFY_BY' => 'L.MODIFY_BY_ID',
			'MODIFY_BY_ID' => 'L.MODIFY_BY_ID',
			'DATE_CREATE' => $DB->DateToCharFunction('L.DATE_CREATE'),
			'DATE_MODIFY' => $DB->DateToCharFunction('L.DATE_MODIFY'),
			'OPENED' => 'L.OPENED',
			'IS_MY_COMPANY' => 'L.IS_MY_COMPANY',
			'ORIGINATOR_ID' => 'L.ORIGINATOR_ID', //EXTERNAL SYSTEM THAT OWNS THIS ITEM
			'ORIGIN_ID' => 'L.ORIGIN_ID', //ITEM ID IN EXTERNAL SYSTEM
			'CREATED_BY_LOGIN' => 'U2.LOGIN',
			'CREATED_BY_NAME' => 'U2.NAME',
			'CREATED_BY_LAST_NAME' => 'U2.LAST_NAME',
			'CREATED_BY_SECOND_NAME' => 'U2.SECOND_NAME',
			'MODIFY_BY_LOGIN' => 'U3.LOGIN',
			'MODIFY_BY_NAME' => 'U3.NAME',
			'MODIFY_BY_LAST_NAME' => 'U3.LAST_NAME',
			'MODIFY_BY_SECOND_NAME' => 'U3.SECOND_NAME'
		);

		$arSqlSelect = array();
		$sSqlJoin = '';
		if (count($arSelect) == 0)
			$arSelect = array_merge(array_keys($arFields), array('UF_*'));

		$obQueryWhere = new CSQLWhere();
		$arFilterField = $arSelect;
		foreach ($arFilter as $sKey => $sValue)
		{
			$arField = $obQueryWhere->MakeOperation($sKey);
			$arFilterField[] = $arField['FIELD'];
		}

		if (in_array('CREATED_BY_LOGIN', $arFilterField) || in_array('CREATED_BY_LOGIN', $arFilterField))
		{
			$arSelect[] = 'CREATED_BY';
			$arSelect[] = 'CREATED_BY_LOGIN';
			$arSelect[] = 'CREATED_BY_NAME';
			$arSelect[] = 'CREATED_BY_LAST_NAME';
			$arSelect[] = 'CREATED_BY_SECOND_NAME';
			$sSqlJoin .= ' LEFT JOIN b_user U2 ON L.CREATED_BY_ID = U2.ID ';
		}
		if (in_array('MODIFY_BY_LOGIN', $arFilterField) || in_array('MODIFY_BY_LOGIN', $arFilterField))
		{
			$arSelect[] = 'MODIFY_BY';
			$arSelect[] = 'MODIFY_BY_LOGIN';
			$arSelect[] = 'MODIFY_BY_NAME';
			$arSelect[] = 'MODIFY_BY_LAST_NAME';
			$arSelect[] = 'MODIFY_BY_SECOND_NAME';
			$sSqlJoin .= ' LEFT JOIN b_user U3 ON  L.MODIFY_BY_ID = U3.ID ';
		}

		foreach($arSelect as $field)
		{
			$field = mb_strtoupper($field);
			if(array_key_exists($field, $arFields))
				$arSqlSelect[$field] = $arFields[$field].($field != '*' ? ' AS '.$field : '');
		}

		if (!isset($arSqlSelect['ID']))
			$arSqlSelect['ID'] = $arFields['ID'];
		$sSqlSelect = implode(",\n", $arSqlSelect);

		if (isset($arFilter['FM']) && !empty($arFilter['FM']))
		{
			$res = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'COMPANY', 'FILTER' => $arFilter['FM']));
			$ids = array();
			while($ar = $res->Fetch())
			{
				$ids[] = $ar['ELEMENT_ID'];
			}

			if(count($ids) == 0)
			{
				// Fix for #26789 (nothing found)
				$rs = new CDBResult();
				$rs->InitFromArray(array());
				return $rs;
			}

			$arFilter['ID'] = $ids;
		}

		$obUserFieldsSql = new CUserTypeSQL();
		$obUserFieldsSql->SetEntity(self::$sUFEntityID, 'L.ID');
		$obUserFieldsSql->SetSelect($arSelect);
		$obUserFieldsSql->SetFilter($arFilter);
		$obUserFieldsSql->SetOrder($arOrder);

		$arSqlSearch = array();
		// check permissions
		$sSqlPerm = '';
		if (!CCrmPerms::IsAdmin()
			&& (!array_key_exists('CHECK_PERMISSIONS', $arFilter) || $arFilter['CHECK_PERMISSIONS'] !== 'N')
		)
		{
			$arPermType = array();
			if (!isset($arFilter['PERMISSION']))
				$arPermType[] = 'READ';
			else
				$arPermType	= is_array($arFilter['PERMISSION']) ? $arFilter['PERMISSION'] : array($arFilter['PERMISSION']);

			$sSqlPerm = self::BuildPermSql('L', $arPermType);
			if ($sSqlPerm === false)
			{
				$CDBResult = new CDBResult();
				$CDBResult->InitFromArray(array());
				return $CDBResult;
			}

			if($sSqlPerm <> '')
			{
				$sSqlPerm = ' AND '.$sSqlPerm;
			}
		}

		// where
		$arWhereFields = array(
			'ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.ID',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'LEAD_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.LEAD_ID',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'COMPANY_TYPE' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.COMPANY_TYPE',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'INDUSTRY' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.INDUSTRY',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'TITLE' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.TITLE',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'REVENUE' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.REVENUE',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'CURRENCY_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.CURRENCY_ID',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'EMPLOYEES' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.EMPLOYEES',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'ADDRESS' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.ADDRESS',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'BANKING_DETAILS' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.BANKING_DETAILS',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'ADDRESS_LEGAL' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.ADDRESS_LEGAL',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'COMMENTS' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.COMMENTS',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'DATE_CREATE' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.DATE_CREATE',
				'FIELD_TYPE' => 'datetime',
				'JOIN' => false
			),
			'DATE_MODIFY' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.DATE_MODIFY',
				'FIELD_TYPE' => 'datetime',
				'JOIN' => false
			),
			'CREATED_BY_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.CREATED_BY_ID',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'OPENED' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.OPENED',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'IS_MY_COMPANY' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.IS_MY_COMPANY',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'MODIFY_BY_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.MODIFY_BY_ID',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'ORIGINATOR_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.ORIGINATOR_ID',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'ORIGIN_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.ORIGIN_ID',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
		);

		$obQueryWhere->SetFields($arWhereFields);
		if (!is_array($arFilter))
			$arFilter = array();
		$sQueryWhereFields = $obQueryWhere->GetQuery($arFilter);

		$sSqlSearch = '';
		foreach($arSqlSearch as $r)
			if($r <> '')
				$sSqlSearch .= "\n\t\t\t\tAND  ($r) ";
		$CCrmUserType = new CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], self::$sUFEntityID);
		$CCrmUserType->ListPrepareFilter($arFilter);
		$r = $obUserFieldsSql->GetFilter();
		if ($r <> '')
			$sSqlSearch .= "\n\t\t\t\tAND ($r) ";

		if (!empty($sQueryWhereFields))
			$sSqlSearch .= "\n\t\t\t\tAND ($sQueryWhereFields) ";

		$arFieldsOrder = array(
			'CREATED_BY' => 'L.CREATED_BY_ID',
			'MODIFY_BY' => 'L.MODIFY_BY_ID',
			'DATE_CREATE' => 'L.DATE_CREATE',
			'DATE_MODIFY' => 'L.DATE_MODIFY'
		);

		// order
		$arSqlOrder = Array();
		if (!is_array($arOrder))
			$arOrder = Array('DATE_CREATE' => 'DESC');
		foreach($arOrder as $by => $order)
		{
			$by = mb_strtoupper($by);
			$order = mb_strtolower($order);
			if ($order != 'asc')
				$order = 'desc';

			if (isset($arFieldsOrder[$by]))
				$arSqlOrder[$by] = " {$arFieldsOrder[$by]} $order ";
			elseif(isset($arFields[$by]) && $by != 'ADDRESS')
				$arSqlOrder[$by] = " L.$by $order ";
			elseif($s = $obUserFieldsSql->GetOrder($by))
				$arSqlOrder[$by] = " $s $order ";
			else
			{
				$by = 'date_create';
				$arSqlOrder[$by] = " L.DATE_CREATE $order ";
			}
		}

		if (count($arSqlOrder) > 0)
			$sSqlOrder = "\n\t\t\t\tORDER BY ".implode(', ', $arSqlOrder);
		else
			$sSqlOrder = '';

		$sSql = "
			SELECT
				$sSqlSelect
				{$obUserFieldsSql->GetSelect()}
			FROM
				b_crm_company L $sSqlJoin
				{$obUserFieldsSql->GetJoin('L.ID')}
			WHERE
				1=1 $sSqlSearch
				$sSqlPerm
			$sSqlOrder";

		if ($nPageTop !== false)
		{
			$nPageTop = (int) $nPageTop;
			$sSql = $DB->TopSql($sSql, $nPageTop);
		}

		$obRes = $DB->Query($sSql, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		$obRes->SetUserFields($USER_FIELD_MANAGER->GetUserFields(self::$sUFEntityID));
		return $obRes;
	}

	public static function GetByID($ID, $bCheckPerms = true)
	{
		$arFilter = array('=ID' => intval($ID));
		if (!$bCheckPerms)
		{
			$arFilter['CHECK_PERMISSIONS'] = 'N';
		}

		$dbRes = CCrmCompany::GetListEx(array(), $arFilter);
		return $dbRes->Fetch();
	}

	public static function BuildPermSql($sAliasPrefix = 'L', $mPermType = 'READ', $arOptions = array())
	{
		return CCrmPerms::BuildSql(
			'COMPANY',
			$sAliasPrefix,
			$mPermType,
			array_merge($arOptions, array('READ_ALL' => true))
		);
	}

	public static function __AfterPrepareSql($sender, $arOrder, $arFilter, $arGroupBy, $arSelectFields)
	{
		$sqlData = array('FROM' => array(), 'WHERE' => array());
		if(isset($arFilter['SEARCH_CONTENT']) && $arFilter['SEARCH_CONTENT'] !== '')
		{
			$tableAlias = $sender->GetTableAlias();
			$queryWhere = new CSQLWhere();
			$queryWhere->SetFields(
				array(
					'SEARCH_CONTENT' => array(
						'FIELD_NAME' => "{$tableAlias}.SEARCH_CONTENT",
						'FIELD_TYPE' => 'string',
						'JOIN' => false
					)
				)
			);
			$options = [];
			if (isset($arFilter['__ENABLE_SEARCH_CONTENT_PHONE_DETECTION']))
			{
				$options['ENABLE_PHONE_DETECTION'] = $arFilter['__ENABLE_SEARCH_CONTENT_PHONE_DETECTION'];
				unset($arFilter['__ENABLE_SEARCH_CONTENT_PHONE_DETECTION']);
			}
			$query = $queryWhere->GetQuery(
				Crm\Search\SearchEnvironment::prepareEntityFilter(
					CCrmOwnerType::Company,
					array(
						'SEARCH_CONTENT' => Crm\Search\SearchEnvironment::prepareSearchContent($arFilter['SEARCH_CONTENT'], $options)
					)
				)
			);
			if($query !== '')
			{
				$sqlData['WHERE'][] = $query;
			}
		}

		if(isset($arFilter['ADDRESSES']))
		{
			foreach($arFilter['ADDRESSES'] as $addressTypeID => $addressFilter)
			{
				$sqlData['FROM'][] = EntityAddress::prepareFilterJoinSql(
					CCrmOwnerType::Company,
					$addressTypeID,
					$addressFilter,
					$sender->GetTableAlias()
				);
			}
		}
		if(isset($arFilter['ASSOCIATED_CONTACT_ID']))
		{
			$sqlData['FROM'][] = ContactCompanyTable::prepareFilterJoinSql(
				CCrmOwnerType::Contact,
				$arFilter['ASSOCIATED_CONTACT_ID'],
				$sender->GetTableAlias()
			);
		}

		Tracking\UI\Filter::buildFilterAfterPrepareSql(
			$sqlData,
			$arFilter,
			\CCrmOwnerType::ResolveID(self::$TYPE_NAME),
			$sender->GetTableAlias()
		);

		$result = array();
		if(!empty($sqlData['FROM']))
		{
			$result['FROM'] = implode(' ', $sqlData['FROM']);
		}
		if(!empty($sqlData['WHERE']))
		{
			$result['WHERE'] = implode(' AND ', $sqlData['WHERE']);
		}
		return !empty($result) ? $result : false;
	}

	public function Add(array &$arFields, $bUpdateSearch = true, $options = array())
	{
		global $DB;

		if(!is_array($options))
		{
			$options = array();
		}

		$this->LAST_ERROR = '';
		$this->checkExceptions = [];

		$isRestoration = isset($options['IS_RESTORATION']) && $options['IS_RESTORATION'];

		$userID = isset($options['CURRENT_USER'])
			? (int)$options['CURRENT_USER'] : CCrmSecurityHelper::GetCurrentUserID();

		if($userID <= 0 && $this->bCheckPermission)
		{
			$arFields['RESULT_MESSAGE'] = $this->LAST_ERROR = GetMessage('CRM_PERMISSION_USER_NOT_DEFINED');
			return false;
		}

		unset($arFields['ID']);

		if(!($isRestoration && isset($arFields['DATE_CREATE'])))
		{
			unset($arFields['DATE_CREATE']);
			$arFields['~DATE_CREATE'] = $DB->CurrentTimeFunction();
		}

		if(!($isRestoration && isset($arFields['DATE_MODIFY'])))
		{
			unset($arFields['DATE_MODIFY']);
			$arFields['~DATE_MODIFY'] = $DB->CurrentTimeFunction();
		}

		if($userID > 0)
		{
			if(!(isset($arFields['CREATED_BY_ID']) && $arFields['CREATED_BY_ID'] > 0))
			{
				$arFields['CREATED_BY_ID'] = $userID;
			}

			if(!(isset($arFields['MODIFY_BY_ID']) && $arFields['MODIFY_BY_ID'] > 0))
			{
				$arFields['MODIFY_BY_ID'] = $userID;
			}

			if(!(isset($arFields['ASSIGNED_BY_ID']) && $arFields['ASSIGNED_BY_ID'] > 0))
			{
				$arFields['ASSIGNED_BY_ID'] = $userID;
			}
		}

		if (isset($arFields['REVENUE']))
			$arFields['REVENUE'] = floatval($arFields['REVENUE']);

		$arFields['IS_MY_COMPANY'] = (isset($arFields['IS_MY_COMPANY']) && $arFields['IS_MY_COMPANY'] === 'Y') ?
			'Y' : 'N';

		if(!isset($arFields['TITLE']) || trim($arFields['TITLE']) === '')
		{
			$arFields['TITLE'] = self::GetAutoTitle();
		}

		$fields = self::GetUserFields();
		$this->fillEmptyFieldValues($arFields, $fields);

		if (!$this->CheckFields($arFields, false, $options))
		{
			$result = false;
			$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
		}
		else
		{
			$arAttr = array();
			if (!empty($arFields['OPENED']))
			{
				$arAttr['OPENED'] = $arFields['OPENED'];
			}

			if ($arFields['IS_MY_COMPANY'] === 'Y')
			{
				$arAttr['IS_MY_COMPANY'] = $arFields['IS_MY_COMPANY'];
			}

			$sPermission = 'ADD';
			if (isset($arFields['PERMISSION']))
			{
				if ($arFields['PERMISSION'] == 'IMPORT')
					$sPermission = 'IMPORT';
				unset($arFields['PERMISSION']);
			}

			if($this->bCheckPermission)
			{
				$arEntityAttr = self::BuildEntityAttr($userID, $arAttr);
				$userPerms =  $userID == CCrmPerms::GetCurrentUserID() ? $this->cPerms : CCrmPerms::GetUserPermissions($userID);
				$sEntityPerm = $userPerms->GetPermType('COMPANY', $sPermission, $arEntityAttr);
				if ($sEntityPerm == BX_CRM_PERM_NONE)
				{
					$this->LAST_ERROR = GetMessage('CRM_PERMISSION_DENIED');
					$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
					return false;
				}

				$assignedByID = intval($arFields['ASSIGNED_BY_ID']);
				if ($sEntityPerm == BX_CRM_PERM_SELF && $assignedByID != $userID)
				{
					$arFields['ASSIGNED_BY_ID'] = $userID;
				}
				if ($sEntityPerm == BX_CRM_PERM_OPEN && $userID == $assignedByID)
				{
					$arFields['OPENED'] = 'Y';
				}
			}

			$assignedByID = intval($arFields['ASSIGNED_BY_ID']);
			$arEntityAttr = self::BuildEntityAttr($assignedByID, $arAttr);
			$userPerms =  $assignedByID == CCrmPerms::GetCurrentUserID() ? $this->cPerms : CCrmPerms::GetUserPermissions($assignedByID);
			$sEntityPerm = $userPerms->GetPermType('COMPANY', $sPermission, $arEntityAttr);
			$this->PrepareEntityAttrs($arEntityAttr, $sEntityPerm);

			//Statistics & History -->
			if(isset($arFields['LEAD_ID']) && $arFields['LEAD_ID'] > 0)
			{
				Bitrix\Crm\Statistics\LeadConversionStatisticsEntry::processBindingsChange($arFields['LEAD_ID']);
			}
			//<-- Statistics & History

			if(isset($arFields['LOGO'])
				&& is_array($arFields['LOGO'])
				&& CFile::CheckImageFile($arFields['LOGO']) == '')
			{
				$arFields['LOGO']['MODULE_ID'] = 'crm';
				CFile::SaveForDB($arFields, 'LOGO', 'crm');
			}

			//region Setup HAS_EMAIL & HAS_PHONE & HAS_IMOL fields
			$arFields['HAS_EMAIL'] = $arFields['HAS_PHONE'] = $arFields['HAS_IMOL'] = 'N';
			if(isset($arFields['FM']) && is_array($arFields['FM']))
			{
				if(CCrmFieldMulti::HasValues($arFields['FM'], CCrmFieldMulti::EMAIL))
				{
					$arFields['HAS_EMAIL'] = 'Y';
				}

				if(CCrmFieldMulti::HasValues($arFields['FM'], CCrmFieldMulti::PHONE))
				{
					$arFields['HAS_PHONE'] = 'Y';
				}

				if(CCrmFieldMulti::HasImolValues($arFields['FM']))
				{
					$arFields['HAS_IMOL'] = 'Y';
				}
			}
			//endregion

			$beforeEvents = GetModuleEvents('crm', 'OnBeforeCrmCompanyAdd');
			while ($arEvent = $beforeEvents->Fetch())
			{
				if(ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
				{
					if(isset($arFields['RESULT_MESSAGE']))
					{
						$this->LAST_ERROR = $arFields['RESULT_MESSAGE'];
					}
					else
					{
						$this->LAST_ERROR = GetMessage('CRM_COMPANY_CREATION_CANCELED', array('#NAME#' => $arEvent['TO_NAME']));
						$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
					}
					return false;
				}
			}

			unset($arFields['ID']);
			$ID = intval($DB->Add('b_crm_company', $arFields, array(), 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__));
			//Append ID to TITLE if required
			if($ID > 0 && $arFields['TITLE'] === self::GetAutoTitle())
			{
				$arFields['TITLE'] = self::GetAutoTitle($ID);
				$sUpdate = $DB->PrepareUpdate('b_crm_company', array('TITLE' => $arFields['TITLE']));
				if($sUpdate <> '')
				{
					$DB->Query(
						"UPDATE b_crm_company SET {$sUpdate} WHERE ID = {$ID}",
						false,
						'FILE: '.__FILE__.'<br /> LINE: '.__LINE__
					);
				};
			}

			$result = $arFields['ID'] = $ID;

			if(defined('BX_COMP_MANAGED_CACHE'))
			{
				$GLOBALS['CACHE_MANAGER']->CleanDir('b_crm_company');
			}

			CCrmPerms::UpdateEntityAttr('COMPANY', $ID, $arEntityAttr);

			//Statistics & History -->
			Bitrix\Crm\Statistics\CompanyGrowthStatisticEntry::register($ID, $arFields);
			//<-- Statistics & History

			if($isRestoration)
			{
				Bitrix\Crm\Timeline\CompanyController::getInstance()->onRestore($ID, array('FIELDS' => $arFields));
			}
			else
			{
				Bitrix\Crm\Timeline\CompanyController::getInstance()->onCreate($ID, array('FIELDS' => $arFields));
			}

			DuplicateOrganizationCriterion::register(CCrmOwnerType::Company, $ID, $arFields['TITLE']);

			EntityAddress::register(
				CCrmOwnerType::Company,
				$ID,
				EntityAddressType::Primary,
				array(
					'ADDRESS_1' => isset($arFields['ADDRESS']) ? $arFields['ADDRESS'] : null,
					'ADDRESS_2' => isset($arFields['ADDRESS_2']) ? $arFields['ADDRESS_2'] : null,
					'CITY' => isset($arFields['ADDRESS_CITY']) ? $arFields['ADDRESS_CITY'] : null,
					'POSTAL_CODE' => isset($arFields['ADDRESS_POSTAL_CODE']) ? $arFields['ADDRESS_POSTAL_CODE'] : null,
					'REGION' => isset($arFields['ADDRESS_REGION']) ? $arFields['ADDRESS_REGION'] : null,
					'PROVINCE' => isset($arFields['ADDRESS_PROVINCE']) ? $arFields['ADDRESS_PROVINCE'] : null,
					'COUNTRY' => isset($arFields['ADDRESS_COUNTRY']) ? $arFields['ADDRESS_COUNTRY'] : null,
					'COUNTRY_CODE' => isset($arFields['ADDRESS_COUNTRY_CODE']) ? $arFields['ADDRESS_COUNTRY_CODE'] : null,
					'LOC_ADDR_ID' => isset($arFields['ADDRESS_LOC_ADDR_ID']) ? (int)$arFields['ADDRESS_LOC_ADDR_ID'] : 0,
					'LOC_ADDR' => isset($arFields['ADDRESS_LOC_ADDR']) ? $arFields['ADDRESS_LOC_ADDR'] : null
				)
			);

			EntityAddress::register(
				CCrmOwnerType::Company,
				$ID,
				EntityAddressType::Registered,
				array(
					'ADDRESS_1' => isset($arFields['REG_ADDRESS']) ? $arFields['REG_ADDRESS'] : null,
					'ADDRESS_2' => isset($arFields['REG_ADDRESS_2']) ? $arFields['REG_ADDRESS_2'] : null,
					'CITY' => isset($arFields['REG_ADDRESS_CITY']) ? $arFields['REG_ADDRESS_CITY'] : null,
					'POSTAL_CODE' => isset($arFields['REG_ADDRESS_POSTAL_CODE']) ? $arFields['REG_ADDRESS_POSTAL_CODE'] : null,
					'REGION' => isset($arFields['REG_ADDRESS_REGION']) ? $arFields['REG_ADDRESS_REGION'] : null,
					'PROVINCE' => isset($arFields['REG_ADDRESS_PROVINCE']) ? $arFields['REG_ADDRESS_PROVINCE'] : null,
					'COUNTRY' => isset($arFields['REG_ADDRESS_COUNTRY']) ? $arFields['REG_ADDRESS_COUNTRY'] : null,
					'COUNTRY_CODE' => isset($arFields['REG_ADDRESS_COUNTRY_CODE']) ? $arFields['REG_ADDRESS_COUNTRY_CODE'] : null,
					'LOC_ADDR_ID' => isset($arFields['REG_ADDRESS_LOC_ADDR_ID']) ? (int)$arFields['REG_ADDRESS_LOC_ADDR_ID'] : 0,
					'LOC_ADDR' => isset($arFields['REG_ADDRESS_LOC_ADDR']) ? $arFields['REG_ADDRESS_LOC_ADDR'] : null
				)
			);

			CCrmEntityHelper::NormalizeUserFields($arFields, self::$sUFEntityID, $GLOBALS['USER_FIELD_MANAGER'], array('IS_NEW' => true));
			$GLOBALS['USER_FIELD_MANAGER']->Update(self::$sUFEntityID, $ID, $arFields);

			//region Duplicate communication data
			if (isset($arFields['FM']) && is_array($arFields['FM']))
			{
				$CCrmFieldMulti = new CCrmFieldMulti();
				$CCrmFieldMulti->SetFields('COMPANY', $ID, $arFields['FM']);
				$duplicateCommData = DuplicateCommunicationCriterion::prepareBulkData($arFields['FM']);
				if(!empty($duplicateCommData))
				{
					DuplicateCommunicationCriterion::bulkRegister(CCrmOwnerType::Company, $ID, $duplicateCommData);
				}
			}
			//endregion
			DuplicateEntityRanking::registerEntityStatistics(CCrmOwnerType::Company, $ID, $arFields);

			// tracking of entity
			Tracking\Entity::onAfterAdd(CCrmOwnerType::Company, $ID, $arFields);

			if($bUpdateSearch)
			{
				CCrmSearch::UpdateSearch(array('ID' => $ID, 'CHECK_PERMISSIONS' => 'N'), 'COMPANY', true);
			}
			if (isset($arFields['CONTACT_ID']) && is_array($arFields['CONTACT_ID']))
			{
					\Bitrix\Crm\Binding\ContactCompanyTable::bindContactIDs($arFields['ID'], $arFields['CONTACT_ID']);
					if (isset($GLOBALS["USER"]))
					{
						if (!class_exists('CUserOptions'))
							include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/classes/'.$GLOBALS['DBType'].'/favorites.php');

						CUserOptions::SetOption('crm', 'crm_contact_search', array('last_selected' => implode(',', $arFields['CONTACT_ID'])));
					}
			}

			//region Search content index
			Bitrix\Crm\Search\SearchContentBuilderFactory::create(
				CCrmOwnerType::Company
			)->build($ID, ['checkExist' => true]);
			//endregion

			if(isset($options['REGISTER_SONET_EVENT']) && $options['REGISTER_SONET_EVENT'] === true)
			{
				$revenue = round((isset($arFields['REVENUE']) ? doubleval($arFields['REVENUE']) : 0.0), 2);
				$currencyID = isset($arFields['CURRENCY_ID']) ? $arFields['CURRENCY_ID'] : '';
				if($currencyID === '')
				{
					$currencyID = CCrmCurrency::GetBaseCurrencyID();
				}

				$multiFields = isset($arFields['FM']) ? $arFields['FM'] : null;
				$phones = CCrmFieldMulti::ExtractValues($multiFields, 'PHONE');
				$emails = CCrmFieldMulti::ExtractValues($multiFields, 'EMAIL');
				$assignedByID = intval($arFields['ASSIGNED_BY_ID']);
				$createdByID = intval($arFields['CREATED_BY_ID']);

				$liveFeedFields = array(
					'USER_ID' => $createdByID,
					'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
					'ENTITY_ID' => $ID,
					'TITLE' => GetMessage('CRM_COMPANY_EVENT_ADD'),
					'MESSAGE' => '',
					'PARAMS' => array(
						'TITLE' => $arFields['TITLE'],
						'LOGO_ID' => isset($arFields['LOGO']) ? $arFields['LOGO'] : '',
						'TYPE' => isset($arFields['COMPANY_TYPE']) ? $arFields['COMPANY_TYPE'] : '',
						'REVENUE' => strval($revenue),
						'CURRENCY_ID' => $currencyID,
						'PHONES' => $phones,
						'EMAILS' => $emails,
						'AUTHOR_ID' => intval($arFields['CREATED_BY_ID']),
						'RESPONSIBLE_ID' => $assignedByID
					)
				);
				CCrmSonetSubscription::RegisterSubscription(
					CCrmOwnerType::Company,
					$ID,
					CCrmSonetSubscriptionType::Responsibility,
					$assignedByID
				);
				$logEventID = CCrmLiveFeed::CreateLogEvent($liveFeedFields, CCrmLiveFeedEvent::Add, array('CURRENT_USER' => $userID));

				if (
					$logEventID
					&& $assignedByID != $createdByID
					&& CModule::IncludeModule("im")
				)
				{
					$url = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Company, $ID);
					$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && SITE_SERVER_NAME <> '') ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));

					$arMessageFields = array(
						"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
						"TO_USER_ID" => $assignedByID,
						"FROM_USER_ID" => $createdByID,
						"NOTIFY_TYPE" => IM_NOTIFY_FROM,
						"NOTIFY_MODULE" => "crm",
						"LOG_ID" => $logEventID,
						"NOTIFY_EVENT" => "company_add",
						"NOTIFY_TAG" => "CRM|COMPANY_RESPONSIBLE|".$ID,
						"NOTIFY_MESSAGE" => GetMessage("CRM_COMPANY_RESPONSIBLE_IM_NOTIFY", Array("#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arFields['TITLE'])."</a>")),
						"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_COMPANY_RESPONSIBLE_IM_NOTIFY", Array("#title#" => htmlspecialcharsbx($arFields['TITLE'])))." (".$serverName.$url.")"
					);
					CIMNotify::Add($arMessageFields);
				}
			}

			$afterEvents = GetModuleEvents('crm', 'OnAfterCrmCompanyAdd');
			while ($arEvent = $afterEvents->Fetch())
			{
				ExecuteModuleEventEx($arEvent, array(&$arFields));
			}

			if(isset($arFields['ORIGIN_ID']) && $arFields['ORIGIN_ID'] !== '')
			{
				$afterEvents = GetModuleEvents('crm', 'OnAfterExternalCrmCompanyAdd');
				while ($arEvent = $afterEvents->Fetch())
				{
					ExecuteModuleEventEx($arEvent, array(&$arFields));
				}
			}
		}

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

		if(isset($arAttr['IS_MY_COMPANY']) && $arAttr['IS_MY_COMPANY'] == 'Y')
		{
			$arResult[] = CCrmPerms::ATTR_READ_ALL;
		}

		$arUserAttr = CCrmPerms::BuildUserEntityAttr($userID);
		return array_merge($arResult, $arUserAttr['INTRANET']);
	}
	private function PrepareEntityAttrs(&$arEntityAttr, $entityPermType)
	{
		// Ensure that entity accessible for user restricted by BX_CRM_PERM_OPEN
		if($entityPermType === BX_CRM_PERM_OPEN && !in_array('O', $arEntityAttr, true))
		{
			$arEntityAttr[] = 'O';
		}
	}

	public function Update($ID, array &$arFields, $bCompare = true, $bUpdateSearch = true, $arOptions = array())
	{
		global $DB;

		$this->LAST_ERROR = '';
		$this->checkExceptions = [];

		$ID = (int) $ID;
		if(!is_array($arOptions))
		{
			$arOptions = array();
		}
		$isSystemAction = isset($options['IS_SYSTEM_ACTION']) && $arOptions['IS_SYSTEM_ACTION'];

		$arFilterTmp = Array('ID' => $ID);
		if (!$this->bCheckPermission)
		{
			$arFilterTmp['CHECK_PERMISSIONS'] = 'N';
		}

		$obRes = self::GetListEx(array(), $arFilterTmp);
		if (!($arRow = $obRes->Fetch()))
		{
			return false;
		}

		if(isset($arOptions['CURRENT_USER']))
		{
			$iUserId = intval($arOptions['CURRENT_USER']);
		}
		else
		{
			$iUserId = CCrmSecurityHelper::GetCurrentUserID();
		}

		if (isset($arFields['DATE_CREATE']))
		{
			unset($arFields['DATE_CREATE']);
		}

		if (isset($arFields['DATE_MODIFY']))
		{
			unset($arFields['DATE_MODIFY']);
		}

		if(isset($arFields['TITLE']) && trim($arFields['TITLE']) === '')
		{
			unset($arFields['TITLE']);
		}

		if(!$isSystemAction)
		{
			$arFields['~DATE_MODIFY'] = $DB->CurrentTimeFunction();
			if(!isset($arFields['MODIFY_BY_ID']) || $arFields['MODIFY_BY_ID'] <= 0)
			{
				$arFields['MODIFY_BY_ID'] = $iUserId;
			}
		}

		if (isset($arFields['ASSIGNED_BY_ID']) && $arFields['ASSIGNED_BY_ID'] <= 0)
		{
			unset($arFields['ASSIGNED_BY_ID']);
		}

		if (isset($arFields['REVENUE']))
		{
			$arFields['REVENUE'] = floatval($arFields['REVENUE']);
		}

		if(isset($arFields['IS_MY_COMPANY']))
		{
			$arFields['IS_MY_COMPANY'] = $arFields['IS_MY_COMPANY'] === 'Y' ?'Y' : 'N';
		}

		$assignedByID = (int)(isset($arFields['ASSIGNED_BY_ID']) ? $arFields['ASSIGNED_BY_ID'] : $arRow['ASSIGNED_BY_ID']);

		$bResult = false;

		$options['CURRENT_FIELDS'] = $arRow;
		if (!$this->CheckFields($arFields, $ID, $arOptions))
		{
			$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
		}
		else
		{
			if($this->bCheckPermission && !CCrmAuthorizationHelper::CheckUpdatePermission(self::$TYPE_NAME, $ID, $this->cPerms))
			{
				$this->LAST_ERROR = GetMessage('CRM_PERMISSION_DENIED');
				$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
				return false;
			}

			if(!isset($arFields['ID']))
			{
				$arFields['ID'] = $ID;
			}

			$beforeEvents = GetModuleEvents('crm', 'OnBeforeCrmCompanyUpdate');
			while ($arEvent = $beforeEvents->Fetch())
			{
				if(ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
				{
					if(isset($arFields['RESULT_MESSAGE']))
					{
						$this->LAST_ERROR = $arFields['RESULT_MESSAGE'];
					}
					else
					{
						$this->LAST_ERROR = GetMessage('CRM_COMPANY_UPDATE_CANCELED', array('#NAME#' => $arEvent['TO_NAME']));
						$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
					}
					return false;
				}
			}

			$arAttr = array();
			$arAttr['OPENED'] = !empty($arFields['OPENED']) ? $arFields['OPENED'] : $arRow['OPENED'];
			$arAttr['IS_MY_COMPANY'] = !empty($arFields['IS_MY_COMPANY']) ? $arFields['IS_MY_COMPANY'] : $arRow['IS_MY_COMPANY'];
			$arEntityAttr = self::BuildEntityAttr($assignedByID, $arAttr);
			if($this->bCheckPermission)
			{
				$sEntityPerm = $this->cPerms->GetPermType('COMPANY', 'WRITE', $arEntityAttr);
				//HACK: Ensure that entity accessible for user restricted by BX_CRM_PERM_OPEN
				$this->PrepareEntityAttrs($arEntityAttr, $sEntityPerm);
				//HACK: Prevent 'OPENED' field change by user restricted by BX_CRM_PERM_OPEN permission
				if($sEntityPerm === BX_CRM_PERM_OPEN && isset($arFields['OPENED']) && $arFields['OPENED'] !== 'Y' && $assignedByID !== $iUserId)
				{
					$arFields['OPENED'] = 'Y';
				}
			}

			if(isset($arFields['LOGO']))
			{
				if(is_numeric($arFields['LOGO']) && $arFields['LOGO'] > 0)
				{
					//New file editor (file is already saved)
					if(isset($arFields['LOGO_del']) && $arFields['LOGO_del'] > 0)
					{
						CFile::Delete($arFields['LOGO_del']);
						if($arFields['LOGO'] == $arFields['LOGO_del'])
						{
							$arFields['LOGO'] = '';
						}
					}
				}
				elseif(is_array($arFields['LOGO']) && CFile::CheckImageFile($arFields['LOGO']) == '')
				{
					//Old file editor (file id is not saved yet)
					$arFields['LOGO']['MODULE_ID'] = 'crm';
					if($arFields['LOGO_del'] == 'Y' && !empty($arRow['LOGO']))
						CFile::Delete($arRow['LOGO']);
					CFile::SaveForDB($arFields, 'LOGO', 'crm');
					if($arFields['LOGO_del'] == 'Y' && !isset($arFields['LOGO']))
						$arFields['LOGO'] = '';
				}
			}

			$sonetEventData = array();
			if ($bCompare)
			{
				$res = CCrmFieldMulti::GetList(
					array('ID' => 'asc'),
					array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $ID)
				);
				$arRow['FM'] = array();
				while($ar = $res->Fetch())
					$arRow['FM'][$ar['TYPE_ID']][$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);

				$arEvents = self::CompareFields($arRow, $arFields, $arOptions);
				foreach($arEvents as $arEvent)
				{
					$arEvent['ENTITY_TYPE'] = 'COMPANY';
					$arEvent['ENTITY_ID'] = $ID;
					$arEvent['EVENT_TYPE'] = 1;

					if(!isset($arEvent['USER_ID']))
					{
						if($iUserId > 0)
						{
							$arEvent['USER_ID'] = $iUserId;
						}
						else if(isset($arFields['MODIFY_BY_ID']) && $arFields['MODIFY_BY_ID'] > 0)
						{
							$arEvent['USER_ID'] = $arFields['MODIFY_BY_ID'];
						}
						else if(isset($arOptions['CURRENT_USER']))
						{
							$arEvent['USER_ID'] = (int)$arOptions['CURRENT_USER'];
						}
					}

					$CCrmEvent = new CCrmEvent();
					$eventID = $CCrmEvent->Add($arEvent, $this->bCheckPermission);
					if(is_int($eventID) && $eventID > 0)
					{
						$fieldID = isset($arEvent['ENTITY_FIELD']) ? $arEvent['ENTITY_FIELD'] : '';
						if($fieldID === '')
						{
							continue;
						}

						switch($fieldID)
						{
							case 'ASSIGNED_BY_ID':
							{
								$sonetEventData[] = array(
									'TYPE' => CCrmLiveFeedEvent::Responsible,
									'FIELDS' => array(
										//'EVENT_ID' => $eventID,
										'TITLE' => GetMessage('CRM_COMPANY_EVENT_UPDATE_ASSIGNED_BY'),
										'MESSAGE' => '',
										'PARAMS' => array(
											'START_RESPONSIBLE_ID' => $arRow['ASSIGNED_BY_ID'],
											'FINAL_RESPONSIBLE_ID' => $arFields['ASSIGNED_BY_ID']
										)
									)
								);
							}
							break;
							case 'TITLE':
							{
								$sonetEventData[] = array(
									'TYPE' => CCrmLiveFeedEvent::Denomination,
									'FIELDS' => array(
										//'EVENT_ID' => $eventID,
										'TITLE' => GetMessage('CRM_COMPANY_EVENT_UPDATE_TITLE'),
										'MESSAGE' => '',
										'PARAMS' => array(
											'START_TITLE' => $arRow['TITLE'],
											'FINAL_TITLE' => $arFields['TITLE']
										)
									)
								);
							}
							break;
						}
					}
				}
			}

			if(isset($arFields['HAS_EMAIL']))
			{
				unset($arFields['HAS_EMAIL']);
			}

			if(isset($arFields['HAS_PHONE']))
			{
				unset($arFields['HAS_PHONE']);
			}

			if(isset($arFields['HAS_IMOL']))
			{
				unset($arFields['HAS_IMOL']);
			}

			unset($arFields["ID"]);
			$sUpdate = $DB->PrepareUpdate('b_crm_company', $arFields, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			if ($sUpdate <> '')
			{
				$DB->Query("UPDATE b_crm_company SET {$sUpdate} WHERE ID = {$ID}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
				$bResult = true;
				$newTitle = isset($arFields['TITLE']) ? $arFields['TITLE'] : '';
				$oldTitle = isset($arRow['TITLE']) ? $arRow['TITLE'] : '';
				if($newTitle !== '' && $newTitle !== $oldTitle)
				{
					DuplicateOrganizationCriterion::register(CCrmOwnerType::Company, $ID, $newTitle);
				}
			}

			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				static $arNameFields = array("TITLE");
				$bClear = false;
				foreach($arNameFields as $val)
				{
					if(isset($arFields[$val]))
					{
						$bClear = true;
						break;
					}
				}
				if ($bClear)
				{
					$GLOBALS["CACHE_MANAGER"]->ClearByTag("crm_entity_name_".CCrmOwnerType::Company."_".$ID);
				}
			}

			CCrmPerms::UpdateEntityAttr('COMPANY', $ID, $arEntityAttr);

			if(isset($arFields['ADDRESS'])
				|| isset($arFields['ADDRESS_2'])
				|| isset($arFields['ADDRESS_CITY'])
				|| isset($arFields['ADDRESS_POSTAL_CODE'])
				|| isset($arFields['ADDRESS_REGION'])
				|| isset($arFields['ADDRESS_PROVINCE'])
				|| isset($arFields['ADDRESS_COUNTRY'])
				|| isset($arFields['ADDRESS_LOC_ADDR_ID']))
			{
				EntityAddress::register(
					CCrmOwnerType::Company,
					$ID,
					EntityAddressType::Primary,
					array(
						'ADDRESS_1' => isset($arFields['ADDRESS'])
							? $arFields['ADDRESS'] : (isset($arRow['ADDRESS']) ? $arRow['ADDRESS'] : null),
						'ADDRESS_2' => isset($arFields['ADDRESS_2'])
							? $arFields['ADDRESS_2'] : (isset($arRow['ADDRESS_2']) ? $arRow['ADDRESS_2'] : null),
						'CITY' => isset($arFields['ADDRESS_CITY'])
							? $arFields['ADDRESS_CITY'] : (isset($arRow['ADDRESS_CITY']) ? $arRow['ADDRESS_CITY'] : null),
						'POSTAL_CODE' => isset($arFields['ADDRESS_POSTAL_CODE'])
							? $arFields['ADDRESS_POSTAL_CODE'] : (isset($arRow['ADDRESS_POSTAL_CODE']) ? $arRow['ADDRESS_POSTAL_CODE'] : null),
						'REGION' => isset($arFields['ADDRESS_REGION'])
							? $arFields['ADDRESS_REGION'] : (isset($arRow['ADDRESS_REGION']) ? $arRow['ADDRESS_REGION'] : null),
						'PROVINCE' => isset($arFields['ADDRESS_PROVINCE'])
							? $arFields['ADDRESS_PROVINCE'] : (isset($arRow['ADDRESS_PROVINCE']) ? $arRow['ADDRESS_PROVINCE'] : null),
						'COUNTRY' => isset($arFields['ADDRESS_COUNTRY'])
							? $arFields['ADDRESS_COUNTRY'] : (isset($arRow['ADDRESS_COUNTRY']) ? $arRow['ADDRESS_COUNTRY'] : null),
						'COUNTRY_CODE' => isset($arFields['ADDRESS_COUNTRY_CODE'])
							? $arFields['ADDRESS_COUNTRY_CODE'] : (isset($arRow['ADDRESS_COUNTRY_CODE']) ? $arRow['ADDRESS_COUNTRY_CODE'] : null),
						'LOC_ADDR_ID' => isset($arFields['ADDRESS_LOC_ADDR_ID'])
							? (int)$arFields['ADDRESS_LOC_ADDR_ID'] : (isset($arRow['ADDRESS_LOC_ADDR_ID']) ? (int)$arRow['ADDRESS_LOC_ADDR_ID'] : 0),
						'LOC_ADDR' => isset($arFields['ADDRESS_LOC_ADDR']) ? $arFields['ADDRESS_LOC_ADDR'] : null
					),
					[
						'updateLocationAddress' => !(
							(isset($arFields['ADDRESS_LOC_ADDR_ID']) && $arFields['ADDRESS_LOC_ADDR_ID'] > 0) ||
							(isset($arFields['ADDRESS_LOC_ADDR']) && is_object($arFields['ADDRESS_LOC_ADDR']))
						)
					]
				);
			}

			if(isset($arFields['REG_ADDRESS'])
				|| isset($arFields['REG_ADDRESS_2'])
				|| isset($arFields['REG_ADDRESS_CITY'])
				|| isset($arFields['REG_ADDRESS_POSTAL_CODE'])
				|| isset($arFields['REG_ADDRESS_REGION'])
				|| isset($arFields['REG_ADDRESS_PROVINCE'])
				|| isset($arFields['REG_ADDRESS_COUNTRY'])
				|| isset($arFields['REG_ADDRESS_LOC_ADDR_ID']))
			{
				EntityAddress::register(
					CCrmOwnerType::Company,
					$ID,
					EntityAddressType::Registered,
					array(
						'ADDRESS_1' => isset($arFields['REG_ADDRESS'])
							? $arFields['REG_ADDRESS'] : (isset($arRow['REG_ADDRESS']) ? $arRow['REG_ADDRESS'] : null),
						'ADDRESS_2' => isset($arFields['REG_ADDRESS_2'])
							? $arFields['REG_ADDRESS_2'] : (isset($arRow['REG_ADDRESS_2']) ? $arRow['REG_ADDRESS_2'] : null),
						'CITY' => isset($arFields['REG_ADDRESS_CITY'])
							? $arFields['REG_ADDRESS_CITY'] : (isset($arRow['REG_ADDRESS_CITY']) ? $arRow['REG_ADDRESS_CITY'] : null),
						'POSTAL_CODE' => isset($arFields['REG_ADDRESS_POSTAL_CODE'])
							? $arFields['REG_ADDRESS_POSTAL_CODE'] : (isset($arRow['REG_ADDRESS_POSTAL_CODE']) ? $arRow['REG_ADDRESS_POSTAL_CODE'] : null),
						'REGION' => isset($arFields['REG_ADDRESS_REGION'])
							? $arFields['REG_ADDRESS_REGION'] : (isset($arRow['REG_ADDRESS_REGION']) ? $arRow['REG_ADDRESS_REGION'] : null),
						'PROVINCE' => isset($arFields['REG_ADDRESS_PROVINCE'])
							? $arFields['REG_ADDRESS_PROVINCE'] : (isset($arRow['REG_ADDRESS_PROVINCE']) ? $arRow['REG_ADDRESS_PROVINCE'] : null),
						'COUNTRY' => isset($arFields['REG_ADDRESS_COUNTRY'])
							? $arFields['REG_ADDRESS_COUNTRY'] : (isset($arRow['REG_ADDRESS_COUNTRY']) ? $arRow['REG_ADDRESS_COUNTRY'] : null),
						'COUNTRY_CODE' => isset($arFields['REG_ADDRESS_COUNTRY_CODE'])
							? $arFields['REG_ADDRESS_COUNTRY_CODE'] : (isset($arRow['REG_ADDRESS_COUNTRY_CODE']) ? $arRow['REG_ADDRESS_COUNTRY_CODE'] : null),
						'LOC_ADDR_ID' => isset($arFields['REG_ADDRESS_LOC_ADDR_ID'])
							? (int)$arFields['REG_ADDRESS_LOC_ADDR_ID'] : (isset($arRow['REG_ADDRESS_LOC_ADDR_ID']) ? (int)$arRow['REG_ADDRESS_LOC_ADDR_ID'] : 0),
						'LOC_ADDR' => isset($arFields['REG_ADDRESS_LOC_ADDR']) ? $arFields['REG_ADDRESS_LOC_ADDR'] : null
					),
					[
						'updateLocationAddress' => !(
							(isset($arFields['REG_ADDRESS_LOC_ADDR_ID']) && $arFields['REG_ADDRESS_LOC_ADDR_ID'] > 0) ||
							(isset($arFields['REG_ADDRESS_LOC_ADDR']) && is_object($arFields['REG_ADDRESS_LOC_ADDR']))
						)
					]
				);
			}

			CCrmEntityHelper::NormalizeUserFields($arFields, self::$sUFEntityID, $GLOBALS['USER_FIELD_MANAGER'], array('IS_NEW' => false));
			$GLOBALS['USER_FIELD_MANAGER']->Update(self::$sUFEntityID, $ID, $arFields);

			//Statistics & History -->
			$oldLeadID = isset($arRow['LEAD_ID']) ? (int)$arRow['LEAD_ID'] : 0;
			$curLeadID = isset($arFields['LEAD_ID']) ? (int)$arFields['LEAD_ID'] : $oldLeadID;
			if($oldLeadID != $curLeadID)
			{
				if($oldLeadID > 0)
				{
					Bitrix\Crm\Statistics\LeadConversionStatisticsEntry::processBindingsChange($oldLeadID);
				}

				if($curLeadID > 0)
				{
					Bitrix\Crm\Statistics\LeadConversionStatisticsEntry::processBindingsChange($curLeadID);
				}
			}

			$enableDupIndexInvalidation = isset($arOptions['ENABLE_DUP_INDEX_INVALIDATION'])
				? (bool)$arOptions['ENABLE_DUP_INDEX_INVALIDATION'] : true;
			if(!$isSystemAction && $enableDupIndexInvalidation)
			{
				\Bitrix\Crm\Integrity\DuplicateManager::markDuplicateIndexAsDirty(CCrmOwnerType::Company, $ID);
			}

			Bitrix\Crm\Statistics\CompanyGrowthStatisticEntry::synchronize($ID, array(
				'ASSIGNED_BY_ID' => $assignedByID
			));
			Crm\Activity\CommunicationStatistics::synchronizeByOwner(\CCrmOwnerType::Company, $ID, array(
				'ASSIGNED_BY_ID' => $assignedByID
			));
			//<-- Statistics & History

			if($bResult)
			{
				$previousAssignedByID = isset($arRow['ASSIGNED_BY_ID']) ? (int)$arRow['ASSIGNED_BY_ID'] : 0;
				if(($assignedByID > 0 || $previousAssignedByID > 0) || $assignedByID !== $previousAssignedByID)
				{
					$assignedByIDs = array();
					if($assignedByID > 0)
					{
						$assignedByIDs[] = $assignedByID;
					}
					if($previousAssignedByID > 0)
					{
						$assignedByIDs[] = $previousAssignedByID;
					}

					EntityCounterManager::reset(
						EntityCounterManager::prepareCodes(
							CCrmOwnerType::Company,
							array(
								EntityCounterType::PENDING,
								EntityCounterType::OVERDUE,
								EntityCounterType::ALL
							)
						),
						$assignedByIDs
					);
				}
				if ($assignedByID !== $previousAssignedByID && $enableDupIndexInvalidation)
				{
					\Bitrix\Crm\Integrity\DuplicateManager::onChangeEntityAssignedBy(CCrmOwnerType::Company, $ID);
				}
			}

			if (isset($arFields['FM']) && is_array($arFields['FM']))
			{
				$CCrmFieldMulti = new CCrmFieldMulti();
				$CCrmFieldMulti->SetFields(CCrmOwnerType::CompanyName, $ID, $arFields['FM']);

				$multifields = DuplicateCommunicationCriterion::prepareEntityMultifieldValues(
					CCrmOwnerType::Company,
					$ID
				);

				DuplicateCommunicationCriterion::bulkRegister(
					CCrmOwnerType::Company,
					$ID,
					DuplicateCommunicationCriterion::prepareBulkData($multifields)
				);

				$hasEmail = CCrmFieldMulti::HasValues($multifields, CCrmFieldMulti::EMAIL) ? 'Y' : 'N';
				$hasPhone = CCrmFieldMulti::HasValues($multifields, CCrmFieldMulti::PHONE) ? 'Y' : 'N';
				$hasImol = CCrmFieldMulti::HasImolValues($multifields) ? 'Y' : 'N';
				if(
					$hasEmail !== (isset($arRow['HAS_EMAIL']) ? $arRow['HAS_EMAIL'] : 'N')
					||
					$hasPhone !== (isset($arRow['HAS_PHONE']) ? $arRow['HAS_PHONE'] : 'N')
					||
					$hasImol !== (isset($arRow['HAS_IMOL']) ? $arRow['HAS_IMOL'] : 'N')
				)
				{
					$DB->Query("UPDATE b_crm_company SET HAS_EMAIL = '{$hasEmail}', HAS_PHONE = '{$hasPhone}', HAS_IMOL = '{$hasImol}' WHERE ID = {$ID}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);

					$arFields['HAS_EMAIL'] = $hasEmail;
					$arFields['HAS_PHONE'] = $hasPhone;
					$arFields['HAS_IMOL'] = $hasImol;
				}
			}

			DuplicateEntityRanking::registerEntityStatistics(CCrmOwnerType::Company, $ID, array_merge($arRow, $arFields));

			// update utm fields
			UtmTable::updateEntityUtmFromFields(CCrmOwnerType::Company, $ID, $arFields);

			if($bUpdateSearch)
			{
				CCrmSearch::UpdateSearch(array('ID' => $ID, 'CHECK_PERMISSIONS' => 'N'), 'COMPANY', true);
			}

			$arFields['ID'] = $ID;
			if (isset($arFields['CONTACT_ID']) && is_array($arFields['CONTACT_ID']))
			{
				$arFields['CONTACT_ID'] = array_filter($arFields['CONTACT_ID']);
				if (empty($arFields['CONTACT_ID']))
				{
					\Bitrix\Crm\Binding\ContactCompanyTable::unbindAllContacts($arFields['ID']);
				}
				else
				{
					$arCurrentContact = \Bitrix\Crm\Binding\ContactCompanyTable::getCompanyContactIDs($arFields['ID']);
					$arAdd = array_diff($arFields['CONTACT_ID'], $arCurrentContact);
					$arDelete = array_diff($arCurrentContact, $arFields['CONTACT_ID']);

					\Bitrix\Crm\Binding\ContactCompanyTable::bindContactIDs($arFields['ID'], $arAdd);
					\Bitrix\Crm\Binding\ContactCompanyTable::unbindContactIDs($arFields['ID'], $arDelete);

					if (isset($GLOBALS["USER"]))
					{
						if (!class_exists('CUserOptions'))
							include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/classes/".$GLOBALS['DBType']."/favorites.php");

						CUserOptions::SetOption("crm", "crm_contact_search", array('last_selected' => implode(',', $arAdd)));
					}
				}
			}

			//region Search content index
			Bitrix\Crm\Search\SearchContentBuilderFactory::create(CCrmOwnerType::Company)
				->build($ID, ['checkExist' => true]);
			//endregion

			Bitrix\Crm\Timeline\CompanyController::getInstance()->onModify(
				$ID,
				array(
					'CURRENT_FIELDS' => $arFields,
					'PREVIOUS_FIELDS' => $arRow,
					'OPTIONS' => $arOptions
				)
			);

			$registerSonetEvent = isset($arOptions['REGISTER_SONET_EVENT']) && $arOptions['REGISTER_SONET_EVENT'] === true;

			if($bResult && isset($arFields['ASSIGNED_BY_ID']))
			{
				CCrmSonetSubscription::ReplaceSubscriptionByEntity(
					CCrmOwnerType::Company,
					$ID,
					CCrmSonetSubscriptionType::Responsibility,
					$arFields['ASSIGNED_BY_ID'],
					$arRow['ASSIGNED_BY_ID'],
					$registerSonetEvent
				);
			}

			if($bResult && $bCompare && $registerSonetEvent && !empty($sonetEventData))
			{
				$modifiedByID = intval($arFields['MODIFY_BY_ID']);
				foreach($sonetEventData as &$sonetEvent)
				{
					$sonetEventFields = &$sonetEvent['FIELDS'];
					$sonetEventFields['ENTITY_TYPE_ID'] = CCrmOwnerType::Company;
					$sonetEventFields['ENTITY_ID'] = $ID;
					$sonetEventFields['USER_ID'] = $modifiedByID;

					$logEventID = CCrmLiveFeed::CreateLogEvent($sonetEventFields, $sonetEvent['TYPE'], array('CURRENT_USER' => $iUserId));

					if (
						$logEventID
						&& $sonetEvent['TYPE'] == CCrmLiveFeedEvent::Responsible
						&& CModule::IncludeModule("im")
					)
					{
						$title = CCrmOwnerType::GetCaption(CCrmOwnerType::Company, $ID, false);
						$url = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Company, $ID);
						$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && SITE_SERVER_NAME <> '') ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));

						if ($sonetEventFields['PARAMS']['FINAL_RESPONSIBLE_ID'] != $modifiedByID)
						{
							$arMessageFields = array(
								"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
								"TO_USER_ID" => $sonetEventFields['PARAMS']['FINAL_RESPONSIBLE_ID'],
								"FROM_USER_ID" => $modifiedByID,
								"NOTIFY_TYPE" => IM_NOTIFY_FROM,
								"NOTIFY_MODULE" => "crm",
								"LOG_ID" => $logEventID,
								"NOTIFY_EVENT" => "company_update",
								"NOTIFY_TAG" => "CRM|COMPANY_RESPONSIBLE|".$ID,
								"NOTIFY_MESSAGE" => GetMessage("CRM_COMPANY_RESPONSIBLE_IM_NOTIFY", Array("#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($title)."</a>")),
								"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_COMPANY_RESPONSIBLE_IM_NOTIFY", Array("#title#" => htmlspecialcharsbx($title)))." (".$serverName.$url.")"
							);

							CIMNotify::Add($arMessageFields);
						}

						if ($sonetEventFields['PARAMS']['START_RESPONSIBLE_ID'] != $modifiedByID)
						{
							$arMessageFields = array(
								"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
								"TO_USER_ID" => $sonetEventFields['PARAMS']['START_RESPONSIBLE_ID'],
								"FROM_USER_ID" => $modifiedByID,
								"NOTIFY_TYPE" => IM_NOTIFY_FROM,
								"NOTIFY_MODULE" => "crm",
								"LOG_ID" => $logEventID,
								"NOTIFY_EVENT" => "company_update",
								"NOTIFY_TAG" => "CRM|COMPANY_RESPONSIBLE|".$ID,
								"NOTIFY_MESSAGE" => GetMessage("CRM_COMPANY_NOT_RESPONSIBLE_IM_NOTIFY", Array("#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($title)."</a>")),
								"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_COMPANY_NOT_RESPONSIBLE_IM_NOTIFY", Array("#title#" => htmlspecialcharsbx($title)))." (".$serverName.$url.")"
							);

							CIMNotify::Add($arMessageFields);
						}
					}

					unset($sonetEventFields);
				}
				unset($sonetEvent);
			}

			if($bResult)
			{
				$afterEvents = GetModuleEvents('crm', 'OnAfterCrmCompanyUpdate');
				while ($arEvent = $afterEvents->Fetch())
					ExecuteModuleEventEx($arEvent, array(&$arFields));
			}
		}
		return $bResult;
	}
	public static function RebuildEntityAccessAttrs($IDs)
	{
		if(!is_array($IDs))
		{
			$IDs = array($IDs);
		}

		$dbResult = self::GetListEx(
			array(),
			array('@ID' => $IDs, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'ASSIGNED_BY_ID', 'OPENED', 'IS_MY_COMPANY')
		);

		if(!is_object($dbResult))
		{
			return;
		}

		while($fields = $dbResult->Fetch())
		{
			$ID = intval($fields['ID']);
			$assignedByID = isset($fields['ASSIGNED_BY_ID']) ? intval($fields['ASSIGNED_BY_ID']) : 0;
			if($assignedByID <= 0)
			{
				continue;
			}

			$attrs = array();
			if(isset($fields['OPENED']))
			{
				$attrs['OPENED'] = $fields['OPENED'];
			}

			if(isset($fields['IS_MY_COMPANY']))
			{
				$attrs['IS_MY_COMPANY'] = $fields['IS_MY_COMPANY'];
			}

			$entityAttrs = self::BuildEntityAttr($assignedByID, $attrs);
			CCrmPerms::UpdateEntityAttr('COMPANY', $ID, $entityAttrs);
		}
	}

	public function Delete($ID, $arOptions = array())
	{
		global $DB, $APPLICATION;

		$ID = intval($ID);
		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		if(isset($arOptions['CURRENT_USER']))
		{
			$iUserId = intval($arOptions['CURRENT_USER']);
		}
		else
		{
			$iUserId = CCrmSecurityHelper::GetCurrentUserID();
		}

		$dbResult = \CCrmCompany::GetListEx(
			array(),
			array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N')
		);
		$arFields = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($arFields))
		{
			return false;
		}

		$assignedByID = isset($arFields['ASSIGNED_BY_ID']) ? (int)$arFields['ASSIGNED_BY_ID'] : 0;

		$sWherePerm = '';
		if ($this->bCheckPermission)
		{
			$arEntityAttr = $this->cPerms->GetEntityAttr('COMPANY', $ID);
			$sEntityPerm = $this->cPerms->GetPermType('COMPANY', 'DELETE', $arEntityAttr[$ID]);
			if ($sEntityPerm == BX_CRM_PERM_NONE)
				return false;
			else if ($sEntityPerm == BX_CRM_PERM_SELF)
				$sWherePerm = " AND ASSIGNED_BY_ID = {$iUserId}";
			else if ($sEntityPerm == BX_CRM_PERM_OPEN)
				$sWherePerm = " AND (OPENED = 'Y' OR ASSIGNED_BY_ID = {$iUserId})";
		}

		$APPLICATION->ResetException();
		$events = GetModuleEvents('crm', 'OnBeforeCrmCompanyDelete');
		while ($arEvent = $events->Fetch())
			if(ExecuteModuleEventEx($arEvent, array($ID))===false)
			{
				$err = GetMessage("MAIN_BEFORE_DEL_ERR").' '.$arEvent['TO_NAME'];
				if($ex = $APPLICATION->GetException())
					$err .= ': '.$ex->GetString();
				$APPLICATION->throwException($err);
				return false;
			}

		$enableDeferredMode = isset($arOptions['ENABLE_DEFERRED_MODE'])
			? (bool)$arOptions['ENABLE_DEFERRED_MODE']
			: \Bitrix\Crm\Settings\CompanySettings::getCurrent()->isDeferredCleaningEnabled();

		//By default we need to clean up related bizproc entities
		$processBizproc = isset($arOptions['PROCESS_BIZPROC']) ? (bool)$arOptions['PROCESS_BIZPROC'] : true;
		if($processBizproc)
		{
			$bizproc = new CCrmBizProc('COMPANY');
			$bizproc->ProcessDeletion($ID);
		}

		$enableRecycleBin = \Bitrix\Crm\Recycling\CompanyController::isEnabled()
			&& \Bitrix\Crm\Settings\CompanySettings::getCurrent()->isRecycleBinEnabled();
		if($enableRecycleBin)
		{
			\Bitrix\Crm\Recycling\CompanyController::getInstance()->moveToBin($ID, array('FIELDS' => $arFields));
		}

		$obRes = $DB->Query("DELETE FROM b_crm_company WHERE ID = {$ID}{$sWherePerm}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		if (is_object($obRes) && $obRes->AffectedRowsCount() > 0)
		{
			if(defined('BX_COMP_MANAGED_CACHE'))
			{
				$GLOBALS['CACHE_MANAGER']->CleanDir('b_crm_company');
			}

			if(!$enableRecycleBin)
			{
				self::ReleaseExternalResources($arFields);
			}

			CCrmSearch::DeleteSearch('COMPANY', $ID);

			$DB->Query("DELETE FROM b_crm_entity_perms WHERE ENTITY='COMPANY' AND ENTITY_ID = $ID", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			$GLOBALS['USER_FIELD_MANAGER']->Delete(self::$sUFEntityID, $ID);

			\Bitrix\Crm\Binding\ContactCompanyTable::unbindAllContacts($ID);

			if(!$enableDeferredMode)
			{
				$CCrmEvent = new CCrmEvent();
				$CCrmEvent->DeleteByElement('COMPANY', $ID);
			}
			else
			{
				Bitrix\Crm\Cleaning\CleaningManager::register(CCrmOwnerType::Company, $ID);
				if(!Bitrix\Crm\Agent\Routine\CleaningAgent::isActive())
				{
					Bitrix\Crm\Agent\Routine\CleaningAgent::activate();
				}
			}

			$enableDupIndexInvalidation = isset($arOptions['ENABLE_DUP_INDEX_INVALIDATION'])
				? (bool)$arOptions['ENABLE_DUP_INDEX_INVALIDATION'] : true;
			if($enableDupIndexInvalidation)
			{
				\Bitrix\Crm\Integrity\DuplicateManager::markDuplicateIndexAsJunk(CCrmOwnerType::Company, $ID);
			}

			DuplicateEntityRanking::unregisterEntityStatistics(CCrmOwnerType::Company, $ID);
			DuplicateOrganizationCriterion::unregister(CCrmOwnerType::Company, $ID);
			DuplicateCommunicationCriterion::unregister(CCrmOwnerType::Company, $ID);
			DuplicateIndexMismatch::unregisterEntity(CCrmOwnerType::Company, $ID);

			//Statistics & History -->
			$leadID = isset($arFields['LEAD_ID']) ? (int)$arFields['LEAD_ID'] : 0;
			if($leadID)
			{
				\Bitrix\Crm\Statistics\LeadConversionStatisticsEntry::processBindingsChange($leadID);
			}
			\Bitrix\Crm\Statistics\CompanyGrowthStatisticEntry::unregister($ID);
			//<-- Statistics & History

			if($assignedByID > 0)
			{
				EntityCounterManager::reset(
					EntityCounterManager::prepareCodes(
						CCrmOwnerType::Company,
						array(
							EntityCounterType::PENDING,
							EntityCounterType::OVERDUE,
							EntityCounterType::ALL
						)
					),
					array($assignedByID)
				);
			}

			CCrmActivity::DeleteByOwner(CCrmOwnerType::Company, $ID);

			if(!$enableRecycleBin)
			{
				$CCrmFieldMulti = new CCrmFieldMulti();
				$CCrmFieldMulti->DeleteByElement('COMPANY', $ID);

				EntityAddress::unregister(CCrmOwnerType::Company, $ID, EntityAddressType::Primary);
				EntityAddress::unregister(CCrmOwnerType::Company, $ID, EntityAddressType::Registered);
				\Bitrix\Crm\Timeline\TimelineEntry::deleteByOwner(CCrmOwnerType::Company, $ID);

				$requisite = new \Bitrix\Crm\EntityRequisite();
				$requisite->deleteByEntity(CCrmOwnerType::Company, $ID);
				unset($requisite);

				CCrmSonetSubscription::UnRegisterSubscriptionByEntity(CCrmOwnerType::Company, $ID);
				CCrmLiveFeed::DeleteLogEvents(
					array(
						'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
						'ENTITY_ID' => $ID
					)
				);
				UtmTable::deleteEntityUtm(CCrmOwnerType::Company, $ID);
				Tracking\Entity::deleteTrace(CCrmOwnerType::Company, $ID);
			}

			CCrmContact::ProcessCompanyDeletion($ID);
			CCrmLead::ProcessCompanyDeletion($ID);
			CCrmDeal::ProcessCompanyDeletion($ID);

			\Bitrix\Crm\Timeline\CompanyController::getInstance()->onDelete(
				$ID,
				array('FIELDS' => $arFields)
			);

			if(Bitrix\Crm\Settings\HistorySettings::getCurrent()->isCompanyDeletionEventEnabled())
			{
				CCrmEvent::RegisterDeleteEvent(CCrmOwnerType::Company, $ID, 0, array('FIELDS' => $arFields));
			}

			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("crm_entity_name_".CCrmOwnerType::Company."_".$ID);
			}

			$afterEvents = GetModuleEvents('crm', 'OnAfterCrmCompanyDelete');
			while ($arEvent = $afterEvents->Fetch())
			{
				ExecuteModuleEventEx($arEvent, array($ID));
			}
		}

		return true;
	}

	public static function ReleaseExternalResources(array $arFields)
	{
		$logoID = isset($arFields['LOGO']) ? (int)$arFields['LOGO'] : 0;
		if($logoID > 0)
		{
			\CFile::Delete($logoID);
		}
	}

	public function CheckFields(&$arFields, $ID = false, $options = array())
	{
		global $APPLICATION, $USER_FIELD_MANAGER;
		$this->LAST_ERROR = '';
		$this->checkExceptions = [];

		if (($ID == false || isset($arFields['TITLE'])) && empty($arFields['TITLE']))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_IS_MISSING', array('%FIELD_NAME%' => GetMessage('CRM_COMPANY_FIELD_TITLE')))."<br />";

		if (isset($arFields['FM']) && is_array($arFields['FM']))
		{
			$CCrmFieldMulti = new CCrmFieldMulti();
			if (!$CCrmFieldMulti->CheckComplexFields($arFields['FM']))
			{
				$this->LAST_ERROR .= $CCrmFieldMulti->LAST_ERROR;
			}
		}

		if (isset($arFields['LOGO']) && is_array($arFields['LOGO']))
		{
			if (($strError = CFile::CheckFile($arFields['LOGO'], 0, false, CFile::GetImageExtensions())) != '')
				$this->LAST_ERROR .= $strError."<br />";
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$isRestoration = isset($options['IS_RESTORATION']) && $options['IS_RESTORATION'];
		if($isRestoration)
		{
			$enableUserFieldCheck = false;
		}
		else
		{
			$enableUserFieldCheck = !(isset($options['DISABLE_USER_FIELD_CHECK'])
				&& $options['DISABLE_USER_FIELD_CHECK'] === true);
		}

		if ($enableUserFieldCheck)
		{
			// We have to prepare field data before check (issue #22966)
			CCrmEntityHelper::NormalizeUserFields(
				$arFields,
				self::$sUFEntityID,
				$USER_FIELD_MANAGER,
				array('IS_NEW' => ($ID == false))
			);

			$enableRequiredUserFieldCheck = !(isset($options['DISABLE_REQUIRED_USER_FIELD_CHECK'])
				&& $options['DISABLE_REQUIRED_USER_FIELD_CHECK'] === true);

			$fieldsToCheck = $arFields;
			$requiredFields = null;
			if($enableRequiredUserFieldCheck)
			{
				/* Comment before status support
				$currentFields = null;
				if($ID > 0)
				{
					if(isset($options['CURRENT_FIELDS']) && is_array($options['CURRENT_FIELDS']))
					{
						$currentFields = $options['CURRENT_FIELDS'];
					}
					else
					{
						$dbResult = self::GetListEx(
							array(),
							array('ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
							false,
							false,
							array('*', 'UF_*')
						);
						$currentFields = $dbResult->Fetch();
					}
				}

				if(is_array($currentFields))
				{
					CCrmEntityHelper::NormalizeUserFields(
						$currentFields,
						self::$sUFEntityID,
						$USER_FIELD_MANAGER,
						array('IS_NEW' => ($ID == false))
					);

					//If Status ID is changed we must perform check of all fields.
					if(isset($arFields['STATUS_ID']) && $arFields['STATUS_ID'] !== $currentFields['STATUS_ID'])
					{
						$fieldsToCheck = array_merge($currentFields, $arFields);
						if(self::GetSemanticID($arFields['STATUS_ID'], $currentFields['CATEGORY_ID']) ===
							Bitrix\Crm\PhaseSemantics::FAILURE)
						{
							//Disable required fields check for failure status due to backward compatibility.
							$enableRequiredUserFieldCheck = false;
						}
					}
					elseif(!isset($arFields['STATUS_ID']) && isset($currentFields['STATUS_ID']))
					{
						$fieldsToCheck = array_merge($arFields, array('STATUS_ID' => $currentFields['STATUS_ID']));
					}
				}*/

				$requiredFields = Crm\Attribute\FieldAttributeManager::getRequiredFields(
					CCrmOwnerType::Company,
					$ID,
					$fieldsToCheck,
					Crm\Attribute\FieldOrigin::UNDEFINED,
					is_array($options['FIELD_CHECK_OPTIONS']) ? $options['FIELD_CHECK_OPTIONS'] : array()
				);

				$requiredSystemFields = isset($requiredFields[Crm\Attribute\FieldOrigin::SYSTEM])
					? $requiredFields[Crm\Attribute\FieldOrigin::SYSTEM] : array();
				if(!empty($requiredSystemFields))
				{
					$validator = new Crm\Entity\CompanyValidator($ID, $fieldsToCheck);
					$validationErrors = array();
					foreach($requiredSystemFields as $fieldName)
					{
						$validator->checkFieldPresence($fieldName, $validationErrors);
					}

					if(!empty($validationErrors))
					{
						$e = new CAdminException($validationErrors);
						$this->checkExceptions[] = $e;
						$this->LAST_ERROR .= $e->GetString();
					}
				}
			}

			$requiredUserFields = is_array($requiredFields) && isset($requiredFields[Crm\Attribute\FieldOrigin::CUSTOM])
				? $requiredFields[Crm\Attribute\FieldOrigin::CUSTOM] : array();

			if (!$USER_FIELD_MANAGER->CheckFields(
				self::$sUFEntityID,
				$ID,
				$fieldsToCheck,
				false,
				$enableRequiredUserFieldCheck,
				$requiredUserFields))
			{
				$e = $APPLICATION->GetException();
				$this->checkExceptions[] = $e;
				$this->LAST_ERROR .= $e->GetString();
			}
		}

		return $this->LAST_ERROR === '';
	}

	public function GetCheckExceptions()
	{
		return $this->checkExceptions;
	}

	public static function CompareFields(array $arFieldsOrig, array $arFieldsModif, array $arOptions = null)
	{
		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		$arMsg = Array();

		if (isset($arFieldsOrig['TITLE']) && isset($arFieldsModif['TITLE'])
			&& $arFieldsOrig['TITLE'] != $arFieldsModif['TITLE'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'TITLE',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_TITLE'),
				'EVENT_TEXT_1' => $arFieldsOrig['TITLE'],
				'EVENT_TEXT_2' => $arFieldsModif['TITLE'],
			);

		if (isset($arFieldsOrig['FM']) && isset($arFieldsModif['FM']))
			$arMsg = array_merge($arMsg, CCrmFieldMulti::CompareFields($arFieldsOrig['FM'], $arFieldsModif['FM']));

		$addressOptions = array();
		if(isset($arOptions['ADDRESS_FIELDS']))
		{
			$addressOptions['FIELDS'] = $arOptions['ADDRESS_FIELDS'];
		}

		$arMsg = array_merge(
			$arMsg,
			CompanyAddress::prepareChangeEvents(
				$arFieldsOrig,
				$arFieldsModif,
				EntityAddressType::Primary,
				$addressOptions
			)
		);

		$arMsg = array_merge(
			$arMsg,
			CompanyAddress::prepareChangeEvents(
				$arFieldsOrig,
				$arFieldsModif,
				EntityAddressType::Registered,
				$addressOptions
			)
		);

		if (isset($arFieldsOrig['BANKING_DETAILS']) && isset($arFieldsModif['BANKING_DETAILS'])
			&& $arFieldsOrig['BANKING_DETAILS'] != $arFieldsModif['BANKING_DETAILS'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'BANKING_DETAILS',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_BANKING_DETAILS'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['BANKING_DETAILS'])? $arFieldsOrig['BANKING_DETAILS']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['BANKING_DETAILS'])? $arFieldsModif['BANKING_DETAILS']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		if (isset($arFieldsOrig['COMPANY_TYPE']) && isset($arFieldsModif['COMPANY_TYPE'])
			&& $arFieldsOrig['COMPANY_TYPE'] != $arFieldsModif['COMPANY_TYPE'])
		{
			$arStatus = CCrmStatus::GetStatusList('COMPANY_TYPE');
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'COMPANY_TYPE',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_COMPANY_TYPE'),
				'EVENT_TEXT_1' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $arFieldsOrig['COMPANY_TYPE'])),
				'EVENT_TEXT_2' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $arFieldsModif['COMPANY_TYPE']))
			);
		}
		if (isset($arFieldsOrig['INDUSTRY']) && isset($arFieldsModif['INDUSTRY'])
			&& $arFieldsOrig['INDUSTRY'] != $arFieldsModif['INDUSTRY'])
		{
			$arStatus = CCrmStatus::GetStatusList('INDUSTRY');
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'INDUSTRY',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_INDUSTRY'),
				'EVENT_TEXT_1' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $arFieldsOrig['INDUSTRY'])),
				'EVENT_TEXT_2' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $arFieldsModif['INDUSTRY']))
			);
		}
		if ((isset($arFieldsOrig['REVENUE']) && isset($arFieldsModif['REVENUE']) && $arFieldsOrig['REVENUE'] != $arFieldsModif['REVENUE'])
			|| (isset($arFieldsOrig['CURRENCY_ID']) && isset($arFieldsModif['CURRENCY_ID']) && $arFieldsOrig['CURRENCY_ID'] != $arFieldsModif['CURRENCY_ID']))
		{
			$arStatus = CCrmCurrencyHelper::PrepareListItems();
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'REVENUE',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_REVENUE'),
				'EVENT_TEXT_1' => floatval($arFieldsOrig['REVENUE']).(($val = CrmCompareFieldsList($arStatus, $arFieldsOrig['CURRENCY_ID'], '')) != '' ? ' ('.$val.')' : ''),
				'EVENT_TEXT_2' => floatval($arFieldsModif['REVENUE']).(($val = CrmCompareFieldsList($arStatus, $arFieldsModif['CURRENCY_ID'], '')) != '' ? ' ('.$val.')' : '')
			);
		}
		if (isset($arFieldsOrig['EMPLOYEES']) && isset($arFieldsModif['EMPLOYEES'])
			&& $arFieldsOrig['EMPLOYEES'] != $arFieldsModif['EMPLOYEES'])
		{
			$arStatus = CCrmStatus::GetStatusList('EMPLOYEES');
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'EMPLOYEES',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_EMPLOYEES'),
				'EVENT_TEXT_1' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $arFieldsOrig['EMPLOYEES'])),
				'EVENT_TEXT_2' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $arFieldsModif['EMPLOYEES']))
			);
		}
		if (isset($arFieldsOrig['COMMENTS']) && isset($arFieldsModif['COMMENTS'])
			&& $arFieldsOrig['COMMENTS'] != $arFieldsModif['COMMENTS'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'COMMENTS',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_COMMENTS'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['COMMENTS'])? $arFieldsOrig['COMMENTS']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['COMMENTS'])? $arFieldsModif['COMMENTS']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		if (isset($arFieldsOrig['IS_MY_COMPANY']) && isset($arFieldsModif['IS_MY_COMPANY'])
			&& $arFieldsOrig['IS_MY_COMPANY'] != $arFieldsModif['IS_MY_COMPANY'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'IS_MY_COMPANY',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_IS_MY_COMPANY1'),
				'EVENT_TEXT_1' => $arFieldsOrig['IS_MY_COMPANY'] === 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'),
				'EVENT_TEXT_2' => $arFieldsModif['IS_MY_COMPANY'] === 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO')
			);

		if (isset($arFieldsOrig['ASSIGNED_BY_ID']) && isset($arFieldsModif['ASSIGNED_BY_ID'])
			&& (int)$arFieldsOrig['ASSIGNED_BY_ID'] != (int)$arFieldsModif['ASSIGNED_BY_ID'])
		{
			$arUser = Array();
			$dbUsers = CUser::GetList(
				'last_name', 'asc',
				array('ID' => implode('|', array(intval($arFieldsOrig['ASSIGNED_BY_ID']), intval($arFieldsModif['ASSIGNED_BY_ID'])))),
				array('FIELDS' => array('ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'LOGIN', 'TITLE', 'EMAIL'))
			);
			while ($arRes = $dbUsers->Fetch())
				$arUser[$arRes['ID']] = CUser::FormatName(CSite::GetNameFormat(false), $arRes);

			$arMsg[] = Array(
				'ENTITY_FIELD' => 'ASSIGNED_BY_ID',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_ASSIGNED_BY_ID'),
				'EVENT_TEXT_1' => CrmCompareFieldsList($arUser, $arFieldsOrig['ASSIGNED_BY_ID']),
				'EVENT_TEXT_2' => CrmCompareFieldsList($arUser, $arFieldsModif['ASSIGNED_BY_ID'])
			);
		}

		return $arMsg;
	}

	public static function GetPermissionAttributes(array $IDs)
	{
		return CCrmPerms::GetEntityAttr(self::$TYPE_NAME, $IDs);
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

	public static function CheckImportPermission($userPermissions = null)
	{
		return CCrmAuthorizationHelper::CheckImportPermission(self::$TYPE_NAME, $userPermissions);
	}

	public static function CheckExportPermission($userPermissions = null)
	{
		return CCrmAuthorizationHelper::CheckExportPermission(self::$TYPE_NAME, $userPermissions);
	}

	public static function SetDefaultResponsible($safe = true)
	{
		global $DB;
		$tableName = CCrmCompany::TABLE_NAME;

		if($safe && !$DB->Query("SELECT ASSIGNED_BY_ID FROM {$tableName} WHERE 1=0", true))
		{
			return false;
		}

		$DB->Query(
			"UPDATE {$tableName} SET ASSIGNED_BY_ID = CREATED_BY_ID WHERE ASSIGNED_BY_ID IS NULL",
			false,
			'File: '.__FILE__.'<br/>Line: '.__LINE__
		);

		return true;
	}

	public static function PrepareFilter(&$arFilter, $arFilter2Logic = null)
	{
		if(!is_array($arFilter2Logic))
		{
			$arFilter2Logic = array('TITLE', 'ADDRESS_LEGAL', 'BANKING_DETAILS', 'ADDRESS', 'COMMENTS');
		}

		// converts data from filter
		if (isset($arFilter['FIND_list']) && !empty($arFilter['FIND']))
		{
			$arFilter[mb_strtoupper($arFilter['FIND_list'])] = $arFilter['FIND'];
			unset($arFilter['FIND_list'], $arFilter['FIND']);
		}

		static $arImmutableFilters = array(
			'FM', 'ID', 'CURRENCY_ID', 'ASSOCIATED_CONTACT_ID',
			'ASSIGNED_BY_ID', 'CREATED_BY_ID', 'MODIFY_BY_ID',
			'COMPANY_TYPE', 'INDUSTRY', 'EMPLOYEES', 'WEBFORM_ID',
			'HAS_PHONE', 'HAS_EMAIL', 'HAS_IMOL', 'IS_MY_COMPANY', 'RQ',
			'SEARCH_CONTENT',
			'FILTER_ID', 'FILTER_APPLIED', 'PRESET_ID'
		);

		foreach ($arFilter as $k => $v)
		{
			if(in_array($k, $arImmutableFilters, true))
			{
				continue;
			}

			$arMatch = array();

			if($k === 'ORIGINATOR_ID')
			{
				// HACK: build filter by internal entities
				$arFilter['=ORIGINATOR_ID'] = $v !== '__INTERNAL' ? $v : null;
				unset($arFilter[$k]);
			}
			elseif($k === 'ADDRESS'
				|| $k === 'ADDRESS_2'
				|| $k === 'ADDRESS_CITY'
				|| $k === 'ADDRESS_REGION'
				|| $k === 'ADDRESS_PROVINCE'
				|| $k === 'ADDRESS_POSTAL_CODE'
				|| $k === 'ADDRESS_COUNTRY'
				|| $k === 'ADDRESS_LEGAL'
				|| $k === 'REG_ADDRESS_2'
				|| $k === 'REG_ADDRESS_CITY'
				|| $k === 'REG_ADDRESS_REGION'
				|| $k === 'REG_ADDRESS_PROVINCE'
				|| $k === 'REG_ADDRESS_POSTAL_CODE'
				|| $k === 'REG_ADDRESS_COUNTRY')
			{
				if(!isset($arFilter['ADDRESSES']))
				{
					$arFilter['ADDRESSES'] = array();
				}

				$addressAliases = array('ADDRESS_LEGAL' => 'REG_ADDRESS');
				$addressTypeID = CompanyAddress::resolveEntityFieldTypeID($k, $addressAliases);

				if(!isset($arFilter['ADDRESSES'][$addressTypeID]))
				{
					$arFilter['ADDRESSES'][$addressTypeID] = array();
				}

				$n = CompanyAddress::mapEntityField($k, $addressTypeID, $addressAliases);
				$arFilter['ADDRESSES'][$addressTypeID][$n] = "{$v}%";

				unset($arFilter[$k]);
			}
			elseif (preg_match('/(.*)_from$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
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
					if (($arMatch[1] == 'DATE_CREATE' || $arMatch[1] == 'DATE_MODIFY') && !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/'.BX_UTF_PCRE_MODIFIER, $v))
					{
						$v = CCrmDateTimeHelper::SetMaxDayTime($v);
					}
					$arFilter['<='.$arMatch[1]] = $v;
				}
				unset($arFilter[$k]);
			}
			elseif (in_array($k, $arFilter2Logic))
			{
				// Bugfix #26956 - skip empty values in logical filter
				$v = trim($v);
				if($v !== '')
				{
					$arFilter['?'.$k] = $v;
				}
				unset($arFilter[$k]);
			}
			elseif ($k != 'ID' && $k != 'LOGIC' && $k != '__INNER_FILTER' && mb_strpos($k, 'UF_') !== 0 && preg_match('/^[^\=\%\?\>\<]{1}/', $k) === 1)
			{
				$arFilter['%'.$k] = $v;
				unset($arFilter[$k]);
			}
		}
	}

	public static function RebuildDuplicateIndex($IDs)
	{
		if(!is_array($IDs))
		{
			$IDs = array($IDs);
		}

		$dbResult = self::GetListEx(
			array(),
			array('@ID' => $IDs, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'TITLE')
		);

		if(!is_object($dbResult))
		{
			return;
		}

		$entityMultifields = DuplicateCommunicationCriterion::prepareBatchEntityMultifieldValues(
			CCrmOwnerType::Company,
			$IDs
		);

		while($fields = $dbResult->Fetch())
		{
			$ID = (int)$fields['ID'];

			$title = isset($fields['TITLE']) ? $fields['TITLE'] : '';
			if($title !== '')
			{
				DuplicateOrganizationCriterion::register(CCrmOwnerType::Company, $ID, $title);
			}

			if(isset($entityMultifields[$ID]))
			{
				DuplicateCommunicationCriterion::bulkRegister(
					CCrmOwnerType::Company,
					$ID,
					DuplicateCommunicationCriterion::prepareBulkData($entityMultifields[$ID])
				);
			}

			DuplicateRequisiteCriterion::registerByEntity(CCrmOwnerType::Company, $ID);

			DuplicateBankDetailCriterion::registerByEntity(CCrmOwnerType::Company, $ID);

			DuplicateEntityRanking::registerEntityStatistics(CCrmOwnerType::Company, $ID, $fields);
		}
	}

	public static function ProcessLeadDeletion($leadID)
	{
		global $DB;
		$DB->Query("UPDATE b_crm_company SET LEAD_ID = NULL WHERE LEAD_ID = {$leadID}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
	}

	public static function CreateRequisite($ID, $presetID)
	{
		if(!is_integer($ID))
		{
			$ID = (int)$ID;
		}

		if($ID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'ID');
		}

		if(!is_integer($presetID))
		{
			$presetID = (int)$presetID;
		}

		if($presetID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'presetID');
		}

		$externalID = "COMPANY_{$ID}";

		if(Crm\EntityRequisite::getByExternalId($externalID, array('ID')) !== null)
		{
			//Already exists
			return false;
		}

		$dbResult = self::GetListEx(
			array(),
			array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N')
		);

		$entityFields = $dbResult->Fetch();
		if(!is_array($entityFields))
		{
			throw new Main\ObjectNotFoundException("The company with ID '{$ID}' is not found");
		}

		$presetEntity = new Crm\EntityPreset();
		$presetFields = $presetEntity->getById($presetID);
		if(!is_array($presetFields))
		{
			throw new Main\ObjectNotFoundException("The preset with ID '{$presetID}' is not found");
		}

		$fieldInfos = $presetEntity->settingsGetFields(
			is_array($presetFields['SETTINGS']) ? $presetFields['SETTINGS'] : array()
		);

		$title = isset($entityFields['TITLE']) ? $entityFields['TITLE'] : '';

		$requisiteFields = array();
		foreach($fieldInfos as $fieldInfo)
		{
			$fieldName = isset($fieldInfo['FIELD_NAME']) ? $fieldInfo['FIELD_NAME'] : '';
			if($fieldName === Crm\EntityRequisite::COMPANY_FULL_NAME)
			{
				if($title !== '')
				{
					$requisiteFields[Crm\EntityRequisite::COMPANY_FULL_NAME] = $title;
				}
			}
			elseif($fieldName === Crm\EntityRequisite::ADDRESS)
			{
				$requisiteFields[Crm\EntityRequisite::ADDRESS] = [
					EntityAddressType::Primary =>
						CompanyAddress::mapEntityFields(
							$entityFields,
							['TYPE_ID' => EntityAddressType::Primary, 'SKIP_EMPTY' => true]
						),
					EntityAddressType::Registered =>
						CompanyAddress::mapEntityFields(
							$entityFields,
							['TYPE_ID' => EntityAddressType::Registered, 'SKIP_EMPTY' => true]
						)
				];
			}
		}

		if(empty($requisiteFields))
		{
			return false;
		}

		$requisiteFields['NAME'] = $title !== '' ? $title : $externalID;
		$requisiteFields['PRESET_ID'] = $presetID;
		$requisiteFields['ACTIVE'] = 'Y';
		$requisiteFields['ENTITY_TYPE_ID'] = CCrmOwnerType::Company;
		$requisiteFields['ENTITY_ID'] = $ID;
		$requisiteFields['XML_ID'] = $externalID;

		$requisiteEntity = new Crm\EntityRequisite();
		return $requisiteEntity->add($requisiteFields)->isSuccess();
	}

	public static function SynchronizeMultifieldMarkers($sourceID, array $fields = null)
	{
		global $DB;

		if($sourceID <= 0)
		{
			return;
		}

		if($fields === null)
		{
			$dbResult = self::GetListEx(
				array(),
				array('=ID' => $sourceID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID', 'HAS_EMAIL', 'HAS_PHONE', 'HAS_IMOL')
			);

			if(is_object($dbResult))
			{
				$fields = $dbResult->Fetch();
			}
		}

		if($fields === null)
		{
			return;
		}

		$multifields = isset($fields['FM']) && is_array($fields['FM']) ? $fields['FM'] : null;
		if($multifields === null)
		{
			$multifields = DuplicateCommunicationCriterion::prepareEntityMultifieldValues(
				CCrmOwnerType::Company,
				$sourceID
			);
		}

		$hasEmail = CCrmFieldMulti::HasValues($multifields, CCrmFieldMulti::EMAIL) ? 'Y' : 'N';
		$hasPhone = CCrmFieldMulti::HasValues($multifields, CCrmFieldMulti::PHONE) ? 'Y' : 'N';
		$hasImol = CCrmFieldMulti::HasImolValues($multifields) ? 'Y' : 'N';

		if(!isset($fields['HAS_EMAIL']) || $fields['HAS_EMAIL'] !== $hasEmail ||
			!isset($fields['HAS_PHONE']) || $fields['HAS_PHONE'] !== $hasPhone ||
			!isset($fields['HAS_IMOL']) || $fields['HAS_IMOL'] !== $hasImol
		)
		{
			$DB->Query("UPDATE b_crm_company SET HAS_EMAIL = '{$hasEmail}', HAS_PHONE = '{$hasPhone}', HAS_IMOL = '{$hasImol}' WHERE ID = {$sourceID}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		}
	}

	public static function GetDefaultTitle()
	{
		return GetMessage('CRM_COMPANY_UNTITLED');
	}

	public static function GetAutoTitleTemplate()
	{
		return GetMessage('CRM_COMPANY_DEFAULT_TITLE_TEMPLATE');
	}

	public static function GetAutoTitle($number = '')
	{
		return GetMessage('CRM_COMPANY_DEFAULT_TITLE_TEMPLATE', array('%NUMBER%' => $number));
	}

	/**
	 * @return array
	 */
	public static function getMyCompanyAdditionalUserFields()
	{
		$fields = [];
		if(Crm\Integration\DocumentGeneratorManager::getInstance()->isEnabled())
		{
			$fields = [
				'UF_LOGO' => [
					'FIELD_NAME' => 'UF_LOGO',
					'ENTITY_ID' => static::GetUserFieldEntityID(),
					'USER_TYPE_ID' => \CUserTypeManager::BASE_TYPE_FILE,
					'SORT' => 100,
					'MULTIPLE' => 'N',
					'MANDATORY' => 'N',
					'SHOW_FILTER' => 'N',
					'SHOW_IN_LIST' => 'N',
					'EDIT_IN_LIST' => 'Y',
					'IS_SEARCHABLE' => 'N',
					'EDIT_FORM_LABEL' => [
						LANGUAGE_ID => GetMessage('CRM_COMPANY_USER_TYPE_DOCGEN_LOGO_TITLE'),
					],
					'LIST_COLUMN_LABEL' => [
						LANGUAGE_ID => GetMessage('CRM_COMPANY_USER_TYPE_DOCGEN_LOGO_TITLE')
					],
				],
				'UF_STAMP' => [
					'FIELD_NAME' => 'UF_STAMP',
					'ENTITY_ID' => static::GetUserFieldEntityID(),
					'USER_TYPE_ID' => \CUserTypeManager::BASE_TYPE_FILE,
					'SORT' => 200,
					'MULTIPLE' => 'N',
					'MANDATORY' => 'N',
					'SHOW_FILTER' => 'N',
					'SHOW_IN_LIST' => 'N',
					'EDIT_IN_LIST' => 'Y',
					'IS_SEARCHABLE' => 'N',
					'EDIT_FORM_LABEL' => [
						LANGUAGE_ID => GetMessage('CRM_COMPANY_USER_TYPE_DOCGEN_STAMP_TITLE'),
					],
					'LIST_COLUMN_LABEL' => [
						LANGUAGE_ID => GetMessage('CRM_COMPANY_USER_TYPE_DOCGEN_STAMP_TITLE')
					],
				],
				'UF_DIRECTOR_SIGN' => [
					'FIELD_NAME' => 'UF_DIRECTOR_SIGN',
					'ENTITY_ID' => static::GetUserFieldEntityID(),
					'USER_TYPE_ID' => \CUserTypeManager::BASE_TYPE_FILE,
					'SORT' => 300,
					'MULTIPLE' => 'N',
					'MANDATORY' => 'N',
					'SHOW_FILTER' => 'N',
					'SHOW_IN_LIST' => 'N',
					'EDIT_IN_LIST' => 'Y',
					'IS_SEARCHABLE' => 'N',
					'EDIT_FORM_LABEL' => [
						LANGUAGE_ID => GetMessage('CRM_COMPANY_USER_TYPE_DIRECTOR_SIGN_TITLE'),
					],
					'LIST_COLUMN_LABEL' => [
						LANGUAGE_ID => GetMessage('CRM_COMPANY_USER_TYPE_DIRECTOR_SIGN_TITLE')
					],
				],
				'UF_ACCOUNTANT_SIGN' => [
					'FIELD_NAME' => 'UF_ACCOUNTANT_SIGN',
					'ENTITY_ID' => static::GetUserFieldEntityID(),
					'USER_TYPE_ID' => \CUserTypeManager::BASE_TYPE_FILE,
					'SORT' => 400,
					'MULTIPLE' => 'N',
					'MANDATORY' => 'N',
					'SHOW_FILTER' => 'N',
					'SHOW_IN_LIST' => 'N',
					'EDIT_IN_LIST' => 'Y',
					'IS_SEARCHABLE' => 'N',
					'EDIT_FORM_LABEL' => [
						LANGUAGE_ID => GetMessage('CRM_COMPANY_USER_TYPE_ACCOUNTANT_SIGN_TITLE'),
					],
					'LIST_COLUMN_LABEL' => [
						LANGUAGE_ID => GetMessage('CRM_COMPANY_USER_TYPE_ACCOUNTANT_SIGN_TITLE')
					],
				],
			];
		}

		return $fields;
	}

	public static function isMyCompany(int $id)
	{
		$result = \CCrmCompany::GetListEx(
			[],
			['=ID' => $id, 'CHECK_PERMISSIONS' => 'N'],
			false,
			false,
			['IS_MY_COMPANY']
		)->Fetch();

		return ($result && $result['IS_MY_COMPANY'] === 'Y');
	}
}
