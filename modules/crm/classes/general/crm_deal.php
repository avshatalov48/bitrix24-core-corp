<?php
IncludeModuleLangFile(__FILE__);

use Bitrix\Crm;
use Bitrix\Crm\CustomerType;
use Bitrix\Crm\UtmTable;
use Bitrix\Crm\Tracking;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Category\DealCategoryChangeError;
use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\Binding\DealContactTable;
use Bitrix\Crm\Settings\DealSettings;
use Bitrix\Crm\Settings\HistorySettings;
use Bitrix\Crm\History\DealStageHistoryEntry;
use Bitrix\Crm\Statistics\DealSumStatisticEntry;
use Bitrix\Crm\Statistics\DealInvoiceStatisticEntry;
use Bitrix\Crm\Statistics\LeadConversionStatisticsEntry;
use Bitrix\Crm\Statistics\DealActivityStatisticEntry;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\Counter\EntityCounterManager;
use Bitrix\Crm\Integration\Channel\DealChannelBinding;

class CAllCrmDeal
{
	static public $sUFEntityID = 'CRM_DEAL';
	const USER_FIELD_ENTITY_ID = 'CRM_DEAL';
	const SUSPENDED_USER_FIELD_ENTITY_ID = 'CRM_DEAL_SPD';
	const TOTAL_COUNT_CACHE_ID =  'crm_deal_total_count';

	public $LAST_ERROR = '';
	protected $checkExceptions = array();

	public $cPerms = null;
	protected $bCheckPermission = true;
	const TABLE_ALIAS = 'L';
	protected static $TYPE_NAME = 'DEAL';
	private static $DEAL_STAGES = null;
	private static $FIELD_INFOS = null;

	function __construct($bCheckPermission = true)
	{
		$this->bCheckPermission = $bCheckPermission;
		$this->cPerms = CCrmPerms::GetCurrentUserPermissions();
	}

	// Service -->
	public static function GetFieldCaption($fieldName)
	{
		$result = GetMessage("CRM_DEAL_FIELD_{$fieldName}");
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
				'TYPE_ID' => array(
					'TYPE' => 'crm_status',
					'CRM_STATUS_TYPE' => 'DEAL_TYPE'
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
					'TYPE' => 'crm_company'
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
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'BEGINDATE' => array(
					'TYPE' => 'date'
				),
				'CLOSEDATE' => array(
					'TYPE' => 'date'
				),
				'OPENED' => array(
					'TYPE' => 'char'
				),
				'CLOSED' => array(
					'TYPE' => 'char'
				),
				'COMMENTS' => array(
					'TYPE' => 'string'
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
				'SOURCE_ID' => array(
					'TYPE' => 'crm_status',
					'CRM_STATUS_TYPE' => 'SOURCE'
				),
				'SOURCE_DESCRIPTION' => array(
					'TYPE' => 'string'
				),
				'LEAD_ID' => array(
					'TYPE' => 'crm_lead',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
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
				)
			);

			// add utm fields
			self::$FIELD_INFOS = self::$FIELD_INFOS + UtmTable::getUtmFieldsInfo();
		}

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
			'PROBABILITY' => array('FIELD' => 'L.PROBABILITY', 'TYPE' => 'int'),
			'CURRENCY_ID' => array('FIELD' => 'L.CURRENCY_ID', 'TYPE' => 'string'),
			'EXCH_RATE' => array('FIELD' => 'L.EXCH_RATE', 'TYPE' => 'double'),
			'OPPORTUNITY' => array('FIELD' => 'L.OPPORTUNITY', 'TYPE' => 'double'),
			'TAX_VALUE' => array('FIELD' => 'L.TAX_VALUE', 'TYPE' => 'double'),
			'ACCOUNT_CURRENCY_ID' => array('FIELD' => 'L.ACCOUNT_CURRENCY_ID', 'TYPE' => 'string'),
			'OPPORTUNITY_ACCOUNT' => array('FIELD' => 'L.OPPORTUNITY_ACCOUNT', 'TYPE' => 'double'),
			'TAX_VALUE_ACCOUNT' => array('FIELD' => 'L.TAX_VALUE_ACCOUNT', 'TYPE' => 'double'),

			'LEAD_ID' => array('FIELD' => 'L.LEAD_ID', 'TYPE' => 'int'),
			'COMPANY_ID' => array('FIELD' => 'L.COMPANY_ID', 'TYPE' => 'int'),
			'COMPANY_TITLE' => array('FIELD' => 'CO.TITLE', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'COMPANY_INDUSTRY' => array('FIELD' => 'CO.INDUSTRY', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'COMPANY_EMPLOYEES' => array('FIELD' => 'CO.EMPLOYEES', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'COMPANY_REVENUE' => array('FIELD' => 'CO.REVENUE', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'COMPANY_CURRENCY_ID' => array('FIELD' => 'CO.CURRENCY_ID', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'COMPANY_TYPE' => array('FIELD' => 'CO.COMPANY_TYPE', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'COMPANY_ADDRESS' => array('FIELD' => 'CO.ADDRESS', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'COMPANY_ADDRESS_LEGAL' => array('FIELD' => 'CO.ADDRESS_LEGAL', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'COMPANY_BANKING_DETAILS' => array('FIELD' => 'CO.BANKING_DETAILS', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'COMPANY_LOGO' => array('FIELD' => 'CO.LOGO', 'TYPE' => 'string', 'FROM' => $companyJoin),

			'CONTACT_ID' => array('FIELD' => 'L.CONTACT_ID', 'TYPE' => 'int'),
			'CONTACT_TYPE_ID' => array('FIELD' => 'C.TYPE_ID', 'TYPE' => 'string', 'FROM' => $contactJoin),
			'CONTACT_HONORIFIC' => array('FIELD' => 'C.HONORIFIC', 'TYPE' => 'string', 'FROM' => $contactJoin),
			'CONTACT_NAME' => array('FIELD' => 'C.NAME', 'TYPE' => 'string', 'FROM' => $contactJoin),
			'CONTACT_SECOND_NAME' => array('FIELD' => 'C.SECOND_NAME', 'TYPE' => 'string', 'FROM' => $contactJoin),
			'CONTACT_LAST_NAME' => array('FIELD' => 'C.LAST_NAME', 'TYPE' => 'string', 'FROM' => $contactJoin),
			'CONTACT_FULL_NAME' => array('FIELD' => 'C.FULL_NAME', 'TYPE' => 'string', 'FROM' => $contactJoin),

			'CONTACT_POST' => array('FIELD' => 'C.POST', 'TYPE' => 'string', 'FROM' => $contactJoin),
			'CONTACT_ADDRESS' => array('FIELD' => 'C.ADDRESS', 'TYPE' => 'string', 'FROM' => $contactJoin),
			'CONTACT_SOURCE_ID' => array('FIELD' => 'C.SOURCE_ID', 'TYPE' => 'string', 'FROM' => $contactJoin),
			'CONTACT_PHOTO' => array('FIELD' => 'C.PHOTO', 'TYPE' => 'string', 'FROM' => $contactJoin),

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

			// For compatibility only
			'PRODUCT_ID' => array('FIELD' => 'L.PRODUCT_ID', 'TYPE' => 'string'),
			// Obsolete
			'EVENT_ID' => array('FIELD' => 'L.EVENT_ID', 'TYPE' => 'string'),
			'EVENT_DATE' => array('FIELD' => 'L.EVENT_DATE', 'TYPE' => 'datetime'),
			'EVENT_DESCRIPTION' => array('FIELD' => 'L.EVENT_DESCRIPTION', 'TYPE' => 'string')
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

				$userID = CCrmPerms::GetCurrentUserID();
				if($userID > 0)
				{
					$activityJoin = CCrmActivity::PrepareJoin($userID, CCrmOwnerType::Deal, 'L', 'A', 'UA', '');

					$result['ACTIVITY_ID'] = array('FIELD' => 'UA.ACTIVITY_ID', 'TYPE' => 'int', 'FROM' => $activityJoin);
					$result['ACTIVITY_TIME'] = array('FIELD' => 'UA.ACTIVITY_TIME', 'TYPE' => 'datetime', 'FROM' => $activityJoin);
					$result['ACTIVITY_SUBJECT'] = array('FIELD' => 'A.SUBJECT', 'TYPE' => 'string', 'FROM' => $activityJoin);
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

		// add utm fields
		$result = array_merge($result, UtmTable::getFieldsDescriptionByEntityTypeId(CCrmOwnerType::Deal));

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
			$supposedHistoryCaseSql = "L.ID IN (SELECT DISTINCT DSHWS.OWNER_ID FROM b_crm_deal_stage_history_with_supposed DSHWS WHERE ";
			$supposedHistoryCaseSqlWhereStarted = false;

			if (!empty($arFilter['ACTIVE_TIME_PERIOD_from']) && !empty($arFilter['ACTIVE_TIME_PERIOD_to']))
			{
				$supposedHistoryCaseSql .= "DSHWS.LAST_UPDATE_DATE <= ".
										   $DB->CharToDateFunction($arFilter['ACTIVE_TIME_PERIOD_to'], 'SHORT');
				$supposedHistoryCaseSql .= " AND DSHWS.CLOSE_DATE >= ".
										   $DB->CharToDateFunction($arFilter['ACTIVE_TIME_PERIOD_from'], 'SHORT');
				$supposedHistoryCaseSqlWhereStarted = true;
			}


			if (!empty($arFilter['%STAGE_SEMANTIC_ID_FROM_HISTORY']))
			{
				$stageSemanticIdsFromFilter = is_array($arFilter['%STAGE_SEMANTIC_ID_FROM_HISTORY']) ? $arFilter['%STAGE_SEMANTIC_ID_FROM_HISTORY'] : array($arFilter['%STAGE_SEMANTIC_ID_FROM_HISTORY']);
				$supposedHistoryCaseSql .= $supposedHistoryCaseSqlWhereStarted ? ' AND ' : ' ';
				$supposedHistoryCaseSql  .= " DSHWS.IS_SUPPOSED = 'N' AND DSHWS.STAGE_SEMANTIC_ID  IN (" . implode(',', array_map(function($el) {return "'" . $el . "'";}, $stageSemanticIdsFromFilter)) . ")";
				$supposedHistoryCaseSqlWhereStarted = true;
			}


			if (!empty($arFilter['%STAGE_ID_FROM_HISTORY']))
			{
				$statusIdsFromFilter = is_array($arFilter['%STAGE_ID_FROM_HISTORY']) ? $arFilter['%STAGE_ID_FROM_HISTORY'] : array($arFilter['%STAGE_ID_FROM_HISTORY']);
				$supposedHistoryCaseSql .= $supposedHistoryCaseSqlWhereStarted ? ' AND ' : ' ';
				$supposedHistoryCaseSql .= " DSHWS.IS_SUPPOSED = 'N' AND DSHWS.STAGE_ID  IN (" . implode(',', array_map(function($el) {return "'" . $el . "'";}, $statusIdsFromFilter)) . ")";
				$supposedHistoryCaseSqlWhereStarted = true;
			}


			if (!empty($arFilter['%STAGE_ID_FROM_SUPPOSED_HISTORY']))
			{
				$statusIdsFromFilter = is_array($arFilter['%STAGE_ID_FROM_SUPPOSED_HISTORY']) ? $arFilter['%STAGE_ID_FROM_SUPPOSED_HISTORY'] : array($arFilter['%STAGE_ID_FROM_SUPPOSED_HISTORY']);
				$supposedHistoryCaseSql .= $supposedHistoryCaseSqlWhereStarted ? ' AND ' : ' ';
				$supposedHistoryCaseSql .= " DSHWS.STAGE_ID  IN (" . implode(',', array_map(function($el) {return "'" . $el . "'";}, $statusIdsFromFilter)) . ")";
				$supposedHistoryCaseSqlWhereStarted = true;
			}

			$supposedHistoryCaseSql .=" )";

			$sqlData['WHERE'][] = $supposedHistoryCaseSql;
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
				list($ufId, $ufType, $ufName) = \Bitrix\Crm\Integration\Calendar::parseUserfieldKey($arFilter['CALENDAR_FIELD']);

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

					$sqlData['WHERE'][] = CDatabase::ForSql($ufName)." <= ".$DB->CharToDateFunction($arFilter['CALENDAR_DATE_TO'], 'SHORT');
					$sqlData['WHERE'][] = CDatabase::ForSql($ufName)." >= ".$DB->CharToDateFunction($arFilter['CALENDAR_DATE_FROM'], 'SHORT');
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
				$sqlData['WHERE'][] = "{$tableAlias}.ID IN (SELECT DP.OWNER_ID from b_crm_product_row DP where DP.OWNER_TYPE = 'D' and DP.OWNER_ID = {$tableAlias}.ID and DP.PRODUCT_ID = {$prodID})";
			}
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
		return self::$sUFEntityID;
	}

	public static function GetUserFields($langID = false)
	{
		global $USER_FIELD_MANAGER;
		return $USER_FIELD_MANAGER->GetUserFields(self::$sUFEntityID, 0, $langID);
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

		$checkPermissions = true;
		if(isset($arFilter['CHECK_PERMISSIONS']))
		{
			$checkPermissions = $arFilter['CHECK_PERMISSIONS'] !== 'N';
		}

		if($checkPermissions)
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
					$dbResult = new CDBResult();
					$dbResult->InitFromArray(array());
					return $dbResult;
				}

				$arFilter['CHECK_PERMISSIONS'] = 'N';
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
		if(is_array($operationInfo) && $operationInfo['OPERATION'] === '=')
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
			$field = strtoupper($field);
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
			'DATE_MODIFY' => 'L.DATE_MODIFY'
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

		$obRes = $DB->Query($sSql, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
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
			? $connection->getSqlHelper()->getTopSql("SELECT ID FROM b_crm_deal ORDER BY ID {$sortType}", $top)
			: "SELECT L.ID FROM b_crm_deal L INNER JOIN ($permissionSql) LP ON L.ID = LP.ENTITY_ID";
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
		if(defined('BX_COMP_MANAGED_CACHE') && $GLOBALS['CACHE_MANAGER']->Read(600, self::TOTAL_COUNT_CACHE_ID, 'b_crm_deal'))
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

	static public function BuildPermSql($sAliasPrefix = 'L', $mPermType = 'READ', $arOptions = array())
	{
		if(isset($arOptions['RESTRICT_BY_ENTITY_TYPES'])
			&& is_array($arOptions['RESTRICT_BY_ENTITY_TYPES'])
			&& !empty($arOptions['RESTRICT_BY_ENTITY_TYPES'])
		)
		{
			$entityTypes = $arOptions['RESTRICT_BY_ENTITY_TYPES'];
		}
		else
		{
			$entityTypes = array_merge(array('DEAL'), DealCategory::getPermissionEntityTypeList());
		}

		return CCrmPerms::BuildSqlForEntitySet(
			$entityTypes,
			$sAliasPrefix,
			$mPermType,
			$arOptions
		);
	}

	public function Add(array &$arFields, $bUpdateSearch = true, $options = array())
	{
		global $DB;

		if(!is_array($options))
		{
			$options = array();
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

		if(!isset($arFields['TITLE']) || trim($arFields['TITLE']) === '')
		{
			$arFields['TITLE'] = self::GetDefaultTitle();
		}

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

			//region Category
			$categoryID = isset($arFields['CATEGORY_ID']) ? max((int)$arFields['CATEGORY_ID'], 0) : 0;
			if($categoryID > 0 && !DealCategory::isEnabled($categoryID))
			{
				$categoryID = 0;
			}
			$arFields['CATEGORY_ID'] = $categoryID;
			//endregion

			//region StageID, SemanticID and IsNew
			if (!isset($arFields['STAGE_ID']))
			{
				$arFields['STAGE_ID'] = self::GetStartStageID(
					$categoryID,
					$this->bCheckPermission
						? Bitrix\Crm\Security\EntityPermissionType::CREATE
						: Bitrix\Crm\Security\EntityPermissionType::UNDEFINED
				);
			}
			$arFields['STAGE_SEMANTIC_ID'] = self::IsStageExists($arFields['STAGE_ID'], $categoryID)
				? self::GetSemanticID($arFields['STAGE_ID'], $categoryID)
				: Bitrix\Crm\PhaseSemantics::UNDEFINED;

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

			// Calculation of Account Data
			$accData = CCrmAccountingHelper::PrepareAccountingData(
				array(
					'CURRENCY_ID' => $arFields['CURRENCY_ID'],
					'SUM' => isset($arFields['OPPORTUNITY']) ? $arFields['OPPORTUNITY'] : null,
					'EXCH_RATE' => $arFields['EXCH_RATE']
				)
			);
			if(is_array($accData))
			{
				$arFields['ACCOUNT_CURRENCY_ID'] = $accData['ACCOUNT_CURRENCY_ID'];
				$arFields['OPPORTUNITY_ACCOUNT'] = $accData['ACCOUNT_SUM'];
			}

			// Calculation of Tax Account Data
			$accData = CCrmAccountingHelper::PrepareAccountingData(
				array(
					'CURRENCY_ID' => $arFields['CURRENCY_ID'],
					'SUM' => isset($arFields['TAX_VALUE']) ? $arFields['TAX_VALUE'] : null,
					'EXCH_RATE' => $arFields['EXCH_RATE']
				)
			);
			if(is_array($accData))
			{
				$arFields['TAX_VALUE_ACCOUNT'] = $accData['ACCOUNT_SUM'];
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
			$ID = intval($DB->Add('b_crm_deal', $arFields, array(), 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__));
			//Append ID to TITLE if required
			if($ID > 0 && $arFields['TITLE'] === self::GetDefaultTitle())
			{
				$arFields['TITLE'] = self::GetDefaultTitle($ID);
				$sUpdate = $DB->PrepareUpdate('b_crm_deal', array('TITLE' => $arFields['TITLE']));
				if(strlen($sUpdate) > 0)
				{
					$DB->Query("UPDATE b_crm_deal SET {$sUpdate} WHERE ID = {$ID}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
				};
			}

			if(defined('BX_COMP_MANAGED_CACHE'))
			{
				$GLOBALS['CACHE_MANAGER']->CleanDir('b_crm_deal');
			}

			$result = $arFields['ID'] = $ID;

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

			CCrmPerms::UpdateEntityAttr($permissionEntityType, $ID, $arEntityAttr);

			//region Save contacts
			if(!empty($contactBindings))
			{
				DealContactTable::bindContacts($ID, $contactBindings);
				if (isset($GLOBALS['USER']))
				{
					if (!class_exists('CUserOptions'))
						include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/classes/'.$GLOBALS['DBType'].'/favorites.php');

					CUserOptions::SetOption(
						'crm',
						'crm_contact_search',
						array('last_selected' => $contactIDs[count($contactIDs) - 1])
					);
				}
			}
			//endregion

			//region Save Observers
			if(!empty($observerIDs))
			{
				Crm\Observer\ObserverManager::registerBulk($observerIDs, \CCrmOwnerType::Deal, $ID);
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

			if ($options['DISABLE_TIMELINE_CREATION'] !== 'Y')
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

			if($assignedByID > 0)
			{
				EntityCounterManager::reset(
					EntityCounterManager::prepareCodes(
						CCrmOwnerType::Deal,
						array(
							EntityCounterType::IDLE,
							EntityCounterType::ALL
						),
						array('DEAL_CATEGORY_ID' => $categoryID, 'EXTENDED_MODE' => true)
					),
					array($assignedByID)
				);
			}

			// tracking of entity
			Tracking\Entity::onAfterAdd(CCrmOwnerType::Deal, $ID, $arFields);

			if($bUpdateSearch)
			{
				$arFilterTmp = Array('ID' => $ID);
				if (!$this->bCheckPermission)
					$arFilterTmp["CHECK_PERMISSIONS"] = "N";
				CCrmSearch::UpdateSearch($arFilterTmp, 'DEAL', true);
			}

			if (isset($GLOBALS['USER']) && isset($arFields['COMPANY_ID']) && intval($arFields['COMPANY_ID']) > 0)
			{
				if (!class_exists('CUserOptions'))
					include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/classes/'.$GLOBALS['DBType'].'/favorites.php');

				CUserOptions::SetOption('crm', 'crm_company_search', array('last_selected' => $arFields['COMPANY_ID']));
			}

			//region Search content index
			Bitrix\Crm\Search\SearchContentBuilderFactory::create(CCrmOwnerType::Deal)->build($ID);
			//endregion

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

				CCrmSonetSubscription::RegisterSubscription(
					CCrmOwnerType::Deal,
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
					$url = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Deal, $ID);
					$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0) ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));

					$arMessageFields = array(
						"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
						"TO_USER_ID" => $assignedByID,
						"FROM_USER_ID" => $createdByID,
						"NOTIFY_TYPE" => IM_NOTIFY_FROM,
						"NOTIFY_MODULE" => "crm",
						"LOG_ID" => $logEventID,
						"NOTIFY_EVENT" => "deal_add",
						"NOTIFY_TAG" => "CRM|DEAL_RESPONSIBLE|".$ID,
						"NOTIFY_MESSAGE" => GetMessage("CRM_DEAL_RESPONSIBLE_IM_NOTIFY", Array("#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arFields['TITLE'])."</a>")),
						"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_DEAL_RESPONSIBLE_IM_NOTIFY", Array("#title#" => htmlspecialcharsbx($arFields['TITLE'])))." (".$serverName.$url.")"
					);
					CIMNotify::Add($arMessageFields);
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

			\Bitrix\Crm\Kanban\SupervisorTable::sendItem($ID, CCrmOwnerType::DealName, 'kanban_add');
		}

		return $result;
	}

	public function CheckFields(&$arFields, $ID = false, $options = array())
	{
		global $APPLICATION, $USER_FIELD_MANAGER;
		$this->LAST_ERROR = '';
		$this->checkExceptions = array();

		if (($ID == false || isset($arFields['TITLE'])) && empty($arFields['TITLE']))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_IS_MISSING', array('%FIELD_NAME%' => GetMessage('CRM_DEAL_FIELD_TITLE')))."<br />\n";

		if (!empty($arFields['BEGINDATE']) && !CheckDateTime($arFields['BEGINDATE']))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_BEGINDATE')))."<br />\n";

		if (!empty($arFields['CLOSEDATE']) && !CheckDateTime($arFields['CLOSEDATE']))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_CLOSEDATE')))."<br />\n";

		if (!empty($arFields['EVENT_DATE']) && !CheckDateTime($arFields['EVENT_DATE']))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_EVENT_DATE')))."<br />\n";

		if(is_string($arFields['OPPORTUNITY']) && $arFields['OPPORTUNITY'] !== '')
		{
			$arFields['OPPORTUNITY'] = str_replace(array(',', ' '), array('.', ''), $arFields['OPPORTUNITY']);
			//HACK: MSSQL returns '.00' for zero value
			if(strpos($arFields['OPPORTUNITY'], '.') === 0)
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

				$requiredFields = Crm\Attribute\FieldAttributeManager::isEnabled()
					? Crm\Attribute\FieldAttributeManager::getRequiredFields(
						CCrmOwnerType::Deal,
						$ID,
						$fieldsToCheck,
						Crm\Attribute\FieldOrigin::UNDEFINED,
						isset($options['FIELD_CHECK_OPTIONS']) && is_array($options['FIELD_CHECK_OPTIONS']) ? $options['FIELD_CHECK_OPTIONS'] : array()
					)
					: array();

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

			$requiredUserFields = is_array($requiredFields) && isset($requiredFields[Crm\Attribute\FieldOrigin::CUSTOM])
				? $requiredFields[Crm\Attribute\FieldOrigin::CUSTOM] : array();

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
			array('ID', 'ASSIGNED_BY_ID', 'OPENED', 'STAGE_ID', 'CATEGORY_ID')
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

			if(isset($fields['STAGE_ID']))
			{
				$attrs['STAGE_ID'] = $fields['STAGE_ID'];
			}

			$entityAttrs = self::BuildEntityAttr($assignedByID, $attrs);
			CCrmPerms::UpdateEntityAttr(
				DealCategory::convertToPermissionEntityType(
					isset($fields['CATEGORY_ID']) ? (int)$fields['CATEGORY_ID'] : 0
				),
				$ID,
				$entityAttrs
			);
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
		$connection = \Bitrix\Main\Application::getInstance()->getConnection();

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
					$dbResult = $connection->query("SELECT MIN(ID) AS ID FROM b_crm_deal WHERE COMPANY_ID = {$companyID} AND STAGE_SEMANTIC_ID = 'S'");
				}
				else//if($contactID > 0)
				{
					$dbResult = $connection->query("SELECT MIN(ID) AS ID FROM b_crm_deal WHERE CONTACT_ID = {$contactID} AND COMPANY_ID <= 0 AND STAGE_SEMANTIC_ID = 'S'");
				}

				$resultData = $dbResult->fetch();
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
				$dbResult = $connection->query("SELECT MIN(ID) AS ID FROM b_crm_deal WHERE COMPANY_ID = {$companyID} AND STAGE_SEMANTIC_ID = 'S'");
			}
			else//if($contactID > 0)
			{
				$dbResult = $connection->query("SELECT MIN(ID) AS ID FROM b_crm_deal WHERE CONTACT_ID = {$contactID} AND COMPANY_ID <= 0 AND STAGE_SEMANTIC_ID = 'S'");
			}

			$resultData = $dbResult->fetch();
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
						"UPDATE b_crm_deal SET IS_RETURN_CUSTOMER = 'Y', IS_REPEATED_APPROACH = 'N' WHERE IS_RETURN_CUSTOMER = 'N' AND CONTACT_ID = {$contactID} AND IFNULL(COMPANY_ID, 0) = 0"
					);
				}
				$connection->queryExecute("UPDATE b_crm_deal SET IS_RETURN_CUSTOMER = 'N' WHERE ID = {$primaryID}");
			}
		}
		elseif($enableSource)
		{
			$connection->queryExecute("UPDATE b_crm_deal SET IS_RETURN_CUSTOMER = 'N' WHERE ID = {$sourceID}");
		}
		//endregion
	}

	public function Update($ID, array &$arFields, $bCompare = true, $bUpdateSearch = true, $options = array())
	{
		global $DB;

		$ID = (int) $ID;
		if(!is_array($options))
		{
			$options = array();
		}
		$isSystemAction = isset($options['IS_SYSTEM_ACTION']) && $options['IS_SYSTEM_ACTION'];

		$this->LAST_ERROR = '';
		$this->checkExceptions = array();

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
				$arFields['MODIFY_BY_ID'] = $userID;
			}
		}

		if(isset($arFields['TITLE']) && trim($arFields['TITLE']) === '')
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
			//Category is read only
			if(isset($arFields['CATEGORY_ID']))
			{
				unset($arFields['CATEGORY_ID']);
			}

			//Semantic ID depends on Stage ID and can't be assigned directly
			$syncStageSemantics = isset($options['SYNCHRONIZE_STAGE_SEMANTICS']) && $options['SYNCHRONIZE_STAGE_SEMANTICS'];
			if(isset($arFields['STAGE_ID']) && ($syncStageSemantics || $arFields['STAGE_ID'] !== $arRow['STAGE_ID']))
			{
				$arFields['STAGE_SEMANTIC_ID'] = self::IsStageExists($arFields['STAGE_ID'], $arRow['CATEGORY_ID'])
					? self::GetSemanticID($arFields['STAGE_ID'], $arRow['CATEGORY_ID'])
					: Bitrix\Crm\PhaseSemantics::UNDEFINED;
				$arFields['IS_NEW'] = $arFields['STAGE_ID'] === self::GetStartStageID($arRow['CATEGORY_ID']) ? 'Y' : 'N';
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

			if (isset($arFields['ASSIGNED_BY_ID']) && $arRow['ASSIGNED_BY_ID'] != $arFields['ASSIGNED_BY_ID'])
				CCrmEvent::SetAssignedByElement($arFields['ASSIGNED_BY_ID'], 'DEAL', $ID);

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
			}

			// Calculation of Account Data
			$accData = CCrmAccountingHelper::PrepareAccountingData(
				array(
					'CURRENCY_ID' => isset($arFields['CURRENCY_ID']) ? $arFields['CURRENCY_ID'] : (isset($arRow['CURRENCY_ID']) ? $arRow['CURRENCY_ID'] : null),
					'SUM' => isset($arFields['OPPORTUNITY']) ? $arFields['OPPORTUNITY'] : (isset($arRow['OPPORTUNITY']) ? $arRow['OPPORTUNITY'] : null),
					'EXCH_RATE' => isset($arFields['EXCH_RATE']) ? $arFields['EXCH_RATE'] : (isset($arRow['EXCH_RATE']) ? $arRow['EXCH_RATE'] : null)
				)
			);
			if(is_array($accData))
			{
				$arFields['ACCOUNT_CURRENCY_ID'] = $accData['ACCOUNT_CURRENCY_ID'];
				$arFields['OPPORTUNITY_ACCOUNT'] = $accData['ACCOUNT_SUM'];
			}
			$accData = CCrmAccountingHelper::PrepareAccountingData(
				array(
					'CURRENCY_ID' => isset($arFields['CURRENCY_ID']) ? $arFields['CURRENCY_ID'] : (isset($arRow['CURRENCY_ID']) ? $arRow['CURRENCY_ID'] : null),
					'SUM' => isset($arFields['TAX_VALUE']) ? $arFields['TAX_VALUE'] : (isset($arRow['TAX_VALUE']) ? $arRow['TAX_VALUE'] : null),
					'EXCH_RATE' => isset($arFields['EXCH_RATE']) ? $arFields['EXCH_RATE'] : (isset($arRow['EXCH_RATE']) ? $arRow['EXCH_RATE'] : null)
				)
			);
			if(is_array($accData))
				$arFields['TAX_VALUE_ACCOUNT'] = $accData['ACCOUNT_SUM'];

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
			$sUpdate = $DB->PrepareUpdate('b_crm_deal', $arFields);
			if (strlen($sUpdate) > 0)
			{
				$DB->Query("UPDATE b_crm_deal SET {$sUpdate} WHERE ID = {$ID}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
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

			CCrmPerms::UpdateEntityAttr($permissionEntityType, $ID, $arEntityAttr);

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

			self::SynchronizeCustomerData($ID, $arRow, array('ENABLE_SOURCE' => false));
			self::SynchronizeCustomerData($ID, $currentFields);

			if (isset($GLOBALS['USER']) && isset($arFields['COMPANY_ID']) && $arFields['COMPANY_ID'] > 0)
			{
				if (!class_exists('CUserOptions'))
					include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/classes/'.$GLOBALS['DBType'].'/favorites.php');

				CUserOptions::SetOption('crm', 'crm_company_search', array('last_selected' => $arFields['COMPANY_ID']));
			}

			//region Complete activities if entity is closed
			if($arRow['STAGE_SEMANTIC_ID'] !== $currentFields['STAGE_SEMANTIC_ID']
				&& $currentFields['STAGE_SEMANTIC_ID'] !== Bitrix\Crm\PhaseSemantics::PROCESS)
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

			if($bResult && (isset($arFields['ASSIGNED_BY_ID']) || isset($arFields['STAGE_ID'])))
			{
				$assignedByIDs = array();
				if($assignedByID > 0)
				{
					$assignedByIDs[] = $assignedByID;
				}

				$previousAssignedByID = isset($arRow['ASSIGNED_BY_ID']) ? (int)$arRow['ASSIGNED_BY_ID'] : 0;
				if($previousAssignedByID > 0 && $assignedByID !== $previousAssignedByID)
				{
					$assignedByIDs[] = $previousAssignedByID;
				}

				if(!empty($assignedByIDs))
				{
					EntityCounterManager::reset(
						EntityCounterManager::prepareCodes(
							CCrmOwnerType::Deal,
							array(
								EntityCounterType::PENDING,
								EntityCounterType::OVERDUE,
								EntityCounterType::IDLE,
								EntityCounterType::ALL
							),
							array('DEAL_CATEGORY_ID' => $categoryID, 'EXTENDED_MODE' => true)
						),
						$assignedByIDs
					);
				}
			}

			// update utm fields
			UtmTable::updateEntityUtmFromFields(CCrmOwnerType::Deal, $ID, $arFields);

			if($bUpdateSearch)
			{
				$arFilterTmp = Array('ID' => $ID);
				if (!$this->bCheckPermission)
					$arFilterTmp['CHECK_PERMISSIONS'] = 'N';
				CCrmSearch::UpdateSearch($arFilterTmp, 'DEAL', true);
			}

			//region Search content index
			Bitrix\Crm\Search\SearchContentBuilderFactory::create(CCrmOwnerType::Deal)->build($ID);
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

			if($bResult && isset($arFields['ASSIGNED_BY_ID']))
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

				$modifiedByID = (int)$arFields['MODIFY_BY_ID'];
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

					//Register contact & company relations
					if($sonetEventType === CCrmLiveFeedEvent::Owner)
					{
						if(is_array($removedContactIDs))
						{
							CCrmLiveFeed::PrepareOwnershipRelations(
								CCrmOwnerType::Contact,
								$removedContactIDs,
								$parents
							);
						}

						if($oldCompanyID > 0)
						{
							CCrmLiveFeed::PrepareOwnershipRelations(
								CCrmOwnerType::Company,
								array($oldCompanyID),
								$parents
							);
						}

						if($newCompanyID > 0)
						{
							CCrmLiveFeed::PrepareOwnershipRelations(
								CCrmOwnerType::Company,
								array($newCompanyID),
								$parents
							);
						}
					}

					if(!empty($parents))
					{
						$sonetEventFields['PARENTS'] = array_values($parents);
					}

					$logEventID = CCrmLiveFeed::CreateLogEvent($sonetEventFields, $sonetEventType, array('CURRENT_USER' => $userID));

					if (
						$logEventID
						&& CModule::IncludeModule("im")
					)
					{
						$title = CCrmOwnerType::GetCaption(CCrmOwnerType::Deal, $ID, false);
						$url = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Deal, $ID);
						$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0) ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));

						if (
							$sonetEvent['TYPE'] == CCrmLiveFeedEvent::Responsible
							&& $sonetEventFields['PARAMS']['FINAL_RESPONSIBLE_ID'] != $modifiedByID
						)
						{
							$arMessageFields = array(
								"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
								"TO_USER_ID" => $sonetEventFields['PARAMS']['FINAL_RESPONSIBLE_ID'],
								"FROM_USER_ID" => $modifiedByID,
								"NOTIFY_TYPE" => IM_NOTIFY_FROM,
								"NOTIFY_MODULE" => "crm",
								"LOG_ID" => $logEventID,
								"NOTIFY_EVENT" => "deal_update",
								"NOTIFY_TAG" => "CRM|DEAL_RESPONSIBLE|".$ID,
								"NOTIFY_MESSAGE" => GetMessage("CRM_DEAL_RESPONSIBLE_IM_NOTIFY", Array("#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($title)."</a>")),
								"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_DEAL_RESPONSIBLE_IM_NOTIFY", Array("#title#" => htmlspecialcharsbx($title)))." (".$serverName.$url.")"
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
								"NOTIFY_EVENT" => "deal_update",
								"NOTIFY_TAG" => "CRM|DEAL_RESPONSIBLE|".$ID,
								"NOTIFY_MESSAGE" => GetMessage("CRM_DEAL_NOT_RESPONSIBLE_IM_NOTIFY", Array("#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($title)."</a>")),
								"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_DEAL_NOT_RESPONSIBLE_IM_NOTIFY", Array("#title#" => htmlspecialcharsbx($title)))." (".$serverName.$url.")"
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
							$infos = self::GetStages($categoryID);

							if (
								$assignedByID != $modifiedByID
								&& isset($infos[$sonetEventFields['PARAMS']['START_STATUS_ID']])
								&& isset($infos[$sonetEventFields['PARAMS']['FINAL_STATUS_ID']])
							)
							{
								$arMessageFields = array(
									"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
									"TO_USER_ID" => $assignedByID,
									"FROM_USER_ID" => $modifiedByID,
									"NOTIFY_TYPE" => IM_NOTIFY_FROM,
									"NOTIFY_MODULE" => "crm",
									"LOG_ID" => $logEventID,
									"NOTIFY_EVENT" => "deal_update",
									"NOTIFY_TAG" => "CRM|DEAL_PROGRESS|".$ID,
									"NOTIFY_MESSAGE" => GetMessage("CRM_DEAL_PROGRESS_IM_NOTIFY", Array(
										"#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($title)."</a>",
										"#start_status_title#" => htmlspecialcharsbx($infos[$sonetEventFields['PARAMS']['START_STATUS_ID']]['NAME']),
										"#final_status_title#" => htmlspecialcharsbx($infos[$sonetEventFields['PARAMS']['FINAL_STATUS_ID']]['NAME'])
									)),
									"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_DEAL_PROGRESS_IM_NOTIFY", Array(
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

			//region After update event
			if($bResult && $enableSystemEvents)
			{
				$afterEvents = GetModuleEvents('crm', 'OnAfterCrmDealUpdate');
				while ($arEvent = $afterEvents->Fetch())
					ExecuteModuleEventEx($arEvent, array(&$arFields));
			}
			//endregion

			self::PullChange('UPDATE', array('ID' => $ID));

			\Bitrix\Crm\Kanban\SupervisorTable::sendItem($ID, CCrmOwnerType::DealName, 'kanban_update');

			if(!$isSystemAction)
			{
				$stageSemanticsId = $arFields['STAGE_SEMANTIC_ID'] ?: $arRow['STAGE_SEMANTIC_ID'];
				if(Crm\Ml\Scoring::isMlAvailable() && !Crm\PhaseSemantics::isFinal($stageSemanticsId))
				{
					Crm\Ml\Scoring::queuePredictionUpdate(CCrmOwnerType::Deal, $ID, [
						'EVENT_TYPE' => Crm\Ml\Scoring::EVENT_ENTITY_UPDATE
					]);
				}
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

		$sWherePerm = '';
		if ($this->bCheckPermission)
		{
			$arEntityAttr = $this->cPerms->GetEntityAttr($permissionEntityType, $ID);
			$sEntityPerm = $this->cPerms->GetPermType($permissionEntityType, 'DELETE', $arEntityAttr[$ID]);
			if ($sEntityPerm == BX_CRM_PERM_NONE)
				return false;
			else if ($sEntityPerm == BX_CRM_PERM_SELF)
				$sWherePerm = " AND ASSIGNED_BY_ID = {$iUserId}";
			else if ($sEntityPerm == BX_CRM_PERM_OPEN)
				$sWherePerm = " AND (OPENED = 'Y' OR ASSIGNED_BY_ID = {$iUserId})";
		}

		$APPLICATION->ResetException();
		$events = GetModuleEvents('crm', 'OnBeforeCrmDealDelete');
		while ($arEvent = $events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array($ID))===false)
			{
				$err = GetMessage("MAIN_BEFORE_DEL_ERR").' '.$arEvent['TO_NAME'];
				if ($ex = $APPLICATION->GetException())
					$err .= ': '.$ex->GetString();
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

		$dbRes = $DB->Query("DELETE FROM b_crm_deal WHERE ID = {$ID}{$sWherePerm}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		if (is_object($dbRes) && $dbRes->AffectedRowsCount() > 0)
		{
			if(defined('BX_COMP_MANAGED_CACHE'))
			{
				$GLOBALS['CACHE_MANAGER']->CleanDir('b_crm_deal');
			}

			self::SynchronizeCustomerData($ID, $arFields, array('ENABLE_SOURCE' => false));

			CCrmSearch::DeleteSearch('DEAL', $ID);

			Bitrix\Crm\Kanban\SortTable::clearEntity($ID, \CCrmOwnerType::DealName);

			$DB->Query("DELETE FROM b_crm_entity_perms WHERE ENTITY='{$permissionEntityType}' AND ENTITY_ID = {$ID}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
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
				if(!Bitrix\Crm\Agent\Routine\CleaningAgent::isActive())
				{
					Bitrix\Crm\Agent\Routine\CleaningAgent::activate();
				}
			}

			if(!isset($arOptions['REGISTER_STATISTICS']) || $arOptions['REGISTER_STATISTICS'] === true)
			{
				DealStageHistoryEntry::unregister($ID);
				DealSumStatisticEntry::unregister($ID);
				DealInvoiceStatisticEntry::unregister($ID);
				DealActivityStatisticEntry::unregister($ID);
				DealChannelBinding::unregisterAll($ID);
			}
			if($assignedByID > 0)
			{
				EntityCounterManager::reset(
					EntityCounterManager::prepareCodes(
						CCrmOwnerType::Deal,
						array(
							EntityCounterType::PENDING,
							EntityCounterType::OVERDUE,
							EntityCounterType::IDLE,
							EntityCounterType::ALL
						),
						array('DEAL_CATEGORY_ID' => $categoryID, 'EXTENDED_MODE' => true)
					),
					array($assignedByID)
				);
			}


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
				'EVENT_TEXT_1' => !empty($arFieldsOrig['COMMENTS'])? $arFieldsOrig['COMMENTS']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['COMMENTS'])? $arFieldsModif['COMMENTS']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
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

		if (isset($arFieldsOrig['OPPORTUNITY'])
			&& isset($arFieldsModif['OPPORTUNITY'])
			&& $arFieldsOrig['OPPORTUNITY'] != $arFieldsModif['OPPORTUNITY'])
		{
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'OPPORTUNITY',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_OPPORTUNITY'),
				'EVENT_TEXT_1' => floatval($arFieldsOrig['OPPORTUNITY']).(($val = CrmCompareFieldsList($arCurrency, $arFieldsOrig['CURRENCY_ID'], '')) != '' ? ' ('.$val.')' : ''),
				'EVENT_TEXT_2' => floatval($arFieldsModif['OPPORTUNITY']).(($val = CrmCompareFieldsList($arCurrency, $arFieldsModif['CURRENCY_ID'], '')) != '' ? ' ('.$val.')' : '')
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

	public static function LoadProductRows($ID)
	{
		return CCrmProductRow::LoadRows('D', $ID);
	}

	public static function SaveProductRows($ID, $arRows, $checkPerms = true, $regEvent = true, $syncOwner = true)
	{
		$result = CCrmProductRow::SaveRows('D', $ID, $arRows, null, $checkPerms, $regEvent, $syncOwner);
		if($result)
		{
			$events = GetModuleEvents('crm', 'OnAfterCrmDealProductRowsSave');
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
				'OPPORTUNITY' => isset($arTotalInfo['OPPORTUNITY']) ? $arTotalInfo['OPPORTUNITY'] : 0.0,
				'TAX_VALUE' => isset($arTotalInfo['TAX_VALUE']) ? $arTotalInfo['TAX_VALUE'] : 0.0
			);

			$entity = new CCrmDeal($checkPerms);
			$entity->Update($ID, $arFields);
		}
	}

	public static function GetCategoryID($ID)
	{
		if($ID <= 0)
		{
			return 0;
		}

		$dbRes = self::GetListEx(
			array(),
			array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'CATEGORY_ID')
		);

		$fields = is_object($dbRes) ? $dbRes->Fetch() : null;
		if(!is_array($fields))
		{
			return -1;
		}
		return isset($fields['CATEGORY_ID']) ? (int)$fields['CATEGORY_ID'] : 0;
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
			$userPermissions = CCrmPerms::GetCurrentUserPermissions();;
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
			return CCrmPerms::GetEntityAttr(DealCategory::convertToPermissionEntityType($categoryID), $IDs);
		}

		$results = array();
		foreach($IDs as $ID)
		{
			$results += CCrmPerms::GetEntityAttr(
				DealCategory::convertToPermissionEntityType(self::GetCategoryID($ID)),
				array($ID)
			);
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
		$canCreateQuote = CCrmQuote::CheckCreatePermission($userPermissions);

		$params['CAN_CONVERT_TO_INVOICE'] = $canCreateInvoice;
		$params['CAN_CONVERT_TO_QUOTE'] = $canCreateQuote;
		$params['CAN_CONVERT'] = $params['CONVERT'] = ($canCreateInvoice || $canCreateQuote);
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

	public static function IsStageExists($stageID, $categoryID = -1)
	{
		if(!is_int($categoryID))
		{
			$categoryID = (int)$categoryID;
		}

		if($categoryID < 0)
		{
			$categoryID = DealCategory::resolveFromStageID($stageID);
		}

		$stageList = self::GetStages($categoryID);
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
		if($stageID === '')
		{
			return Bitrix\Crm\PhaseSemantics::UNDEFINED;
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
			return Bitrix\Crm\PhaseSemantics::SUCCESS;
		}

		if($stageID === DealCategory::prepareStageID($categoryID, 'LOSE'))
		{
			return Bitrix\Crm\PhaseSemantics::FAILURE;
		}

		return (self::GetStageSort($stageID, $categoryID) > self::GetFinalStageSort($categoryID))
			? Bitrix\Crm\PhaseSemantics::FAILURE : Bitrix\Crm\PhaseSemantics::PROCESS;
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

		$stageID = isset($fields['STAGE_ID']) ? $fields['STAGE_ID'] : '';
		if($stageID === '')
		{
			return DealCategoryChangeError::STAGE_NOT_FOUND;
		}

		$checkRunningBizProcess = !isset($options['ENABLE_WORKFLOW_CHECK']) || $options['ENABLE_WORKFLOW_CHECK'] === true;
		if($checkRunningBizProcess && \CCrmBizProcHelper::HasRunningWorkflows(CCrmOwnerType::Deal, $ID))
		{
			return DealCategoryChangeError::HAS_WORKFLOWS;
		}

		$successStageID = DealCategory::prepareStageID($newCategoryID, 'WON');
		$failureStageID = DealCategory::prepareStageID($newCategoryID, 'LOSE');
		$processStageID = '';
		//Looking for first process stage ID
		foreach(array_keys(self::GetStages($newCategoryID)) as $statusID)
		{
			if($successStageID !== $statusID)
			{
				$processStageID = $statusID;
				break;
			}
		}

		if($processStageID === '')
		{
			$processStageID = DealCategory::prepareStageID($newCategoryID, 'NEW');
		}

		$semanticID = self::GetSemanticID($stageID, $categoryID);
		if($semanticID === Bitrix\Crm\PhaseSemantics::SUCCESS)
		{
			$newStageID = $successStageID;
		}
		elseif($semanticID === Bitrix\Crm\PhaseSemantics::FAILURE)
		{
			$newStageID = $failureStageID;
		}
		else//if($semanticID === Bitrix\Crm\PhaseSemantics::PROCESS)
		{
			$newStageID = $processStageID;
		}

		$connection = \Bitrix\Main\Application::getConnection();
		$now = $connection->getSqlHelper()->getCurrentDateTimeFunction();
		$connection->query(
			"UPDATE b_crm_deal SET CATEGORY_ID = {$newCategoryID}, STAGE_ID = '{$newStageID}', DATE_MODIFY = {$now} WHERE ID = {$ID}"
		);

		//region Update Permissions
		CCrmPerms::DeleteEntityAttr(DealCategory::convertToPermissionEntityType($categoryID), $ID);

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
		CCrmPerms::UpdateEntityAttr($permissionEntityType, $ID, $entityAttrs);
		//endregion

		//region Reset counters
		EntityCounterManager::reset(
			EntityCounterManager::prepareCodes(
				CCrmOwnerType::Deal,
				array(
					EntityCounterType::PENDING,
					EntityCounterType::OVERDUE,
					EntityCounterType::IDLE,
					EntityCounterType::ALL
				),
				array('DEAL_CATEGORY_ID' => $categoryID)
			),
			array($assignedByID)
		);
		EntityCounterManager::reset(
			EntityCounterManager::prepareCodes(
				CCrmOwnerType::Deal,
				array(
					EntityCounterType::PENDING,
					EntityCounterType::OVERDUE,
					EntityCounterType::IDLE,
					EntityCounterType::ALL
				),
				array('DEAL_CATEGORY_ID' => $newCategoryID)
			),
			array($assignedByID)
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
		$userID = isset($options['USER_ID']) ? (int)$options['USER_ID'] : 0;
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

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
			$type = strtolower($type);
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
				"UPDATE {$tableName} SET CONTACT_ID = {$newID} WHERE CONTACT_ID = {$oldID}",
				false,
				'File: '.__FILE__.'<br>Line: '.__LINE__
			);
		}
		elseif($ownerTypeID === CCrmOwnerType::Company)
		{
			$DB->Query(
				"UPDATE {$tableName} SET COMPANY_ID = {$newID} WHERE COMPANY_ID = {$oldID}",
				false,
				'File: '.__FILE__.'<br>Line: '.__LINE__
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
		$DB->Query("UPDATE b_crm_deal SET CONTACT_ID = NULL WHERE CONTACT_ID = {$contactID}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);

		\Bitrix\Crm\Binding\DealContactTable::unbindAllDeals($contactID);
	}
	public static function ProcessCompanyDeletion($companyID)
	{
		global $DB;
		$DB->Query("UPDATE b_crm_deal SET COMPANY_ID = NULL WHERE COMPANY_ID = {$companyID}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
	}
	public static function ProcessLeadDeletion($leadID)
	{
		global $DB;
		$DB->Query("UPDATE b_crm_deal SET LEAD_ID = NULL WHERE LEAD_ID = {$leadID}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
	}
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
}

?>
