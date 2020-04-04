<?php
IncludeModuleLangFile(__FILE__);

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\UtmTable;
use Bitrix\Crm\Tracking;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\ContactAddress;
use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\Binding\ContactCompanyTable;
use Bitrix\Crm\Integrity\DuplicateBankDetailCriterion;
use Bitrix\Crm\Integrity\DuplicateRequisiteCriterion;
use Bitrix\Crm\Integrity\DuplicateCommunicationCriterion;
use Bitrix\Crm\Integrity\DuplicatePersonCriterion;
use Bitrix\Crm\Integrity\DuplicateEntityRanking;
use Bitrix\Crm\Integrity\DuplicateIndexMismatch;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\Counter\EntityCounterManager;

class CAllCrmContact
{
	static public $sUFEntityID = 'CRM_CONTACT';
	const USER_FIELD_ENTITY_ID = 'CRM_CONTACT';
	const SUSPENDED_USER_FIELD_ENTITY_ID = 'CRM_CONTACT_SPD';
	const TOTAL_COUNT_CACHE_ID =  'crm_contact_total_count';

	public $LAST_ERROR = '';
	public $cPerms = null;
	protected $bCheckPermission = true;
	const TABLE_ALIAS = 'L';
	protected static $TYPE_NAME = 'CONTACT';
	private static $FIELD_INFOS = null;
	const DEFAULT_FORM_ID = 'CRM_CONTACT_SHOW_V12';

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

		$result = GetMessage("CRM_CONTACT_FIELD_{$fieldName}");
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
				'HONORIFIC' => array(
					'TYPE' => 'crm_status',
					'CRM_STATUS_TYPE' => 'HONORIFIC'
				),
				'NAME' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				),
				'SECOND_NAME' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				),
				'LAST_NAME' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				),
				'FULL_NAME' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Hidden)
				),
				'PHOTO' => array(
					'TYPE' => 'file'
				),
				'BIRTHDATE' => array(
					'TYPE' => 'date'
				),
				'BIRTHDAY_SORT' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Hidden)
				),
				'TYPE_ID' => array(
					'TYPE' => 'crm_status',
					'CRM_STATUS_TYPE' => 'CONTACT_TYPE'
				),
				'SOURCE_ID' => array(
					'TYPE' => 'crm_status',
					'CRM_STATUS_TYPE' => 'SOURCE'
				),
				'SOURCE_DESCRIPTION' => array(
					'TYPE' => 'string'
				),
				'POST' => array(
					'TYPE' => 'string'
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
				'COMMENTS' => array(
					'TYPE' => 'string'
				),
				'OPENED' => array(
					'TYPE' => 'char'
				),
				'EXPORT' => array(
					'TYPE' => 'char'
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
				'COMPANY_ID' => array(
					'TYPE' => 'crm_company',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Deprecated)
				),
				'COMPANY_IDS' => array(
					'TYPE' => 'crm_company',
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
				'FACE_ID' => array(
					'TYPE' => 'integer'
				)
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
			'POST' => array('FIELD' => 'L.POST', 'TYPE' => 'string'),

			'COMMENTS' => array('FIELD' => 'L.COMMENTS', 'TYPE' => 'string'),
			'HONORIFIC' => array('FIELD' => 'L.HONORIFIC', 'TYPE' => 'string'),
			'NAME' => array('FIELD' => 'L.NAME', 'TYPE' => 'string'),
			'SECOND_NAME' => array('FIELD' => 'L.SECOND_NAME', 'TYPE' => 'string'),
			'LAST_NAME' => array('FIELD' => 'L.LAST_NAME', 'TYPE' => 'string'),
			'FULL_NAME' => array('FIELD' => 'L.FULL_NAME', 'TYPE' => 'string'),

			'PHOTO' => array('FIELD' => 'L.PHOTO', 'TYPE' => 'string'),
			'LEAD_ID' => array('FIELD' => 'L.LEAD_ID', 'TYPE' => 'int'),
			'TYPE_ID' => array('FIELD' => 'L.TYPE_ID', 'TYPE' => 'string'),

			'SOURCE_ID' => array('FIELD' => 'L.SOURCE_ID', 'TYPE' => 'string'),
			'SOURCE_DESCRIPTION' => array('FIELD' => 'L.SOURCE_DESCRIPTION', 'TYPE' => 'string'),

			'COMPANY_ID' => array('FIELD' => 'L.COMPANY_ID', 'TYPE' => 'int'),
			'COMPANY_TITLE' => array('FIELD' => 'C.TITLE', 'TYPE' => 'string', 'FROM' => 'LEFT JOIN b_crm_company C ON L.COMPANY_ID = C.ID'),
			'COMPANY_LOGO' => array('FIELD' => 'C.LOGO', 'TYPE' => 'int', 'FROM' => 'LEFT JOIN b_crm_company C ON L.COMPANY_ID = C.ID'),
			'BIRTHDATE' => array('FIELD' => 'L.BIRTHDATE', 'TYPE' => 'date'),
			'BIRTHDAY_SORT' => array('FIELD' => 'L.BIRTHDAY_SORT', 'TYPE' => 'int'),
			'EXPORT' => array('FIELD' => 'L.EXPORT', 'TYPE' => 'char'),

			'HAS_PHONE' => array('FIELD' => 'L.HAS_PHONE', 'TYPE' => 'char'),
			'HAS_EMAIL' => array('FIELD' => 'L.HAS_EMAIL', 'TYPE' => 'char'),
			'HAS_IMOL' => array('FIELD' => 'L.HAS_IMOL', 'TYPE' => 'char'),

			'DATE_CREATE' => array('FIELD' => 'L.DATE_CREATE', 'TYPE' => 'datetime'),
			'DATE_MODIFY' => array('FIELD' => 'L.DATE_MODIFY', 'TYPE' => 'datetime'),

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

			'OPENED' => array('FIELD' => 'L.OPENED', 'TYPE' => 'char'),
			'WEBFORM_ID' => array('FIELD' => 'L.WEBFORM_ID', 'TYPE' => 'int'),
			'ORIGINATOR_ID' => array('FIELD' => 'L.ORIGINATOR_ID', 'TYPE' => 'string'), //EXTERNAL SYSTEM THAT OWNS THIS ITEM
			'ORIGIN_ID' => array('FIELD' => 'L.ORIGIN_ID', 'TYPE' => 'string'), //ITEM ID IN EXTERNAL SYSTEM
			'ORIGIN_VERSION' => array('FIELD' => 'L.ORIGIN_VERSION', 'TYPE' => 'string'), //ITEM VERSION IN EXTERNAL SYSTEM
			'FACE_ID' => array('FIELD' => 'L.FACE_ID', 'TYPE' => 'int')
		);

		if(!(is_array($arOptions) && isset($arOptions['DISABLE_ADDRESS']) && $arOptions['DISABLE_ADDRESS']))
		{
			$addrJoin = 'LEFT JOIN b_crm_addr ADDR ON L.ID = ADDR.ENTITY_ID AND ADDR.TYPE_ID = '
				.EntityAddress::Primary.' AND ADDR.ENTITY_TYPE_ID = '.CCrmOwnerType::Contact;

			$result['ADDRESS'] = array('FIELD' => 'ADDR.ADDRESS_1', 'TYPE' => 'string', 'FROM' => $addrJoin);
			$result['ADDRESS_2'] = array('FIELD' => 'ADDR.ADDRESS_2', 'TYPE' => 'string', 'FROM' => $addrJoin);
			$result['ADDRESS_CITY'] = array('FIELD' => 'ADDR.CITY', 'TYPE' => 'string', 'FROM' => $addrJoin);
			$result['ADDRESS_POSTAL_CODE'] = array('FIELD' => 'ADDR.POSTAL_CODE', 'TYPE' => 'string', 'FROM' => $addrJoin);
			$result['ADDRESS_REGION'] = array('FIELD' => 'ADDR.REGION', 'TYPE' => 'string', 'FROM' => $addrJoin);
			$result['ADDRESS_PROVINCE'] = array('FIELD' => 'ADDR.PROVINCE', 'TYPE' => 'string', 'FROM' => $addrJoin);
			$result['ADDRESS_COUNTRY'] = array('FIELD' => 'ADDR.COUNTRY', 'TYPE' => 'string', 'FROM' => $addrJoin);
			$result['ADDRESS_COUNTRY_CODE'] = array('FIELD' => 'ADDR.COUNTRY_CODE', 'TYPE' => 'string', 'FROM' => $addrJoin);
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
				$commonActivityJoin = CCrmActivity::PrepareJoin(0, CCrmOwnerType::Contact, 'L', 'AC', 'UAC', 'ACUSR');

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
					$activityJoin = CCrmActivity::PrepareJoin($userID, CCrmOwnerType::Contact, 'L', 'A', 'UA', '');

					$result['ACTIVITY_ID'] = array('FIELD' => 'UA.ACTIVITY_ID', 'TYPE' => 'int', 'FROM' => $activityJoin);
					$result['ACTIVITY_TIME'] = array('FIELD' => 'UA.ACTIVITY_TIME', 'TYPE' => 'datetime', 'FROM' => $activityJoin);
					$result['ACTIVITY_SUBJECT'] = array('FIELD' => 'A.SUBJECT', 'TYPE' => 'string', 'FROM' => $activityJoin);
				}
			}
		}

		// add utm fields
		$result = array_merge($result, UtmTable::getFieldsDescriptionByEntityTypeId(CCrmOwnerType::Contact));

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
			CCrmContact::DB_TYPE,
			CCrmContact::TABLE_NAME,
			self::TABLE_ALIAS,
			self::GetFields(isset($arOptions['FIELD_OPTIONS']) ? $arOptions['FIELD_OPTIONS'] : null),
			self::$sUFEntityID,
			'CONTACT',
			array('CCrmContact', 'BuildPermSql'),
			array('CCrmContact', '__AfterPrepareSql')
		);

		return $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields, $arOptions);
	}

	public static function CreateListBuilder(array $arFieldOptions = null)
	{
		return new CCrmEntityListBuilder(
			CCrmContact::DB_TYPE,
			CCrmContact::TABLE_NAME,
			self::TABLE_ALIAS,
			self::GetFields($arFieldOptions),
			self::$sUFEntityID,
			'CONTACT',
			array('CCrmContact', 'BuildPermSql'),
			array('CCrmContact', '__AfterPrepareSql')
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
		if($top <= 0)
		{
			return array();
		}

		$sortType = strtoupper($sortType) !== 'DESC' ? 'ASC' : 'DESC';

		$permissionSql = '';
		if(!CCrmPerms::IsAdmin())
		{
			if(!$userPermissions)
			{
				$userPermissions = CCrmPerms::GetCurrentUserPermissions();
			}

			$permissionSql = self::BuildPermSql(
				'L',
				'READ',
				array(
					'PERMS' => $userPermissions,
					'RAW_QUERY' => array('TOP' => $top, 'SORT_TYPE' => $sortType)
				)
			);
		}

		if($permissionSql === false)
		{
			return array();
		}

		$connection = \Bitrix\Main\Application::getConnection();
		$sql = $permissionSql === ''
			? $connection->getSqlHelper()->getTopSql("SELECT ID FROM b_crm_contact ORDER BY ID {$sortType}", $top)
			: "SELECT L.ID FROM b_crm_contact L INNER JOIN ($permissionSql) LP ON L.ID = LP.ENTITY_ID";
		$dbResult = $connection->query($sql);

		$results = array();
		while($field = $dbResult->fetch())
		{
			$results[] = (int)$field['ID'];
		}
		return $results;
	}

	public static function GetTotalCount()
	{
		if(defined('BX_COMP_MANAGED_CACHE') && $GLOBALS['CACHE_MANAGER']->Read(600, self::TOTAL_COUNT_CACHE_ID, 'b_crm_contact'))
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
	public static function GetList($arOrder = array('DATE_CREATE' => 'DESC'), $arFilter = array(), $arSelect = array(), $nPageTop = false)
	{
		global $DB, $USER_FIELD_MANAGER;

		//fields
		$arFields = array(
			'ID' => 'L.ID',
			'POST' => 'L.POST',
			'ADDRESS' => 'L.ADDRESS',
			'COMMENTS' => 'L.COMMENTS',
			'NAME' => 'L.NAME',
			'LEAD_ID' => 'L.LEAD_ID',
			'TYPE_ID' => 'L.TYPE_ID',
			'SOURCE_ID' => 'L.SOURCE_ID',
			'COMPANY_ID' => 'L.COMPANY_ID',
			'COMPANY_TITLE' => 'C.TITLE',
			'SOURCE_DESCRIPTION' => 'L.SOURCE_DESCRIPTION',
			'PHOTO' => 'L.PHOTO',
			'SECOND_NAME' => 'L.SECOND_NAME',
			'LAST_NAME' => 'L.LAST_NAME',
			'FULL_NAME' => 'L.FULL_NAME',
			'BIRTHDATE' => $DB->DateToCharFunction('L.BIRTHDATE'),
			'EXPORT' => 'L.EXPORT',
			'ASSIGNED_BY' => 'L.ASSIGNED_BY_ID',
			'ASSIGNED_BY_ID' => 'L.ASSIGNED_BY_ID',
			'CREATED_BY' => 'L.CREATED_BY_ID',
			'CREATED_BY_ID' => 'L.CREATED_BY_ID',
			'MODIFY_BY' => 'L.MODIFY_BY_ID',
			'MODIFY_BY_ID' => 'L.MODIFY_BY_ID',
			'DATE_CREATE' => $DB->DateToCharFunction('L.DATE_CREATE'),
			'DATE_MODIFY' => $DB->DateToCharFunction('L.DATE_MODIFY'),
			'OPENED' => 'L.OPENED',
			'ORIGINATOR_ID' => 'L.ORIGINATOR_ID', //EXTERNAL SYSTEM THAT OWNS THIS ITEM
			'ORIGIN_ID' => 'L.ORIGIN_ID', //ITEM ID IN EXTERNAL SYSTEM

			'ASSIGNED_BY_LOGIN' => 'U.LOGIN',
			'ASSIGNED_BY_NAME' => 'U.NAME',
			'ASSIGNED_BY_LAST_NAME' => 'U.LAST_NAME',
			'ASSIGNED_BY_SECOND_NAME' => 'U.SECOND_NAME',
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

		if (in_array('ASSIGNED_BY_LOGIN', $arFilterField) || in_array('ASSIGNED_BY', $arFilterField))
		{
			$arSelect[] = 'ASSIGNED_BY_LOGIN';
			$arSelect[] = 'ASSIGNED_BY_NAME';
			$arSelect[] = 'ASSIGNED_BY_LAST_NAME';
			$arSelect[] = 'ASSIGNED_BY_SECOND_NAME';
			$sSqlJoin .= ' LEFT JOIN b_user U ON L.ASSIGNED_BY_ID = U.ID ';
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
		if (in_array('COMPANY_ID', $arFilterField) || in_array('COMPANY_TITLE', $arFilterField))
		{
			$arSelect[] = 'COMPANY_ID';
			$arSelect[] = 'COMPANY_TITLE';
			$sSqlJoin .= ' LEFT JOIN b_crm_company C ON L.COMPANY_ID = C.ID ';
		}

		foreach($arSelect as $field)
		{
			$field = strtoupper($field);
			if (array_key_exists($field, $arFields))
				$arSqlSelect[$field] = $arFields[$field].($field != '*' ? ' AS '.$field : '');
		}

		if (!isset($arSqlSelect['ID']))
			$arSqlSelect['ID'] = $arFields['ID'];
		$sSqlSelect = implode(",\n", $arSqlSelect);

		if (isset($arFilter['FM']) && !empty($arFilter['FM']))
		{
			$res = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'CONTACT', 'FILTER' => $arFilter['FM']));
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

		$obUserFieldsSql = new CUserTypeSQL;
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
			if(strlen($sSqlPerm) > 0)
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
			'COMPANY_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.COMPANY_ID',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'COMPANY_TITLE' => array(
				'TABLE_ALIAS' => 'C',
				'FIELD_NAME' => 'C.TITLE',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'TYPE_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.TYPE_ID',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'SOURCE_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.SOURCE_ID',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'NAME' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.NAME',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'SECOND_NAME' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.SECOND_NAME',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'LAST_NAME' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.LAST_NAME',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'FULL_NAME' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.FULL_NAME',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'BIRTHDATE' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.BIRTHDATE',
				'FIELD_TYPE' => 'date',
				'JOIN' => false
			),
			'POST' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.POST',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'ADDRESS' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.ADDRESS',
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
			'ASSIGNED_BY_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.ASSIGNED_BY_ID',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'OPENED' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.OPENED',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'MODIFY_BY_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.MODIFY_BY_ID',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'EXPORT' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.EXPORT',
				'FIELD_TYPE' => 'string',
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
			)
		);

		$obQueryWhere->SetFields($arWhereFields);
		if (!is_array($arFilter))
			$arFilter = array();
		$sQueryWhereFields = $obQueryWhere->GetQuery($arFilter);

		$sSqlSearch = '';
		foreach($arSqlSearch as $r)
			if (strlen($r) > 0)
				$sSqlSearch .= "\n\t\t\t\tAND  ($r) ";
		$CCrmUserType = new CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], self::$sUFEntityID);
		$CCrmUserType->ListPrepareFilter($arFilter);
		$r = $obUserFieldsSql->GetFilter();
		if (strlen($r) > 0)
			$sSqlSearch .= "\n\t\t\t\tAND ($r) ";

		if (!empty($sQueryWhereFields))
			$sSqlSearch .= "\n\t\t\t\tAND ($sQueryWhereFields) ";

		$arFieldsOrder = array(
			'ASSIGNED_BY' => 'L.ASSIGNED_BY_ID',
			'CREATED_BY' => 'L.CREATED_BY_ID',
			'MODIFY_BY' => 'L.MODIFY_BY_ID',
			'DATE_CREATE' => 'L.DATE_CREATE',
			'DATE_MODIFY' => 'L.DATE_MODIFY',
			'COMPANY_ID' => 'C.TITLE'
		);

		// order
		$arSqlOrder = Array();
		if (!is_array($arOrder))
			$arOrder = Array('DATE_CREATE' => 'DESC');
		foreach($arOrder as $by => $order)
		{
			$by = strtoupper($by);
			$order = strtolower($order);
			if ($order != 'asc')
				$order = 'desc';

			if (isset($arFieldsOrder[$by]))
				$arSqlOrder[$by] = " {$arFieldsOrder[$by]} $order ";
			else if (isset($arFields[$by]) && $by != 'ADDRESS')
				$arSqlOrder[$by] = " L.$by $order ";
			else if ($s = $obUserFieldsSql->GetOrder($by))
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
				b_crm_contact L $sSqlJoin
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

		$dbRes = CCrmContact::GetListEx(array(), $arFilter);
		return $dbRes->Fetch();
	}

	public static function GetFullName($arFields)
	{
		if(!is_array($arFields))
		{
			return '';
		}

		$name = isset($arFields['NAME']) ? $arFields['NAME'] : '';
		$lastName = isset($arFields['LAST_NAME']) ? $arFields['LAST_NAME'] : '';

		if($name === '' && $lastName === '')
		{
			return '';
		}

		return $name !== '' ? ($lastName !== '' ? "{$name} {$lastName}" : $name) : $lastName;
	}

	public static function BuildPermSql($sAliasPrefix = 'L', $mPermType = 'READ', $arOptions = array())
	{
		return CCrmPerms::BuildSql('CONTACT', $sAliasPrefix, $mPermType, $arOptions);
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
					CCrmOwnerType::Contact,
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

		if(isset($arFilter['ASSOCIATED_COMPANY_TITLE']))
		{
			$sql = ContactCompanyTable::prepareFilterJoinSqlByTitle(
				CCrmOwnerType::Company,
				$arFilter['ASSOCIATED_COMPANY_TITLE'],
				$sender->GetTableAlias()
			);

			if($sql !== '')
			{
				$sqlData['FROM'][] = $sql;
			}
			unset($arFilter['ASSOCIATED_COMPANY_TITLE']);
		}
		if(isset($arFilter['ASSOCIATED_COMPANY_ID']) && $arFilter['ASSOCIATED_COMPANY_ID'] > 0)
		{
			$sqlData['FROM'][] = ContactCompanyTable::prepareFilterJoinSql(
				CCrmOwnerType::Company,
				$arFilter['ASSOCIATED_COMPANY_ID'],
				$sender->GetTableAlias()
			);
		}
		if(isset($arFilter['ASSOCIATED_DEAL_ID']) && $arFilter['ASSOCIATED_DEAL_ID'] > 0)
		{
			$sqlData['FROM'][] = Crm\Binding\DealContactTable::prepareFilterJoinSql(
				CCrmOwnerType::Deal,
				$arFilter['ASSOCIATED_DEAL_ID'],
				$sender->GetTableAlias()
			);
		}
		if(isset($arFilter['ADDRESSES']))
		{
			foreach($arFilter['ADDRESSES'] as $addressTypeID => $addressFilter)
			{
				$sqlData['FROM'][] = EntityAddress::prepareFilterJoinSql(
					CCrmOwnerType::Contact,
					$addressTypeID,
					$addressFilter,
					$sender->GetTableAlias()
				);
			}
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

		if (!$this->CheckFields($arFields, false, $options))
		{
			$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
			$result = false;
		}
		else
		{
			if(isset($arFields['BIRTHDATE']))
			{
				if($arFields['BIRTHDATE'] !== '')
				{
					$birthDate = $arFields['BIRTHDATE'];
					$arFields['~BIRTHDATE'] = $DB->CharToDateFunction($birthDate, 'SHORT', false);
					$arFields['BIRTHDAY_SORT'] = \Bitrix\Crm\BirthdayReminder::prepareSorting($birthDate);
				}
				else
				{
					$arFields['BIRTHDAY_SORT'] = \Bitrix\Crm\BirthdayReminder::prepareSorting('');
				}
				unset($arFields['BIRTHDATE']);
			}
			else
			{
				$arFields['BIRTHDAY_SORT'] = \Bitrix\Crm\BirthdayReminder::prepareSorting('');
			}

			$arAttr = array();
			if (!empty($arFields['OPENED']))
				$arAttr['OPENED'] = $arFields['OPENED'];

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
				$sEntityPerm = $userPerms->GetPermType('CONTACT', $sPermission, $arEntityAttr);
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
			}

			$assignedByID = intval($arFields['ASSIGNED_BY_ID']);
			$arEntityAttr = self::BuildEntityAttr($assignedByID, $arAttr);
			$userPerms =  $assignedByID == CCrmPerms::GetCurrentUserID() ? $this->cPerms : CCrmPerms::GetUserPermissions($assignedByID);
			$sEntityPerm = $userPerms->GetPermType('CONTACT', $sPermission, $arEntityAttr);
			$this->PrepareEntityAttrs($arEntityAttr, $sEntityPerm);

			if(isset($arFields['PHOTO'])
				&& is_array($arFields['PHOTO'])
				&& strlen(CFile::CheckImageFile($arFields['PHOTO'])) === 0)
			{
				$arFields['PHOTO']['MODULE_ID'] = 'crm';
				CFile::SaveForDB($arFields, 'PHOTO', 'crm');
			}
			elseif (!empty($arFields['FACE_ID']) && Main\Loader::includeModule('faceid'))
			{
				// Set photo from FaceId module
				$face = \Bitrix\Faceid\FaceTable::getRowById($arFields['FACE_ID']);
				if (!empty($face))
				{
					$file = \CFile::MakeFileArray($face['FILE_ID']);

					$io = \CBXVirtualIo::GetInstance();
					$filePath = $io->GetPhysicalName($file['tmp_name']);
					$binaryImageContent = $io->GetFile($filePath)->GetContents();

					$arFields['PHOTO'] = array(
						'name' => 'face_'.$arFields['FACE_ID'].'.jpg',
						'type' => 'image/jpeg',
						'content' => $binaryImageContent
					);

					$arFields['PHOTO']['MODULE_ID'] = 'crm';
					CFile::SaveForDB($arFields, 'PHOTO', 'crm');
				}
			}

			$arFields['FULL_NAME'] = self::GetFullName($arFields);

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

			//region Preparation of companies
			$companyBindings = isset($arFields['COMPANY_BINDINGS']) && is_array($arFields['COMPANY_BINDINGS'])
				? $arFields['COMPANY_BINDINGS'] : null;
			$companyIDs = isset($arFields['COMPANY_IDS']) && is_array($arFields['COMPANY_IDS'])
				? $arFields['COMPANY_IDS'] : null;
			unset($arFields['COMPANY_IDS']);
			//For backward compatibility only
			$companyID = isset($arFields['COMPANY_ID']) ? max((int)$arFields['COMPANY_ID'], 0) : null;
			if($companyID !== null && $companyIDs === null)
			{
				$companyIDs = array();
				if($companyID > 0)
				{
					$companyIDs[] = $companyID;
				}
			}
			unset($arFields['COMPANY_ID']);

			if(is_array($companyIDs) && !is_array($companyBindings))
			{
				$companyBindings = EntityBinding::prepareEntityBindings(
					\CCrmOwnerType::Company,
					$companyIDs
				);

				EntityBinding::markFirstAsPrimary($companyBindings);
			}
			/* Please uncomment if required
			elseif(is_array($companyBindings) && !is_array($companyIDs))
			{
				$companyIDs = EntityBinding::prepareEntityIDs(
					CCrmOwnerType::Company,
					$companyBindings
				);
			}
			*/
			//endregion

			//region Rise BeforeAdd event
			$beforeEvents = GetModuleEvents('crm', 'OnBeforeCrmContactAdd');
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
						$this->LAST_ERROR = GetMessage('CRM_CONTACT_CREATION_CANCELED', array('#NAME#' => $arEvent['TO_NAME']));
						$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
					}
					return false;
				}
			}
			//endregion

			$ID = intval($DB->Add('b_crm_contact', $arFields, array(), 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__));

			if(defined('BX_COMP_MANAGED_CACHE'))
			{
				$GLOBALS['CACHE_MANAGER']->CleanDir('b_crm_contact');
			}

			$result = $arFields['ID'] = $ID;
			CCrmPerms::UpdateEntityAttr('CONTACT', $ID, $arEntityAttr);

			//Statistics & History -->
			Bitrix\Crm\Statistics\ContactGrowthStatisticEntry::register($ID, $arFields);
			//<-- Statistics & History

			//region Save companies
			if (is_array($companyBindings))
			{
				\Bitrix\Crm\Binding\ContactCompanyTable::bindCompanies($ID, $companyBindings);
			}

			if (isset($GLOBALS['USER']) && $companyID > 0)
			{
				if (!class_exists('CUserOptions'))
					include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/classes/'.$GLOBALS['DBType'].'/favorites.php');

				CUserOptions::SetOption('crm', 'crm_company_search', array('last_selected' => $companyID));
			}
			//endregion

			//region Statistics & History
			if(isset($arFields['LEAD_ID']) && $arFields['LEAD_ID'] > 0)
			{
				Bitrix\Crm\Statistics\LeadConversionStatisticsEntry::processBindingsChange($arFields['LEAD_ID']);
			}
			//endregion

			if($isRestoration)
			{
				Bitrix\Crm\Timeline\ContactController::getInstance()->onRestore($ID, array('FIELDS' => $arFields));
			}
			else
			{
				Bitrix\Crm\Timeline\ContactController::getInstance()->onCreate($ID, array('FIELDS' => $arFields));
			}

			$lastName = isset($arFields['LAST_NAME']) ? $arFields['LAST_NAME'] : '';
			if($lastName !== '')
			{
				DuplicatePersonCriterion::register(
					CCrmOwnerType::Contact,
					$ID,
					$lastName,
					isset($arFields['NAME']) ? $arFields['NAME'] : '',
					isset($arFields['SECOND_NAME']) ? $arFields['SECOND_NAME'] : ''
				);
			}

			EntityAddress::register(
				CCrmOwnerType::Contact,
				$ID,
				EntityAddress::Primary,
				array(
					'ADDRESS_1' => isset($arFields['ADDRESS']) ? $arFields['ADDRESS'] : null,
					'ADDRESS_2' => isset($arFields['ADDRESS_2']) ? $arFields['ADDRESS_2'] : null,
					'CITY' => isset($arFields['ADDRESS_CITY']) ? $arFields['ADDRESS_CITY'] : null,
					'POSTAL_CODE' => isset($arFields['ADDRESS_POSTAL_CODE']) ? $arFields['ADDRESS_POSTAL_CODE'] : null,
					'REGION' => isset($arFields['ADDRESS_REGION']) ? $arFields['ADDRESS_REGION'] : null,
					'PROVINCE' => isset($arFields['ADDRESS_PROVINCE']) ? $arFields['ADDRESS_PROVINCE'] : null,
					'COUNTRY' => isset($arFields['ADDRESS_COUNTRY']) ? $arFields['ADDRESS_COUNTRY'] : null,
					'COUNTRY_CODE' => isset($arFields['ADDRESS_COUNTRY_CODE']) ? $arFields['ADDRESS_COUNTRY_CODE'] : null
				)
			);

			CCrmEntityHelper::NormalizeUserFields($arFields, self::$sUFEntityID, $GLOBALS['USER_FIELD_MANAGER'], array('IS_NEW' => true));
			$GLOBALS['USER_FIELD_MANAGER']->Update(self::$sUFEntityID, $ID, $arFields);

			//region Duplicate communication data
			if (isset($arFields['FM']) && is_array($arFields['FM']))
			{
				$CCrmFieldMulti = new CCrmFieldMulti();
				$CCrmFieldMulti->SetFields('CONTACT', $ID, $arFields['FM']);
				$duplicateCommData = DuplicateCommunicationCriterion::prepareBulkData($arFields['FM']);
				if(!empty($duplicateCommData))
				{
					DuplicateCommunicationCriterion::bulkRegister(CCrmOwnerType::Contact, $ID, $duplicateCommData);
				}
			}
			//endregion

			//endregion
			DuplicateEntityRanking::registerEntityStatistics(CCrmOwnerType::Contact, $ID, $arFields);

			// tracking of entity
			Tracking\Entity::onAfterAdd(CCrmOwnerType::Contact, $ID, $arFields);

			if($bUpdateSearch)
			{
				CCrmSearch::UpdateSearch(array('ID' => $ID, 'CHECK_PERMISSIONS' => 'N'), 'CONTACT', true);
			}

			//region Search content index
			Bitrix\Crm\Search\SearchContentBuilderFactory::create(CCrmOwnerType::Contact)->build($ID);
			//endregion

			if(isset($options['REGISTER_SONET_EVENT']) && $options['REGISTER_SONET_EVENT'] === true)
			{
				$multiFields = isset($arFields['FM']) ? $arFields['FM'] : null;
				$phones = CCrmFieldMulti::ExtractValues($multiFields, 'PHONE');
				$emails = CCrmFieldMulti::ExtractValues($multiFields, 'EMAIL');
				$assignedByID = intval($arFields['ASSIGNED_BY_ID']);
				$createdByID = intval($arFields['CREATED_BY_ID']);

				$liveFeedFields = array(
					'USER_ID' => $createdByID,
					'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
					'ENTITY_ID' => $ID,
					'TITLE' => GetMessage('CRM_CONTACT_EVENT_ADD'),
					'MESSAGE' => '',
					'PARAMS' => array(
						'NAME' => isset($arFields['NAME']) ? $arFields['NAME'] : '',
						'SECOND_NAME' => isset($arFields['SECOND_NAME']) ? $arFields['SECOND_NAME'] : '',
						'LAST_NAME' => isset($arFields['LAST_NAME']) ? $arFields['LAST_NAME'] : '',
						'HONORIFIC' => isset($arFields['HONORIFIC']) ? $arFields['HONORIFIC'] : '',
						'PHOTO_ID' => isset($arFields['PHOTO']) ? $arFields['PHOTO'] : '',
						'COMPANY_ID' => isset($arFields['COMPANY_ID']) ? $arFields['COMPANY_ID'] : '',
						'PHONES' => $phones,
						'EMAILS' => $emails,
						'AUTHOR_ID' => intval($arFields['CREATED_BY_ID']),
						'RESPONSIBLE_ID' => $assignedByID
					)
				);

				//region Register company relation
				if(is_array($companyBindings))
				{
					$parents = array();
					CCrmLiveFeed::PrepareOwnershipRelations(
						CCrmOwnerType::Company,
						EntityBinding::prepareEntityIDs(CCrmOwnerType::Company, $companyBindings),
						$parents
					);

					if(!empty($parents))
					{
						$liveFeedFields['PARENTS'] = array_values($parents);
					}
				}
				//endregion

				CCrmSonetSubscription::RegisterSubscription(
					CCrmOwnerType::Contact,
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
					$url = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Contact, $ID);
					$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0) ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));

					$arMessageFields = array(
						"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
						"TO_USER_ID" => $assignedByID,
						"FROM_USER_ID" => $createdByID,
						"NOTIFY_TYPE" => IM_NOTIFY_FROM,
						"NOTIFY_MODULE" => "crm",
						"LOG_ID" => $logEventID,
						"NOTIFY_EVENT" => "contact_add",
						"NOTIFY_TAG" => "CRM|CONTACT_RESPONSIBLE|".$ID,
						"NOTIFY_MESSAGE" => GetMessage("CRM_CONTACT_RESPONSIBLE_IM_NOTIFY", Array("#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arFields['FULL_NAME'])."</a>")),
						"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_CONTACT_RESPONSIBLE_IM_NOTIFY", Array("#title#" => htmlspecialcharsbx($arFields['FULL_NAME'])))." (".$serverName.$url.")"
					);
					CIMNotify::Add($arMessageFields);
				}
			}

			//region Rise AfterAdd event
			$afterEvents = GetModuleEvents('crm', 'OnAfterCrmContactAdd');
			while ($arEvent = $afterEvents->Fetch())
			{
				ExecuteModuleEventEx($arEvent, array(&$arFields));
			}
			//endregion

			if(isset($arFields['ORIGIN_ID']) && $arFields['ORIGIN_ID'] !== '')
			{
				$afterEvents = GetModuleEvents('crm', 'OnAfterExternalCrmContactAdd');
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

		$arUserAttr = CCrmPerms::BuildUserEntityAttr($userID);
		return array_merge($arResult, $arUserAttr['INTRANET']);
	}

	static public function RebuildEntityAccessAttrs($IDs)
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
			array('ID', 'ASSIGNED_BY_ID', 'OPENED')
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

			$entityAttrs = self::BuildEntityAttr($assignedByID, $attrs);
			CCrmPerms::UpdateEntityAttr('CONTACT', $ID, $entityAttrs);
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

	public function Update($ID, array &$arFields, $bCompare = true, $bUpdateSearch = true, $arOptions = array())
	{
		global $DB;

		$this->LAST_ERROR = '';
		$ID = (int) $ID;
		if(!is_array($arOptions))
		{
			$arOptions = array();
		}
		$isSystemAction = isset($options['IS_SYSTEM_ACTION']) && $arOptions['IS_SYSTEM_ACTION'];

		$arFilterTmp = array('ID' => $ID);
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

		$assignedByID = (int)(isset($arFields['ASSIGNED_BY_ID']) ? $arFields['ASSIGNED_BY_ID'] : $arRow['ASSIGNED_BY_ID']);

		$bResult = false;
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

			$beforeEvents = GetModuleEvents('crm', 'OnBeforeCrmContactUpdate');
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
						$this->LAST_ERROR = GetMessage('CRM_CONTACT_UPDATE_CANCELED', array('#NAME#' => $arEvent['TO_NAME']));
						$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
					}
					return false;
				}
			}

			$arAttr = array();
			$arAttr['OPENED'] = !empty($arFields['OPENED']) ? $arFields['OPENED'] : $arRow['OPENED'];
			$arEntityAttr = self::BuildEntityAttr($assignedByID, $arAttr);
			if($this->bCheckPermission)
			{
				$sEntityPerm = $this->cPerms->GetPermType('CONTACT', 'WRITE', $arEntityAttr);
				//HACK: Ensure that entity accessible for user restricted by BX_CRM_PERM_OPEN
				$this->PrepareEntityAttrs($arEntityAttr, $sEntityPerm);
				//HACK: Prevent 'OPENED' field change by user restricted by BX_CRM_PERM_OPEN permission
				if($sEntityPerm === BX_CRM_PERM_OPEN && isset($arFields['OPENED']) && $arFields['OPENED'] !== 'Y' && $assignedByID !== $iUserId)
				{
					$arFields['OPENED'] = 'Y';
				}
			}

			if(isset($arFields['PHOTO']))
			{
				if(is_numeric($arFields['PHOTO']) && $arFields['PHOTO'] > 0)
				{
					//New file editor (file is already saved)
					if(isset($arFields['PHOTO_del']) && $arFields['PHOTO_del'] > 0)
					{
						CFile::Delete($arFields['PHOTO_del']);
						if($arFields['PHOTO'] == $arFields['PHOTO_del'])
						{
							$arFields['PHOTO'] = '';
						}
					}
				}
				elseif(is_array($arFields['PHOTO']) && strlen(CFile::CheckImageFile($arFields['PHOTO'])) === 0)
				{
					//Old file editor (file id is not saved yet)
					$arFields['PHOTO']['MODULE_ID'] = 'crm';
					if($arFields['PHOTO_del'] == 'Y' && !empty($arRow['PHOTO']))
						CFile::Delete($arRow['PHOTO']);
					CFile::SaveForDB($arFields, 'PHOTO', 'crm');
					if($arFields['PHOTO_del'] == 'Y' && !isset($arFields['PHOTO']))
						$arFields['PHOTO'] = '';
				}
			}

			if (isset($arFields['ASSIGNED_BY_ID']) && intval($arRow['ASSIGNED_BY_ID']) !== intval($arFields['ASSIGNED_BY_ID']))
			{
				CcrmEvent::SetAssignedByElement($arFields['ASSIGNED_BY_ID'], 'CONTACT', $ID);
			}

			//region Preparation of companies
			$originalCompanyBindings = \Bitrix\Crm\Binding\ContactCompanyTable::getContactBindings($ID);
			$originalCompanyIDs = EntityBinding::prepareEntityIDs(CCrmOwnerType::Company, $originalCompanyBindings);
			$companyBindings = isset($arFields['COMPANY_BINDINGS']) && is_array($arFields['COMPANY_BINDINGS'])
				? $arFields['COMPANY_BINDINGS'] : null;
			$companyIDs = isset($arFields['COMPANY_IDS']) && is_array($arFields['COMPANY_IDS'])
				? $arFields['COMPANY_IDS'] : null;
			unset($arFields['COMPANY_IDS']);

			//For backward compatibility only
			$companyID = isset($arFields['COMPANY_ID']) ? max((int)$arFields['COMPANY_ID'], 0) : null;
			if($companyID !== null &&
				$companyIDs === null &&
				$companyID !== null &&
				!in_array($companyID, $originalCompanyIDs, true))
			{
				//Compatibility mode. Trying to simulate single binding mode If company is not found in bindings.
				$companyIDs = array();
				if($companyID > 0)
				{
					$companyIDs[] = $companyID;
				}
			}
			unset($arFields['COMPANY_ID']);

			$removedCompanyIDs = null;
			$addedCompanyIDs = null;

			$addedCompanyBindings = null;
			$removedCompanyBindings = null;

			if(is_array($companyIDs) && !is_array($companyBindings))
			{
				$companyBindings = EntityBinding::prepareEntityBindings(
					\CCrmOwnerType::Company,
					$companyIDs
				);

				EntityBinding::markFirstAsPrimary($companyBindings);
			}
			/* Please uncomment if required
			elseif(is_array($companyBindings) && !is_array($companyIDs))
			{
				$companyIDs = EntityBinding::prepareEntityIDs(
					CCrmOwnerType::Company,
					$companyBindings
				);
			}
			*/

			if(is_array($companyBindings))
			{
				$removedCompanyBindings = array();
				$addedCompanyBindings = array();

				EntityBinding::prepareBindingChanges(
					CCrmOwnerType::Company,
					$originalCompanyBindings,
					$companyBindings,
					$addedCompanyBindings,
					$removedCompanyBindings
				);

				$addedCompanyIDs = EntityBinding::prepareEntityIDs(
					CCrmOwnerType::Company,
					$addedCompanyBindings
				);

				$removedCompanyIDs = EntityBinding::prepareEntityIDs(
					CCrmOwnerType::Company,
					$removedCompanyBindings
				);
			}
			//endregion

			$sonetEventData = array();
			if ($bCompare)
			{
				$res = CCrmFieldMulti::GetList(
					array('ID' => 'asc'),
					array('ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => $ID)
				);
				$arRow['FM'] = array();
				while($ar = $res->Fetch())
					$arRow['FM'][$ar['TYPE_ID']][$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);

				$compareOptions = array();
				if(!empty($addedCompanyIDs) || !empty($removedCompanyIDs))
				{
					$compareOptions['COMPANIES'] = array('ADDED' => $addedCompanyIDs, 'REMOVED' => $removedCompanyIDs);
				}
				$arEvents = self::CompareFields($arRow, $arFields, array_merge($compareOptions, $arOptions));
				foreach($arEvents as $arEvent)
				{
					$arEvent['ENTITY_TYPE'] = 'CONTACT';
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
								$sonetEventData[CCrmLiveFeedEvent::Responsible] = array(
									'TYPE' => CCrmLiveFeedEvent::Responsible,
									'FIELDS' => array(
										//'EVENT_ID' => $eventID,
										'TITLE' => GetMessage('CRM_CONTACT_EVENT_UPDATE_ASSIGNED_BY'),
										'MESSAGE' => '',
										'PARAMS' => array(
											'START_RESPONSIBLE_ID' => $arRow['ASSIGNED_BY_ID'],
											'FINAL_RESPONSIBLE_ID' => $arFields['ASSIGNED_BY_ID']
										)
									)
								);
							}
							break;
							case 'COMPANY_ID':
							{
								if(!isset($sonetEventData[CCrmLiveFeedEvent::Owner]))
								{
									$sonetEventData[CCrmLiveFeedEvent::Owner] = array(
										'CODE'=> 'COMPANY',
										'TYPE' => CCrmLiveFeedEvent::Owner,
										'FIELDS' => array(
											//'EVENT_ID' => $eventID,
											'TITLE' => GetMessage('CRM_CONTACT_EVENT_UPDATE_COMPANY'),
											'MESSAGE' => '',
											'PARAMS' => array(
												'REMOVED_OWNER_COMPANY_IDS' => is_array($removedCompanyIDs)
													? $removedCompanyIDs : array(),
												'ADDED_OWNER_COMPANY_IDS' => is_array($addedCompanyIDs)
													? $addedCompanyIDs : array(),
												//Todo: Remove START_OWNER_COMPANY_ID and FINAL_OWNER_COMPANY_ID when log template will be ready
												'START_OWNER_COMPANY_ID' => is_array($removedCompanyIDs)
													&& isset($removedCompanyIDs[0]) ? $removedCompanyIDs[0] : 0,
												'FINAL_OWNER_COMPANY_ID' => is_array($addedCompanyIDs)
													&& isset($addedCompanyIDs[0]) ? $addedCompanyIDs[0] : 0
											)
										)
									);
								}
							}
							break;
						}
					}
				}
			}

			if(isset($arFields['NAME'])
				|| isset($arFields['SECOND_NAME'])
				|| isset($arFields['LAST_NAME'])
				|| isset($arFields['HONORIFIC'])
			)
			{
				if((isset($arFields['NAME']) && $arFields['NAME'] !== $arRow['NAME'])
					|| (isset($arFields['SECOND_NAME']) && $arFields['SECOND_NAME'] !== $arRow['SECOND_NAME'])
					|| (isset($arFields['LAST_NAME']) && $arFields['LAST_NAME'] !== $arRow['LAST_NAME'])
					|| (isset($arFields['HONORIFIC']) && $arFields['HONORIFIC'] !== $arRow['HONORIFIC'])
				)
				{
					CCrmActivity::ResetEntityCommunicationSettings(CCrmOwnerType::Contact, $ID);
				}
			}

			if (isset($arFields['BIRTHDAY_SORT']))
			{
				unset($arFields['BIRTHDAY_SORT']);
			}

			if(isset($arFields['BIRTHDATE']))
			{
				if($arFields['BIRTHDATE'] !== '')
				{
					$birthDate = $arFields['BIRTHDATE'];
					$arFields['~BIRTHDATE'] = $DB->CharToDateFunction($birthDate, 'SHORT', false);
					$arFields['BIRTHDAY_SORT'] = \Bitrix\Crm\BirthdayReminder::prepareSorting($birthDate);
					unset($arFields['BIRTHDATE']);
				}
				else
				{
					$arFields['BIRTHDAY_SORT'] = \Bitrix\Crm\BirthdayReminder::prepareSorting('');
				}
			}

			if (isset($arFields['NAME']) && isset($arFields['LAST_NAME']))
			{
				$arFields['FULL_NAME'] = self::GetFullName($arFields);
			}
			else
			{
				$dbRes = $DB->Query("SELECT NAME, LAST_NAME FROM b_crm_contact WHERE ID = $ID", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
				$arRes = $dbRes->Fetch();
				if(isset($arFields['NAME']))
				{
					$arRes['NAME'] = $arFields['NAME'];
				}

				if(isset($arFields['LAST_NAME']))
				{
					$arRes['LAST_NAME'] = $arFields['LAST_NAME'];
				}

				$arFields['FULL_NAME'] =  self::GetFullName($arRes);
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

			unset($arFields['ID']);
			$sUpdate = $DB->PrepareUpdate('b_crm_contact', $arFields, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			if (strlen($sUpdate) > 0)
			{
				$bResult = true;
				$DB->Query("UPDATE b_crm_contact SET {$sUpdate} WHERE ID = {$ID}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);

				if(isset($arFields['LAST_NAME']) || isset($arFields['NAME']) || isset($arFields['SECOND_NAME']))
				{
					$lastName = isset($arFields['LAST_NAME'])
						? $arFields['LAST_NAME'] : (isset($arRow['LAST_NAME']) ? $arRow['LAST_NAME'] : '');
					$name = isset($arFields['NAME'])
						? $arFields['NAME'] : (isset($arRow['NAME']) ? $arRow['NAME'] : '');
					$secondName = isset($arFields['SECOND_NAME'])
						? $arFields['SECOND_NAME'] : (isset($arRow['SECOND_NAME']) ? $arRow['SECOND_NAME'] : '');

					DuplicatePersonCriterion::register(CCrmOwnerType::Contact, $ID, $lastName, $name, $secondName);
				}
			}
			CCrmPerms::UpdateEntityAttr('CONTACT', $ID, $arEntityAttr);

			//region Save companies
			if(!empty($removedCompanyBindings))
			{
				\Bitrix\Crm\Binding\ContactCompanyTable::unbindCompanies($ID, $removedCompanyBindings);
			}

			if(!empty($addedCompanyBindings))
			{
				\Bitrix\Crm\Binding\ContactCompanyTable::bindCompanies($ID, $addedCompanyBindings);

				if (isset($GLOBALS['USER']))
				{
					if (!class_exists('CUserOptions'))
						include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/classes/'.$GLOBALS['DBType'].'/favorites.php');

					CUserOptions::SetOption(
						'crm',
						'crm_company_search',
						array(
							'last_selected' => EntityBinding::getLastEntityID(
								CCrmOwnerType::Company,
								$addedCompanyBindings
							)
						)
					);
				}
			}
			//endregion

			if(isset($arFields['ADDRESS'])
				|| isset($arFields['ADDRESS_2'])
				|| isset($arFields['ADDRESS_CITY'])
				|| isset($arFields['ADDRESS_POSTAL_CODE'])
				|| isset($arFields['ADDRESS_REGION'])
				|| isset($arFields['ADDRESS_PROVINCE'])
				|| isset($arFields['ADDRESS_COUNTRY']))
			{
				EntityAddress::register(
					CCrmOwnerType::Contact,
					$ID,
					EntityAddress::Primary,
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
							? $arFields['ADDRESS_COUNTRY_CODE'] : (isset($arRow['ADDRESS_COUNTRY_CODE']) ? $arRow['ADDRESS_COUNTRY_CODE'] : null)
					)
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
			Bitrix\Crm\Statistics\ContactGrowthStatisticEntry::synchronize($ID, array(
				'ASSIGNED_BY_ID' => $assignedByID
			));
			Crm\Activity\CommunicationStatistics::synchronizeByOwner(\CCrmOwnerType::Contact, $ID, array(
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
							CCrmOwnerType::Contact,
							array(
								EntityCounterType::PENDING,
								EntityCounterType::OVERDUE,
								EntityCounterType::ALL
							)
						),
						$assignedByIDs
					);
				}
			}

			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				static $arNameFields = array("NAME", "LAST_NAME", "SECOND_NAME");
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
					$GLOBALS["CACHE_MANAGER"]->ClearByTag("crm_entity_name_".CCrmOwnerType::Contact."_".$ID);
				}
			}

			if (isset($arFields['FM']) && is_array($arFields['FM']))
			{
				$CCrmFieldMulti = new CCrmFieldMulti();
				$CCrmFieldMulti->SetFields(CCrmOwnerType::ContactName, $ID, $arFields['FM']);

				$multifields = DuplicateCommunicationCriterion::prepareEntityMultifieldValues(
					CCrmOwnerType::Contact,
					$ID
				);

				DuplicateCommunicationCriterion::bulkRegister(
					CCrmOwnerType::Contact,
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
					$DB->Query("UPDATE b_crm_contact SET HAS_EMAIL = '{$hasEmail}', HAS_PHONE = '{$hasPhone}', HAS_IMOL = '{$hasImol}' WHERE ID = {$ID}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);

					$arFields['HAS_EMAIL'] = $hasEmail;
					$arFields['HAS_PHONE'] = $hasPhone;
					$arFields['HAS_IMOL'] = $hasImol;
				}
			}
			DuplicateEntityRanking::registerEntityStatistics(CCrmOwnerType::Contact, $ID, array_merge($arRow, $arFields));

			// update utm fields
			UtmTable::updateEntityUtmFromFields(CCrmOwnerType::Contact, $ID, $arFields);

			if($bUpdateSearch)
			{
				CCrmSearch::UpdateSearch(array('ID' => $ID, 'CHECK_PERMISSIONS' => 'N'), 'CONTACT', true);
			}

			//region Search content index
			Bitrix\Crm\Search\SearchContentBuilderFactory::create(CCrmOwnerType::Contact)->build($ID);
			//endregion

			Bitrix\Crm\Timeline\ContactController::getInstance()->onModify(
				$ID,
				array(
					'CURRENT_FIELDS' => $arFields,
					'PREVIOUS_FIELDS' => $arRow,
					'OPTIONS' => $arOptions
				)
			);

			if (isset($GLOBALS["USER"]) && isset($arFields['COMPANY_ID']) && intval($arFields['COMPANY_ID']) > 0)
			{
				if (!class_exists('CUserOptions'))
					include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/classes/".$GLOBALS['DBType']."/favorites.php");

				CUserOptions::SetOption("crm", "crm_company_search", array('last_selected' => $arFields['COMPANY_ID']));
			}

			$arFields['ID'] = $ID;

			$registerSonetEvent = isset($arOptions['REGISTER_SONET_EVENT']) && $arOptions['REGISTER_SONET_EVENT'] === true;

			if($bResult && isset($arFields['ASSIGNED_BY_ID']))
			{
				CCrmSonetSubscription::ReplaceSubscriptionByEntity(
					CCrmOwnerType::Contact,
					$ID,
					CCrmSonetSubscriptionType::Responsibility,
					$arFields['ASSIGNED_BY_ID'],
					$arRow['ASSIGNED_BY_ID'],
					$registerSonetEvent
				);
			}

			if($bResult && $bCompare && $registerSonetEvent && !empty($sonetEventData))
			{
				//region Preparation of Parent Company IDs
				$parentCompanyIDs = is_array($companyIDs)
					? $companyIDs : \Bitrix\Crm\Binding\ContactCompanyTable::getContactCompanyIDs($ID);
				//endregion

				$modifiedByID = intval($arFields['MODIFY_BY_ID']);
				foreach($sonetEventData as &$sonetEvent)
				{
					$sonetEventType = $sonetEvent['TYPE'];
					$sonetEventFields = &$sonetEvent['FIELDS'];
					$sonetEventFields['ENTITY_TYPE_ID'] = CCrmOwnerType::Contact;
					$sonetEventFields['ENTITY_ID'] = $ID;
					$sonetEventFields['USER_ID'] = $modifiedByID;

					//Register company relation
					$parents = array();
					if($sonetEventType === CCrmLiveFeedEvent::Owner && is_array($removedCompanyIDs))
					{
						CCrmLiveFeed::PrepareOwnershipRelations(
							CCrmOwnerType::Company,
							array_merge($parentCompanyIDs, $removedCompanyIDs),
							$parents
						);
					}
					else
					{
						CCrmLiveFeed::PrepareOwnershipRelations(
							CCrmOwnerType::Company,
							$parentCompanyIDs,
							$parents
						);
					}

					if(!empty($parents))
					{
						$sonetEventFields['PARENTS'] = array_values($parents);
					}

					$logEventID = CCrmLiveFeed::CreateLogEvent($sonetEventFields, $sonetEventType, array('CURRENT_USER' => $iUserId));

					if (
						$logEventID
						&& $sonetEvent['TYPE'] == CCrmLiveFeedEvent::Responsible
						&& CModule::IncludeModule("im")
					)
					{
						$title = CCrmOwnerType::GetCaption(CCrmOwnerType::Contact, $ID, false);
						$url = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Contact, $ID);
						$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0) ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));

						if ($sonetEventFields['PARAMS']['FINAL_RESPONSIBLE_ID'] != $modifiedByID)
						{
							$arMessageFields = array(
								"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
								"TO_USER_ID" => $sonetEventFields['PARAMS']['FINAL_RESPONSIBLE_ID'],
								"FROM_USER_ID" => $modifiedByID,
								"NOTIFY_TYPE" => IM_NOTIFY_FROM,
								"NOTIFY_MODULE" => "crm",
								"LOG_ID" => $logEventID,
								"NOTIFY_EVENT" => "contact_update",
								"NOTIFY_TAG" => "CRM|CONTACT_RESPONSIBLE|".$ID,
								"NOTIFY_MESSAGE" => GetMessage("CRM_CONTACT_RESPONSIBLE_IM_NOTIFY", Array("#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($title)."</a>")),
								"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_CONTACT_RESPONSIBLE_IM_NOTIFY", Array("#title#" => htmlspecialcharsbx($title)))." (".$serverName.$url.")"
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
								"NOTIFY_EVENT" => "contact_update",
								"NOTIFY_TAG" => "CRM|CONTACT_RESPONSIBLE|".$ID,
								"NOTIFY_MESSAGE" => GetMessage("CRM_CONTACT_NOT_RESPONSIBLE_IM_NOTIFY", Array("#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($title)."</a>")),
								"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_CONTACT_NOT_RESPONSIBLE_IM_NOTIFY", Array("#title#" => htmlspecialcharsbx($title)))." (".$serverName.$url.")"
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
				$afterEvents = GetModuleEvents('crm', 'OnAfterCrmContactUpdate');
				while ($arEvent = $afterEvents->Fetch())
					ExecuteModuleEventEx($arEvent, array(&$arFields));
			}
		}
		return $bResult;
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

		$dbResult = \CCrmContact::GetListEx(
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
			$arEntityAttr = $this->cPerms->GetEntityAttr('CONTACT', $ID);
			$sEntityPerm = $this->cPerms->GetPermType('CONTACT', 'DELETE', $arEntityAttr[$ID]);
			if ($sEntityPerm == BX_CRM_PERM_NONE)
				return false;
			else if ($sEntityPerm == BX_CRM_PERM_SELF)
				$sWherePerm = " AND ASSIGNED_BY_ID = {$iUserId}";
			else if ($sEntityPerm == BX_CRM_PERM_OPEN)
				$sWherePerm = " AND (OPENED = 'Y' OR ASSIGNED_BY_ID = {$iUserId})";
		}

		$APPLICATION->ResetException();
		$events = GetModuleEvents('crm', 'OnBeforeCrmContactDelete');
		while ($arEvent = $events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array($ID))===false)
			{
				$err = GetMessage("MAIN_BEFORE_DEL_ERR").' '.$arEvent['TO_NAME'];
				if ($ex = $APPLICATION->GetException())
					$err .= ': '.$ex->GetString();
				$APPLICATION->throwException($err);
				return false;
			}

		$enableDeferredMode = isset($arOptions['ENABLE_DEFERRED_MODE'])
			? (bool)$arOptions['ENABLE_DEFERRED_MODE']
			: \Bitrix\Crm\Settings\ContactSettings::getCurrent()->isDeferredCleaningEnabled();

		//By default we need to clean up related bizproc entities
		$processBizproc = isset($arOptions['PROCESS_BIZPROC']) ? (bool)$arOptions['PROCESS_BIZPROC'] : true;
		if($processBizproc)
		{
			$bizproc = new CCrmBizProc('CONTACT');
			$bizproc->ProcessDeletion($ID);
		}

		$enableRecycleBin = \Bitrix\Crm\Recycling\ContactController::isEnabled()
			&& \Bitrix\Crm\Settings\ContactSettings::getCurrent()->isRecycleBinEnabled();
		if($enableRecycleBin)
		{
			\Bitrix\Crm\Recycling\ContactController::getInstance()->moveToBin($ID, array('FIELDS' => $arFields));
		}

		$obRes = $DB->Query("DELETE FROM b_crm_contact WHERE ID = {$ID}{$sWherePerm}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		if (is_object($obRes) && $obRes->AffectedRowsCount() > 0)
		{
			if(defined('BX_COMP_MANAGED_CACHE'))
			{
				$GLOBALS['CACHE_MANAGER']->CleanDir('b_crm_contact');
			}

			if(!$enableRecycleBin)
			{
				self::ReleaseExternalResources($arFields);
			}

			CCrmSearch::DeleteSearch('CONTACT', $ID);

			$DB->Query("DELETE FROM b_crm_entity_perms WHERE ENTITY='CONTACT' AND ENTITY_ID = $ID", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			$GLOBALS['USER_FIELD_MANAGER']->Delete(self::$sUFEntityID, $ID);

			CCrmDeal::ProcessContactDeletion($ID);
			CCrmLead::ProcessContactDeletion($ID);

			\Bitrix\Crm\Binding\ContactCompanyTable::unbindAllCompanies($ID);
			\Bitrix\Crm\Binding\QuoteContactTable::unbindAllQuotes($ID);

			if(!$enableDeferredMode)
			{
				$CCrmEvent = new CCrmEvent();
				$CCrmEvent->DeleteByElement('CONTACT', $ID);
			}
			else
			{
				Bitrix\Crm\Cleaning\CleaningManager::register(CCrmOwnerType::Contact, $ID);
				if(!Bitrix\Crm\Agent\Routine\CleaningAgent::isActive())
				{
					Bitrix\Crm\Agent\Routine\CleaningAgent::activate();
				}
			}

			DuplicateEntityRanking::unregisterEntityStatistics(CCrmOwnerType::Contact, $ID);
			DuplicateCommunicationCriterion::unregister(CCrmOwnerType::Contact, $ID);
			DuplicatePersonCriterion::unregister(CCrmOwnerType::Contact, $ID);
			DuplicateIndexMismatch::unregisterEntity(CCrmOwnerType::Contact, $ID);

			$enableDupIndexInvalidation = isset($arOptions['ENABLE_DUP_INDEX_INVALIDATION'])
				? (bool)$arOptions['ENABLE_DUP_INDEX_INVALIDATION'] : true;
			if($enableDupIndexInvalidation)
			{
				\Bitrix\Crm\Integrity\DuplicateIndexBuilder::markAsJunk(CCrmOwnerType::Contact, $ID);
			}

			//Statistics & History -->
			$leadID = isset($arFields['LEAD_ID']) ? (int)$arFields['LEAD_ID'] : 0;
			if($leadID)
			{
				\Bitrix\Crm\Statistics\LeadConversionStatisticsEntry::processBindingsChange($leadID);
			}
			\Bitrix\Crm\Statistics\ContactGrowthStatisticEntry::unregister($ID);
			//<-- Statistics & History

			if($assignedByID > 0)
			{
				EntityCounterManager::reset(
					EntityCounterManager::prepareCodes(
						CCrmOwnerType::Contact,
						array(
							EntityCounterType::PENDING,
							EntityCounterType::OVERDUE,
							EntityCounterType::ALL
						)
					),
					array($assignedByID)
				);
			}

			CCrmActivity::DeleteByOwner(CCrmOwnerType::Contact, $ID);

			if(!$enableRecycleBin)
			{
				$CCrmFieldMulti = new CCrmFieldMulti();
				$CCrmFieldMulti->DeleteByElement('CONTACT', $ID);

				EntityAddress::unregister(CCrmOwnerType::Contact, $ID, EntityAddress::Primary);
				\Bitrix\Crm\Timeline\TimelineEntry::deleteByOwner(CCrmOwnerType::Contact, $ID);

				$requisite = new \Bitrix\Crm\EntityRequisite();
				$requisite->deleteByEntity(CCrmOwnerType::Contact, $ID);
				unset($requisite);

				CCrmSonetSubscription::UnRegisterSubscriptionByEntity(CCrmOwnerType::Contact, $ID);
				CCrmLiveFeed::DeleteLogEvents(
					array(
						'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
						'ENTITY_ID' => $ID
					)
				);
				UtmTable::deleteEntityUtm(CCrmOwnerType::Contact, $ID);
				Tracking\Entity::deleteTrace(CCrmOwnerType::Contact, $ID);
			}

			\Bitrix\Crm\Timeline\ContactController::getInstance()->onDelete(
				$ID,
				array('FIELDS' => $arFields)
			);

			if(\Bitrix\Crm\Settings\HistorySettings::getCurrent()->isContactDeletionEventEnabled())
			{
				CCrmEvent::RegisterDeleteEvent(CCrmOwnerType::Contact, $ID, 0, array('FIELDS' => $arFields));
			}

			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("crm_entity_name_".CCrmOwnerType::Contact."_".$ID);
			}

			$afterEvents = GetModuleEvents('crm', 'OnAfterCrmContactDelete');
			while ($arEvent = $afterEvents->Fetch())
			{
				ExecuteModuleEventEx($arEvent, array($ID));
			}

			CCrmLiveFeed::DeleteUserCrmConnection('C_'.$ID);
		}
		return true;
	}

	public static function ReleaseExternalResources(array $arFields)
	{
		$photoID = isset($arFields['PHOTO']) ? (int)$arFields['PHOTO'] : 0;
		if($photoID > 0)
		{
			\CFile::Delete($photoID);
		}
	}

	public function CheckFields(&$arFields, $ID = false, $options = array())
	{
		global $APPLICATION, $USER_FIELD_MANAGER;
		$this->LAST_ERROR = '';

		if (($ID == false || (isset($arFields['NAME']) && isset($arFields['LAST_NAME'])))
			&& (empty($arFields['NAME']) && empty($arFields['LAST_NAME'])))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_REQUIRED_FIELDS')."<br />";

		if (isset($arFields['FM']) && is_array($arFields['FM']))
		{
			$CCrmFieldMulti = new CCrmFieldMulti();
			if (!$CCrmFieldMulti->CheckComplexFields($arFields['FM']))
				$this->LAST_ERROR .= $CCrmFieldMulti->LAST_ERROR;
		}

		if (isset($arFields['PHOTO']) && is_array($arFields['PHOTO']))
		{
			if (($strError = CFile::CheckFile($arFields['PHOTO'], 0, 0, CFile::GetImageExtensions())) != '')
				$this->LAST_ERROR .= $strError."<br />";
		}

		if(isset($arFields['BIRTHDATE']) && $arFields['BIRTHDATE'] !== '' && !CheckDateTime($arFields['BIRTHDATE']))
		{
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => self::GetFieldCaption('BIRTHDATE')))."<br />";
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

		if($enableUserFieldCheck)
		{
			// We have to prepare field data before check (issue #22966)
			CCrmEntityHelper::NormalizeUserFields($arFields, self::$sUFEntityID, $USER_FIELD_MANAGER, array('IS_NEW' => ($ID == false)));

			$enableRequiredUserFieldCheck = !(isset($options['DISABLE_REQUIRED_USER_FIELD_CHECK'])
				&& $options['DISABLE_REQUIRED_USER_FIELD_CHECK'] === true);

			if(!$USER_FIELD_MANAGER->CheckFields(self::$sUFEntityID, $ID, $arFields, false, $enableRequiredUserFieldCheck))
			{
				$e = $APPLICATION->GetException();
				$this->LAST_ERROR .= $e->GetString();
			}
		}

		return $this->LAST_ERROR === '';
	}

	/**
	 * @deprecated
	 * @see Bitrix\Crm\Binding\ContactCompanyTable::getCompanyContactIDs
	 * @param $companyID
	 * @return CDBResult
	 */
	public static function GetContactByCompanyID($companyID)
	{
		$companyID = (int)$companyID;
		return self::GetList(Array(), Array('COMPANY_ID' => ($companyID > 0 ? $companyID : -1)));
	}

	/**
	 * @deprecated
	 * @see Bitrix\Crm\Binding\ContactCompanyTable::bindContactIDs
	 * @param array $arIDs
	 * @param int $companyID
	 * @return bool
	 */
	public function UpdateCompanyID(array $arIDs, $companyID)
	{
		global $DB;

		if (!is_array($arIDs))
			return false;

		$arContactID = Array();
		foreach ($arIDs as $ID)
			$arContactID[] = (int) $ID;

		$companyID = (int) $companyID;

		if (!empty($arContactID))
			$DB->Query("UPDATE b_crm_contact SET COMPANY_ID = $companyID WHERE ID IN (".implode(',', $arContactID).")", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);

		return true;
	}

	public static function CompareFields(array $arFieldsOrig, array $arFieldsModif, array $arOptions = null)
	{
		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		$arMsg = Array();

		if (isset($arFieldsModif['HONORIFIC']))
		{
			$origHonorific = isset($arFieldsOrig['HONORIFIC']) ? $arFieldsOrig['HONORIFIC'] : '';
			$modifHonrific = isset($arFieldsModif['HONORIFIC']) ? $arFieldsModif['HONORIFIC'] : '';
			if($origHonorific !== $modifHonrific)
			{
				$arMsg[] = Array(
					'ENTITY_FIELD' => 'HONORIFIC',
					'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_HONORIFIC'),
					'EVENT_TEXT_1' => $origHonorific !== '' ? $origHonorific : GetMessage('CRM_FIELD_COMPARE_EMPTY'),
					'EVENT_TEXT_2' => $modifHonrific !== '' ? $modifHonrific : GetMessage('CRM_FIELD_COMPARE_EMPTY')
				);
			}
		}

		if (isset($arFieldsOrig['NAME']) && isset($arFieldsModif['NAME'])
			&& $arFieldsOrig['NAME'] != $arFieldsModif['NAME'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'NAME',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_NAME'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['NAME'])? $arFieldsOrig['NAME']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['NAME'])? $arFieldsModif['NAME']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		if (isset($arFieldsOrig['LAST_NAME']) && isset($arFieldsModif['LAST_NAME'])
			&& $arFieldsOrig['LAST_NAME'] != $arFieldsModif['LAST_NAME'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'LAST_NAME',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_LAST_NAME'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['LAST_NAME'])? $arFieldsOrig['LAST_NAME']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['LAST_NAME'])? $arFieldsModif['LAST_NAME']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		if (isset($arFieldsOrig['SECOND_NAME']) && isset($arFieldsModif['SECOND_NAME'])
			&& $arFieldsOrig['SECOND_NAME'] != $arFieldsModif['SECOND_NAME'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'SECOND_NAME',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_SECOND_NAME'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['SECOND_NAME'])? $arFieldsOrig['SECOND_NAME']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['SECOND_NAME'])? $arFieldsModif['SECOND_NAME']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		if (isset($arFieldsOrig['FM']) && isset($arFieldsModif['FM']))
			$arMsg = array_merge($arMsg, CCrmFieldMulti::CompareFields($arFieldsOrig['FM'], $arFieldsModif['FM']));

		if (isset($arFieldsOrig['POST']) && isset($arFieldsModif['POST'])
			&& $arFieldsOrig['POST'] != $arFieldsModif['POST'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'POST',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_POST'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['POST'])? $arFieldsOrig['POST']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['POST'])? $arFieldsModif['POST']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		$addressOptions = array();
		if(isset($arOptions['ADDRESS_FIELDS']))
		{
			$addressOptions['FIELDS'] = $arOptions['ADDRESS_FIELDS'];
		}

		$arMsg = array_merge(
			$arMsg,
			ContactAddress::prepareChangeEvents(
				$arFieldsOrig,
				$arFieldsModif,
				ContactAddress::Primary,
				$addressOptions
			)
		);

		if (isset($arFieldsOrig['COMMENTS']) && isset($arFieldsModif['COMMENTS'])
			&& $arFieldsOrig['COMMENTS'] != $arFieldsModif['COMMENTS'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'COMMENTS',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_COMMENTS'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['COMMENTS'])? $arFieldsOrig['COMMENTS']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['COMMENTS'])? $arFieldsModif['COMMENTS']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		if(isset($arOptions['COMPANIES']) && is_array($arOptions['COMPANIES']))
		{
			$addedCompanyIDs = isset($arOptions['COMPANIES']['ADDED']) && is_array($arOptions['COMPANIES']['ADDED'])
				? $arOptions['COMPANIES']['ADDED'] : array();

			$removedCompanyIDs = isset($arOptions['COMPANIES']['REMOVED']) && is_array($arOptions['COMPANIES']['REMOVED'])
				? $arOptions['COMPANIES']['REMOVED'] : array();

			if(!empty($addedCompanyIDs) || !empty($removedCompanyIDs))
			{
				//region Preparation of company titles
				$dbResult = CCrmCompany::GetListEx(
					array(),
					array(
						'CHECK_PERMISSIONS' => 'N',
						'@ID' => array_merge($addedCompanyIDs, $removedCompanyIDs)
					),
					false,
					false,
					array('ID', 'TITLE')
				);

				$companyTitles = array();
				while ($ary = $dbResult->Fetch())
				{
					$companyTitles[$ary['ID']] = $ary['TITLE'];
				}
				//endregion
				if(count($addedCompanyIDs) <= 1 && count($removedCompanyIDs) <= 1)
				{
					//region Single binding mode
					$arMsg[] = Array(
						'ENTITY_FIELD' => 'COMPANY_ID',
						'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_COMPANY_ID'),
						'EVENT_TEXT_1' => CrmCompareFieldsList(
							$companyTitles,
							isset($removedCompanyIDs[0]) ? $removedCompanyIDs[0] : 0
						),
						'EVENT_TEXT_2' => CrmCompareFieldsList(
							$companyTitles,
							isset($addedCompanyIDs[0]) ? $addedCompanyIDs[0] : 0
						)
					);
					//endregion
				}
				else
				{
					//region Multiple binding mode
					//region Add companies event
					$texts = array();
					foreach($addedCompanyIDs as $companyID)
					{
						if(isset($companyTitles[$companyID]))
						{
							$texts[] = $companyTitles[$companyID];
						}
					}

					$arMsg[] = Array(
						'ENTITY_FIELD' => 'COMPANY_ID',
						'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_COMPANIES_ADDED'),
						'EVENT_TEXT_1' => implode(', ', $texts),
					);
					//endregion
					//region Remove companies event
					$texts = array();
					foreach($removedCompanyIDs as $companyID)
					{
						if(isset($companyTitles[$companyID]))
						{
							$texts[] = $companyTitles[$companyID];
						}
					}

					$arMsg[] = Array(
						'ENTITY_FIELD' => 'COMPANY_ID',
						'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_COMPANIES_REMOVED'),
						'EVENT_TEXT_1' => implode(', ', $texts),
					);
					//endregion
					//endregion
				}
			}
		}

		if (isset($arFieldsOrig['SOURCE_ID']) && isset($arFieldsModif['SOURCE_ID'])
			&& $arFieldsOrig['SOURCE_ID'] != $arFieldsModif['SOURCE_ID'])
		{
			$arStatus = CCrmStatus::GetStatusList('SOURCE');
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'SOURCE_ID',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_SOURCE_ID'),
				'EVENT_TEXT_1' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $arFieldsOrig['SOURCE_ID'])),
				'EVENT_TEXT_2' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $arFieldsModif['SOURCE_ID']))
			);
		}

		if (isset($arFieldsOrig['SOURCE_DESCRIPTION']) && isset($arFieldsModif['SOURCE_DESCRIPTION'])
			&& $arFieldsOrig['SOURCE_DESCRIPTION'] != $arFieldsModif['SOURCE_DESCRIPTION'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'SOURCE_DESCRIPTION',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_SOURCE_DESCRIPTION'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['SOURCE_DESCRIPTION'])? $arFieldsOrig['SOURCE_DESCRIPTION']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['SOURCE_DESCRIPTION'])? $arFieldsModif['SOURCE_DESCRIPTION']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		if (isset($arFieldsOrig['TYPE_ID']) && isset($arFieldsModif['TYPE_ID'])
			&& $arFieldsOrig['TYPE_ID'] != $arFieldsModif['TYPE_ID'])
		{
			$arStatus = CCrmStatus::GetStatusList('CONTACT_TYPE');
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'TYPE_ID',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_TYPE_ID'),
				'EVENT_TEXT_1' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $arFieldsOrig['TYPE_ID'])),
				'EVENT_TEXT_2' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $arFieldsModif['TYPE_ID']))
			);
		}

		if (isset($arFieldsOrig['ASSIGNED_BY_ID']) && isset($arFieldsModif['ASSIGNED_BY_ID'])
			&& (int)$arFieldsOrig['ASSIGNED_BY_ID'] != (int)$arFieldsModif['ASSIGNED_BY_ID'])
		{
			$arUser = Array();
			$dbUsers = CUser::GetList(
				($sort_by = 'last_name'), ($sort_dir = 'asc'),
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

		if (isset($arFieldsModif['BIRTHDATE']))
		{
			$origBirthdate = isset($arFieldsOrig['BIRTHDATE']) ? $arFieldsOrig['BIRTHDATE'] : '';
			$modifBirthdate = isset($arFieldsModif['BIRTHDATE']) ? $arFieldsModif['BIRTHDATE'] : '';
			if($origBirthdate !== $modifBirthdate)
			{
				$arMsg[] = Array(
					'ENTITY_FIELD' => 'BIRTHDATE',
					'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_BIRTHDATE'),
					'EVENT_TEXT_1' => $origBirthdate !== '' ? $origBirthdate : GetMessage('CRM_FIELD_COMPARE_EMPTY'),
					'EVENT_TEXT_2' => $modifBirthdate !== '' ? $modifBirthdate : GetMessage('CRM_FIELD_COMPARE_EMPTY')
				);
			}
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

	public static function PrepareFilter(&$arFilter, $arFilter2Logic = null)
	{
		if(!is_array($arFilter2Logic))
		{
			$arFilter2Logic = array('TITLE', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'POST', 'ADDRESS', 'COMMENTS');
		}

		// converts data from filter
		if (isset($arFilter['FIND_list']) && !empty($arFilter['FIND']))
		{
			$arFilter[strtoupper($arFilter['FIND_list'])] = $arFilter['FIND'];
			unset($arFilter['FIND_list'], $arFilter['FIND']);
		}

		static $arImmutableFilters = array(
			'FM', 'ID', 'COMPANY_ID', 'COMPANY_ID_value', 'ASSOCIATED_COMPANY_ID', 'ASSOCIATED_DEAL_ID',
			'ASSIGNED_BY_ID', 'ASSIGNED_BY_ID_value',
			'CREATED_BY_ID', 'CREATED_BY_ID_value',
			'MODIFY_BY_ID', 'MODIFY_BY_ID_value',
			'TYPE_ID', 'SOURCE_ID', 'WEBFORM_ID',
			'HAS_PHONE', 'HAS_EMAIL', 'HAS_IMOL', 'RQ',
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
				|| $k === 'ADDRESS_COUNTRY')
			{
				if(!isset($arFilter['ADDRESSES']))
				{
					$arFilter['ADDRESSES'] = array();
				}

				$addressTypeID = ContactAddress::resolveEntityFieldTypeID($k);
				if(!isset($arFilter['ADDRESSES'][$addressTypeID]))
				{
					$arFilter['ADDRESSES'][$addressTypeID] = array();
				}

				$n = ContactAddress::mapEntityField($k, $addressTypeID);
				$arFilter['ADDRESSES'][$addressTypeID][$n] = "{$v}%";
				unset($arFilter[$k]);
			}
			elseif (preg_match('/(.*)_from$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
			{
				if(strlen($v) > 0)
				{
					$arFilter['>='.$arMatch[1]] = $v;
				}
				unset($arFilter[$k]);
			}
			elseif (preg_match('/(.*)_to$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
			{
				if(strlen($v) > 0)
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
			elseif ($k != 'ID' && $k != 'LOGIC' && $k != '__INNER_FILTER' && strpos($k, 'UF_') !== 0 && preg_match('/^[^\=\%\?\>\<]{1}/', $k) === 1)
			{
				$arFilter['%'.$k] = $v;
				unset($arFilter[$k]);
			}
		}
	}

	public static function GetCount($arFilter)
	{
		$fields = self::GetFields();
		return CSqlUtil::GetCount(CCrmContact::TABLE_NAME, self::TABLE_ALIAS, $fields, $arFilter);
	}

	public static function PrepareFormattedName(array $arFields, $nameTemplate = '')
	{
		if(!is_string($nameTemplate) || $nameTemplate === '')
		{
			$nameTemplate = \Bitrix\Crm\Format\PersonNameFormatter::getFormat();
		}

		static $honorificList = null;
		if($honorificList === null)
		{
			$honorificList = CCrmStatus::GetStatusList('HONORIFIC');
		}
		$honorific = '';
		$honorificID = isset($arFields['HONORIFIC']) ? $arFields['HONORIFIC'] : '';
		if($honorificID !== '' && isset($honorificList[$honorificID]))
		{
			$honorific = $honorificList[$honorificID];
		}

		return CUser::FormatName(
			$nameTemplate,
			array(
				'LOGIN' => '',
				'TITLE' => $honorific,
				'NAME' => isset($arFields['NAME']) ? $arFields['NAME'] : '',
				'SECOND_NAME' => isset($arFields['SECOND_NAME']) ? $arFields['SECOND_NAME'] : '',
				'LAST_NAME' => isset($arFields['LAST_NAME']) ? $arFields['LAST_NAME'] : ''
			),
			false,
			false
		);
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
			array('ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'DATE_MODIFY')
		);

		if(!is_object($dbResult))
		{
			return;
		}

		$entityMultifields = DuplicateCommunicationCriterion::prepareBatchEntityMultifieldValues(
			CCrmOwnerType::Contact,
			$IDs
		);

		while($fields = $dbResult->Fetch())
		{
			$ID = (int)$fields['ID'];

			$lastName = isset($fields['LAST_NAME']) ? $fields['LAST_NAME'] : '';
			if($lastName !== '')
			{
				DuplicatePersonCriterion::register(
					CCrmOwnerType::Contact,
					$ID,
					$lastName,
					isset($fields['NAME']) ? $fields['NAME'] : '',
					isset($fields['SECOND_NAME']) ? $fields['SECOND_NAME'] : ''
				);
			}

			if(isset($entityMultifields[$ID]))
			{
				DuplicateCommunicationCriterion::bulkRegister(
					CCrmOwnerType::Contact,
					$ID,
					DuplicateCommunicationCriterion::prepareBulkData($entityMultifields[$ID])
				);
			}

			DuplicateRequisiteCriterion::registerByEntity(CCrmOwnerType::Contact, $ID);

			DuplicateBankDetailCriterion::registerByEntity(CCrmOwnerType::Contact, $ID);

			DuplicateEntityRanking::registerEntityStatistics(CCrmOwnerType::Contact, $ID, $fields);
		}
	}

	public static function Rebind($ownerTypeID, $oldID, $newID)
	{
		global $DB;

		$ownerTypeID = (int)$ownerTypeID;
		$oldID = (int)$oldID;
		$newID = (int)$newID;
		$tableName = CCrmContact::TABLE_NAME;
		if($ownerTypeID === CCrmOwnerType::Company)
		{
			$DB->Query(
				"UPDATE {$tableName} SET COMPANY_ID = {$newID} WHERE COMPANY_ID = {$oldID}",
				false,
				'File: '.__FILE__.'<br>Line: '.__LINE__
			);
		}
	}

	public static function ProcessLeadDeletion($leadID)
	{
		global $DB;
		$DB->Query("UPDATE b_crm_contact SET LEAD_ID = NULL WHERE LEAD_ID = {$leadID}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
	}

	public static function ProcessCompanyDeletion($companyID)
	{
		global $DB;
		$DB->Query("UPDATE b_crm_contact SET COMPANY_ID = NULL WHERE COMPANY_ID = {$companyID}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
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

		$externalID = "CONTACT_{$ID}";

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
			throw new Main\ObjectNotFoundException("The contact with ID '{$ID}' is not found");
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

		$fullName = self::GetFullName($entityFields);

		$requisiteFields = array();
		foreach($fieldInfos as $fieldInfo)
		{
			$fieldName = isset($fieldInfo['FIELD_NAME']) ? $fieldInfo['FIELD_NAME'] : '';
			if($fieldName === Crm\EntityRequisite::PERSON_FIRST_NAME)
			{
				if(isset($entityFields['NAME']) && $entityFields['NAME'] !== '')
				{
					$requisiteFields[Crm\EntityRequisite::PERSON_FIRST_NAME] = $entityFields['NAME'];
				}
			}
			elseif($fieldName === Crm\EntityRequisite::PERSON_SECOND_NAME)
			{
				if(isset($entityFields['SECOND_NAME']) && $entityFields['SECOND_NAME'] !== '')
				{
					$requisiteFields[Crm\EntityRequisite::PERSON_SECOND_NAME] = $entityFields['SECOND_NAME'];
				}
			}
			elseif($fieldName === Crm\EntityRequisite::PERSON_LAST_NAME)
			{
				if(isset($entityFields['LAST_NAME']) && $entityFields['LAST_NAME'] !== '')
				{
					$requisiteFields[Crm\EntityRequisite::PERSON_LAST_NAME] = $entityFields['LAST_NAME'];
				}
			}
			elseif($fieldName === Crm\EntityRequisite::PERSON_FULL_NAME)
			{
				if($fullName !== '')
				{
					$requisiteFields[Crm\EntityRequisite::PERSON_FULL_NAME] = $fullName;
				}
			}
			elseif($fieldName === Crm\EntityRequisite::ADDRESS)
			{
				$requisiteFields[Crm\EntityRequisite::ADDRESS] = array(
					EntityAddress::Primary =>
						ContactAddress::mapEntityFields(
							$entityFields,
							array('TYPE_ID' => EntityAddress::Primary, 'SKIP_EMPTY' => true)
						)
				);
			}
		}

		if(empty($requisiteFields))
		{
			return false;
		}

		$requisiteFields['NAME'] = $fullName !== '' ? $fullName : $externalID;
		$requisiteFields['PRESET_ID'] = $presetID;
		$requisiteFields['ACTIVE'] = 'Y';
		$requisiteFields['ENTITY_TYPE_ID'] = CCrmOwnerType::Contact;
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
				CCrmOwnerType::Contact,
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
			$DB->Query("UPDATE b_crm_contact SET HAS_EMAIL = '{$hasEmail}', HAS_PHONE = '{$hasPhone}', HAS_IMOL = '{$hasImol}' WHERE ID = {$sourceID}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		}
	}

	public static function GetDefaultName()
	{
		return GetMessage('CRM_CONTACT_UNNAMED');
	}
}
?>
