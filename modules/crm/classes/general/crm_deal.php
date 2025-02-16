<?php
IncludeModuleLangFile(__FILE__);
//@codingStandardsIgnoreFile

use Bitrix\Crm;
use Bitrix\Crm\Activity\Entity;
use Bitrix\Crm\Activity\Provider\ToDo;
use Bitrix\Crm\Binding\DealContactTable;
use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Category\DealCategoryChangeError;
use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\CustomerType;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\Entity\Traits\EntityFieldsNormalizer;
use Bitrix\Crm\Entity\Traits\UserFieldPreparer;
use Bitrix\Crm\FieldContext\EntityFactory;
use Bitrix\Crm\FieldContext\ValueFiller;
use Bitrix\Crm\Format\TextHelper;
use Bitrix\Crm\History\DealStageHistoryEntry;
use Bitrix\Crm\Integration\Channel\DealChannelBinding;
use Bitrix\Crm\Integration\Im\ProcessEntity\NotificationManager;
use Bitrix\Crm\Integration\PullManager;
use Bitrix\Crm\Item;
use Bitrix\Crm\Kanban\ViewMode;
use Bitrix\Crm\Security\QueryBuilder\OptionsBuilder;
use Bitrix\Crm\Security\QueryBuilder\Result\JoinWithUnionSpecification;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\DealSettings;
use Bitrix\Crm\Settings\HistorySettings;
use Bitrix\Crm\Statistics\DealActivityStatisticEntry;
use Bitrix\Crm\Statistics\DealInvoiceStatisticEntry;
use Bitrix\Crm\Statistics\DealSumStatisticEntry;
use Bitrix\Crm\Statistics\LeadConversionStatisticsEntry;
use Bitrix\Crm\Tracking;
use Bitrix\Crm\UserField\Visibility\VisibilityManager;
use Bitrix\Crm\UtmTable;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;

class CAllCrmDeal
{
	use UserFieldPreparer;
	use EntityFieldsNormalizer;

	static public $sUFEntityID = 'CRM_DEAL';

	const USER_FIELD_ENTITY_ID = 'CRM_DEAL';
	const SUSPENDED_USER_FIELD_ENTITY_ID = 'CRM_DEAL_SPD';
	const TOTAL_COUNT_CACHE_ID = 'crm_deal_total_count';
	const CACHE_TTL = 3600;

	protected const TABLE_NAME = 'b_crm_deal';

	public $LAST_ERROR = '';
	protected $checkExceptions = array();

	public $cPerms = null;
	protected $bCheckPermission = true;
	const TABLE_ALIAS = 'L';
	protected static $TYPE_NAME = 'DEAL';
	private static $DEAL_STAGES = null;
	private static $FIELD_INFOS = null;

	private static ?Crm\Entity\Compatibility\Adapter $lastActivityAdapter = null;
	private static ?Crm\Entity\Compatibility\Adapter $commentsAdapter = null;

	/** @var \Bitrix\Crm\Entity\Compatibility\Adapter */
	private $compatibilityAdapter;

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
		return Crm\Settings\DealSettings::getCurrent()->isFactoryEnabled();
	}

	private function getCompatibilityAdapter(): Crm\Entity\Compatibility\Adapter
	{
		if (!$this->compatibilityAdapter)
		{
			$this->compatibilityAdapter = static::createCompatibilityAdapter();

			if ($this->compatibilityAdapter instanceof Crm\Entity\Compatibility\Adapter\Operation)
			{
				$this->compatibilityAdapter
					// bind newly created adapter to this instance
					->setCheckPermissions((bool)$this->bCheckPermission)
					->setErrorMessageContainer($this->LAST_ERROR)
					->setCheckExceptionsContainer($this->checkExceptions)
				;
			}
		}

		return $this->compatibilityAdapter;
	}

	private static function createCompatibilityAdapter(): Crm\Entity\Compatibility\Adapter
	{
		$factory = Container::getInstance()->getFactory(\CCrmOwnerType::Deal);
		if (!$factory)
		{
			throw new Error('No factory for deal');
		}

		return
			(new Crm\Entity\Compatibility\Adapter\Operation($factory))
				->setRunAutomation(false)
				->setRunBizProc(false)
				->setAlwaysExposedFields([
					'MODIFY_BY_ID',
					'EXCH_RATE',
					'ACCOUNT_CURRENCY_ID',
					'OPPORTUNITY_ACCOUNT',
					'TAX_VALUE_ACCOUNT',
					'ID',
				])
				->setExposedOnlyAfterAddFields([
					'CREATED_BY_ID',
					'ASSIGNED_BY_ID',
					'TITLE',
					'IS_RECURRING',
					'CATEGORY_ID',
					'STAGE_ID',
					'STAGE_SEMANTIC_ID',
					'IS_NEW',
					'CURRENCY_ID',
					'CLOSED',
					'COMPANY_ID',
				])
		;
	}

	private static function getLastActivityAdapter(): Crm\Entity\Compatibility\Adapter
	{
		if (!self::$lastActivityAdapter)
		{
			$factory = Container::getInstance()->getFactory(\CCrmOwnerType::Deal);
			self::$lastActivityAdapter = new Crm\Entity\Compatibility\Adapter\LastActivity($factory);
			self::$lastActivityAdapter->setTableAlias(self::TABLE_ALIAS);
		}

		return self::$lastActivityAdapter;
	}

	private static function getCommentsAdapter(): Crm\Entity\Compatibility\Adapter\Comments
	{
		if (!self::$commentsAdapter)
		{
			self::$commentsAdapter = new Crm\Entity\Compatibility\Adapter\Comments(\CCrmOwnerType::Deal);
		}

		return self::$commentsAdapter;
	}

	// Service -->
	public static function GetFieldCaption($fieldName)
	{
		$result = GetMessage("CRM_DEAL_FIELD_{$fieldName}");
		if (!(is_string($result) && $result !== ''))
		{
			$result = GetMessage("CRM_DEAL_FIELD_{$fieldName}_MSGVER_1");
		}

		if (!(is_string($result) && $result !== '')
			&& Crm\Tracking\UI\Details::isTrackingField($fieldName))
		{
			$result = Crm\Tracking\UI\Details::getFieldCaption($fieldName);
		}
		if (
			!(is_string($result) && $result !== '')
			&& Crm\Service\ParentFieldManager::isParentFieldName($fieldName)
		)
		{
			$entityTypeId = Crm\Service\ParentFieldManager::getEntityTypeIdFromFieldName($fieldName);
			$result = \CCrmOwnerType::GetDescription($entityTypeId);
		}

		return is_string($result) ? $result : '';
	}
	// Get Fields Metadata
	public static function GetFieldsInfo()
	{
		if (self::$FIELD_INFOS)
		{
			return self::$FIELD_INFOS;
		}

		self::$FIELD_INFOS = array(
			'ID' => array(
				'TYPE' => 'integer',
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
			),
			'TITLE' => array(
				'TYPE' => 'string',
			),
			'TYPE_ID' => array(
				'TYPE' => 'crm_status',
				'CRM_STATUS_TYPE' => 'DEAL_TYPE',
				'ATTRIBUTES' => [CCrmFieldInfoAttr::HasDefaultValue],
			),
			'CATEGORY_ID' => array(
				'TYPE' => 'crm_category',
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::Immutable)
			),
			'STAGE_ID' => array(
				'TYPE' => 'crm_status',
				'CRM_STATUS_TYPE' => 'DEAL_STAGE',
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::Progress)
			),
			'STAGE_SEMANTIC_ID' => array(
				'TYPE' => 'string',
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
			),
			'IS_NEW' => array(
				'TYPE' => 'char',
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
			),
			'IS_RECURRING' => array(
				'TYPE' => 'char'
			),
			'IS_RETURN_CUSTOMER' => array(
				'TYPE' => 'char'
			),
			'IS_REPEATED_APPROACH' => array(
				'TYPE' => 'char'
			),
			'PROBABILITY' => array(
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
			'TAX_VALUE' => array(
				'TYPE' => 'double'
			),
			'EXCH_RATE' => array(
				'TYPE' => 'double',
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::Hidden)
			),
			'ACCOUNT_CURRENCY_ID' => array(
				'TYPE' => 'string',
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::Hidden)
			),
			'OPPORTUNITY_ACCOUNT' => array(
				'TYPE' => 'double',
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::Hidden)
			),
			'TAX_VALUE_ACCOUNT' => array(
				'TYPE' => 'double',
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::Hidden)
			),
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
			'QUOTE_ID' => array(
				'TYPE' => 'crm_quote',
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly),
				'SETTINGS' => [
					'parentEntityTypeId' => \CCrmOwnerType::Quote,
				],
			),
			'BEGINDATE' => array(
				'TYPE' => 'date',
				'ATTRIBUTES' => [CCrmFieldInfoAttr::HasDefaultValue],
			),
			'CLOSEDATE' => array(
				'TYPE' => 'date',
				'ATTRIBUTES' => [CCrmFieldInfoAttr::HasDefaultValue],
			),
			'OPENED' => array(
				'TYPE' => 'char'
			),
			'CLOSED' => array(
				'TYPE' => 'char'
			),
			'COMMENTS' => array(
				'TYPE' => 'string',
				'VALUE_TYPE' => 'html',
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
			'SOURCE_ID' => array(
				'TYPE' => 'crm_status',
				'CRM_STATUS_TYPE' => 'SOURCE'
			),
			'SOURCE_DESCRIPTION' => array(
				'TYPE' => 'string'
			),
			'LEAD_ID' => array(
				'TYPE' => 'crm_lead',
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly),
				'SETTINGS' => [
					'parentEntityTypeId' => \CCrmOwnerType::Lead,
				],
			),
			'ADDITIONAL_INFO' => array(
				'TYPE' => 'string'
			),
			'LOCATION_ID' => array(
				'TYPE' => 'location'
			),
			'ORIGINATOR_ID' => array(
				'TYPE' => 'string'
			),
			'ORIGIN_ID' => array(
				'TYPE' => 'string'
			),
		);

		// add utm fields
		self::$FIELD_INFOS += UtmTable::getUtmFieldsInfo();
		self::$FIELD_INFOS += Container::getInstance()->getParentFieldManager()->getParentFieldsInfo(\CCrmOwnerType::Deal);

		self::$FIELD_INFOS += self::getLastActivityAdapter()->getFieldsInfo();

		return self::$FIELD_INFOS;
	}
	public static function GetFields($arOptions = null)
	{
		$companyJoin = 'LEFT JOIN b_crm_company CO ON L.COMPANY_ID = CO.ID';
		$contactJoin = 'LEFT JOIN b_crm_contact C ON L.CONTACT_ID = C.ID';
		$quoteJoin = 'LEFT JOIN b_crm_quote Q ON L.QUOTE_ID = Q.ID';
		$assignedByJoin = 'LEFT JOIN b_user U ON L.ASSIGNED_BY_ID = U.ID';
		$createdByJoin = 'LEFT JOIN b_user U2 ON L.CREATED_BY_ID = U2.ID';
		$modifyByJoin = 'LEFT JOIN b_user U3 ON L.MODIFY_BY_ID = U3.ID';

		$result = array(
			'ID' => array('FIELD' => 'L.ID', 'TYPE' => 'int'),
			'TITLE' => array('FIELD' => 'L.TITLE', 'TYPE' => 'string'),
			'TYPE_ID' => array('FIELD' => 'L.TYPE_ID', 'TYPE' => 'string'),
			'STAGE_ID' => array('FIELD' => 'L.STAGE_ID', 'TYPE' => 'string'),
			'ORDER_STAGE' => array('FIELD' => 'L.ORDER_STAGE', 'TYPE' => 'string'),
			'PROBABILITY' => array('FIELD' => 'L.PROBABILITY', 'TYPE' => 'int'),
			'CURRENCY_ID' => array('FIELD' => 'L.CURRENCY_ID', 'TYPE' => 'string'),
			'EXCH_RATE' => array('FIELD' => 'L.EXCH_RATE', 'TYPE' => 'double'),
			'OPPORTUNITY' => array('FIELD' => 'L.OPPORTUNITY', 'TYPE' => 'double'),
			'IS_MANUAL_OPPORTUNITY' => array('FIELD' => 'L.IS_MANUAL_OPPORTUNITY', 'TYPE' => 'char'),
			'TAX_VALUE' => array('FIELD' => 'L.TAX_VALUE', 'TYPE' => 'double'),
			'ACCOUNT_CURRENCY_ID' => array('FIELD' => 'L.ACCOUNT_CURRENCY_ID', 'TYPE' => 'string'),
			'OPPORTUNITY_ACCOUNT' => array('FIELD' => 'L.OPPORTUNITY_ACCOUNT', 'TYPE' => 'double'),
			'TAX_VALUE_ACCOUNT' => array('FIELD' => 'L.TAX_VALUE_ACCOUNT', 'TYPE' => 'double'),

			'LEAD_ID' => array('FIELD' => 'L.LEAD_ID', 'TYPE' => 'int'),
			'COMPANY_ID' => array('FIELD' => 'L.COMPANY_ID', 'TYPE' => 'int'),
			'COMPANY_ADDRESS' => array('FIELD' => 'CO.ADDRESS', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'COMPANY_ADDRESS_LEGAL' => array('FIELD' => 'CO.ADDRESS_LEGAL', 'TYPE' => 'string', 'FROM' => $companyJoin),

			'CONTACT_ID' => array('FIELD' => 'L.CONTACT_ID', 'TYPE' => 'int'),
			'CONTACT_ADDRESS' => array('FIELD' => 'C.ADDRESS', 'TYPE' => 'string', 'FROM' => $contactJoin),

			'QUOTE_ID' => array('FIELD' => 'L.QUOTE_ID', 'TYPE' => 'int'),
			'QUOTE_TITLE' => array('FIELD' => 'Q.TITLE', 'TYPE' => 'string', 'FROM' => $quoteJoin),

			'BEGINDATE' => array('FIELD' => 'L.BEGINDATE', 'TYPE' => 'date'),
			'CLOSEDATE' => array('FIELD' => 'L.CLOSEDATE', 'TYPE' => 'date'),

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

			'OPENED' => array('FIELD' => 'L.OPENED', 'TYPE' => 'char'),
			'CLOSED' => array('FIELD' => 'L.CLOSED', 'TYPE' => 'char'),
			'COMMENTS' => array('FIELD' => 'L.COMMENTS', 'TYPE' => 'string'),
			'ADDITIONAL_INFO' => array('FIELD' => 'L.ADDITIONAL_INFO', 'TYPE' => 'string'),
			'LOCATION_ID' => array('FIELD' => 'L.LOCATION_ID', 'TYPE' => 'string'),

			'CATEGORY_ID' => array('FIELD' => 'L.CATEGORY_ID', 'TYPE' => 'int'),
			'STAGE_SEMANTIC_ID' => array('FIELD' => 'L.STAGE_SEMANTIC_ID', 'TYPE' => 'string'),
			'IS_NEW' => array('FIELD' => 'L.IS_NEW', 'TYPE' => 'char'),
			'IS_RECURRING' => array('FIELD' => 'L.IS_RECURRING', 'TYPE' => 'char'),
			'IS_RETURN_CUSTOMER' => array('FIELD' => 'L.IS_RETURN_CUSTOMER', 'TYPE' => 'char'),
			'IS_REPEATED_APPROACH' => array('FIELD' => 'L.IS_REPEATED_APPROACH', 'TYPE' => 'char'),

			'SOURCE_ID' => array('FIELD' => 'L.SOURCE_ID', 'TYPE' => 'string'),
			'SOURCE_DESCRIPTION' => array('FIELD' => 'L.SOURCE_DESCRIPTION', 'TYPE' => 'string'),

			'WEBFORM_ID' => array('FIELD' => 'L.WEBFORM_ID', 'TYPE' => 'int'),
			'ORIGINATOR_ID' => array('FIELD' => 'L.ORIGINATOR_ID', 'TYPE' => 'string'), //EXTERNAL SYSTEM THAT OWNS THIS ITEM
			'ORIGIN_ID' => array('FIELD' => 'L.ORIGIN_ID', 'TYPE' => 'string'), //ITEM ID IN EXTERNAL SYSTEM

			'MOVED_BY_ID' => ['FIELD' => 'L.MOVED_BY_ID', 'TYPE' => 'int'],
			'MOVED_TIME' => ['FIELD' => 'L.MOVED_TIME', 'TYPE' => 'datetime'],

			// For compatibility only
			'PRODUCT_ID' => array('FIELD' => 'L.PRODUCT_ID', 'TYPE' => 'string'),
			// Obsolete
			'EVENT_ID' => array('FIELD' => 'L.EVENT_ID', 'TYPE' => 'string'),
			'EVENT_DATE' => array('FIELD' => 'L.EVENT_DATE', 'TYPE' => 'datetime'),
			'EVENT_DESCRIPTION' => array('FIELD' => 'L.EVENT_DESCRIPTION', 'TYPE' => 'string'),
			'LAST_ACTIVITY_TIME' => array('FIELD' => 'L.LAST_ACTIVITY_TIME', 'TYPE' => 'datetime')
		);

		// Creation of field aliases
		$result['ASSIGNED_BY'] = $result['ASSIGNED_BY_ID'];
		$result['CREATED_BY'] = $result['CREATED_BY_ID'];
		$result['MODIFY_BY'] = $result['MODIFY_BY_ID'];

		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		$categoryID = isset($arOptions['CATEGORY_ID']) ? (int)$arOptions['CATEGORY_ID'] : 0;
		$additionalFields = isset($arOptions['ADDITIONAL_FIELDS'])
			? $arOptions['ADDITIONAL_FIELDS'] : null;

		if(is_array($additionalFields))
		{
			if(in_array('STAGE_SORT', $additionalFields, true))
			{
				$statusEntityID = DealCategory::getStatusEntityID($categoryID);
				$stageJoin = "LEFT JOIN b_crm_status ST ON ST.ENTITY_ID = '{$statusEntityID}' AND L.STAGE_ID = ST.STATUS_ID";
				$result['STAGE_SORT'] = array('FIELD' => 'ST.SORT', 'TYPE' => 'int', 'FROM' => $stageJoin);
			}

			if(in_array('ACTIVITY', $additionalFields, true))
			{
				$commonActivityJoin = CCrmActivity::PrepareJoin(0, CCrmOwnerType::Deal, 'L', 'AC', 'UAC', 'ACUSR');

				$result['C_ACTIVITY_ID'] = array('FIELD' => 'UAC.ACTIVITY_ID', 'TYPE' => 'int', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_TIME'] = array('FIELD' => 'UAC.ACTIVITY_TIME', 'TYPE' => 'datetime', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_SUBJECT'] = array('FIELD' => 'AC.SUBJECT', 'TYPE' => 'string', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_RESP_ID'] = array('FIELD' => 'AC.RESPONSIBLE_ID', 'TYPE' => 'int', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_RESP_LOGIN'] = array('FIELD' => 'ACUSR.LOGIN', 'TYPE' => 'string', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_RESP_NAME'] = array('FIELD' => 'ACUSR.NAME', 'TYPE' => 'string', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_RESP_LAST_NAME'] = array('FIELD' => 'ACUSR.LAST_NAME', 'TYPE' => 'string', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_RESP_SECOND_NAME'] = array('FIELD' => 'ACUSR.SECOND_NAME', 'TYPE' => 'string', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_TYPE_ID'] = array('FIELD' => 'AC.TYPE_ID', 'TYPE' => 'int', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_PROVIDER_ID'] = array('FIELD' => 'AC.PROVIDER_ID', 'TYPE' => 'string', 'FROM' => $commonActivityJoin);

				$userID = CCrmPerms::GetCurrentUserID();
				if($userID > 0)
				{
					$activityJoin = CCrmActivity::PrepareJoin($userID, CCrmOwnerType::Deal, 'L', 'A', 'UA', '');

					$result['ACTIVITY_ID'] = array('FIELD' => 'UA.ACTIVITY_ID', 'TYPE' => 'int', 'FROM' => $activityJoin);
					$result['ACTIVITY_TIME'] = array('FIELD' => 'UA.ACTIVITY_TIME', 'TYPE' => 'datetime', 'FROM' => $activityJoin);
					$result['ACTIVITY_SUBJECT'] = array('FIELD' => 'A.SUBJECT', 'TYPE' => 'string', 'FROM' => $activityJoin);
					$result['ACTIVITY_TYPE_ID'] = array('FIELD' => 'A.TYPE_ID', 'TYPE' => 'int', 'FROM' => $activityJoin);
					$result['ACTIVITY_PROVIDER_ID'] = array('FIELD' => 'A.PROVIDER_ID', 'TYPE' => 'string', 'FROM' => $activityJoin);
				}
			}

			if (in_array('RECURRING', $additionalFields, true))
			{
				$recurringJoin = "LEFT JOIN b_crm_deal_recur DR ON DR.DEAL_ID = L.ID";
				$result['CRM_DEAL_RECURRING_ACTIVE'] = array('FIELD' => 'DR.ACTIVE', 'TYPE' => 'string', 'FROM' => $recurringJoin);
				$result['CRM_DEAL_RECURRING_COUNTER_REPEAT'] = array('FIELD' => 'DR.COUNTER_REPEAT', 'TYPE' => 'int', 'FROM' => $recurringJoin);
				$result['CRM_DEAL_RECURRING_NEXT_EXECUTION'] = array('FIELD' => 'DR.NEXT_EXECUTION', 'TYPE' => 'date', 'FROM' => $recurringJoin);
				$result['CRM_DEAL_RECURRING_START_DATE'] = array('FIELD' => 'DR.START_DATE', 'TYPE' => 'date', 'FROM' => $recurringJoin);
				$result['CRM_DEAL_RECURRING_LIMIT_DATE'] = array('FIELD' => 'DR.LIMIT_DATE', 'TYPE' => 'date', 'FROM' => $recurringJoin);
				$result['CRM_DEAL_RECURRING_LIMIT_REPEAT'] = array('FIELD' => 'DR.LIMIT_REPEAT', 'TYPE' => 'int', 'FROM' => $recurringJoin);
			}
		}

		$result = array_merge(
			$result,
			self::prepareClientFields(
				CCrmContact::GetFields([
					'TABLE_ALIAS' => 'C',
					'ADD_FIELD_ALIASES' => false,
				]),
				'CONTACT_',
				$contactJoin
			)
		);

		$result = array_merge(
			$result,
			self::prepareClientFields(
				CCrmCompany::GetFields([
					'TABLE_ALIAS' => 'CO',
					'ADD_FIELD_ALIASES' => false,
				]),
				'COMPANY_',
				$companyJoin
			)
		);

		// add utm fields
		$result = array_merge($result, UtmTable::getFieldsDescriptionByEntityTypeId(CCrmOwnerType::Deal));
		$result = array_merge(
			$result,
			Container::getInstance()->getParentFieldManager()->getParentFieldsSqlInfo(
				CCrmOwnerType::Deal,
				'L'
			)
		);

		// add uf fields
		if (
			isset($arOptions['UF_FIELDS']) &&
			is_array($arOptions['UF_FIELDS'])
		)
		{
			foreach ($arOptions['UF_FIELDS'] as $ufField)
			{
				if (
					isset($ufField['FIELD']) &&
					isset($ufField['TYPE']) &&
					is_string($ufField['FIELD']) &&
					is_string($ufField['TYPE'])
				)
				{
					$result[$ufField['FIELD']] = [
						'FIELD' => 'UF.' . $ufField['FIELD'],
						'TYPE' => $ufField['TYPE'],
						'FROM' => 'LEFT JOIN b_uts_crm_deal UF ON L.ID = UF.VALUE_ID'
					];
				}

			}
		}

		$result += self::getLastActivityAdapter()->getFields();

		return $result;
	}

	private static function prepareClientFields(array $fields, string $fieldPrefix, string $joinSql): array
	{
		$result = [];
		foreach ($fields as $fieldId => $fieldParams)
		{
			if ($fieldId === 'ID')
			{
				continue;
			}
			if (isset($fieldParams['FROM']) && !empty($fieldParams['FROM']))
			{
				continue;
			}

			$newFieldId = mb_strpos($fieldId, $fieldPrefix) === 0 ? $fieldId : ($fieldPrefix . $fieldId);

			$fieldParams['FROM'] = $joinSql;
			$result[$newFieldId] = $fieldParams;
		}

		return $result;
	}

	public static function __AfterPrepareSql(/*CCrmEntityListBuilder*/ $sender, $arOrder, $arFilter, $arGroupBy, $arSelectFields)
	{
		$sqlData = [
			'SELECT' => [],
			'FROM' => [],
			'WHERE' => [],
			'ORDERBY' => [],
		];
		if (isset($arFilter['SEARCH_CONTENT']) && $arFilter['SEARCH_CONTENT'] !== '')
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
					CCrmOwnerType::Deal,
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

		if (!empty($arFilter['ACTIVE_TIME_PERIOD_from']) || !empty($arFilter['%STAGE_ID_FROM_HISTORY']) || !empty($arFilter['%STAGE_ID_FROM_SUPPOSED_HISTORY']) || !empty($arFilter['%STAGE_SEMANTIC_ID_FROM_HISTORY']))
		{
			global $DB;

			$supposedHistoryConditions = [];

			if (!empty($arFilter['ACTIVE_TIME_PERIOD_from']) && !empty($arFilter['ACTIVE_TIME_PERIOD_to']))
			{
				$supposedHistoryConditions[] = "DSHWS.LAST_UPDATE_DATE <= " . $DB->CharToDateFunction($arFilter['ACTIVE_TIME_PERIOD_to'], 'SHORT');
				$supposedHistoryConditions[] = "DSHWS.CLOSE_DATE >= " . $DB->CharToDateFunction($arFilter['ACTIVE_TIME_PERIOD_from'], 'SHORT');
			}

			if (!empty($arFilter['%STAGE_SEMANTIC_ID_FROM_HISTORY']))
			{
				$stageSemanticIdsFromFilter = is_array($arFilter['%STAGE_SEMANTIC_ID_FROM_HISTORY']) ? $arFilter['%STAGE_SEMANTIC_ID_FROM_HISTORY'] : array($arFilter['%STAGE_SEMANTIC_ID_FROM_HISTORY']);
				$stageIdsForSql = [];
				foreach ($stageSemanticIdsFromFilter as $value)
				{
					$stageIdsForSql[] = "'" . Application::getConnection()->getSqlHelper()->forSql($value) . "'";
				}
				$supposedHistoryConditions[] = "DSHWS.IS_SUPPOSED = 'N'";
				$supposedHistoryConditions[] = "DSHWS.STAGE_SEMANTIC_ID IN (" . implode(', ', $stageIdsForSql) . ")";
			}

			if (!empty($arFilter['%STAGE_ID_FROM_HISTORY']))
			{
				$statusIdsFromFilter = is_array($arFilter['%STAGE_ID_FROM_HISTORY']) ? $arFilter['%STAGE_ID_FROM_HISTORY'] : array($arFilter['%STAGE_ID_FROM_HISTORY']);
				$statusIdsForSql = [];
				foreach ($statusIdsFromFilter as $value)
				{
					$statusIdsForSql[] = "'" . Application::getConnection()->getSqlHelper()->forSql($value) . "'";
				}
				$supposedHistoryConditions[] = "DSHWS.IS_SUPPOSED = 'N'";
				$supposedHistoryConditions[] = "DSHWS.STAGE_ID  IN (" . implode(',', $statusIdsForSql) . ")";
			}

			if (!empty($arFilter['%STAGE_ID_FROM_SUPPOSED_HISTORY']))
			{
				$statusIdsFromFilter = is_array($arFilter['%STAGE_ID_FROM_SUPPOSED_HISTORY']) ? $arFilter['%STAGE_ID_FROM_SUPPOSED_HISTORY'] : array($arFilter['%STAGE_ID_FROM_SUPPOSED_HISTORY']);
				$statusIdsForSql = [];
				foreach ($statusIdsFromFilter as $value)
				{
					$statusIdsForSql[] = "'" . Application::getConnection()->getSqlHelper()->forSql($value) . "'";
				}
				$supposedHistoryConditions[] = "DSHWS.STAGE_ID IN (" . implode(',', $statusIdsForSql) . ")";
			}

			if(count($supposedHistoryConditions) > 0)
			{
				$sqlData['WHERE'][] = "L.ID IN (SELECT DISTINCT DSHWS.OWNER_ID FROM b_crm_deal_stage_history_with_supposed DSHWS WHERE ". implode(" AND ", $supposedHistoryConditions) ." )";
			}
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
			elseif ($arFilter['CALENDAR_FIELD'] == 'CLOSEDATE')
			{
				$sqlData['WHERE'][] = "L.CLOSEDATE <= ".$DB->CharToDateFunction($arFilter['CALENDAR_DATE_TO'], 'SHORT');
				$sqlData['WHERE'][] = "L.CLOSEDATE >= ".$DB->CharToDateFunction($arFilter['CALENDAR_DATE_FROM'], 'SHORT');
			}
			else
			{
				[$ufId, $ufType, $ufName] = \Bitrix\Crm\Integration\Calendar::parseUserfieldKey($arFilter['CALENDAR_FIELD']);

				if (intval($ufId) > 0 && $ufType == 'resourcebooking' || is_null($ufType))
				{
					// L = b_crm_deal
					$sqlData['FROM'][] = "INNER JOIN b_calendar_resource RBUF ".
						"ON RBUF.PARENT_ID = L.ID".
						" AND RBUF.PARENT_TYPE = 'CRM_DEAL'".
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
		$productId = 0;
		$operationInfo = Crm\UI\Filter\EntityHandler::findFieldOperation('PRODUCT_ROW_PRODUCT_ID', $arFilter);
		if(is_array($operationInfo))
		{
			$productFilter = '';
			$productId = $operationInfo['CONDITION'];
			if (is_array($productId))
			{
				\Bitrix\Main\Type\Collection::normalizeArrayValuesByInt($productId);
				$productIds = implode(',', $productId);
				$productFilter = "and DP.PRODUCT_ID in ({$productIds})";
			}
			else
			{
				$productId = (int)$productId;
				if ($productId > 0)
				{
					$productFilter = "and DP.PRODUCT_ID = {$productId}";
				}
			}
			if (!empty($productFilter) && $operationInfo['OPERATION'] === '=')
			{
				$tableAlias = $sender->GetTableAlias();
				$sqlData['WHERE'][] = "{$tableAlias}.ID IN (
					SELECT DP.OWNER_ID from b_crm_product_row DP
					where DP.OWNER_TYPE = 'D'
					and DP.OWNER_ID = {$tableAlias}.ID
					{$productFilter}
				)";
			}
		}

		$operationInfo = Crm\UI\Filter\EntityHandler::findFieldOperation('IS_PRODUCT_RESERVED', $arFilter);
		if (is_array($operationInfo) && \Bitrix\Main\Loader::includeModule('sale'))
		{
			$productFilter = '';
			if (is_array($productId))
			{
				\Bitrix\Main\Type\Collection::normalizeArrayValuesByInt($productId);
				$productIds = implode(',', $productId);
				$productFilter = "and DP.PRODUCT_ID in ({$productIds})";
			}
			elseif ($productId > 0)
			{
				$productFilter ="AND DP.PRODUCT_ID = {$productId}";
			}

			$inCondition = $operationInfo['CONDITION'] === 'Y' ? 'IN' : 'NOT IN';
			$reserveStoreFilter = '';
			$reserveStoreIdOperationInfo = Crm\UI\Filter\EntityHandler::findFieldOperation('RESERVE_STORE_ID', $arFilter);
			if (is_array($reserveStoreIdOperationInfo))
			{
				$reserveStoreId = (int)$reserveStoreIdOperationInfo['CONDITION'];
				$reserveStoreFilter = "AND BR.STORE_ID = {$reserveStoreId}";
			}
			$tableAlias = $sender->GetTableAlias();
			$sqlData['WHERE'][] = "{$tableAlias}.ID {$inCondition} (
				SELECT DP.OWNER_ID FROM b_crm_product_row DP
				INNER JOIN b_crm_product_reservation_map RM ON RM.PRODUCT_ROW_ID = DP.ID
				INNER JOIN b_sale_basket_reservation BR ON RM.BASKET_RESERVATION_ID = BR.ID
				WHERE DP.OWNER_TYPE = 'D'
				AND BR.QUANTITY > 0
				{$productFilter}
				{$reserveStoreFilter}
			)";
		}

		$operationInfo = Crm\UI\Filter\EntityHandler::findFieldOperation('ASSOCIATED_CONTACT_ID', $arFilter);
		if(is_array($operationInfo))
		{
			if($operationInfo['OPERATION'] === '=')
			{
				$sqlData['FROM'][] = DealContactTable::prepareFilterJoinSql(
					CCrmOwnerType::Contact,
					$operationInfo['CONDITION'],
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

		if (isset($arFilter['OBSERVER_IDS']))
		{
			$observerIds = is_array($arFilter['OBSERVER_IDS']) ? $arFilter['OBSERVER_IDS'] : [];
			$observersFilter = CCrmEntityHelper::prepareObserversFieldFilter(
				CCrmOwnerType::Deal,
				$sender->GetTableAlias(),
				$observerIds
			);
			if (!empty($observersFilter))
			{
				$sqlData['WHERE'][] = $observersFilter;
			}
		}

		$sqlData = array_merge_recursive(
			$sqlData,
			self::getClientUFSqlData(
				$arOrder,
				$arFilter,
				CCrmOwnerType::Contact
			),
			self::getClientUFSqlData(
				$arOrder,
				$arFilter,
				CCrmOwnerType::Company
			),
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
		if(!empty($sqlData['ORDERBY']))
		{
			$result['ORDERBY'] = [
				'SQL' => implode(', ', $sqlData['ORDERBY']),
				'POSITION' => 0,
			];
		}

		return !empty($result) ? $result : false;
	}
	// <-- Service

	public static function GetUserFieldEntityID()
	{
		return self::$sUFEntityID;
	}

	/**
	 * @param bool|string $langId
	 * @param int $valueId
	 * @return array|mixed
	 */
	public static function GetUserFields($langId = false, int $valueId = 0)
	{
		global $USER_FIELD_MANAGER;
		return $USER_FIELD_MANAGER->GetUserFields(self::$sUFEntityID, $valueId, $langId);
	}

	// GetList with navigation support
	public static function GetListEx($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array(), $arOptions = array())
	{
		global $USER;
		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		if(!isset($arOptions['PERMISSION_SQL_TYPE']))
		{
			$arOptions['PERMISSION_SQL_TYPE'] = 'FROM';
			$arOptions['PERMISSION_SQL_UNION'] = 'DISTINCT';
		}

		$checkPermissions = true;
		if(isset($arFilter['CHECK_PERMISSIONS']))
		{
			$checkPermissions = $arFilter['CHECK_PERMISSIONS'] !== 'N';
		}

		if($checkPermissions)
		{
			if (isset($arFilter['ID']) && is_array($arFilter['ID']) && count($arFilter['ID']) > 0)
			{
				$ids = array_filter(array_map('intval', $arFilter['ID']), fn($id) => (int)$id > 0);
				$idsFilteredByPerms = [];

				if (!empty($ids))
				{
					$idsFilteredByPerms = self::filterIdsByReadPermission($ids, $USER->GetID());
				}

				if(empty($idsFilteredByPerms)) // hasn't access to any ID in the filter
				{
					if(is_array($arGroupBy) && count($arGroupBy) == 0)
					{
						return 0;
					}

					$dbResult = new CDBResult();
					$dbResult->InitFromArray(array());
					return $dbResult;
				}

				$arFilter['ID'] = $idsFilteredByPerms;
				$arFilter['CHECK_PERMISSIONS'] = 'N';
			}
			else
			{
				$ID = isset($arFilter['ID']) && is_numeric($arFilter['ID']) ? (int)$arFilter['ID'] : 0;
				if($ID <= 0)
				{
					$ID = isset($arFilter['=ID']) && is_numeric($arFilter['=ID']) ? (int)$arFilter['=ID'] : 0;
				}

				if($ID > 0)
				{
					if(!self::CheckReadPermission($ID))
					{
						if(is_array($arGroupBy) && count($arGroupBy) == 0)
						{
							return 0;
						}

						$dbResult = new CDBResult();
						$dbResult->InitFromArray(array());
						return $dbResult;
					}

					$arFilter['CHECK_PERMISSIONS'] = 'N';
				}
			}
		}

		if (
			!isset($arFilter['IS_RECURRING'])
			&& !isset($arFilter['=IS_RECURRING'])
			&& !(isset($arFilter['ID']) || isset($arFilter['=ID']) || isset($arFilter['@ID']))
		)
		{
			if(!isset($arFilter['LOGIC']) || $arFilter['LOGIC'] === 'AND')
			{
				$arFilter['=IS_RECURRING'] = 'N';
			}
			else
			{
				unset($arFilter['CHECK_PERMISSIONS']);

				$arFilter = array(
					'__INNER_FILTER' => $arFilter,
					'=IS_RECURRING' => 'N'
				);

				if(!$checkPermissions)
				{
					$arFilter['CHECK_PERMISSIONS'] = 'N';
				}
			}
		}

		$operationInfo = Crm\UI\Filter\EntityHandler::findFieldOperation('CATEGORY_ID', $arFilter);
		if(is_array($operationInfo) && in_array($operationInfo['OPERATION'], ['=', 'IN']))
		{
			$categoryIDs = is_array($operationInfo['CONDITION'])
				? $operationInfo['CONDITION'] : array($operationInfo['CONDITION']);

			$entityTypes = array();
			foreach($categoryIDs as $categoryID)
			{
				if($categoryID >= 0)
				{
					$entityTypes[] = DealCategory::convertToPermissionEntityType($categoryID);
				}
			}

			if(!empty($entityTypes))
			{
				$arOptions['RESTRICT_BY_ENTITY_TYPES'] = array_unique($entityTypes);
			}
		}

		if (JoinWithUnionSpecification::getInstance()->isSatisfiedBy($arFilter))
		{
			// When forming a request for restricting rights, the optimization mode with the use of union was used.
			$arOptions['PERMISSION_BUILDER_OPTION_OBSERVER_JOIN_AS_UNION'] = true;
		}

		$lb = new CCrmEntityListBuilder(
			CCrmDeal::DB_TYPE,
			CCrmDeal::TABLE_NAME,
			self::TABLE_ALIAS,
			self::GetFields(isset($arOptions['FIELD_OPTIONS']) ? $arOptions['FIELD_OPTIONS'] : null),
			self::$sUFEntityID,
			'DEAL',
			array('CCrmDeal', 'BuildPermSql'),
			array('CCrmDeal', '__AfterPrepareSql')
		);

		return $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields, $arOptions);
	}

	public static function CreateListBuilder(array $arFieldOptions = null)
	{
		return new CCrmEntityListBuilder(
			CCrmDeal::DB_TYPE,
			CCrmDeal::TABLE_NAME,
			self::TABLE_ALIAS,
			self::GetFields($arFieldOptions),
			self::$sUFEntityID,
			'DEAL',
			array('CCrmDeal', 'BuildPermSql'),
			array('CCrmDeal', '__AfterPrepareSql')
		);
	}

	/**
	 * @param array $arOrder
	 * @param array $arFilter
	 * @param array $arSelect
	 * @return CDBResult
	 * Obsolete. Always select all record from database. Please use GetListEx instead.
	 */
	public static function GetList($arOrder = Array('DATE_CREATE' => 'DESC'), $arFilter = Array(), $arSelect = Array(), $nPageTop = false)
	{
		global $DB, $USER_FIELD_MANAGER;

		// fields
		$arFields = array(
			'ID' => 'L.ID',
			'COMMENTS' => 'L.COMMENTS',
			'ADDITIONAL_INFO' => 'L.ADDITIONAL_INFO',
			'LOCATION_ID' => 'L.LOCATION_ID',
			'TITLE' => 'L.TITLE',
			'LEAD_ID' => 'L.LEAD_ID',
			'COMPANY_ID' => 'L.COMPANY_ID',
			'COMPANY_TITLE' => 'C.TITLE',
			'CONTACT_ID' => 'L.CONTACT_ID',
			'CONTACT_FULL_NAME' => 'CT.FULL_NAME',
			/*'STATE_ID' => 'L.STATE_ID',*/
			'STAGE_ID' => 'L.STAGE_ID',
			'CLOSED' => 'L.CLOSED',
			'TYPE_ID' => 'L.TYPE_ID',
			'PRODUCT_ID' => 'L.PRODUCT_ID',
			'PROBABILITY' => 'L.PROBABILITY',
			'OPPORTUNITY' => 'L.OPPORTUNITY',
			'IS_MANUAL_OPPORTUNITY' => 'L.IS_MANUAL_OPPORTUNITY',
			'TAX_VALUE' => 'L.TAX_VALUE',
			'CURRENCY_ID' => 'L.CURRENCY_ID',
			'IS_RECURRING' => 'L.IS_RECURRING',
			'OPPORTUNITY_ACCOUNT' => 'L.OPPORTUNITY_ACCOUNT',
			'TAX_VALUE_ACCOUNT' => 'L.TAX_VALUE_ACCOUNT',
			'ACCOUNT_CURRENCY_ID' => 'L.ACCOUNT_CURRENCY_ID',
			'BEGINDATE' => $DB->DateToCharFunction('L.BEGINDATE'),
			'CLOSEDATE' => $DB->DateToCharFunction('L.CLOSEDATE'),
			'EVENT_ID' => 'L.EVENT_ID',
			'EVENT_DATE' => $DB->DateToCharFunction('L.EVENT_DATE'),
			'EVENT_DESCRIPTION' => 'L.EVENT_DESCRIPTION',
			'ASSIGNED_BY' => 'L.ASSIGNED_BY_ID',
			'ASSIGNED_BY_ID' => 'L.ASSIGNED_BY_ID',
			'CREATED_BY' => 'L.CREATED_BY_ID',
			'CREATED_BY_ID' => 'L.CREATED_BY_ID',
			'MODIFY_BY' => 'L.MODIFY_BY_ID',
			'MODIFY_BY_ID' => 'L.MODIFY_BY_ID',
			'DATE_CREATE' => $DB->DateToCharFunction('L.DATE_CREATE'),
			'DATE_MODIFY' => $DB->DateToCharFunction('L.DATE_MODIFY'),
			'OPENED' => 'L.OPENED',
			'EXCH_RATE' => 'L.EXCH_RATE',
			'ORIGINATOR_ID' => 'L.ORIGINATOR_ID', //EXTERNAL SYSTEM THAT OWNS THIS ITEM
			'ORIGIN_ID' => 'L.ORIGIN_ID', //ITEM ID IN EXTERNAL SYSTEM
			'ORDER_STAGE' => 'L.ORDER_STAGE',
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

		if (
			!isset($arFilter['IS_RECURRING'])
			&& !isset($arFilter['=IS_RECURRING'])
			&& !(isset($arFilter['ID']) || isset($arFilter['=ID']) || isset($arFilter['@ID']))
		)
		{
			$arFilter['=IS_RECURRING'] = 'N';
		}

		if (in_array('ASSIGNED_BY_LOGIN', $arFilterField) || in_array('ASSIGNED_BY', $arFilterField))
		{
			$arSelect[] = 'ASSIGNED_BY_LOGIN';
			$arSelect[] = 'ASSIGNED_BY_NAME';
			$arSelect[] = 'ASSIGNED_BY_LAST_NAME';
			$arSelect[] = 'ASSIGNED_BY_SECOND_NAME';
			$sSqlJoin .= ' LEFT JOIN b_user U ON L.ASSIGNED_BY_ID = U.ID ';
		}
		if (in_array('CREATED_BY_LOGIN', $arFilterField))
		{
			$arSelect[] = 'CREATED_BY';
			$arSelect[] = 'CREATED_BY_LOGIN';
			$arSelect[] = 'CREATED_BY_NAME';
			$arSelect[] = 'CREATED_BY_LAST_NAME';
			$arSelect[] = 'CREATED_BY_SECOND_NAME';
			$sSqlJoin .= ' LEFT JOIN b_user U2 ON L.CREATED_BY_ID = U2.ID ';
		}
		if (in_array('MODIFY_BY_LOGIN', $arFilterField))
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
		if (in_array('CONTACT_ID', $arFilterField) || in_array('CONTACT_FULL_NAME', $arFilterField))
		{
			$arSelect[] = 'CONTACT_ID';
			$arSelect[] = 'CONTACT_FULL_NAME';
			$sSqlJoin .= ' LEFT JOIN b_crm_contact CT ON L.CONTACT_ID = CT.ID ';
		}


		foreach($arSelect as $field)
		{
			$field = mb_strtoupper($field);
			if (array_key_exists($field, $arFields))
				$arSqlSelect[$field] = $arFields[$field].($field != '*' ? ' AS '.$field : '');
		}

		if (!isset($arSqlSelect['ID']))
			$arSqlSelect['ID'] = $arFields['ID'];
		$sSqlSelect = implode(",\n", $arSqlSelect);

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
			'CONTACT_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.CONTACT_ID',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'CONTACT_FULL_NAME' => array(
				'TABLE_ALIAS' => 'CT',
				'FIELD_NAME' => 'CT.FULL_NAME',
				'FIELD_TYPE' => 'string',
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
			'STATE_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.STATE_ID',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'STAGE_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.STAGE_ID',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'TYPE_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.TYPE_ID',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'PRODUCT_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.PRODUCT_ID',
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
			'TAX_VALUE' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.TAX_VALUE',
				'FIELD_TYPE' => 'int',
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
			'TAX_VALUE_ACCOUNT' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.TAX_VALUE_ACCOUNT',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'TITLE' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.TITLE',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'CLOSED' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.CLOSED',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'COMMENTS' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.COMMENTS',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'ADDITIONAL_INFO' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.ADDITIONAL_INFO',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'LOCATION_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.LOCATION_ID',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'IS_RECURRING' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.IS_RECURRING',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'DATE_CREATE' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.DATE_CREATE',
				'FIELD_TYPE' => 'datetime',
				'JOIN' => false
			),
			'BEGINDATE' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.BEGINDATE',
				'FIELD_TYPE' => 'datetime',
				'JOIN' => false
			),
			'CLOSEDATE' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.CLOSEDATE',
				'FIELD_TYPE' => 'datetime',
				'JOIN' => false
			),
			'EVENT_DATE' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.EVENT_DATE',
				'FIELD_TYPE' => 'datetime',
				'JOIN' => false
			),
			'DATE_MODIFY' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.DATE_MODIFY',
				'FIELD_TYPE' => 'datetime',
				'JOIN' => false
			),
			'PROBABILITY' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.PROBABILITY',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'EVENT_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.EVENT_ID',
				'FIELD_TYPE' => 'string',
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
			)
		);

		$obQueryWhere->SetFields($arWhereFields);
		if (!is_array($arFilter))
			$arFilter = array();
		$sQueryWhereFields = $obQueryWhere->GetQuery($arFilter);

		$sSqlSearch = '';
		foreach($arSqlSearch as $r)
			if ($r <> '')
				$sSqlSearch .= "\n\t\t\t\tAND  ($r) ";
		$CCrmUserType = new CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], self::$sUFEntityID);
		$CCrmUserType->ListPrepareFilter($arFilter);
		$r = $obUserFieldsSql->GetFilter();
		if ($r <> '')
			$sSqlSearch .= "\n\t\t\t\tAND ($r) ";

		if (!empty($sQueryWhereFields))
			$sSqlSearch .= "\n\t\t\t\tAND ($sQueryWhereFields) ";

		$arFieldsOrder = array(
			'ASSIGNED_BY' => 'L.ASSIGNED_BY_ID',
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
				b_crm_deal L $sSqlJoin
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

		$obRes = $DB->Query($sSql);
		$obRes->SetUserFields($USER_FIELD_MANAGER->GetUserFields(self::$sUFEntityID));
		return $obRes;
	}

	public static function GetByID($ID, $bCheckPerms = true)
	{
		if(!is_int($ID))
		{
			$ID = (int)$ID;
		}

		if($bCheckPerms && !self::CheckReadPermission($ID))
		{
			return null;
		}

		$dbRes = self::GetListEx(
			array(),
			array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N')
		);
		return $dbRes->Fetch();
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

		return \Bitrix\Crm\Entity\Deal::getInstance()->getTopIDs([
			'order' => ['ID' => $sortType],
			'limit' => $top,
			'userPermissions' => $userPermissions
		]);
	}

	public static function GetTotalCount()
	{
		if(defined('BX_COMP_MANAGED_CACHE') && $GLOBALS['CACHE_MANAGER']->Read(self::CACHE_TTL, self::TOTAL_COUNT_CACHE_ID, 'b_crm_deal'))
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

	static public function BuildPermSql($sAliasPrefix = 'L', $mPermType = 'READ', $arOptions = [])
	{
		$allowSkipCheckOtherEntityTypes = false;
		if(isset($arOptions['RESTRICT_BY_ENTITY_TYPES'])
			&& is_array($arOptions['RESTRICT_BY_ENTITY_TYPES'])
			&& !empty($arOptions['RESTRICT_BY_ENTITY_TYPES'])
		)
		{
			$entityTypes = $arOptions['RESTRICT_BY_ENTITY_TYPES'];
			$allowSkipCheckOtherEntityTypes = true;
		}
		else
		{
			$entityTypes = array_merge(['DEAL'], DealCategory::getPermissionEntityTypeList());
		}

		$userId = null;
		if (isset($arOptions['PERMS']) && is_object($arOptions['PERMS']))
		{
			/** @var \CCrmPerms $arOptions['PERMS'] */
			$userId = $arOptions['PERMS']->GetUserID();
		}
		$builderOptions = OptionsBuilder::makeFromArray((array)$arOptions)
			->setOperations((array)$mPermType)
			->setAliasPrefix((string)$sAliasPrefix)
			->setSkipCheckOtherEntityTypes($allowSkipCheckOtherEntityTypes)
			->build()
		;

		$queryBuilder = Container::getInstance()
			->getUserPermissions($userId)
			->createListQueryBuilder($entityTypes, $builderOptions)
		;

		return $queryBuilder->buildCompatible();
	}

	public function Add(array &$arFields, $bUpdateSearch = true, $options = array())
	{
		global $DB;

		if (!is_array($options))
		{
			$options = array();
		}

		$this->LAST_ERROR = '';
		$this->checkExceptions = array();

		$isRestoration = isset($options['IS_RESTORATION']) && $options['IS_RESTORATION'];

		if ($this->isUseOperation())
		{
			return $this->getCompatibilityAdapter()->performAdd($arFields, $options);
		}

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
		$arFields['CREATED_BY_ID'] = (int)($arFields['CREATED_BY_ID'] ?? 0);
		$arFields['MODIFY_BY_ID'] = (int)($arFields['MODIFY_BY_ID'] ?? 0);
		$arFields['ASSIGNED_BY_ID'] = (int)($arFields['ASSIGNED_BY_ID'] ?? 0);

		if(!isset($arFields['TITLE']) || !is_string($arFields['TITLE']) || trim($arFields['TITLE']) === '')
		{
			$arFields['TITLE'] = self::GetDefaultTitle();
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
			if (!isset($arFields['IS_RECURRING']))
			{
				$arFields['IS_RECURRING'] = 'N';
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
			self::getCommentsAdapter()->normalizeFields(null, $arFields);

			//region Category
			$categoryID = isset($arFields['CATEGORY_ID']) ? max((int)$arFields['CATEGORY_ID'], 0) : 0;
			if($categoryID > 0 && !DealCategory::isEnabled($categoryID))
			{
				$categoryID = 0;
			}
			$arFields['CATEGORY_ID'] = $categoryID;
			//endregion

			//region StageID, SemanticID and IsNew
			$permissionTypeId = (
				$this->bCheckPermission
					? Bitrix\Crm\Security\EntityPermissionType::CREATE
					: Bitrix\Crm\Security\EntityPermissionType::UNDEFINED
			);

			$arFields['STAGE_ID'] ??= '';

			$viewMode = ($options['ITEM_OPTIONS']['VIEW_MODE'] ?? null);

			if (
				( empty($arFields['STAGE_ID']) || !self::IsStageExists($arFields['STAGE_ID'], $categoryID) )
				&& $viewMode !== ViewMode::MODE_ACTIVITIES
			)
			{
				$arFields['STAGE_ID'] = self::GetStartStageID($categoryID, $permissionTypeId);
			}

			$viewModeActivitiesStageId = null;
			if ($viewMode === ViewMode::MODE_ACTIVITIES)
			{
				$viewModeActivitiesStageId = $arFields['STAGE_ID'];
				$arFields['STAGE_ID'] = self::GetStartStageID($categoryID, $permissionTypeId);
			}

			$isStageExist = self::IsStageExists($arFields['STAGE_ID'], $categoryID);
			$arFields['STAGE_SEMANTIC_ID'] = $isStageExist
				? self::GetSemanticID($arFields['STAGE_ID'], $categoryID)
				: Bitrix\Crm\PhaseSemantics::UNDEFINED
			;

			$arFields['IS_NEW'] = $arFields['STAGE_ID'] === self::GetStartStageID($categoryID) ? 'Y' : 'N';
			//endregion

			$observerIDs = isset($arFields['OBSERVER_IDS']) && is_array($arFields['OBSERVER_IDS'])
				? $arFields['OBSERVER_IDS'] : null;
			unset($arFields['OBSERVER_IDS']);

			$arAttr = array();
			if (!empty($arFields['STAGE_ID']))
			{
				$arAttr['STAGE_ID'] = $arFields['STAGE_ID'];
			}
			if (!empty($arFields['OPENED']))
			{
				$arAttr['OPENED'] = $arFields['OPENED'];
			}
			if(!empty($observerIDs))
			{
				$arAttr['CONCERNED_USER_IDS'] = $observerIDs;
			}

			$permissionEntityType = DealCategory::convertToPermissionEntityType($arFields['CATEGORY_ID']);
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
				$sEntityPerm = $userPerms->GetPermType($permissionEntityType, $sPermission, $arEntityAttr);
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
			$sEntityPerm = $userPerms->GetPermType($permissionEntityType, $sPermission, $arEntityAttr);
			self::PrepareEntityAttrs($arEntityAttr, $sEntityPerm);

			//Prepare currency & exchange rate
			if(!isset($arFields['CURRENCY_ID']))
			{
				$arFields['CURRENCY_ID'] = CCrmCurrency::GetBaseCurrencyID();
			}

			if(!isset($arFields['EXCH_RATE']))
			{
				$arFields['EXCH_RATE'] = CCrmCurrency::GetExchangeRate($arFields['CURRENCY_ID']);
			}

			$arFields = array_merge($arFields, \CCrmAccountingHelper::calculateAccountingData($arFields, [], true));

			//Scavenging
			if(isset($arFields['BEGINDATE']) && (!is_string($arFields['BEGINDATE']) || trim($arFields['BEGINDATE']) === ''))
			{
				unset($arFields['BEGINDATE']);
			}

			if(isset($arFields['CLOSEDATE']) && (!is_string($arFields['CLOSEDATE']) || trim($arFields['CLOSEDATE']) === ''))
			{
				unset($arFields['CLOSEDATE']);
			}

			$currentDate = ConvertTimeStamp(time() + \CTimeZone::GetOffset(), 'SHORT', SITE_ID);
			$arFields['~BEGINDATE'] = $DB->CharToDateFunction(
				isset($arFields['BEGINDATE']) ? $arFields['BEGINDATE'] : $currentDate,
				'SHORT',
				false
			);

			if(isset($arFields['BEGINDATE']))
			{
				$arFields['__BEGINDATE'] = $arFields['BEGINDATE'];
				unset($arFields['BEGINDATE']);
			}

			$isFinalStage = self::GetStageSemantics($arFields['STAGE_ID'], $categoryID) !== 'process';
			$enableCloseDateSync = DealSettings::getCurrent()->isCloseDateSyncEnabled();
			if(isset($options['ENABLE_CLOSE_DATE_SYNC']) && is_bool($options['ENABLE_CLOSE_DATE_SYNC']))
			{
				$enableCloseDateSync = $options['ENABLE_CLOSE_DATE_SYNC'];
			}

			$arFields['CLOSED'] = $isFinalStage ? 'Y' : 'N';
			if($enableCloseDateSync && $isFinalStage)
			{
				$arFields['CLOSEDATE'] = $currentDate;
				$arFields['~CLOSEDATE'] = $DB->CharToDateFunction($currentDate, 'SHORT', false);
			}
			elseif($isFinalStage && !isset($arFields['CLOSEDATE']))
			{
				$arFields['CLOSEDATE'] = $currentDate;
				$arFields['~CLOSEDATE'] = $DB->CharToDateFunction($currentDate, 'SHORT', false);
			}
			elseif(isset($arFields['CLOSEDATE']))
			{
				$arFields['~CLOSEDATE'] = $DB->CharToDateFunction($arFields['CLOSEDATE'], 'SHORT', false);
			}

			if(isset($arFields['CLOSEDATE']))
			{
				$arFields['__CLOSEDATE'] = $arFields['CLOSEDATE'];
				unset($arFields['CLOSEDATE']);
			}

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

			//region Rise BeforeAdd event
			$beforeEvents = GetModuleEvents('crm', 'OnBeforeCrmDealAdd');
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
						$this->LAST_ERROR = GetMessage('CRM_DEAL_CREATION_CANCELED', array('#NAME#' => $arEvent['TO_NAME']));
						$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
					}
					return false;
				}
			}
			//endregion

			if(!isset($arFields['COMPANY_ID']))
			{
				$arFields['COMPANY_ID'] = 0;
			}

			unset($arFields['ID']);

			$this->normalizeEntityFields($arFields);
			$ID = (int) $DB->Add(self::TABLE_NAME, $arFields, [], 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);

			//Append ID to TITLE if required
			if($ID > 0 && $arFields['TITLE'] === self::GetDefaultTitle())
			{
				$arFields['TITLE'] = self::GetDefaultTitle($ID);
				$sUpdate = $DB->PrepareUpdate('b_crm_deal', array('TITLE' => $arFields['TITLE']));
				if($sUpdate <> '')
				{
					$DB->Query("UPDATE b_crm_deal SET {$sUpdate} WHERE ID = {$ID}");
				};
			}

			if(defined('BX_COMP_MANAGED_CACHE'))
			{
				$GLOBALS['CACHE_MANAGER']->CleanDir('b_crm_deal');
			}

			$result = $arFields['ID'] = $ID;

			self::clearStageCache($ID);

			//Restore BEGINDATE and CLOSEDATE
			if(isset($arFields['__BEGINDATE']))
			{
				$arFields['BEGINDATE'] = $arFields['__BEGINDATE'];
				unset($arFields['__BEGINDATE']);
			}

			if(isset($arFields['__CLOSEDATE']))
			{
				$arFields['CLOSEDATE'] = $arFields['__CLOSEDATE'];
				unset($arFields['__CLOSEDATE']);
			}

			//region User Field
			CCrmEntityHelper::NormalizeUserFields($arFields, self::$sUFEntityID, $GLOBALS['USER_FIELD_MANAGER'], array('IS_NEW' => true));
			$GLOBALS['USER_FIELD_MANAGER']->Update(self::$sUFEntityID, $ID, $arFields);
			//endregion

			//region Save Observers
			if(!empty($observerIDs))
			{
				Crm\Observer\ObserverManager::registerBulk($observerIDs, \CCrmOwnerType::Deal, $ID);
			}
			//endregion

			$securityRegisterOptions = (new \Bitrix\Crm\Security\Controller\RegisterOptions())
				->setEntityAttributes($arEntityAttr)
			;
			Crm\Security\Manager::getEntityController(CCrmOwnerType::Deal)
				->register($permissionEntityType, $ID, $securityRegisterOptions)
			;

			//region Save contacts
			if (!empty($contactBindings))
			{
				DealContactTable::bindContacts($ID, $contactBindings);
			}
			//endregion

			if(!empty($contactBindings))
			{
				$arFields['CONTACT_ID'] = EntityBinding::getPrimaryEntityID(CCrmOwnerType::Contact, $contactBindings);
			}
			self::SynchronizeCustomerData($ID, $arFields);

			//Statistics & History -->
			if ($arFields['IS_RECURRING'] !== 'Y')
			{
				DealSumStatisticEntry::register($ID, $arFields);
				DealInvoiceStatisticEntry::synchronize($ID, $arFields);
				DealStageHistoryEntry::register($ID, $arFields, array('IS_NEW' => true));
			}

			if(isset($arFields['LEAD_ID']) && $arFields['LEAD_ID'] > 0)
			{
				LeadConversionStatisticsEntry::processBindingsChange($arFields['LEAD_ID']);
			}
			//<-- Statistics & History

			if (($options['DISABLE_TIMELINE_CREATION'] ?? null) !== 'Y')
			{
				if($isRestoration)
				{
					Bitrix\Crm\Timeline\DealController::getInstance()->onRestore($ID, array('FIELDS' => $arFields));
				}
				else
				{
					Bitrix\Crm\Timeline\DealController::getInstance()->onCreate(
						$ID,
						array(
							'FIELDS' => $arFields,
							'CONTACT_BINDINGS' => $contactBindings
						)
					);
				}
			}

			$currentStageSemantics = isset($arFields['STAGE_ID'], $arFields['STAGE_SEMANTIC_ID'])
				? Container::getInstance()->getFactory(\CCrmOwnerType::Deal)?->getStageSemantics($arFields['STAGE_ID'])
				: Crm\PhaseSemantics::UNDEFINED
			;

			CCrmEntityHelper::registerAdditionalTimelineEvents([
				'entityTypeId' => \CCrmOwnerType::Deal,
				'entityId' => $ID,
				'fieldsInfo' => static::GetFieldsInfo(),
				'previousFields' => [],
				'currentFields' => $arFields,
				'previousStageSemantics' => Crm\PhaseSemantics::UNDEFINED,
				'currentStageSemantics' => $currentStageSemantics ?? Crm\PhaseSemantics::UNDEFINED,
				'options' => $options,
				'bindings' => [
					'entityTypeId' => \CCrmOwnerType::Contact,
					'previous' => [],
					'current' => $contactBindings,
				]
			]);

			\Bitrix\Crm\Counter\Monitor::getInstance()->onEntityAdd(CCrmOwnerType::Deal, $arFields);

			// tracking of entity
			Tracking\Entity::onAfterAdd(CCrmOwnerType::Deal, $ID, $arFields);
			//region save parent relations
			Container::getInstance()->getParentFieldManager()->saveParentRelationsForIdentifier(
				new Crm\ItemIdentifier(\CCrmOwnerType::Deal, $ID),
				$arFields
			);
			//endregion

			if($bUpdateSearch)
			{
				$arFilterTmp = Array('ID' => $ID);
				if (!$this->bCheckPermission)
					$arFilterTmp["CHECK_PERMISSIONS"] = "N";
				CCrmSearch::UpdateSearch($arFilterTmp, 'DEAL', true);
			}

			//region Search content index
			Bitrix\Crm\Search\SearchContentBuilderFactory::create(
				CCrmOwnerType::Deal
			)->build($ID, ['checkExist' => true]);
			//endregion

			self::getCommentsAdapter()->performAdd($arFields, $options);

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

				$companyID = (int)$arFields['COMPANY_ID'];
				$liveFeedFields = array(
					'USER_ID' => $createdByID,
					'ENTITY_TYPE_ID' => CCrmOwnerType::Deal,
					'ENTITY_ID' => $ID,
					'TITLE' => GetMessage('CRM_DEAL_EVENT_ADD'),
					'MESSAGE' => '',
					'PARAMS' => array(
						'TITLE' => isset($arFields['TITLE']) ? $arFields['TITLE'] : '',
						'STAGE_ID' => isset($arFields['STAGE_ID']) ? $arFields['STAGE_ID'] : '',
						'CATEGORY_ID' => isset($arFields['CATEGORY_ID']) ? $arFields['CATEGORY_ID'] : 0,
						'OPPORTUNITY' => strval($opportunity),
						'CURRENCY_ID' => $currencyID,
						'COMPANY_ID' => $companyID,
						'CONTACT_ID' => isset($arFields['CONTACT_ID']) ? intval($arFields['CONTACT_ID']) : 0,
						'AUTHOR_ID' => intval($arFields['CREATED_BY_ID']),
						'RESPONSIBLE_ID' => $assignedByID
					)
				);

				//Register contact & company relations
				$parents = array();
				if($companyID > 0)
				{
					CCrmLiveFeed::PrepareOwnershipRelations(
						CCrmOwnerType::Company,
						array($companyID),
						$parents
					);
				}

				if(is_array($contactIDs))
				{
					CCrmLiveFeed::PrepareOwnershipRelations(
						CCrmOwnerType::Contact,
						$contactIDs,
						$parents
					);
				}

				if(!empty($parents))
				{
					$liveFeedFields['PARENTS'] = array_values($parents);
				}

				if (Crm\Settings\Crm::isLiveFeedRecordsGenerationEnabled())
				{
					CCrmSonetSubscription::RegisterSubscription(
						CCrmOwnerType::Deal,
						$ID,
						CCrmSonetSubscriptionType::Responsibility,
						$assignedByID
					);
				}

				$logEventID = CCrmLiveFeed::CreateLogEvent($liveFeedFields, CCrmLiveFeedEvent::Add, ['CURRENT_USER' => $userID]);

				if ($logEventID !== false && !$isRestoration)
				{
					$difference = Crm\Comparer\ComparerBase::compareEntityFields([], [
						Item::FIELD_NAME_ID => $ID,
						Item::FIELD_NAME_TITLE => $arFields['TITLE'],
						Item::FIELD_NAME_CREATED_BY => $createdByID,
						Item::FIELD_NAME_ASSIGNED => $assignedByID,
						Item::FIELD_NAME_OBSERVERS => $observerIDs,
					]);

					NotificationManager::getInstance()->sendAllNotificationsAboutAdd(
						CCrmOwnerType::Deal,
						$difference,
					);
				}
			}

			//region Rise AfterAdd event
			$afterEvents = GetModuleEvents('crm', 'OnAfterCrmDealAdd');
			while ($arEvent = $afterEvents->Fetch())
			{
				ExecuteModuleEventEx($arEvent, array(&$arFields));
			}
			//endregion

			if(isset($arFields['ORIGIN_ID']) && $arFields['ORIGIN_ID'] !== '')
			{
				$afterEvents = GetModuleEvents('crm', 'OnAfterExternalCrmDealAdd');
				while ($arEvent = $afterEvents->Fetch())
				{
					ExecuteModuleEventEx($arEvent, array(&$arFields));
				}
			}

			if ($ID>0)
			{
				if (
					$viewMode === ViewMode::MODE_ACTIVITIES
					&& $viewModeActivitiesStageId
				)
				{
					$deadline = (new Crm\Kanban\EntityActivityDeadline())->getDeadline($viewModeActivitiesStageId);

					if ($deadline)
					{
						(new Entity\ToDo(new Crm\ItemIdentifier(\CCrmOwnerType::Deal, $ID), new ToDo\ToDo()))
							->createWithDefaultSubjectAndDescription($deadline);
					}
				}

				$item = Crm\Kanban\Entity::getInstance(self::$TYPE_NAME)
					->createPullItem($arFields);

				PullManager::getInstance()->sendItemAddedEvent(
					$item,
					[
						'TYPE' => self::$TYPE_NAME,
						'CATEGORY_ID' => \CCrmDeal::GetCategoryID($ID),
						'SKIP_CURRENT_USER' => ($userID !== 0),
					]
				);
			}

		}

		return $result;
	}

	public function CheckFields(&$arFields, $ID = false, $options = array())
	{
		global $APPLICATION, $USER_FIELD_MANAGER;
		$this->LAST_ERROR = '';
		$this->checkExceptions = array();

		if (!empty($arFields['BEGINDATE']) && !CheckDateTime($arFields['BEGINDATE']))
		{
			$this->LAST_ERROR .=
				GetMessage('CRM_ERROR_FIELD_INCORRECT', ['%FIELD_NAME%' => GetMessage('CRM_FIELD_BEGINDATE')]) . "<br />\n"
			;
		}

		if (!empty($arFields['CLOSEDATE']) && !CheckDateTime($arFields['CLOSEDATE']))
		{
			$this->LAST_ERROR .=
				GetMessage('CRM_ERROR_FIELD_INCORRECT', ['%FIELD_NAME%' => GetMessage('CRM_FIELD_CLOSEDATE')]) . "<br />\n"
			;
		}

		if (!empty($arFields['EVENT_DATE']) && !CheckDateTime($arFields['EVENT_DATE']))
		{
			$this->LAST_ERROR .=
				GetMessage('CRM_ERROR_FIELD_INCORRECT', ['%FIELD_NAME%' => GetMessage('CRM_FIELD_EVENT_DATE')]) . "<br />\n"
			;
		}

		if (($ID == false || isset($arFields['TITLE'])) && empty($arFields['TITLE']))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_IS_MISSING', array('%FIELD_NAME%' => GetMessage('CRM_DEAL_FIELD_TITLE')))."<br />\n";

		if(
			isset($arFields['OPPORTUNITY']) &&
			is_string($arFields['OPPORTUNITY']) &&
			$arFields['OPPORTUNITY'] !== ''
		)
		{
			$arFields['OPPORTUNITY'] = str_replace(array(',', ' '), array('.', ''), $arFields['OPPORTUNITY']);
			//HACK: MSSQL returns '.00' for zero value
			if(mb_strpos($arFields['OPPORTUNITY'], '.') === 0)
			{
				$arFields['OPPORTUNITY'] = '0'.$arFields['OPPORTUNITY'];
			}

			if (!preg_match('/^-?\d{1,}(\.\d{1,})?$/', $arFields['OPPORTUNITY']))
			{
				$this->LAST_ERROR .= GetMessage('CRM_DEAL_FIELD_OPPORTUNITY_INVALID')."<br />\n";
			}
		}

		if (!empty($arFields['PROBABILITY']))
		{
			$arFields['PROBABILITY'] = intval($arFields['PROBABILITY']);
			if ($arFields['PROBABILITY'] > 100)
				$arFields['PROBABILITY'] = 100;
		}


		if (!is_array($options))
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

					if(!array_key_exists('OBSERVER_IDS', $currentFields))
					{
						$currentFields['OBSERVER_IDS'] = Crm\Observer\ObserverManager::getEntityObserverIDs(
							\CCrmOwnerType::Deal,
							$ID
						);
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

					//If Stage ID is changed we must perform check of all fields.
					if(isset($arFields['STAGE_ID']) && $arFields['STAGE_ID'] !== $currentFields['STAGE_ID'])
					{
						$fieldsToCheck = array_merge($currentFields, $arFields);
						if(self::GetSemanticID($arFields['STAGE_ID'], $currentFields['CATEGORY_ID']) === Bitrix\Crm\PhaseSemantics::FAILURE)
						{
							//Disable required fields check for failure stage due to backward compatibility.
							$enableRequiredUserFieldCheck = false;
						}
					}
					elseif(!isset($arFields['STAGE_ID']) && isset($currentFields['STAGE_ID']))
					{
						$fieldsToCheck = array_merge($arFields, array('STAGE_ID' => $currentFields['STAGE_ID']));
					}
				}

				$requiredFields = Crm\Attribute\FieldAttributeManager::getRequiredFields(
					CCrmOwnerType::Deal,
					$ID,
					$fieldsToCheck,
					Crm\Attribute\FieldOrigin::UNDEFINED,
					(is_array($options['FIELD_CHECK_OPTIONS'] ?? null) ? $options['FIELD_CHECK_OPTIONS'] : [])
				);

				$requiredSystemFields = isset($requiredFields[Crm\Attribute\FieldOrigin::SYSTEM])
					? $requiredFields[Crm\Attribute\FieldOrigin::SYSTEM] : array();
				if(!empty($requiredSystemFields))
				{
					$validator = new Crm\Entity\DealValidator($ID, $fieldsToCheck);
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
		$notAccessibleFields = VisibilityManager::getNotAccessibleFields(CCrmOwnerType::Deal);
		return array_diff($fields, $notAccessibleFields);
	}

	public function GetCheckExceptions()
	{
		return $this->checkExceptions;
	}

	static public function BuildEntityAttr($userID, $arAttr = array())
	{
		$userID = (int)$userID;
		$arResult = array("U{$userID}");
		if(isset($arAttr['OPENED']) && $arAttr['OPENED'] == 'Y')
		{
			$arResult[] = 'O';
		}

		$stageID = isset($arAttr['STAGE_ID']) ? $arAttr['STAGE_ID'] : '';
		if($stageID !== '')
		{
			$arResult[] = "STAGE_ID{$stageID}";
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
			$IDs = [$IDs];
		}

		$dbResult = self::GetListEx(
			[],
			[
				'@ID' => $IDs,
				'CHECK_PERMISSIONS' => 'N',
			],
			false,
			false,
			[
				'ID',
				'ASSIGNED_BY_ID',
				'OPENED',
				'STAGE_ID',
				'CATEGORY_ID',
			]
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

			if(isset($fields['STAGE_ID']))
			{
				$attrs['STAGE_ID'] = $fields['STAGE_ID'];
			}

			$entityAttrs = self::BuildEntityAttr($assignedByID, $attrs);
			$securityRegisterOptions = (new \Bitrix\Crm\Security\Controller\RegisterOptions())
				->setEntityAttributes($entityAttrs)
				->setEntityFields($fields)
			;
			Crm\Security\Manager::getEntityController(CCrmOwnerType::Deal)
				->register(
					DealCategory::convertToPermissionEntityType(
						isset($fields['CATEGORY_ID']) ? (int)$fields['CATEGORY_ID'] : 0
					),
					$ID,
					$securityRegisterOptions
				)
			;
		}
	}

	static private function PrepareEntityAttrs(&$arEntityAttr, $entityPermType)
	{
		// Ensure that entity accessable for user restricted by BX_CRM_PERM_OPEN
		if($entityPermType === BX_CRM_PERM_OPEN && !in_array('O', $arEntityAttr, true))
		{
			$arEntityAttr[] = 'O';
		}
	}

	static protected function SynchronizeCustomerData($sourceID, array $fields, array $options = null)
	{
		if(!is_array($options))
		{
			$options = array();
		}

		$companyID = isset($fields['COMPANY_ID']) ? (int)$fields['COMPANY_ID'] : 0;
		$contactID = isset($fields['CONTACT_ID']) ? (int)$fields['CONTACT_ID'] : 0;

		$enableSource = !isset($options['ENABLE_SOURCE']) || $options['ENABLE_SOURCE'] === true;
		$connection = Application::getInstance()->getConnection();

		//region REPEATED APPROACH
		if($enableSource)
		{
			$isRepeatedApproach = false;
			if(isset($fields['LEAD_ID'])
				&& $fields['LEAD_ID'] > 0
				&& \CCrmLead::GetCustomerType($fields['LEAD_ID']) === CustomerType::RETURNING
				&& ($companyID > 0 || $contactID > 0)
			)
			{
				if($companyID > 0)
				{
					$resultData = self::queryMinDealIdWithCache(
						"SELECT ID, IS_RETURN_CUSTOMER FROM b_crm_deal WHERE COMPANY_ID = {$companyID} AND STAGE_SEMANTIC_ID = 'S' ORDER BY ID ASC LIMIT 1",
						$connection,
					);
				}
				else//if($contactID > 0)
				{
					$resultData = self::queryMinDealIdWithCache(
						"SELECT ID, IS_RETURN_CUSTOMER FROM b_crm_deal WHERE CONTACT_ID = {$contactID} AND COMPANY_ID <= 0 AND STAGE_SEMANTIC_ID = 'S' ORDER BY ID ASC LIMIT 1",
						$connection,
					);
				}

				$primaryID = is_array($resultData) && isset($resultData['ID']) ? (int)$resultData['ID'] : 0;
				$isRepeatedApproach = ($primaryID === 0);
			}

			$flag = $isRepeatedApproach ? 'Y' : 'N';
			$connection->queryExecute("UPDATE b_crm_deal SET IS_REPEATED_APPROACH = '{$flag}' WHERE ID = {$sourceID}");
		}
		//endregion

		//region RETURN CUSTOMER
		if($companyID > 0 || $contactID > 0)
		{
			if($companyID > 0)
			{
				$resultData = self::queryMinDealIdWithCache(
					"SELECT ID FROM b_crm_deal WHERE COMPANY_ID = {$companyID} AND STAGE_SEMANTIC_ID = 'S' ORDER BY ID ASC LIMIT 1",
					$connection,
				);
			}
			else//if($contactID > 0)
			{
				$resultData = self::queryMinDealIdWithCache(
					"SELECT ID FROM b_crm_deal WHERE CONTACT_ID = {$contactID} AND COMPANY_ID <= 0 AND STAGE_SEMANTIC_ID = 'S' ORDER BY ID ASC LIMIT 1",
					$connection,
				);
			}

			$primaryID = is_array($resultData) && isset($resultData['ID']) ? (int)$resultData['ID'] : 0;
			if($primaryID > 0)
			{
				if($companyID > 0)
				{
					$connection->queryExecute(
						"UPDATE b_crm_deal SET IS_RETURN_CUSTOMER = 'Y', IS_REPEATED_APPROACH = 'N' WHERE IS_RETURN_CUSTOMER = 'N' AND COMPANY_ID = {$companyID}"
					);
				}
				elseif($contactID > 0)
				{
					$connection->queryExecute(
						"UPDATE b_crm_deal SET IS_RETURN_CUSTOMER = 'Y', IS_REPEATED_APPROACH = 'N' WHERE IS_RETURN_CUSTOMER = 'N' AND CONTACT_ID = {$contactID} AND coalesce(COMPANY_ID, 0) = 0"
					);
				}

				$currentIsReturnCustomer = $connection->query("SELECT IS_RETURN_CUSTOMER from b_crm_deal WHERE ID = {$primaryID}")->fetch()['IS_RETURN_CUSTOMER'] ?? null;
				if ($currentIsReturnCustomer !== 'N')
				{
					$connection->queryExecute("UPDATE b_crm_deal SET IS_RETURN_CUSTOMER = 'N' WHERE ID = {$primaryID}");
				}
			}
		}
		elseif($enableSource && $fields['IS_RETURN_CUSTOMER'] !== 'N')
		{
			$connection->queryExecute("UPDATE b_crm_deal SET IS_RETURN_CUSTOMER = 'N' WHERE ID = {$sourceID}");
		}

		Container::getInstance()->getDealBroker()?->resetAllCache();
		//endregion
	}

	private static function queryMinDealIdWithCache(string $sql, \Bitrix\Main\DB\Connection $connection)
	{
		static $cache = [];
		$hash = hash('crc32b', $sql);

		if (isset($cache[$hash]))
		{
			return $cache[$hash];
		}

		$dbResult = $connection->query($sql);
		$resultData = $dbResult->fetch();

		$cache[$hash] = $resultData;

		return $resultData;
	}

	public function Update($ID, array &$arFields, $bCompare = true, $bUpdateSearch = true, $options = array())
	{
		global $DB;

		$ID = (int) $ID;
		if(!is_array($options))
		{
			$options = array();
		}
		$options['IS_COMPARE_ENABLED'] = $bCompare;
		$isSystemAction = isset($options['IS_SYSTEM_ACTION']) && $options['IS_SYSTEM_ACTION'];

		$this->LAST_ERROR = '';
		$this->checkExceptions = array();

		if ($this->isUseOperation())
		{
			return $this->getCompatibilityAdapter()->performUpdate($ID, $arFields, $options);
		}

		if(isset($options['CURRENT_USER']))
		{
			$userID = intval($options['CURRENT_USER']);
		}
		else
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		$arFilterTmp = array('ID' => $ID);
		if (!$this->bCheckPermission)
			$arFilterTmp['CHECK_PERMISSIONS'] = 'N';

		$obRes = self::GetListEx(array(), $arFilterTmp, false, false, array('*', 'UF_*'));
		if (!($arRow = $obRes->Fetch()))
			return false;

		unset(
			$arFields['DATE_CREATE'],
			$arFields['DATE_MODIFY'],
			$arFields['CATEGORY_ID'],
			$arFields['MOVED_BY_ID'],
			$arFields['MOVED_TIME']
		);

		if(!$isSystemAction)
		{
			$arFields['~DATE_MODIFY'] = $DB->CurrentTimeFunction();
			if(!isset($arFields['MODIFY_BY_ID']) || $arFields['MODIFY_BY_ID'] <= 0)
			{
				$arFields['MODIFY_BY_ID'] = $userID;
			}
		}

		if(isset($arFields['TITLE']) && (!is_scalar($arFields['TITLE']) || trim($arFields['TITLE']) === ''))
		{
			unset($arFields['TITLE']);
		}

		//Scavenging
		if(isset($arFields['BEGINDATE']) && (!is_string($arFields['BEGINDATE']) || trim($arFields['BEGINDATE']) === ''))
		{
			unset($arFields['BEGINDATE']);
		}

		if(isset($arFields['CLOSEDATE']) && (!is_string($arFields['CLOSEDATE']) || trim($arFields['CLOSEDATE']) === ''))
		{
			unset($arFields['CLOSEDATE']);
		}

		if (isset($arFields['ASSIGNED_BY_ID']) && $arFields['ASSIGNED_BY_ID'] <= 0)
		{
			unset($arFields['ASSIGNED_BY_ID']);
		}

		$assignedByID = (int)(isset($arFields['ASSIGNED_BY_ID']) ? $arFields['ASSIGNED_BY_ID'] : $arRow['ASSIGNED_BY_ID']);

		$bResult = false;

		$options['CURRENT_FIELDS'] = $arRow;
		if (!$this->CheckFields($arFields, $ID, $options))
		{
			$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
		}
		else
		{
			//region Category, SemanticID and IsNew
			if (
				isset($arFields['STAGE_ID'])
				&& !self::IsStageExists($arFields['STAGE_ID'], $arRow['CATEGORY_ID'])
			)
			{
				// Ignore if the received stage_id is not exists
				unset($arFields['STAGE_ID']);
			}

			//Semantic ID depends on Stage ID and can't be assigned directly
			$syncStageSemantics = isset($options['SYNCHRONIZE_STAGE_SEMANTICS']) && $options['SYNCHRONIZE_STAGE_SEMANTICS'];
			if(isset($arFields['STAGE_ID']) && ($syncStageSemantics || $arFields['STAGE_ID'] !== $arRow['STAGE_ID']))
			{
				$arFields['STAGE_SEMANTIC_ID'] = self::IsStageExists($arFields['STAGE_ID'], $arRow['CATEGORY_ID'])
					? self::GetSemanticID($arFields['STAGE_ID'], $arRow['CATEGORY_ID'])
					: Bitrix\Crm\PhaseSemantics::UNDEFINED;
				$arFields['IS_NEW'] = $arFields['STAGE_ID'] === self::GetStartStageID($arRow['CATEGORY_ID']) ? 'Y' : 'N';

				if ($arFields['STAGE_ID'] !== $arRow['STAGE_ID'])
				{
					$arFields['MOVED_BY_ID'] = (int)$userID;
					$arFields['MOVED_TIME'] = (new \Bitrix\Main\Type\DateTime())->toString();
				}
			}
			else
			{
				unset($arFields['STAGE_SEMANTIC_ID'], $arFields['IS_NEW']);
			}
			//endregion

			$permissionEntityType = DealCategory::convertToPermissionEntityType($arRow['CATEGORY_ID']);
			if($this->bCheckPermission && !CCrmAuthorizationHelper::CheckUpdatePermission($permissionEntityType, $ID, $this->cPerms))
			{
				$this->LAST_ERROR = GetMessage('CRM_PERMISSION_DENIED');
				$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
				return false;
			}

			$updateOperationRestriction = Crm\Restriction\RestrictionManager::getUpdateOperationRestriction(new Crm\ItemIdentifier(
				\CCrmOwnerType::Deal,
				(int)$ID
			));
			if (!$isSystemAction && !$updateOperationRestriction->hasPermission())
			{
				$this->LAST_ERROR = $updateOperationRestriction->getErrorMessage();
				$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
				return false;
			}

			$stageId = $arFields['STAGE_ID'] ?? $arRow['STAGE_ID'];
			$categoryID = isset($arRow['CATEGORY_ID']) ? (int)$arRow['CATEGORY_ID'] : 0;
			if (
				$this->bCheckPermission
				&& $stageId !== $arRow['STAGE_ID']
				&& !Container::getInstance()->getUserPermissions($userID)->isStageTransitionAllowed(
					$arRow['STAGE_ID'],
					$stageId,
					new Crm\ItemIdentifier(CCrmOwnerType::Deal, $ID, $categoryID)
				)
			)
			{
				$this->LAST_ERROR = Loc::getMessage('CRM_PERMISSION_STAGE_TRANSITION_NOT_ALLOWED');

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
				$beforeEvents = GetModuleEvents('crm', 'OnBeforeCrmDealUpdate');
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
							$this->LAST_ERROR = GetMessage('CRM_DEAL_UPDATE_CANCELED', array('#NAME#' => $arEvent['TO_NAME']));
							$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
						}
						return false;
					}
				}
			}
			//endregion

			$arAttr = array();
			$arAttr['STAGE_ID'] = !empty($arFields['STAGE_ID']) ? $arFields['STAGE_ID'] : $arRow['STAGE_ID'];
			$arAttr['OPENED'] = !empty($arFields['OPENED']) ? $arFields['OPENED'] : $arRow['OPENED'];

			$originalObserverIDs = Crm\Observer\ObserverManager::getEntityObserverIDs(CCrmOwnerType::Deal, $ID);
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

			$arEntityAttr = self::BuildEntityAttr($assignedByID, $arAttr);
			if($this->bCheckPermission)
			{
				$sEntityPerm = $this->cPerms->GetPermType($permissionEntityType, 'WRITE', $arEntityAttr);
				//HACK: Ensure that entity accessible for user restricted by BX_CRM_PERM_OPEN
				self::PrepareEntityAttrs($arEntityAttr, $sEntityPerm);
				//HACK: Prevent 'OPENED' field change by user restricted by BX_CRM_PERM_OPEN permission
				if($sEntityPerm === BX_CRM_PERM_OPEN && isset($arFields['OPENED']) && $arFields['OPENED'] !== 'Y' && $assignedByID !== $userID)
				{
					$arFields['OPENED'] = 'Y';
				}
			}

			//region Preparation of contacts
			$originalContactBindings = DealContactTable::getDealBindings($ID);
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
			self::getCommentsAdapter()
				->setPreviousFields((int)$ID, $arRow)
				->normalizeFields((int)$ID, $arFields)
			;

			$sonetEventData = array();
			if ($bCompare)
			{
				$compareOptions = array();
				if(!empty($addedContactIDs) || !empty($removedContactIDs))
				{
					$compareOptions['CONTACTS'] = array('ADDED' => $addedContactIDs, 'REMOVED' => $removedContactIDs);
				}
				$arEvents = self::CompareFields($arRow, $arFields, $this->bCheckPermission, array_merge($compareOptions, $options));
				foreach($arEvents as $arEvent)
				{
					$arEvent['ENTITY_TYPE'] = 'DEAL';
					$arEvent['ENTITY_ID'] = $ID;
					$arEvent['EVENT_TYPE'] = 1;

					if(!isset($arEvent['USER_ID']))
					{
						if($userID > 0)
						{
							$arEvent['USER_ID'] = $userID;
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
						case 'STAGE_ID':
						{
							$sonetEventData[CCrmLiveFeedEvent::Progress] = array(
								'TYPE' => CCrmLiveFeedEvent::Progress,
								'FIELDS' => array(
									//'EVENT_ID' => $eventID,
									'TITLE' => GetMessage('CRM_DEAL_EVENT_UPDATE_STAGE'),
									'MESSAGE' => '',
									'PARAMS' => array(
										'START_STATUS_ID' => $arRow['STAGE_ID'],
										'FINAL_STATUS_ID' => $arFields['STAGE_ID'],
										'CATEGORY_ID' => intval($arRow['CATEGORY_ID'])
									)
								)
							);
						}
						break;
						case 'ASSIGNED_BY_ID':
						{
							$sonetEventData[CCrmLiveFeedEvent::Responsible] = array(
								'TYPE' => CCrmLiveFeedEvent::Responsible,
								'FIELDS' => array(
									//'EVENT_ID' => $eventID,
									'TITLE' => GetMessage('CRM_DEAL_EVENT_UPDATE_ASSIGNED_BY'),
									'MESSAGE' => '',
									'PARAMS' => array(
										'START_RESPONSIBLE_ID' => $arRow['ASSIGNED_BY_ID'],
										'FINAL_RESPONSIBLE_ID' => $arFields['ASSIGNED_BY_ID']
									)
								)
							);
						}
						break;
						case 'CONTACT_ID':
						case 'COMPANY_ID':
						{
							if(!isset($sonetEventData[CCrmLiveFeedEvent::Client]))
							{
								$oldCompanyID = isset($arRow['COMPANY_ID']) ? intval($arRow['COMPANY_ID']) : 0;
								$sonetEventData[CCrmLiveFeedEvent::Client] = array(
									'CODE'=> 'CLIENT',
									'TYPE' => CCrmLiveFeedEvent::Client,
									'FIELDS' => array(
										//'EVENT_ID' => $eventID,
										'TITLE' => GetMessage('CRM_DEAL_EVENT_UPDATE_CLIENT'),
										'MESSAGE' => '',
										'PARAMS' => array(
											'REMOVED_CLIENT_CONTACT_IDS' => is_array($removedContactIDs)
												? $removedContactIDs : array(),
											'ADDED_CLIENT_CONTACT_IDS' => is_array($addedContactIDs)
												? $addedContactIDs : array(),
											//Todo: Remove START_CLIENT_CONTACT_ID and FINAL_CLIENT_CONTACT_ID when log template will be ready
											'START_CLIENT_CONTACT_ID' => is_array($removedContactIDs)
												&& isset($removedContactIDs[0]) ? $removedContactIDs[0] : 0,
											'FINAL_CLIENT_CONTACT_ID' => is_array($addedContactIDs)
												&& isset($addedContactIDs[0]) ? $addedContactIDs[0] : 0,
											'START_CLIENT_COMPANY_ID' => $oldCompanyID,
											'FINAL_CLIENT_COMPANY_ID' => isset($arFields['COMPANY_ID']) ? intval($arFields['COMPANY_ID']) : $oldCompanyID
										)
									)
								);
							}
						}
						break;
						case 'TITLE':
						{
							$sonetEventData[CCrmLiveFeedEvent::Denomination] = array(
								'TYPE' => CCrmLiveFeedEvent::Denomination,
								'FIELDS' => array(
									//'EVENT_ID' => $eventID,
									'TITLE' => GetMessage('CRM_DEAL_EVENT_UPDATE_TITLE'),
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

			$arFields = array_merge($arFields, \CCrmAccountingHelper::calculateAccountingData($arFields, $arRow, true));

			$currentDate = ConvertTimeStamp(time() + \CTimeZone::GetOffset(), 'SHORT', SITE_ID);
			$enableCloseDateSync = DealSettings::getCurrent()->isCloseDateSyncEnabled();
			if(isset($options['ENABLE_CLOSE_DATE_SYNC']) && is_bool($options['ENABLE_CLOSE_DATE_SYNC']))
			{
				$enableCloseDateSync = $options['ENABLE_CLOSE_DATE_SYNC'];
			}

			$categoryID = isset($arRow['CATEGORY_ID']) ? (int)$arRow['CATEGORY_ID'] : 0;
			if(isset($arFields['STAGE_ID']))
			{
				$isFinalStage = self::GetStageSemantics($arFields['STAGE_ID'], $categoryID) !== 'process';
				$isStageChanged = !isset($arRow['STAGE_ID']) || $arRow['STAGE_ID'] !== $arFields['STAGE_ID'];

				$arFields['CLOSED'] = $isFinalStage ? 'Y' : 'N';
				if($enableCloseDateSync && $isFinalStage && $isStageChanged)
				{
					$arFields['CLOSEDATE'] = $currentDate;
					$arFields['~CLOSEDATE'] = $DB->CharToDateFunction($currentDate, 'SHORT', false);
				}
				elseif(isset($arFields['CLOSEDATE']))
				{
					$arFields['~CLOSEDATE'] = $DB->CharToDateFunction($arFields['CLOSEDATE'], 'SHORT', false);
				}
			}
			elseif(isset($arFields['CLOSEDATE']))
			{
				$arFields['~CLOSEDATE'] = $DB->CharToDateFunction($arFields['CLOSEDATE'], 'SHORT', false);
			}

			if(isset($arFields['BEGINDATE']))
			{
				$arFields['~BEGINDATE'] = $DB->CharToDateFunction($arFields['BEGINDATE'], 'SHORT', false);
			}

			if(isset($arFields['BEGINDATE']))
			{
				$arFields['__BEGINDATE'] = $arFields['BEGINDATE'];
				unset($arFields['BEGINDATE']);
			}

			if(isset($arFields['CLOSEDATE']))
			{
				$arFields['__CLOSEDATE'] = $arFields['CLOSEDATE'];
				unset($arFields['CLOSEDATE']);
			}

			unset($arFields['ID']);

			$this->normalizeEntityFields($arFields);
			$sUpdate = $DB->PrepareUpdate(self::TABLE_NAME, $arFields);

			if ($sUpdate <> '')
			{
				$DB->Query("UPDATE b_crm_deal SET {$sUpdate} WHERE ID = {$ID}");
				$bResult = true;
			}

			//Restore BEGINDATE and CLOSEDATE
			if(isset($arFields['__BEGINDATE']))
			{
				$arFields['BEGINDATE'] = $arFields['__BEGINDATE'];
				unset($arFields['__BEGINDATE']);
			}

			if(isset($arFields['__CLOSEDATE']))
			{
				$arFields['CLOSEDATE'] = $arFields['__CLOSEDATE'];
				unset($arFields['__CLOSEDATE']);
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
					$GLOBALS["CACHE_MANAGER"]->ClearByTag("crm_entity_name_".CCrmOwnerType::Deal."_".$ID);
				}
			}

			//region User Field
			CCrmEntityHelper::NormalizeUserFields($arFields, self::$sUFEntityID, $GLOBALS['USER_FIELD_MANAGER'], array('IS_NEW' => false));
			$GLOBALS['USER_FIELD_MANAGER']->Update(self::$sUFEntityID, $ID, $arFields);
			//endregion

			//region Ensure entity has not been deleted yet by concurrent process
			$currentDbResult = \CCrmDeal::GetListEx(
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
				DealContactTable::unbindContacts($ID, $removedContactBindings);
			}

			if(!empty($addedContactBindings))
			{
				DealContactTable::bindContacts($ID, $addedContactBindings);
			}
			//endregion

			//region Save Observers
			if(!empty($addedObserverIDs))
			{
				Crm\Observer\ObserverManager::registerBulk(
					$addedObserverIDs,
					\CCrmOwnerType::Deal,
					$ID,
					count($originalObserverIDs)
				);
			}

			if(!empty($removedObserverIDs))
			{
				Crm\Observer\ObserverManager::unregisterBulk(
					$removedObserverIDs,
					\CCrmOwnerType::Deal,
					$ID
				);
			}
			//endregion

			//region Save access rights for owner and observers
			$securityRegisterOptions = (new \Bitrix\Crm\Security\Controller\RegisterOptions())
				->setEntityAttributes($arEntityAttr)
				->setEntityFields($currentFields)
			;
			Crm\Security\Manager::getEntityController(CCrmOwnerType::Deal)
				->register($permissionEntityType, $ID, $securityRegisterOptions)
			;
			//endregion

			self::SynchronizeCustomerData($ID, $arRow, array('ENABLE_SOURCE' => false));
			self::SynchronizeCustomerData($ID, $currentFields);

			//region Complete activities if entity is closed
			if (
				$arRow['STAGE_SEMANTIC_ID'] !== $currentFields['STAGE_SEMANTIC_ID']
				&& $currentFields['STAGE_SEMANTIC_ID'] !== Bitrix\Crm\PhaseSemantics::PROCESS
			)
			{
				CCrmActivity::SetAutoCompletedByOwner(CCrmOwnerType::Deal, $ID);
			}
			//endregion
			//region Statistics & History
			if(!isset($options['REGISTER_STATISTICS']) || $options['REGISTER_STATISTICS'] === true)
			{
				DealSumStatisticEntry::register($ID, $currentFields);

				DealStageHistoryEntry::synchronize($ID, $currentFields);
				DealInvoiceStatisticEntry::synchronize($ID, $currentFields);
				DealActivityStatisticEntry::synchronize($ID, $currentFields);
				DealChannelBinding::synchronize($ID, $currentFields);

				if(isset($arFields['STAGE_ID']))
				{
					DealStageHistoryEntry::register($ID, $currentFields, array('IS_NEW' => false));
				}

				$oldLeadID = isset($arRow['LEAD_ID']) ? (int)$arRow['LEAD_ID'] : 0;
				$curLeadID = isset($arFields['LEAD_ID']) ? (int)$arFields['LEAD_ID'] : $oldLeadID;
				if($oldLeadID != $curLeadID)
				{
					if($oldLeadID > 0)
					{
						LeadConversionStatisticsEntry::processBindingsChange($oldLeadID);
					}

					if($curLeadID > 0)
					{
						LeadConversionStatisticsEntry::processBindingsChange($curLeadID);
					}
				}
			}
			//endregion

			self::getCommentsAdapter()
				->setPreviousFields((int)$ID, $arRow)
				->performUpdate((int)$ID, $arFields, $options)
			;

			if ($bResult)
			{
				\Bitrix\Crm\Counter\Monitor::getInstance()->onEntityUpdate(CCrmOwnerType::Deal, $arRow, $currentFields);
			}

			// update utm fields
			UtmTable::updateEntityUtmFromFields(CCrmOwnerType::Deal, $ID, $arFields);

			//region save parent relations
			Container::getInstance()->getParentFieldManager()->saveParentRelationsForIdentifier(
				new Crm\ItemIdentifier(\CCrmOwnerType::Deal, $ID),
				$arFields
			);
			//endregion

			if($bUpdateSearch)
			{
				$arFilterTmp = Array('ID' => $ID);
				if (!$this->bCheckPermission)
					$arFilterTmp['CHECK_PERMISSIONS'] = 'N';
				CCrmSearch::UpdateSearch($arFilterTmp, 'DEAL', true);
			}

			//region Search content index
			Bitrix\Crm\Search\SearchContentBuilderFactory::create(CCrmOwnerType::Deal)
				->build($ID, ['checkExist' => true]);
			//endregion

			Bitrix\Crm\Timeline\DealController::getInstance()->onModify(
				$ID,
				array(
					'CURRENT_FIELDS' => $arFields,
					'PREVIOUS_FIELDS' => $arRow,
					'CONTACT_BINDINGS' => $contactBindings,
					'ADDED_CONTACT_BINDINGS' => $addedContactBindings
				)
			);

			$currentStageSemantics = isset($arFields['STAGE_ID'], $arFields['STAGE_SEMANTIC_ID'])
				? Container::getInstance()->getFactory(\CCrmOwnerType::Deal)?->getStageSemantics($arFields['STAGE_ID'])
				: Crm\PhaseSemantics::UNDEFINED
			;

			CCrmEntityHelper::registerAdditionalTimelineEvents([
				'entityTypeId' => \CCrmOwnerType::Deal,
				'entityId' => $ID,
				'fieldsInfo' => static::GetFieldsInfo(),
				'previousFields' => $arRow,
				'currentFields' => $arFields,
				'previousStageSemantics' => $arRow['STAGE_SEMANTIC_ID'] ?? Crm\PhaseSemantics::UNDEFINED,
				'currentStageSemantics' => $currentStageSemantics ?? Crm\PhaseSemantics::UNDEFINED,
				'options' => $options,
				'bindings' => [
					'entityTypeId' => \CCrmOwnerType::Contact,
					'previous' => $originalContactBindings,
					'current' => $contactBindings,
				]
			]);

			Bitrix\Crm\Integration\Im\Chat::onEntityModification(
				CCrmOwnerType::Deal,
				$ID,
				array(
					'CURRENT_FIELDS' => $arFields,
					'PREVIOUS_FIELDS' => $arRow,
					'ADDED_OBSERVER_IDS' => $addedObserverIDs,
					'REMOVED_OBSERVER_IDS' => $removedObserverIDs
				)
			);

			$arFields['ID'] = $ID;

			if (isset($arFields['FM']) && is_array($arFields['FM']))
			{
				$CCrmFieldMulti = new CCrmFieldMulti();
				$CCrmFieldMulti->SetFields('DEAL', $ID, $arFields['FM']);
			}

			// Responsible user sync
			//CCrmActivity::Synchronize(CCrmOwnerType::Deal, $ID);

			$registerSonetEvent = isset($options['REGISTER_SONET_EVENT']) && $options['REGISTER_SONET_EVENT'] === true;

			if (
				$bResult
				&& isset($arFields['ASSIGNED_BY_ID'])
				&& Crm\Settings\Crm::isLiveFeedRecordsGenerationEnabled()
			)
			{
				CCrmSonetSubscription::ReplaceSubscriptionByEntity(
					CCrmOwnerType::Deal,
					$ID,
					CCrmSonetSubscriptionType::Responsibility,
					$arFields['ASSIGNED_BY_ID'],
					$arRow['ASSIGNED_BY_ID'],
					$registerSonetEvent
				);
			}

			$title = CCrmOwnerType::GetCaption(CCrmOwnerType::Deal, $ID, false);
			$modifiedByID = (int)$arFields['MODIFY_BY_ID'];
			$difference = Crm\Comparer\ComparerBase::compareEntityFields([],[
				Item::FIELD_NAME_ID => $ID,
				Item::FIELD_NAME_TITLE => $title,
				Item::FIELD_NAME_UPDATED_BY => $modifiedByID,
			]);

			if (!empty($addedObserverIDs) || !empty($removedObserverIDs))
			{
				$difference
					->setPreviousValue(Item::FIELD_NAME_OBSERVERS, $originalObserverIDs ?? [])
					->setCurrentValue(Item::FIELD_NAME_OBSERVERS, $observerIDs ?? [])
				;
			}

			if($bResult && $bCompare && $registerSonetEvent && !empty($sonetEventData))
			{
				//region Preparation of Parent Contact IDs
				$parentContactIDs = is_array($contactIDs)
					? $contactIDs : DealContactTable::getDealContactIDs($ID);
				//endregion

				//COMPANY
				$newCompanyID = isset($arFields['COMPANY_ID']) ? (int)$arFields['COMPANY_ID'] : 0;
				$oldCompanyID = isset($arRow['COMPANY_ID']) ? (int)$arRow['COMPANY_ID'] : 0;
				$companyID = $newCompanyID > 0 ? $newCompanyID : $oldCompanyID;

				foreach($sonetEventData as &$sonetEvent)
				{
					$sonetEventType = $sonetEvent['TYPE'];
					$sonetEventFields = &$sonetEvent['FIELDS'];
					$sonetEventFields['ENTITY_TYPE_ID'] = CCrmOwnerType::Deal;
					$sonetEventFields['ENTITY_ID'] = $ID;
					$sonetEventFields['USER_ID'] = $modifiedByID;

					$parents = array();
					//Register company relation
					if($companyID > 0)
					{
						CCrmLiveFeed::PrepareOwnershipRelations(
							CCrmOwnerType::Company,
							array($companyID),
							$parents
						);
					}

					//Register contact relation
					CCrmLiveFeed::PrepareOwnershipRelations(
						CCrmOwnerType::Contact,
						$parentContactIDs,
						$parents
					);

					if(!empty($parents))
					{
						$sonetEventFields['PARENTS'] = array_values($parents);
					}

					$logEventID = CCrmLiveFeed::CreateLogEvent($sonetEventFields, $sonetEventType, ['CURRENT_USER' => $userID]);

					if (
						$logEventID !== false
						&& CModule::IncludeModule('im')
					)
					{
						$url = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Deal, $ID);
						$absoluteUrl = CCrmUrlUtil::ToAbsoluteUrl($url);

						if ($sonetEvent['TYPE'] === CCrmLiveFeedEvent::Responsible)
						{
							$difference
								->setPreviousValue(
									Item::FIELD_NAME_ASSIGNED,
									(int)$sonetEventFields['PARAMS']['START_RESPONSIBLE_ID'],
								)
								->setCurrentValue(
									Item::FIELD_NAME_ASSIGNED,
									(int)$sonetEventFields['PARAMS']['FINAL_RESPONSIBLE_ID'],
								)
							;
						}

						if (
							$sonetEvent['TYPE'] === CCrmLiveFeedEvent::Progress
							&& $sonetEventFields['PARAMS']['START_STATUS_ID']
							&& $sonetEventFields['PARAMS']['FINAL_STATUS_ID']

						)
						{
							$assignedByID = (isset($arFields['ASSIGNED_BY_ID']) ? $arFields['ASSIGNED_BY_ID'] : $arRow['ASSIGNED_BY_ID']);
							$infos = self::GetStages($categoryID);

							if (
								$assignedByID != $modifiedByID
								&& isset($infos[$sonetEventFields['PARAMS']['START_STATUS_ID']])
								&& isset($infos[$sonetEventFields['PARAMS']['FINAL_STATUS_ID']])
							)
							{
								$title = "<a href=\"" . $url . "\" class=\"bx-notifier-item-action\">" . htmlspecialcharsbx($title) . "</a>";
								$titleOut = htmlspecialcharsbx($title);
								$startStatusTitle = htmlspecialcharsbx($infos[$sonetEventFields['PARAMS']['START_STATUS_ID']]['NAME']);
								$finalStatusTitle = htmlspecialcharsbx($infos[$sonetEventFields['PARAMS']['FINAL_STATUS_ID']]['NAME']);

								$notifyMessage = static function (?string $languageId = null) use (
									$title,
									$startStatusTitle,
									$finalStatusTitle,
								) {
									$replace = [
										"#title#" => $title,
										"#start_status_title#" => $startStatusTitle,
										"#final_status_title#" => $finalStatusTitle,
									];

									return Loc::getMessage(
										"CRM_DEAL_PROGRESS_IM_NOTIFY_2",
										$replace,
										$languageId,
									);
								};

								$notifyMessageOut = static function (?string $languageId = null) use (
									$absoluteUrl,
									$titleOut,
									$startStatusTitle,
									$finalStatusTitle
								) {
									$replace = [
										"#title#" => $titleOut,
										"#start_status_title#" => $startStatusTitle,
										"#final_status_title#" => $finalStatusTitle,
									];

									$message = Loc::getMessage(
										"CRM_DEAL_PROGRESS_IM_NOTIFY_2",
										$replace,
										$languageId,
									);

									return "{$message} ({$absoluteUrl})";
								};

								$arMessageFields = array(
									"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
									"TO_USER_ID" => $assignedByID,
									"FROM_USER_ID" => $modifiedByID,
									"NOTIFY_TYPE" => IM_NOTIFY_FROM,
									"NOTIFY_MODULE" => "crm",
									"LOG_ID" => $logEventID,
									//"NOTIFY_EVENT" => "deal_update",
									"NOTIFY_EVENT" => "changeStage",
									"NOTIFY_TAG" => "CRM|DEAL_PROGRESS|".$ID,
									"NOTIFY_MESSAGE" => $notifyMessage,
									"NOTIFY_MESSAGE_OUT" => $notifyMessageOut,
								);

								CIMNotify::Add($arMessageFields);
							}
						}

					}

					unset($sonetEventFields);
				}

				unset($sonetEvent);
			}

			NotificationManager::getInstance()->sendAllNotificationsAboutUpdate(
				CCrmOwnerType::Deal,
				$difference,
			);

			//region After update event
			if($bResult && $enableSystemEvents)
			{
				$afterEvents = GetModuleEvents('crm', 'OnAfterCrmDealUpdate');
				while ($arEvent = $afterEvents->Fetch())
					ExecuteModuleEventEx($arEvent, array(&$arFields));
			}
			//endregion

			self::PullChange('UPDATE', array('ID' => $ID));

			if(!$isSystemAction)
			{
				$stageSemanticsId = ($arFields['STAGE_SEMANTIC_ID'] ?? null) ?: $arRow['STAGE_SEMANTIC_ID'];
				if(Crm\Ml\Scoring::isMlAvailable() && !Crm\PhaseSemantics::isFinal($stageSemanticsId))
				{
					Crm\Ml\Scoring::queuePredictionUpdate(CCrmOwnerType::Deal, $ID, [
						'EVENT_TYPE' => Crm\Ml\Scoring::EVENT_ENTITY_UPDATE
					]);
				}
			}

			if ($bResult)
			{
				$scope = Container::getInstance()->getContext()->getScope();
				$filler = new ValueFiller(CCrmOwnerType::Deal, $ID, $scope);
				$filler->fill($options['CURRENT_FIELDS'], $arFields);

				if (
					is_array($arRow)
					&& is_array($arFields)
					&& ComparerBase::compareEntityFields($arRow, $arFields)->isChanged('STAGE_ID')
				)
				{
					self::clearStageCache($ID);
				}

				if (
					isset($arRow['STAGE_ID'], $currentFields['STAGE_ID'])
					&& ComparerBase::isMovedToFinalStage(
						CCrmOwnerType::Deal,
						$arRow['STAGE_ID'],
						$currentFields['STAGE_ID']
					)
				)
				{
					$item = Container::getInstance()->getFactory(CCrmOwnerType::Deal)?->getItem($ID);
					if ($item)
					{
						(new Bitrix\Crm\Service\Operation\Action\DeleteEntityBadges())->process($item);
					}
				}
			}

			if ($bResult && !$syncStageSemantics)
			{
				$item = Crm\Kanban\Entity::getInstance(self::$TYPE_NAME)
					->createPullItem(array_merge($arRow, $arFields));

				PullManager::getInstance()->sendItemUpdatedEvent(
					$item,
					[
						'TYPE' => self::$TYPE_NAME,
						'CATEGORY_ID' => \CCrmDeal::GetCategoryID($ID),
						'SKIP_CURRENT_USER' => ($userID !== 0),
						'IGNORE_DELAY' => \CCrmBizProcHelper::isActiveDebugEntity(\CCrmOwnerType::Deal, $ID),
						'EVENT_ID' => ($options['eventId'] ?? null),
					]
				);
			}
		}

		return $bResult;
	}

	public function Delete($ID, $arOptions = array())
	{
		global $DB, $APPLICATION;

		$ID = (int)$ID;

		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		if($this->isUseOperation())
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

		$dbResult = \CCrmDeal::GetListEx(
			array(),
			array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N')
		);
		$arFields = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($arFields))
		{
			return false;
		}

		$assignedByID = isset($arFields['ASSIGNED_BY_ID']) ? (int)$arFields['ASSIGNED_BY_ID'] : 0;
		$categoryID = isset($arFields['CATEGORY_ID']) ? (int)$arFields['CATEGORY_ID'] : 0;

		$permissionEntityType = DealCategory::convertToPermissionEntityType($categoryID);

		$hasDeletePerm = Container::getInstance()
			->getUserPermissions($iUserId)
			->checkDeletePermissions(CCrmOwnerType::Deal, $ID, $categoryID)
		;

		if ($this->bCheckPermission && !$hasDeletePerm)
		{
			$this->LAST_ERROR = Loc::getMessage('CRM_DEAL_NO_PERMISSIONS_TO_DELETE', [
				'#DEAL_NAME#' => htmlspecialcharsbx($arFields['TITLE'] ?? $arFields['ID']),
			]);

			return false;
		}

		$APPLICATION->ResetException();
		$events = GetModuleEvents('crm', 'OnBeforeCrmDealDelete');
		while ($arEvent = $events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array($ID))===false)
			{
				$err = GetMessage("MAIN_BEFORE_DEL_ERR").' '.$arEvent['TO_NAME'];
				if ($ex = $APPLICATION->GetException())
				{
					$err .= ': ' . $ex->GetString();
					if ($ex->GetID() === 'system')
					{
						$err =  $ex->GetString();
					}
				}
				$this->LAST_ERROR = $err;
				$APPLICATION->ThrowException($this->LAST_ERROR);
				return false;
			}

		$enableDeferredMode = isset($arOptions['ENABLE_DEFERRED_MODE'])
			? (bool)$arOptions['ENABLE_DEFERRED_MODE']
			: \Bitrix\Crm\Settings\DealSettings::getCurrent()->isDeferredCleaningEnabled();

		//By default we need to clean up related bizproc entities
		$processBizproc = isset($arOptions['PROCESS_BIZPROC']) ? (bool)$arOptions['PROCESS_BIZPROC'] : true;
		if($processBizproc)
		{
			$bizproc = new CCrmBizProc('DEAL');
			$bizproc->ProcessDeletion($ID);
		}

		$enableRecycleBin = \Bitrix\Crm\Recycling\DealController::isEnabled()
			&& \Bitrix\Crm\Settings\DealSettings::getCurrent()->isRecycleBinEnabled();
		if($enableRecycleBin)
		{
			\Bitrix\Crm\Recycling\DealController::getInstance()->moveToBin($ID, array('FIELDS' => $arFields));
		}

		$dbRes = $DB->Query("DELETE FROM b_crm_deal WHERE ID = {$ID}");
		if (is_object($dbRes) && $dbRes->AffectedRowsCount() > 0)
		{
			if(defined('BX_COMP_MANAGED_CACHE'))
			{
				$GLOBALS['CACHE_MANAGER']->CleanDir('b_crm_deal');
			}
			self::clearCategoryCache($ID);
			self::clearStageCache($ID);

			self::SynchronizeCustomerData($ID, $arFields, array('ENABLE_SOURCE' => false));

			CCrmSearch::DeleteSearch('DEAL', $ID);

			Bitrix\Crm\Search\SearchContentBuilderFactory::create(
				CCrmOwnerType::Deal
			)->removeShortIndex($ID);

			Bitrix\Crm\Kanban\SortTable::clearEntity($ID, \CCrmOwnerType::DealName);

			Crm\Security\Manager::getEntityController(CCrmOwnerType::Deal)
				->unregister(
					$permissionEntityType,
					$ID
				)
			;

			$GLOBALS['USER_FIELD_MANAGER']->Delete(self::$sUFEntityID, $ID);

			DealContactTable::unbindAllContacts($ID);

			if(!$enableDeferredMode)
			{
				$CCrmEvent = new CCrmEvent();
				$CCrmEvent->DeleteByElement('DEAL', $ID);
			}
			else
			{
				Bitrix\Crm\Cleaning\CleaningManager::register(CCrmOwnerType::Deal, $ID);
			}

			if(!isset($arOptions['REGISTER_STATISTICS']) || $arOptions['REGISTER_STATISTICS'] === true)
			{
				DealStageHistoryEntry::unregister($ID);
				DealSumStatisticEntry::unregister($ID);
				DealInvoiceStatisticEntry::unregister($ID);
				DealActivityStatisticEntry::unregister($ID);
				DealChannelBinding::unregisterAll($ID);
			}

			\Bitrix\Crm\Counter\Monitor::getInstance()->onEntityDelete(CCrmOwnerType::Deal, $arFields);

			//Statistics & History -->
			$leadID = isset($arFields['LEAD_ID']) ? (int)$arFields['LEAD_ID'] : 0;
			if($leadID)
			{
				LeadConversionStatisticsEntry::processBindingsChange($leadID);
			}
			//<-- Statistics & History

			CCrmActivity::DeleteByOwner(CCrmOwnerType::Deal, $ID);

			if(!$enableRecycleBin)
			{
				CCrmProductRow::DeleteByOwner('D', $ID);
				CCrmProductRow::DeleteSettings('D', $ID);

				\Bitrix\Crm\Requisite\EntityLink::unregister(CCrmOwnerType::Deal, $ID);
				\Bitrix\Crm\Timeline\TimelineEntry::deleteByOwner(CCrmOwnerType::Deal, $ID);
				\Bitrix\Crm\Pseudoactivity\WaitEntry::deleteByOwner(CCrmOwnerType::Deal, $ID);
				\Bitrix\Crm\Observer\ObserverManager::deleteByOwner(CCrmOwnerType::Deal, $ID);
				\Bitrix\Crm\Ml\Scoring::onEntityDelete(CCrmOwnerType::Deal, $ID);

				self::getCommentsAdapter()->performDelete((int)$ID, $arOptions);

				Crm\Integration\Im\Chat::deleteChat(
					array(
						'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
						'ENTITY_ID' => $ID
					)
				);

				CCrmSonetSubscription::UnRegisterSubscriptionByEntity(CCrmOwnerType::Deal, $ID);
				CCrmLiveFeed::DeleteLogEvents(
					array(
						'ENTITY_TYPE_ID' => CCrmOwnerType::Deal,
						'ENTITY_ID' => $ID
					)
				);
				UtmTable::deleteEntityUtm(CCrmOwnerType::Deal, $ID);
				Tracking\Entity::deleteTrace(CCrmOwnerType::Deal, $ID);
			}

			// Deletion of deal details
			\Bitrix\Crm\Timeline\DealController::getInstance()->onDelete(
				$ID,
				array('FIELDS' => $arFields)
			);

			if ($arFields['IS_RECURRING'] === "Y")
			{
				$dealRecurringItem = \Bitrix\Crm\Recurring\Entity\Item\DealExist::loadByDealId($ID);
				if ($dealRecurringItem)
				{
					$dealRecurringItem->delete();
				}
			}

			self::PullChange('DELETE', array('ID' => $ID));

			if(HistorySettings::getCurrent()->isDealDeletionEventEnabled())
			{
				CCrmEvent::RegisterDeleteEvent(CCrmOwnerType::Deal, $ID, $iUserId, array('FIELDS' => $arFields));
			}

			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("crm_entity_name_".CCrmOwnerType::Deal."_".$ID);
			}

			$afterEvents = GetModuleEvents('crm', 'OnAfterCrmDealDelete');
			while ($arEvent = $afterEvents->Fetch())
			{
				ExecuteModuleEventEx($arEvent, array($ID));
			}

			$fieldsContextEntity = EntityFactory::getInstance()->getEntity(CCrmOwnerType::Deal);
			if ($fieldsContextEntity)
			{
				$fieldsContextEntity::deleteByItemId($ID);
			}

			$item = Crm\Kanban\Entity::getInstance(self::$TYPE_NAME)
				->createPullItem($arFields);

			PullManager::getInstance()->sendItemDeletedEvent(
				$item,
				[
					'TYPE' => self::$TYPE_NAME,
					'CATEGORY_ID' => $categoryID,
					'SKIP_CURRENT_USER' => false,
					'EVENT_ID' => ($arOptions['eventId'] ?? null),
				]
			);
		}
		return true;
	}

	public static function CompareFields($arFieldsOrig, $arFieldsModif, $bCheckPerms = true, array $arOptions = null)
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
				'EVENT_NAME' => GetMessage(
					'CRM_DEAL_FIELD_COMPARE',
					array('#FIELD_NAME#' => self::GetFieldCaption('TITLE'))
				),
				'EVENT_TEXT_1' => $arFieldsOrig['TITLE'],
				'EVENT_TEXT_2' => $arFieldsModif['TITLE'],
			);

		if (isset($arFieldsOrig['COMPANY_ID']) && isset($arFieldsModif['COMPANY_ID'])
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
		if (isset($arFieldsOrig['STAGE_ID']) && isset($arFieldsModif['STAGE_ID'])
			&& $arFieldsOrig['STAGE_ID'] != $arFieldsModif['STAGE_ID'])
		{
			$arStatus = self::GetStageNames(
				isset($arFieldsOrig['CATEGORY_ID']) ? (int)$arFieldsOrig['CATEGORY_ID'] : 0
			);
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'STAGE_ID',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_DEAL_STAGE'),
				'EVENT_TEXT_1' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $arFieldsOrig['STAGE_ID'])),
				'EVENT_TEXT_2' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $arFieldsModif['STAGE_ID']))
			);
		}

		if (isset($arFieldsOrig['TYPE_ID']) && isset($arFieldsModif['TYPE_ID'])
			&& $arFieldsOrig['TYPE_ID'] != $arFieldsModif['TYPE_ID'])
		{
			$arStatus = CCrmStatus::GetStatusList('DEAL_TYPE');
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'TYPE_ID',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_DEAL_TYPE'),
				'EVENT_TEXT_1' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $arFieldsOrig['TYPE_ID'])),
				'EVENT_TEXT_2' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $arFieldsModif['TYPE_ID']))
			);
		}

		if (isset($arFieldsOrig['COMMENTS']) && isset($arFieldsModif['COMMENTS'])
			&& $arFieldsOrig['COMMENTS'] != $arFieldsModif['COMMENTS'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'COMMENTS',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_COMMENTS'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['COMMENTS'])? TextHelper::convertBbCodeToHtml($arFieldsOrig['COMMENTS']): GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['COMMENTS'])? TextHelper::convertBbCodeToHtml($arFieldsModif['COMMENTS']): GetMessage('CRM_FIELD_COMPARE_EMPTY'),
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

		if (
			isset($arFieldsOrig['OPPORTUNITY'])
			&& isset($arFieldsModif['OPPORTUNITY'])
			&& $arFieldsOrig['OPPORTUNITY'] != $arFieldsModif['OPPORTUNITY']
		)
		{
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'OPPORTUNITY',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_OPPORTUNITY'),
				'EVENT_TEXT_1' => floatval($arFieldsOrig['OPPORTUNITY']).(($val = CrmCompareFieldsList($arCurrency, ($arFieldsOrig['CURRENCY_ID'] ?? null), '')) != '' ? ' ('.$val.')' : ''),
				'EVENT_TEXT_2' => floatval($arFieldsModif['OPPORTUNITY']).(($val = CrmCompareFieldsList($arCurrency, ($arFieldsModif['CURRENCY_ID'] ?? null), '')) != '' ? ' ('.$val.')' : '')
			);
		}

		if (
			isset($arFieldsOrig['IS_MANUAL_OPPORTUNITY'])
			&& isset($arFieldsModif['IS_MANUAL_OPPORTUNITY'])
			&& $arFieldsOrig['IS_MANUAL_OPPORTUNITY'] != $arFieldsModif['IS_MANUAL_OPPORTUNITY']
		)
		{
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'IS_MANUAL_OPPORTUNITY',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_IS_MANUAL_OPPORTUNITY'),
				'EVENT_TEXT_1' => GetMessage('CRM_FIELD_COMPARE_IS_MANUAL_OPPORTUNITY_'.($arFieldsOrig['IS_MANUAL_OPPORTUNITY'] == 'Y' ? 'Y' : 'N')),
				'EVENT_TEXT_2' => GetMessage('CRM_FIELD_COMPARE_IS_MANUAL_OPPORTUNITY_'.($arFieldsModif['IS_MANUAL_OPPORTUNITY'] == 'Y' ? 'Y' : 'N')),
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

		if (isset($arFieldsOrig['PROBABILITY']) && isset($arFieldsModif['PROBABILITY'])
			&& $arFieldsOrig['PROBABILITY'] != $arFieldsModif['PROBABILITY'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'PROBABILITY',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_PROBABILITY'),
				'EVENT_TEXT_1' => intval($arFieldsOrig['PROBABILITY']).'%',
				'EVENT_TEXT_2' => intval($arFieldsModif['PROBABILITY']).'%',
			);

		if (array_key_exists('BEGINDATE', $arFieldsOrig) && array_key_exists('BEGINDATE', $arFieldsModif) &&
			ConvertTimeStamp(strtotime($arFieldsOrig['BEGINDATE'])) != $arFieldsModif['BEGINDATE'] && $arFieldsOrig['BEGINDATE'] != $arFieldsModif['BEGINDATE'])
		{
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'BEGINDATE',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_BEGINDATE'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['BEGINDATE'])? ConvertTimeStamp(strtotime($arFieldsOrig['BEGINDATE'])): GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['BEGINDATE'])? $arFieldsModif['BEGINDATE']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);
		}
		if (array_key_exists('CLOSEDATE', $arFieldsOrig) && array_key_exists('CLOSEDATE', $arFieldsModif) &&
			ConvertTimeStamp(strtotime($arFieldsOrig['CLOSEDATE'])) != $arFieldsModif['CLOSEDATE'] && $arFieldsOrig['CLOSEDATE'] != $arFieldsModif['CLOSEDATE'])
		{
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'CLOSEDATE',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_CLOSEDATE'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['CLOSEDATE'])? ConvertTimeStamp(strtotime($arFieldsOrig['CLOSEDATE'])): GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['CLOSEDATE'])? $arFieldsModif['CLOSEDATE']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);
		}
		if (array_key_exists('EVENT_DATE', $arFieldsOrig) && array_key_exists('EVENT_DATE', $arFieldsModif) &&
			ConvertTimeStamp(strtotime($arFieldsOrig['EVENT_DATE'])) != $arFieldsModif['EVENT_DATE'] && $arFieldsOrig['EVENT_DATE'] != $arFieldsModif['EVENT_DATE'])
		{
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'EVENT_DATE',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_EVENT_DATE'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['EVENT_DATE'])? ConvertTimeStamp(strtotime($arFieldsOrig['EVENT_DATE'])): GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['EVENT_DATE'])? $arFieldsModif['EVENT_DATE']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);
		}
		if (isset($arFieldsOrig['EVENT_ID']) && isset($arFieldsModif['EVENT_ID'])
			&& $arFieldsOrig['EVENT_ID'] != $arFieldsModif['EVENT_ID'])
		{
			$arStatus = CCrmStatus::GetStatusList('EVENT_TYPE');
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'EVENT_ID',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_EVENT_ID'),
				'EVENT_TEXT_1' => CrmCompareFieldsList($arStatus, $arFieldsOrig['EVENT_ID']),
				'EVENT_TEXT_2' => CrmCompareFieldsList($arStatus, $arFieldsModif['EVENT_ID'])
			);
		}
		if (isset($arFieldsOrig['EVENT_DESCRIPTION']) && isset($arFieldsModif['EVENT_DESCRIPTION'])
			&& $arFieldsOrig['EVENT_DESCRIPTION'] != $arFieldsModif['EVENT_DESCRIPTION'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'EVENT_DESCRIPTION',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_EVENT_DESCRIPTION'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['EVENT_DESCRIPTION'])? $arFieldsOrig['EVENT_DESCRIPTION']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['EVENT_DESCRIPTION'])? $arFieldsModif['EVENT_DESCRIPTION']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		if (isset($arFieldsOrig['CLOSED']) && isset($arFieldsModif['CLOSED'])
			&& $arFieldsOrig['CLOSED'] != $arFieldsModif['CLOSED'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'CLOSED',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_CLOSED'),
				'EVENT_TEXT_1' => $arFieldsOrig['CLOSED'] == 'Y'? GetMessage('MAIN_YES'): GetMessage('MAIN_NO'),
				'EVENT_TEXT_2' => $arFieldsModif['CLOSED'] == 'Y'? GetMessage('MAIN_YES'): GetMessage('MAIN_NO'),
			);

		if (isset($arFieldsOrig['LOCATION_ID']) &&
			isset($arFieldsModif['LOCATION_ID']) &&
			$arFieldsOrig['LOCATION_ID'] != $arFieldsModif['LOCATION_ID']
		)
		{
			$origLocationID = $arFieldsOrig['LOCATION_ID'];
			$modifLocationID = $arFieldsModif['LOCATION_ID'];

			$origLocationName = $modifLocationName = '';
			if (IsModuleInstalled('sale') && CModule::IncludeModule('sale'))
			{
				$location = new CSaleLocation();

				if($origLocationID > 0)
				{
					$origLocationName = $location->GetLocationString($origLocationID);
					if ($origLocationName == '')
					{
						$origLocationName = "[{$origLocationID}]";
					}
				}

				if($modifLocationID > 0)
				{
					$modifLocationName = $location->GetLocationString($modifLocationID);
					if ($modifLocationName == '')
					{
						$modifLocationName = "[{$modifLocationID}]";
					}
				}
			}

			$arMsg[] = Array(
				'ENTITY_FIELD' => 'LOCATION_ID',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_LOCATION_ID'),
				'EVENT_TEXT_1' => $origLocationName,
				'EVENT_TEXT_2' => $modifLocationName,
			);
		}
		return $arMsg;
	}

	public static function addProductRows(
		int $ID,
		array $newRows,
		array $options = [],
		$checkPerms = true,
		$regEvent = true,
		$syncOwner = true
	)
	{
		$rows = self::LoadProductRows($ID);
		if (!is_array($rows))
		{
			return false;
		}

		$sort = self::getMaxProductDealSort($rows);
		foreach ($newRows as $newRow)
		{
			$sort += 10;

			$newRow['SORT'] = $sort;

			$rows[] = $newRow;
		}

		return \CCrmDeal::SaveProductRows($ID, $rows, $checkPerms, $regEvent, $syncOwner);
	}

	/**
	 * @param array $products
	 * @return int
	 */
	private static function getMaxProductDealSort(array $products): int
	{
		$sort = 0;

		foreach ($products as $product)
		{
			if ($product['SORT'] <= $sort)
			{
				continue;
			}

			$sort = $product['SORT'];
		}

		return $sort;
	}

	public static function LoadProductRows($ID)
	{
		return CCrmProductRow::LoadRows(\CCrmOwnerTypeAbbr::Deal, $ID);
	}

	/**
	 * @return array
	 */
	public static function getSafeSaveRows(): array
	{
		return CCrmProductRow::getSafeSaveRows();
	}

	public static function SaveProductRows($ID, $arRows, $checkPerms = true, $regEvent = true, $syncOwner = true)
	{
		global $APPLICATION;

		/**
		 * @var CMain $APPLICATION
		 */

		$events = GetModuleEvents('crm', 'OnBeforeCrmDealProductRowsSave');
		while ($event = $events->Fetch())
		{
			$eventResult = ExecuteModuleEventEx($event, array($ID, $arRows));
			if ($eventResult instanceof \Bitrix\Main\Result)
			{
				$error = join(', ', $eventResult->getErrorMessages());
				if ($error)
				{
					$APPLICATION->ThrowException($error);
					return false;
				}
			}
			elseif ($eventResult === false)
			{
				return false;
			}
		}

		$result = CCrmProductRow::SaveRows('D', $ID, $arRows, null, $checkPerms, $regEvent, $syncOwner);

		if($result)
		{
			$events = GetModuleEvents('crm', 'OnAfterCrmDealProductRowsSave');
			while ($event = $events->Fetch())
			{
				ExecuteModuleEventEx($event, array($ID, $arRows));
			}
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
			array('ID', 'CURRENCY_ID', 'OPPORTUNITY', 'TAX_VALUE', 'EXCH_RATE')
		);

		$entity = new CCrmDeal(false);
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

		$arTotalInfo = CCrmProductRow::CalculateTotalInfo('D', $ID, $checkPerms);

		if (is_array($arTotalInfo))
		{
			$arFields = array(
				'TAX_VALUE' => isset($arTotalInfo['TAX_VALUE']) ? $arTotalInfo['TAX_VALUE'] : 0.0
			);

			$entity = new CCrmDeal($checkPerms);
			if (!$entity::isManualOpportunity($ID))
			{
				$arFields['OPPORTUNITY'] = isset($arTotalInfo['OPPORTUNITY']) ? $arTotalInfo['OPPORTUNITY'] : 0.0;

				$arFields['OPPORTUNITY'] += static::calculateDeliveryTotal($ID);
			}
			$entity->Update($ID, $arFields);
		}
	}

	/**
	 * @deprecated
	 *
	 * Collects sum of shipments from orders related to deal $id
	 * @param int $id Deal ID
	 * @return int|float
	 */
	public static function calculateDeliveryTotal($id)
	{
		$id = (int)$id;
		if ($id <= 0)
		{
			return 0;
		}

		$deal = new Crm\ItemIdentifier(\CCrmOwnerType::Deal, $id);

		return Container::getInstance()->getAccounting()->calculateDeliveryTotal($deal);
	}

	public static function GetCategoryID($ID)
	{
		return (int)Container::getInstance()->getFactory(CCrmOwnerType::Deal)->getItemCategoryId((int)$ID);
	}

	public static function clearCategoryCache($ID)
	{
		Container::getInstance()->getFactory(CCrmOwnerType::Deal)->clearItemCategoryCache((int)$ID);
	}

	private static function clearStageCache($ID)
	{
		Container::getInstance()->getFactory(CCrmOwnerType::Deal)->clearItemStageCache((int)$ID);
	}

	protected static function GetPermittedCategoryIDs($permissionType, CCrmPerms $userPermissions = null)
	{
		if(!($userPermissions instanceof CCrmPerms))
		{
			$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		}

		$categoryIDs = array();
		$allCategoryIDs = DealCategory::getAllIDs();
		foreach($allCategoryIDs as $categoryID)
		{
			$permissionEntity = DealCategory::convertToPermissionEntityType($categoryID);
			if($permissionType === 'CREATE')
			{
				$result = CCrmAuthorizationHelper::CheckCreatePermission($permissionEntity, $userPermissions);
			}
			elseif($permissionType === 'UPDATE')
			{
				$result = CCrmAuthorizationHelper::CheckUpdatePermission($permissionEntity, 0, $userPermissions);
			}
			else
			{
				$result = CCrmAuthorizationHelper::CheckReadPermission($permissionEntity, 0, $userPermissions);
			}

			if($result)
			{
				$categoryIDs[] = $categoryID;
			}
		}
		return $categoryIDs;
	}

	public static function GetPermittedToMoveCategoryIDs(CCrmPerms $userPermissions = null)
	{
		return self::GetPermittedCategoryIDs('CREATE', $userPermissions);
	}

	public static function GetPermittedToCreateCategoryIDs(CCrmPerms $userPermissions = null)
	{
		return self::GetPermittedCategoryIDs('CREATE', $userPermissions);
	}

	public static function GetPermittedToReadCategoryIDs(CCrmPerms $userPermissions = null)
	{
		return self::GetPermittedCategoryIDs('READ', $userPermissions);
	}

	public static function GetPermittedToUpdateCategoryIDs(CCrmPerms $userPermissions = null)
	{
		return self::GetPermittedCategoryIDs('UPDATE', $userPermissions);
	}

	public static function GetPermissionEntityTypeName($categoryID = 0)
	{
		return  DealCategory::convertToPermissionEntityType($categoryID);
	}

	public static function GetStageCreatePermissionType($stageID, CCrmPerms $userPermissions = null, $categoryID = 0)
	{
		if($userPermissions === null)
		{
			$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		}

		return $userPermissions->GetPermType(
			DealCategory::convertToPermissionEntityType($categoryID),
			'ADD',
			array("STAGE_ID{$stageID}")
		);
	}

	public static function GetStageUpdatePermissionType($stageID, CCrmPerms $userPermissions = null, $categoryID = 0)
	{
		if($userPermissions === null)
		{
			$userPermissions = CCrmPerms::GetCurrentUserPermissions();;
		}

		return $userPermissions->GetPermType(
			DealCategory::convertToPermissionEntityType($categoryID),
			'WRITE',
			array("STAGE_ID{$stageID}")
		);
	}

	public static function GetPermissionAttributes(array $IDs, $categoryID = -1)
	{
		if($categoryID >= 0)
		{
			$permEntity = DealCategory::convertToPermissionEntityType($categoryID);

			return \Bitrix\Crm\Security\Manager::resolveController($permEntity)
				->getPermissionAttributes($permEntity, $IDs)
			;
		}

		$results = [];
		foreach($IDs as $ID)
		{
			$permEntity = DealCategory::convertToPermissionEntityType(self::GetCategoryID($ID));
			$results += \Bitrix\Crm\Security\Manager::resolveController($permEntity)
				->getPermissionAttributes($permEntity, [$ID])
			;
		}

		return $results;
	}

	public static function IsAccessEnabled(CCrmPerms $userPermissions = null)
	{
		return self::CheckReadPermission(0, $userPermissions, -1);
	}

	public static function CheckImportPermission($userPermissions = null, $categoryID = -1)
	{
		if($categoryID >= 0)
		{
			$categoryIDs = array($categoryID);
		}
		else
		{
			$categoryIDs = DealCategory::getAllIDs();
		}

		foreach($categoryIDs as $curCategoryID)
		{
			$permissionEntity = DealCategory::convertToPermissionEntityType($curCategoryID);
			if(CCrmAuthorizationHelper::CheckImportPermission($permissionEntity, $userPermissions))
			{
				return true;
			}
		}
		return false;
	}

	public static function CheckExportPermission($userPermissions = null, $categoryID = -1)
	{
		if($categoryID >= 0)
		{
			$categoryIDs = array($categoryID);
		}
		else
		{
			$categoryIDs = DealCategory::getAllIDs();
		}

		foreach($categoryIDs as $curCategoryID)
		{
			$permissionEntity = DealCategory::convertToPermissionEntityType($curCategoryID);
			if(CCrmAuthorizationHelper::CheckExportPermission($permissionEntity, $userPermissions))
			{
				return true;
			}
		}
		return false;
	}

	public static function CheckCreatePermission($userPermissions = null, $categoryID = -1)
	{
		if($categoryID >= 0)
		{
			$categoryIDs = array($categoryID);
		}
		else
		{
			$categoryIDs = DealCategory::getAllIDs();
		}

		foreach($categoryIDs as $curCategoryID)
		{
			$permissionEntity = DealCategory::convertToPermissionEntityType($curCategoryID);
			if(CCrmAuthorizationHelper::CheckCreatePermission($permissionEntity, $userPermissions))
			{
				return true;
			}
		}
		return false;
	}

	public static function CheckUpdatePermission($ID, $userPermissions = null, $categoryID = -1, array $options = null)
	{
		if($categoryID < 0 && $ID > 0)
		{
			$categoryID = self::GetCategoryID($ID);
		}

		if($categoryID >= 0)
		{
			$categoryIDs = array($categoryID);
		}
		else
		{
			$categoryIDs = DealCategory::getAllIDs();
			if($ID > 0)
			{
				$ID = 0;
			}
		}

		$entityAttrs = $ID > 0 && is_array($options) && isset($options['ENTITY_ATTRS']) ? $options['ENTITY_ATTRS'] : null;
		foreach($categoryIDs as $curCategoryID)
		{
			$permissionEntity = DealCategory::convertToPermissionEntityType($curCategoryID);
			if(CCrmAuthorizationHelper::CheckUpdatePermission($permissionEntity, $ID, $userPermissions, $entityAttrs))
			{
				return true;
			}
		}
		return false;
	}

	public static function CheckDeletePermission($ID, $userPermissions = null, $categoryID = -1, array $options = null)
	{
		if(!($userPermissions instanceof CCrmPerms))
		{
			$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		}

		if($categoryID < 0 && $ID > 0)
		{
			$categoryID = self::GetCategoryID($ID);
		}

		if($categoryID >= 0)
		{
			$categoryIDs = array($categoryID);
		}
		else
		{
			$categoryIDs = DealCategory::getAllIDs();
			if($ID > 0)
			{
				$ID = 0;
			}
		}

		$entityAttrs = $ID > 0 && is_array($options) && isset($options['ENTITY_ATTRS']) ? $options['ENTITY_ATTRS'] : null;
		foreach($categoryIDs as $curCategoryID)
		{
			$permissionEntity = DealCategory::convertToPermissionEntityType($curCategoryID);
			if(CCrmAuthorizationHelper::CheckDeletePermission($permissionEntity, $ID, $userPermissions, $entityAttrs))
			{
				return true;
			}
		}
		return false;
	}

	public static function CheckReadPermission($ID = 0, $userPermissions = null, $categoryID = -1, array $options = null)
	{
		if(!($userPermissions instanceof CCrmPerms))
		{
			$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		}

		if($categoryID < 0 && $ID > 0)
		{
			$categoryID = self::GetCategoryID($ID);
		}

		if($categoryID >= 0)
		{
			$categoryIDs = array($categoryID);
		}
		else
		{
			$categoryIDs = DealCategory::getAllIDs();
			if($ID > 0)
			{
				$ID = 0;
			}
		}

		$entityAttrs = $ID > 0 && is_array($options) && isset($options['ENTITY_ATTRS']) ? $options['ENTITY_ATTRS'] : null;
		foreach($categoryIDs as $curCategoryID)
		{
			$permissionEntity = DealCategory::convertToPermissionEntityType($curCategoryID);
			if(CCrmAuthorizationHelper::CheckReadPermission($permissionEntity, $ID, $userPermissions, $entityAttrs))
			{
				return true;
			}
		}
		return false;
	}

	public static function CheckConvertPermission($ID = 0, $entityTypeID = 0, $userPermissions = null)
	{
		if(!$userPermissions)
		{
			$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		}

		if($entityTypeID === CCrmOwnerType::Invoice)
		{
			return CCrmInvoice::CheckCreatePermission($userPermissions);
		}
		elseif($entityTypeID === CCrmOwnerType::Quote)
		{
			return CCrmQuote::CheckCreatePermission($userPermissions);
		}

		return (CCrmInvoice::CheckCreatePermission($userPermissions)
			|| CCrmQuote::CheckCreatePermission($userPermissions));
	}

	public static function PrepareConversionPermissionFlags($ID, array &$params, $userPermissions = null)
	{
		if(!$userPermissions)
		{
			$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		}

		$canCreateInvoice = IsModuleInstalled('sale') && CCrmInvoice::CheckCreatePermission($userPermissions);
		$userPermissions = Container::getInstance()->getUserPermissions($userPermissions->GetUserID());
		$canCreateSmartInvoice = $userPermissions->checkAddPermissions(\CCrmOwnerType::SmartInvoice);
		$canCreateQuote = $userPermissions->checkAddPermissions(\CCrmOwnerType::Quote);

		$params['CAN_CONVERT_TO_INVOICE'] = $canCreateInvoice;
		$params['CAN_CONVERT_TO_SMART_INVOICE'] = $canCreateSmartInvoice;
		$params['CAN_CONVERT_TO_QUOTE'] = $canCreateQuote;
		$params['CAN_CONVERT'] = $params['CONVERT'] = ($canCreateInvoice || $canCreateQuote || $canCreateSmartInvoice);
		$params['CONVERSION_PERMITTED'] = true;
	}

	public static function ResolvePermissionEntityType($ID)
	{
		return DealCategory::convertToPermissionEntityType(self::GetCategoryID($ID));
	}

	public static function HasPermissionEntityType($permissionEntityType)
	{
		return DealCategory::hasPermissionEntity($permissionEntityType);
	}

	public static function PrepareFilter(&$arFilter, $arFilter2Logic = null)
	{
		if(!is_array($arFilter2Logic))
		{
			$arFilter2Logic = array('TITLE', 'COMMENTS');
		}

		static $arImmutableFilters = array(
			'FM', 'ID',
			'ASSIGNED_BY_ID', 'ASSIGNED_BY_ID_value',
			'CURRENCY_ID',
			'CONTACT_ID', 'CONTACT_ID_value', 'ASSOCIATED_CONTACT_ID',
			'COMPANY_ID', 'COMPANY_ID_value',
			'STAGE_SEMANTIC_ID',
			'CREATED_BY_ID', 'CREATED_BY_ID_value',
			'MODIFY_BY_ID', 'MODIFY_BY_ID_value',
			'PRODUCT_ROW_PRODUCT_ID', 'PRODUCT_ROW_PRODUCT_ID_value',
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

			if(in_array($k, array('PRODUCT_ID', 'TYPE_ID', 'STAGE_ID', 'COMPANY_ID', 'CONTACT_ID')))
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
			elseif (preg_match('/(.*)_from$/iu', $k, $arMatch))
			{
				if($v <> '')
				{
					$arFilter['>='.$arMatch[1]] = $v;
				}
				unset($arFilter[$k]);
			}
			elseif (preg_match('/(.*)_to$/iu', $k, $arMatch))
			{
				if($v <> '')
				{
					if (($arMatch[1] == 'DATE_CREATE' || $arMatch[1] == 'DATE_MODIFY') && !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/u', $v))
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

	public static function GetStages($categoryID = 0)
	{
		if(!is_int($categoryID))
		{
			$categoryID = (int)$categoryID;
		}
		$categoryID = max($categoryID, 0);

		if(!is_array(self::$DEAL_STAGES))
		{
			self::$DEAL_STAGES = array();
		}

		if(!isset(self::$DEAL_STAGES[$categoryID]))
		{
			self::$DEAL_STAGES[$categoryID] = DealCategory::getStageInfos($categoryID);
		}

		return self::$DEAL_STAGES[$categoryID];
	}

	public static function GetStageNames($categoryID = 0)
	{
		$result = array();
		foreach(self::GetStages($categoryID) as $stageID => $stage)
		{
			$result[$stageID] = $stage['NAME'];
		}
		return $result;
	}

	/**
	 * Get start Stage ID for specified Permission Type.
	 * If Permission Type is not defined permission check will be disabled.
	 * @param int $categoryID Category ID.
	 * @param int $permissionTypeID Permission Type (see \Bitrix\Crm\Security\EntityPermissionType).
	 * @param CCrmPerms $userPermissions User Permissions
	 * @return string
	 */
	public static function GetStartStageID($categoryID = 0, $permissionTypeID = 0, CCrmPerms $userPermissions = null)
	{
		$categoryID = (int)$categoryID;
		$stageIDs = array_keys(self::GetStages($categoryID));
		if(empty($stageIDs))
		{
			return '';
		}

		$permissionType = Bitrix\Crm\Security\EntityPermissionType::resolveName($permissionTypeID);
		if($permissionType === '')
		{
			return $stageIDs[0];
		}

		if($userPermissions === null)
		{
			$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		}

		$permissionEntity = DealCategory::convertToPermissionEntityType($categoryID);
		foreach($stageIDs as $stageID)
		{
			$permission = $userPermissions->GetPermType($permissionEntity, $permissionType, array("STAGE_ID{$stageID}"));
			if($permission !== BX_CRM_PERM_NONE)
			{
				return $stageID;
			}
		}
		return '';
	}

	public static function GetFinalStageID($categoryID = 0)
	{
		return self::GetSuccessStageID($categoryID);
	}

	public static function GetSuccessStageID($categoryID = 0)
	{
		$categoryID = (int)$categoryID;
		return DealCategory::prepareStageID($categoryID, 'WON');
	}

	public static function GetFailureStageID($categoryID = 0)
	{
		$categoryID = (int)$categoryID;
		return DealCategory::prepareStageID($categoryID, 'LOSE');
	}

	public static function GetFinalStageSort($categoryID = 0)
	{
		return self::GetStageSort(DealCategory::prepareStageID($categoryID, 'WON'), $categoryID);
	}

	public static function GetStageSort($stageID, $categoryID = -1)
	{
		$stageID = strval($stageID);
		if($stageID === '')
		{
			return -1;
		}

		if(!is_int($categoryID))
		{
			$categoryID = (int)$categoryID;
		}

		if($categoryID < 0)
		{
			$categoryID = DealCategory::resolveFromStageID($stageID);
		}

		$categoryID = (int)$categoryID;
		$stages = self::GetStages($categoryID);
		return isset($stages[$stageID]) && isset($stages[$stageID]['SORT']) ? (int)$stages[$stageID]['SORT'] : -1;
	}

	public static function IsStageExists($stageID, $categoryID = -1, array $params = [])
	{
		$stageID = (string)$stageID;
		$categoryID = (int)$categoryID;

		if($categoryID < 0)
		{
			$categoryID = DealCategory::resolveFromStageID($stageID);
		}

		if (isset($params['viewMode']) && $params['viewMode'] === ViewMode::MODE_ACTIVITIES)
		{
			$entityActivities = new Crm\Kanban\Entity\EntityActivities(\CCrmOwnerType::Deal, $categoryID);
			$stageList = array_flip(
				array_column($entityActivities->getStagesList($categoryID), 'STATUS_ID')
			);
		}
		else
		{
			$stageList = self::GetStages($categoryID);
		}

		return isset($stageList[$stageID]);
	}

	public static function GetStageName($stageID, $categoryID = -1)
	{
		return DealCategory::getStageName($stageID, $categoryID);
	}

	public static function GetStageSemantics($stageID, $categoryID = -1)
	{
		if($stageID === '')
		{
			return '';
		}

		if(!is_int($categoryID))
		{
			$categoryID = (int)$categoryID;
		}

		if($categoryID < 0)
		{
			$categoryID = DealCategory::resolveFromStageID($stageID);
		}

		if($stageID === DealCategory::prepareStageID($categoryID, 'WON'))
		{
			return 'success';
		}

		if($stageID === DealCategory::prepareStageID($categoryID, 'LOSE'))
		{
			return 'failure';
		}

		return (self::GetStageSort($stageID, $categoryID) > self::GetFinalStageSort($categoryID)) ? 'apology' : 'process';
	}

	public static function GetSemanticID($stageID, $categoryID = -1)
	{
		if (is_null($stageID))
		{
			if (!is_int($categoryID))
			{
				$categoryID = (int)$categoryID;
			}

			if ($categoryID < 0)
			{
				$categoryID = DealCategory::resolveFromStageID($stageID);
			}

			return (self::GetStageSort($stageID, $categoryID) > self::GetFinalStageSort($categoryID))
				? Bitrix\Crm\PhaseSemantics::FAILURE
				: Bitrix\Crm\PhaseSemantics::PROCESS
			;
		}

		if ($stageID === '')
		{
			return Bitrix\Crm\PhaseSemantics::UNDEFINED;
		}

		$semantics = Container::getInstance()
			->getFactory(\CCrmOwnerType::Deal)
			?->getStageSemantics($stageID)
		;

		return $semantics ?? Bitrix\Crm\PhaseSemantics::UNDEFINED;
	}

	public static function GetAllStageNames($categoryID = 0)
	{
		$result = array();
		$stages = self::GetStages($categoryID);
		foreach($stages as $stageID => $stage)
		{
			$result[$stageID] = $stage['NAME'];
		}
		return $result;
	}

	public static function MoveToCategory($ID, $newCategoryID, array $options = null)
	{
		if(!is_int($ID))
		{
			$ID = (int)$ID;
		}

		if($ID <= 0)
		{
			return DealCategoryChangeError::GENERAL;
		}

		if(!is_int($newCategoryID))
		{
			$newCategoryID = (int)$newCategoryID;
		}

		if($newCategoryID !== 0 && !DealCategory::exists($newCategoryID))
		{
			return DealCategoryChangeError::CATEGORY_NOT_FOUND;
		}

		if($options === null)
		{
			$options = array();
		}

		$dbResult = self::GetListEx(
			array(),
			array('ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'CATEGORY_ID', 'STAGE_ID', 'ASSIGNED_BY_ID', 'OPENED')
		);

		$fields = $dbResult->Fetch();
		if(!is_array($fields))
		{
			return DealCategoryChangeError::NOT_FOUND;
		}

		$categoryID = isset($fields['CATEGORY_ID']) ? (int)$fields['CATEGORY_ID'] : 0;
		if($categoryID === $newCategoryID)
		{
			return DealCategoryChangeError::CATEGORY_NOT_CHANGED;
		}

		$assignedByID = isset($fields['ASSIGNED_BY_ID']) ? (int)$fields['ASSIGNED_BY_ID'] : 0;
		if($assignedByID <= 0)
		{
			return DealCategoryChangeError::RESPONSIBLE_NOT_FOUND;
		}

		$stageID = $fields['STAGE_ID'] ?? '';
		if(empty($stageID))
		{
			$stageID = self::GetStartStageID($newCategoryID);
			if (empty($stageID))
			{
				return DealCategoryChangeError::STAGE_NOT_FOUND;
			}
		}

		$checkRunningBizProcess = !isset($options['ENABLE_WORKFLOW_CHECK']) || $options['ENABLE_WORKFLOW_CHECK'] === true;
		if($checkRunningBizProcess && \CCrmBizProcHelper::HasRunningWorkflows(CCrmOwnerType::Deal, $ID))
		{
			return DealCategoryChangeError::HAS_WORKFLOWS;
		}

		$processStageID = $options['PREFERRED_STAGE_ID'] ?? '';
		$event = new \Bitrix\Main\Event(
			'crm',
			'OnBeforeDealMoveToCategory',
			[
				'id' => $ID,
				'categoryId' => $newCategoryID,
				'stageId' => $processStageID,
			]
		);
		$event->send();
		/** @var @var \Bitrix\Main\EventResult $eventResult */
		foreach ($event->getResults() as $eventResult)
		{
			if ($eventResult->getType() === \Bitrix\Main\EventResult::ERROR)
			{
				return DealCategoryChangeError::USER_EVENT_FAILURE;
			}
			$parameters = $eventResult->getParameters();
			if (isset($parameters['categoryId']))
			{
				$newCategoryID = (int)$parameters['categoryId'];
				if($categoryID === $newCategoryID)
				{
					return DealCategoryChangeError::CATEGORY_NOT_CHANGED;
				}
				if($newCategoryID !== 0 && !DealCategory::exists($newCategoryID))
				{
					return DealCategoryChangeError::CATEGORY_NOT_FOUND;
				}
			}
			if (isset($parameters['stageId']))
			{
				$processStageID = $parameters['stageId'];
			}
		}

		$updateOperationRestriction = Crm\Restriction\RestrictionManager::getUpdateOperationRestriction(new Crm\ItemIdentifier(
			\CCrmOwnerType::Deal,
			(int)$ID
		));
		if (!$updateOperationRestriction->hasPermission())
		{
			return DealCategoryChangeError::RESTRICTION_APPLIED;
		}

		$successStageID = DealCategory::prepareStageID($newCategoryID, 'WON');
		$failureStageID = DealCategory::prepareStageID($newCategoryID, 'LOSE');
		$categoryStages = self::GetStages($newCategoryID);
		if (!$processStageID || !array_key_exists($processStageID, $categoryStages))
		{
			//Looking for first process stage ID
			foreach (array_keys($categoryStages) as $statusID)
			{
				if ($successStageID !== $statusID)
				{
					$processStageID = $statusID;
					break;
				}
			}

			if($processStageID === '')
			{
				$processStageID = DealCategory::prepareStageID($newCategoryID, 'NEW');
			}
		}

		$semanticID = self::GetSemanticID($stageID, $categoryID);
		$newStageID = $processStageID;
		if (!isset($options['PREFERRED_STAGE_ID']))
		{
			if ($semanticID === Bitrix\Crm\PhaseSemantics::SUCCESS)
			{
				$newStageID = $successStageID;
			}
			elseif ($semanticID === Bitrix\Crm\PhaseSemantics::FAILURE)
			{
				$newStageID = $failureStageID;
			}
		}
		$newStageSemanticID = self::GetSemanticID($newStageID, $newCategoryID);

		// Robots can provide user ID by options
		$userID = isset($options['USER_ID']) ? (int)$options['USER_ID'] : 0;
		if($userID <= 0)
		{
			$userID = Container::getInstance()->getContext()->getUserId();
		}

		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$escapedCategoryId = (int)$newCategoryID;
		$escapedStageId = $sqlHelper->forSql($newStageID);
		$escapedNewStageSemanticId = $sqlHelper->forSql($newStageSemanticID);
		$now = $sqlHelper->getCurrentDateTimeFunction();

		$currentFields = array_merge($fields, [
			'CATEGORY_ID' => $newCategoryID,
			'STAGE_ID' => $newStageID,
		]);

		$fieldsToUpdate = [
			'CATEGORY_ID' => $escapedCategoryId,
			'STAGE_ID' => $escapedStageId,
			'=DATE_MODIFY' => $now,
		];

		if ($userID > 0)
		{
			$fieldsToUpdate['MODIFY_BY_ID'] = $userID;
			$currentFields['MODIFY_BY_ID'] = $userID;
		}

		if ($semanticID != $newStageSemanticID)
		{
			$fieldsToUpdate['STAGE_SEMANTIC_ID'] = $escapedNewStageSemanticId;
		}

		DealTable::update($ID, $fieldsToUpdate);

		Bitrix\Crm\Timeline\DealController::getInstance()->onModify($ID, [
			'PREVIOUS_FIELDS' => $fields,
			'CURRENT_FIELDS' => $currentFields,
		]);

		//region Update Permissions
		$permissionEntityController = Crm\Security\Manager::getEntityController(CCrmOwnerType::Deal);
		$permissionEntityController->unregister(DealCategory::convertToPermissionEntityType($categoryID), $ID);

		self::clearCategoryCache($ID);
		self::clearStageCache($ID);
		Container::getInstance()->getDealBroker()?->deleteCache($ID);

		$entityAttrs = self::BuildEntityAttr(
			$assignedByID,
				array(
					'STAGE_ID' => $newStageID,
					'OPENED' => isset($fields['OPENED']) ? $fields['OPENED'] : 'N'
				)
		);
		$userPermissions = CCrmPerms::GetUserPermissions($assignedByID);
		$permissionEntityType = DealCategory::convertToPermissionEntityType($newCategoryID);
		self::PrepareEntityAttrs(
			$entityAttrs,
			$userPermissions->GetPermType($permissionEntityType, 'WRITE', $entityAttrs)
		);

		$securityRegisterOptions = (new \Bitrix\Crm\Security\Controller\RegisterOptions())
			->setEntityAttributes($entityAttrs)
			->setEntityFields($currentFields)
		;
		$permissionEntityController->register($permissionEntityType, $ID, $securityRegisterOptions);

		\Bitrix\Crm\Counter\Monitor::getInstance()->onEntityUpdate(
			CCrmOwnerType::Deal,
			$fields,
			$currentFields
		);
		//endregion
		if(!isset($options['REGISTER_STATISTICS']) || $options['REGISTER_STATISTICS'] === true)
		{
			DealStageHistoryEntry::processCagegoryChange($ID);
			DealSumStatisticEntry::processCagegoryChange($ID);
			DealInvoiceStatisticEntry::processCagegoryChange($ID);
			DealActivityStatisticEntry::processCagegoryChange($ID);
		}
		$timestamp = time() + CTimeZone::GetOffset();


		$eventEntity = new CCrmEvent();
		$eventEntity->Add(
			array(
				'USER_ID' => $userID,
				'ENTITY_ID' => $ID,
				'ENTITY_TYPE' => CCrmOwnerType::DealName,
				'EVENT_TYPE' => CCrmEvent::TYPE_CHANGE,
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_DEAL_CATEGORY'),
				'DATE_CREATE' => ConvertTimeStamp($timestamp, 'FULL', SITE_ID),
				'EVENT_TEXT_1' => DealCategory::getName($categoryID),
				'EVENT_TEXT_2' => DealCategory::getName($newCategoryID)
			),
			false
		);

		$event = new \Bitrix\Main\Event(
			'crm',
			'OnAfterDealMoveToCategory',
			[
				'id' => $ID,
				'categoryId' => $newCategoryID,
				'stageId' => $newStageID,
			]
		);
		$event->send();

		return DealCategoryChangeError::NONE;
	}

	public static function AddObserverIDs($ID, array $userIDs)
	{
		if(empty($userIDs))
		{
			return;
		}

		$observerIDs = array_unique(
			array_merge(
				Crm\Observer\ObserverManager::getEntityObserverIDs(CCrmOwnerType::Deal, $ID),
				$userIDs
			),
			SORT_NUMERIC
		);

		$fields = array('OBSERVER_IDS' => $observerIDs);
		$entity = new CCrmDeal(false);
		$entity->Update($ID,$fields);
	}

	public static function RemoveObserverIDs($ID, array $userIDs)
	{
		if(empty($userIDs))
		{
			return;
		}

		$observerIDs = array_diff(
			Crm\Observer\ObserverManager::getEntityObserverIDs(CCrmOwnerType::Deal, $ID),
			$userIDs
		);

		$fields = array('OBSERVER_IDS' => $observerIDs);
		$entity = new CCrmDeal(false);
		$entity->Update($ID, $fields);
	}

	public static function ReplaceObserverIDs($ID, array $userIDs)
	{
		if(empty($userIDs))
		{
			return;
		}

		$fields = array('OBSERVER_IDS' => $userIDs);
		$entity = new CCrmDeal(false);
		$entity->Update($ID, $fields);
	}

	public static function PullChange($type, $arParams)
	{
		if(!CModule::IncludeModule('pull'))
		{
			return;
		}

		$type = strval($type);
		if($type === '')
		{
			$type = 'update';
		}
		else
		{
			$type = mb_strtolower($type);
		}

		CPullWatch::AddToStack(
			'CRM_DEAL_CHANGE',
			array(
				'module_id'  => 'crm',
				'command'    => "crm_deal_{$type}",
				'params'     => $arParams
			)
		);
	}

	public static function GetCount($arFilter)
	{
		$fields = self::GetFields();
		return CSqlUtil::GetCount(CCrmDeal::TABLE_NAME, self::TABLE_ALIAS, $fields, $arFilter);
	}
	public static function Rebind($ownerTypeID, $oldID, $newID)
	{
		global $DB;

		$ownerTypeID = intval($ownerTypeID);
		$oldID = intval($oldID);
		$newID = intval($newID);
		$tableName = CCrmDeal::TABLE_NAME;

		if($ownerTypeID === CCrmOwnerType::Contact)
		{
			$DB->Query(
				"UPDATE {$tableName} SET CONTACT_ID = {$newID} WHERE CONTACT_ID = {$oldID}"
			);
		}
		elseif($ownerTypeID === CCrmOwnerType::Company)
		{
			$DB->Query(
				"UPDATE {$tableName} SET COMPANY_ID = {$newID} WHERE COMPANY_ID = {$oldID}"
			);
		}
	}
	public static function FindByCommunication($entityTypeID, $type, $value, $checkPermissions = true, array $select = null, array $order = null)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if($entityTypeID !== CCrmOwnerType::Contact && $entityTypeID !== CCrmOwnerType::Company)
		{
			return array();
		}

		$criterion = new \Bitrix\Crm\Integrity\DuplicateCommunicationCriterion($type, $value);
		/** @var \Bitrix\Crm\Integrity\Duplicate $duplicate */
		$duplicate = $criterion->find($entityTypeID, 20);
		if($duplicate === null)
		{
			return array();
		}

		$entityIDs = array();
		$entities = $duplicate->getEntities();
		foreach($entities as $entity)
		{
			/** @var \Bitrix\Crm\Integrity\DuplicateEntity $entity */
			$entityIDs[] = $entity->getEntityID();
		}
		//return $entityMap;

		if($select === null)
		{
			$select = array();
		}

		if($order === null)
		{
			$order = array();
		}

		if(empty($entityIDs))
		{
			return array();
		}

		if($entityTypeID === CCrmOwnerType::Contact)
		{
			$filter = array('@CONTACT_ID' => $entityIDs);
		}
		else//($entityTypeID === CCrmOwnerType::Company)
		{
			$filter = array('@COMPANY_ID' => $entityIDs);
		}

		if (!$checkPermissions)
		{
			$filter['CHECK_PERMISSIONS'] = 'N';
		}

		$results = array();
		$dbResult = self::GetListEx($order, $filter, false, false, $select);
		if(is_object($dbResult))
		{
			while($filelds = $dbResult->Fetch())
			{
				$results[] = $filelds;
			}
		}
		return $results;
	}

	public static function RebuildSemantics(array $IDs, array $options = null)
	{
		$dbResult = self::GetListEx(
			array(),
			array('@ID' => $IDs, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'STAGE_SEMANTIC_ID', 'STAGE_ID', 'CATEGORY_ID')
		);

		if(!is_object($dbResult))
		{
			return;
		}

		$entity = new CCrmDeal(false);
		$forced = is_array($options) && isset($options['FORCED']) ? $options['FORCED'] : false;
		while($fields = $dbResult->Fetch())
		{
			$ID = (int)$fields['ID'];

			if(isset($fields['STAGE_SEMANTIC_ID']) && !$forced)
			{
				continue;
			}

			$updateFields = array('STAGE_ID' => isset($fields['STAGE_ID']) ? $fields['STAGE_ID'] : '');
			$entity->Update(
				$ID,
				$updateFields,
				false,
				false,
				array(
					'SYNCHRONIZE_STAGE_SEMANTICS' => true,
					'REGISTER_SONET_EVENT' => false,
					'ENABLE_SYSTEM_EVENTS' => false,
					'IS_SYSTEM_ACTION' => true
				)
			);
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
				'ID', 'DATE_CREATE', 'DATE_MODIFY', 'STAGE_ID', 'CATEGORY_ID',
				'ASSIGNED_BY_ID', 'BEGINDATE', 'CLOSEDATE', 'IS_RECURRING',
				'CURRENCY_ID', 'OPPORTUNITY', 'UF_*'
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
		$enableInvoiceStatistics = isset($options['ENABLE_INVOICE_STATISTICS']) ? $options['ENABLE_INVOICE_STATISTICS'] : true;
		$enableActivityStatistics = isset($options['ENABLE_ACTIVITY_STATISTICS']) ? $options['ENABLE_ACTIVITY_STATISTICS'] : true;

		while($fields = $dbResult->Fetch())
		{
			$ID = (int)$fields['ID'];
			//--> History
			if($enableHistory && ($forced || !DealStageHistoryEntry::isRegistered($ID)))
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

				if($createdTime && $modifiedTime && $createdTime->getTimestamp() !== $modifiedTime->getTimestamp())
				{
					DealStageHistoryEntry::register(
						$ID,
						$fields,
						array('IS_NEW' => false, 'TIME' => $modifiedTime, 'FORCED' => $forced)
					);
				}
				elseif($createdTime)
				{
					DealStageHistoryEntry::register(
						$ID,
						$fields,
						array('IS_NEW' => true, 'TIME' => $createdTime, 'FORCED' => $forced)
					);
				}
			}
			//<-- History
			//--> Statistics
			if ($fields['IS_RECURRING'] !== 'Y')
			{
				if($enableSumStatistics && ($forced || !DealSumStatisticEntry::isRegistered($ID)))
				{
					DealSumStatisticEntry::register($ID, $fields, array('FORCED' => $forced));
				}

				if($enableInvoiceStatistics && ($forced || !DealInvoiceStatisticEntry::isRegistered($ID)))
				{
					DealInvoiceStatisticEntry::register($ID, $fields);
				}

				if($enableActivityStatistics)
				{
					$timeline = DealActivityStatisticEntry::prepareTimeline($ID);
					foreach($timeline as $date)
					{
						DealActivityStatisticEntry::register($ID, $fields, array('FORCED' => $forced, 'DATE' => $date));
					}
				}
			}
			//<-- Statistics
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

		$entity = new CCrmDeal(false);
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

	public static function ProcessContactDeletion($contactID)
	{
		global $DB;
		$DB->Query("UPDATE b_crm_deal SET CONTACT_ID = NULL WHERE CONTACT_ID = {$contactID}");

		\Bitrix\Crm\Binding\DealContactTable::unbindAllDeals($contactID);
	}
	public static function ProcessCompanyDeletion($companyID)
	{
		global $DB;
		$DB->Query("UPDATE b_crm_deal SET COMPANY_ID = NULL WHERE COMPANY_ID = {$companyID}");
	}
	public static function ProcessLeadDeletion($leadID)
	{
		global $DB;
		$DB->Query("UPDATE b_crm_deal SET LEAD_ID = NULL WHERE LEAD_ID = {$leadID}");
	}
	/**
	 * @deprecated
	 * @param array $fields
	 */
	public static function ProcessStatusModification(array $fields)
	{
		$entityID = isset($fields['ENTITY_ID']) ? $fields['ENTITY_ID'] : '';
		$statusID = isset($fields['STATUS_ID']) ? $fields['STATUS_ID'] : '';

		if(($entityID === 'DEAL_STAGE' || preg_match("/DEAL_STAGE_\d+/", $entityID) == 1) && $statusID !== '')
		{
			$categoryID = Crm\Category\DealCategory::convertFromStatusEntityID($entityID);
			Crm\Attribute\FieldAttributeManager::processPhaseModification(
				$statusID,
				\CCrmOwnerType::Deal,
				Crm\Attribute\FieldAttributeManager::resolveEntityScope(
					\CCrmOwnerType::Deal,
					0,
					array('CATEGORY_ID' => $categoryID)
				),
				Crm\Category\DealCategory::getStageInfos($categoryID)
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

		if(($entityID === 'DEAL_STAGE' || preg_match("/DEAL_STAGE_\d+/", $entityID) == 1) && $statusID !== '')
		{
			$categoryID = Crm\Category\DealCategory::convertFromStatusEntityID($entityID);
			Crm\Attribute\FieldAttributeManager::processPhaseDeletion(
				$statusID,
				\CCrmOwnerType::Deal,
				Crm\Attribute\FieldAttributeManager::resolveEntityScope(
					\CCrmOwnerType::Deal,
					0,
					array('CATEGORY_ID' => $categoryID)
				)
			);
		}
	}

	public static function GetDefaultTitleTemplate()
	{
		return GetMessage('CRM_DEAL_DEFAULT_TITLE_TEMPLATE');
	}

	public static function GetDefaultTitle($number = '')
	{
		return GetMessage('CRM_DEAL_DEFAULT_TITLE_TEMPLATE', array('%NUMBER%' => $number));
	}

	public static function existsEntityWithStatus($stageId)
	{
		$queryObject = self::getListEx(
			['ID' => 'DESC'],
			['STAGE_ID' => $stageId, 'CHECK_PERMISSIONS' => 'N'],
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

	private static function getClientUFSqlData(
		array $order,
		array $filter,
		int $entityTypeId
	): array
	{
		$entities = [
			CCrmOwnerType::Contact => [
				'FIELD_PREFIX' => 'CONTACT_UF_',
				'DEAL_FIELD_NAME' => 'CONTACT_ID',
				'TABLE' => CCrmContact::TABLE_NAME,
				'TABLE_ALIAS' => 'C',
				'UF_ENTITY_ID' => CCrmContact::GetUserFieldEntityID(),
			],
			CCrmOwnerType::Company => [
				'FIELD_PREFIX' => 'COMPANY_UF_',
				'DEAL_FIELD_NAME' => 'COMPANY_ID',
				'TABLE' => CCrmCompany::TABLE_NAME,
				'TABLE_ALIAS' => 'CO',
				'UF_ENTITY_ID' => CCrmCompany::GetUserFieldEntityID(),
			],
		];
		if (!isset($entities[$entityTypeId]))
		{
			throw new \Bitrix\Main\NotSupportedException();
		}
		$entity = $entities[$entityTypeId];

		$fieldPrefix = $entity['FIELD_PREFIX'];
		$dealTableFieldName = self::TABLE_ALIAS . '.' .$entity['DEAL_FIELD_NAME'];
		$clientTableName = $entity['TABLE'];
		$clientTableAlias = $entity['TABLE_ALIAS'];
		$clientUFEntityId = $entity['UF_ENTITY_ID'];

		$ufFilterSql = null;
		$ufFilter = [];
		$sqlWhere = new CSQLWhere();
		foreach ($filter as $filterKey => $filterValue)
		{
			$filterFieldName = $sqlWhere->MakeOperation($filterKey)['FIELD'];
			if (mb_strpos($filterFieldName, $fieldPrefix) !== 0)
			{
				continue;
			}
			$filterFieldNamePos = mb_strpos($filterKey, $fieldPrefix);
			$filterKey =
				($filterFieldNamePos > 0 ? mb_substr($filterKey, 0, $filterFieldNamePos) : '')
				. 'UF_'
				. mb_substr($filterKey, $filterFieldNamePos + mb_strlen($fieldPrefix));

			//Adapt nested filters for UserTypeSQL
			if (mb_strpos($filterKey, '__INNER_FILTER') === 0)
			{
				$ufFilter[] = $filterValue;
			}
			else
			{
				$ufFilter[$filterKey] = $filterValue;
			}
		}
		if (!empty($ufFilter))
		{
			$userType = new CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], $clientUFEntityId);
			$userType->ListPrepareFilter($ufFilter);

			$ufFilterSql = new CUserTypeSQL();
			$ufFilterSql->SetEntity($clientUFEntityId, $clientTableAlias . '.ID');
			$ufFilterSql->SetFilter($ufFilter);
		}

		$ufOrderSql = null;
		$ufOrder = [];
		foreach ($order as $orderField => $orderDirection)
		{
			if (mb_strpos($orderField, $fieldPrefix) !== 0)
			{
				continue;
			}
			$orderField = 'UF_' . mb_substr($orderField, mb_strlen($fieldPrefix));

			$ufOrder[$orderField] = $orderDirection;
		}
		if (!empty($ufOrder))
		{
			$ufOrderSql = new CUserTypeSQL();
			$ufOrderSql->SetEntity($clientUFEntityId, $clientTableAlias . '.ID');
			$ufOrderSql->SetOrder($ufOrder);
			$ufOrderSql->table_alias .= $clientTableAlias;
		}

		$result = [];
		if ($ufFilterSql)
		{
			// Adding user fields to WHERE
			$ufWhere = $ufFilterSql->GetFilter();
			if ($ufWhere !== '')
			{
				$ufSql = $dealTableFieldName . ' IN (SELECT '
					. $clientTableAlias . '.ID FROM ' . $clientTableName . ' ' . $clientTableAlias . ' '
					. $ufFilterSql->GetJoin($clientTableAlias . '.ID') . ' WHERE ' . $ufWhere . ')';

				// Adding user fields to joins
				$result['WHERE'][] = $ufSql;
			}
		}

		if ($ufOrderSql)
		{
			$ufOrderFound = false;

			foreach ($ufOrder as $orderField => $orderDirection)
			{
				$orderDirection = mb_strtoupper($orderDirection);
				if (!in_array($orderDirection, ['ASC', 'DESC'], true))
				{
					$orderDirection = 'ASC';
				}
				$ufOrderField = $ufOrderSql->GetOrder($orderField);
				if ($ufOrderField !== '')
				{
					$result['ORDERBY'][] = $ufOrderSql->GetOrder($orderField) . ' ' . $orderDirection;
					$ufOrderFound =- true;
				}
			}
			if ($ufOrderFound)
			{
				$result['FROM'][] = $ufOrderSql->GetJoin($dealTableFieldName);
			}
		}

		return $result;
	}

	public function getLastError(): string
	{
		return (string)$this->LAST_ERROR;
	}

	public static function filterIdsByReadPermission(array $ids, int $userId): array
	{
		if (empty($ids))
		{
			return [];
		}

		$userPermissions = Container::getInstance()->getUserPermissions($userId);
		if ($userPermissions->isAdmin())
		{
			return $ids;
		}

		$entityTypeIds = [];
		$categoryIDs = DealCategory::getAllIDs();
		foreach ($categoryIDs as $curCategoryID) {
			$entityTypeIds[] = DealCategory::convertToPermissionEntityType($curCategoryID);
		}

		$queryBuilderFactory = \Bitrix\Crm\Security\QueryBuilderFactory::getInstance();

		$qb = $queryBuilderFactory->make(
			$entityTypeIds,
			$userPermissions,
		);

		$builderResult = $qb->build();

		if (!$builderResult->hasAccess())
		{
			return [];
		}

		$query = \Bitrix\Crm\DealTable::query()
			->setSelect(['ID'])
			->registerRuntimeField('permission',
				new \Bitrix\Main\Entity\ReferenceField(
					'ENTITY',
					$builderResult->getEntity(),
					$builderResult->getOrmConditions(),
					['join_type' => 'INNER']
				)
			)
			->whereIn('ID', $ids);

		return array_column($query->fetchAll(), 'ID');
	}
}
