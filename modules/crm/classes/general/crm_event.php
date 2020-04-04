<?php

IncludeModuleLangFile(__FILE__);
use \Bitrix\Main\Type\Date;
use \Bitrix\Main\Type\DateTime;
use \Bitrix\Crm;
use \Bitrix\Crm\Settings\HistorySettings;
use \Bitrix\Crm\Security\EntityAuthorization;

class CCrmEvent
{
	protected $cdb = null;
	protected $currentUserID = 0;

	const TYPE_USER = 0;
	const TYPE_CHANGE = 1;
	const TYPE_EMAIL = 2;
	const TYPE_VIEW = 3;
	const TYPE_EXPORT = 4;
	const TYPE_DELETE = 5;

	/** @var array  */
	private static $eventTypes = null;
	function __construct()
	{
		global $DB;
		$this->cdb = $DB;

		global $USER;
		$currentUser = (is_object($USER) && ((get_class($USER) === 'CUser') || ($USER instanceof CUser))) ? $USER : (new CUser());
		$this->currentUserID = $currentUser->GetId();
	}
	public function Add($arFields, $bPermCheck = true)
	{
		$err_mess = (self::err_mess()).'<br />Function: Add<br />Line: ';
		$db_events = GetModuleEvents('crm', 'OnBeforeCrmAddEvent');
		while($arEvent = $db_events->Fetch())
			$arFields = ExecuteModuleEventEx($arEvent, array($arFields));

		if (isset($arFields['ENTITY']) && is_array($arFields['ENTITY']))
		{
			foreach($arFields['ENTITY'] as $key => $arEntity)
				if (!(isset($arEntity['ENTITY_TYPE']) && isset($arEntity['ENTITY_ID'])))
					unset($arEntity['ENTITY'][$key]);
		}
		else if (isset($arFields['ENTITY_TYPE']) && isset($arFields['ENTITY_ID']))
		{
			$arFields['ENTITY'] = array(
				array(
					'ENTITY_TYPE' => $arFields['ENTITY_TYPE'],
					'ENTITY_ID' => $arFields['ENTITY_ID'],
					'ENTITY_FIELD' => isset($arFields['ENTITY_FIELD']) ? $arFields['ENTITY_FIELD'] : '',
					'USER_ID' => (int)(isset($arFields['USER_ID']) ? intval($arFields['USER_ID']) : $this->currentUserID)
				)
			);
		}
		else
			return false;

		if (isset($arFields['EVENT_ID']))
		{
			$CCrmStatus = new CCrmStatus('EVENT_TYPE');
			$ar = $CCrmStatus->GetStatusByStatusId($arFields['EVENT_ID']);
			$arFields['EVENT_NAME'] = isset($ar['NAME'])? $ar['NAME']: '';
		}

		if (!$this->CheckFields($arFields))
			return false;

		if (!isset($arFields['EVENT_TYPE']))
			$arFields['EVENT_TYPE'] = 0;

		$arFiles = Array();
		if (isset($arFields['FILES']) && !empty($arFields['FILES']))
		{
			$arFields['~FILES'] = Array();
			if (isset($arFields['FILES'][0]))
				$arFields['~FILES'] = $arFields['FILES'];
			else
			{
				foreach($arFields['FILES'] as $type => $ar)
					foreach($ar as $key => $value)
						$arFields['~FILES'][$key][$type] = $value;
			}

			foreach($arFields['~FILES'] as &$arFile)
			{
				$arFile['del'] = 'N';
				$arFile['MODULE_ID'] = 'crm';
				$fid = intval(CFile::SaveFile($arFile, 'crm'));

				if ($fid > 0)
				{
					$arFiles[] = $fid;
				}
			}
			unset($arFile);
		}


		$arFields_i = Array(
			'ASSIGNED_BY_ID'=> (int)(isset($arFields['USER_ID']) ? intval($arFields['USER_ID']) : $this->currentUserID),
			'CREATED_BY_ID'	=> (int)(isset($arFields['USER_ID']) ? intval($arFields['USER_ID']) : $this->currentUserID),
			'EVENT_ID' 		=> isset($arFields['EVENT_ID'])? $arFields['EVENT_ID']: '',
			'EVENT_NAME' 	=> $arFields['EVENT_NAME'],
			'EVENT_TYPE' 	=> intval($arFields['EVENT_TYPE']),
			'EVENT_TEXT_1'  => isset($arFields['EVENT_TEXT_1'])? $arFields['EVENT_TEXT_1']: '',
			'EVENT_TEXT_2'  => isset($arFields['EVENT_TEXT_2'])? $arFields['EVENT_TEXT_2']: '',
			'FILES' => null,
		);
		if (count($arFiles) > 0)
		{
			$arFields_i['FILES'] = serialize($arFiles);
		}

		//Validate DATE_CREATE
		if (isset($arFields['DATE_CREATE']))
		{
			$sqlTime = CDatabase::FormatDate($arFields['DATE_CREATE'], CLang::GetDateFormat('FULL', false), 'YYYY-MM-DD HH:MI:SS');
			if (!(is_string($sqlTime) && $sqlTime !== ''))
			{
				unset($arFields['DATE_CREATE']);
			}
		}

		if (isset($arFields['DATE_CREATE']))
		{
			$arFields_i['DATE_CREATE'] = $arFields['DATE_CREATE'];
		}
		else
		{
			$arFields_i['~DATE_CREATE'] = $this->cdb->GetNowFunction();
		}

		$EVENT_ID = $this->cdb->Add('b_crm_event', $arFields_i, array("FILES"), 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		$this->AddRelation($EVENT_ID, $arFields['ENTITY'], $bPermCheck);

		$db_events = GetModuleEvents('crm', 'OnAfterCrmAddEvent');
		while($arEvent = $db_events->Fetch())
			ExecuteModuleEventEx($arEvent, array($EVENT_ID, $arFields));

		return $EVENT_ID;
	}
	public function Share($srcEntity, $dstEntities, $typeName)
	{
		$typeName = strtoupper(strval($typeName));
		if($typeName === '')
		{
			return;
		}

		global $DB;
		$srcEntityType = isset($srcEntity['ENTITY_TYPE']) ? $DB->ForSql($srcEntity['ENTITY_TYPE']) : '';
		$srcEntityID = isset($srcEntity['ENTITY_ID']) ? intval($srcEntity['ENTITY_ID']) : 0;

		if($srcEntityType === '' || $srcEntityID <= 0)
		{
			return;
		}

		$dbResult = null;
		if($typeName === 'MESSAGE')
		{
			$dbResult = $DB->Query("SELECT ID FROM b_crm_event WHERE ID IN (SELECT EVENT_ID FROM b_crm_event_relations WHERE ENTITY_TYPE = '{$srcEntityType}' AND ENTITY_ID = {$srcEntityID}) AND (EVENT_TYPE = 2 OR (EVENT_TYPE = 0 AND EVENT_ID = 'MESSAGE'))");
		}

		if($dbResult)
		{
			while($arResult = $dbResult->Fetch())
			{
				self::AddRelation($arResult['ID'], $dstEntities, false);
			}
		}
	}
	public function AddRelation($EVENT_ID, $arFields, $bPermCheck = true)
	{
		$CCrmPerms = \CCrmAuthorizationHelper::GetUserPermissions();
		$EVENT_ID = intval($EVENT_ID);
		$REL_ID = 0;
		foreach ($arFields as $arRel)
		{
			$entityType = $arRel['ENTITY_TYPE'];
			$entityTypeID = \CCrmOwnerType::ResolveID($entityType);
			$entityID = (int)$arRel['ENTITY_ID'];

			if($bPermCheck
				&& \CCrmOwnerType::IsEntity($entityTypeID)
				&& !EntityAuthorization::checkUpdatePermission($entityTypeID, $entityID, $CCrmPerms)
			)
			{
				continue;
			}

			$arRel_i = array(
				'ENTITY_TYPE'	=> $entityType,
				'ENTITY_ID'	 	=> $entityID,
				'ENTITY_FIELD'  => isset($arRel['ENTITY_FIELD']) ? $arRel['ENTITY_FIELD'] : '',
				'EVENT_ID' 		=> $EVENT_ID,
				'ASSIGNED_BY_ID'=> isset($arRel['USER_ID']) ? intval($arRel['USER_ID']) : $this->currentUserID,
			);

			$REL_ID = $this->cdb->Add('b_crm_event_relations', $arRel_i, array(), 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		}
		return $REL_ID; //?
	}
	public function RemoveRelation($RELATION_ID, $ENTITY_TYPE, $bPermCheck = true)
	{
		$RELATION_ID = intval($RELATION_ID);

		if (!in_array($ENTITY_TYPE, Array('LEAD', 'CONTACT', 'COMPANY', 'DEAL', 'QUOTE')))
			return false;

		if ($bPermCheck)
		{
			$CrmPerms = new CCrmPerms($this->currentUserID);
			if ($CrmPerms->HavePerm($ENTITY_TYPE, BX_CRM_PERM_NONE))
				return false;
		}

		$sSql = "DELETE FROM b_crm_event_relations WHERE ID = $RELATION_ID";
		$this->cdb->Query($sSql, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		return true;
	}
	public function CheckFields($arFields)
	{
		$aMsg = array();

		if(!is_set($arFields, 'EVENT_NAME') || trim($arFields['EVENT_NAME'])=='')
			$aMsg[] = array('id'=>'EVENT_NAME', 'text'=>GetMessage('CRM_EVENT_ERR_ENTITY_NAME'));

		if(isset($arFields['DATE_CREATE'])
			&& !empty($arFields['DATE_CREATE'])
			&& !CheckDateTime($arFields['DATE_CREATE'], FORMAT_DATETIME))
		{
			$aMsg[] = array('id'=>'EVENT_DATE', 'text'=>GetMessage('CRM_EVENT_ERR_ENTITY_DATE_NOT_VALID'));
		}

		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$GLOBALS['APPLICATION']->ThrowException($e);
			return false;
		}

		return true;
	}
	public static function GetFields()
	{
		$relationJoin = 'INNER JOIN b_crm_event_relations CER ON CE.ID = CER.EVENT_ID';
		$createdByJoin = 'LEFT JOIN b_user U ON CE.CREATED_BY_ID = U.ID';

		$result = array(
			'ID' => array('FIELD' => 'CER.ID', 'TYPE' => 'int', 'FROM' => $relationJoin),

			'ENTITY_TYPE' => array('FIELD' => 'CER.ENTITY_TYPE', 'TYPE' => 'string', 'FROM' => $relationJoin),
			'ENTITY_ID' => array('FIELD' => 'CER.ENTITY_ID', 'TYPE' => 'int', 'FROM' => $relationJoin),
			'ENTITY_FIELD' => array('FIELD' => 'CER.ENTITY_FIELD', 'TYPE' => 'string', 'FROM' => $relationJoin),

			'EVENT_REL_ID' => array('FIELD' => 'CER.EVENT_ID', 'TYPE' => 'string', 'FROM' => $relationJoin),
			'EVENT_ID' => array('FIELD' => 'CE.EVENT_ID', 'TYPE' => 'string'),
			'EVENT_TYPE' => array('FIELD' => 'CE.EVENT_TYPE', 'TYPE' => 'string'),
			'EVENT_NAME' => array('FIELD' => 'CE.EVENT_NAME', 'TYPE' => 'string'),
			'EVENT_TEXT_1' => array('FIELD' => 'CE.EVENT_TEXT_1', 'TYPE' => 'string'),
			'EVENT_TEXT_2' => array('FIELD' => 'CE.EVENT_TEXT_2', 'TYPE' => 'string'),
			'FILES' => array('FIELD' => 'CE.FILES', 'TYPE' => 'string'),

			'CREATED_BY_ID' => array('FIELD' => 'CE.CREATED_BY_ID', 'TYPE' => 'int'),
			'CREATED_BY_LOGIN' => array('FIELD' => 'U.LOGIN', 'TYPE' => 'string', 'FROM'=> $createdByJoin),
			'CREATED_BY_NAME' => array('FIELD' => 'U.NAME', 'TYPE' => 'string', 'FROM'=> $createdByJoin),
			'CREATED_BY_LAST_NAME' => array('FIELD' => 'U.LAST_NAME', 'TYPE' => 'string', 'FROM'=> $createdByJoin),
			'CREATED_BY_SECOND_NAME' => array('FIELD' => 'U.SECOND_NAME', 'TYPE' => 'string', 'FROM'=> $createdByJoin),
			'CREATED_BY_PERSONAL_PHOTO' => array('FIELD' => 'U.PERSONAL_PHOTO', 'TYPE' => 'int', 'FROM'=> $createdByJoin),

			'DATE_CREATE' => array('FIELD' => 'CE.DATE_CREATE', 'TYPE' => 'datetime'),
			'ASSIGNED_BY_ID' => array('FIELD' => 'CER.ASSIGNED_BY_ID', 'TYPE' => 'int', 'FROM' => $relationJoin)
		);
		return $result;
	}
	static public function BuildPermSql($aliasPrefix = 'CE', $permType = 'READ')
	{
		if(empty($arFilter['ENTITY_TYPE']))
		{
			$arEntity = array(
				CCrmOwnerType::LeadName,
				CCrmOwnerType::DealName,
				CCrmOwnerType::QuoteName,
				CCrmOwnerType::ContactName,
				CCrmOwnerType::CompanyName
			);
		}
		elseif(isset($arFilter['ENTITY_TYPE']) && is_array($arFilter['ENTITY_TYPE']))
		{
			$arEntity = $arFilter['ENTITY_TYPE'];
		}
		else
		{
			$arEntity = array($arFilter['ENTITY_TYPE']);
		}

		$entitiesSql = array();
		$permOptions = array('IDENTITY_COLUMN' => 'ENTITY_ID');
		foreach ($arEntity as $entityType)
		{
			if($entityType === CCrmOwnerType::LeadName)
			{
				$entitiesSql[CCrmOwnerType::LeadName] = CCrmLead::BuildPermSql('CER', $permType, $permOptions);
			}
			elseif($entityType === CCrmOwnerType::DealName)
			{
				$entitiesSql[CCrmOwnerType::DealName] = CCrmDeal::BuildPermSql('CER', $permType, $permOptions);
			}
			elseif($entityType === CCrmOwnerType::QuoteName)
			{
				$entitiesSql[CCrmOwnerType::QuoteName] = CCrmQuote::BuildPermSql('CER', $permType, $permOptions);
			}
			elseif($entityType === CCrmOwnerType::ContactName)
			{
				$entitiesSql[CCrmOwnerType::ContactName] = CCrmContact::BuildPermSql('CER', $permType, $permOptions);
			}
			elseif($entityType === CCrmOwnerType::CompanyName)
			{
				$entitiesSql[CCrmOwnerType::CompanyName] = CCrmCompany::BuildPermSql('CER', $permType, $permOptions);
			}
		}

		foreach($entitiesSql as $entityType => $entitySql)
		{
			if(!is_string($entitySql))
			{
				//If $entityPermSql is not string - acces denied. Clear permission SQL and related records will be ignored.
				unset($entitiesSql[$entityType]);
				continue;
			}

			if($entitySql !== '')
			{
				$entitiesSql[$entityType] = "(CER.ENTITY_TYPE = '{$entityType}' AND ({$entitySql}))";
			}
			else
			{
				// No permissions check - fetch all related records
				$entitiesSql[$entityType] = "(CER.ENTITY_TYPE = '$entityType')";
			}
		}

		//If $entitiesSql is empty - user does not have permissions at all.
		if(empty($entitiesSql))
		{
			return false;
		}

		return implode(' OR ', $entitiesSql);
	}
	// GetList with navigation support
	public static function GetListEx($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array(), $arOptions = array())
	{
		$operationInfo = Crm\UI\Filter\EntityHandler::findFieldOperation('ENTITY', $arFilter);
		if(is_array($operationInfo) && $operationInfo['OPERATION'] === '=')
		{
			$ary = explode('_', $operationInfo['CONDITION']);
			if(count($ary) === 2)
			{
				$arFilter['ENTITY_TYPE'] = CUserTypeCrm::GetLongEntityType($ary[0]);
				$arFilter['ENTITY_ID'] = intval($ary[1]);
			}
		}

		global $DBType;
		$lb = new CCrmEntityListBuilder(
			$DBType,
			'b_crm_event',
			'CE',
			self::GetFields(),
			'',
			'',
			array('CCrmEvent', 'BuildPermSql')
		);
		//HACK:: override user fields data for unserialize file IDs
		$lb->SetUserFields(array('FILES' => array('MULTIPLE' => 'Y')));
		return $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields, $arOptions);
	}
	public static function GetList($arSort=array(), $arFilter=Array(), $nPageTop = false)
	{
		global $DB, $USER;
		$currentUser = (isset($USER) && ((get_class($USER) === 'CUser') || ($USER instanceof CUser))) ? $USER : (new CUser());

		$arSqlSearch = Array();
		$strSqlSearch = "";
		$err_mess = (self::err_mess()).'<br />Function: GetList<br />Line: ';

		if (isset($arFilter['ENTITY']))
		{
			$ar = explode('_', $arFilter['ENTITY']);
			$arFilter['ENTITY_TYPE'] = CUserTypeCrm::GetLongEntityType($ar[0]);
			$arFilter['ENTITY_ID'] = intval($ar[1]);
			unset($arFilter['ENTITY']);
		}

		// permission check
		$strPermission = "";
		if (!$currentUser->IsAdmin())
		{
			$CCrmPerms = new CCrmPerms($currentUser->GetID());
			$arUserAttr = array();
			$arEntity = array();
			if (empty($arFilter['ENTITY_TYPE']))
				$arEntity = array('LEAD', 'DEAL', 'CONTACT', 'COMPANY', 'QUOTE');
			else if (is_array($arFilter['ENTITY_TYPE']))
				$arEntity = $arFilter['ENTITY_TYPE'];
			else
				$arEntity = array($arFilter['ENTITY_TYPE']);

			$arInEntity = array();
			foreach ($arEntity as $sEntityType)
			{
				$arEntityAttr = $CCrmPerms->GetUserAttrForSelectEntity($sEntityType, 'READ');
				$arUserAttr[$sEntityType] = $arEntityAttr;
			}

			if (empty($arUserAttr))
			{
				$CDBResult = new CDBResult();
				$CDBResult->InitFromArray(array());
				return $CDBResult;
			}

			$arUserPerm = array();
			foreach ($arUserAttr as $sEntityType => $_arAttrs)
			{
				if (isset($_arAttrs[0]) && is_array($_arAttrs[0]) && empty($_arAttrs[0]))
				{
					$arInEntity[] = $sEntityType;
					continue;
				}
				foreach ($_arAttrs as $_arAttr)
				{
					if (empty($_arAttr))
						continue;
					$_icnt = count($_arAttr);
					$_idcnt = -1;
					foreach ($_arAttr as $sAttr)
						if ($sAttr[0] == 'D')
							$_idcnt++;
					if ($_icnt == 1 && ($_idcnt == 1 || $_idcnt == -1))
						$_idcnt = 0;

					$arUserPerm[] = "(P.ENTITY = '$sEntityType' AND SUM(CASE WHEN P.ATTR = '".implode("' or P.ATTR = '", $_arAttr)."' THEN 1 ELSE 0 END) = ".($_icnt - $_idcnt).')';
				}
			}

			$arPermission = array();
			if (!empty($arInEntity))
				$arPermission[] = " CER.ENTITY_TYPE IN ('".implode("','", $arInEntity)."')";

			if (!empty($arUserPerm))
				$arPermission[] = "
						EXISTS(
							SELECT 1
							FROM b_crm_entity_perms P
							WHERE P.ENTITY = CER.ENTITY_TYPE AND CER.ENTITY_ID = P.ENTITY_ID
							GROUP BY P.ENTITY, P.ENTITY_ID
							HAVING ".implode(" \n\t\t\t\t\t\t\t\tOR ", $arUserPerm)."
						)";
			if (!empty($arPermission))
				$strPermission = 'AND ('.implode(' OR ', $arPermission).')';
		}

		$sOrder = '';
		foreach($arSort as $key => $val)
		{
			$ord = (strtoupper($val) <> 'ASC'? 'DESC':'ASC');
			switch (strtoupper($key))
			{
				case 'ID':	$sOrder .= ', CER.ID '.$ord; break;
				case 'CREATED_BY_ID':	$sOrder .= ', CE.CREATED_BY_ID '.$ord; break;
				case 'EVENT_TYPE':	$sOrder .= ', CE.EVENT_TYPE '.$ord; break;
				case 'ENTITY_TYPE':	$sOrder .= ', CER.ENTITY_TYPE '.$ord; break;
				case 'ENTITY_ID':	$sOrder .= ', CER.ENTITY_ID '.$ord; break;
				case 'EVENT_ID':	$sOrder .= ', CE.EVENT_ID '.$ord; break;
				case 'DATE_CREATE':	$sOrder .= ', CE.DATE_CREATE '.$ord; break;
				case 'EVENT_NAME':	$sOrder .= ', CE.EVENT_NAME 	 '.$ord; break;
				case 'ENTITY_FIELD':	$sOrder .= ', CER.ENTITY_FIELD 	 '.$ord; break;
			}
		}

		if (strlen($sOrder)<=0)
			$sOrder = 'CER.ID DESC';

		$strSqlOrder = ' ORDER BY '.TrimEx($sOrder,',');

		// where
		$arWhereFields = array(
			'ID' => array(
				'TABLE_ALIAS' => 'CER',
				'FIELD_NAME' => 'CER.ID',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'ENTITY_TYPE' => array(
				'TABLE_ALIAS' => 'CER',
				'FIELD_NAME' => 'CER.ENTITY_TYPE',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'EVENT_REL_ID' => array(
				'TABLE_ALIAS' => 'CER',
				'FIELD_NAME' => 'CER.EVENT_ID',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'EVENT_ID' => array(
				'TABLE_ALIAS' => 'CE',
				'FIELD_NAME' => 'CE.EVENT_ID',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'CREATED_BY_ID' => array(
				'TABLE_ALIAS' => 'CE',
				'FIELD_NAME' => 'CE.CREATED_BY_ID',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'ASSIGNED_BY_ID' => array(
				'TABLE_ALIAS' => 'CER',
				'FIELD_NAME' => 'CER.ASSIGNED_BY_ID',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'EVENT_TYPE' => array(
				'TABLE_ALIAS' => 'CE',
				'FIELD_NAME' => 'CE.EVENT_TYPE',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'EVENT_DESC' => array(
				'TABLE_ALIAS' => 'CE',
				'FIELD_NAME' => 'CE.EVENT_TEXT_1',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'ENTITY_ID' => array(
				'TABLE_ALIAS' => 'CER',
				'FIELD_NAME' => 'CER.ENTITY_ID',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'ENTITY_FIELD' => array(
				'TABLE_ALIAS' => 'CER',
				'FIELD_NAME' => 'CER.ENTITY_FIELD',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'DATE_CREATE' => array(
				'TABLE_ALIAS' => 'CE',
				'FIELD_NAME' => 'CE.DATE_CREATE',
				'FIELD_TYPE' => 'datetime',
				'JOIN' => false
			)
		);


		$obQueryWhere = new CSQLWhere();
		$obQueryWhere->SetFields($arWhereFields);
		if (!is_array($arFilter))
			$arFilter = array();
		$sQueryWhereFields = $obQueryWhere->GetQuery($arFilter);

		if (!empty($sQueryWhereFields))
			$strSqlSearch .= "\n\t\t\t\tAND ($sQueryWhereFields) ";

		$strSql = "
			SELECT
				CER.ID,
				CER.ENTITY_TYPE,
				CER.ENTITY_ID,
				CER.ENTITY_FIELD,
				".$DB->DateToCharFunction('CE.DATE_CREATE')." DATE_CREATE,
				CER.EVENT_ID,
				CE.EVENT_NAME,
				CE.EVENT_TYPE,
				CE.EVENT_TEXT_1,
				CE.EVENT_TEXT_2,
				CE.FILES,
				CE.CREATED_BY_ID,
				U.LOGIN as CREATED_BY_LOGIN,
				U.NAME as CREATED_BY_NAME,
				U.LAST_NAME as CREATED_BY_LAST_NAME,
				U.SECOND_NAME as CREATED_BY_SECOND_NAME
			FROM
				b_crm_event_relations CER,
				b_crm_event CE LEFT JOIN b_user U ON CE.CREATED_BY_ID = U.ID
			WHERE
				CER.EVENT_ID = CE.ID
				$strSqlSearch
				$strPermission
				$strSqlOrder";

		if ($nPageTop !== false)
		{
			$nPageTop = (int) $nPageTop;
			$strSql = $DB->TopSql($strSql, $nPageTop);
		}

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$res->SetUserFields(array('FILES' => array('MULTIPLE' => 'Y')));
		return $res;
	}
	public function DeleteByElement($entityTypeName, $entityID)
	{
		$entityID = (int)$entityID;

		if ($entityTypeName == '' || $entityID == 0)
		{
			return false;
		}

		$db_events = GetModuleEvents('crm', 'OnBeforeCrmEventDeleteByElement');
		while($arEvent = $db_events->Fetch())
			ExecuteModuleEventEx($arEvent, array($entityTypeName, $entityID));

		$err_mess = (self::err_mess()).'<br>Function: DeleteByElement<br>Line: ';

		// check unrelated events
		$entityTypeName = $this->cdb->ForSql($entityTypeName);
		$sql = "SELECT EVENT_ID, COUNT(ID) as CNT
			FROM b_crm_event_relations
			WHERE EVENT_ID IN(SELECT EVENT_ID FROM b_crm_event_relations WHERE ENTITY_TYPE = '{$entityTypeName}' AND ENTITY_ID = {$entityID})
			GROUP BY EVENT_ID";
		$dbRelationResult = $this->cdb->Query($sql, false, $err_mess.__LINE__);
		while($relationFields = $dbRelationResult->Fetch())
		{
			if($relationFields['CNT'] > 1)
			{
				continue;
			}

			$eventID = $relationFields['EVENT_ID'];
			$dbItemResult = $this->cdb->Query("SELECT ID, FILES FROM b_crm_event WHERE ID = {$eventID}", false, $err_mess.__LINE__);
			if($itemFields = $dbItemResult->Fetch())
			{
				$arFiles = isset($itemFields['FILES']) ? unserialize($itemFields['FILES']) : null;
				if(is_array($arFiles))
				{
					foreach($arFiles as $iFileId)
					{
						CFile::Delete((int)$iFileId);
					}
				}
				$this->cdb->Query("DELETE FROM b_crm_event WHERE ID = {$eventID}", false, $err_mess.__LINE__);
			}
		}
		// delete event relations
		$res = $this->cdb->Query("DELETE FROM b_crm_event_relations WHERE ENTITY_TYPE = '{$entityTypeName}' AND ENTITY_ID = {$entityID}", false, $err_mess.__LINE__);
		return $res;
	}
	public function Delete($ID, $arOptions = array())
	{
		global $USER;

		if(isset($arOptions['CURRENT_USER']))
		{
			$iUserId = intval($arOptions['CURRENT_USER']);
		}
		else
		{
			$iUserId = $USER->GetId();
		}

		$err_mess = (self::err_mess()).'<br>Function: Delete<br>Line: ';

		$ID = IntVal($ID);

		$db_events = GetModuleEvents('crm', 'OnBeforeCrmEventDelete');
		while($arEvent = $db_events->Fetch())
			ExecuteModuleEventEx($arEvent, array($ID));

		// if not admin - delete only self items
		if (!CCrmPerms::IsAdmin($iUserId))
		{
			$sql = "SELECT CER.ID
					FROM
						b_crm_event_relations CER,
						b_crm_event CE
					WHERE
						CE.ID = CER.EVENT_ID
					AND CER.ID = '$ID'
					AND CER.ASSIGNED_BY_ID = '".$iUserId."' AND CE.EVENT_TYPE = 0";
			$res = $this->cdb->Query($sql, false, $err_mess.__LINE__);
			if (!$res->Fetch())
				return false;
		}

		// check unrelated events
		$sql = "SELECT EVENT_ID, COUNT(ID) as CNT
				FROM b_crm_event_relations
				WHERE EVENT_ID = (SELECT EVENT_ID FROM b_crm_event_relations WHERE ID = '$ID')
				GROUP BY EVENT_ID";
		$res = $this->cdb->Query($sql, false, $err_mess.__LINE__);
		if ($row = $res->Fetch())
		{
			// delete event
			if ($row['CNT'] == 1)
			{
				$obRes = $this->cdb->Query("SELECT ID, FILES FROM b_crm_event WHERE ID = '$row[EVENT_ID]'", false, $err_mess.__LINE__);
				if (($aRow = $obRes->Fetch()) !== false)
				{
					if (($arFiles = unserialize($aRow['FILES'])) !== false)
					{
						foreach ($arFiles as $iFileId)
							CFile::Delete((int) $iFileId);
					}
					$this->cdb->Query("DELETE FROM b_crm_event WHERE ID = '$row[EVENT_ID]'", false, $err_mess.__LINE__);
				}
			}
		}
		// delete event relation
		$res = $this->cdb->Query("DELETE FROM b_crm_event_relations WHERE ID = '$ID'", false, $err_mess.__LINE__);

		return $res;
	}
	static public function SetAssignedByElement($assignedId, $entityType, $entityId)
	{
		global $DB;

		$err_mess = (self::err_mess()).'<br>Function: SetAssignedByElement<br>Line: ';

		$assignedId = IntVal($assignedId);
		$entityId = IntVal($entityId);

		if ($entityType == '' || $entityId == 0)
			return false;

		$res = $DB->Query("UPDATE b_crm_event_relations SET ASSIGNED_BY_ID = $assignedId WHERE ENTITY_TYPE = '".$DB->ForSql($entityType)."' AND ENTITY_ID = '$entityId'", false, $err_mess.__LINE__);

		return $res;
	}
	static public function Rebind($entityTypeID, $srcEntityID, $dstEntityID)
	{
		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
		$srcEntityID = (int)$srcEntityID;
		$dstEntityID = (int)$dstEntityID;

		$sql = "SELECT R.EVENT_ID FROM b_crm_event_relations R
		INNER JOIN b_crm_event E ON R.EVENT_ID = E.ID
			AND R.ENTITY_TYPE = '{$entityTypeName}'
			AND R.ENTITY_ID = {$srcEntityID}
			AND E.EVENT_TYPE IN (0, 2)";

		global $DB;
		$err_mess = (self::err_mess()).'<br>Function: Rebind<br>Line: ';
		$dbResult = $DB->Query($sql, false, $err_mess.__LINE__);
		if(!is_object($dbResult))
		{
			return;
		}

		$IDs = array();
		while($fields = $dbResult->Fetch())
		{
			if(isset($fields['EVENT_ID']))
			{
				$IDs[] = (int)$fields['EVENT_ID'];
			}
		}

		if(!empty($IDs))
		{
			$sql = 'UPDATE b_crm_event_relations SET ENTITY_ID = '.$dstEntityID.' WHERE EVENT_ID IN('.implode(',', $IDs).')';
			$DB->Query($sql, false, $err_mess.__LINE__);
		}
	}
	/**
	 * @return array
	*/
	static public function GetEventTypes()
	{
		if(self::$eventTypes === null)
		{
			self::$eventTypes = array(
				self::TYPE_USER => GetMessage('CRM_EVENT_TYPE_USER'),
				self::TYPE_CHANGE => GetMessage('CRM_EVENT_TYPE_CHANGE'),
				self::TYPE_EMAIL => GetMessage('CRM_EVENT_TYPE_SNS'),
				self::TYPE_VIEW => GetMessage('CRM_EVENT_TYPE_VIEW'),
				self::TYPE_EXPORT => GetMessage('CRM_EVENT_TYPE_EXPORT'),
				self::TYPE_DELETE => GetMessage('CRM_EVENT_TYPE_DELETE')
			);
		}
		return self::$eventTypes;
	}
	/**
	 * @return string
	*/
	static public function GetEventTypeName($eventType)
	{
		$types = self::GetEventTypes();
		return isset($types[$eventType]) ? $types[$eventType] : '';
	}
	static public function RegisterViewEvent($entityTypeID, $entityID, $userID = 0)
	{
		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);

		if(is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		if($userID <= 0)
		{
			return false;
		}

		$timestamp = time() + CTimeZone::GetOffset();
		//Event grouping interval in seconds
		$interval = HistorySettings::getCurrent()->getViewEventGroupingInterval() * 60;

		$query = new Bitrix\Main\Entity\Query(Bitrix\Crm\EventTable::getEntity());
		$query->addSelect('DATE_CREATE');
		$query->addFilter('=EVENT_TYPE', CCrmEvent::TYPE_VIEW);
		$query->addFilter('>=DATE_CREATE', ConvertTimeStamp(($timestamp - $interval), 'FULL'));

		$subQuery = new Bitrix\Main\Entity\Query(Bitrix\Crm\EventRelationsTable::getEntity());
		$subQuery->addSelect('EVENT_ID');
		$subQuery->addFilter('=ENTITY_TYPE', $entityTypeName);
		$subQuery->addFilter('=ENTITY_ID', $entityID);
		$query->addFilter('@ID', new Bitrix\Main\DB\SqlExpression($subQuery->getQuery()));

		$query->addOrder('DATE_CREATE', 'DESC');
		$query->setLimit(1);

		$dbResult = $query->exec();
		if(is_array($dbResult->fetch()))
		{
			return false;
		}

		$entity = new CCrmEvent();
		$entity->Add(
			array(
				'USER_ID' => $userID,
				'ENTITY_ID' => $entityID,
				'ENTITY_TYPE' => $entityTypeName,
				'EVENT_TYPE' => CCrmEvent::TYPE_VIEW,
				'EVENT_NAME' => CCrmEvent::GetEventTypeName(CCrmEvent::TYPE_VIEW),
				'DATE_CREATE' => ConvertTimeStamp($timestamp, 'FULL', SITE_ID)
			),
			false
		);
		return true;
	}
	static public function RegisterExportEvent($entityTypeID, $entityID, $userID = 0)
	{
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
			if($userID <= 0)
			{
				return false;
			}
		}

		$eventType = CCrmEvent::TYPE_EXPORT;
		$timestamp = time() + CTimeZone::GetOffset();
		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);

		$entity = new CCrmEvent();
		$entity->Add(
			array(
				'USER_ID' => $userID,
				'ENTITY_ID' => $entityID,
				'ENTITY_TYPE' => $entityTypeName,
				'EVENT_TYPE' => $eventType,
				'EVENT_NAME' => CCrmEvent::GetEventTypeName($eventType),
				'DATE_CREATE' => ConvertTimeStamp($timestamp, 'FULL', SITE_ID)
			),
			false
		);

		return true;
	}
	static public function RegisterDeleteEvent($entityTypeID, $entityID, $userID = 0, array $options = null)
	{
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
			if($userID <= 0)
			{
				return false;
			}
		}

		$timestamp = time() + CTimeZone::GetOffset();
		$entityTypeCaption = CCrmOwnerType::GetDescription($entityTypeID);
		$caption = CCrmOwnerType::GetCaption($entityTypeID, $entityID, false, $options);

		$entity = new CCrmEvent();
		return (
			$entity->Add(
				array(
					'USER_ID' => $userID,
					'ENTITY_ID' => 0,
					'ENTITY_TYPE' => CCrmOwnerType::SystemName,
					'EVENT_TYPE' => CCrmEvent::TYPE_DELETE,
					'EVENT_NAME' => CCrmEvent::GetEventTypeName(CCrmEvent::TYPE_DELETE),
					'DATE_CREATE' => ConvertTimeStamp($timestamp, 'FULL', SITE_ID),
					'EVENT_TEXT_1' => "{$entityTypeCaption}: [{$entityID}] {$caption}"
				),
				false
			)
		);
	}
	static public function GetEventType($ID)
	{
		$ID = (int)$ID;
		if($ID <= 0)
		{
			return -1;
		}

		$dbResult = self::GetListEx(
			array(),
			array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('EVENT_TYPE')
		);
		$arFields = is_object($dbResult) ? $dbResult->Fetch() : null;
		return is_array($arFields) && isset($arFields['EVENT_TYPE']) ? (int)$arFields['EVENT_TYPE'] : CCrmEvent::TYPE_USER;
	}
	private static function err_mess()
	{
		return '<br />Class: CCrmEvent<br />File: '.__FILE__;
	}
}

?>
