<?php
IncludeModuleLangFile(__FILE__);

use Bitrix\Crm;
use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\Binding\LeadContactTable;
use Bitrix\Crm\CustomerType;
use Bitrix\Crm\Entity\Traits\EntityFieldsNormalizer;
use Bitrix\Crm\Entity\Traits\UserFieldPreparer;
use Bitrix\Crm\EntityAddressType;
use Bitrix\Crm\Integration\Channel\LeadChannelBinding;
use Bitrix\Crm\Integration\PullManager;
use Bitrix\Crm\Integrity\DuplicateCommunicationCriterion;
use Bitrix\Crm\Integrity\DuplicateIndexMismatch;
use Bitrix\Crm\Integrity\DuplicateManager;
use Bitrix\Crm\Kanban\ViewMode;
use Bitrix\Crm\Tracking;
use Bitrix\Crm\UserField\Visibility\VisibilityManager;
use Bitrix\Crm\UtmTable;

class CAllCrmLead
{
	use UserFieldPreparer;
	use EntityFieldsNormalizer;

	static public $sUFEntityID = 'CRM_LEAD';

	const USER_FIELD_ENTITY_ID = 'CRM_LEAD';
	const SUSPENDED_USER_FIELD_ENTITY_ID = 'CRM_LEAD_SPD';
	const TOTAL_COUNT_CACHE_ID = 'crm_lead_total_count';
	const CACHE_TTL = 3600;

	protected const TABLE_NAME = 'b_crm_lead';

	public $LAST_ERROR = '';
	protected $checkExceptions = array();

	private static ?\Bitrix\Crm\Entity\Compatibility\Adapter $lastActivityAdapter = null;
	private static ?Crm\Entity\Compatibility\Adapter $contentTypeIdAdapter = null;

	/** @var \Bitrix\Crm\Entity\Compatibility\Adapter */
	private $compatibilityAdapter;

	public $cPerms = null;
	protected $bCheckPermission = true;
	const TABLE_ALIAS = 'L';
	protected static $TYPE_NAME = 'LEAD';
	protected static $FIELD_INFOS = null;
	protected static $LEAD_STATUSES = null;
	protected static $LEAD_STATUSES_BY_GROUP = null;
	const DEFAULT_FORM_ID = 'CRM_LEAD_SHOW_V12';

	function __construct($bCheckPermission = true)
	{
		$this->bCheckPermission = $bCheckPermission;
		$this->cPerms = CCrmPerms::GetCurrentUserPermissions();
	}

	/**
	 * Returns true if this class should invoke Service\Operation instead old API.
	 * For a start it will return false by default. Please use this period to test your customization on compatibility with new API.
	 * Later it will return true by default.
	 * In several months this class will be declared as deprecated and old code will be deleted completely.
	 *
	 * @return bool
	 */
	public function isUseOperation(): bool
	{
		return static::isFactoryEnabled();
	}

	private static function isFactoryEnabled(): bool
	{
		return Crm\Settings\LeadSettings::getCurrent()->isFactoryEnabled();
	}

	private function getCompatibilityAdapter(): Crm\Entity\Compatibility\Adapter
	{
		if (!$this->compatibilityAdapter)
		{
			$this->compatibilityAdapter = static::createCompatibilityAdapter();

			if ($this->compatibilityAdapter instanceof Crm\Entity\Compatibility\Adapter\Operation)
			{
				$this->compatibilityAdapter
					//bind newly created adapter to this instance
					->setCheckPermissions((bool)$this->bCheckPermission)
					->setErrorMessageContainer($this->LAST_ERROR)
					->setCheckExceptionsContainer($this->checkExceptions)
				;
			}
		}

		return $this->compatibilityAdapter;
	}

	private static function createCompatibilityAdapter(): Bitrix\Crm\Entity\Compatibility\Adapter
	{
		$factory = Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Lead);
		if (!$factory)
		{
			throw new Error('No factory for lead');
		}

		$compatibilityAdapter =
			(new Crm\Entity\Compatibility\Adapter\Operation($factory))
				->setRunAutomation(false)
				->setRunBizProc(false)
				->setAlwaysExposedFields([
					'ID',
					'MODIFY_BY_ID',
					'EXCH_RATE',
					'ACCOUNT_CURRENCY_ID',
					'OPPORTUNITY_ACCOUNT',
				])
				->setExposedOnlyAfterAddFields([
					'CREATED_BY_ID',
					'ASSIGNED_BY_ID',
					'OPPORTUNITY',
					'TITLE',
					'BIRTHDAY_SORT',
					'STATUS_ID',
					'STATUS_SEMANTIC_ID',
					'CURRENCY_ID',
					'HAS_IMOL',
					'HAS_PHONE',
					'HAS_EMAIL',
					'DATE_MODIFY',
					'DATE_CREATE',
				])
				->setExposedOnlyAfterUpdateFields([
					'FULL_NAME',
				])
		;

		$addressAdapter = new Crm\Entity\Compatibility\Adapter\Address(\CCrmOwnerType::Lead, EntityAddressType::Primary);
		$compatibilityAdapter->addChild($addressAdapter);

		return $compatibilityAdapter;
	}

	private static function getLastActivityAdapter(): Crm\Entity\Compatibility\Adapter
	{
		if (!self::$lastActivityAdapter)
		{
			$factory = Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Lead);
			self::$lastActivityAdapter = new Crm\Entity\Compatibility\Adapter\LastActivity($factory);
			self::$lastActivityAdapter->setTableAlias(self::TABLE_ALIAS);
		}

		return self::$lastActivityAdapter;
	}

	private static function getContentTypeIdAdapter(): Crm\Entity\Compatibility\Adapter\ContentTypeId
	{
		if (!self::$contentTypeIdAdapter)
		{
			self::$contentTypeIdAdapter = new Crm\Entity\Compatibility\Adapter\ContentTypeId(\CCrmOwnerType::Lead);
		}

		return self::$contentTypeIdAdapter;
	}

	// Service -->
	public static function GetFieldCaption($fieldName)
	{
		if(\CCrmFieldMulti::IsSupportedType($fieldName))
		{
			return \CCrmFieldMulti::GetEntityTypeCaption($fieldName);
		}

		$result = GetMessage("CRM_LEAD_FIELD_{$fieldName}");

		if (!(is_string($result) && $result !== '')
			&& Crm\Tracking\UI\Details::isTrackingField($fieldName))
		{
			$result = Crm\Tracking\UI\Details::getFieldCaption($fieldName);
		}

		if (Crm\Service\ParentFieldManager::isParentFieldName($fieldName))
		{
			$entityTypeId = Crm\Service\ParentFieldManager::getEntityTypeIdFromFieldName($fieldName);
			$result = \CCrmOwnerType::GetDescription($entityTypeId);
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
				'HONORIFIC' => array(
					'TYPE' => 'crm_status',
					'CRM_STATUS_TYPE' => 'HONORIFIC'
				),
				'NAME' => array(
					'TYPE' => 'string'
				),
				'SECOND_NAME' => array(
					'TYPE' => 'string'
				),
				'LAST_NAME' => array(
					'TYPE' => 'string'
				),
				'BIRTHDATE' => array(
					'TYPE' => 'date'
				),
				'BIRTHDAY_SORT' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Hidden)
				),
				'COMPANY_TITLE' => array(
					'TYPE' => 'string'
				),
				'SOURCE_ID' => array(
					'TYPE' => 'crm_status',
					'CRM_STATUS_TYPE' => 'SOURCE',
					'ATTRIBUTES' => [CCrmFieldInfoAttr::HasDefaultValue],
				),
				'SOURCE_DESCRIPTION' => array(
					'TYPE' => 'string'
				),
				'STATUS_ID' => array(
					'TYPE' => 'crm_status',
					'CRM_STATUS_TYPE' => 'STATUS',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Progress)
				),
				'STATUS_DESCRIPTION' => array(
					'TYPE' => 'string'
				),
				'STATUS_SEMANTIC_ID' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
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
				'ADDRESS_LOC_ADDR_ID' => array(
					'TYPE' => 'integer'
				),
				'CURRENCY_ID' => array(
					'TYPE' => 'crm_currency'
				),
				'OPPORTUNITY' => array(
					'TYPE' => 'double'
				),
				'IS_MANUAL_OPPORTUNITY' => array(
					'TYPE' => 'char'
				),
				'OPENED' => array(
					'TYPE' => 'char'
				),
				'COMMENTS' => array(
					'TYPE' => 'string',
					'VALUE_TYPE' => 'html',
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
					'TYPE' => 'user',
				),
				'CREATED_BY_ID' => array(
					'TYPE' => 'user',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'MODIFY_BY_ID' => array(
					'TYPE' => 'user',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'MOVED_BY_ID' => [
					'TYPE' => 'user',
					'ATTRIBUTES' => [CCrmFieldInfoAttr::ReadOnly],
				],
				'DATE_CREATE' => array(
					'TYPE' => 'datetime',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'DATE_MODIFY' => array(
					'TYPE' => 'datetime',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'MOVED_TIME' => [
					'TYPE' => 'datetime',
					'ATTRIBUTES' => [CCrmFieldInfoAttr::ReadOnly],
				],
				'COMPANY_ID' => array(
					'TYPE' => 'crm_company',
					'SETTINGS' => [
						'parentEntityTypeId' => \CCrmOwnerType::Company,
					],
				),
				'CONTACT_ID' => array(
					'TYPE' => 'crm_contact',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Deprecated)
				),
				'CONTACT_IDS' => array(
					'TYPE' => 'crm_contact',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple)
				),
				'IS_RETURN_CUSTOMER' => array(
					'TYPE' => 'char',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'DATE_CLOSED' => array(
					'TYPE' => 'datetime',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'ORIGINATOR_ID' => array(
					'TYPE' => 'string'
				),
				'ORIGIN_ID' => array(
					'TYPE' => 'string'
				),
				/*'DISCOUNT_TYPE_ID' => array(
					'TYPE' => 'integer'
				),
				'DISCOUNT_RATE' => array(
					'TYPE' => 'double'
				),
				'DISCOUNT_SUM' => array(
					'TYPE' => 'double'
				)*/
			);

			// add utm fields
			self::$FIELD_INFOS += UtmTable::getUtmFieldsInfo();

			self::$FIELD_INFOS += Crm\Service\Container::getInstance()->getParentFieldManager()->getParentFieldsInfo(\CCrmOwnerType::Lead);
			self::$FIELD_INFOS += self::getLastActivityAdapter()->getFieldsInfo();
		}

		return self::$FIELD_INFOS;
	}

	public static function GetFields($arOptions = null)
	{
		$companyJoin = 'LEFT JOIN b_crm_company CO ON L.COMPANY_ID = CO.ID';
		$contactJoin = 'LEFT JOIN b_crm_contact C ON L.CONTACT_ID = C.ID';
		$assignedByJoin = 'LEFT JOIN b_user U ON L.ASSIGNED_BY_ID = U.ID';
		$createdByJoin = 'LEFT JOIN b_user U2 ON L.CREATED_BY_ID = U2.ID';
		$modifyByJoin = 'LEFT JOIN b_user U3 ON L.MODIFY_BY_ID = U3.ID';

		$result = array(
			'ID' => array('FIELD' => 'L.ID', 'TYPE' => 'int'),
			'TITLE' => array('FIELD' => 'L.TITLE', 'TYPE' => 'string'),
			'HONORIFIC' => array('FIELD' => 'L.HONORIFIC', 'TYPE' => 'string'),
			'NAME' => array('FIELD' => 'L.NAME', 'TYPE' => 'string'),
			'SECOND_NAME' => array('FIELD' => 'L.SECOND_NAME', 'TYPE' => 'string'),
			'LAST_NAME' => array('FIELD' => 'L.LAST_NAME', 'TYPE' => 'string'),
			'FULL_NAME' => array('FIELD' => 'L.FULL_NAME', 'TYPE' => 'string'),
			'COMPANY_TITLE' => array('FIELD' => 'L.COMPANY_TITLE', 'TYPE' => 'string'),

			'COMPANY_ID' => array('FIELD' => 'L.COMPANY_ID', 'TYPE' => 'int'),
			'ASSOCIATED_COMPANY_TITLE' => array('FIELD' => 'CO.TITLE', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'CONTACT_ID' => array('FIELD' => 'L.CONTACT_ID', 'TYPE' => 'int'),
			'CONTACT_HONORIFIC' => array('FIELD' => 'C.HONORIFIC', 'TYPE' => 'string', 'FROM' => $contactJoin),
			'CONTACT_NAME' => array('FIELD' => 'C.NAME', 'TYPE' => 'string', 'FROM' => $contactJoin),
			'CONTACT_SECOND_NAME' => array('FIELD' => 'C.SECOND_NAME', 'TYPE' => 'string', 'FROM' => $contactJoin),
			'CONTACT_LAST_NAME' => array('FIELD' => 'C.LAST_NAME', 'TYPE' => 'string', 'FROM' => $contactJoin),
			'CONTACT_FULL_NAME' => array('FIELD' => 'C.FULL_NAME', 'TYPE' => 'string', 'FROM' => $contactJoin),
			'IS_RETURN_CUSTOMER' => array('FIELD' => 'L.IS_RETURN_CUSTOMER', 'TYPE' => 'char'),

			'BIRTHDATE' => array('FIELD' => 'L.BIRTHDATE', 'TYPE' => 'date'),
			'BIRTHDAY_SORT' => array('FIELD' => 'L.BIRTHDAY_SORT', 'TYPE' => 'int'),

			'SOURCE_ID' => array('FIELD' => 'L.SOURCE_ID', 'TYPE' => 'string'),
			'SOURCE_DESCRIPTION' => array('FIELD' => 'L.SOURCE_DESCRIPTION', 'TYPE' => 'string'),
			'STATUS_ID' => array('FIELD' => 'L.STATUS_ID', 'TYPE' => 'string'),
			'STATUS_DESCRIPTION' => array('FIELD' => 'L.STATUS_DESCRIPTION', 'TYPE' => 'string'),

			'POST' => array('FIELD' => 'L.POST', 'TYPE' => 'string'),
			'COMMENTS' => array('FIELD' => 'L.COMMENTS', 'TYPE' => 'string'),

			'CURRENCY_ID' => array('FIELD' => 'L.CURRENCY_ID', 'TYPE' => 'string'),
			'EXCH_RATE' => array('FIELD' => 'L.EXCH_RATE', 'TYPE' => 'double'),
			'OPPORTUNITY' => array('FIELD' => 'L.OPPORTUNITY', 'TYPE' => 'double'),
			'IS_MANUAL_OPPORTUNITY' => array('FIELD' => 'L.IS_MANUAL_OPPORTUNITY', 'TYPE' => 'char'),
			'ACCOUNT_CURRENCY_ID' => array('FIELD' => 'L.ACCOUNT_CURRENCY_ID', 'TYPE' => 'string'),
			'OPPORTUNITY_ACCOUNT' => array('FIELD' => 'L.OPPORTUNITY_ACCOUNT', 'TYPE' => 'double'),

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

			'DATE_CREATE' => array('FIELD' => 'L.DATE_CREATE', 'TYPE' => 'datetime'),
			'DATE_MODIFY' => array('FIELD' => 'L.DATE_MODIFY', 'TYPE' => 'datetime'),
			'DATE_CLOSED' => array('FIELD' => 'L.DATE_CLOSED', 'TYPE' => 'datetime'),

			'STATUS_SEMANTIC_ID' => array('FIELD' => 'L.STATUS_SEMANTIC_ID', 'TYPE' => 'string'),

			'OPENED' => array('FIELD' => 'L.OPENED', 'TYPE' => 'char'),
			'WEBFORM_ID' => array('FIELD' => 'L.WEBFORM_ID', 'TYPE' => 'int'),
			'ORIGINATOR_ID' => array('FIELD' => 'L.ORIGINATOR_ID', 'TYPE' => 'string'), //EXTERNAL SYSTEM THAT OWNS THIS ITEM
			'ORIGIN_ID' => array('FIELD' => 'L.ORIGIN_ID', 'TYPE' => 'string'), //ITEM ID IN EXTERNAL SYSTEM
			'FACE_ID' => array('FIELD' => 'L.FACE_ID', 'TYPE' => 'int'),

			'MOVED_BY_ID' => ['FIELD' => 'L.MOVED_BY_ID', 'TYPE' => 'int'],
			'MOVED_TIME' => ['FIELD' => 'L.MOVED_TIME', 'TYPE' => 'datetime'],

			// For compatibility only
			'PRODUCT_ID' => array('FIELD' => 'L.PRODUCT_ID', 'TYPE' => 'string')
		);

		if(!(is_array($arOptions) && isset($arOptions['DISABLE_ADDRESS']) && $arOptions['DISABLE_ADDRESS']))
		{
			$addrJoin = 'LEFT JOIN b_crm_addr ADDR ON L.ID = ADDR.ENTITY_ID AND ADDR.TYPE_ID = '
				.EntityAddressType::Primary.' AND ADDR.ENTITY_TYPE_ID = '.CCrmOwnerType::Lead;

			$result['ADDRESS'] = array('FIELD' => 'ADDR.ADDRESS_1', 'TYPE' => 'string', 'FROM' => $addrJoin);
			$result['ADDRESS_2'] = array('FIELD' => 'ADDR.ADDRESS_2', 'TYPE' => 'string', 'FROM' => $addrJoin);
			$result['ADDRESS_CITY'] = array('FIELD' => 'ADDR.CITY', 'TYPE' => 'string', 'FROM' => $addrJoin);
			$result['ADDRESS_POSTAL_CODE'] = array('FIELD' => 'ADDR.POSTAL_CODE', 'TYPE' => 'string', 'FROM' => $addrJoin);
			$result['ADDRESS_REGION'] = array('FIELD' => 'ADDR.REGION', 'TYPE' => 'string', 'FROM' => $addrJoin);
			$result['ADDRESS_PROVINCE'] = array('FIELD' => 'ADDR.PROVINCE', 'TYPE' => 'string', 'FROM' => $addrJoin);
			$result['ADDRESS_COUNTRY'] = array('FIELD' => 'ADDR.COUNTRY', 'TYPE' => 'string', 'FROM' => $addrJoin);
			$result['ADDRESS_COUNTRY_CODE'] = array('FIELD' => 'ADDR.COUNTRY_CODE', 'TYPE' => 'string', 'FROM' => $addrJoin);
			$result['ADDRESS_LOC_ADDR_ID'] = array('FIELD' => 'ADDR.LOC_ADDR_ID', 'TYPE' => 'integer', 'FROM' => $addrJoin);
		}

		// Creation of field aliases
		$result['ASSIGNED_BY'] = $result['ASSIGNED_BY_ID'];
		$result['CREATED_BY'] = $result['CREATED_BY_ID'];
		$result['MODIFY_BY'] = $result['MODIFY_BY_ID'];

		$additionalFields = is_array($arOptions) && isset($arOptions['ADDITIONAL_FIELDS'])
			? $arOptions['ADDITIONAL_FIELDS'] : null;

		if(is_array($additionalFields))
		{
			if(in_array('STATUS_SORT', $additionalFields, true))
			{
				$statusJoin = "LEFT JOIN b_crm_status ST ON ST.ENTITY_ID = 'STATUS' AND L.STATUS_ID = ST.STATUS_ID";
				$result['STATUS_SORT'] = array('FIELD' => 'ST.SORT', 'TYPE' => 'int', 'FROM' => $statusJoin);
			}

			if(in_array('ACTIVITY', $additionalFields, true))
			{
				$commonActivityJoin = CCrmActivity::PrepareJoin(0, CCrmOwnerType::Lead, 'L', 'AC', 'UAC', 'ACUSR');

				$result['C_ACTIVITY_ID'] = ['FIELD' => 'UAC.ACTIVITY_ID', 'TYPE' => 'int', 'FROM' => $commonActivityJoin];
				$result['C_ACTIVITY_TIME'] = ['FIELD' => 'UAC.ACTIVITY_TIME', 'TYPE' => 'datetime', 'FROM' => $commonActivityJoin];
				$result['C_ACTIVITY_SUBJECT'] = ['FIELD' => 'AC.SUBJECT', 'TYPE' => 'string', 'FROM' => $commonActivityJoin];
				$result['C_ACTIVITY_RESP_ID'] = ['FIELD' => 'AC.RESPONSIBLE_ID', 'TYPE' => 'int', 'FROM' => $commonActivityJoin];
				$result['C_ACTIVITY_RESP_LOGIN'] = ['FIELD' => 'ACUSR.LOGIN', 'TYPE' => 'string', 'FROM' => $commonActivityJoin];
				$result['C_ACTIVITY_RESP_NAME'] = ['FIELD' => 'ACUSR.NAME', 'TYPE' => 'string', 'FROM' => $commonActivityJoin];
				$result['C_ACTIVITY_RESP_LAST_NAME'] = ['FIELD' => 'ACUSR.LAST_NAME', 'TYPE' => 'string', 'FROM' => $commonActivityJoin];
				$result['C_ACTIVITY_RESP_SECOND_NAME'] = ['FIELD' => 'ACUSR.SECOND_NAME', 'TYPE' => 'string', 'FROM' => $commonActivityJoin];
				$result['C_ACTIVITY_TYPE_ID'] = ['FIELD' => 'AC.TYPE_ID', 'TYPE' => 'int', 'FROM' => $commonActivityJoin];
				$result['C_ACTIVITY_PROVIDER_ID'] = ['FIELD' => 'AC.PROVIDER_ID', 'TYPE' => 'string', 'FROM' => $commonActivityJoin];

				$userID = CCrmPerms::GetCurrentUserID();
				if($userID > 0)
				{
					$activityJoin = CCrmActivity::PrepareJoin($userID, CCrmOwnerType::Lead, 'L', 'A', 'UA', '');

					$result['ACTIVITY_ID'] = ['FIELD' => 'UA.ACTIVITY_ID', 'TYPE' => 'int', 'FROM' => $activityJoin];
					$result['ACTIVITY_TIME'] = ['FIELD' => 'UA.ACTIVITY_TIME', 'TYPE' => 'datetime', 'FROM' => $activityJoin];
					$result['ACTIVITY_SUBJECT'] = ['FIELD' => 'A.SUBJECT', 'TYPE' => 'string', 'FROM' => $activityJoin];
					$result['ACTIVITY_TYPE_ID'] = ['FIELD' => 'A.TYPE_ID', 'TYPE' => 'int', 'FROM' => $activityJoin];
					$result['ACTIVITY_PROVIDER_ID'] = ['FIELD' => 'A.PROVIDER_ID', 'TYPE' => 'string', 'FROM' => $activityJoin];
				}
			}
		}

		// add utm fields
		$result = array_merge($result, UtmTable::getFieldsDescriptionByEntityTypeId(CCrmOwnerType::Lead));

		$result = array_merge(
			$result,
			Crm\Service\Container::getInstance()->getParentFieldManager()->getParentFieldsSqlInfo(
				CCrmOwnerType::Lead,
				'L'
			)
		);

		$result += self::getLastActivityAdapter()->getFields();

		return $result;
	}

	public static function __AfterPrepareSql(/*CCrmEntityListBuilder*/ $sender, $arOrder, $arFilter, $arGroupBy, $arSelectFields)
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
					CCrmOwnerType::Lead,
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

		if (!empty($arFilter['ACTIVE_TIME_PERIOD_from']) || !empty($arFilter['%STATUS_ID_FROM_HISTORY']) || !empty($arFilter['%STATUS_ID_FROM_SUPPOSED_HISTORY']) || !empty($arFilter['%STATUS_SEMANTIC_ID_FROM_HISTORY']))
		{
			global $DB;
			$supposedHistoryConditions = [];

			if (!empty($arFilter['ACTIVE_TIME_PERIOD_from']) && !empty($arFilter['ACTIVE_TIME_PERIOD_to']))
			{
				$supposedHistoryConditions[] = "LSHWS.LAST_UPDATE_DATE <= " . $DB->CharToDateFunction($arFilter['ACTIVE_TIME_PERIOD_to'], 'SHORT');
				$supposedHistoryConditions[] = "LSHWS.CLOSE_DATE >= " . $DB->CharToDateFunction($arFilter['ACTIVE_TIME_PERIOD_from'], 'SHORT');
			}
			if (!empty($arFilter['%STATUS_SEMANTIC_ID_FROM_HISTORY']))
			{
				$statusSemanticIdsFromFilter = is_array($arFilter['%STATUS_SEMANTIC_ID_FROM_HISTORY']) ? $arFilter['%STATUS_SEMANTIC_ID_FROM_HISTORY'] : array($arFilter['%STATUS_SEMANTIC_ID_FROM_HISTORY']);
				$statusSemanticIdsForSql = [];
				foreach ($statusSemanticIdsFromFilter as $value)
				{
					$statusSemanticIdsForSql[] = "'" . \Bitrix\Main\Application::getConnection()->getSqlHelper()->forSql($value) . "'";
				}
				$supposedHistoryConditions[] = "LSHWS.IS_SUPPOSED = 'N'";
				$supposedHistoryConditions[] = "LSHWS.STATUS_SEMANTIC_ID IN (" . implode(', ', $statusSemanticIdsForSql) . ")";
			}
			if (!empty($arFilter['%STATUS_ID_FROM_HISTORY']))
			{
				$statusIdsFromFilter = is_array($arFilter['%STATUS_ID_FROM_HISTORY']) ? $arFilter['%STATUS_ID_FROM_HISTORY'] : array($arFilter['%STATUS_ID_FROM_HISTORY']);
				$statusIdsForSql = [];
				foreach ($statusIdsFromFilter as $value)
				{
					$statusIdsForSql[] = "'" . \Bitrix\Main\Application::getConnection()->getSqlHelper()->forSql($value) . "'";
				}
				$supposedHistoryConditions[] = "LSHWS.IS_SUPPOSED = 'N'";
				$supposedHistoryConditions[] = "LSHWS.STATUS_ID  IN (" . implode(', ', $statusIdsForSql) . ")";
			}
			if (!empty($arFilter['%STATUS_ID_FROM_SUPPOSED_HISTORY']))
			{
				$statusIdsFromFilter = is_array($arFilter['%STATUS_ID_FROM_SUPPOSED_HISTORY']) ? $arFilter['%STATUS_ID_FROM_SUPPOSED_HISTORY'] : array($arFilter['%STATUS_ID_FROM_SUPPOSED_HISTORY']);
				$statusIdsForSql = [];
				foreach ($statusIdsFromFilter as $value)
				{
					$statusIdsForSql[] = "'" . \Bitrix\Main\Application::getConnection()->getSqlHelper()->forSql($value) . "'";
				}
				$supposedHistoryConditions[] .= " LSHWS.STATUS_ID  IN (" . implode(', ', $statusIdsForSql) . ")";
			}

			$sqlData['WHERE'][] = "L.ID IN (SELECT DISTINCT LSHWS.OWNER_ID FROM b_crm_lead_status_history_with_supposed LSHWS WHERE " . implode(" AND ", $supposedHistoryConditions) . ")";
		}

		if(isset($arFilter['CALENDAR_DATE_FROM']) && $arFilter['CALENDAR_DATE_FROM'] !== ''
		&& isset($arFilter['CALENDAR_DATE_TO']) && $arFilter['CALENDAR_DATE_TO'] !== '')
		{
			global $DB;

			if ($arFilter['CALENDAR_FIELD'] == 'DATE_CREATE')
			{
				$sqlData['WHERE'][] = "L.DATE_CREATE <= ".$DB->CharToDateFunction($arFilter['CALENDAR_DATE_TO'], 'SHORT');
				$sqlData['WHERE'][] = "L.DATE_CREATE >= ".$DB->CharToDateFunction($arFilter['CALENDAR_DATE_FROM'], 'SHORT');
			}
			else
			{
				[$ufId, $ufType, $ufName] = \Bitrix\Crm\Integration\Calendar::parseUserfieldKey($arFilter['CALENDAR_FIELD']);

				if (intval($ufId) > 0 && $ufType == 'resourcebooking' || is_null($ufType))
				{
					// L = b_crm_lead
					$sqlData['FROM'][] = "INNER JOIN b_calendar_resource RBUF ".
						"ON RBUF.PARENT_ID = L.ID".
						" AND RBUF.PARENT_TYPE = 'CRM_LEAD'".
						" AND RBUF.UF_ID = ".intval($arFilter['CALENDAR_FIELD']);

					$sqlData['SELECT'][] = $DB->DateToCharFunction("RBUF.DATE_FROM").' as RES_BOOKING_FROM';
					$sqlData['SELECT'][] = $DB->DateToCharFunction("RBUF.DATE_TO").' as RES_BOOKING_TO';
					$sqlData['SELECT'][] = 'RBUF.SKIP_TIME as RES_BOOKING_SKIP_TIME';
					$sqlData['SELECT'][] = 'RBUF.TZ_FROM as RES_BOOKING_TZ_FROM';
					$sqlData['SELECT'][] = 'RBUF.TZ_TO as RES_BOOKING_TZ_TO';
					$sqlData['SELECT'][] = 'RBUF.RESOURCE_ID as RES_BOOKING_RESOURCE_ID';
					$sqlData['SELECT'][] = 'RBUF.CAL_TYPE as RES_BOOKING_CAL_TYPE';
					$sqlData['SELECT'][] = 'RBUF.EVENT_ID as RES_BOOKING_EVENT_ID';

					$sqlData['WHERE'][] = "RBUF.DATE_FROM <= ".$DB->CharToDateFunction($arFilter['CALENDAR_DATE_TO'], 'SHORT');
					$sqlData['WHERE'][] = "RBUF.DATE_TO >= ".$DB->CharToDateFunction($arFilter['CALENDAR_DATE_FROM'], 'SHORT');
				}
				elseif(intval($ufId) > 0 && ($ufType == 'date' || $ufType == 'datetime'))
				{
					if (!in_array($ufName, $arSelectFields))
					{
						$alias = $sender->GetTableAlias();

						$ufSelectSql = new CUserTypeSQL();
						$ufSelectSql->SetEntity(self::GetUserFieldEntityID(), $alias.'.ID');
						$ufSelectSql->SetSelect(array($ufName));
						$sqlData['SELECT'][] = trim($ufSelectSql->GetSelect(), ', ');
						$sqlData['FROM'][] = $ufSelectSql->GetJoin($alias.'.ID');
					}

					$sqlData['WHERE'][] = $DB->ForSql($ufName)." <= ".$DB->CharToDateFunction($arFilter['CALENDAR_DATE_TO'], 'SHORT');
					$sqlData['WHERE'][] = $DB->ForSql($ufName)." >= ".$DB->CharToDateFunction($arFilter['CALENDAR_DATE_FROM'], 'SHORT');
				}
			}
		}

		// Applying filter by PRODUCT_ID
		$operationInfo = Crm\UI\Filter\EntityHandler::findFieldOperation('PRODUCT_ROW_PRODUCT_ID', $arFilter);
		if(is_array($operationInfo))
		{
			$prodID = (int)$operationInfo['CONDITION'];
			if($prodID > 0 && $operationInfo['OPERATION'] === '=')
			{
				$tableAlias = $sender->GetTableAlias();
				$sqlData['WHERE'][] = "{$tableAlias}.ID IN (SELECT LP.OWNER_ID from b_crm_product_row LP where LP.OWNER_TYPE = 'L' and LP.OWNER_ID = {$tableAlias}.ID and LP.PRODUCT_ID = {$prodID})";
			}
		}

		$operationInfo = Crm\UI\Filter\EntityHandler::findFieldOperation('ASSOCIATED_CONTACT_ID', $arFilter);
		if(is_array($operationInfo))
		{
			if($operationInfo['OPERATION'] === '=')
			{
				$sqlData['FROM'][] = LeadContactTable::prepareFilterJoinSql(
					CCrmOwnerType::Contact,
					$operationInfo['CONDITION'],
					$sender->GetTableAlias()
				);
			}
		}

		Tracking\UI\Filter::buildFilterAfterPrepareSql(
			$sqlData,
			$arFilter,
			\CCrmOwnerType::Lead,
			$sender->GetTableAlias()
		);

		$result = array();
		if(!empty($sqlData['SELECT']))
		{
			$result['SELECT'] = ", ".implode(', ', $sqlData['SELECT']);
		}
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
	// <-- Service

	public static function GetUserFieldEntityID()
	{
		return self::USER_FIELD_ENTITY_ID;
	}
	public static function GetUserFields($langID = false)
	{
		global $USER_FIELD_MANAGER;
		return $USER_FIELD_MANAGER->GetUserFields(self::USER_FIELD_ENTITY_ID, 0, $langID);
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

		return \Bitrix\Crm\Entity\Lead::getInstance()->getTopIDs([
			'order' => ['ID' => $sortType],
			'limit' => $top,
			'userPermissions' => $userPermissions
		]);
	}

	public static function GetTotalCount()
	{
		if(defined('BX_COMP_MANAGED_CACHE') && $GLOBALS['CACHE_MANAGER']->Read(self::CACHE_TTL, self::TOTAL_COUNT_CACHE_ID, 'b_crm_lead'))
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
			CCrmLead::DB_TYPE,
			CCrmLead::TABLE_NAME,
			self::TABLE_ALIAS,
			self::GetFields(isset($arOptions['FIELD_OPTIONS']) ? $arOptions['FIELD_OPTIONS'] : null),
			self::$sUFEntityID,
			'LEAD',
			array('CCrmLead', 'BuildPermSql'),
			array('CCrmLead', '__AfterPrepareSql')
		);

		return $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields, $arOptions);
	}

	public static function CreateListBuilder(array $arFieldOptions = null)
	{
		return new CCrmEntityListBuilder(
			CCrmLead::DB_TYPE,
			CCrmLead::TABLE_NAME,
			self::TABLE_ALIAS,
			self::GetFields($arFieldOptions),
			self::$sUFEntityID,
			'LEAD',
			array('CCrmLead', 'BuildPermSql'),
			array('CCrmLead', '__AfterPrepareSql')
		);
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
			'CONTACT_ID' => 'L.CONTACT_ID',
			'COMPANY_ID' => 'L.COMPANY_ID',
			'IS_RETURN_CUSTOMER' => 'L.IS_RETURN_CUSTOMER',
			'POST' => 'L.POST',
			'ADDRESS' => 'L.ADDRESS',
			'COMMENTS' => 'L.COMMENTS',
			'NAME' => 'L.NAME',
			'COMPANY_TITLE' => 'L.COMPANY_TITLE',
			'TITLE' => 'L.TITLE',
			'PRODUCT_ID' => 'L.PRODUCT_ID',
			'SOURCE_ID' => 'L.SOURCE_ID',
			'SOURCE_DESCRIPTION' => 'L.SOURCE_DESCRIPTION',
			'STATUS_ID' => 'L.STATUS_ID',
			'STATUS_DESCRIPTION' => 'L.STATUS_DESCRIPTION',
			'SECOND_NAME' => 'L.SECOND_NAME',
			'LAST_NAME' => 'L.LAST_NAME',
			'FULL_NAME' => 'L.FULL_NAME',
			'OPPORTUNITY' => 'L.OPPORTUNITY',
			'IS_MANUAL_OPPORTUNITY' => 'L.IS_MANUAL_OPPORTUNITY',
			'CURRENCY_ID' => 'L.CURRENCY_ID',
			'OPPORTUNITY_ACCOUNT' => 'L.OPPORTUNITY_ACCOUNT',
			'ACCOUNT_CURRENCY_ID' => 'L.ACCOUNT_CURRENCY_ID',
			'ASSIGNED_BY' => 'L.ASSIGNED_BY_ID',
			'ASSIGNED_BY_ID' => 'L.ASSIGNED_BY_ID',
			'CREATED_BY' => 'L.CREATED_BY_ID',
			'CREATED_BY_ID' => 'L.CREATED_BY_ID',
			'MODIFY_BY' => 'L.MODIFY_BY_ID',
			'MODIFY_BY_ID' => 'L.MODIFY_BY_ID',
			'DATE_CREATE' => $DB->DateToCharFunction('L.DATE_CREATE'),
			'DATE_MODIFY' => $DB->DateToCharFunction('L.DATE_MODIFY'),
			'BIRTHDATE' => $DB->DateToCharFunction('L.BIRTHDATE'),
			'OPENED' => 'L.OPENED',
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
			'MODIFY_BY_SECOND_NAME' => 'U3.SECOND_NAME',
			'EXCH_RATE' => 'L.EXCH_RATE',
			'ORIGINATOR_ID' => 'L.ORIGINATOR_ID', //EXTERNAL SYSTEM THAT OWNS THIS ITEM
			'ORIGIN_ID' => 'L.ORIGIN_ID', //ITEM ID IN EXTERNAL SYSTEM
			'DATE_CLOSED' => $DB->DateToCharFunction('L.DATE_CLOSED')
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
			$res = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'LEAD', 'FILTER' => $arFilter['FM']));
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
			&& (!array_key_exists('CHECK_PERMISSIONS', $arFilter) ||  $arFilter['CHECK_PERMISSIONS'] !== 'N')
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
			'CONTACT_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.CONTACT_ID',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'COMPANY_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.COMPANY_ID',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'IS_RETURN_CUSTOMER' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.IS_RETURN_CUSTOMER',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'SOURCE_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.SOURCE_ID',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'STATUS_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.STATUS_ID',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'CURRENCY_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.CURRENCY_ID',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'OPPORTUNITY' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.OPPORTUNITY',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'IS_MANUAL_OPPORTUNITY' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.IS_MANUAL_OPPORTUNITY',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'ACCOUNT_CURRENCY_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.ACCOUNT_CURRENCY_ID',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'OPPORTUNITY_ACCOUNT' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.OPPORTUNITY_ACCOUNT',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'PRODUCT_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.PRODUCT_ID',
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
			'TITLE' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.TITLE',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'COMPANY_TITLE' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.COMPANY_TITLE',
				'FIELD_TYPE' => 'string',
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
			'EXCH_RATE' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.EXCH_RATE',
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
			'DATE_CLOSED' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.DATE_CLOSED',
				'FIELD_TYPE' => 'datetime',
				'JOIN' => false
			)
		);

		$obQueryWhere->SetFields($arWhereFields);
		if(!is_array($arFilter))
			$arFilter = array();
		$sQueryWhereFields = $obQueryWhere->GetQuery($arFilter);

		$sSqlSearch = '';
		foreach($arSqlSearch as $r)
			if($r <> '')
				$sSqlSearch .= "\n\t\t\t\tAND  ($r) ";
		$CCrmUserType = new CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], self::$sUFEntityID);
		$CCrmUserType->ListPrepareFilter($arFilter);
		$r = $obUserFieldsSql->GetFilter();
		if($r <> '')
			$sSqlSearch .= "\n\t\t\t\tAND ($r) ";

		if(!empty($sQueryWhereFields))
			$sSqlSearch .= "\n\t\t\t\tAND ($sQueryWhereFields) ";

		$arFieldsOrder = array(
			'ASSIGNED_BY' => 'L.ASSIGNED_BY_ID',
			'CREATED_BY' => 'L.CREATED_BY_ID',
			'MODIFY_BY' => 'L.MODIFY_BY_ID',
			'DATE_CREATE' => 'L.DATE_CREATE',
			'DATE_MODIFY' => 'L.DATE_MODIFY',
			'DATE_CLOSED' => 'L.DATE_CLOSED'
		);

		// order
		$arSqlOrder = Array();
		if (!is_array($arOrder))
			$arOrder = Array('DATE_CREATE' => 'DESC');
		foreach ($arOrder as $by => $order)
		{
			$by = mb_strtoupper($by);
			$order = mb_strtolower($order);
			if($order != 'asc')
				$order = 'desc';

			if (isset($arFieldsOrder[$by]))
				$arSqlOrder[$by] = " {$arFieldsOrder[$by]} $order ";
			else if(isset($arFields[$by]) && $by != 'ADDRESS')
				$arSqlOrder[$by] = " L.$by $order ";
			else if($s = $obUserFieldsSql->GetOrder($by))
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
				b_crm_lead L $sSqlJoin
				{$obUserFieldsSql->GetJoin('L.ID')}
			WHERE
				1=1
				$sSqlSearch
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

		$dbRes = CCrmLead::GetListEx(array(), $arFilter);
		return $dbRes->Fetch();
	}

	static public function BuildPermSql($sAliasPrefix = 'L', $mPermType = 'READ', $arOptions = [])
	{
		$userId = null;
		if (isset($arOptions['PERMS']) && is_object($arOptions['PERMS']))
		{
			/** @var \CCrmPerms $arOptions['PERMS'] */
			$userId = $arOptions['PERMS']->GetUserID();
		}
		$builderOptions =
			Crm\Security\QueryBuilder\Options::createFromArray((array)$arOptions)
				->setOperations((array)$mPermType)
				->setAliasPrefix((string)$sAliasPrefix)
		;

		$queryBuilder = Crm\Service\Container::getInstance()
			->getUserPermissions($userId)
			->createListQueryBuilder(self::$TYPE_NAME, $builderOptions)
		;

		return $queryBuilder->buildCompatible();
	}
	public function Add(array &$arFields, $bUpdateSearch = true, $options = array())
	{
		global $DB;

		if(!is_array($options))
		{
			$options = array();
		}

		if ($this->isUseOperation())
		{
			return $this->getCompatibilityAdapter()->performAdd($arFields, $options);
		}

		$this->LAST_ERROR = '';
		$this->checkExceptions = array();

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

		if(!($isRestoration && isset($arFields['MOVED_TIME'])))
		{
			unset($arFields['MOVED_TIME']);
		}
		if(!($isRestoration && isset($arFields['MOVED_BY_ID'])))
		{
			unset($arFields['MOVED_BY_ID']);
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

		if(!isset($arFields['OPPORTUNITY']))
		{
			$arFields['OPPORTUNITY'] = 0.0;
		}

		if(!isset($arFields['TITLE']) || !is_string($arFields['TITLE']) || trim($arFields['TITLE']) === '')
		{
			$arFields['TITLE'] = self::GetDefaultTitle();
		}

		$fields = self::GetUserFields();
		$this->fillEmptyFieldValues($arFields, $fields);

		if(!$this->CheckFields($arFields, false, $options))
		{
			$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
			return false;
		}

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

		if (!isset($arFields['MOVED_BY_ID']))
		{
			$arFields['MOVED_BY_ID'] = (int)$userID;
		}
		if (!isset($arFields['MOVED_TIME']))
		{
			$arFields['MOVED_TIME'] = (new \Bitrix\Main\Type\DateTime())->toString();
		}

		self::getLastActivityAdapter()->performAdd($arFields, $options);

		$permissionTypeId = (
			$this->bCheckPermission
				? Bitrix\Crm\Security\EntityPermissionType::CREATE
				: Bitrix\Crm\Security\EntityPermissionType::UNDEFINED
		);

		if(!isset($arFields['STATUS_ID']) || (string)$arFields['STATUS_ID'] === '')
		{
			$arFields['STATUS_ID'] = self::GetStartStatusID($permissionTypeId);
		}

		$viewMode = ($options['ITEM_OPTIONS']['VIEW_MODE'] ?? null);

		$viewModeActivitiesStatusId = null;
		if ($viewMode === ViewMode::MODE_ACTIVITIES)
		{
			$viewModeActivitiesStatusId = $arFields['STATUS_ID'];
			$arFields['STATUS_ID'] = self::GetStartStatusID($permissionTypeId);
		}

		$isStatusExist = self::IsStatusExists($arFields['STATUS_ID']);
		$arFields['STATUS_SEMANTIC_ID'] = $isStatusExist
			? self::GetSemanticID($arFields['STATUS_ID'])
			: Bitrix\Crm\PhaseSemantics::UNDEFINED
		;

		if (isset($arFields['DATE_CLOSED']))
			unset($arFields['DATE_CLOSED']);
		self::EnsureStatusesLoaded();
		if (in_array($arFields['STATUS_ID'], self::$LEAD_STATUSES_BY_GROUP['FINISHED']))
			$arFields['~DATE_CLOSED'] = $DB->CurrentTimeFunction();

		$observerIDs = isset($arFields['OBSERVER_IDS']) && is_array($arFields['OBSERVER_IDS'])
			? $arFields['OBSERVER_IDS'] : null;
		unset($arFields['OBSERVER_IDS']);

		$arAttr = array();

		$arAttr['STATUS_ID'] = $arFields['STATUS_ID'];
		if (!empty($arFields['OPENED']))
		{
			$arAttr['OPENED'] = $arFields['OPENED'];
		}

		if(!empty($observerIDs))
		{
			$arAttr['CONCERNED_USER_IDS'] = $observerIDs;
		}

		$sPermission = 'ADD';
		if (isset($arFields['PERMISSION']))
		{
			if ($arFields['PERMISSION'] == 'IMPORT')
				$sPermission = 'IMPORT';
			unset($arFields['PERMISSION']);
		}

		$assignedByID = (int)$arFields['ASSIGNED_BY_ID'];
		if($this->bCheckPermission)
		{
			$arEntityAttr = self::BuildEntityAttr($userID, $arAttr);
			$userPerms =  $userID == CCrmPerms::GetCurrentUserID() ? $this->cPerms : CCrmPerms::GetUserPermissions($userID);
			$sEntityPerm = $userPerms->GetPermType('LEAD', $sPermission, $arEntityAttr);
			if ($sEntityPerm == BX_CRM_PERM_NONE)
			{
				$this->LAST_ERROR = GetMessage('CRM_PERMISSION_DENIED');
				$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
				return false;
			}

			if ($sEntityPerm == BX_CRM_PERM_SELF && $assignedByID != $userID)
			{
				$arFields['ASSIGNED_BY_ID'] = $assignedByID = $userID;
			}
			if ($sEntityPerm == BX_CRM_PERM_OPEN && $userID == $assignedByID)
			{
				$arFields['OPENED'] = 'Y';
			}
		}

		$assignedByID = (int)$arFields['ASSIGNED_BY_ID'];
		$arEntityAttr = self::BuildEntityAttr($assignedByID, $arAttr);
		$userPerms =  $assignedByID == CCrmPerms::GetCurrentUserID() ? $this->cPerms : CCrmPerms::GetUserPermissions($assignedByID);
		$sEntityPerm = $userPerms->GetPermType(self::$TYPE_NAME, $sPermission, $arEntityAttr);
		$this->PrepareEntityAttrs($arEntityAttr, $sEntityPerm);

		//Prepare currency & exchange rate
		if(!isset($arFields['CURRENCY_ID']))
		{
			$arFields['CURRENCY_ID'] = CCrmCurrency::GetBaseCurrencyID();
		}

		if(!isset($arFields['EXCH_RATE']))
		{
			$arFields['EXCH_RATE'] = CCrmCurrency::GetExchangeRate($arFields['CURRENCY_ID']);
		}

		$arFields = array_merge($arFields, \CCrmAccountingHelper::calculateAccountingData($arFields));

		if (isset($arFields['NAME']) || isset($arFields['LAST_NAME']))
		{
			$arFields['FULL_NAME'] = trim((isset($arFields['NAME'])? $arFields['NAME']: '').' '.(isset($arFields['LAST_NAME'])? $arFields['LAST_NAME']: ''));
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

		//region Preparation of contacts
		$contactBindings = isset($arFields['CONTACT_BINDINGS']) && is_array($arFields['CONTACT_BINDINGS'])
			? $arFields['CONTACT_BINDINGS'] : null;
		$contactIDs = isset($arFields['CONTACT_IDS']) && is_array($arFields['CONTACT_IDS'])
			? $arFields['CONTACT_IDS'] : null;
		unset($arFields['CONTACT_IDS']);
		//For backward compatibility only
		$contactID = isset($arFields['CONTACT_ID']) ? max((int)$arFields['CONTACT_ID'], 0) : null;
		if($contactID !== null && $contactIDs === null && $contactBindings === null)
		{
			$contactIDs = array();
			if($contactID > 0)
			{
				$contactIDs[] = $contactID;
			}
		}
		unset($arFields['CONTACT_ID']);

		if(is_array($contactIDs) && !is_array($contactBindings))
		{
			$contactBindings = EntityBinding::prepareEntityBindings(
				\CCrmOwnerType::Contact,
				$contactIDs
			);

			EntityBinding::markFirstAsPrimary($contactBindings);
		}
		elseif(is_array($contactBindings))
		{
			if(EntityBinding::findPrimaryBinding($contactBindings) === null)
			{
				EntityBinding::markFirstAsPrimary($contactBindings);
			}

			$contactIDs = EntityBinding::prepareEntityIDs(
				CCrmOwnerType::Contact,
				$contactBindings
			);
		}
		//endregion

		//region Synchronize CustomerType
		$customerType = isset($arFields['IS_RETURN_CUSTOMER']) && $arFields['IS_RETURN_CUSTOMER'] === 'Y'
			? CustomerType::RETURNING : CustomerType::GENERAL;

		$effectiveCustomerType = CustomerType::GENERAL;

		$companyID = isset($arFields['COMPANY_ID']) ? (int)$arFields['COMPANY_ID'] : 0;
		if($companyID > 0)
		{
			$effectiveCustomerType = CustomerType::RETURNING;
		}
		elseif((is_array($contactIDs) && !empty($contactIDs))
			|| !is_array($contactIDs) && !empty($originalContactIDs)
		)
		{
			$effectiveCustomerType = CustomerType::RETURNING;
		}

		if($customerType !== $effectiveCustomerType)
		{
			$arFields['IS_RETURN_CUSTOMER'] = $effectiveCustomerType === CustomerType::RETURNING ? 'Y' : 'N';
		}
		//endregion

		//region Rise BeforeAdd event
		$beforeEvents = GetModuleEvents('crm', 'OnBeforeCrmLeadAdd');
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
					$this->LAST_ERROR = GetMessage('CRM_LEAD_CREATION_CANCELED', array('#NAME#' => $arEvent['TO_NAME']));
					$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
				}
				return false;
			}
		}
		//endregion

		unset($arFields['ID']);

		$this->normalizeEntityFields($arFields);
		$ID = (int) $DB->Add(self::TABLE_NAME, $arFields, [], '', false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);

		//Append ID to TITLE if required
		if($ID > 0 && $arFields['TITLE'] === self::GetDefaultTitle())
		{
			$arFields['TITLE'] = self::GetDefaultTitle($ID);
			$sUpdate = $DB->PrepareUpdate('b_crm_lead', array('TITLE' => $arFields['TITLE']));
			if($sUpdate <> '')
			{
				$DB->Query("UPDATE b_crm_lead SET {$sUpdate} WHERE ID = {$ID}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			};
		}

		if(defined('BX_COMP_MANAGED_CACHE'))
		{
			$GLOBALS['CACHE_MANAGER']->CleanDir('b_crm_lead');
		}

		$arFields['ID'] = $ID;
		$arFields['DATE_CREATE'] = $arFields['DATE_MODIFY'] = ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL');

		//region Save Observers
		if(!empty($observerIDs))
		{
			Crm\Observer\ObserverManager::registerBulk($observerIDs, \CCrmOwnerType::Lead, $ID);
		}
		//endregion

		$securityRegisterOptions = (new \Bitrix\Crm\Security\Controller\RegisterOptions())
			->setEntityAttributes($arEntityAttr)
		;
		Crm\Security\Manager::getEntityController(CCrmOwnerType::Lead)
			->register(self::$TYPE_NAME, $ID, $securityRegisterOptions)
		;

		$addressFields = array(
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
		);

		if(!\Bitrix\Crm\EntityAddress::isEmpty($addressFields) || $addressFields['LOC_ADDR'])
		{
			\Bitrix\Crm\EntityAddress::register(
				CCrmOwnerType::Lead,
				$ID,
				EntityAddressType::Primary,
				$addressFields
			);
		}

		CCrmEntityHelper::NormalizeUserFields($arFields, self::$sUFEntityID, $GLOBALS['USER_FIELD_MANAGER'], array('IS_NEW' => true));
		$GLOBALS['USER_FIELD_MANAGER']->Update(self::$sUFEntityID, $ID, $arFields);

		//Statistics & History -->
		Bitrix\Crm\Statistics\LeadSumStatisticEntry::register($ID, $arFields);
		Bitrix\Crm\History\LeadStatusHistoryEntry::register($ID, $arFields, array('IS_NEW' => !$isRestoration));
		if($arFields['STATUS_ID'] === 'CONVERTED')
		{
			Bitrix\Crm\Statistics\LeadConversionStatisticsEntry::register($ID, $arFields, array('IS_NEW' => !$isRestoration));
		}
		//Bitrix\Crm\Statistics\LeadProcessStatisticsEntry::register($ID, $arFields, array('IS_NEW' => true));
		//<-- Statistics & History

		if($isRestoration)
		{
			Bitrix\Crm\Timeline\LeadController::getInstance()->onRestore($ID, array('FIELDS' => $arFields));
		}
		else
		{
			Bitrix\Crm\Timeline\LeadController::getInstance()->onCreate($ID, array('FIELDS' => $arFields));
		}

		CCrmEntityHelper::registerAdditionalTimelineEvents([
			'entityTypeId' => \CCrmOwnerType::Lead,
			'entityId' => $ID,
			'fieldsInfo' => static::GetFieldsInfo(),
			'previousFields' => [],
			'currentFields' => $arFields,
			'previousStageSemantics' => Crm\PhaseSemantics::UNDEFINED,
			'currentStageSemantics' => $arFields['STATUS_SEMANTIC_ID'] ?? Crm\PhaseSemantics::UNDEFINED,
			'options' => $options,
			'bindings' => [
				'entityTypeId' => \CCrmOwnerType::Contact,
				'previous' => [],
				'current' => $contactBindings,
			],
			'isMarkEventRegistrationEnabled' => false,
		]);

		//region Duplicate communication data
		if (isset($arFields['FM']) && is_array($arFields['FM']))
		{
			$CCrmFieldMulti = new CCrmFieldMulti();
			$CCrmFieldMulti->SetFields('LEAD', $ID, $arFields['FM']);
		}
		//endregion

		$duplicateCriterionRegistrar = DuplicateManager::getCriterionRegistrar(\CCrmOwnerType::Lead);
		$data =
			(new Crm\Integrity\CriterionRegistrar\Data())
				->setEntityTypeId(\CCrmOwnerType::Lead)
				->setEntityId($ID)
				->setCurrentFields($arFields)
		;
		$duplicateCriterionRegistrar->register($data);

		\Bitrix\Crm\Counter\Monitor::getInstance()->onEntityAdd(CCrmOwnerType::Lead, $arFields);
		// tracking of entity
		Tracking\Entity::onAfterAdd(CCrmOwnerType::Lead, $ID, $arFields);

		//region Save contacts
		if(!empty($contactBindings))
		{
			LeadContactTable::bindContacts($ID, $contactBindings);
			if(isset($GLOBALS['USER']))
			{
				CUserOptions::SetOption(
					'crm',
					'crm_contact_search',
					array('last_selected' => $contactIDs[count($contactIDs) - 1])
				);
			}
		}
		//endregion

		//region save parent relations
		Crm\Service\Container::getInstance()->getParentFieldManager()->saveParentRelationsForIdentifier(
			new Crm\ItemIdentifier(\CCrmOwnerType::Lead, $ID),
			$arFields
		);
		//endregion

		if($bUpdateSearch)
		{
			CCrmSearch::UpdateSearch(array('ID' => $ID, 'CHECK_PERMISSIONS' => 'N'), 'LEAD', true);
		}

		//region Search content index
		Bitrix\Crm\Search\SearchContentBuilderFactory::create(
			CCrmOwnerType::Lead
		)->build($ID, ['checkExist' => true]);
		//endregion

		self::getContentTypeIdAdapter()->performAdd($arFields, $options);

		if(isset($options['REGISTER_SONET_EVENT']) && $options['REGISTER_SONET_EVENT'] === true)
		{
			$opportunity = round((isset($arFields['OPPORTUNITY']) ? doubleval($arFields['OPPORTUNITY']) : 0.0), 2);
			$currencyID = isset($arFields['CURRENCY_ID']) ? $arFields['CURRENCY_ID'] : '';
			if($currencyID === '')
			{
				$currencyID = CCrmCurrency::GetBaseCurrencyID();
			}
			$assignedByID = intval($arFields['ASSIGNED_BY_ID']);
			$createdByID = intval($arFields['CREATED_BY_ID']);

			$liveFeedFields = array(
				'USER_ID' => $createdByID,
				'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
				'ENTITY_ID' => $ID,
				//'EVENT_ID' => $eventID,
				'TITLE' => GetMessage('CRM_LEAD_EVENT_ADD'),
				'MESSAGE' => '',
				'PARAMS' => array(
					'TITLE' => $arFields['TITLE'],
					'NAME' => isset($arFields['NAME']) ? $arFields['NAME'] : '',
					'SECOND_NAME' => isset($arFields['SECOND_NAME']) ? $arFields['SECOND_NAME'] : '',
					'LAST_NAME' => isset($arFields['LAST_NAME']) ? $arFields['LAST_NAME'] : '',
					'HONORIFIC' => isset($arFields['HONORIFIC']) ? $arFields['HONORIFIC'] : '',
					'COMPANY_TITLE' => isset($arFields['COMPANY_TITLE']) ? $arFields['COMPANY_TITLE'] : '',
					'STATUS_ID' => $arFields['STATUS_ID'],
					'OPPORTUNITY' => strval($opportunity),
					'CURRENCY_ID' => $currencyID,
					'AUTHOR_ID' => intval($arFields['CREATED_BY_ID']),
					'RESPONSIBLE_ID' => $assignedByID
				)
			);

			if (Crm\Settings\Crm::isLiveFeedRecordsGenerationEnabled())
			{
				CCrmSonetSubscription::RegisterSubscription(
					CCrmOwnerType::Lead,
					$ID,
					CCrmSonetSubscriptionType::Responsibility,
					$assignedByID
				);
			}

			$logEventID = CCrmLiveFeed::CreateLogEvent($liveFeedFields, CCrmLiveFeedEvent::Add, ['CURRENT_USER' => $userID]);

			if (
				$logEventID !== false
				&& $assignedByID != $createdByID
				&& CModule::IncludeModule("im")
				&& \Bitrix\Crm\Settings\LeadSettings::isEnabled()
			)
			{
				$url = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Lead, $ID);
				$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && SITE_SERVER_NAME <> '') ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));

				$arMessageFields = array(
					"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
					"TO_USER_ID" => $assignedByID,
					"FROM_USER_ID" => $createdByID,
					"NOTIFY_TYPE" => IM_NOTIFY_FROM,
					"NOTIFY_MODULE" => "crm",
					"LOG_ID" => $logEventID,
					//"NOTIFY_EVENT" => "lead_add",
					"NOTIFY_EVENT" => "changeAssignedBy",
					"NOTIFY_TAG" => "CRM|LEAD|".$ID,
					"NOTIFY_MESSAGE" => GetMessage("CRM_LEAD_RESPONSIBLE_IM_NOTIFY", Array("#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arFields['TITLE'])."</a>")),
					"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_LEAD_RESPONSIBLE_IM_NOTIFY", Array("#title#" => htmlspecialcharsbx($arFields['TITLE'])))." (".$serverName.$url.")"
				);
				CIMNotify::Add($arMessageFields);
			}
		}

		//region Rise AfterAdd event
		$afterEvents = GetModuleEvents('crm', 'OnAfterCrmLeadAdd');
		while ($arEvent = $afterEvents->Fetch())
		{
			ExecuteModuleEventEx($arEvent, array(&$arFields));
		}
		//endregion

		if(isset($arFields['ORIGIN_ID']) && $arFields['ORIGIN_ID'] !== '')
		{
			$afterEvents = GetModuleEvents('crm', 'OnAfterExternalCrmLeadAdd');
			while ($arEvent = $afterEvents->Fetch())
			{
				ExecuteModuleEventEx($arEvent, array(&$arFields));
			}
		}

		if ($ID>0)
		{
			if (
				$viewMode === ViewMode::MODE_ACTIVITIES
				&& $viewModeActivitiesStatusId
			)
			{
				$deadline = (new Crm\Kanban\EntityActivityDeadline())->getDeadline($viewModeActivitiesStatusId);

				if ($deadline)
				{
					\Bitrix\Crm\Activity\Entity\ToDo::createWithDefaultDescription(
						\CCrmOwnerType::Lead,
						$ID,
						$deadline
					);
				}
			}

			$item = Crm\Kanban\Entity::getInstance(self::$TYPE_NAME)
				->createPullItem($arFields);

			PullManager::getInstance()->sendItemAddedEvent(
				$item,
				[
					'TYPE' => self::$TYPE_NAME,
					'SKIP_CURRENT_USER' => ($userID !== 0),
				]
			);
		}

		return $ID;
	}

	static public function BuildEntityAttr($userID, $arAttr = array())
	{
		$userID = (int)$userID;
		$arResult = array("U{$userID}");
		if(isset($arAttr['OPENED']) && $arAttr['OPENED'] == 'Y')
		{
			$arResult[] = 'O';
		}

		$statusID = isset($arAttr['STATUS_ID']) ? $arAttr['STATUS_ID'] : '';
		if($statusID !== '')
		{
			$arResult[] = "STATUS_ID{$statusID}";
		}

		if(isset($arAttr['CONCERNED_USER_IDS']) && is_array($arAttr['CONCERNED_USER_IDS']))
		{
			foreach($arAttr['CONCERNED_USER_IDS'] as $concernedUserID)
			{
				$arResult[] = "CU{$concernedUserID}";
			}
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

		$dbResult = self::GetListEx(
			array(),
			array('@ID' => $IDs, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'ASSIGNED_BY_ID', 'OPENED', 'STATUS_ID')
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

			$attrs = [];
			if(isset($fields['OPENED']))
			{
				$attrs['OPENED'] = $fields['OPENED'];
			}

			if(isset($fields['STATUS_ID']))
			{
				$attrs['STATUS_ID'] = $fields['STATUS_ID'];
			}

			$entityAttrs = self::BuildEntityAttr($assignedByID, $attrs);
			$securityRegisterOptions = (new \Bitrix\Crm\Security\Controller\RegisterOptions())
				->setEntityAttributes($entityAttrs)
				->setEntityFields($fields)
			;
			Crm\Security\Manager::getEntityController(CCrmOwnerType::Lead)
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

	public function Update($ID, array &$arFields, $bCompare = true, $bUpdateSearch = true, $options = array())
	{
		global $DB;

		$this->LAST_ERROR = '';
		$this->checkExceptions = array();

		$ID = (int) $ID;
		if(!is_array($options))
		{
			$options = array();
		}

		$options['IS_COMPARE_ENABLED'] = $bCompare;

		if ($this->isUseOperation())
		{
			return $this->getCompatibilityAdapter()->performUpdate($ID, $arFields, $options);
		}

		$isSystemAction = isset($options['IS_SYSTEM_ACTION']) && $options['IS_SYSTEM_ACTION'];

		if(isset($options['CURRENT_USER']))
		{
			$iUserId = intval($options['CURRENT_USER']);
		}
		else
		{
			$iUserId = CCrmSecurityHelper::GetCurrentUserID();
		}

		$arRow = $this->getCurrentFields($ID);
		if ($arRow === false)
		{
			return false;
		}

		unset(
			$arFields['DATE_CREATE'],
			$arFields['DATE_MODIFY'],
			$arFields['DATE_CLOSED'],
			$arFields['IS_RETURN_CUSTOMER'],
			$arFields['MOVED_BY_ID'],
			$arFields['MOVED_TIME']
		);

		if(!$isSystemAction)
		{
			$arFields['~DATE_MODIFY'] = $DB->CurrentTimeFunction();
			if(!isset($arFields['MODIFY_BY_ID']) || $arFields['MODIFY_BY_ID'] <= 0)
			{
				$arFields['MODIFY_BY_ID'] = $iUserId;
			}
		}

		if (!empty($arFields['STATUS_ID']) && $arFields['STATUS_ID'] !== $arRow['STATUS_ID'])
		{
			self::EnsureStatusesLoaded();
			if (in_array($arFields['STATUS_ID'], self::$LEAD_STATUSES_BY_GROUP['FINISHED']))
				$arFields['~DATE_CLOSED'] = $DB->CurrentTimeFunction();
		}

		if(isset($arFields['ASSIGNED_BY_ID']) && $arFields['ASSIGNED_BY_ID'] <= 0)
		{
			unset($arFields['ASSIGNED_BY_ID']);
		}

		$companyID = isset($arFields['COMPANY_ID'])
			? (int)$arFields['COMPANY_ID'] : (isset($arRow['COMPANY_ID']) ? (int)$arRow['COMPANY_ID'] : 0);
		$assignedByID = (int)(isset($arFields['ASSIGNED_BY_ID']) ? $arFields['ASSIGNED_BY_ID'] : $arRow['ASSIGNED_BY_ID']);
		$statusID = isset($arFields['STATUS_ID']) ? $arFields['STATUS_ID'] : $arRow['STATUS_ID'];
		$customerType = isset($arRow['IS_RETURN_CUSTOMER']) && $arRow['IS_RETURN_CUSTOMER'] === 'Y'
			? CustomerType::RETURNING : CustomerType::GENERAL;

		$bResult = false;
		$options['CURRENT_FIELDS'] = $arRow;

		if(!$this->CheckFields($arFields, $ID, $options))
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

			$enableSystemEvents = !isset($options['ENABLE_SYSTEM_EVENTS']) || $options['ENABLE_SYSTEM_EVENTS'] === true;
			//region Before update event
			if($enableSystemEvents)
			{
				$beforeEvents = GetModuleEvents('crm', 'OnBeforeCrmLeadUpdate');
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
							$this->LAST_ERROR = GetMessage('CRM_LEAD_UPDATE_CANCELED', array('#NAME#' => $arEvent['TO_NAME']));
							$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
						}
						return false;
					}
				}
			}
			//endregion

			$arAttr = array();
			$arAttr['STATUS_ID'] = !empty($arFields['STATUS_ID']) ? $arFields['STATUS_ID'] : $arRow['STATUS_ID'];

			$originalObserverIDs = Crm\Observer\ObserverManager::getEntityObserverIDs(CCrmOwnerType::Lead, $ID);
			$observerIDs = isset($arFields['OBSERVER_IDS']) && is_array($arFields['OBSERVER_IDS'])
				? $arFields['OBSERVER_IDS'] : null;
			if($observerIDs !== null && count($observerIDs) > 0)
			{
				$arAttr['CONCERNED_USER_IDS'] = $observerIDs;
			}
			elseif($observerIDs === null && count($originalObserverIDs) > 0)
			{
				$arAttr['CONCERNED_USER_IDS'] = $originalObserverIDs;
			}

			//region Semantic ID depends on Status ID and can't be assigned directly
			$syncStatusSemantics = isset($options['SYNCHRONIZE_STATUS_SEMANTICS']) && $options['SYNCHRONIZE_STATUS_SEMANTICS'];
			if(isset($arFields['STATUS_ID']) && ($syncStatusSemantics || $arFields['STATUS_ID'] !== $arRow['STATUS_ID']))
			{
				$arFields['STATUS_SEMANTIC_ID'] = self::IsStatusExists($arFields['STATUS_ID'])
					? self::GetSemanticID($arFields['STATUS_ID'])
					: Bitrix\Crm\PhaseSemantics::UNDEFINED;

				if ($arFields['STATUS_ID'] !== $arRow['STATUS_ID'])
				{
					$arFields['MOVED_BY_ID'] = $iUserId;
					$arFields['MOVED_TIME'] = (new \Bitrix\Main\Type\DateTime())->toString();
				}
			}
			else
			{
				unset($arFields['STATUS_SEMANTIC_ID']);
			}
			//endregion

			$arAttr['OPENED'] = !empty($arFields['OPENED']) ? $arFields['OPENED'] : $arRow['OPENED'];
			$arEntityAttr = self::BuildEntityAttr($assignedByID, $arAttr);
			if($this->bCheckPermission)
			{
				$sEntityPerm = $this->cPerms->GetPermType(self::$TYPE_NAME, 'WRITE', $arEntityAttr);
				//HACK: Ensure that entity accessible for user restricted by BX_CRM_PERM_OPEN
				$this->PrepareEntityAttrs($arEntityAttr, $sEntityPerm);
				//HACK: Prevent 'OPENED' field change by user restricted by BX_CRM_PERM_OPEN permission
				if($sEntityPerm === BX_CRM_PERM_OPEN && isset($arFields['OPENED']) && $arFields['OPENED'] !== 'Y' && $assignedByID !== $iUserId)
				{
					$arFields['OPENED'] = 'Y';
				}
			}

			if (isset($arFields['ASSIGNED_BY_ID']) && $arRow['ASSIGNED_BY_ID'] != $arFields['ASSIGNED_BY_ID'])
			{
				CCrmEvent::SetAssignedByElement($arFields['ASSIGNED_BY_ID'], 'LEAD', $ID);
			}

			//region Preparation of contacts
			$originalContactBindings = LeadContactTable::getLeadBindings($ID);
			$originalContactIDs = EntityBinding::prepareEntityIDs(CCrmOwnerType::Contact, $originalContactBindings);
			$contactBindings = isset($arFields['CONTACT_BINDINGS']) && is_array($arFields['CONTACT_BINDINGS'])
				? $arFields['CONTACT_BINDINGS'] : null;
			$contactIDs = isset($arFields['CONTACT_IDS']) && is_array($arFields['CONTACT_IDS'])
				? $arFields['CONTACT_IDS'] : null;
			unset($arFields['CONTACT_IDS']);
			//region Backward compatibility
			$contactID = isset($arFields['CONTACT_ID']) ? max((int)$arFields['CONTACT_ID'], 0) : null;
			if($contactBindings === null &&
				$contactIDs === null &&
				$contactID !== null &&
				!in_array($contactID, $originalContactIDs, true))
			{
				//Compatibility mode. Trying to simulate single binding mode if contact is not found in bindings.
				$contactIDs = array();
				if($contactID > 0)
				{
					$contactIDs[] = $contactID;
				}
			}
			unset($arFields['CONTACT_ID']);
			//endregion

			$addedContactIDs = null;
			$removedContactIDs = null;

			$addedContactBindings = null;
			$removedContactBindings = null;

			if(is_array($contactIDs) && !is_array($contactBindings))
			{
				$contactBindings = EntityBinding::prepareEntityBindings(
					\CCrmOwnerType::Contact,
					$contactIDs
				);

				EntityBinding::markFirstAsPrimary($contactBindings);
			}
			elseif(is_array($contactBindings))
			{
				if(EntityBinding::findPrimaryBinding($contactBindings) === null)
				{
					EntityBinding::markFirstAsPrimary($contactBindings);
				}

				$contactIDs = EntityBinding::prepareEntityIDs(
					CCrmOwnerType::Contact,
					$contactBindings
				);
			}

			if(is_array($contactBindings))
			{
				$removedContactBindings = array();
				$addedContactBindings = array();

				EntityBinding::prepareBindingChanges(
					CCrmOwnerType::Contact,
					$originalContactBindings,
					$contactBindings,
					$addedContactBindings,
					$removedContactBindings
				);

				$addedContactIDs = EntityBinding::prepareEntityIDs(
					CCrmOwnerType::Contact,
					$addedContactBindings
				);

				$removedContactIDs = EntityBinding::prepareEntityIDs(
					CCrmOwnerType::Contact,
					$removedContactBindings
				);
			}
			//endregion

			//region Synchronize CustomerType
			if(self::GetSemanticID($statusID) !== Bitrix\Crm\PhaseSemantics::SUCCESS &&
				!Bitrix\Crm\History\LeadStatusHistoryEntry::checkStatus($ID, 'CONVERTED')
			)
			{
				$effectiveCustomerType = CustomerType::GENERAL;
				if($companyID > 0)
				{
					$effectiveCustomerType = CustomerType::RETURNING;
				}
				elseif((is_array($contactIDs) && !empty($contactIDs))
					|| !is_array($contactIDs) && !empty($originalContactIDs)
				)
				{
					$effectiveCustomerType = CustomerType::RETURNING;
				}

				if($customerType !== $effectiveCustomerType)
				{
					$arFields['IS_RETURN_CUSTOMER'] = $effectiveCustomerType === CustomerType::RETURNING ? 'Y' : 'N';
				}
			}
			//endregion

			//region Observers
			$addedObserverIDs = null;
			$removedObserverIDs = null;
			if(is_array($observerIDs))
			{
				$addedObserverIDs = array_diff($observerIDs, $originalObserverIDs);
				$removedObserverIDs = array_diff($originalObserverIDs, $observerIDs);
			}
			//endregion

			self::getLastActivityAdapter()->performUpdate((int)$ID, $arFields, $options);

			//
			$sonetEventData = array();
			if ($bCompare)
			{
				$res = CCrmFieldMulti::GetList(
					array('ID' => 'asc'),
					array('ENTITY_ID' => 'LEAD', 'ELEMENT_ID' => $ID)
				);
				$arRow['FM'] = array();
				while($ar = $res->Fetch())
					$arRow['FM'][$ar['TYPE_ID']][$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);

				$compareOptions = array();
				if(!empty($addedContactIDs) || !empty($removedContactIDs))
				{
					$compareOptions['CONTACTS'] = array('ADDED' => $addedContactIDs, 'REMOVED' => $removedContactIDs);
				}

				$arEvents = self::CompareFields($arRow, $arFields, $this->bCheckPermission, array_merge($compareOptions, $options));
				foreach($arEvents as $arEvent)
				{
					$arEvent['ENTITY_TYPE'] = 'LEAD';
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
						else if(isset($options['CURRENT_USER']))
						{
							$arEvent['USER_ID'] = (int)$options['CURRENT_USER'];
						}
					}

					if ($arEvent['ENTITY_FIELD'] !== 'CONTACT_ID' && $arEvent['ENTITY_FIELD'] !== 'COMPANY_ID')
					{
						$CCrmEvent = new CCrmEvent();
						$eventID = $CCrmEvent->Add($arEvent, $this->bCheckPermission);
					}

					$fieldID = isset($arEvent['ENTITY_FIELD']) ? $arEvent['ENTITY_FIELD'] : '';
					if($fieldID === '')
					{
						continue;
					}

					switch($fieldID)
					{
						case 'STATUS_ID':
							{
								$sonetEventData[] = array(
									'TYPE' => CCrmLiveFeedEvent::Progress,
									'FIELDS' => array(
										//'EVENT_ID' => $eventID,
										'TITLE' => GetMessage('CRM_LEAD_EVENT_UPDATE_STATUS'),
										'MESSAGE' => '',
										'PARAMS' => array(
											'START_STATUS_ID' => $arRow['STATUS_ID'],
											'FINAL_STATUS_ID' => $arFields['STATUS_ID']
										)
									)
								);
							}
							break;
						case 'ASSIGNED_BY_ID':
							{
								$sonetEventData[] = array(
									'TYPE' => CCrmLiveFeedEvent::Responsible,
									'FIELDS' => array(
										//'EVENT_ID' => $eventID,
										'TITLE' => GetMessage('CRM_LEAD_EVENT_UPDATE_ASSIGNED_BY'),
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
										'TITLE' => GetMessage('CRM_LEAD_EVENT_UPDATE_TITLE'),
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

			$arFields = array_merge($arFields, \CCrmAccountingHelper::calculateAccountingData($arFields, $arRow));

			if (isset($arFields['NAME']) && isset($arFields['LAST_NAME']))
			{
				$arFields['FULL_NAME'] = trim($arFields['NAME'] . ' ' . $arFields['LAST_NAME']);
			}
			else
			{
				$dbRes = $DB->Query("SELECT NAME, LAST_NAME FROM b_crm_lead WHERE ID = {$ID}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
				$arRes = $dbRes->Fetch();
				$arFields['FULL_NAME'] = trim((isset($arFields['NAME'])? $arFields['NAME']: $arRes['NAME']).' '.(isset($arFields['LAST_NAME'])? $arFields['LAST_NAME']: $arRes['LAST_NAME']));
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

			$this->normalizeEntityFields($arFields);
			$sUpdate = $DB->PrepareUpdate(self::TABLE_NAME, $arFields);

			if ($sUpdate <> '')
			{
				$DB->Query("UPDATE b_crm_lead SET {$sUpdate} WHERE ID = {$ID}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);

				$bResult = true;
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
					$GLOBALS["CACHE_MANAGER"]->ClearByTag("crm_entity_name_".CCrmOwnerType::Lead."_".$ID);
				}
			}

			//region User Field
			CCrmEntityHelper::NormalizeUserFields($arFields, self::$sUFEntityID, $GLOBALS['USER_FIELD_MANAGER'], array('IS_NEW' => false));
			$GLOBALS['USER_FIELD_MANAGER']->Update(self::$sUFEntityID, $ID, $arFields);
			//endregion

			//region Ensure entity has not been deleted yet by concurrent process
			$currentDbResult = \CCrmLead::GetListEx(
				array(),
				array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('*', 'UF_*')
			);

			$currentFields = $currentDbResult->Fetch();
			if(!is_array($currentFields))
			{
				return false;
			}
			//endregion

			//region Save contacts
			if(!empty($removedContactBindings))
			{
				LeadContactTable::unbindContacts($ID, $removedContactBindings);
			}
			if(!empty($addedContactBindings))
			{
				LeadContactTable::bindContacts($ID, $addedContactBindings);
			}
			//endregion

			//region Save Observers
			if(!empty($addedObserverIDs))
			{
				Crm\Observer\ObserverManager::registerBulk(
					$addedObserverIDs,
					\CCrmOwnerType::Lead,
					$ID,
					count($originalObserverIDs)
				);
			}

			if(!empty($removedObserverIDs))
			{
				Crm\Observer\ObserverManager::unregisterBulk(
					$removedObserverIDs,
					\CCrmOwnerType::Lead,
					$ID
				);
			}
			//endregion

			//region Save access rights for owner and observers
			$securityRegisterOptions = (new \Bitrix\Crm\Security\Controller\RegisterOptions())
				->setEntityAttributes($arEntityAttr)
			;
			Crm\Security\Manager::getEntityController(CCrmOwnerType::Lead)
				->register(self::$TYPE_NAME, $ID, $securityRegisterOptions)
			;
			//endregion

			//region Address
			if(isset($arFields['ADDRESS_DELETE']) && ($arFields['ADDRESS_DELETE'] === 'Y'))
			{
				\Bitrix\Crm\EntityAddress::unregister(
					CCrmOwnerType::Lead,
					$ID,
					EntityAddressType::Primary);
			}
			elseif(isset($arFields['ADDRESS'])
				|| isset($arFields['ADDRESS_2'])
				|| isset($arFields['ADDRESS_CITY'])
				|| isset($arFields['ADDRESS_POSTAL_CODE'])
				|| isset($arFields['ADDRESS_REGION'])
				|| isset($arFields['ADDRESS_PROVINCE'])
				|| isset($arFields['ADDRESS_COUNTRY'])
				|| isset($arFields['ADDRESS_LOC_ADDR_ID'])
				|| isset($arFields['ADDRESS_LOC_ADDR']))
			{
				\Bitrix\Crm\EntityAddress::register(
					CCrmOwnerType::Lead,
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
				);
			}
			//endregion

			//region Enrich associated company and primary contact of returning customer
			if(isset($arFields['IS_RETURN_CUSTOMER']) && $arFields['IS_RETURN_CUSTOMER'] === 'Y')
			{
				if($companyID > 0)
				{
					$merger = new \Bitrix\Crm\Merger\CompanyMerger(0,false);
					try
					{
						$merger->enrich(
							new \Bitrix\Crm\Merger\LeadMerger(0,false),
							$ID,
							$companyID
						);
					}
					catch (\Bitrix\Crm\Merger\EntityMergerException $e)
					{
					}
				}

				if(!empty($contactIDs) && $contactIDs[0] > 0)
				{
					$merger = new \Bitrix\Crm\Merger\ContactMerger(0,false);
					try
					{
						$merger->enrich(
							new \Bitrix\Crm\Merger\LeadMerger(0,false),
							$ID,
							$contactIDs[0]
						);
					}
					catch (\Bitrix\Crm\Merger\EntityMergerException $e)
					{
					}
				}
			}
			//endregion

			//region Complete activities if entity is closed
			if($arRow['STATUS_SEMANTIC_ID'] !== $currentFields['STATUS_SEMANTIC_ID']
				&& $currentFields['STATUS_SEMANTIC_ID'] !== Bitrix\Crm\PhaseSemantics::PROCESS
				&& (!isset($options['ENABLE_ACTIVITY_COMPLETION']) || $options['ENABLE_ACTIVITY_COMPLETION'] === true)
			)
			{
				$providerIDs = array();
				$completionConfig = \Bitrix\Crm\Settings\LeadSettings::getCurrent()->getActivityCompletionConfig();
				foreach(\Bitrix\Crm\Activity\Provider\ProviderManager::getCompletableProviderList() as $providerInfo)
				{
					$providerID = $providerInfo['ID'];
					if(!isset($completionConfig[$providerID]) || $completionConfig[$providerID])
					{
						$providerIDs[] = $providerID;
					}
				}

				$providerQty = count($providerIDs);
				if($providerQty > 0)
				{
					$activityUserID = $iUserId;
					if($activityUserID <= 0 && isset($arFields['MODIFY_BY_ID']))
					{
						$activityUserID = $arFields['MODIFY_BY_ID'];
					}
					\CCrmActivity::SetAutoCompletedByOwner(
						CCrmOwnerType::Lead,
						$ID,
						$providerQty < count($completionConfig) ? $providerIDs : array(),
						array('CURRENT_USER' => $activityUserID)
					);
				}
			}
			//endregion

			//region Statistics & History
			Bitrix\Crm\Statistics\LeadSumStatisticEntry::register($ID, $currentFields);
			Bitrix\Crm\History\LeadStatusHistoryEntry::synchronize($ID, $currentFields);
			LeadChannelBinding::synchronize($ID, $currentFields);
			if(isset($arFields['STATUS_ID']))
			{
				$currentSemanticID = self::GetSemanticID($arFields['STATUS_ID']);
				$previousSemanticID = self::GetSemanticID($arRow['STATUS_ID']);
				if($currentSemanticID !== Bitrix\Crm\PhaseSemantics::SUCCESS &&
					$previousSemanticID === Bitrix\Crm\PhaseSemantics::SUCCESS
				)
				{
					$converter = new Bitrix\Crm\Conversion\LeadConverter(
						new Bitrix\Crm\Conversion\LeadConversionConfig()
					);
					$converter->setEntityID($ID);
					$converter->unbindChildEntities();
				}

				Bitrix\Crm\History\LeadStatusHistoryEntry::register($ID, $currentFields, array('IS_NEW' => false));

				if(($arFields['STATUS_ID'] === 'CONVERTED' && $arRow['STATUS_ID'] !== 'CONVERTED')
					|| ($arFields['STATUS_ID'] !== 'CONVERTED' && $arRow['STATUS_ID'] === 'CONVERTED'))
				{
					Bitrix\Crm\Statistics\LeadConversionStatisticsEntry::register($ID, $currentFields, array('IS_NEW' => false));
				}
			}
			//endregion

			if (isset($arFields['FM']) && is_array($arFields['FM']))
			{
				$CCrmFieldMulti = new CCrmFieldMulti();
				$CCrmFieldMulti->SetFields(CCrmOwnerType::LeadName, $ID, $arFields['FM']);

				$multifields = DuplicateCommunicationCriterion::prepareEntityMultifieldValues(
					CCrmOwnerType::Lead,
					$ID
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
					$DB->Query("UPDATE b_crm_lead SET HAS_EMAIL = '{$hasEmail}', HAS_PHONE = '{$hasPhone}', HAS_IMOL = '{$hasImol}' WHERE ID = {$ID}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);

					$arFields['HAS_EMAIL'] = $hasEmail;
					$arFields['HAS_PHONE'] = $hasPhone;
					$arFields['HAS_IMOL'] = $hasImol;
				}
			}

			$duplicateCriterionRegistrar = DuplicateManager::getCriterionRegistrar(\CCrmOwnerType::Lead);
			$data =
				(new Crm\Integrity\CriterionRegistrar\Data())
					->setEntityTypeId(\CCrmOwnerType::Lead)
					->setEntityId($ID)
					->setCurrentFields($arFields)
					->setPreviousFields($arRow)
			;
			$duplicateCriterionRegistrar->update($data);

			$enableDupIndexInvalidation = (bool)($arOptions['ENABLE_DUP_INDEX_INVALIDATION'] ?? true);
			if(!$isSystemAction && $enableDupIndexInvalidation)
			{
				DuplicateManager::markDuplicateIndexAsDirty(CCrmOwnerType::Lead, $ID);
			}

			if($bResult && (isset($arFields['ASSIGNED_BY_ID']) || isset($arFields['STATUS_ID'])))
			{
				$previousAssignedByID = isset($arRow['ASSIGNED_BY_ID']) ? (int)$arRow['ASSIGNED_BY_ID'] : 0;
				if ($assignedByID !== $previousAssignedByID && $enableDupIndexInvalidation)
				{
					DuplicateManager::onChangeEntityAssignedBy(CCrmOwnerType::Lead, $ID);
				}
			}
			if ($bResult)
			{
				\Bitrix\Crm\Counter\Monitor::getInstance()->onEntityUpdate(CCrmOwnerType::Lead, $arRow, $currentFields);
			}

			self::getContentTypeIdAdapter()
				->setPreviousFields((int)$ID, $arRow)
				->performUpdate((int)$ID, $arFields, $options)
			;

			// update utm fields
			UtmTable::updateEntityUtmFromFields(CCrmOwnerType::Lead, $ID, $arFields);
			//region Search
			if($bUpdateSearch)
			{
				CCrmSearch::UpdateSearch(array('ID' => $ID, 'CHECK_PERMISSIONS' => 'N'), 'LEAD', true);
			}
			//endregion
			//region Search content index
			Bitrix\Crm\Search\SearchContentBuilderFactory::create(CCrmOwnerType::Lead)
				->build($ID, ['checkExist' => true]);
			//endregion

			//region save parent relations
			Crm\Service\Container::getInstance()->getParentFieldManager()->saveParentRelationsForIdentifier(
				new Crm\ItemIdentifier(\CCrmOwnerType::Lead, $ID),
				$arFields
			);
			//endregion

			Bitrix\Crm\Timeline\LeadController::getInstance()->onModify(
				$ID,
				array('CURRENT_FIELDS' => $arFields, 'PREVIOUS_FIELDS' => $arRow)
			);

			CCrmEntityHelper::registerAdditionalTimelineEvents([
				'entityTypeId' => \CCrmOwnerType::Lead,
				'entityId' => $ID,
				'fieldsInfo' => static::GetFieldsInfo(),
				'previousFields' => $arRow,
				'currentFields' => $arFields,
				'previousStageSemantics' => $arRow['STATUS_SEMANTIC_ID'] ?? Crm\PhaseSemantics::UNDEFINED,
				'currentStageSemantics' => $arFields['STATUS_SEMANTIC_ID'] ?? Crm\PhaseSemantics::UNDEFINED,
				'options' => $options,
				'bindings' => [
					'entityTypeId' => \CCrmOwnerType::Contact,
					'previous' => $originalContactBindings,
					'current' => $contactBindings,
				],
				'isMarkEventRegistrationEnabled' => false,
			]);

			Bitrix\Crm\Integration\Im\Chat::onEntityModification(
				CCrmOwnerType::Lead,
				$ID,
				array(
					'CURRENT_FIELDS' => $arFields,
					'PREVIOUS_FIELDS' => $arRow,
					'ADDED_OBSERVER_IDS' => $addedObserverIDs,
					'REMOVED_OBSERVER_IDS' => $removedObserverIDs
				)
			);

			$arFields['ID'] = $ID;
			//region Social network
			$registerSonetEvent = isset($options['REGISTER_SONET_EVENT']) && $options['REGISTER_SONET_EVENT'] === true;

			if (
				$bResult
				&& isset($arFields['ASSIGNED_BY_ID'])
				&& Crm\Settings\Crm::isLiveFeedRecordsGenerationEnabled()
			)
			{
				CCrmSonetSubscription::ReplaceSubscriptionByEntity(
					CCrmOwnerType::Lead,
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
					$sonetEventFields['ENTITY_TYPE_ID'] = CCrmOwnerType::Lead;
					$sonetEventFields['ENTITY_ID'] = $ID;
					$sonetEventFields['USER_ID'] = $modifiedByID;

					$logEventID = CCrmLiveFeed::CreateLogEvent($sonetEventFields, $sonetEvent['TYPE'], ['CURRENT_USER' => $iUserId]);

					if (
						$logEventID !== false
						&& CModule::IncludeModule("im")
						&& \Bitrix\Crm\Settings\LeadSettings::isEnabled()
					)
					{
						$title = CCrmOwnerType::GetCaption(CCrmOwnerType::Lead, $ID, false);
						$url = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Lead, $ID);
						$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && SITE_SERVER_NAME <> '') ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));

						if (
							$sonetEventFields['PARAMS']['FINAL_RESPONSIBLE_ID'] != $modifiedByID
							&& $sonetEvent['TYPE'] == CCrmLiveFeedEvent::Responsible
						)
						{
							$arMessageFields = array(
								"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
								"TO_USER_ID" => $sonetEventFields['PARAMS']['FINAL_RESPONSIBLE_ID'],
								"FROM_USER_ID" => $modifiedByID,
								"NOTIFY_TYPE" => IM_NOTIFY_FROM,
								"NOTIFY_MODULE" => "crm",
								"LOG_ID" => $logEventID,
								//"NOTIFY_EVENT" => "lead_update",
								"NOTIFY_EVENT" => "changeAssignedBy",
								"NOTIFY_TAG" => "CRM|LEAD_RESPONSIBLE|".$ID,
								"NOTIFY_MESSAGE" => GetMessage("CRM_LEAD_RESPONSIBLE_IM_NOTIFY", Array("#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($title)."</a>")),
								"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_LEAD_RESPONSIBLE_IM_NOTIFY", Array("#title#" => htmlspecialcharsbx($title)))." (".$serverName.$url.")"
							);

							CIMNotify::Add($arMessageFields);
						}

						if (
							$sonetEvent['TYPE'] == CCrmLiveFeedEvent::Responsible
							&& $sonetEventFields['PARAMS']['START_RESPONSIBLE_ID'] != $modifiedByID
						)
						{
							$arMessageFields = array(
								"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
								"TO_USER_ID" => $sonetEventFields['PARAMS']['START_RESPONSIBLE_ID'],
								"FROM_USER_ID" => $modifiedByID,
								"NOTIFY_TYPE" => IM_NOTIFY_FROM,
								"NOTIFY_MODULE" => "crm",
								"LOG_ID" => $logEventID,
								//"NOTIFY_EVENT" => "lead_update",
								"NOTIFY_EVENT" => "changeAssignedBy",
								"NOTIFY_TAG" => "CRM|LEAD_RESPONSIBLE|".$ID,
								"NOTIFY_MESSAGE" => GetMessage("CRM_LEAD_NOT_RESPONSIBLE_IM_NOTIFY", Array("#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($title)."</a>")),
								"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_LEAD_NOT_RESPONSIBLE_IM_NOTIFY", Array("#title#" => htmlspecialcharsbx($title)))." (".$serverName.$url.")"
							);

							CIMNotify::Add($arMessageFields);
						}

						if (
							$sonetEvent['TYPE'] == CCrmLiveFeedEvent::Progress
							&& $sonetEventFields['PARAMS']['START_STATUS_ID']
							&& $sonetEventFields['PARAMS']['FINAL_STATUS_ID']

						)
						{
							$assignedByID = (isset($arFields['ASSIGNED_BY_ID']) ? $arFields['ASSIGNED_BY_ID'] : $arRow['ASSIGNED_BY_ID']);
							$infos = CCrmStatus::GetStatus('STATUS');

							if (
								$assignedByID != $modifiedByID
								&& array_key_exists($sonetEventFields['PARAMS']['START_STATUS_ID'], $infos)
								&& array_key_exists($sonetEventFields['PARAMS']['FINAL_STATUS_ID'], $infos)
							)
							{
								$arMessageFields = array(
									"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
									"TO_USER_ID" => $assignedByID,
									"FROM_USER_ID" => $modifiedByID,
									"NOTIFY_TYPE" => IM_NOTIFY_FROM,
									"NOTIFY_MODULE" => "crm",
									"LOG_ID" => $logEventID,
									//"NOTIFY_EVENT" => "lead_update",
									"NOTIFY_EVENT" => "changeStage",
									"NOTIFY_TAG" => "CRM|LEAD_PROGRESS|".$ID,
									"NOTIFY_MESSAGE" => GetMessage("CRM_LEAD_PROGRESS_IM_NOTIFY", Array(
										"#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($title)."</a>",
										"#start_status_title#" => htmlspecialcharsbx($infos[$sonetEventFields['PARAMS']['START_STATUS_ID']]['NAME']),
										"#final_status_title#" => htmlspecialcharsbx($infos[$sonetEventFields['PARAMS']['FINAL_STATUS_ID']]['NAME'])
									)),
									"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_LEAD_PROGRESS_IM_NOTIFY", Array(
											"#title#" => htmlspecialcharsbx($title),
											"#start_status_title#" => htmlspecialcharsbx($infos[$sonetEventFields['PARAMS']['START_STATUS_ID']]['NAME']),
											"#final_status_title#" => htmlspecialcharsbx($infos[$sonetEventFields['PARAMS']['FINAL_STATUS_ID']]['NAME'])
										))." (".$serverName.$url.")"
								);

								CIMNotify::Add($arMessageFields);
							}
						}
					}

					unset($sonetEventFields);
				}
				unset($sonetEvent);
			}
			//endregion
			//region After update event
			if($bResult && $enableSystemEvents)
			{
				$afterEvents = GetModuleEvents('crm', 'OnAfterCrmLeadUpdate');
				while ($arEvent = $afterEvents->Fetch())
					ExecuteModuleEventEx($arEvent, array(&$arFields));
			}
			//endregion

			$statusSemanticsId = $arFields['STATUS_SEMANTIC_ID'] ?: $arRow['STATUS_SEMANTIC_ID'];
			if(Crm\Ml\Scoring::isMlAvailable() && !Crm\PhaseSemantics::isFinal($statusSemanticsId))
			{
				Crm\Ml\Scoring::queuePredictionUpdate(CCrmOwnerType::Lead, $ID, [
					'EVENT_TYPE' => Crm\Ml\Scoring::EVENT_ENTITY_UPDATE
				]);
			}

			if ($bResult && !$syncStatusSemantics)
			{
				$item = Crm\Kanban\Entity::getInstance(self::$TYPE_NAME)
					->createPullItem(array_merge($arRow, $arFields));

				PullManager::getInstance()->sendItemUpdatedEvent(
					$item,
					[
						'TYPE' => self::$TYPE_NAME,
						'SKIP_CURRENT_USER' => ($iUserId !== 0),
					]
				);
			}
		}

		return $bResult;
	}

	/**
	 * @param int $id
	 * @return array|bool
	 */
	private function getCurrentFields(int $id)
	{
		$filter = ['ID' => $id];
		if (!$this->bCheckPermission)
		{
			$filter['CHECK_PERMISSIONS'] = 'N';
		}

		$res = self::GetListEx([], $filter, false, false, ['*', 'UF_*']);
		if (!($fields = $res->fetch()))
		{
			return false;
		}

		$fields['FM'] = Crm\Entity\Lead::getInstance()->getEntityMultifields($id, ['skipEmpty' => true]);

		return $fields;
	}

	public function Delete($ID, $arOptions = array())
	{
		global $DB, $APPLICATION;
		$ID = intval($ID);

		$this->LAST_ERROR = '';
		$APPLICATION->ResetException();

		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		if ($this->isUseOperation())
		{
			return $this->getCompatibilityAdapter()->performDelete($ID, $arOptions);
		}

		if(isset($arOptions['CURRENT_USER']))
		{
			$iUserId = intval($arOptions['CURRENT_USER']);
		}
		else
		{
			$iUserId = CCrmSecurityHelper::GetCurrentUserID();
		}

		$dbRes = self::GetListEx(
			array(),
			array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('*')
		);
		$arFields = $dbRes ? $dbRes->Fetch() : null;
		if(!is_array($arFields))
		{
			$err = GetMessage('CRM_LEAD_DELETION_NOT_FOUND');
			$this->LAST_ERROR = $err;
			$APPLICATION->throwException($err);
			return false;
		}

		$assignedByID = isset($arFields['ASSIGNED_BY_ID']) ? (int)$arFields['ASSIGNED_BY_ID'] : 0;
		$isConverted = isset($arFields['STATUS_ID']) && $arFields['STATUS_ID'] === 'CONVERTED';
		$companyID =  isset($arFields['COMPANY_ID']) ? (int)$arFields['COMPANY_ID'] : 0;
		$contactID = isset($arFields['CONTACT_ID']) ? (int)$arFields['CONTACT_ID'] : 0;

		if($companyID > 0 && !CCrmCompany::Exists($companyID))
		{
			$companyID = 0;
		}

		if($contactID > 0 && !CCrmContact::Exists($contactID))
		{
			$contactID = 0;
		}

		$hasDeletePerm = \Bitrix\Crm\Service\Container::getInstance()
			->getUserPermissions($iUserId)
			->checkDeletePermissions(CCrmOwnerType::Lead, $ID);

		if (
			$this->bCheckPermission
			&& !$hasDeletePerm
		)
		{
			return false;
		}

		$events = GetModuleEvents('crm', 'OnBeforeCrmLeadDelete');
		while ($arEvent = $events->Fetch())
		{
			if(ExecuteModuleEventEx($arEvent, array($ID))===false)
			{
				$err = GetMessage("MAIN_BEFORE_DEL_ERR").' '.$arEvent['TO_NAME'];
				if($ex = $APPLICATION->GetException())
					$err .= ': '.$ex->GetString();
				$APPLICATION->throwException($err);
				$this->LAST_ERROR = $err;
				return false;
			}
		}

		$enableDeferredMode = isset($arOptions['ENABLE_DEFERRED_MODE'])
			? (bool)$arOptions['ENABLE_DEFERRED_MODE']
			: \Bitrix\Crm\Settings\LeadSettings::getCurrent()->isDeferredCleaningEnabled();

		//By default we need to clean up related bizproc entities
		$processBizproc = isset($arOptions['PROCESS_BIZPROC']) ? (bool)$arOptions['PROCESS_BIZPROC'] : true;
		if($processBizproc)
		{
			$bizproc = new CCrmBizProc('LEAD');
			$bizproc->ProcessDeletion($ID);
		}

		$enableRecycleBin = \Bitrix\Crm\Recycling\LeadController::isEnabled()
			&& \Bitrix\Crm\Settings\LeadSettings::getCurrent()->isRecycleBinEnabled();
		if($enableRecycleBin)
		{
			\Bitrix\Crm\Recycling\LeadController::getInstance()->moveToBin($ID, array('FIELDS' => $arFields));
		}

		$tableName = CCrmLead::TABLE_NAME;
		$sSql = "DELETE FROM {$tableName} WHERE ID = {$ID}";
		$obRes = $DB->Query($sSql, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		if (is_object($obRes) && $obRes->AffectedRowsCount() > 0)
		{
			if(defined('BX_COMP_MANAGED_CACHE'))
			{
				$GLOBALS['CACHE_MANAGER']->CleanDir('b_crm_lead');
				$GLOBALS['CACHE_MANAGER']->ClearByTag('b_crm_lead');
			}

			CCrmSearch::DeleteSearch('LEAD', $ID);

			Bitrix\Crm\Search\SearchContentBuilderFactory::create(
				CCrmOwnerType::Lead
			)->removeShortIndex($ID);

			Bitrix\Crm\Kanban\SortTable::clearEntity($ID, \CCrmOwnerType::LeadName);

			Crm\Security\Manager::getEntityController(CCrmOwnerType::Lead)
				->unregister(self::$TYPE_NAME, $ID)
			;

			$GLOBALS['USER_FIELD_MANAGER']->Delete(self::$sUFEntityID, $ID);

			LeadContactTable::unbindAllContacts($ID);

			if(!$enableDeferredMode)
			{
				$CCrmEvent = new CCrmEvent();
				$CCrmEvent->DeleteByElement('LEAD', $ID);
			}
			else
			{
				Bitrix\Crm\Cleaning\CleaningManager::register(CCrmOwnerType::Lead, $ID);
			}

			Bitrix\Crm\History\LeadStatusHistoryEntry::unregister($ID);
			Bitrix\Crm\Statistics\LeadSumStatisticEntry::unregister($ID);
			Bitrix\Crm\Statistics\LeadActivityStatisticEntry::unregister($ID);
			//Bitrix\Crm\Statistics\LeadProcessStatisticsEntry::unregister($ID);
			LeadChannelBinding::unregisterAll($ID);

			if($isConverted)
			{
				Bitrix\Crm\Statistics\LeadConversionStatisticsEntry::unregister($ID);
			}

			$enableDupIndexInvalidation = is_array($arOptions) && isset($arOptions['ENABLE_DUP_INDEX_INVALIDATION'])
				? (bool)$arOptions['ENABLE_DUP_INDEX_INVALIDATION']
				: true;

			if($enableDupIndexInvalidation)
			{
				DuplicateManager::markDuplicateIndexAsJunk(CCrmOwnerType::Lead, $ID);
			}

			$duplicateCriterionRegistrar = DuplicateManager::getCriterionRegistrar(\CCrmOwnerType::Lead);
			$data =
				(new Crm\Integrity\CriterionRegistrar\Data())
					->setEntityTypeId(\CCrmOwnerType::Lead)
					->setEntityId($ID)
			;
			$duplicateCriterionRegistrar->unregister($data);

			DuplicateIndexMismatch::unregisterEntity(CCrmOwnerType::Lead, $ID);

			\Bitrix\Crm\Counter\Monitor::getInstance()->onEntityDelete(CCrmOwnerType::Lead, $arFields);

			if($isConverted)
			{
				if($contactID > 0)
				{
					CCrmActivity::ChangeOwner(CCrmOwnerType::Lead, $ID, CCrmOwnerType::Contact, $contactID);
				}
				elseif($companyID> 0)
				{
					CCrmActivity::ChangeOwner(CCrmOwnerType::Lead, $ID, CCrmOwnerType::Company, $companyID);
				}
			}

			CCrmActivity::DeleteByOwner(CCrmOwnerType::Lead, $ID);

			if(!$enableRecycleBin)
			{
				$CCrmFieldMulti = new CCrmFieldMulti();
				$CCrmFieldMulti->DeleteByElement('LEAD', $ID);

				CCrmProductRow::DeleteByOwner('L', $ID);
				CCrmProductRow::DeleteSettings('L', $ID);

				Crm\EntityAddress::unregister(CCrmOwnerType::Lead, $ID, EntityAddressType::Primary);
				Crm\Timeline\TimelineEntry::deleteByOwner(CCrmOwnerType::Lead, $ID);
				Crm\Pseudoactivity\WaitEntry::deleteByOwner(CCrmOwnerType::Lead, $ID);
				Crm\Observer\ObserverManager::deleteByOwner(CCrmOwnerType::Lead, $ID);
				Crm\Ml\Scoring::onEntityDelete(CCrmOwnerType::Lead, $ID);

				self::getContentTypeIdAdapter()->performDelete((int)$ID, $arOptions);

				Crm\Integration\Im\Chat::deleteChat(
					array(
						'ENTITY_TYPE_ID' => \CCrmOwnerType::Lead,
						'ENTITY_ID' => $ID
					)
				);

				CCrmSonetSubscription::UnRegisterSubscriptionByEntity(CCrmOwnerType::Lead, $ID);
				CCrmLiveFeed::DeleteLogEvents(
					array(
						'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
						'ENTITY_ID' => $ID
					),
					array(
						'UNREGISTER_RELATION' => true,
						'UNREGISTER_SUBSCRIPTION' => true
					)
				);
				UtmTable::deleteEntityUtm(CCrmOwnerType::Lead, $ID);
				Tracking\Entity::deleteTrace(CCrmOwnerType::Lead, $ID);
			}

			CCrmContact::ProcessLeadDeletion($ID);
			CCrmCompany::ProcessLeadDeletion($ID);
			CCrmDeal::ProcessLeadDeletion($ID);

			\Bitrix\Crm\Timeline\LeadController::getInstance()->onDelete(
				$ID,
				array('FIELDS' => $arFields)
			);

			if(Bitrix\Crm\Settings\HistorySettings::getCurrent()->isLeadDeletionEventEnabled())
			{
				CCrmEvent::RegisterDeleteEvent(CCrmOwnerType::Lead, $ID, $iUserId, array('FIELDS' => $arFields));
			}

			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("crm_entity_name_".CCrmOwnerType::Lead."_".$ID);
			}

			$afterEvents = GetModuleEvents('crm', 'OnAfterCrmLeadDelete');
			while ($arEvent = $afterEvents->Fetch())
			{
				ExecuteModuleEventEx($arEvent, array($ID));
			}
		}

		$item = Crm\Kanban\Entity::getInstance(self::$TYPE_NAME)
			->createPullItem($arFields);

		PullManager::getInstance()->sendItemDeletedEvent(
			$item,
			[
				'TYPE' => self::$TYPE_NAME,
				'SKIP_CURRENT_USER' => false,
				'EVENT_ID' => ($arOptions['eventId'] ?? null),
			]
		);

		return true;
	}

	public function CheckFields(&$arFields, $ID = false, $options = array())
	{
		global $APPLICATION, $USER_FIELD_MANAGER;
		$this->LAST_ERROR = '';
		$this->checkExceptions = array();

		if (isset($arFields['BIRTHDATE']) && $arFields['BIRTHDATE'] !== '' && !CheckDateTime($arFields['BIRTHDATE']))
		{
			$this->LAST_ERROR .= GetMessage(
					'CRM_ERROR_FIELD_INCORRECT',
					['%FIELD_NAME%' => CAllCrmLead::GetFieldCaption('BIRTHDATE')]
				) . "<br />"
			;
		}

		if (($ID == false || isset($arFields['TITLE'])) && empty($arFields['TITLE']))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_IS_MISSING', array('%FIELD_NAME%' => GetMessage('CRM_LEAD_FIELD_TITLE')))."<br />";

		if(is_string($arFields['OPPORTUNITY']) && $arFields['OPPORTUNITY'] !== '')
		{
			$arFields['OPPORTUNITY'] = str_replace(array(',', ' '), array('.', ''), $arFields['OPPORTUNITY']);
			//HACK: MSSQL returns '.00' for zero value
			if(mb_strpos($arFields['OPPORTUNITY'], '.') === 0)
			{
				$arFields['OPPORTUNITY'] = '0'.$arFields['OPPORTUNITY'];
			}

			if (!preg_match('/^-?\d{1,}(\.\d{1,})?$/', $arFields['OPPORTUNITY']))
			{
				$this->LAST_ERROR .= GetMessage('CRM_LEAD_FIELD_OPPORTUNITY_INVALID')."<br />";
			}
		}

		if (isset($arFields['FM']) && is_array($arFields['FM']))
		{
			$CCrmFieldMulti = new CCrmFieldMulti();
			if (!$CCrmFieldMulti->CheckComplexFields($arFields['FM']))
			{
				$this->LAST_ERROR .= $CCrmFieldMulti->LAST_ERROR;
			}
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
						if(is_array($currentFields))
						{
							$currentFields['FM'] = Crm\Entity\Lead::getInstance()->getEntityMultifields($ID, array('skipEmpty' => true));
						}
					}

					if(!array_key_exists('OBSERVER_IDS', $currentFields))
					{
						$currentFields['OBSERVER_IDS'] = Crm\Observer\ObserverManager::getEntityObserverIDs(
							\CCrmOwnerType::Lead,
							$ID
						);
					}
				}

				//If Status ID is changed we must perform check of all fields.
				if(is_array($currentFields))
				{
					CCrmEntityHelper::NormalizeUserFields(
						$currentFields,
						self::$sUFEntityID,
						$USER_FIELD_MANAGER,
						array('IS_NEW' => ($ID == false))
					);

					if(isset($arFields['STATUS_ID']) && $arFields['STATUS_ID'] !== $currentFields['STATUS_ID'])
					{
						$fieldsToCheck = array_merge($currentFields, $arFields);
						if(self::GetSemanticID($arFields['STATUS_ID']) === Bitrix\Crm\PhaseSemantics::FAILURE)
						{
							//Disable required fields check for failure status due to backward compatibility.
							$enableRequiredUserFieldCheck = false;
						}
					}
					elseif(!isset($arFields['STATUS_ID']) && isset($currentFields['STATUS_ID']))
					{
						$fieldsToCheck = array_merge($arFields, array('STATUS_ID' => $currentFields['STATUS_ID']));
					}

					if(isset($currentFields['FM']) && isset($arFields['FM']))
					{
						$fieldsToCheck['FM'] = array_merge($currentFields['FM'], $arFields['FM']);
					}
				}

				$requiredFields = Crm\Attribute\FieldAttributeManager::getRequiredFields(
					CCrmOwnerType::Lead,
					$ID,
					$fieldsToCheck,
					Crm\Attribute\FieldOrigin::UNDEFINED,
					is_array($options['FIELD_CHECK_OPTIONS']) ? $options['FIELD_CHECK_OPTIONS'] : array()
				);

				$requiredSystemFields = isset($requiredFields[Crm\Attribute\FieldOrigin::SYSTEM])
					? $requiredFields[Crm\Attribute\FieldOrigin::SYSTEM] : array();

				if(!empty($requiredSystemFields))
				{
					$validator = new Crm\Entity\LeadValidator($ID, $fieldsToCheck);
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

			CCrmEntityHelper::NormalizeUserFields($fieldsToCheck, self::$sUFEntityID, $USER_FIELD_MANAGER, array('IS_NEW' => ($ID == false)));

			$requiredUserFields = $this->getRequiredUserFields($requiredFields);

			if (!$USER_FIELD_MANAGER->CheckFields(
					self::$sUFEntityID,
					$ID,
					$fieldsToCheck,
					false,
					$enableRequiredUserFieldCheck,
					$requiredUserFields
				)
			)
			{
				$e = $APPLICATION->GetException();
				$this->checkExceptions[] = $e;
				$this->LAST_ERROR .= $e->GetString();
			}
		}
		// Temporary crutch.
		// This check will be removed when operations will be completely supported for contacts:
		$allowSetSystemFields = $options['ALLOW_SET_SYSTEM_FIELDS'] ?? false;
		if ($allowSetSystemFields)
		{
			$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(CCrmOwnerType::Lead);
			$currentUserId =  isset($options['CURRENT_USER'])
				? (int)$options['CURRENT_USER']
				: CCrmSecurityHelper::GetCurrentUserID()
			;

			$checkSystemFieldsResult = (new \Bitrix\Crm\Service\Operation\Import(
				$factory->createItem(),
				new \Bitrix\Crm\Service\Operation\Settings(Container::getInstance()->getContext()),
				$factory->getFieldsCollection()
			))->checkSystemFieldsValues([
				\Bitrix\Crm\Item::FIELD_NAME_CREATED_TIME => isset($arFields['DATE_CREATE'])
					? Main\Type\DateTime::createFromUserTime($arFields['DATE_CREATE'])
					: null
				,
				\Bitrix\Crm\Item::FIELD_NAME_UPDATED_TIME => isset($arFields['DATE_MODIFY'])
					? Main\Type\DateTime::createFromUserTime($arFields['DATE_MODIFY'])
					: null
				,
				\Bitrix\Crm\Item::FIELD_NAME_CREATED_BY =>
					(isset($arFields['CREATED_BY_ID']) && $arFields['CREATED_BY_ID'] != $currentUserId)
						? (int)$arFields['CREATED_BY_ID']
						: null
				,
				\Bitrix\Crm\Item::FIELD_NAME_UPDATED_BY =>
					(isset($arFields['MODIFY_BY_ID']) && $arFields['MODIFY_BY_ID'] != $currentUserId)
						? (int)$arFields['MODIFY_BY_ID']
						: null
				,
			]);
			if (!$checkSystemFieldsResult->isSuccess())
			{
				$this->LAST_ERROR .= implode(', ', $checkSystemFieldsResult->getErrorMessages());
			}
		}

		return ($this->LAST_ERROR === '');
	}

	/**
	 * @param $requiredFields
	 * @return array
	 */
	private function getRequiredUserFields($requiredFields): array
	{
		$requiredUserFields = (
		is_array($requiredFields) && isset($requiredFields[Crm\Attribute\FieldOrigin::CUSTOM])
			? $requiredFields[Crm\Attribute\FieldOrigin::CUSTOM] : []
		);
		return $this->excludeRequiredButNotAvailableFields($requiredUserFields);
	}

	/**
	 * @param array $fields
	 * @return array
	 */
	private function excludeRequiredButNotAvailableFields(array $fields): array
	{
		$notAccessibleFields = VisibilityManager::getNotAccessibleFields(CCrmOwnerType::Lead);
		return array_diff($fields, $notAccessibleFields);
	}

	public function GetCheckExceptions()
	{
		return $this->checkExceptions;
	}

	public static function CompareFields(array $arFieldsOrig, array $arFieldsModif, $bCheckPerms = true, array $arOptions = null)
	{
		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		$arMsg = Array();

		if(isset($arFieldsOrig['TITLE']) && isset($arFieldsModif['TITLE'])
			&& $arFieldsOrig['TITLE'] != $arFieldsModif['TITLE'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'TITLE',
				'EVENT_NAME' => GetMessage(
					'CRM_LEAD_FIELD_COMPARE',
					array('#FIELD_NAME#' => self::GetFieldCaption('TITLE'))
				),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['TITLE'])? $arFieldsOrig['TITLE']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['TITLE'])? $arFieldsModif['TITLE']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		if(isset($arFieldsOrig['NAME']) && isset($arFieldsModif['NAME'])
			&& $arFieldsOrig['NAME'] != $arFieldsModif['NAME'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'NAME',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_NAME'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['NAME'])? $arFieldsOrig['NAME']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['NAME'])? $arFieldsModif['NAME']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		if(isset($arFieldsOrig['LAST_NAME']) && isset($arFieldsModif['LAST_NAME'])
			&& $arFieldsOrig['LAST_NAME'] != $arFieldsModif['LAST_NAME'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'LAST_NAME',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_LAST_NAME'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['LAST_NAME'])? $arFieldsOrig['LAST_NAME']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['LAST_NAME'])? $arFieldsModif['LAST_NAME']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		if(isset($arFieldsOrig['SECOND_NAME']) && isset($arFieldsModif['SECOND_NAME'])
			&& $arFieldsOrig['SECOND_NAME'] != $arFieldsModif['SECOND_NAME'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'SECOND_NAME',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_SECOND_NAME'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['SECOND_NAME'])? $arFieldsOrig['SECOND_NAME']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['SECOND_NAME'])? $arFieldsModif['SECOND_NAME']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		if (isset($arFieldsOrig['FM']) && isset($arFieldsModif['FM']))
			$arMsg = array_merge($arMsg, CCrmFieldMulti::CompareFields($arFieldsOrig['FM'], $arFieldsModif['FM']));

		if(isset($arFieldsOrig['POST']) && isset($arFieldsModif['POST'])
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
			\Bitrix\Crm\LeadAddress::prepareChangeEvents(
				$arFieldsOrig,
				$arFieldsModif,
				\Bitrix\Crm\ContactAddress::Primary,
				$addressOptions
			)
		);

		if(isset($arFieldsOrig['STATUS_ID']) && isset($arFieldsModif['STATUS_ID'])
			&& $arFieldsOrig['STATUS_ID'] != $arFieldsModif['STATUS_ID'])
		{
			$arStatus = CCrmStatus::GetStatusList('STATUS');
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'STATUS_ID',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_STATUS_ID'),
				'EVENT_TEXT_1' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $arFieldsOrig['STATUS_ID'])),
				'EVENT_TEXT_2' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $arFieldsModif['STATUS_ID']))
			);
		}
		if(isset($arFieldsOrig['COMMENTS']) && isset($arFieldsModif['COMMENTS'])
			&& $arFieldsOrig['COMMENTS'] != $arFieldsModif['COMMENTS'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'COMMENTS',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_COMMENTS'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['COMMENTS'])? $arFieldsOrig['COMMENTS']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['COMMENTS'])? $arFieldsModif['COMMENTS']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		if(isset($arFieldsOrig['STATUS_DESCRIPTION']) && isset($arFieldsModif['STATUS_DESCRIPTION'])
			&& $arFieldsOrig['STATUS_DESCRIPTION'] != $arFieldsModif['STATUS_DESCRIPTION'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'STATUS_DESCRIPTION',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_STATUS_DESCRIPTION'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['STATUS_DESCRIPTION'])? $arFieldsOrig['STATUS_DESCRIPTION']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['STATUS_DESCRIPTION'])? $arFieldsModif['STATUS_DESCRIPTION']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		$arCurrency = CCrmCurrencyHelper::PrepareListItems();

		if(isset($arFieldsOrig['CURRENCY_ID'])
			&& isset($arFieldsModif['CURRENCY_ID'])
			&& $arFieldsOrig['CURRENCY_ID'] != $arFieldsModif['CURRENCY_ID'])
		{
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'CURRENCY_ID',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_CURRENCY'),
				'EVENT_TEXT_1' => isset($arCurrency[$arFieldsOrig['CURRENCY_ID']]) ? $arCurrency[$arFieldsOrig['CURRENCY_ID']] : '',
				'EVENT_TEXT_2' => isset($arCurrency[$arFieldsModif['CURRENCY_ID']]) ? $arCurrency[$arFieldsModif['CURRENCY_ID']] : ''
			);
		}

		if((isset($arFieldsOrig['OPPORTUNITY']) && isset($arFieldsModif['OPPORTUNITY']) && $arFieldsOrig['OPPORTUNITY'] != $arFieldsModif['OPPORTUNITY'])
			|| (isset($arFieldsOrig['CURRENCY_ID']) && isset($arFieldsModif['CURRENCY_ID']) && $arFieldsOrig['CURRENCY_ID'] != $arFieldsModif['CURRENCY_ID']))
		{
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'OPPORTUNITY',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_OPPORTUNITY'),
				'EVENT_TEXT_1' => floatval($arFieldsOrig['OPPORTUNITY']).(($val = CrmCompareFieldsList($arCurrency, $arFieldsOrig['CURRENCY_ID'], '')) != '' ? ' ('.$val.')' : ''),
				'EVENT_TEXT_2' => floatval($arFieldsModif['OPPORTUNITY']).(($val = CrmCompareFieldsList($arCurrency, $arFieldsModif['CURRENCY_ID'], '')) != '' ? ' ('.$val.')' : '')
			);
		}

		if (isset($arFieldsOrig['IS_MANUAL_OPPORTUNITY'])
			&& isset($arFieldsModif['IS_MANUAL_OPPORTUNITY'])
			&& $arFieldsOrig['IS_MANUAL_OPPORTUNITY'] != $arFieldsModif['IS_MANUAL_OPPORTUNITY'])
		{
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'IS_MANUAL_OPPORTUNITY',
				'EVENT_NAME' => GetMessage('CRM_LEAD_FIELD_COMPARE_IS_MANUAL_OPPORTUNITY'),
				'EVENT_TEXT_1' => GetMessage('CRM_LEAD_FIELD_COMPARE_IS_MANUAL_OPPORTUNITY_'.($arFieldsOrig['IS_MANUAL_OPPORTUNITY'] == 'Y' ? 'Y' : 'N')),
				'EVENT_TEXT_2' => GetMessage('CRM_LEAD_FIELD_COMPARE_IS_MANUAL_OPPORTUNITY_'.($arFieldsModif['IS_MANUAL_OPPORTUNITY'] == 'Y' ? 'Y' : 'N')),
			);
		}

		if(isset($arFieldsOrig['SOURCE_ID']) && isset($arFieldsModif['SOURCE_ID'])
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

		if(isset($arFieldsOrig['COMPANY_TITLE']) && isset($arFieldsModif['COMPANY_TITLE'])
			&& $arFieldsOrig['COMPANY_TITLE'] != $arFieldsModif['COMPANY_TITLE'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'COMPANY_TITLE',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_COMPANY_TITLE'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['COMPANY_TITLE'])? $arFieldsOrig['COMPANY_TITLE']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['COMPANY_TITLE'])? $arFieldsModif['COMPANY_TITLE']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		if(isset($arFieldsOrig['SOURCE_DESCRIPTION']) && isset($arFieldsModif['SOURCE_DESCRIPTION'])
			&& $arFieldsOrig['SOURCE_DESCRIPTION'] != $arFieldsModif['SOURCE_DESCRIPTION'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'SOURCE_DESCRIPTION',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_SOURCE_DESCRIPTION'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['SOURCE_DESCRIPTION'])? $arFieldsOrig['SOURCE_DESCRIPTION']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['SOURCE_DESCRIPTION'])? $arFieldsModif['SOURCE_DESCRIPTION']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		if(isset($arFieldsOrig['ASSIGNED_BY_ID']) && isset($arFieldsModif['ASSIGNED_BY_ID'])
			&& $arFieldsOrig['ASSIGNED_BY_ID'] != $arFieldsModif['ASSIGNED_BY_ID'])
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

		if(isset($arFieldsModif['BIRTHDATE']))
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


		if(isset($arFieldsOrig['COMPANY_ID']) && isset($arFieldsModif['COMPANY_ID'])
			&& (int)$arFieldsOrig['COMPANY_ID'] != (int)$arFieldsModif['COMPANY_ID'])
		{
			$arCompany = Array();

			$arFilterTmp = array('ID' => array($arFieldsOrig['COMPANY_ID'], $arFieldsModif['COMPANY_ID']));
			if (!$bCheckPerms)
				$arFilterTmp["CHECK_PERMISSIONS"] = "N";

			$dbRes = CCrmCompany::GetList(Array('TITLE'=>'ASC'), $arFilterTmp);
			while ($arRes = $dbRes->Fetch())
				$arCompany[$arRes['ID']] = $arRes['TITLE'];

			$arMsg[] = Array(
				'ENTITY_FIELD' => 'COMPANY_ID',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_COMPANY_ID'),
				'EVENT_TEXT_1' => CrmCompareFieldsList($arCompany, $arFieldsOrig['COMPANY_ID']),
				'EVENT_TEXT_2' => CrmCompareFieldsList($arCompany, $arFieldsModif['COMPANY_ID'])
			);
		}

		if(isset($arOptions['CONTACTS']) && is_array($arOptions['CONTACTS']))
		{
			$addedContactIDs = isset($arOptions['CONTACTS']['ADDED']) && is_array($arOptions['CONTACTS']['ADDED'])
				? $arOptions['CONTACTS']['ADDED'] : array();

			$removedContactIDs = isset($arOptions['CONTACTS']['REMOVED']) && is_array($arOptions['CONTACTS']['REMOVED'])
				? $arOptions['CONTACTS']['REMOVED'] : array();

			if(!empty($addedContactIDs) || !empty($removedContactIDs))
			{
				//region Preparation of contact names
				$dbResult = CCrmContact::GetListEx(
					array(),
					array(
						'CHECK_PERMISSIONS' => 'N',
						'@ID' => array_merge($addedContactIDs, $removedContactIDs)
					),
					false,
					false,
					array('ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME')
				);

				$contactNames = array();
				while ($ary = $dbResult->Fetch())
				{
					$contactNames[$ary['ID']] = CCrmContact::PrepareFormattedName($ary);
				}
				//endregion
				if(count($addedContactIDs) <= 1 && count($removedContactIDs) <= 1)
				{
					//region Single binding mode
					$arMsg[] = Array(
						'ENTITY_FIELD' => 'CONTACT_ID',
						'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_CONTACT_ID'),
						'EVENT_TEXT_1' => CrmCompareFieldsList(
							$contactNames,
							isset($removedContactIDs[0]) ? $removedContactIDs[0] : 0
						),
						'EVENT_TEXT_2' => CrmCompareFieldsList(
							$contactNames,
							isset($addedContactIDs[0]) ? $addedContactIDs[0] : 0
						)
					);
					//endregion
				}
				else
				{
					//region Multiple binding mode
					//region Add contacts event
					$texts = array();
					foreach($addedContactIDs as $contactID)
					{
						if(isset($contactNames[$contactID]))
						{
							$texts[] = $contactNames[$contactID];
						}
					}

					$arMsg[] = Array(
						'ENTITY_FIELD' => 'CONTACT_ID',
						'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_CONTACTS_ADDED'),
						'EVENT_TEXT_1' => implode(', ', $texts),
					);
					//endregion
					//region Remove companies event
					$texts = array();
					foreach($removedContactIDs as $contactID)
					{
						if(isset($contactNames[$contactID]))
						{
							$texts[] = $contactNames[$contactID];
						}
					}

					$arMsg[] = Array(
						'ENTITY_FIELD' => 'CONTACT_ID',
						'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_CONTACTS_REMOVED'),
						'EVENT_TEXT_1' => implode(', ', $texts),
					);
					//endregion
					//endregion
				}
			}
		}

		if(isset($arFieldsModif['IS_RETURN_CUSTOMER']))
		{
			if($arFieldsModif['IS_RETURN_CUSTOMER'] === 'Y')
			{
				$arMsg[] = Array(
					'ENTITY_FIELD' => 'IS_RETURN_CUSTOMER',
					'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_RETURNING_CUSTOMER')
				);
			}
			else
			{
				$arMsg[] = Array(
					'ENTITY_FIELD' => 'IS_RETURN_CUSTOMER',
					'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_GENERAL_CUSTOMER')
				);
			}
		}

		return $arMsg;
	}

	public static function LoadProductRows($ID)
	{
		return CCrmProductRow::LoadRows('L', $ID);
	}

	public static function SaveProductRows($ID, $arRows, $checkPerms = true, $regEvent = true, $syncOwner = true)
	{
		$result = CCrmProductRow::SaveRows('L', $ID, $arRows, null, $checkPerms, $regEvent, $syncOwner);
		if($result)
		{
			$events = GetModuleEvents('crm', 'OnAfterCrmLeadProductRowsSave');
			while ($event = $events->Fetch())
				ExecuteModuleEventEx($event, array($ID, $arRows));
		}
		return $result;
	}

	public static function OnAccountCurrencyChange()
	{
		$accountCurrencyID = CCrmCurrency::GetAccountCurrencyID();
		if(!isset($accountCurrencyID[0]))
		{
			return;
		}

		$rs = self::GetList(
			array('ID' => 'ASC'),
			//array('!ACCOUNT_CURRENCY_ID' => $accountCurrencyID),
			array(),
			array('ID', 'CURRENCY_ID', 'OPPORTUNITY', 'EXCH_RATE')
		);

		$entity = new CCrmLead(false);
		while($arParams = $rs->Fetch())
		{
			$ID = intval($arParams['ID']);
			$entity->Update($ID, $arParams, false, false);
			$arRows = CCrmProductRow::LoadRows('D', $ID);

			$context = array();
			if(isset($arParams['CURRENCY_ID']))
			{
				$context['CURRENCY_ID'] = $arParams['CURRENCY_ID'];
			}

			if(isset($arParams['EXCH_RATE']))
			{
				$context['EXCH_RATE'] = $arParams['EXCH_RATE'];
			}

			if(count($arRows) > 0)
			{
				CCrmProductRow::SaveRows('D', $ID, $arRows, $context);
			}
		}
	}

	public static function SynchronizeProductRows($ID, $checkPerms = true)
	{

		$arTotalInfo = CCrmProductRow::CalculateTotalInfo('L', $ID, $checkPerms);

		if (is_array($arTotalInfo))
		{
			$arFields = array(
				'TAX_VALUE' => isset($arTotalInfo['TAX_VALUE']) ? $arTotalInfo['TAX_VALUE'] : 0.0
			);

			$entity = new CCrmLead($checkPerms);
			if (!$entity::isManualOpportunity($ID))
			{
				$arFields['OPPORTUNITY'] = isset($arTotalInfo['OPPORTUNITY']) ? $arTotalInfo['OPPORTUNITY'] : 0.0;
			}
			$entity->Update($ID, $arFields);
		}
	}

	public static function GetStatusCreatePermissionType($statusID, CCrmPerms $userPermissions = null)
	{
		if($userPermissions === null)
		{
			$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		}

		return $userPermissions->GetPermType(
			self::$TYPE_NAME,
			'ADD',
			array("STATUS_ID{$statusID}")
		);
	}

	public static function GetStatusUpdatePermissionType($statusID, CCrmPerms $userPermissions = null)
	{
		if($userPermissions === null)
		{
			$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		}

		return $userPermissions->GetPermType(
			self::$TYPE_NAME,
			'WRITE',
			array("STATUS_ID{$statusID}")
		);
	}

	public static function GetPermissionAttributes(array $IDs)
	{
		return
			\Bitrix\Crm\Security\Manager::resolveController(self::$TYPE_NAME)
				->getPermissionAttributes(self::$TYPE_NAME, $IDs)
		;
	}

	public static function IsAccessEnabled(CCrmPerms $userPermissions = null)
	{
		return self::CheckReadPermission(0, $userPermissions);
	}

	public static function CheckImportPermission($userPermissions = null)
	{
		return CCrmAuthorizationHelper::CheckImportPermission(self::$TYPE_NAME, $userPermissions);
	}

	public static function CheckCreatePermission($userPermissions = null)
	{
		return CCrmAuthorizationHelper::CheckCreatePermission(self::$TYPE_NAME, $userPermissions);
	}

	public static function CheckUpdatePermission($ID, $userPermissions = null, array $options = null)
	{
		$entityAttrs = $ID > 0 && is_array($options) && isset($options['ENTITY_ATTRS']) ? $options['ENTITY_ATTRS'] : null;
		return CCrmAuthorizationHelper::CheckUpdatePermission(self::$TYPE_NAME, $ID, $userPermissions, $entityAttrs);
	}

	public static function CheckDeletePermission($ID, $userPermissions = null, array $options = null)
	{
		$entityAttrs = $ID > 0 && is_array($options) && isset($options['ENTITY_ATTRS']) ? $options['ENTITY_ATTRS'] : null;
		return CCrmAuthorizationHelper::CheckDeletePermission(self::$TYPE_NAME, $ID, $userPermissions, $entityAttrs);
	}

	public static function CheckReadPermission($ID = 0, $userPermissions = null)
	{
		return CCrmAuthorizationHelper::CheckReadPermission(self::$TYPE_NAME, $ID, $userPermissions);
	}

	public static function CheckConvertPermission($ID = 0, $entityTypeID = 0, $userPermissions = null)
	{
		if(!$userPermissions)
		{
			$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		}

		if($entityTypeID === CCrmOwnerType::Contact)
		{
			return CCrmContact::CheckCreatePermission($userPermissions)
				&& CCrmAuthorizationHelper::CheckUpdatePermission(self::$TYPE_NAME, $ID, $userPermissions);
		}
		elseif($entityTypeID === CCrmOwnerType::Company)
		{
			return CCrmCompany::CheckCreatePermission($userPermissions)
				&& CCrmAuthorizationHelper::CheckUpdatePermission(self::$TYPE_NAME, $ID, $userPermissions);
		}
		elseif($entityTypeID === CCrmOwnerType::Deal)
		{
			return CCrmDeal::CheckCreatePermission($userPermissions)
				&& CCrmAuthorizationHelper::CheckUpdatePermission(self::$TYPE_NAME, $ID, $userPermissions);
		}

		return CCrmAuthorizationHelper::CheckUpdatePermission(self::$TYPE_NAME, $ID, $userPermissions)
			&& (CCrmContact::CheckCreatePermission($userPermissions)
			|| CCrmCompany::CheckCreatePermission($userPermissions)
			|| CCrmDeal::CheckCreatePermission($userPermissions));
	}
	public static function PrepareConversionPermissionFlags($ID, array &$params, $userPermissions = null)
	{
		if(!$userPermissions)
		{
			$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		}

		$canEdit = CCrmAuthorizationHelper::CheckUpdatePermission(self::$TYPE_NAME, $ID, $userPermissions);
		$canCreateContact = CCrmContact::CheckCreatePermission($userPermissions);
		$canCreateCompany = CCrmCompany::CheckCreatePermission($userPermissions);
		$canCreateDeal = CCrmDeal::CheckCreatePermission($userPermissions);

		$params['CAN_CONVERT_TO_CONTACT'] = $canEdit && $canCreateContact;
		$params['CAN_CONVERT_TO_COMPANY'] = $canEdit && $canCreateCompany;
		$params['CAN_CONVERT_TO_DEAL'] = $canEdit && $canCreateDeal;
		$params['CAN_CONVERT'] = $params['CONVERT'] = $canEdit && ($canCreateContact || $canCreateCompany || $canCreateDeal);
		$params['CONVERSION_PERMITTED'] = true;

		if (!Crm\Restriction\RestrictionManager::getLeadsRestriction()->hasPermission())
		{
			$params['CAN_CONVERT'] = false;
		}
	}

	public static function PrepareFilter(&$arFilter, $arFilter2Logic = null)
	{
		if(!is_array($arFilter2Logic))
		{
			$arFilter2Logic = array('TITLE', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'POST', 'ADDRESS', 'COMMENTS', 'COMPANY_TITLE');
		}

		// converts data from filter
		if (isset($arFilter['FIND_list']) && !empty($arFilter['FIND']))
		{
			if ($arFilter['FIND_list'] == 't_n_ln')
			{
				$find = $arFilter['FIND'];
				$arFilter['__INNER_FILTER'] = array(
					'LOGIC' => 'OR',
					'%TITLE' => $find,
					'$NAME' => $find,
					'%LAST_NAME' => $find,
					'%COMPANY_TITLE' => $find
				);
			}
			else
			{
				$arFilter[mb_strtoupper($arFilter['FIND_list'])] = $arFilter['FIND'];
			}
			unset($arFilter['FIND_list'], $arFilter['FIND']);
		}

		static $arImmutableFilters = array(
			'FM', 'ID', 'CURRENCY_ID',
			'ASSIGNED_BY_ID', 'CREATED_BY_ID', 'MODIFY_BY_ID',
			'PRODUCT_ROW_PRODUCT_ID',
			'HAS_PHONE', 'HAS_EMAIL', 'HAS_IMOL',
			'WEBFORM_ID',
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

			if(in_array($k, array('PRODUCT_ID', 'STATUS_ID', 'SOURCE_ID', 'COMPANY_ID', 'CONTACT_ID')))
			{
				// Bugfix #23121 - to suppress comparison by LIKE
				$arFilter['='.$k] = $v;
				unset($arFilter[$k]);
			}
			elseif($k === 'ORIGINATOR_ID')
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
				$arFilter["=%{$k}"] = "{$v}%";
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
					if (($arMatch[1] == 'DATE_CREATE' || $arMatch[1] == 'DATE_MODIFY' || $arMatch[1] == 'DATE_CLOSED')
						&& !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/'.BX_UTF_PCRE_MODIFIER, $v))
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
			elseif($k === 'STATUS_CONVERTED')
			{
				if($v !== '')
				{
					$arFilter[$v === 'N' ? '!@STATUS_ID' : '@STATUS_ID'] = array('JUNK', 'CONVERTED');
				}
				unset($arFilter['STATUS_CONVERTED']);
			}
			elseif ($k != 'ID' && $k != 'LOGIC' && $k != '__INNER_FILTER' && mb_strpos($k, 'UF_') !== 0 && preg_match('/^[^\=\%\?\>\<]{1}/', $k) === 1)
			{
				$arFilter['%'.$k] = $v;
				unset($arFilter[$k]);
			}
		}
	}

	public static function EnsureStatusesLoaded()
	{
		if (self::$LEAD_STATUSES === null)
		{
			$bFinished = $bFailed = false;
			self::$LEAD_STATUSES = CCrmStatus::GetStatus('STATUS');
			$statusesWork = array();
			$statusesSuccess = array();
			$statusesFailed = array();
			$statusesFinished = array();
			foreach (self::$LEAD_STATUSES as $statusInfo)
			{
				if (!$bFinished && $statusInfo['STATUS_ID'] === 'CONVERTED')
				{
					$statusesSuccess[] = $statusInfo['STATUS_ID'];
					$bFinished = true;
				}
				if (!$bFailed && $statusInfo['STATUS_ID'] === 'JUNK')
					$bFailed = true;
				if ($bFinished)
					$statusesFinished[] = $statusInfo['STATUS_ID'];
				else
					$statusesWork[] = $statusInfo['STATUS_ID'];
				if ($bFailed)
					$statusesFailed[] = $statusInfo['STATUS_ID'];
			}
			self::$LEAD_STATUSES_BY_GROUP = array(
				'WORK' => $statusesWork,
				'SUCCESS' => $statusesSuccess,
				'FAILED' => $statusesFailed,
				'FINISHED' => $statusesFinished
			);
		}
	}

	public static function GetStatuses()
	{
		self::EnsureStatusesLoaded();
		return self::$LEAD_STATUSES;
	}

	public static function GetStatusNames()
	{
		$result = array();
		foreach(self::GetStatuses() as $statusID => $status)
		{
			$result[$statusID] = $status['NAME'];
		}
		return $result;
	}

	public static function IsStatusExists($statusID)
	{
		$statusList = self::GetStatuses();
		return isset($statusList[$statusID]);
	}

	public static function IsStatusFinished($statusID)
	{
		self::EnsureStatusesLoaded();
		return in_array($statusID, self::$LEAD_STATUSES_BY_GROUP['FINISHED'], true);
	}

	public static function GetFinalStatusSort()
	{
		return self::GetStatusSort('CONVERTED');
	}

	public static function GetStatusSort($statusID)
	{
		$statusID = strval($statusID);
		if($statusID === '')
		{
			return -1;
		}

		self::EnsureStatusesLoaded();
		$info = isset(self::$LEAD_STATUSES[$statusID]) ? self::$LEAD_STATUSES[$statusID] : null;
		return is_array($info) && isset($info['SORT']) ? (int)($info['SORT']) : -1;
	}

	public static function GetStatusSemantics($statusID)
	{
		if($statusID === 'CONVERTED')
		{
			return 'success';
		}

		if($statusID === 'JUNK')
		{
			return 'failure';
		}

		return (self::GetStatusSort($statusID) > self::GetFinalStatusSort()) ? 'apology' : 'process';
	}

	public static function GetSemanticID($statusID)
	{
		if($statusID === 'CONVERTED')
		{
			return Bitrix\Crm\PhaseSemantics::SUCCESS;
		}

		if($statusID === 'JUNK')
		{
			return Bitrix\Crm\PhaseSemantics::FAILURE;
		}

		return (self::GetStatusSort($statusID) > self::GetFinalStatusSort())
			? Bitrix\Crm\PhaseSemantics::FAILURE : Bitrix\Crm\PhaseSemantics::PROCESS;
	}

	/**
	 * Get start Status ID for specified Permission Type.
	 * If Permission Type is not defined permission check will be disabled.
	 * @param int $permissionTypeID Permission Type (see \Bitrix\Crm\Security\EntityPermissionType).
	 * @param CCrmPerms $userPermissions User Permissions
	 * @return string
	 */
	public static function GetStartStatusID($permissionTypeID = 0, CCrmPerms $userPermissions = null)
	{
		$statusIDs = array_keys(self::GetStatuses());
		if(empty($statusIDs))
		{
			return '';
		}

		$permissionType = Bitrix\Crm\Security\EntityPermissionType::resolveName($permissionTypeID);
		if($permissionType === '')
		{
			return $statusIDs[0];
		}

		if($userPermissions === null)
		{
			$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		}

		foreach($statusIDs as $statusID)
		{
			$permission = $userPermissions->GetPermType(self::$TYPE_NAME, $permissionType, array("STATUS_ID{$statusID}"));
			if($permission !== BX_CRM_PERM_NONE)
			{
				return $statusID;
			}
		}
		return '';
	}

	public static function GetAssociatedIDs($entityTypeID, $entityID)
	{
		$results = array();
		$dbResult = null;
		if($entityTypeID === CCrmOwnerType::Contact)
		{
			$dbResult = self::GetListEx(
				array(),
				array('=CONTACT_ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID')
			);
		}
		elseif($entityTypeID === CCrmOwnerType::Company)
		{
			$dbResult = self::GetListEx(
				array(),
				array('=COMPANY_ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID')
			);
		}

		if(is_object($dbResult))
		{
			while($fields = $dbResult->Fetch())
			{
				$results[] = (int)$fields['ID'];
			}
		}
		return $results;
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

	public static function AddObserverIDs($ID, array $userIDs)
	{
		if(empty($userIDs))
		{
			return;
		}

		$observerIDs = array_unique(
			array_merge(
				Crm\Observer\ObserverManager::getEntityObserverIDs(CCrmOwnerType::Lead, $ID),
				$userIDs
			),
			SORT_NUMERIC
		);

		$fields = array('OBSERVER_IDS' => $observerIDs);
		$entity = new CCrmLead(false);
		$entity->Update($ID,$fields);
	}

	public static function RemoveObserverIDs($ID, array $userIDs)
	{
		if(empty($userIDs))
		{
			return;
		}

		$observerIDs = array_diff(
			Crm\Observer\ObserverManager::getEntityObserverIDs(CCrmOwnerType::Lead, $ID),
			$userIDs
		);

		$fields = array('OBSERVER_IDS' => $observerIDs);
		$entity = new CCrmLead(false);
		$entity->Update($ID, $fields);
	}

	public static function ReplaceObserverIDs($ID, array $userIDs)
	{
		if(empty($userIDs))
		{
			return;
		}

		$fields = array('OBSERVER_IDS' => $userIDs);
		$entity = new CCrmLead(false);
		$entity->Update($ID, $fields);
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
			array('ID', 'TITLE', 'COMPANY_TITLE', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'ADDRESS', 'DATE_MODIFY')
		);

		if(!is_object($dbResult))
		{
			return;
		}

		$entityMultifields = DuplicateCommunicationCriterion::prepareBatchEntityMultifieldValues(
			CCrmOwnerType::Lead,
			$IDs
		);

		$duplicateCriterionRegistrar = DuplicateManager::getCriterionRegistrarForReindex(\CCrmOwnerType::Lead);

		while($fields = $dbResult->Fetch())
		{
			$ID = (int)$fields['ID'];
			$fields['FM'] = $entityMultifields[$ID] ?? null;

			$data =
				(new Crm\Integrity\CriterionRegistrar\Data())
					->setEntityTypeId(\CCrmOwnerType::Lead)
					->setEntityId($ID)
					->setCurrentFields($fields)
			;
			$duplicateCriterionRegistrar->register($data);
		}
	}

	public static function RebuildStatistics(array $IDs, array $options = null)
	{
		$dbResult = self::GetListEx(
			array(),
			array('@ID' => $IDs, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array(
				'ID', 'DATE_CREATE', 'DATE_MODIFY', 'STATUS_ID', 'SOURCE_ID',
				'ASSIGNED_BY_ID' , 'CURRENCY_ID', 'OPPORTUNITY', 'UF_*'
			)
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
		$enableActivityStatistics = isset($options['ENABLE_ACTIVITY_STATISTICS']) ? $options['ENABLE_ACTIVITY_STATISTICS'] : true;
		$enableConversionStatistics = isset($options['ENABLE_CONVERSION_STATISTICS']) ? $options['ENABLE_CONVERSION_STATISTICS'] : true;

		while($fields = $dbResult->Fetch())
		{
			$ID = (int)$fields['ID'];
			$statusID = isset($fields['STATUS_ID']) ? $fields['STATUS_ID'] : '';
			if($statusID !== '')
			{
				$created = isset($fields['DATE_CREATE']) ? $fields['DATE_CREATE'] : '';
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

				if($createdTime === null)
				{
					$createdTime = new Bitrix\Main\Type\DateTime();
				}

				$modified = isset($fields['DATE_MODIFY']) ? $fields['DATE_MODIFY'] : '';
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

				if($modifiedTime === null)
				{
					$modifiedTime = $createdTime;
				}

				$isNew = $createdTime->getTimestamp() === $modifiedTime->getTimestamp();

				//--> History
				if($enableHistory && ($forced || !Bitrix\Crm\History\LeadStatusHistoryEntry::isRegistered($ID)))
				{

					Bitrix\Crm\History\LeadStatusHistoryEntry::register(
						$ID,
						$fields,
						array('IS_NEW' => $isNew, 'TIME' => $isNew ? $createdTime : $modifiedTime)
					);
				}
				//<-- History

				//--> Statistics
				if($enableConversionStatistics && $statusID === 'CONVERTED')
				{
					$isRegistered = false;
					if($forced)
					{
						Bitrix\Crm\Statistics\LeadConversionStatisticsEntry::unregister($ID);
					}
					else
					{
						$isRegistered = Bitrix\Crm\Statistics\LeadConversionStatisticsEntry::isRegistered($ID);
					}

					$statusHistory = \Bitrix\Crm\History\LeadStatusHistoryEntry::getLatest($ID);
					if($statusHistory && ($forced || !$isRegistered))
					{
						Bitrix\Crm\Statistics\LeadConversionStatisticsEntry::register(
							$ID,
							$fields,
							array(
								'IS_NEW' => $isNew,
								'DATE' => $statusHistory['CREATED_DATE']
							)
						);
					}
				}
				//<-- Statistics
			}

			//--> Statistics
			if($enableSumStatistics && ($forced || !Bitrix\Crm\Statistics\LeadSumStatisticEntry::isRegistered($ID)))
			{
				Bitrix\Crm\Statistics\LeadSumStatisticEntry::register($ID, $fields, array('FORCED' => $forced));
			}

			if($enableActivityStatistics)
			{
				$timeline = Bitrix\Crm\Statistics\LeadActivityStatisticEntry::prepareTimeline($ID);
				foreach($timeline as $date)
				{
					Bitrix\Crm\Statistics\LeadActivityStatisticEntry::register($ID, $fields, array('DATE' => $date));
				}
			}
			//<-- Statistics
		}
	}

	public static function RebuildSemantics(array $IDs, array $options = null)
	{
		$dbResult = self::GetListEx(
			array(),
			array('@ID' => $IDs, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'STATUS_SEMANTIC_ID', 'STATUS_ID')
		);

		if(!is_object($dbResult))
		{
			return;
		}

		$entity = new CCrmLead(false);
		$forced = is_array($options) && isset($options['FORCED']) ? $options['FORCED'] : false;
		while($fields = $dbResult->Fetch())
		{
			$ID = (int)$fields['ID'];

			if(isset($fields['STATUS_SEMANTIC_ID']) && !$forced)
			{
				continue;
			}

			$updateFields = array('STATUS_ID' => isset($fields['STATUS_ID']) ? $fields['STATUS_ID'] : '');
			$entity->Update(
				$ID,
				$updateFields,
				false,
				false,
				array(
					'SYNCHRONIZE_STATUS_SEMANTICS' => true,
					'REGISTER_SONET_EVENT' => false,
					'ENABLE_SYSTEM_EVENTS' => false,
					'IS_SYSTEM_ACTION' => true
				)
			);
		}
	}

	public static function RefreshAccountingData(array $IDs)
	{
		$dbResult = self::GetListEx(
			array(),
			array('@ID' => $IDs, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'CURRENCY_ID', 'EXCH_RATE')
		);

		if(!is_object($dbResult))
		{
			return;
		}

		$entity = new CCrmLead(false);
		while($fields = $dbResult->Fetch())
		{
			$ID = (int)$fields['ID'];

			$currencyID = isset($fields['CURRENCY_ID']) ? $fields['CURRENCY_ID'] : '';
			$exchRate = isset($fields['EXCH_RATE']) ? (double)$fields['EXCH_RATE'] : -1;

			$currentCurrencyID = $currencyID !== '' ? $currencyID : CCrmCurrency::GetBaseCurrencyID();
			$currentExchRate = CCrmCurrency::GetExchangeRate($currencyID);
			if($currentCurrencyID === $currencyID && $currentExchRate === $exchRate)
			{
				continue;
			}

			$updateFields = array('CURRENCY_ID' => $currentCurrencyID, 'EXCH_RATE' => $currentExchRate);
			$entity->Update(
				$ID,
				$updateFields,
				false,
				false,
				array(
					'REGISTER_SONET_EVENT' => false,
					'ENABLE_SYSTEM_EVENTS' => false,
					'IS_SYSTEM_ACTION' => true
				)
			);
		}
	}

	public static function ProcessContactDeletion($contactID)
	{
		//We have to call update for each entity for synchronize customer type.
		$entity = new CCrmLead(false);

		$contactIdentifier = null;
		if ($contactID > 0)
		{
			$contactIdentifier = new Crm\ItemIdentifier(\CCrmOwnerType::Contact, (int)$contactID);
		}

		foreach(\Bitrix\Crm\Binding\LeadContactTable::getContactLeadIDs($contactID) as $ID)
		{
			$fields = array(
				'CONTACT_IDS' => array_filter(
					\Bitrix\Crm\Binding\LeadContactTable::getLeadContactIDs($ID),
					function($currentContactID) use($contactID)
					{
						return $currentContactID != $contactID;
					}
				)
			);

			$entity->Update(
				$ID,
				$fields,
				true,
				true,
				[
					'EXCLUDE_FROM_RELATION_REGISTRATION' => [
						$contactIdentifier,
					],
				],
			);
		}
	}
	public static function ProcessCompanyDeletion($companyID)
	{
		$dbResult = self::GetListEx(
			array(),
			array('=COMPANY_ID' => $companyID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID')
		);

		$companyIdentifier = null;
		if ($companyID > 0)
		{
			$companyIdentifier = new Crm\ItemIdentifier(\CCrmOwnerType::Company, (int)$companyID);
		}

		$entity = new CCrmLead(false);
		while($fields = $dbResult->Fetch())
		{
			$fields['COMPANY_ID'] = 0;
			$entity->Update(
				$fields['ID'],
				$fields,
				true,
				true,
				[
					'EXCLUDE_FROM_RELATION_REGISTRATION' => [
						$companyIdentifier,
					],
				],
			);
		}
	}
	/**
	 * @deprecated
	 * @param array $fields
	 */
	public static function ProcessStatusModification(array $fields)
	{
		$entityID = isset($fields['ENTITY_ID']) ? $fields['ENTITY_ID'] : '';
		$statusID = isset($fields['STATUS_ID']) ? $fields['STATUS_ID'] : '';

		if($entityID === 'STATUS' && $statusID !== '')
		{
			Crm\Attribute\FieldAttributeManager::processPhaseModification(
				$statusID,
				\CCrmOwnerType::Lead,
				'',
				\CCrmStatus::GetStatus('STATUS')
			);
		}
	}

	/**
	 * @deprecated
	 * @param array $fields
	 */
	public static function ProcessStatusDeletion(array $fields)
	{
		$entityID = isset($fields['ENTITY_ID']) ? $fields['ENTITY_ID'] : '';
		$statusID = isset($fields['STATUS_ID']) ? $fields['STATUS_ID'] : '';

		if($entityID === 'STATUS' && $statusID !== '')
		{
			Crm\Attribute\FieldAttributeManager::processPhaseDeletion($statusID, \CCrmOwnerType::Lead, '');
		}
	}

	protected static function SynchronizeCustomerType($ID, array $fields = null)
	{
		if(!is_array($fields))
		{
			$dbResult = self::GetListEx(
				array(),
				array('ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID', 'IS_RETURN_CUSTOMER', 'STATUS_ID', 'COMPANY_ID', 'CONTACT_ID')
			);

			$fields = $dbResult->Fetch();
		}

		if(!is_array($fields))
		{
			return;
		}

		$customerType = isset($fields['IS_RETURN_CUSTOMER']) && $fields['IS_RETURN_CUSTOMER'] === 'Y'
			? CustomerType::RETURNING : CustomerType::GENERAL;

		$effectiveCustomerType = CustomerType::GENERAL;
		if(
			(isset($fields['COMPANY_ID']) && $fields['COMPANY_ID'] > 0)
			|| (isset($fields['CONTACT_ID']) && $fields['CONTACT_ID'] > 0)
		)
		{
			$effectiveCustomerType = CustomerType::RETURNING;
		}

		if($customerType === $effectiveCustomerType)
		{
			return;
		}

		$entity = new CCrmLead(false);
		$updateFields = array('IS_RETURN_CUSTOMER' => $effectiveCustomerType === CustomerType::RETURNING ? 'Y' : 'N');
		$entity->Update($ID, $updateFields, false, false);
	}

	public static function GetSubsidiaryEntities($ID)
	{
		return Crm\Entity\Lead::getSubsidiaryEntities($ID);
	}

	public static function GetCustomerType($ID)
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			return CustomerType::UNDEFINED;
		}

		$dbRes = self::GetListEx(
			array(),
			array('ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'IS_RETURN_CUSTOMER')
		);

		$fields = $dbRes->Fetch();
		if(!is_array($fields))
		{
			return CustomerType::UNDEFINED;
		}

		return isset($fields['IS_RETURN_CUSTOMER']) && $fields['IS_RETURN_CUSTOMER'] === 'Y'
			? CustomerType::RETURNING : CustomerType::GENERAL;
	}

	public static function ResolveCustomerType(array $arFields)
	{
		return isset($arFields['IS_RETURN_CUSTOMER']) && $arFields['IS_RETURN_CUSTOMER'] === 'Y'
			? CustomerType::RETURNING : CustomerType::GENERAL;
	}
	public static function getCustomerFields()
	{
		return array(
			'HONORIFIC', 'LAST_NAME', 'NAME', 'SECOND_NAME',
			'BIRTHDATE', 'FM', 'COMPANY_TITLE', 'POST',
			'ADDRESS', 'ADDRESS_2', 'ADDRESS_CITY', 'ADDRESS_REGION',
			'ADDRESS_PROVINCE', 'ADDRESS_POSTAL_CODE',
			'ADDRESS_COUNTRY', 'ADDRESS_COUNTRY_CODE', 'ADDRESS_LOC_ADDR_ID'
		);
	}

	public static function GetDefaultTitleTemplate()
	{
		return GetMessage('CRM_LEAD_DEFAULT_TITLE_TEMPLATE');
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
				CCrmOwnerType::Lead,
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
			$DB->Query("UPDATE b_crm_lead SET HAS_EMAIL = '{$hasEmail}', HAS_PHONE = '{$hasPhone}', HAS_IMOL = '{$hasImol}' WHERE ID = {$sourceID}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		}
	}

	public static function GetDefaultTitle($number = '')
	{
		return GetMessage('CRM_LEAD_DEFAULT_TITLE_TEMPLATE', array('%NUMBER%' => $number));
	}

	public static function existsEntityWithStatus($statusId)
	{
		$queryObject = self::getListEx(
			['ID' => 'DESC'],
			['STATUS_ID' => $statusId, 'CHECK_PERMISSIONS' => 'N'],
			false,
			false,
			['ID']
		);

		return (bool) $queryObject->fetch();
	}

	public static function isManualOpportunity($ID)
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
			array('ID', 'IS_MANUAL_OPPORTUNITY')
		);

		if ($arRes = $dbRes->Fetch())
		{
			return ($arRes['IS_MANUAL_OPPORTUNITY'] == 'Y');
		}
		return false;
	}

	public static function Rebind(int $ownerTypeId, int $oldId, int $newId)
	{
		$ownerTypeId = intval($ownerTypeId);
		$oldId = intval($oldId);
		$newId = intval($newId);
		$tableName = CCrmLead::TABLE_NAME;

		$connection = \Bitrix\Main\Application::getConnection();
		if($ownerTypeId === CCrmOwnerType::Contact)
		{
			$connection->query("UPDATE {$tableName} SET CONTACT_ID = {$newId} WHERE CONTACT_ID = {$oldId}");
		}
		elseif($ownerTypeId === CCrmOwnerType::Company)
		{
			$connection->query("UPDATE {$tableName} SET COMPANY_ID = {$newId} WHERE COMPANY_ID = {$oldId}");
		}
	}
}
