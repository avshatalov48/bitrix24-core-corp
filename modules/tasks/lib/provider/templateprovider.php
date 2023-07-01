<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Provider;

use Bitrix\Tasks\Access\Permission\PermissionDictionary;
use Bitrix\Tasks\Access\Permission\TasksTemplatePermissionTable;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Internals\Task\Template\ScenarioTable;
use Bitrix\Tasks\Internals\Task\Template\TemplateMemberTable;
use Bitrix\Tasks\Util\User;
use \CDBResult;
use \CUserTypeSQL;
use Bitrix\Tasks\Template\DependencyTable;
use \CTasks;

class TemplateProvider
{
	use UserProviderTrait;

	private $db;
	private $userFieldManager;

	private
		$arOrder,
		$arFilter,
		$arSelect,
		$arParams,
		$arNavParams;

	private
		$arSqlSearch = [],
		$obUserFieldsSql,
		$arFields,
		$strFrom,
		$strWhere = '',
		$strSqlOrder = '',
		$strSqlSelect;

	public function __construct(\CDatabase $db, \CUserTypeManager $userFieldManager)
	{
		$this->db = $db;
		$this->userFieldManager = $userFieldManager;
	}

	public function getList($arOrder = [], $arFilter = [], $arSelect = [], $arParams = [], $arNavParams = []): CDBResult
	{
		$this->configure($arOrder, $arFilter, $arSelect, $arParams, $arNavParams);

		$this
			->makeUserFields()
			->makeArSqlSearch()
			->makeArFields()
			->makeArSelect()
			->makeFrom()
			->makeOrder()
			->makeSelect()
			->makeAccessSql()
			->makeWhere();

		$query = $this->buildQuery();

		$result = $this->executeQuery($query);

		$rows = [];
		while ($row = $result->Fetch())
		{
			$rows[] = $row;
		}

		if (empty($rows))
		{
			return $result;
		}

		$templateIds = array_column($rows, 'ID');
		$members = $this->getMembers($templateIds);

		foreach ($rows as $k => $row)
		{
			$row['RESPONSIBLES'] = (isset($members[$row['ID']][MemberTable::MEMBER_TYPE_RESPONSIBLE]))
				? serialize($members[$row['ID']][MemberTable::MEMBER_TYPE_RESPONSIBLE])
				: serialize([]);
			$row['ACCOMPLICES'] = (isset($members[$row['ID']][MemberTable::MEMBER_TYPE_ACCOMPLICE]))
				? serialize($members[$row['ID']][MemberTable::MEMBER_TYPE_ACCOMPLICE])
				: serialize([]);
			$row['AUDITORS'] = (isset($members[$row['ID']][MemberTable::MEMBER_TYPE_AUDITOR]))
				? serialize($members[$row['ID']][MemberTable::MEMBER_TYPE_AUDITOR])
				: serialize([]);

			$rows[$k] = $row;
		}

		$cdbResult = new CDBResult($result);
		$cdbResult->InitFromArray($rows);

		return $cdbResult;
	}

	public function getCount($includeSubTemplates = false, array $arParams = []): int
	{
		$this->configure([], [], [], $arParams, []);

		$tableName = DependencyTable::getTableName();
		$parentIdColumnName = DependencyTable::getPARENTIDColumnName();

		if (!$this->userId)
		{
			return 0;
		}

		$this->strSqlSelect = 'COUNT(DISTINCT TT.ID) AS CNT';
		$this->strFrom 		= 'FROM b_tasks_template TT';

		if (!$includeSubTemplates)
		{
			$this->strFrom .= "\nLEFT JOIN " . $tableName . " TDD ON TT.ID = TDD.TEMPLATE_ID AND TDD.DIRECT = 1";
			$this->arSqlSearch[] = "TDD." . $parentIdColumnName . " IS NULL";
		}

		$this->makeAccessSql();
		$this->makeWhere();

		$query = $this->buildQuery();

		if ($dbRes = $this->db->Query($query, false, "File: ".__FILE__."<br>Line: ".__LINE__))
		{
			if ($arRes = $dbRes->Fetch())
			{
				return (int) $arRes["CNT"];
			}
		}

		return 0;
	}

	private function getMembers(array $templateIds): array
	{
		$members = TemplateMemberTable::getList([
			'select' => ['*'],
			'filter' => [
				'@TEMPLATE_ID' => $templateIds,
			],
		])->fetchAll();

		$result = [];
		foreach ($members as $member)
		{
			$result[$member['TEMPLATE_ID']][$member['TYPE']][] = $member['USER_ID'];
		}

		return $result;
	}

	private function makeAccessSql(): self
	{
		if (!$this->userId)
		{
			return $this;
		}

		$isAdmin = (array_key_exists('USER_IS_ADMIN', $this->arParams) ? $this->arParams['USER_IS_ADMIN'] : User::isSuper($this->userId));
		if ($isAdmin)
		{
			return $this;
		}

		$query = [];
		$permissions = $this->getPermissions();

		// user can view department's templates
		$departmentMembers = $this->getDepartmentMembers();

		if (
			!empty($departmentMembers)
			&& in_array(PermissionDictionary::TEMPLATE_DEPARTMENT_VIEW, $permissions)
		)
		{
			$query[] = 'TT.CREATED_BY IN ('. implode(',', $departmentMembers) .')';
		}

		// non department's templates
		if (in_array(PermissionDictionary::TEMPLATE_NON_DEPARTMENT_VIEW, $permissions))
		{
			$query[] = 'TT.CREATED_BY NOT IN ('. (!empty($departmentMembers) ? implode(',', $departmentMembers) : 0) .')';
		}

		// individual rights
		$accessCodes = $this->getUserModel()->getAccessCodes();
		if (!empty($accessCodes))
		{
			$this->strFrom .= "\nLEFT JOIN ". TasksTemplatePermissionTable::getTableName() . " TTP ON TTP.TEMPLATE_ID = TT.ID";
			$query[] = '
				TTP.ACCESS_CODE IN ("'. implode('","', $accessCodes) .'")
				AND TTP.PERMISSION_ID IN ('. PermissionDictionary::TEMPLATE_VIEW .', '. PermissionDictionary::TEMPLATE_FULL .')
			';
		}

		if (empty($query))
		{
			$this->arSqlSearch[] = '(1 = 0)';
		}
		else
		{
			$this->arSqlSearch[] = '((' . implode(') OR (', $query) . '))';
		}

		return $this;
	}

	private function executeQuery(string $query): CDBResult
	{
		if (
			isset($this->arNavParams["NAV_PARAMS"])
			&& is_array($this->arNavParams["NAV_PARAMS"])
		)
		{
			$nTopCount = (int) ($this->arNavParams['NAV_PARAMS']['nTopCount'] ?? 0);

			if ($nTopCount > 0)
			{
				$query = $this->db->TopSql($query, $nTopCount);
				$res = $this->db->Query($query, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);

				$res->SetUserFields($this->userFieldManager->GetUserFields("TASKS_TASK_TEMPLATE"));
			}
			else
			{
				$res_cnt = $this->db->Query("SELECT COUNT(DISTINCT TT.ID) as C " . $this->strFrom . " " . $this->strWhere);
				$res_cnt = $res_cnt->Fetch();
				$res = new CDBResult();
				$res->SetUserFields($this->userFieldManager->GetUserFields("TASKS_TASK_TEMPLATE"));
				$res->NavQuery($query, $res_cnt["C"], $this->arNavParams["NAV_PARAMS"]);
			}
		}
		else
		{
			$res = $this->db->Query($query, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);
			$res->SetUserFields($this->userFieldManager->GetUserFields("TASKS_TASK_TEMPLATE"));
		}

		return $res;
	}

	private function buildQuery(): string
	{
		$query = "
			SELECT DISTINCT
				". $this->strSqlSelect ."
				". $this->strFrom ."
				". $this->strWhere ."
				". $this->strSqlOrder;

		return $query;
	}

	private function makeSelect(): self
	{
		$this->strSqlSelect = "TT.ID AS ID";

		$arSqlSelect = [];
		foreach ($this->arSelect as $field)
		{
			$field = strtoupper($field);
			if (array_key_exists($field, $this->arFields))
			{
				$arSqlSelect[$field] = \Bitrix\Tasks\DB\Helper::wrapColumnWithFunction($this->arFields[$field]['FIELD'])." AS ".$field;
			}
		}

		if (count($arSqlSelect))
		{
			$this->strSqlSelect = implode(",\n", $arSqlSelect);
		}

		$ufSelect = $this->obUserFieldsSql->GetSelect();
		if(strlen($ufSelect))
		{
			$this->strSqlSelect .= $ufSelect;
		}

		return $this;
	}

	private function makeOrder(): self
	{
		$arSqlOrder = [];
		foreach ($this->arOrder as $by => $order)
		{
			$by = strtolower($by);
			$order = strtolower($order);
			if ($order != "asc")
				$order = "desc";

			if ($by == "task")
				$arSqlOrder[] = " TT ".$order." ";
			elseif ($by == "id")
				$arSqlOrder[] = " TT.ID ".$order." ";
			elseif ($by == "group_id")
				$arSqlOrder[] = " TT.GROUP_ID ".$order." ";
			elseif ($by == "title")
				$arSqlOrder[] = " TT.TITLE ".$order." ";
			elseif ($by == "deadline")
				$arSqlOrder[] = " TT.DEADLINE_AFTER ".$order." ";
			elseif ($by == "depends_on")
				$arSqlOrder[] = " TT.DEPENDS_ON ".$order." ";
			elseif ($by == "rand")
				$arSqlOrder[] = ' RAND(' . rand(0, 1000000) . ') ';
			elseif ($by === 'creator_last_name')
				$arSqlOrder[] = " CU.LAST_NAME ".$order." ";
			elseif ($by === 'responsible_last_name')
				$arSqlOrder[] = " RU.LAST_NAME ".$order." ";
			elseif ($by === 'tparam_type')
				$arSqlOrder[] = " TT.TPARAM_TYPE ".$order." ";
			elseif ($by === 'template_children_count')
				$arSqlOrder[] = " TEMPLATE_CHILDREN_COUNT ".$order." ";
			elseif ($by === 'base_template_id')
				$arSqlOrder[] = " BASE_TEMPLATE_ID ".$order." ";
			elseif(substr($by, 0, 3) === 'uf_')
			{
				if ($s = $this->obUserFieldsSql->GetOrder($by))
				{
					$arSqlOrder[$by] = " ".$s." ".$order." ";
				}
			}
			else
			{
				$arSqlOrder[] = " TT.ID ".$order." ";
				$by = "id";
			}

			if (
				($by !== 'rand')
				&& ( ! in_array(strtoupper($by), $this->arSelect) )
			)
			{
				$this->arSelect[] = strtoupper($by);
			}
		}

		DelDuplicateSort($arSqlOrder);
		$arSqlOrderCnt = count($arSqlOrder);
		for ($i = 0; $i < $arSqlOrderCnt; $i++)
		{
			if ($i == 0)
			{
				$this->strSqlOrder = " ORDER BY ";
			}
			else
			{
				$this->strSqlOrder .= ",";
			}

			$this->strSqlOrder .= $arSqlOrder[$i];
		}

		return $this;
	}

	private function makeWhere(): self
	{
		if (count($this->arSqlSearch))
		{
			$this->strWhere = "WHERE " . implode(" AND ", $this->arSqlSearch);
		}

		return $this;
	}

	private function makeFrom(): self
	{
		$selectBaseTemplateId = in_array('BASE_TEMPLATE_ID', $this->arSelect);
		$useChildrenCount = in_array('TEMPLATE_CHILDREN_COUNT', $this->arSelect);

		foreach($this->arOrder as $field => $direction)
		{
			if($field == 'BASE_TEMPLATE_ID')
			{
				$selectBaseTemplateId = true;
			}
			if($field == 'TEMPLATE_CHILDREN_COUNT')
			{
				$useChildrenCount = true;
			}
		}

		foreach($this->arFilter as $key => $value)
		{
			$keyParsed = CTasks::MkOperationFilter($key);
			if($keyParsed['FIELD'] == 'TEMPLATE_CHILDREN_COUNT')
			{
				$useChildrenCount = true;
			}
		}

		$includeSubtree = isset($this->arParams['INCLUDE_TEMPLATE_SUBTREE']) && ($this->arParams['INCLUDE_TEMPLATE_SUBTREE'] === true || $this->arParams['INCLUDE_TEMPLATE_SUBTREE'] === 'Y');
		$excludeSubtree = isset($this->arParams['EXCLUDE_TEMPLATE_SUBTREE']) && ($this->arParams['EXCLUDE_TEMPLATE_SUBTREE'] === true || $this->arParams['EXCLUDE_TEMPLATE_SUBTREE'] === 'Y');

		if($excludeSubtree)
		{
			$treeJoin = "";
		}
		else
		{
			$treeJoin = "LEFT JOIN ". DependencyTable::getTableName() ." TD on TT.ID = TD.TEMPLATE_ID".($includeSubtree ? "" : " AND TD.DIRECT = 1");
		}

		$temporalTableName = \Bitrix\Tasks\DB\Helper::getTemporaryTableNameSql();

		$this->strFrom = "FROM
				b_tasks_template TT

			". $treeJoin ."

			".($selectBaseTemplateId ? "
			LEFT JOIN
				". DependencyTable::getTableName() ." TDD ON TT.ID = TDD.TEMPLATE_ID AND TDD.DIRECT = 1
			" : "
			")."

			".($useChildrenCount ? "
				LEFT JOIN (
					SELECT TTI.ID, COUNT(TDDC.TEMPLATE_ID) AS TEMPLATE_CHILDREN_COUNT
					from
						b_tasks_template TTI
						INNER JOIN ". DependencyTable::getTableName() ." TDDC ON TTI.ID = TDDC.PARENT_TEMPLATE_ID AND TDDC.DIRECT = 1
					GROUP BY TTI.ID
				) ".$temporalTableName." on ".$temporalTableName.".ID = TT.ID
			" : "
			")."

			LEFT JOIN
				b_user CU ON CU.ID = TT.CREATED_BY
			LEFT JOIN
				b_user RU ON RU.ID = TT.RESPONSIBLE_ID
			INNER JOIN
				 " . ScenarioTable::getTableName() . " TS ON TS.TEMPLATE_ID = TT.ID

			". $this->obUserFieldsSql->GetJoin("TT.ID");

		return $this;
	}

	private function makeArSelect(): self
	{
		$defaultSelect = array();
		$alwaysSelect = array();
		foreach($this->arFields as $field => $rule)
		{
			if(
				isset($rule['DEFAULT'])
				// && $rule['DEFAULT']
			)
			{
				$defaultSelect[] = $field;
			}
			if(
				isset($rule['ALWAYS'])
				// && $rule['ALWAYS']
			)
			{
				$alwaysSelect[] = $field;
			}
		}

		if (count($this->arSelect) <= 0)
		{
			$this->arSelect = $defaultSelect;
		}
		elseif(in_array("*", $this->arSelect))
		{
			$this->arSelect = array_diff(array_merge($defaultSelect, $this->arSelect), array("*"));
		}

		$this->arSelect = array_merge($this->arSelect, $alwaysSelect);

		if (!in_array("ID", $this->arSelect))
		{
			$this->arSelect[] = "ID";
		}

		return $this;
	}

	private function makeArSqlSearch(): self
	{
		$this->applyFilter();

		$r = $this->obUserFieldsSql->GetFilter();
		if ($r <> '')
		{
			$this->arSqlSearch[] = "(".$r.")";
		}

		return $this;
	}

	private function makeUserFields(): self
	{
		$this->obUserFieldsSql = new CUserTypeSQL();
		$this->obUserFieldsSql->SetEntity("TASKS_TASK_TEMPLATE", "TT.ID");
		$this->obUserFieldsSql->SetSelect($this->arSelect);
		$this->obUserFieldsSql->SetFilter($this->arFilter);
		$this->obUserFieldsSql->SetOrder($this->arOrder);

		return $this;
	}

	private function makeArFields(): self
	{
		$this->arFields = [

			// task fields
			'ID' 						=> ['FIELD' => 'TT.ID', 'DEFAULT' => true],
			'TITLE' 					=> ['FIELD' => 'TT.TITLE', 'DEFAULT' => true],
			'DESCRIPTION' 				=> ['FIELD' => 'TT.DESCRIPTION', 'DEFAULT' => true],
			'DESCRIPTION_IN_BBCODE' 	=> ['FIELD' => 'TT.DESCRIPTION_IN_BBCODE', 'DEFAULT' => true],
			'PRIORITY' 					=> ['FIELD' => 'TT.PRIORITY', 'DEFAULT' => true],
			'STATUS' 					=> ['FIELD' => 'TT.STATUS', 'DEFAULT' => true],
			'STAGE_ID' 					=> ['FIELD' => 'TT.STAGE_ID', 'DEFAULT' => true],
			'RESPONSIBLE_ID' 			=> ['FIELD' => 'TT.RESPONSIBLE_ID', 'DEFAULT' => true],
			'DEADLINE_AFTER' 			=> ['FIELD' => 'TT.DEADLINE_AFTER', 'DEFAULT' => true],
			'START_DATE_PLAN_AFTER' 	=> ['FIELD' => 'TT.START_DATE_PLAN_AFTER', 'DEFAULT' => true],
			'END_DATE_PLAN_AFTER' 		=> ['FIELD' => 'TT.END_DATE_PLAN_AFTER', 'DEFAULT' => true],
			'REPLICATE' 				=> ['FIELD' => 'TT.REPLICATE', 'DEFAULT' => true],
			'CREATED_BY' 				=> ['FIELD' => 'TT.CREATED_BY', 'DEFAULT' => true],
			'XML_ID' 					=> ['FIELD' => 'TT.XML_ID', 'DEFAULT' => true],
			'ALLOW_CHANGE_DEADLINE' 	=> ['FIELD' => 'TT.ALLOW_CHANGE_DEADLINE', 'DEFAULT' => true],
			'ALLOW_TIME_TRACKING' 		=> ['FIELD' => 'TT.ALLOW_TIME_TRACKING', 'DEFAULT' => true],
			'TASK_CONTROL' 				=> ['FIELD' => 'TT.TASK_CONTROL', 'DEFAULT' => true],
			'ADD_IN_REPORT' 			=> ['FIELD' => 'TT.ADD_IN_REPORT', 'DEFAULT' => true],
			'GROUP_ID' 					=> ['FIELD' => 'TT.GROUP_ID', 'DEFAULT' => true],
			'PARENT_ID' 				=> ['FIELD' => 'TT.PARENT_ID', 'DEFAULT' => true],
			'MULTITASK' 				=> ['FIELD' => 'TT.MULTITASK', 'DEFAULT' => true],
			'SITE_ID' 					=> ['FIELD' => 'TT.SITE_ID', 'DEFAULT' => true],
			'ACCOMPLICES' 				=> ['FIELD' => 'TT.ACCOMPLICES', 'DEFAULT' => true],
			'AUDITORS' 					=> ['FIELD' => 'TT.AUDITORS', 'DEFAULT' => true],
			'RESPONSIBLES' 				=> ['FIELD' => 'TT.RESPONSIBLES', 'DEFAULT' => true],
			'FILES' 					=> ['FIELD' => 'TT.FILES', 'DEFAULT' => true],
			'TAGS' 						=> ['FIELD' => 'TT.TAGS', 'DEFAULT' => true],
			'DEPENDS_ON' 				=> ['FIELD' => 'TT.DEPENDS_ON', 'DEFAULT' => true],
			'MATCH_WORK_TIME' 			=> ['FIELD' => 'TT.MATCH_WORK_TIME', 'DEFAULT' => true],

			// template parameters
			'TASK_ID' 					=> ['FIELD' => 'TT.TASK_ID', 'DEFAULT' => true],
			'TPARAM_TYPE' 				=> ['FIELD' => 'TT.TPARAM_TYPE', 'DEFAULT' => true],
			'TPARAM_REPLICATION_COUNT' 	=> ['FIELD' => 'TT.TPARAM_REPLICATION_COUNT', 'DEFAULT' => true],
			'REPLICATE_PARAMS' 			=> ['FIELD' => 'TT.REPLICATE_PARAMS', 'DEFAULT' => true],

			// virtual
			'BASE_TEMPLATE_ID' 			=> ['FIELD' => 'CASE WHEN TDD.' . DependencyTable::getPARENTIDColumnName() . ' IS NULL THEN 0 ELSE TDD.' . DependencyTable::getPARENTIDColumnName() . ' END', 'DEFAULT' => false],
			'TEMPLATE_CHILDREN_COUNT' 	=> ['FIELD' => 'CASE WHEN TEMPLATE_CHILDREN_COUNT IS NULL THEN 0 ELSE TEMPLATE_CHILDREN_COUNT END', 'DEFAULT' => false],

			// additional
			'CREATED_BY_NAME' 			=> ['FIELD' => 'CU.NAME', 'DEFAULT' => true, 'ALWAYS' => true],
			'CREATED_BY_LAST_NAME' 		=> ['FIELD' => 'CU.LAST_NAME ', 'DEFAULT' => true, 'ALWAYS' => true],
			'CREATED_BY_SECOND_NAME' 	=> ['FIELD' => 'CU.SECOND_NAME', 'DEFAULT' => true, 'ALWAYS' => true],
			'CREATED_BY_LOGIN' 			=> ['FIELD' => 'CU.LOGIN', 'DEFAULT' => true, 'ALWAYS' => true],
			'CREATED_BY_WORK_POSITION' 	=> ['FIELD' => 'CU.WORK_POSITION', 'DEFAULT' => true, 'ALWAYS' => true],
			'CREATED_BY_PHOTO' 			=> ['FIELD' => 'CU.PERSONAL_PHOTO', 'DEFAULT' => true, 'ALWAYS' => true],
			'RESPONSIBLE_NAME' 			=> ['FIELD' => 'RU.NAME', 'DEFAULT' => true, 'ALWAYS' => true],
			'RESPONSIBLE_LAST_NAME' 	=> ['FIELD' => 'RU.LAST_NAME', 'DEFAULT' => true, 'ALWAYS' => true],
			'RESPONSIBLE_SECOND_NAME' 	=> ['FIELD' => 'RU.SECOND_NAME', 'DEFAULT' => true, 'ALWAYS' => true],
			'RESPONSIBLE_LOGIN' 		=> ['FIELD' => 'RU.LOGIN', 'DEFAULT' => true, 'ALWAYS' => true],
			'RESPONSIBLE_WORK_POSITION' => ['FIELD' => 'RU.WORK_POSITION', 'DEFAULT' => true, 'ALWAYS' => true],
			'RESPONSIBLE_PHOTO' 		=> ['FIELD' => 'RU.PERSONAL_PHOTO', 'DEFAULT' => true, 'ALWAYS' => true],
			'SCENARIO' 					=> ['FIELD' => 'TS.SCENARIO', 'DEFAULT' => true, 'ALWAYS' => true],
		];

		return $this;
	}

	private function applyFilter()
	{
		foreach ($this->arFilter as $key => $val)
		{
			$res = CTasks::MkOperationFilter($key);
			$key = $res["FIELD"];
			$cOperationType = $res["OPERATION"];

			$key = strtoupper($key);

			switch ($key)
			{
				case "CREATED_BY":
				case "TASK_ID":
				case "GROUP_ID":
				case "TPARAM_TYPE":
				case "ID":
					$this->arSqlSearch[] = CTasks::FilterCreate("TT.".$key, $val, "number", $bFullJoin, $cOperationType);
					break;

				case "RESPONSIBLE":
					$this->arSqlSearch[] = CTasks::FilterCreate("TT.RESPONSIBLE_ID", $val, "number", $bFullJoin, $cOperationType);
					break;

				case 'TAGS':
					if (!is_array($val))
					{
						$val = [$val];
					}
					global $DB;
					$tags = array_filter(
						array_map(
							function ($tag) use ($DB) {
								return ($tag ? $DB->ForSql($tag) : false);
							},
							$val
						)
					);
					$tagsCount = count($tags);
					if ($tagsCount)
					{
						$tags = "('" . implode("','", $tags) . "')";
						$this->arSqlSearch[] = trim("
							TT.ID IN (
								SELECT TTT.TEMPLATE_ID
								FROM (
									SELECT TEMPLATE_ID, COUNT(TEMPLATE_ID) AS CNT
									FROM b_tasks_template_tag
									WHERE NAME IN {$tags}
									GROUP BY TEMPLATE_ID
									HAVING CNT = {$tagsCount}
								) TTT
							)
						");
					}
					break;

				case "SCENARIO":
					$scenarios = !is_array($val) ? [$val] : $val;
					$filteredValue = [];
					foreach ($scenarios as $scenario)
					{
						if (ScenarioTable::isValidScenario($scenario))
						{
							$filteredValue[] = $scenario;
						}
					}
					$this->arSqlSearch[] = CTasks::FilterCreate("TS." . $key, $filteredValue, "string_equal", $bFullJoin);
					break;

				case "TITLE":
				case "ZOMBIE":
				case "XML_ID":
					$this->arSqlSearch[] = CTasks::FilterCreate("TT.".$key, $val, "string", $bFullJoin, $cOperationType);
					break;

				case "REPLICATE":
				case "PRIORITY":
					$this->arSqlSearch[] = CTasks::FilterCreate("TT.".$key, $val, "string_equal", $bFullJoin, $cOperationType);
					break;

				case 'SEARCH_INDEX':
					$fieldsToLike = array(
						"TT.TITLE",
						"TT.DESCRIPTION",
						"CONCAT_WS(' ', CU.NAME, CU.LAST_NAME, CU.SECOND_NAME, CU.LOGIN, RU.NAME, RU.LAST_NAME, RU.SECOND_NAME, RU.LOGIN)"
					);

					$filter = '(';
					$filter .= CTasks::FilterCreate("TT.ID", (int)$val, "number", $bFullJoin, $cOperationType);

					foreach ($fieldsToLike as $field)
					{
						$filter .= " OR ".CTasks::FilterCreate($field, $val, "string", $bFullJoin, "S");
					}
					$filter .= ')';

					$this->arSqlSearch[] = $filter;

					break;

				case "BASE_TEMPLATE_ID":

					$parentColumnName = DependencyTable::getPARENTIDColumnName();
					$columnName = DependencyTable::getIDColumnName();

					$val = (string) $val;
					if($val === '' || $val === '0')
					{
						$val = false;
					}

					$excludeSubtree = (
						isset($this->arParams['EXCLUDE_TEMPLATE_SUBTREE'])
						&& (
							$this->arParams['EXCLUDE_TEMPLATE_SUBTREE'] === true
							|| $this->arParams['EXCLUDE_TEMPLATE_SUBTREE'] === 'Y'
						)
					);

					if($excludeSubtree)
					{
						$this->arSqlSearch[] = "TT.ID NOT IN (SELECT " . $columnName . " FROM ". DependencyTable::getTableName() ." WHERE " . $parentColumnName . " = '" . intval($val) . "')";
					}
					else
					{
						$this->arSqlSearch[] = '('.($val ? "TD." . $parentColumnName . " = '".intval($val)."'" : "TD." . $parentColumnName . " = 0 OR TD." . $parentColumnName . " IS NULL").')';
					}

					break;
			}
		}
	}

	private function configure(array $arOrder = [], array $arFilter = [], array $arSelect = [], array $arParams = [], array $arNavParams = [])
	{
		$this->arOrder 		= $arOrder;
		$this->arFilter 	= $arFilter;
		$this->arSelect 	= $arSelect;
		$this->arParams 	= $arParams;
		$this->arNavParams 	= $arNavParams;

		if (!array_key_exists('ZOMBIE', $arFilter) || $arFilter['ZOMBIE'] != 'Y')
		{
			$this->arFilter['ZOMBIE'] = 'N';
		}

		if (isset($arParams['USER_ID']))
		{
			$this->userId = (int) $arParams['USER_ID'];
		}

		if (!isset($arFilter['SCENARIO']) || !ScenarioTable::isValidScenario($arFilter['SCENARIO']))
		{
			$this->arFilter['SCENARIO'] = ScenarioTable::SCENARIO_DEFAULT;
		}

		$this->executorId = $this->userId;
	}
}