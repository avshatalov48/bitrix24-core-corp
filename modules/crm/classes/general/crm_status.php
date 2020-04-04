<?php

if(!defined('CACHED_b_crm_status')) define('CACHED_b_crm_status', 360000);

IncludeModuleLangFile(__FILE__);

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Category\DealCategory;

class CCrmStatus
{
	protected $entityId = '';
	private static $FIELD_INFOS = null;
	private static $STATUSES = array();
	private static $SETTINGS = null;

	private $LAST_ERROR = '';
	function __construct($entityId)
	{
		$this->entityId = $entityId;
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
				'ENTITY_ID' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(
						CCrmFieldInfoAttr::Required,
						CCrmFieldInfoAttr::Immutable
					)
				),
				'STATUS_ID' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(
						CCrmFieldInfoAttr::Required,
						CCrmFieldInfoAttr::Immutable
					)
				),
				'SORT' => array('TYPE' => 'integer'),
				'NAME' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				),
				'NAME_INIT' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'SYSTEM' => array(
					'TYPE' => 'char',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'EXTRA' => array('TYPE' => 'crm_status_extra')
			);
		}
		return self::$FIELD_INFOS;
	}
	public static function GetFieldCaption($fieldName)
	{
		$result = GetMessage("CRM_STATUS_FIELD_{$fieldName}");
		return is_string($result) ? $result : '';
	}
	public static function GetEntityTypes()
	{
		$arEntityType = array(
			'STATUS' => array(
				'ID' =>'STATUS',
				'NAME' => GetMessage('CRM_STATUS_TYPE_STATUS'),
				'SEMANTIC_INFO' => self::GetLeadStatusSemanticInfo()
			),
			'SOURCE' => array('ID' =>'SOURCE', 'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE')),
			'CONTACT_TYPE' => array('ID' =>'CONTACT_TYPE', 'NAME' => GetMessage('CRM_STATUS_TYPE_CONTACT_TYPE')),
			'COMPANY_TYPE' => array('ID' =>'COMPANY_TYPE', 'NAME' => GetMessage('CRM_STATUS_TYPE_COMPANY_TYPE')),
			'EMPLOYEES' => array('ID' =>'EMPLOYEES', 'NAME' => GetMessage('CRM_STATUS_TYPE_EMPLOYEES')),
			'INDUSTRY' => array('ID' =>'INDUSTRY', 'NAME' => GetMessage('CRM_STATUS_TYPE_INDUSTRY')),
			'DEAL_TYPE' => array('ID' =>'DEAL_TYPE', 'NAME' => GetMessage('CRM_STATUS_TYPE_DEAL_TYPE')),
			'INVOICE_STATUS' => array(
				'ID' =>'INVOICE_STATUS',
				'NAME' => GetMessage('CRM_STATUS_TYPE_INVOICE_STATUS'),
				'SEMANTIC_INFO' => self::GetInvoiceStatusSemanticInfo()
			)
		);

		if(DealCategory::isCustomized())
		{
			DealCategory::prepareStatusEntityInfos($arEntityType, true);
		}
		else
		{
			$arEntityType['DEAL_STAGE'] = array(
				'ID' =>'DEAL_STAGE',
				'NAME' => GetMessage('CRM_STATUS_TYPE_DEAL_STAGE'),
				'SEMANTIC_INFO' => self::GetDealStageSemanticInfo()
			);
		}

		$arEntityType = array_merge(
			$arEntityType,
			array(
				'QUOTE_STATUS' => array(
					'ID' =>'QUOTE_STATUS',
					'NAME' => GetMessage('CRM_STATUS_TYPE_QUOTE_STATUS'),
					'SEMANTIC_INFO' => self::GetQuoteStatusSemanticInfo()
				),
				'HONORIFIC' => array('ID' =>'HONORIFIC', 'NAME' => GetMessage('CRM_STATUS_TYPE_HONORIFIC')),
				'EVENT_TYPE' => array('ID' =>'EVENT_TYPE', 'NAME' => GetMessage('CRM_STATUS_TYPE_EVENT_TYPE')),
				'CALL_LIST' => array('ID' => 'CALL_LIST', 'NAME' => GetMessage('CRM_STATUS_TYPE_CALL_LIST'))
			)
		);

		if(self::IsDepricatedTypesEnabled())
		{
			$arEntityType['PRODUCT'] = array('ID' => 'PRODUCT', 'NAME' => GetMessage('CRM_STATUS_TYPE_PRODUCT'));
		}

		return $arEntityType;
	}
	public static function GetFieldExtraTypeInfo()
	{
		return array(
			'SEMANTICS' => array('TYPE' => 'string', 'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)),
			'COLOR' => array('TYPE' => 'string')
		);
	}
	public static function IsDepricatedTypesEnabled()
	{
		return strtoupper(COption::GetOptionString('crm', 'enable_depricated_statuses', 'N')) !== 'N';
	}
	public static function EnableDepricatedTypes($enable)
	{
		return COption::SetOptionString('crm', 'enable_depricated_statuses', $enable ? 'Y' : 'N');
	}
	private static function GetCachedStatuses($entityId)
	{
		return isset(self::$STATUSES[$entityId]) ? self::$STATUSES[$entityId] : null;
	}
	private static function SetCachedStatuses($entityId, $items)
	{
		self::$STATUSES[$entityId] = $items;
	}
	private static function ClearCachedStatuses($entityId)
	{
		unset(self::$STATUSES[$entityId]);
	}
	public function Add($arFields, $bCheckStatusId = true)
	{
		$this->LAST_ERROR = '';

		if (!$this->CheckFields($arFields, $bCheckStatusId))
			return false;

		if (!is_set($arFields['SORT']) ||
			(is_set($arFields['SORT']) && !intval($arFields['SORT']) > 0))
			$arFields['SORT'] = 10;

		if (!is_set($arFields, 'SYSTEM'))
			$arFields['SYSTEM'] = 'N';

		if (!is_set($arFields, 'STATUS_ID'))
			$arFields['STATUS_ID'] = '';

		$statusID = $arFields['STATUS_ID'];
		if($statusID === '')
		{
			if(DealCategory::hasStatusEntity($this->entityId))
			{
				$statusID = DealCategory::issueStageID($this->entityId);
			}
			else
			{
				$statusID = !empty($arFields['STATUS_ID']) ? $arFields['STATUS_ID'] : $this->GetNextStatusId();
			}
		}
		elseif(DealCategory::hasStatusEntity($this->entityId))
		{
			$statusID = DealCategory::prepareStageID(
				DealCategory::convertFromStatusEntityID($this->entityId),
				DealCategory::removeStageNamespaceID($statusID)
			);
		}

		$arFields_i = Array(
			'ENTITY_ID'	=> $this->entityId,
			'STATUS_ID'	=> $statusID,
			'NAME'		=> $arFields['NAME'],
			'NAME_INIT'	=> $arFields['SYSTEM'] == 'Y' ? $arFields['NAME'] : '',
			'SORT'		=> IntVal($arFields['SORT']),
			'SYSTEM'	=> $arFields['SYSTEM'] == 'Y'? 'Y': 'N',
		);

		global $DB;
		$ID = $DB->Add('b_crm_status', $arFields_i, array(), 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		self::ClearCachedStatuses($this->entityId);
		return $ID;
	}
	public function Update($ID, $arFields, $arOptions = array())
	{
		$this->LAST_ERROR = '';

		if (!$this->CheckFields($arFields))
			return false;

		$ID = IntVal($ID);

		if (!is_set($arFields['SORT']) ||
			(is_set($arFields['SORT']) && !intval($arFields['SORT']) > 0))
			$arFields['SORT'] = 10;

		$arFields_u = array(
			'NAME'   => $arFields['NAME'],
			'SORT'   => intval($arFields['SORT'])
		);

		if (isset($arFields['SYSTEM']))
		{
			$arFields_u['SYSTEM'] = ($arFields['SYSTEM'] === 'Y' ? 'Y' : 'N');
		}

		if(is_array($arOptions)
			&& isset($arOptions['ENABLE_STATUS_ID'])
			&& $arOptions['ENABLE_STATUS_ID']
			&& isset($arFields['STATUS_ID']))
		{
			$arFields_u['STATUS_ID'] = $arFields['STATUS_ID'];
		}

		if(is_array($arOptions)
			&& isset($arOptions['ENABLE_NAME_INIT'])
			&& $arOptions['ENABLE_NAME_INIT']
			&& isset($arFields['NAME_INIT']))
		{
			$arFields_u['NAME_INIT'] = $arFields['NAME_INIT'];
		}

		global $DB;
		$strUpdate = $DB->PrepareUpdate('b_crm_status', $arFields_u);
		if(!$DB->Query('UPDATE b_crm_status SET '.$strUpdate.' WHERE ID='.$ID, false, array(), 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__))
			return false;

		$fields = $this->GetStatusById($ID);
		if(is_array($fields))
		{
			CCrmLead::ProcessStatusModification($fields);
			CCrmDeal::ProcessStatusModification($fields);
		}

		self::ClearCachedStatuses($this->entityId);
		return $ID;
	}

	public function Delete($ID)
	{
		$this->LAST_ERROR = '';
		$ID = IntVal($ID);

		$fields = $this->GetStatusById($ID);
		if(!is_array($fields))
		{
			return false;
		}

		CCrmLead::ProcessStatusDeletion($fields);
		CCrmDeal::ProcessStatusDeletion($fields);

		global $DB;
		$res = $DB->Query("DELETE FROM b_crm_status WHERE ID=$ID", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		self::ClearCachedStatuses($this->entityId);

		return $res;
	}

	public static function GetList($arSort=array(), $arFilter=Array())
	{
		$sqlHelper = $connection = \Bitrix\Main\Application::getConnection()->getSqlHelper();

		global $DB;
		$arSqlSearch = Array();
		if (is_array($arFilter))
		{
			$filter_keys = array_keys($arFilter);
			for ($i=0, $ic=count($filter_keys); $i<$ic; $i++)
			{
				$val = $arFilter[$filter_keys[$i]];
				if (strlen($val)<=0 || $val=='NOT_REF') continue;
				switch(strtoupper($filter_keys[$i]))
				{
					case 'ID':
						$arSqlSearch[] = "CS.ID = '".$DB->ForSql($val)."'";
					break;
					case 'ENTITY_ID':
						$arSqlSearch[] = "CS.ENTITY_ID = '".$DB->ForSql($val)."'";
					break;
					case 'STATUS_ID':
						$arSqlSearch[] = "CS.STATUS_ID = '".$DB->ForSql($val)."'";
					break;
					case 'NAME':
						$arSqlSearch[] = GetFilterQuery('CS.NAME', $val);
					break;
					case 'SORT':
						$arSqlSearch[] = "CS.SORT = '".$DB->ForSql($val)."'";
					break;
					case 'SYSTEM':
						$arSqlSearch[] = "CS.".$sqlHelper->quote('SYSTEM')."='".(($val=='Y') ? 'Y' : 'N')."'";
					break;
				}
			}
		}

		$sOrder = '';
		foreach($arSort as $key=>$val)
		{
			$ord = (strtoupper($val) <> 'ASC'? 'DESC':'ASC');
			switch (strtoupper($key))
			{
				case 'ID':		$sOrder .= ', CS.ID '.$ord; break;
				case 'ENTITY_ID':	$sOrder .= ', CS.ENTITY_ID '.$ord; break;
				case 'STATUS_ID':	$sOrder .= ', CS.STATUS_ID '.$ord; break;
				case 'NAME':	$sOrder .= ', CS.NAME '.$ord; break;
				case 'SORT':	$sOrder .= ', CS.SORT '.$ord; break;
				case 'SYSTEM':	$sOrder .= ", CS.".$sqlHelper->quote('SYSTEM')." ".$ord; break;
			}
		}

		if (strlen($sOrder)<=0)
			$sOrder = 'CS.ID DESC';

		$strSqlOrder = ' ORDER BY '.TrimEx($sOrder,',');
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);

		$strSql = "SELECT CS.* FROM b_crm_status CS WHERE {$strSqlSearch} {$strSqlOrder}";
		$res = $DB->Query($strSql, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);

		return $res;
	}
	public function CheckStatusId($statusId)
	{
		global $DB;
		$res = $DB->Query("SELECT ID FROM b_crm_status WHERE ENTITY_ID='{$DB->ForSql($this->entityId)}' AND STATUS_ID ='{$DB->ForSql($statusId)}'", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		$fields = is_object($res) ? $res->Fetch() : array();
		return isset($fields['ID']);
	}
	public function GetNextStatusId()
	{
		global $DB, $DBType;
		$dbTypeUC = strtoupper($DBType);

		if($dbTypeUC === 'MYSQL')
		{
			$sql = "SELECT STATUS_ID AS MAX_STATUS_ID FROM b_crm_status WHERE ENTITY_ID = '{$DB->ForSql($this->entityId)}' AND CAST(STATUS_ID AS UNSIGNED) > 0 ORDER BY CAST(STATUS_ID AS UNSIGNED) DESC LIMIT 1";
		}
		elseif($dbTypeUC === 'MSSQL')
		{
			$sql = "SELECT TOP 1 STATUS_ID AS MAX_STATUS_ID FROM B_CRM_STATUS WHERE ENTITY_ID = '{$DB->ForSql($this->entityId)}' AND CAST((CASE WHEN ISNUMERIC(STATUS_ID) > 0 THEN STATUS_ID ELSE '0' END) AS INT) > 0 ORDER BY CAST((CASE WHEN ISNUMERIC(STATUS_ID) > 0 THEN STATUS_ID ELSE '0' END) AS INT) DESC";
		}
		elseif($dbTypeUC === 'ORACLE')
		{
			$sql = "SELECT STATUS_ID AS MAX_STATUS_ID FROM (SELECT STATUS_ID FROM B_CRM_STATUS WHERE ENTITY_ID = '{$DB->ForSql($this->entityId)}' AND COALESCE(TO_NUMBER(REGEXP_SUBSTR(STATUS_ID, '^\d+(\.\d+)?')), 0) > 0 ORDER BY COALESCE(TO_NUMBER(REGEXP_SUBSTR(STATUS_ID, '^\d+(\.\d+)?')), 0) DESC) WHERE ROWNUM <= 1";
		}
		else
		{
			return 0;
		}

		$res = $DB->Query($sql, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		$fields = is_object($res) ? $res->Fetch() : array();
		return (isset($fields['MAX_STATUS_ID']) ? intval($fields['MAX_STATUS_ID']) : 0) + 1;
	}
	public static function GetStatus($entityId, $internalOnly = false)
	{
		if(!is_string($entityId))
		{
			return array();
		}

		global $DB;
		$arStatus = array();

		if(CACHED_b_crm_status===false)
		{
			$squery = "
				SELECT *
				FROM b_crm_status
				WHERE ENTITY_ID = '".$DB->ForSql($entityId)."'
				ORDER BY SORT ASC
			";
			$res = $DB->Query($squery, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			while ($row = $res->Fetch())
			{
				$arStatus[$row['STATUS_ID']] = $row;
			}

			return $arStatus;
		}
		else
		{
			$cached = self::GetCachedStatuses($entityId);
			if($cached !== null)
			{
				$arStatus = $cached;
			}
			else
			{
				$squery = "
					SELECT *
					FROM b_crm_status
					WHERE ENTITY_ID = '".$DB->ForSql($entityId)."'
					ORDER BY SORT ASC
				";
				$res = $DB->Query($squery, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
				while($row = $res->Fetch())
				{
					$arStatus[$row['STATUS_ID']] = $row;
				}

				self::SetCachedStatuses($entityId, $arStatus);
			}
			return $arStatus;
		}
	}
	public static function GetEntityID($statusId)
	{
		global $DB;
		$res = $DB->Query("SELECT ENTITY_ID FROM b_crm_status WHERE STATUS_ID ='{$DB->ForSql($statusId)}'", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		$fields = is_object($res) ? $res->Fetch() : array();
		return isset($fields['ENTITY_ID']) ? $fields['ENTITY_ID'] : '';
	}

	public static function GetFirstStatusID($entityId, $internalOnly = false)
	{
		$arStatusList = self::GetStatusList($entityId, $internalOnly);
		return !empty($arStatusList) ? key($arStatusList) : null;
	}
	public static function GetStatusList($entityId, $internalOnly = false)
	{
		$arStatusList = Array();
		$ar = self::GetStatus($entityId, $internalOnly);
		if(is_array($ar))
		{
			foreach($ar as $arStatus)
			{
				$arStatusList[$arStatus['STATUS_ID']] = $arStatus['NAME'];
			}
		}

		return $arStatusList;
	}
	public static function GetStatusListEx($entityId)
	{
		$arStatusList = Array();
		$ar = self::GetStatus($entityId);
		foreach($ar as $arStatus)
			$arStatusList[$arStatus['STATUS_ID']] = htmlspecialcharsbx($arStatus['NAME']);

		return $arStatusList;
	}
	public function GetStatusById($ID)
	{
		if(!is_int($ID))
		{
			$ID = (int)$ID;
		}

		$arStatus = self::GetStatus($this->entityId);
		foreach($arStatus as $item)
		{
			$currentID = isset($item['ID']) ? (int)$item['ID'] : 0;
			if($currentID === $ID)
			{
				return $item;
			}
		}
		return false;
	}
	public function GetStatusByStatusId($statusId)
	{
		$arStatus = self::GetStatus($this->entityId);
		return isset($arStatus[$statusId]) ? $arStatus[$statusId]: false;
	}
	private function CheckFields($arFields, $bCheckStatusId = true)
	{
		$aMsg = array();

		if(is_set($arFields, 'NAME') && trim($arFields['NAME'])=='')
			$aMsg[] = array('id'=>'NAME', 'text'=>GetMessage('CRM_STATUS_ERR_NAME'));
		if(is_set($arFields, 'SYSTEM') && !($arFields['SYSTEM'] == 'Y' || $arFields['SYSTEM'] == 'N'))
			$aMsg[] = array('id'=>'SYSTEM', 'text'=>GetMessage('CRM_STATUS_ERR_SYSTEM'));
		if(is_set($arFields, 'STATUS_ID') && trim($arFields['STATUS_ID'])=='')
			$aMsg[] = array('id'=>'STATUS_ID', 'text'=>GetMessage('CRM_STATUS_ERR_STATUS_ID'));
		if (is_set($arFields, 'STATUS_ID') && $bCheckStatusId && $this->CheckStatusId($arFields['STATUS_ID']))
			$aMsg[] = array('id'=>'STATUS_ID', 'text'=>GetMessage('CRM_STATUS_ERR_DUPLICATE_STATUS_ID'));

		if(!empty($aMsg))
		{
			foreach($aMsg as $msg)
			{
				$this->LAST_ERROR .= $msg."<br />\n";
			}

			$e = new CAdminException($aMsg);
			$GLOBALS['APPLICATION']->ThrowException($e);
			return false;
		}

		return true;
	}
	public function GetLastError()
	{
		return $this->LAST_ERROR;
	}
	public static function InstallDefault($entityId, $statusId = null)
	{
		if(!is_string($entityId))
		{
			return;
		}

		$items = array();
		$entityId = strtoupper($entityId);
		if($entityId === 'STATUS')
		{
			$items = self::GetDefaultLeadStatuses();
		}
		elseif($entityId === 'DEAL_STAGE')
		{
			$items = self::GetDefaultDealStages();
		}
		elseif($entityId === 'SOURCE')
		{
			$items = self::GetDefaultSources();
		}
		elseif($entityId === 'CONTACT_TYPE')
		{
			$items = self::GetDefaultContactTypes();
		}
		elseif($entityId === 'COMPANY_TYPE')
		{
			$items = self::GetDefaultCompanyTypes();
		}
		elseif($entityId === 'QUOTE_STATUS')
		{
			$items = self::GetDefaultQuoteStatuses();
		}
		elseif($entityId === 'EMPLOYEES')
		{
			$items = self::GetDefaultEmployees();
		}
		elseif($entityId === 'CALL_LIST')
		{
			$items = self::GetDefaultCallListStates();
		}
		elseif($entityId === 'INVOICE_STATUS')
		{
			$items = self::GetDefaultInvoiceStatuses();
		}

		if ($statusId !== null)
		{
			$filtered = array();
			foreach ($items as $item)
			{
				if ($item['STATUS_ID'] != $statusId)
				{
					continue;
				}

				$filtered[] = $item;
			}
			$items = $filtered;
		}

		self::BulkCreate($entityId, $items);
	}
	public static function GetDefaultLeadStatuses()
	{
		return array(
			array(
				'NAME' => GetMessage('CRM_STATUS_TYPE_STATUS_NEW'),
				'STATUS_ID' => 'NEW',
				'SORT' => 10,
				'SYSTEM' => 'Y'
			),
			array(
				'NAME' => GetMessage('CRM_STATUS_TYPE_STATUS_IN_PROCESS'),
				'STATUS_ID' => 'IN_PROCESS',
				'SORT' => 20,
				'SYSTEM' => 'N'
			),
			array(
				'NAME' => GetMessage('CRM_STATUS_TYPE_STATUS_PROCESSED'),
				'STATUS_ID' => 'PROCESSED',
				'SORT' => 30,
				'SYSTEM' => 'N'
			),
			array(
				'NAME' => GetMessage('CRM_STATUS_TYPE_STATUS_CONVERTED'),
				'STATUS_ID' => 'CONVERTED',
				'SORT' => 40,
				'SYSTEM' => 'Y'
			),
			array(
				'NAME' => GetMessage('CRM_STATUS_TYPE_STATUS_JUNK'),
				'STATUS_ID' => 'JUNK',
				'SORT' => 50,
				'SYSTEM' => 'Y'
			)
		);
	}
	public static function GetDefaultSources()
	{
		return array(
			array(
				'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_CALL'),
				'STATUS_ID' => 'CALL',
				'SORT' => 10,
				'SYSTEM' => 'Y'
			),
			array(
				'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_EMAIL'),
				'STATUS_ID' => 'EMAIL',
				'SORT' => 20,
			),
			array(
				'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_WEB'),
				'STATUS_ID' => 'WEB',
				'SORT' => 30
			),
			array(
				'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_ADVERTISING'), //!NEW
				'STATUS_ID' => 'ADVERTISING',
				'SORT' => 40
			),
			array(
				'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_PARTNER'),
				'STATUS_ID' => 'PARTNER',
				'SORT' => 50,
			),
			array(
				'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_RECOMMENDATION'), //!NEW
				'STATUS_ID' => 'RECOMMENDATION',
				'SORT' => 60,
			),
			array(
				'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_TRADE_SHOW'),
				'STATUS_ID' => 'TRADE_SHOW',
				'SORT' => 70,
			),
			array(
				'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_WEBFORM'),
				'STATUS_ID' => 'WEBFORM',
				'SORT' => 75,
				'SYSTEM' => 'Y'
			),
			array(
				'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_CALLBACK'),
				'STATUS_ID' => 'CALLBACK',
				'SORT' => 77,
				'SYSTEM' => 'Y'
			),
			array(
				'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_RC_GENERATOR'),
				'STATUS_ID' => 'RC_GENERATOR',
				'SORT' => 78,
				'SYSTEM' => 'Y'
			),
			array(
				'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_STORE'),
				'STATUS_ID' => 'STORE',
				'SORT' => 79,
				'SYSTEM' => 'Y'
			),

			array(
				'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_OTHER'),
				'STATUS_ID' => 'OTHER',
				'SORT' => 80,
			)
		);
	}
	public static function GetDefaultContactTypes()
	{
		return array(
			array(
				'NAME' => GetMessage('CRM_CONTACT_TYPE_CLIENT'),
				'STATUS_ID' => 'CLIENT',
				'SORT' => 10
			),
			array(
				'NAME' => GetMessage('CRM_CONTACT_TYPE_SUPPLIER'),
				'STATUS_ID' => 'SUPPLIER',
				'SORT' => 20
			),
			array(
				'NAME' => GetMessage('CRM_CONTACT_TYPE_PARTNER'),
				'STATUS_ID' => 'PARTNER',
				'SORT' => 30
			),
			array(
				'NAME' => GetMessage('CRM_CONTACT_TYPE_OTHER'),
				'STATUS_ID' => 'OTHER',
				'SORT' => 40
			)
		);
	}
	public static function GetDefaultCompanyTypes()
	{
		return array(
			array(
				'NAME' => GetMessage('CRM_COMPANY_TYPE_CUSTOMER'),
				'STATUS_ID' => 'CUSTOMER',
				'SORT' => 10
			),
			array(
				'NAME' => GetMessage('CRM_COMPANY_TYPE_SUPPLIER'),
				'STATUS_ID' => 'SUPPLIER',
				'SORT' => 20
			),
			array(
				'NAME' => GetMessage('CRM_COMPANY_TYPE_COMPETITOR'),
				'STATUS_ID' => 'COMPETITOR',
				'SORT' => 30
			),
			array(
				'NAME' => GetMessage('CRM_COMPANY_TYPE_PARTNER'),
				'STATUS_ID' => 'PARTNER',
				'SORT' => 40
			),
			array(
				'NAME' => GetMessage('CRM_COMPANY_TYPE_OTHER'),
				'STATUS_ID' => 'OTHER',
				'SORT' => 50
			)
		);
	}
	public static function GetDefaultDealStages($namespace = '')
	{
		$prefix = is_string($namespace) && $namespace !== '' ? "{$namespace}:" : '';
		return array(
			array(
				'NAME' => GetMessage('CRM_DEAL_STAGE_NEW'),
				'STATUS_ID' => "{$prefix}NEW",
				'SORT' => 10,
				'SYSTEM' => 'Y'
			),
			array(
				'NAME' => GetMessage('CRM_DEAL_STAGE_PREPARATION'),
				'STATUS_ID' => "{$prefix}PREPARATION",
				'SORT' => 20,
				'SYSTEM' => 'N'
			),
			array(
				'NAME' => GetMessage('CRM_DEAL_STAGE_PREPAYMENT_INVOICE'),
				'STATUS_ID' => "{$prefix}PREPAYMENT_INVOICE", //PRELIMINARY_INVOICE
				'SORT' => 30,
				'SYSTEM' => 'N'
			),
			array(
				'NAME' => GetMessage('CRM_DEAL_STAGE_EXECUTING'),
				'STATUS_ID' => "{$prefix}EXECUTING",
				'SORT' => 40,
				'SYSTEM' => 'N'
			),
			array(
				'NAME' => GetMessage('CRM_DEAL_STAGE_FINAL_INVOICE'),
				'STATUS_ID' => "{$prefix}FINAL_INVOICE",
				'SORT' => 50,
				'SYSTEM' => 'N'
			),
			array(
				'NAME' => GetMessage('CRM_DEAL_STAGE_WON'),
				'STATUS_ID' => "{$prefix}WON",
				'SORT' => 60,
				'SYSTEM' => 'Y'
			),
			array(
				'NAME' => GetMessage('CRM_DEAL_STAGE_LOSE'),
				'STATUS_ID' => "{$prefix}LOSE",
				'SORT' => 70,
				'SYSTEM' => 'Y'
			),
			array(
				'NAME' => GetMessage('CRM_DEAL_STAGE_APOLOGY'),
				'STATUS_ID' => "{$prefix}APOLOGY",
				'SORT' => 80,
				'SYSTEM' => 'N'
			)
		);
	}
	public static function GetDefaultQuoteStatuses()
	{
		return array(
			array(
				'NAME' => GetMessage('CRM_QUOTE_STATUS_DRAFT'),
				'STATUS_ID' => 'DRAFT',
				'SORT' => 10,
				'SYSTEM' => 'Y'
			),
			array(
				'NAME' => GetMessage('CRM_QUOTE_STATUS_SENT'),
				'STATUS_ID' => 'SENT',
				'SORT' => 20,
				'SYSTEM' => 'N'
			),
			array(
				'NAME' => GetMessage('CRM_QUOTE_STATUS_APPROVED'),
				'STATUS_ID' => 'APPROVED',
				'SORT' => 30,
				'SYSTEM' => 'Y'
			),
			array(
				'NAME' => GetMessage('CRM_QUOTE_STATUS_DECLAINED'),
				'STATUS_ID' => 'DECLAINED',
				'SORT' => 40,
				'SYSTEM' => 'Y'
			),
			array(
				'NAME' => GetMessage('CRM_QUOTE_STATUS_APOLOGY'),
				'STATUS_ID' => 'APOLOGY',
				'SORT' => 50,
				'SYSTEM' => 'N'
			)
		);
	}

	public static function GetDefaultInvoiceStatuses()
	{
		return array(
			array(
				'NAME' => GetMessage('CRM_INVOICE_STATUS_NEW'),
				'STATUS_ID' => 'N',
				'SORT' => 100,
				'SYSTEM' => 'Y'
			),
			array(
				'NAME' => GetMessage('CRM_INVOICE_STATUS_SENT'),
				'STATUS_ID' => 'S',
				'SORT' => 110,
				'SYSTEM' => 'N'
			),
			array(
				'NAME' => GetMessage('CRM_INVOICE_STATUS_PAID'),
				'STATUS_ID' => 'P',
				'SORT' => 130,
				'SYSTEM' => 'Y'
			),
			array(
				'NAME' => GetMessage('CRM_INVOICE_STATUS_REFUSED'),
				'STATUS_ID' => 'D',
				'SORT' => 140,
				'SYSTEM' => 'Y'
			)
		);
	}
	public static function GetDefaultCallListStates()
	{
		return array(
			array(
				'NAME' => GetMessage('CRM_CALL_LIST_IN_WORK'),
				'STATUS_ID' => 'IN_WORK',
				'SORT' => 10,
				'SYSTEM' => 'Y'
			),
			array(
				'NAME' => GetMessage('CRM_CALL_LIST_SUCCESS'),
				'STATUS_ID' => 'SUCCESS',
				'SORT' => 20,
				'SYSTEM' => 'Y'
			),
			array(
				'NAME' => GetMessage('CRM_CALL_LIST_WRONG_NUMBER'),
				'STATUS_ID' => 'WRONG_NUMBER',
				'SORT' => 30,
				'SYSTEM' => 'Y'
			),
			array(
				'NAME' => GetMessage('CRM_CALL_LIST_STOP_CALLING'),
				'STATUS_ID' => 'STOP_CALLING',
				'SORT' => 40,
				'SYSTEM' => 'Y'
			),
		);
	}
	public static function GetDefaultEmployees()
	{
		return array(
			array(
				'NAME' => GetMessage('CRM_EMPLOYEES_1'),
				'STATUS_ID' => 'EMPLOYEES_1',
				'SORT' => 10,
				'SYSTEM' => 'Y'
			),
			array(
				'NAME' => GetMessage('CRM_EMPLOYEES_2'),
				'STATUS_ID' => 'EMPLOYEES_2',
				'SORT' => 20,
				'SYSTEM' => 'N'
			),
			array(
				'NAME' => GetMessage('CRM_EMPLOYEES_3'),
				'STATUS_ID' => 'EMPLOYEES_3',
				'SORT' => 30,
				'SYSTEM' => 'N'
			),
			array(
				'NAME' => GetMessage('CRM_EMPLOYEES_4'),
				'STATUS_ID' => 'EMPLOYEES_4',
				'SORT' => 40,
				'SYSTEM' => 'N'
			)
		);
	}

	public static function GetDefaultLeadStatusName($statusID)
	{
		$s = Loc::getMessage("CRM_STATUS_TYPE_STATUS_{$statusID}");
		return is_string($s) ? $s : "[{$statusID}]";
	}
	public static function GetDefaultDealStageName($stageID)
	{
		$s = Loc::getMessage("CRM_DEAL_STAGE_{$stageID}");
		return is_string($s) ? $s : "[{$stageID}]";
	}

	public static function BulkCreate($entityId, array $items)
	{
		$entity = new CCrmStatus($entityId);
		foreach($items as $item)
		{
			if(!$entity->CheckStatusId($item['STATUS_ID']))
			{
				$entity->Add($item);
			}
		}
	}
	public static function Erase($entityId)
	{
		$entity = new CCrmStatus($entityId);
		$entity->DeleteAll();
	}

	public function DeleteAll()
	{
		global $DB;
		$entityId = $this->entityId;
		$res = $DB->Query("DELETE FROM b_crm_status WHERE ENTITY_ID = '{$entityId}'", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		self::ClearCachedStatuses($entityId);
		return $res;
	}

	public static function MarkAsEnabled($entityId, $enabled)
	{
		if(!is_string($entityId))
		{
			return;
		}
		$entityId = strtoupper($entityId);
		$settings = self::GetSettings();

		if(!isset($settings[$entityId]))
		{
			$settings[$entityId] = array();
		}
		$settings[$entityId]['enabled'] = $enabled ? 'Y' : 'N';
		self::SaveSettings();
	}
	public static function CheckIfEnabled($entityId)
	{
		if(!is_string($entityId))
		{
			return false;
		}
		$entityId = strtoupper($entityId);
		$settings = self::GetSettings();
		return !isset($settings[$entityId])
			|| !isset($settings[$entityId]['enabled'])
			|| strtoupper($settings[$entityId]['enabled']) === 'Y';
	}
	private static function GetSettings()
	{
		self::$SETTINGS = CUserOptions::GetOption('crm', 'status', null);
		if(!is_array(self::$SETTINGS))
		{
			self::$SETTINGS = array();
		}
		return self::$SETTINGS;
	}
	private static function SaveSettings()
	{
		CUserOptions::SetOption('crm', 'status', self::$SETTINGS);
	}

	public static function GetLeadStatusSemanticInfo()
	{
		return array(
			'START_FIELD' => 'NEW',
			'FINAL_SUCCESS_FIELD' => 'CONVERTED',
			'FINAL_UNSUCCESS_FIELD' => 'JUNK',
			'FINAL_SORT' => 0
		);
	}
	public static function GetDealStageSemanticInfo($namespace = '')
	{
		$prefix = is_string($namespace) && $namespace !== '' ? "{$namespace}:" : '';
		return array(
			'START_FIELD' => "{$prefix}NEW",
			'FINAL_SUCCESS_FIELD' => "{$prefix}WON",
			'FINAL_UNSUCCESS_FIELD' => "{$prefix}LOSE",
			'FINAL_SORT' => 0
		);
	}
	public static function GetQuoteStatusSemanticInfo()
	{
		return array(
			'START_FIELD' => 'DRAFT',
			'FINAL_SUCCESS_FIELD' => 'APPROVED',
			'FINAL_UNSUCCESS_FIELD' => 'DECLAINED',
			'FINAL_SORT' => 0
		);
	}
	public static function GetInvoiceStatusSemanticInfo()
	{
		return array(
			'START_FIELD' => 'N',
			'FINAL_SUCCESS_FIELD' => 'P',
			'FINAL_UNSUCCESS_FIELD' => 'D',
			'FINAL_SORT' => 0
		);
	}
	// Checking User Permissions -->
	public static function CheckCreatePermission()
	{
		$perms = CCrmPerms::GetCurrentUserPermissions();
		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
	}
	public static function CheckUpdatePermission($ID)
	{
		$perms = CCrmPerms::GetCurrentUserPermissions();
		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
	}
	public static function CheckDeletePermission($ID)
	{
		$perms = CCrmPerms::GetCurrentUserPermissions();
		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
	}
	public static function CheckReadPermission($ID = 0)
	{
		$perms = CCrmPerms::GetCurrentUserPermissions();
		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ');
	}
	// <-- Checking User Permissions
}

?>
