<?php
use Bitrix\Main;
use Bitrix\Crm\Restriction\RestrictionManager;

class CCrmEntityListBuilder
{
	private $dbType = '';
	private $tableName = '';
	private $tableAlias = '';
	private $fields = array();
	private $ufEntityID = '';
	private $userFields = array();
	private $fmEntityID = '';
	private $permissionCallback = array();
	private $afterPrepareSqlCallback = array();
	private $sqlData = array();

	function __construct($dbType, $tableName, $tableAlias, $fields, $ufEntityID = '', $fmEntityID = '',  $permissionCallback = array(), $afterPrepareSqlCallback = array())
	{
		$this->dbType = strval($dbType);
		if($this->dbType === '')
		{
			$this->dbType = 'mysql';
		}

		$this->tableName = strval($tableName);
		$this->tableAlias = strval($tableAlias);

		if(is_array($fields))
		{
			$this->fields = $fields;
		}

		$this->ufEntityID = strval($ufEntityID);
		$this->fmEntityID = strval($fmEntityID);

		if(is_array($permissionCallback))
		{
			$this->permissionCallback = $permissionCallback;
		}

		if(is_array($afterPrepareSqlCallback))
		{
			$this->afterPrepareSqlCallback = $afterPrepareSqlCallback;
		}
	}

	public function GetTableName()
	{
		return $this->tableName;
	}

	public function GetTableAlias()
	{
		return $this->tableAlias;
	}

	public function GetFields()
	{
		return $this->fields;
	}

	public function SetFields(array $fields)
	{
		return $this->fields = $fields;
	}

	public function GetSqlData()
	{
		return $this->sqlData;
	}

	//Override user fields
	public function SetUserFields($fields)
	{
		if(is_array($fields))
		{
			$this->userFields = $fields;
		}
	}

	public function GetUserFields()
	{
		return $this->userFields;
	}

	private function Insert2SqlOrder($sql, $position)
	{
		if(!(isset($this->sqlData['ORDERBY']) && $this->sqlData['ORDERBY'] !== ''))
		{
			$this->sqlData['ORDERBY'] = $sql;
			return;
		}

		$parts = explode(',', $this->sqlData['ORDERBY']);
		array_splice($parts, $position, 0, array($sql));
		$this->sqlData['ORDERBY'] = implode(', ', $parts);
	}
	private function Add2SqlData($sql, $type, $add2Start = false, $replace = '')
	{
		$sql = strval($sql);
		if($sql === '')
		{
			return;
		}

		if($type === 'SELECT')
		{
			if (isset($this->sqlData['SELECT']) && $this->sqlData['SELECT'] !== '')
			{
				$this->sqlData['SELECT'] .= $sql;
			}
			else
			{
				$this->sqlData['SELECT'] = $sql;
			}
		}
		elseif($type === 'FROM')
		{
			if (!isset($this->sqlData['FROM']) || $this->sqlData['FROM'] === '')
			{
				$this->sqlData['FROM'] = $sql;
			}
			else
			{
				if($replace !== '' && mb_strpos($this->sqlData['FROM'], $replace) !== false)
				{
					$this->sqlData['FROM'] = str_replace($replace, $sql, $this->sqlData['FROM']);
				}
				elseif(mb_stripos($this->sqlData['FROM'], trim($sql)) === false)
				{
					if($add2Start)
					{
						$this->sqlData['FROM'] = $sql.' '.$this->sqlData['FROM'];
					}
					else
					{
						$this->sqlData['FROM'] .= ' '.$sql;
					}
				}
			}
		}
		elseif($type === 'WHERE')
		{
			if (isset($this->sqlData['WHERE']) && $this->sqlData['WHERE'] !== '')
			{
				$this->sqlData['WHERE'] = "({$this->sqlData['WHERE']}) AND ($sql)";
			}
			else
			{
				$this->sqlData['WHERE'] = $sql;
			}
		}
		elseif($type === 'ORDERBY')
		{
			if (isset($this->sqlData['ORDERBY']) && $this->sqlData['ORDERBY'] !== '')
			{
				$this->sqlData['ORDERBY'] .= ', '.$sql;
			}
			else
			{
				$this->sqlData['ORDERBY'] = $sql;
			}
		}
	}

	public function Prepare($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array(), $arOptions = array())
	{
		global $DB;

		if(!is_array($arOrder))
		{
			$arOrder = array();
		}

		if(!is_array($arFilter))
		{
			$arFilter = array();
		}

		foreach($arFilter as $key => $value)
		{
			if ($key === 'SEARCH_CONTENT')
			{
				continue;
			}
			if ($key === 'RQ')
			{
				foreach($value as $rqKey => $item)
				{
					if ($item['VALUE'] === '^%^')
					{
						$arFilter[$key][$rqKey]['OPERATION'] = '!=';
						$arFilter[$key][$rqKey]['VALUE'] = false;
					}
					if ($item['VALUE'] === '^&^')
					{
						$arFilter[$key][$rqKey]['OPERATION'] = '=';
						$arFilter[$key][$rqKey]['VALUE'] = false;
					}
				}
			}
			else
			{
				if($value === '^%^' || $value === '^&^')
				{
					unset($arFilter[$key]);
					if(mb_strpos($key, '?') === 0)
					{
						$key = mb_substr($key, 1);
					}
				}
				if($value === '^%^')
				{
					$arFilter['!' . $key] = false;
				}
				elseif($value === '^&^')
				{
					$arFilter[$key] = false;
				}
			}
		}

		// ID must present in select (If select is empty it will be filled by CSqlUtil::PrepareSql)
		if(!is_array($arSelectFields))
		{
			$arSelectFields = array();
		}

		if(count($arSelectFields) > 0 && !in_array('*', $arSelectFields, true) && !in_array('ID', $arSelectFields, true))
		{
			$arSelectFields[] = 'ID';
		}

		if(!is_array($arOptions))
		{
			$arOptions = array();
		}
		$arOptions['DB_TYPE'] = $this->dbType;

		$isExternalContext = isset($arOptions['IS_EXTERNAL_CONTEXT'])
			&& ($arOptions['IS_EXTERNAL_CONTEXT'] === true || $arOptions['IS_EXTERNAL_CONTEXT'] === 'Y');
		if($isExternalContext)
		{
			// Sanitizing of filter data
			unset($arFilter['CHECK_PERMISSIONS'], $arFilter['__JOINS'], $arFilter['__CONDITIONS']);
		}
		$needReturnSql = (bool)($arOptions['NEED_RETURN_SQL'] ?? false);

		// Processing of special fields
		if ($this->fmEntityID !== '' && isset($arFilter['FM']))
		{
			CCrmFieldMulti::PrepareExternalFilter(
				$arFilter,
				array(
					'ENTITY_ID' => $this->fmEntityID,
					'MASTER_ALIAS' => $this->tableAlias,
					'MASTER_IDENTITY' => 'ID'
				)
			);
		}

		// Processing of requisite fields
		if (isset($arFilter['RQ']))
		{
			$rqEntityTypeId = CCrmOwnerType::Undefined;
			if ($this->fmEntityID === 'COMPANY')
				$rqEntityTypeId = CCrmOwnerType::Company;
			else if ($this->fmEntityID === 'CONTACT')
				$rqEntityTypeId = CCrmOwnerType::Contact;
			if ($rqEntityTypeId !== CCrmOwnerType::Undefined)
			{
				$requisite = new Bitrix\Crm\EntityRequisite();
				$requisite->prepareEntityListExternalFilter(
					$arFilter,
					array(
						'ENTITY_TYPE_ID' => $rqEntityTypeId,
						'MASTER_ALIAS' => $this->tableAlias,
						'MASTER_IDENTITY' => 'ID'
					)
				);
				unset($requisite);
			}
			unset($rqEntityTypeId);
		}

		// Processing user fields
		$ufSelectSql = null;
		$ufFilterSql = null;
		if($this->ufEntityID !== '')
		{
			$ufSelectSql = new CUserTypeSQL();
			$ufSelectSql->SetEntity($this->ufEntityID, $this->tableAlias.'.ID');
			$ufSelectSql->SetSelect($arSelectFields);
			$ufSelectSql->SetOrder($arOrder);

			$ufFilterSql = new CUserTypeSQL();
			$ufFilterSql->SetEntity($this->ufEntityID, $this->tableAlias.'.ID');

			$ufFilter = array();
			foreach($arFilter as $filterKey => $filterValue)
			{
				//Adapt nested filters for UserTypeSQL
				if(mb_strpos($filterKey, '__INNER_FILTER') === 0)
				{
					$ufFilter[] = $filterValue;
				}
				else
				{
					$ufFilter[$filterKey] = $filterValue;
				}
			}

			$userType = new CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], $this->ufEntityID);
			$userType->ListPrepareFilter($ufFilter);

			$ufFilterSql->SetFilter($ufFilter);
		}

		$this->sqlData = CSqlUtil::PrepareSql($this->fields, $arOrder, $arFilter, $arGroupBy, $arSelectFields, $arOptions);
		$this->sqlData['SELECT'] = str_replace('%%_DISTINCT_%% ', '', $this->sqlData['SELECT']);

		// 'JOINS' and 'CONDITIONS' for implement custom filter logic
		if(isset($arFilter['__JOINS']))
		{
			if(is_array($arFilter['__JOINS']))
			{
				foreach($arFilter['__JOINS'] as $join)
				{
					// INNER JOINs will be added tostart
					$this->Add2SqlData($join['SQL'], 'FROM', (!isset($join['TYPE']) || $join['TYPE'] === 'INNER'), (isset($join['REPLACE']) ? $join['REPLACE'] : ''));

					if(isset($this->sqlData['FROM_WHERE']))
					{
						$this->sqlData['FROM_WHERE'] .= ' ';
					}
					$this->sqlData['FROM_WHERE'] .= $join['SQL'];
				}
			}
			unset($arFilter['__JOINS']);
		}
		if(isset($arFilter['__CONDITIONS']))
		{
			if(is_array($arFilter['__CONDITIONS']))
			{
				foreach($arFilter['__CONDITIONS'] as $condition)
				{
					$this->Add2SqlData($condition['SQL'], 'WHERE');
				}
			}
			unset($arFilter['__CONDITIONS']);
		}
		unset($arFilter['__JOINS'], $arFilter['__CONDITIONS']);

		// Apply user permission logic
		if(count($this->permissionCallback) > 0)
		{
			$needCheckPermissions = (!array_key_exists('CHECK_PERMISSIONS', $arFilter) || $arFilter['CHECK_PERMISSIONS'] !== 'N');
			if ($needCheckPermissions)
			{
				$permissionsUserId = null;
				if (isset($arOptions['PERMS']) && is_object($arOptions['PERMS']))
				{
					/** @var \CCrmPerms $arOptions['PERMS'] */
					$permissionsUserId = $arOptions['PERMS']->GetUserID();
				}
				$needCheckPermissions = !\Bitrix\Crm\Service\Container::getInstance()->getUserPermissions($permissionsUserId)->isAdmin();
			}
			if ($needCheckPermissions)
			{
				if(count($arFilter) === 1 && isset($arFilter['ID']) || isset($arFilter['=ID']) || isset($arFilter['@ID']))
				{
					if(isset($arFilter['ID']))
					{
						$arOptions['RESTRICT_BY_IDS'] = is_array($arFilter['ID']) ? $arFilter['ID'] : array($arFilter['ID']);
					}
					elseif(isset($arFilter['=ID']))
					{
						$arOptions['RESTRICT_BY_IDS'] = is_array($arFilter['=ID']) ? $arFilter['=ID'] : array($arFilter['=ID']);
					}
					elseif(isset($arFilter['@ID']))
					{
						$arOptions['RESTRICT_BY_IDS'] = is_array($arFilter['@ID']) ? $arFilter['@ID'] : array($arFilter['@ID']);
					}
				}
				$arPermType = !isset($arFilter['PERMISSION']) ? 'READ' : (is_array($arFilter['PERMISSION']) ? $arFilter['PERMISSION'] : array($arFilter['PERMISSION']));
				$permissionSql = call_user_func_array($this->permissionCallback, array($this->tableAlias, $arPermType, $arOptions));

				if(is_bool($permissionSql) && !$permissionSql)
				{
					if ($needReturnSql)
					{
						return null;
					}
					//Get count only
					if(is_array($arGroupBy) && count($arGroupBy) == 0)
					{
						return 0;
					}

					$CDBResult = new CDBResult();
					$CDBResult->InitFromArray(array());
					return $CDBResult;
				}

				if($permissionSql !== '')
				{
					$sqlType = isset($arOptions['PERMISSION_SQL_TYPE']) && $arOptions['PERMISSION_SQL_TYPE'] === 'FROM' ? 'FROM' : 'WHERE';
					$this->Add2SqlData($permissionSql, $sqlType, $sqlType === 'FROM');

					if($sqlType === 'FROM')
					{
						if(isset($this->sqlData['FROM_WHERE']))
						{
							$this->sqlData['FROM_WHERE'] .= ' ';
						}
						$this->sqlData['FROM_WHERE'] .= $permissionSql;
					}
				}
			}
		}

		// Apply custom SQL logic
		if(count($this->afterPrepareSqlCallback) > 0)
		{
			$arUserSql = call_user_func_array($this->afterPrepareSqlCallback, array($this, $arOrder, $arFilter, $arGroupBy, $arSelectFields));
			if(is_array($arUserSql))
			{
				if(isset($arUserSql['SELECT']))
				{
					$this->Add2SqlData($arUserSql['SELECT'], 'SELECT');
				}

				if(isset($arUserSql['FROM']))
				{
					$this->Add2SqlData($arUserSql['FROM'], 'FROM');

					if(isset($this->sqlData['FROM_WHERE']))
					{
						$this->sqlData['FROM_WHERE'] .= ' ';
					}
					$this->sqlData['FROM_WHERE'] .= $arUserSql['FROM'];
				}

				if(isset($arUserSql['WHERE']))
				{
					$this->Add2SqlData($arUserSql['WHERE'], 'WHERE');
				}

				if(isset($arUserSql['ORDERBY']))
				{
					if (is_array($arUserSql['ORDERBY']))
					{
						if (isset($arUserSql['ORDERBY']['SQL'], $arUserSql['ORDERBY']['POSITION']))
						{
							$this->Insert2SqlOrder($arUserSql['ORDERBY']['SQL'], $arUserSql['ORDERBY']['POSITION']);
						}
					}
					else
					{
						$this->Add2SqlData($arUserSql['ORDERBY'], 'ORDERBY');
					}
				}
			}
		}

		if($ufSelectSql)
		{
			// Adding user fields to SELECT
			$this->Add2SqlData($ufSelectSql->GetSelect(), 'SELECT');

			// Adding user fields to ORDER BY
			if(is_array($arOrder))
			{
				$orderKeyPos = 0;
				foreach ($arOrder as $orderKey => $order)
				{
					$orderSql = $ufSelectSql->GetOrder($orderKey);
					if(is_string($orderSql) && $orderSql !== '')
					{
						$order = mb_strtoupper($order);
						if($order !== 'ASC' && $order !== 'DESC')
						{
							$order = 'ASC';
						}

						$this->Insert2SqlOrder("$orderSql $order", $orderKeyPos);
					}
					$orderKeyPos++;
				}
			}

			// Adding user fields to joins
			$this->Add2SqlData($ufSelectSql->GetJoin($this->tableAlias.'.ID'), 'FROM');
		}

		if($ufFilterSql)
		{
			// Adding user fields to WHERE
			$ufWhere = $ufFilterSql->GetFilter();
			if($ufWhere !== '')
			{
				$ufSql = $this->tableAlias.'.ID IN (SELECT '
					.$this->tableAlias.'.ID FROM '.$this->tableName.' '.$this->tableAlias.' '
					.$ufFilterSql->GetJoin($this->tableAlias.'.ID').' WHERE '.$ufWhere.')';

					// Adding user fields to joins
					$this->Add2SqlData($ufSql, 'WHERE');
			}
		}

		$enableRowCountThreshold = !isset($arOptions['ENABLE_ROW_COUNT_THRESHOLD'])
			|| ($arOptions['ENABLE_ROW_COUNT_THRESHOLD'] === true || $arOptions['ENABLE_ROW_COUNT_THRESHOLD'] === 'Y');

		//Get count only
		if (is_array($arGroupBy) && count($arGroupBy) == 0)
		{
			if ($needReturnSql)
			{
				return $this->GetRowCountSql(
					$enableRowCountThreshold
						? RestrictionManager::getSqlRestriction()->getRowCountThreshold()
						: 0
				);
			}

			return $this->GetRowCount(
				$enableRowCountThreshold
					? RestrictionManager::getSqlRestriction()->getRowCountThreshold()
					: 0
			);
		}

		$sql = 'SELECT '.$this->sqlData['SELECT'].' FROM '.$this->tableName.' '.$this->tableAlias;

		if (isset($this->sqlData['FROM'][0]))
		{
			$sql .= ' '.$this->sqlData['FROM'];
		}

		if (isset($this->sqlData['WHERE'][0]))
		{
			$sql .= ' WHERE '.$this->sqlData['WHERE'];
		}

		if (isset($this->sqlData['GROUPBY'][0]))
		{
			$sql .= ' GROUP BY '.$this->sqlData['GROUPBY'];
		}

		if (isset($this->sqlData['ORDERBY'][0]))
		{
			$sql .= ' ORDER BY '.$this->sqlData['ORDERBY'];
		}

		$enableNavigation = is_array($arNavStartParams);
		$top = $enableNavigation && isset($arNavStartParams['nTopCount']) ? intval($arNavStartParams['nTopCount']) : 0;
		if ($enableNavigation && $top <= 0)
		{
			if ($needReturnSql)
			{
				return $sql;
			}

			$dbRes = new CDBResult();
			if($this->ufEntityID !== '')
			{
				$dbRes->SetUserFields($GLOBALS['USER_FIELD_MANAGER']->GetUserFields($this->ufEntityID));
			}
			elseif(!empty($this->userFields))
			{
				$dbRes->SetUserFields($this->userFields);
			}

			//Trace('CCrmEntityListBuilder::Prepare, SQL', $sql, 1);
			$cnt = $this->GetRowCount(RestrictionManager::getSqlRestriction()->getRowCountThreshold());
			$dbRes->NavQuery($sql, $cnt, $arNavStartParams);
		}
		else
		{
			$limit = $top;
			$offset = 0;

			if(isset($arOptions['QUERY_OPTIONS']) && is_array($arOptions['QUERY_OPTIONS']))
			{
				$queryOptions = $arOptions['QUERY_OPTIONS'];
				$limit = isset($queryOptions['LIMIT']) ? (int)$queryOptions['LIMIT'] : 0;
				$offset = isset($queryOptions['OFFSET']) ? (int)$queryOptions['OFFSET'] : 0;
			}

			$threshold = $enableRowCountThreshold ? RestrictionManager::getSqlRestriction()->getRowCountThreshold() : 0;
			if($threshold > 0 && $threshold < ($limit + $offset))
			{
				$delta = $threshold - $offset;
				if($delta <= 0)
				{
					$obRes = new CDBResult();
					$obRes->InitFromArray(array());
					return $obRes;
				}

				$limit = $delta;
			}

			if($limit > 0)
			{
				$sql = Main\Application::getConnection()->getSqlHelper()->getTopSql($sql, $limit, $offset);
			}

			if ($needReturnSql)
			{
				return $sql;
			}

			//Trace('CCrmEntityListBuilder::Prepare, SQL', $sql, 1);
			$dbRes = $DB->Query($sql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
			if($this->ufEntityID !== '')
			{
				$dbRes->SetUserFields($GLOBALS['USER_FIELD_MANAGER']->GetUserFields($this->ufEntityID));
			}
			elseif(!empty($this->userFields))
			{
				$dbRes->SetUserFields($this->userFields);
			}
		}
		return $dbRes;
	}

	private function GetRowCountSql($threshold)
	{
		$fieldsKeys = array_keys($this->fields);
		$primaryKey = $fieldsKeys[0];

		if($threshold > 0)
		{
			if(isset($this->tableAlias[0]))
			{
				$sql = "SELECT {$this->tableAlias}.{$primaryKey} FROM {$this->tableName} {$this->tableAlias}";
			}
			else
			{
				$sql = "SELECT {$primaryKey} FROM {$this->tableName}";
			}

			if(isset($this->sqlData['FROM_WHERE'][0]))
			{
				$sql .= ' '.$this->sqlData['FROM_WHERE'];
			}
			elseif (isset($this->sqlData['FROM'][0]))
			{
				$sql .= ' '.$this->sqlData['FROM'];
			}
			elseif(isset($this->fields[$primaryKey]['FROM']) && isset($this->fields[$primaryKey]['FROM'][0]))
			{
				//Hack for CrmEvent table.
				$sql .= ' '.$this->fields['ID']['FROM'];
			}
			if (isset($this->sqlData['WHERE'][0]))
			{
				$sql .= ' WHERE '.$this->sqlData['WHERE'];
			}
			if (isset($arSql['GROUPBY'][0]))
			{
				$sql .= ' GROUP BY '.$this->sqlData['GROUPBY'];
			}

			$sql = Main\Application::getConnection()->getSqlHelper()->getTopSql($sql, $threshold, 0);
			$sql = "SELECT COUNT(*) AS CNT FROM ({$sql}) T";
		}
		else
		{
			$sql = "SELECT COUNT('x') as CNT FROM {$this->tableName}";
			if(isset($this->tableAlias[0]))
			{
				$sql .= ' '.$this->tableAlias;
			}

			if(isset($this->sqlData['FROM_WHERE']) && $this->sqlData['FROM_WHERE'] !== '')
			{
				$sql .= ' '.$this->sqlData['FROM_WHERE'];
			}
			elseif(isset($this->sqlData['FROM']) && $this->sqlData['FROM'] !== '')
			{
				$sql .= ' '.$this->sqlData['FROM'];
			}
			elseif(isset($this->fields['ID'])
				&& isset($this->fields['ID']['FROM'])
				&& $this->fields['ID']['FROM'] !== '')
			{
				//Hack for CrmEvent table.
				$sql .= ' '.$this->fields['ID']['FROM'];
			}

			if (isset($this->sqlData['WHERE'][0]))
			{
				$sql .= ' WHERE '.$this->sqlData['WHERE'];
			}
			if (isset($arSql['GROUPBY'][0]))
			{
				$sql .= ' GROUP BY '.$this->sqlData['GROUPBY'];
			}
		}

		return $sql;
	}

	public function GetRowCount($threshold)
	{
		global $DB;

		$sql = $this->GetRowCountSql($threshold);

		$dbResult = $DB->Query($sql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
		$result = 0;
		while($ary = $dbResult->Fetch())
		{
			$result += (int)$ary['CNT'];
		}

		return $result;
	}

	public static function PrepareFromQueryData(array $arSql, $tableName, $tableAlias, $dbType, $arNavStartParams = false)
	{
		global $DB;

		$sql = 'SELECT '.$arSql['SELECT'].' FROM '.$tableName.' '.$tableAlias.' '.$arSql['FROM'].' GROUP BY '.$arSql['GROUPBY'].' ORDER BY '.$arSql['ORDERBY'];
		$enableNavigation = is_array($arNavStartParams);
		$top = $enableNavigation && isset($arNavStartParams['nTopCount']) ? (int)$arNavStartParams['nTopCount'] : 0;
		if ($enableNavigation && $top <= 0)
		{
			if(COption::GetOptionString('crm', 'enable_rough_row_count', 'Y') === 'Y')
			{
				$cnt = self::GetRoughRowCount($arSql, $tableName, $tableAlias, $dbType);
			}
			else
			{
				$cnt = CSqlUtil::GetRowCount($arSql, $tableName, $tableAlias, $dbType);
			}

			$dbResult = new CDBResult();
			$dbResult->NavQuery($sql, $cnt, $arNavStartParams);
			return $dbResult;
		}

		if($enableNavigation && $top > 0)
		{
			CSqlUtil::PrepareSelectTop($sql, $top, $dbType);
		}
		$dbResult = $DB->Query($sql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
		return $dbResult;
	}

	private static function GetRoughRowCount(&$arSql, $tableName, $tableAlias = '', $dbType = '')
	{
		global $DB;

		$tableName = strval($tableName);
		$tableAlias = strval($tableAlias);
		$dbType = strval($dbType);
		if($dbType === '')
		{
			$dbType = 'MYSQL';
		}
		else
		{
			$dbType = mb_strtoupper($dbType);
		}

		if($dbType !== 'MYSQL')
		{
			return CSqlUtil::GetRowCount($arSql, $tableName, $tableAlias, $dbType);
		}

		$subQuery = $tableAlias !== ''
			? "SELECT {$tableAlias}.ID FROM {$tableName} {$tableAlias}"
			: "SELECT ID FROM {$tableName}";

		if ($arSql['FROM'] !== '')
		{
			$subQuery .= ' '.$arSql['FROM'];
		}

		if ($arSql['WHERE'] !== '')
		{
			$subQuery .= ' WHERE '.$arSql['WHERE'];
		}

		if ($arSql['GROUPBY'] !== '')
		{
			$subQuery .= ' GROUP BY '.$arSql['GROUPBY'];
		}

		$query = "SELECT COUNT(*) as CNT FROM ($subQuery ORDER BY NULL LIMIT 0, 5000) AS T";
		$rs = $DB->Query($query, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);

		$result = 0;
		while($ary = $rs->Fetch())
		{
			$result += intval($ary['CNT']);
		}

		return $result;
	}
}
