<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */


if (!CModule::IncludeModule('report'))
	return;

use Bitrix\Main;
use Bitrix\Tasks\Integration\Intranet\Department;
use Bitrix\Tasks\Internals\Task\LabelTable;

class CTasksReportHelper extends CReportHelper
{
	static $PATH_TO_USER = '/company/personal/user/#user_id#/';

	protected static $nRows = 0;
	protected static $userFieldMoneyList = null;

	protected static function prepareUFInfo()
	{
		if (is_array(self::$arUFId))
			return;

		self::$arUFId = array('TASKS_TASK');

		/** @global CUserTypeManager $USER_FIELD_MANAGER */
		global $USER_FIELD_MANAGER;

//		$allowedUserTypes = array('disk_file', 'crm');
		$allowedUserTypes = array('disk_file', 'crm', 'string', 'date', 'datetime', 'enumeration', 'double', 'integer',
			'boolean', 'file', 'employee', 'crm_status', 'iblock_element', 'iblock_section', 'money');

		self::$ufInfo = [];
		self::$ufEnumerations = [];
		self::$userFieldMoneyList = [];

		foreach(self::$arUFId as $ufId)
		{
			$arUserFields = $USER_FIELD_MANAGER->GetUserFields($ufId, 0, LANGUAGE_ID);

			if (is_array($arUserFields) && count($arUserFields) > 0)
			{
				foreach ($arUserFields as $field)
				{
					if (isset($field['FIELD_NAME']) && \Bitrix\Tasks\Util\UserField::isUFKey($field['FIELD_NAME'])
						/*&& (!isset($field['MULTIPLE']) || $field['MULTIPLE'] !== 'Y')*/
						&& isset($field['USER_TYPE_ID']) && in_array($field['USER_TYPE_ID'], $allowedUserTypes, true))
					{
						if ($field['FIELD_NAME'] === 'UF_TASK_WEBDAV_FILES')
						{
							$field['EDIT_FORM_LABEL'] = $field['LIST_COLUMN_LABEL'] = $field['LIST_FILTER_LABEL'] =
								GetMessage('TASKS_REPORT_UF_TASK_WEBDAV_FILES');
						}
						if (mb_substr(trim($field['FIELD_NAME']), 0, 8) == 'UF_AUTO_')
						{
							$field['LIST_COLUMN_LABEL'] = $field['LIST_FILTER_LABEL'] = $field['EDIT_FORM_LABEL'];
						}

						self::$ufInfo[$ufId][$field['FIELD_NAME']] = $field;

						if ($field['USER_TYPE_ID'] === 'datetime' && $field['MULTIPLE'] !== 'Y')
							self::$ufInfo[$ufId][$field['FIELD_NAME'].self::UF_DATETIME_SHORT_POSTFIX] = $field;

						$blPostfix = defined('self::UF_BOOLEAN_POSTFIX') ? self::UF_BOOLEAN_POSTFIX : '_BLINL';
						if ($field['USER_TYPE_ID'] === 'boolean' && $field['MULTIPLE'] !== 'Y')
							self::$ufInfo[$ufId][$field['FIELD_NAME'].$blPostfix] = $field;

						if ($field['USER_TYPE_ID'] === 'money')
						{
							self::$userFieldMoneyList[] = $field['FIELD_NAME'];
						}
					}
				}
			}
		}
	}

	protected static function prepareUFEnumerations($usedUFMap = null)
	{
		$ufInfo = static::getUFInfo();

		if ($usedUFMap !== null && !is_array($usedUFMap))
		{
			$usedUFMap = array();
		}

		if (is_array($ufInfo))
		{
			foreach ($ufInfo as $entityId => $fieldList)
			{
				foreach ($fieldList as $field)
				{
					if (is_array($field) && isset($field['USER_TYPE_ID']) && $field['USER_TYPE_ID'] === 'enumeration'
						&& isset($field['ENTITY_ID']) && strval($field['ENTITY_ID']) <> ''
						&& !isset(self::$ufEnumerations[$field['ENTITY_ID']][$field['FIELD_NAME']])
						&& ($usedUFMap === null || isset($usedUFMap[$field['ENTITY_ID']][$field['FIELD_NAME']]))
						&& is_array($field['USER_TYPE']) && isset($field['USER_TYPE']['CLASS_NAME'])
						&& !empty($field['USER_TYPE']['CLASS_NAME'])
						&& is_callable(array($field['USER_TYPE']['CLASS_NAME'], 'GetList')))
					{
						self::$ufEnumerations[$field['ENTITY_ID']][$field['FIELD_NAME']] = array();
						$rsEnum = call_user_func_array(array($field['USER_TYPE']['CLASS_NAME'], 'GetList'), array($field));
						while($ar = $rsEnum->Fetch())
						{
							self::$ufEnumerations[$field['ENTITY_ID']][$field['FIELD_NAME']][$ar['ID']] = $ar;
						}
					}
				}
			}
		}
	}


	public static function setPathToUser($path)
	{
		self::$PATH_TO_USER = $path;
	}


	public static function getEntityName()
	{
		return 'Bitrix\Tasks\Task';
	}


	public static function getOwnerId()
	{
		return 'TASKS';
	}


	public static function getColumnList()
	{
		IncludeModuleLangFile(__FILE__);

		$columnList = array(
			'ID',
			'TITLE',
			'DESCRIPTION_TR',
			'PRIORITY',
			'STATUS',
			'STATUS_PSEUDO',
			'STATUS_SUB' => array(
				'IS_NEW',
				'IS_OPEN',
				'IS_RUNNING',
				'IS_FINISHED',
				'IS_OVERDUE',
				'IS_MARKED',
				'IS_EFFECTIVE',
				'IS_EFFECTIVE_PRCNT'
			),
			'ADD_IN_REPORT',
			'CREATED_DATE',
			'START_DATE_PLAN',
			'END_DATE_PLAN',
			'DURATION_PLAN_HOURS',
			'DATE_START',
			'CHANGED_DATE',
			'CLOSED_DATE',
			'DEADLINE',
			'TIME_SPENT_IN_LOGS',
			'TIME_SPENT_IN_LOGS_FOR_PERIOD',
			'ALLOW_TIME_TRACKING',
			'TIME_ESTIMATE',
			'MARK',
			'TAGS',
			'GROUP' => array(
				'ID',
				'NAME'
			),
			'CREATED_BY_USER' => array(
				'ID',
				'SHORT_NAME',
				'NAME',
				'LAST_NAME',
				'WORK_POSITION'
			),
			'RESPONSIBLE' => array(
				'ID',
				'SHORT_NAME',
				'NAME',
				'LAST_NAME',
				'WORK_POSITION'
			),
			'Member:TASK_COWORKED.USER' => array(
				'ID',
				'SHORT_NAME',
				'NAME',
				'LAST_NAME',
				'WORK_POSITION'
			),
			'CHANGED_BY_USER' => array(
				'ID',
				'SHORT_NAME',
				'NAME',
				'LAST_NAME',
				'WORK_POSITION'
			),
			'STATUS_CHANGED_BY_USER' => array(
				'ID',
				'SHORT_NAME',
				'NAME',
				'LAST_NAME',
				'WORK_POSITION'
			),
			'CLOSED_BY_USER' => array(
				'ID',
				'SHORT_NAME',
				'NAME',
				'LAST_NAME',
				'WORK_POSITION'
			),

			'DURATION', // deprecated, use TIME_SPENT_IN_LOGS
			'DURATION_FOR_PERIOD', // deprecated, use TIME_SPENT_IN_LOGS_FOR_PERIOD
		);

		// Append user fields
		$blPostfix = defined('self::UF_BOOLEAN_POSTFIX') ? self::UF_BOOLEAN_POSTFIX : '_BLINL';
		self::prepareUFInfo();
		if (is_array(self::$ufInfo) && count(self::$ufInfo) > 0)
		{
			if (isset(self::$ufInfo['TASKS_TASK']) && is_array(self::$ufInfo['TASKS_TASK'])
				&& count(self::$ufInfo['TASKS_TASK']) > 0)
			{
				foreach (self::$ufInfo['TASKS_TASK'] as $ufKey => $uf)
				{
					if (($uf['USER_TYPE_ID'] !== 'datetime' && $uf['USER_TYPE_ID'] !== 'boolean')
						|| $uf['MULTIPLE'] === 'Y'
						|| mb_substr($ufKey, -mb_strlen(self::UF_DATETIME_SHORT_POSTFIX)) === self::UF_DATETIME_SHORT_POSTFIX
						|| mb_substr($ufKey, -mb_strlen($blPostfix)) === $blPostfix)
					{
						$columnList[] = $ufKey;
					}
				}
			}
		}

		return $columnList;
	}

	public static function setRuntimeFields(\Bitrix\Main\Entity\Base $entity, $sqlTimeInterval)
	{
		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$entity->addField(array( // in minutes (deprecated, use TIME_SPENT_IN_LOGS_FOR_PERIOD, which is in seconds, that works correctly in expressions)
			'data_type' => 'integer',
			'expression' => array(
				'ROUND((SELECT  SUM(CASE WHEN '.$sqlHelper->getDatetimeToDateFunction('CREATED_DATE').' '.
					$sqlTimeInterval.' THEN SECONDS ELSE 0 END)/60 FROM b_tasks_elapsed_time WHERE TASK_ID = %s),0)',
				'ID'
			)
		), 'DURATION_FOR_PERIOD');

		$entity->addField(array( // in seconds
			'data_type' => 'integer',
			'expression' => array(
				'(SELECT  SUM(CASE WHEN '.$sqlHelper->getDatetimeToDateFunction('CREATED_DATE').' '.$sqlTimeInterval.
					' THEN SECONDS ELSE 0 END) '.
				'FROM b_tasks_elapsed_time WHERE TASK_ID = %s)',
				'ID'
			)
		), 'TIME_SPENT_IN_LOGS_FOR_PERIOD');

		$entity->addField(array(
			'data_type' => 'boolean',
			'expression' => array(
				'CASE WHEN %s '.$sqlTimeInterval.' THEN 1 ELSE 0 END',
				'CREATED_DATE'
			),
			'values' => array(0, 1)
		), 'IS_NEW');

		$entity->addField(array(
			'data_type' => 'boolean',
			'expression' => array(
				'CASE WHEN %s '.$sqlTimeInterval.' THEN 0 ELSE 1 END',
				'DATE_START'
			),
			'values' => array(0, 1)
		), 'IS_OPEN');

		$entity->addField(array(
			'data_type' => 'boolean',
			'expression' => array(
				'CASE WHEN %s '.$sqlTimeInterval.' AND %s IS NOT NULL THEN 1 ELSE 0 END',
				'CLOSED_DATE', 'CLOSED_DATE'
			),
			'values' => array(0, 1)
		), 'IS_FINISHED');

		$entity->addField(array(
			'data_type' => 'string',
			'expression' => array(
				'GROUP_CONCAT(DISTINCT (%s) ORDER BY (%s) SEPARATOR \' / \')',
				LabelTable::getTaskRelationName() . ':TASK.TAG.NAME',
				LabelTable::getTaskRelationName() . ':TASK.TAG.NAME',
			),
		), 'TAGS');

		self::appendBooleanUserFieldsIfNull($entity);
		self::appendDateTimeUserFieldsAsShort($entity);
		self::appendMoneyUserFieldsAsSeparated($entity);
		self::appendTextUserFieldsAsTrimmed($entity);
	}

	public static function getCustomSelectFields($select, $fList)
	{
		$customFields = array();

		return $customFields;
	}

	public static function getCustomColumnTypes()
	{
		return array(
			'DURATION_PLAN_HOURS' => 'float',
			'DURATION' => 'float',
			'DURATION_FOR_PERIOD' => 'float',
			'TIME_ESTIMATE' => 'float'
		);
	}


	public static function getDefaultColumns()
	{
		return array(
			array('name' => 'TITLE'),
			array('name' => 'PRIORITY'),
			array('name' => 'RESPONSIBLE.SHORT_NAME'),
			array('name' => 'STATUS_PSEUDO')
		);
	}


	public static function getCalcVariations()
	{
		return array_merge(parent::getCalcVariations(), array(
			'IS_OVERDUE_PRCNT' => array(),
			'IS_MARKED_PRCNT' => array(),
			'IS_EFFECTIVE_PRCNT' => array(),
			'TAGS' => array(),
			'Member:TASK_COWORKED.USER.SHORT_NAME' => array(
				'COUNT_DISTINCT',
				'GROUP_CONCAT'
			)
		));
	}


	public static function getCompareVariations()
	{
		return array_merge(parent::getCompareVariations(), array(
			'STATUS' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'STATUS_PSEUDO' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'PRIORITY' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'MARK' => array(
				'EQUAL',
				'NOT_EQUAL'
			)
		));
	}


	public static function buildSelectTreePopupElelemnt($humanTitle, $fullHumanTitle, $fieldDefinition, $fieldType, $ufInfo = array())
	{
		$isUF = false;
		$isMultiple = false;
		if (is_array($ufInfo) && isset($ufInfo['ENTITY_ID']) && isset($ufInfo['FIELD_NAME']))
		{
			if (isset($ufInfo['MULTIPLE']) && $ufInfo['MULTIPLE'] === 'Y')
				$isMultiple = true;
			$isUF = true;
		}

		if ($isUF && $isMultiple
			&& mb_substr($fieldDefinition, -mb_strlen(self::UF_TEXT_TRIM_POSTFIX)) === self::UF_TEXT_TRIM_POSTFIX)
		{
			return '';
		}

		return parent::buildSelectTreePopupElelemnt(
			$humanTitle, $fullHumanTitle, $fieldDefinition, $fieldType, $ufInfo
		);
	}


	public static function beforeViewDataQuery(&$select, &$filter, &$group, &$order, &$limit, &$options, &$runtime = null)
	{
		parent::beforeViewDataQuery($select, $filter, $group, $order, $limit, $options);
		// unset($select['TAGS']);
		self::rewriteTagsFilter($filter, $runtime);
		self::rewriteMoneyFilter($filter, $runtime);

		global $DB;

		$permFilter = array(
			'LOGIC' => 'OR'
		);

		$userId = \Bitrix\Tasks\Util\User::getId();

		// owner permission
		if (isset($_GET['select_my_tasks']) ||
			(!isset($_GET['select_my_tasks']) && !isset($_GET['select_depts_tasks']) && !isset($_GET['select_group_tasks']))
		)
		{
			$runtime['IS_TASK_COWORKER'] = array(
				'data_type' => 'integer',
				'expression' => array("(CASE WHEN EXISTS("
					."SELECT 'x' FROM b_tasks_member TM "
					."WHERE TM.TASK_ID = ".$DB->escL.("tasks_task").$DB->escR.".ID AND TM.USER_ID = ".$userId." AND TM.TYPE = 'A'"
				.") THEN 1 ELSE 0 END)")
			);

			$permFilter[] = array(
				'LOGIC' => 'OR',
				'=RESPONSIBLE_ID' => $userId,
				'=IS_TASK_COWORKER' => 1
			);
		}

		// own departments permission
		if (isset($_GET['select_depts_tasks']))
		{
			$permFilterDepts = array(
				'LOGIC' => 'OR',
				'=CREATED_BY' => $userId
			);

			$deptsPermSql = self::getSubordinateSql();

			if(!empty($deptsPermSql))
			{
				$runtime['IS_SUBORDINATED_TASK'] = [
					'data_type' => 'integer',
					'expression' => [$deptsPermSql],
				];

				$permFilterDepts[] = [
					'!RESPONSIBLE_ID' => $userId,
					'=IS_SUBORDINATED_TASK' => 1,
				];
			}

			$permFilter[] = $permFilterDepts;
		}

		// group permission
		if (isset($_GET['select_group_tasks']))
		{
			$allowedGroups = \Bitrix\Tasks\Integration\SocialNetwork\Group::getIdsByAllowedAction('view_all', true, \Bitrix\Tasks\Util\User::getId());
			$permFilter[] = array('=GROUP_ID' => $allowedGroups);
		}

		// concat permissions with common filter
		$filter[] = $permFilter;
	}

	protected static function rewriteTagsFilter(&$filter, &$runtime)
	{
		global $DB;

		static $operationCodes = null;
		static $fieldName = 'TAGS';
		static $rtFilterCount = 0;
		static $sqlHelper = null;

		if ($operationCodes === null)
			$operationCodes = array_flip(CReport::$iBlockCompareVariations);

		if ($sqlHelper === null)
			$sqlHelper = Main\Application::getConnection()->getSqlHelper();

		$newFilters = array();

		foreach ($filter as $k => &$v)
		{
			if ($k !== 'LOGIC' && !is_numeric($k))
			{
				$matches = array();
				if (preg_match('/(=|!|>%|%|!%)?'.$fieldName.'/', $k, $matches))
				{
					if (!isset($matches[1]))
					{
						$matches[1] = '>%';
						if (mb_substr($v, -1) === '%')
							$v = mb_substr($v, 0, mb_strlen($v) - 1);
					}
					$operationCode = $operationCodes[$matches[1]];
					$caseResult = array();
					$compareSql = '';
					$where = new CSQLWhere();

					switch ($operationCode)
					{
						case 'EQUAL':
							$compareSql = 'TG.NAME = \''.$sqlHelper->forSql($v).'\'';
							$caseResult = array(1, 0);
							break;
						case 'NOT_EQUAL':
							$compareSql = 'TG.NAME = \''.$sqlHelper->forSql($v).'\'';
							$caseResult = array(0, 1);
							break;
						case 'START_WITH':
							$compareSql = $where->_Upper('TG.NAME')." LIKE '".$where->ForLIKE(ToUpper($v))."%' ESCAPE '!'";
							$caseResult = array(1, 0);
							break;
						case 'CONTAINS':
							$compareSql = $where->_Upper('TG.NAME')." LIKE '%".$where->ForLIKE(ToUpper($v))."%' ESCAPE '!'";
							$caseResult = array(1, 0);
							break;
						case 'NOT_CONTAINS':
							$compareSql = $where->_Upper('TG.NAME')." LIKE '%".$where->ForLIKE(ToUpper($v))."%' ESCAPE '!'";
							$caseResult = array(0, 1);
							break;
					}
					$compareSql = str_replace('%', '%%', $compareSql);
					$rtFilterDef = array(
						'data_type' => 'integer',
						'expression' => array(
							"(CASE WHEN EXISTS("
								."SELECT 'x' FROM b_tasks_label TG "
								."INNER JOIN b_tasks_task_tag btt on TG.ID=btt.TAG_ID "
								."WHERE btt.TASK_ID = {$DB->escL}tasks_task{$DB->escR}.ID AND $compareSql"
							.") THEN $caseResult[0] ELSE $caseResult[1] END)"
						)
					);
					$runtime['IS_RT_'.$fieldName.'_'.$operationCode.'_'.$rtFilterCount] = $rtFilterDef;
					$newFilters['=IS_RT_'.$fieldName.'_'.$operationCode.'_'.$rtFilterCount++] = 1;
					unset($filter[$k]);
				}
			}

			if (is_array($v))
			{
				self::rewriteTagsFilter($v, $runtime);
			}
		}
		unset($v);

		if (!empty($newFilters))
		{
			foreach ($newFilters as $k => $v)
				$filter[$k] = $v;
		}
	}

	protected static function rewriteMoneyFilter(&$filter, &$runtime)
	{
		static $operationCodes = null;
		static $moneyFieldRegExp = null;
		static $sqlHelper = null;
		static $allowedOperations = null;

		if ($operationCodes === null)
		{
			$operationCodes = array_flip(CReport::$iBlockCompareVariations);
		}

		if ($sqlHelper === null)
		{
			$sqlHelper = Main\Application::getConnection()->getSqlHelper();
		}

		if ($allowedOperations === null)
		{
			$allowedOperations = ['EQUAL', 'GREATER', 'LESS', 'NOT_EQUAL', 'GREATER_OR_EQUAL', 'LESS_OR_EQUAL'];
		}

		if ($moneyFieldRegExp === null)
		{
			$moneyFieldRegExp = '';

			if (is_array(self::$userFieldMoneyList) && !empty(self::$userFieldMoneyList))
			{
				$moneyFieldRegExp .= '(';
				$number = 0;
				foreach (self::$userFieldMoneyList as $fieldName)
				{
					if ($number++ > 0)
					{
						$moneyFieldRegExp .= '|';
					}
					$moneyFieldRegExp .= preg_quote($fieldName);
				}
				unset($number);
				$moneyFieldRegExp .= ')';
			}
		}

		if ($moneyFieldRegExp !== '')
		{
			$newFilter = [];
			foreach ($filter as $k => &$v)
			{
				$skipFilterElement = false;
				if (is_array($v))
				{
					self::rewriteMoneyFilter($v, $runtime);
				}
				else if ($k !== 'LOGIC' && !is_numeric($k))
				{
					$matches = array();
					if (preg_match('/^(>=|<=|=|>|<|!)?(.*'.$moneyFieldRegExp.')$/', $k, $matches))
					{
						if (is_string($v) && $v !== '')
						{
							if (!isset($matches[1]))
							{
								$matches[1] = '=';
							}
							$operationCode = $operationCodes[$matches[1]];
							$valueParts = explode('|', $v);
							if (is_array($valueParts) && isset($valueParts[0])
								&& is_string($valueParts[0]) && $valueParts[0] !== '')
							{
								$numberFieldName = $matches[2].self::UF_MONEY_NUMBER_POSTFIX;
								$currencyFieldName = $matches[2].self::UF_MONEY_CURRENCY_POSTFIX;
								$numberValue = (double)$valueParts[0];
								$currencyValue = '';
								if (isset($valueParts[1]) && is_string($valueParts[1]) && $valueParts[1] !== '')
								{
									$currencyValue = $valueParts[1];
								}
								if (in_array($operationCode, $allowedOperations, true))
								{
									$filterOperation = CReport::$iBlockCompareVariations[$operationCode];
									if ($currencyValue === '')
									{
										$newFilter[$filterOperation.$numberFieldName] = $numberValue;
									}
									else
									{
										if ($filterOperation === '!')
										{
											$newFilter[] = [
												'LOGIC' => 'OR',
												$filterOperation.$numberFieldName => $numberValue,
												'!'.$currencyFieldName => $currencyValue
											];
										}
										else
										{
											$newFilter[] = [
												'LOGIC' => 'AND',
												$filterOperation.$numberFieldName => $numberValue,
												'='.$currencyFieldName => $currencyValue
											];
										}
									}
								}
							}
						}
						$skipFilterElement = true;
					}
				}
				if (!$skipFilterElement)
				{
					if (is_numeric($k))
					{
						$newFilter[] = $v;
					}
					else
					{
						$newFilter[$k] = $v;
					}
				}
			}
			unset($v);

			$filter = $newFilter;
		}
	}

	/* remove it when PHP 5.3 available */
	public static function formatResults(&$rows, &$columnInfo, $total, &$customChartData = null)
	{
		foreach ($rows as $rowNum => &$row)
		{
			foreach ($row as $k => &$v)
			{
				if (!array_key_exists($k, $columnInfo))
				{
					continue;
				}

				$cInfo = $columnInfo[$k];

				if (is_array($v))
				{
					foreach ($v as $subk => &$subv)
					{
						$customChartValue = is_null($customChartData) ? null : array();
						self::formatResultValue($k, $subv, $row, $cInfo, $total, $customChartValue);
						if (is_array($customChartValue)
							&& isset($customChartValue['exist']) && $customChartValue['exist'] = true)
						{
							if (!isset($customChartData[$rowNum]))
								$customChartData[$rowNum] = array();
							if (!isset($customChartData[$rowNum][$k]))
								$customChartData[$rowNum][$k] = array();
							$customChartData[$rowNum][$k]['multiple'] = true;
							if (!isset($customChartData[$rowNum][$k][$subk]))
								$customChartData[$rowNum][$k][$subk] = array();
							$customChartData[$rowNum][$k][$subk]['type'] = $customChartValue['type'];
							$customChartData[$rowNum][$k][$subk]['value'] = $customChartValue['value'];
						}
					}
				}
				else
				{
					$customChartValue = is_null($customChartData) ? null : array();
					self::formatResultValue($k, $v, $row, $cInfo, $total, $customChartValue);
					if (is_array($customChartValue)
						&& isset($customChartValue['exist']) && $customChartValue['exist'] = true)
					{
						if (!isset($customChartData[$rowNum]))
							$customChartData[$rowNum] = array();
						if (!isset($customChartData[$rowNum][$k]))
							$customChartData[$rowNum][$k] = array();
						$customChartData[$rowNum][$k]['multiple'] = false;
						if (!isset($customChartData[$rowNum][$k][0]))
							$customChartData[$rowNum][$k][0] = array();
						$customChartData[$rowNum][$k][0]['type'] = $customChartValue['type'];
						$customChartData[$rowNum][$k][0]['value'] = $customChartValue['value'];
					}
				}
			}
			self::$nRows++;
		}

		unset($row, $v, $subv);
	}
	/* \remove it */


	public static function formatResultValue($k, &$v, &$row, &$cInfo, $total, &$customChartValue = null)
	{
		$field = $cInfo['field'];
		$bChartValue = false;
		$chartValueType = null;
		$chartValue = null;

		if ($k == 'STATUS' || $k == 'STATUS_PSEUDO' || $k == 'PRIORITY')
		{
			if (empty($cInfo['aggr']) || $cInfo['aggr'] !== 'COUNT_DISTINCT')
			{
				$v = htmlspecialcharsbx(GetMessage($field->getLangCode().'_VALUE_'.$v));
			}
		}
		elseif (mb_strpos($k, 'DURATION_PLAN_HOURS') !== false && !mb_strlen($cInfo['prcnt']))
		{
			$bChartValue = true;
			$chartValueType = 'float';
			$chartValue = 0.0;
			if (!empty($v))
			{
				$days = floor($v/24);
				$hours = $v - $days*24;
				$v = '';

				if (!empty($days))
				{
					$chartValue += floatval($days * 24);
					$v .= $days.GetMessage('TASKS_REPORT_DURATION_DAYS');
				}

				if (!empty($hours))
				{
					$chartValue += floatval($hours);
					if (!empty($days)) $v .= ' ';
					$v .= $hours.GetMessage('TASKS_REPORT_DURATION_HOURS');
				}

				$chartValue = round($chartValue, 2);
			}
		}
		elseif (mb_strpos($k, 'DURATION') !== false && !mb_strlen($cInfo['prcnt']))
		{
			$hours = floor($v/60);
			$minutes = date('i', ($v % 60)*60);
			$v = $hours.':'.$minutes;

			$bChartValue = true;
			$chartValueType = 'float';
			$chartValue = round(floatval($hours) + (floatval($minutes)/60), 2);
		}
		elseif (
			(
				mb_strpos($k, 'TIME_ESTIMATE') !== false
				||
				mb_strpos($k, 'TIME_SPENT_IN_LOGS') !== false
				||
				mb_strpos($k, 'TIME_SPENT_IN_LOGS_FOR_PERIOD') !== false
			)
			&& !mb_strlen($cInfo['prcnt']))
		{
			$hours = floor($v/3600);
			$minutes = date('i', $v % 3600);
			$v = $hours.':'.$minutes;

			$bChartValue = true;
			$chartValueType = 'float';
			$chartValue = round(floatval($hours) + (floatval($minutes)/60), 2);
		}
		elseif ($k == 'MARK' && empty($cInfo['aggr']))
		{
			$v = GetMessage($field->getLangCode().'_VALUE_'.$v);
			if (empty($v))
			{
				$v = GetMessage($field->getLangCode().'_VALUE_NONE');
			}
		}
		elseif ($k == 'DESCRIPTION_TR')
		{
			$v = \Bitrix\Tasks\UI::convertBBCodeToHtml($v);
			$v = htmlspecialcharsbx(str_replace("\x0D", ' ', str_replace("\x0A", ' ', PrepareTxtForEmail(strip_tags($v)))));
		}
		else
		{
			parent::formatResultValue($k, $v, $row, $cInfo, $total);
		}

		if ($bChartValue && is_array($customChartValue))
		{
			$customChartValue['exist'] = true;
			$customChartValue['type'] = $chartValueType;
			$customChartValue['value'] = $chartValue;
		}
	}


	public static function formatResultsTotal(&$total, &$columnInfo, &$customChartTotal = null)
	{
		parent::formatResultsTotal($total, $columnInfo);

		foreach ($total as $k => $v)
		{
			// remove prefix TOTAL_
			$original_k = mb_substr($k, 6);

			$cInfo = $columnInfo[$original_k];

			if (mb_strpos($k, 'DURATION_PLAN_HOURS') !== false && !mb_strlen($cInfo['prcnt']))
			{
				if (!empty($v))
				{
					$days = floor($v/24);
					$hours = $v - $days*24;
					$v = '';
					if (!empty($days)) $v .= $days.GetMessage('TASKS_REPORT_DURATION_DAYS');
					if (!empty($hours))
					{
						if (!empty($days)) $v .= ' ';
						$v .= $hours.GetMessage('TASKS_REPORT_DURATION_HOURS');
					}
					$total[$k] = $v;
				}
			}
			elseif (mb_strpos($k, 'DURATION') !== false && !mb_strlen($cInfo['prcnt']))
			{
				$hours = floor($v/60);
				$minutes = date('i', ($v % 60)*60);
				$total[$k] = $hours.':'.$minutes;
			}
			elseif (
				(
					(mb_strpos($k, 'TIME_ESTIMATE') !== false)
					||
					(mb_strpos($k, 'TIME_SPENT_IN_LOGS') !== false)
				) && !mb_strlen($cInfo['prcnt']))
			{
				$hours = floor($v/3600);
				$minutes = date('i', $v % 3600);
				$total[$k] = $hours.':'.$minutes;
			}
			elseif (mb_strpos($k, 'IS_EFFECTIVE_PRCNT') !== false && $cInfo['prcnt'] === '')
			{
				if (self::$nRows > 0 && mb_substr($v, 0, 2) !== '--')
					$total[$k] = round(doubleval($v) / self::$nRows, 2).'%';
			}
		}
	}


	public static function getPeriodFilter($date_from, $date_to)
	{
		$filter = array('LOGIC' => 'AND');

		if (!is_null($date_from) && !is_null($date_to))
		{
			$filter[] = array(
				'LOGIC' => 'OR',
				array(
					'LOGIC' => 'AND',
					'>=CREATED_DATE' => $date_from,
					'<=CREATED_DATE' => $date_to
				),
				array(
					'LOGIC' => 'AND',
					'>=CLOSED_DATE' => $date_from,
					'<=CLOSED_DATE' => $date_to
				),
				array(
					'LOGIC' => 'AND',
					'<CREATED_DATE' => $date_from,
					array(
						'LOGIC' => 'OR',
						'>CLOSED_DATE' => $date_to,
						'=CLOSED_DATE' => ''
					)
				)
			);
		}
		else if (!is_null($date_from))
		{
			$filter[] = array(
				'LOGIC' => 'OR',
				'>=CREATED_DATE' => $date_from,
				'>=CLOSED_DATE' => $date_from,
				'=CLOSED_DATE' => ''
			);
		}
		else if (!is_null($date_to))
		{
			$filter[] = array(
				'LOGIC' => 'OR',
				'<=CREATED_DATE' => $date_to,
				'<=CLOSED_DATE' => $date_to
			);
		}

		return $filter;
	}


	public static function getDefaultElemHref($elem, $fList)
	{
		$href = null;

		if (empty($elem['aggr']) || $elem['aggr'] == 'GROUP_CONCAT')
		{
			$field = $fList[$elem['name']];
			$pathToUser = self::$PATH_TO_USER;
			$pathToUser = str_replace('#user_id#/', '', $pathToUser);

			if ($field->getEntity()->getName() == 'Task' && $elem['name'] == 'TITLE')
			{
				$href = array('pattern' => $pathToUser.'#RESPONSIBLE_ID#/tasks/task/view/#ID#/');
			}
			elseif ($field->getEntity()->getName() == 'User')
			{
				if ($elem['name'] == 'CREATED_BY_USER.SHORT_NAME')
				{
					$href = array('pattern' => $pathToUser.'#CREATED_BY#/');
				}
				elseif ($elem['name'] == 'RESPONSIBLE.SHORT_NAME')
				{
					$href = array('pattern' => $pathToUser.'#RESPONSIBLE_ID#/');
				}
				elseif ($elem['name'] == 'Member:TASK_COWORKED.USER.SHORT_NAME')
				{
					$href = array('pattern' => $pathToUser.'#Member:TASK_COWORKED.USER.ID#/');
				}
				elseif ($elem['name'] == 'CHANGED_BY_USER.SHORT_NAME')
				{
					$href = array('pattern' => $pathToUser.'#CHANGED_BY#/');
				}
				elseif ($elem['name'] == 'STATUS_CHANGED_BY_USER.SHORT_NAME')
				{
					$href = array('pattern' => $pathToUser.'#STATUS_CHANGED_BY#/');
				}
				elseif ($elem['name'] == 'CLOSED_BY_USER.SHORT_NAME')
				{
					$href = array('pattern' => $pathToUser.'#CLOSED_BY#/');
				}
			}
			elseif ($field->getEntity()->getName() == 'Group' && $elem['name'] == 'GROUP.NAME')
			{
				$href = array('pattern' => '/workgroups/group/#GROUP_ID#/');
			}
		}

		return $href;
	}


	public static function getDefaultReports()
	{
		IncludeModuleLangFile(__FILE__);

		$reports = array(
			'11.0.1' => array(
				array(
					'title' => GetMessage('TASKS_REPORT_DEFAULT_2'),
					'mark_default' => 2,
					'settings' => unserialize('a:6:{s:6:"entity";s:5:"Tasks";s:6:"period";a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:4:{i:0;a:1:{s:4:"name";s:22:"RESPONSIBLE.SHORT_NAME";}i:1;a:1:{s:4:"name";s:10:"GROUP.NAME";}i:2;a:2:{s:4:"name";s:8:"DURATION";s:4:"aggr";s:3:"SUM";}i:3;a:2:{s:4:"name";s:10:"IS_RUNNING";s:4:"aggr";s:3:"SUM";}}s:6:"filter";a:1:{i:0;a:2:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:5:"GROUP";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:0;s:5:"limit";N;}', ['allowed_classes' => false])
				),
				array(
					'title' => GetMessage('TASKS_REPORT_DEFAULT_3'),
					'mark_default' => 3,
					'settings' => unserialize('a:6:{s:6:"entity";s:5:"Tasks";s:6:"period";a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:8:{i:0;a:1:{s:4:"name";s:22:"RESPONSIBLE.SHORT_NAME";}i:1;a:1:{s:4:"name";s:5:"TITLE";}i:2;a:1:{s:4:"name";s:13:"STATUS_PSEUDO";}i:3;a:1:{s:4:"name";s:8:"PRIORITY";}i:4;a:1:{s:4:"name";s:12:"CREATED_DATE";}i:5;a:1:{s:4:"name";s:10:"DATE_START";}i:6;a:1:{s:4:"name";s:11:"CLOSED_DATE";}i:7;a:1:{s:4:"name";s:8:"DEADLINE";}}s:6:"filter";a:1:{i:0;a:3:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:11:"RESPONSIBLE";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:5:"GROUP";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:0;s:5:"limit";N;}', ['allowed_classes' => false])
				)
			),
			'11.0.3' => array(
				array(
					'title' => GetMessage('TASKS_REPORT_DEFAULT_4'),
					'mark_default' => 4,
					'settings' => unserialize('a:6:{s:6:"entity";s:5:"Tasks";s:6:"period";a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:7:{i:0;a:2:{s:4:"name";s:22:"RESPONSIBLE.SHORT_NAME";s:5:"alias";s:9:"SSSSSSSSS";}i:1;a:3:{s:4:"name";s:6:"IS_NEW";s:5:"alias";s:5:"SSSSS";s:4:"aggr";s:3:"SUM";}i:2;a:3:{s:4:"name";s:2:"ID";s:5:"alias";s:8:"SSSSSSSS";s:4:"aggr";s:14:"COUNT_DISTINCT";}i:3;a:3:{s:4:"name";s:11:"IS_FINISHED";s:5:"alias";s:9:"SSSSSSSSS";s:4:"aggr";s:3:"SUM";}i:4;a:4:{s:4:"name";s:10:"IS_OVERDUE";s:5:"alias";s:10:"SSSSSSSSSS";s:4:"aggr";s:3:"SUM";s:5:"prcnt";s:1:"2";}i:5;a:4:{s:4:"name";s:9:"IS_MARKED";s:5:"alias";s:7:"SSSSSSS";s:4:"aggr";s:3:"SUM";s:5:"prcnt";s:1:"2";}i:6;a:4:{s:4:"name";s:12:"IS_EFFECTIVE";s:5:"alias";s:13:"SSSSSSSSSSSSS";s:4:"aggr";s:3:"SUM";s:5:"prcnt";s:1:"2";}}s:6:"filter";a:1:{i:0;a:4:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:11:"RESPONSIBLE";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:5:"GROUP";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:2;a:5:{s:4:"type";s:5:"field";s:4:"name";s:13:"ADD_IN_REPORT";s:7:"compare";s:5:"EQUAL";s:5:"value";s:4:"true";s:10:"changeable";s:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:0;s:5:"limit";N;}', ['allowed_classes' => false])
				)
			),
			'11.0.8' => array(
				array(
					'title' => GetMessage('TASKS_REPORT_DEFAULT_5'),
					'mark_default' => 5,
					'settings' => unserialize('a:6:{s:6:"entity";s:5:"Tasks";s:6:"period";a:2:{s:4:"type";s:9:"month_ago";s:5:"value";N;}s:6:"select";a:6:{i:0;a:1:{s:4:"name";s:5:"TITLE";}i:2;a:1:{s:4:"name";s:22:"RESPONSIBLE.SHORT_NAME";}i:7;a:1:{s:4:"name";s:8:"PRIORITY";}i:3;a:1:{s:4:"name";s:13:"STATUS_PSEUDO";}i:5;a:1:{s:4:"name";s:8:"DURATION";}i:6;a:1:{s:4:"name";s:4:"MARK";}}s:6:"filter";a:1:{i:0;a:5:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:11:"RESPONSIBLE";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:8:"PRIORITY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:1:"1";s:10:"changeable";s:1:"1";}i:2;a:5:{s:4:"type";s:5:"field";s:4:"name";s:13:"STATUS_PSEUDO";s:7:"compare";s:5:"EQUAL";s:5:"value";s:1:"5";s:10:"changeable";s:1:"1";}i:3;a:5:{s:4:"type";s:5:"field";s:4:"name";s:5:"GROUP";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:0;s:5:"limit";N;}', ['allowed_classes' => false])
				)
			),
			'14.0.10' => array(
				array(
					'title' => GetMessage('TASKS_REPORT_DEFAULT_6'),
					'description' => GetMessage('TASKS_REPORT_DEFAULT_6_DESCR'),
					'mark_default' => 6,
					'settings' => unserialize('a:9:{s:6:"entity";s:17:"Bitrix\Tasks\Task";s:6:"period";a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:8:{i:0;a:1:{s:4:"name";s:5:"TITLE";}i:3;a:1:{s:4:"name";s:13:"STATUS_PSEUDO";}i:10;a:1:{s:4:"name";s:13:"TIME_ESTIMATE";}i:15;a:1:{s:4:"name";s:19:"DURATION_FOR_PERIOD";}i:5;a:1:{s:4:"name";s:8:"DURATION";}i:7;a:1:{s:4:"name";s:8:"DEADLINE";}i:6;a:1:{s:4:"name";s:11:"CLOSED_DATE";}i:8;a:1:{s:4:"name";s:22:"RESPONSIBLE.SHORT_NAME";}}s:6:"filter";a:2:{i:0;a:5:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:5:"GROUP";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:13:"STATUS_PSEUDO";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:2;a:5:{s:4:"type";s:5:"field";s:4:"name";s:11:"RESPONSIBLE";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:3;a:2:{s:4:"type";s:6:"filter";s:4:"name";s:1:"1";}s:5:"LOGIC";s:3:"AND";}i:1;a:3:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:19:"ALLOW_TIME_TRACKING";s:7:"compare";s:5:"EQUAL";s:5:"value";s:4:"true";s:10:"changeable";s:1:"0";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:8:"DURATION";s:7:"compare";s:7:"GREATER";s:5:"value";s:1:"0";s:10:"changeable";s:1:"0";}s:5:"LOGIC";s:2:"OR";}}s:4:"sort";i:0;s:9:"sort_type";s:3:"ASC";s:5:"limit";N;s:12:"red_neg_vals";b:0;s:13:"grouping_mode";b:0;}', ['allowed_classes' => false])
				),
				array(
					'title' => GetMessage('TASKS_REPORT_DEFAULT_7'),
					'description' => GetMessage('TASKS_REPORT_DEFAULT_7_DESCR'),
					'mark_default' => 7,
					'settings' => unserialize('a:10:{s:6:"entity";s:17:"Bitrix\Tasks\Task";s:6:"period";a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:5:{i:2;a:1:{s:4:"name";s:22:"RESPONSIBLE.SHORT_NAME";}i:4;a:3:{s:4:"name";s:2:"ID";s:5:"alias";s:0:"";s:4:"aggr";s:14:"COUNT_DISTINCT";}i:10;a:3:{s:4:"name";s:13:"TIME_ESTIMATE";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:8;a:3:{s:4:"name";s:19:"DURATION_FOR_PERIOD";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:6;a:3:{s:4:"name";s:8:"DURATION";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}}s:6:"filter";a:2:{i:0;a:4:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:5:"GROUP";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:13:"STATUS_PSEUDO";s:7:"compare";s:5:"EQUAL";s:5:"value";s:1:"5";s:10:"changeable";s:1:"1";}i:2;a:2:{s:4:"type";s:6:"filter";s:4:"name";s:1:"1";}s:5:"LOGIC";s:3:"AND";}i:1;a:3:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:19:"ALLOW_TIME_TRACKING";s:7:"compare";s:5:"EQUAL";s:5:"value";s:4:"true";s:10:"changeable";s:1:"0";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:8:"DURATION";s:7:"compare";s:7:"GREATER";s:5:"value";s:1:"0";s:10:"changeable";s:1:"0";}s:5:"LOGIC";s:2:"OR";}}s:4:"sort";i:8;s:9:"sort_type";s:4:"DESC";s:5:"limit";N;s:12:"red_neg_vals";b:0;s:13:"grouping_mode";b:0;s:5:"chart";a:4:{s:7:"display";b:1;s:4:"type";s:3:"bar";s:8:"x_column";i:2;s:9:"y_columns";a:2:{i:0;i:10;i:1;i:6;}}}', ['allowed_classes' => false])
				)
			)
		);

		foreach ($reports as $version => &$vreports)
		{
			foreach ($vreports as $num => &$report)
			{
				if ($version === '11.0.3' && $num === 0)
				{
					$report['settings']['select'][0]['alias'] = GetMessage('TASKS_REPORT_EFF_EMPLOYEE');
					$report['settings']['select'][1]['alias'] = GetMessage('TASKS_REPORT_EFF_NEW');
					$report['settings']['select'][2]['alias'] = GetMessage('TASKS_REPORT_EFF_OPEN');
					$report['settings']['select'][3]['alias'] = GetMessage('TASKS_REPORT_EFF_CLOSED');
					$report['settings']['select'][4]['alias'] = GetMessage('TASKS_REPORT_EFF_OVERDUE');
					$report['settings']['select'][5]['alias'] = GetMessage('TASKS_REPORT_EFF_MARKED');
					$report['settings']['select'][6]['alias'] = GetMessage('TASKS_REPORT_EFF_EFFICIENCY');
				}
				else if ($version === '14.0.10' && $report['mark_default'] === 7)
				{
					$report['settings']['select'][4]['alias'] = GetMessage('TASKS_REPORT_DEFAULT_7_ALIAS_4');
					$report['settings']['select'][6]['alias'] = GetMessage('TASKS_REPORT_DEFAULT_7_ALIAS_6');
					$report['settings']['select'][8]['alias'] = GetMessage('TASKS_REPORT_DEFAULT_7_ALIAS_8');
					$report['settings']['select'][10]['alias'] = GetMessage('TASKS_REPORT_DEFAULT_7_ALIAS_10');
				}
			}
		}

		unset($vreports, $report);

		return $reports;
	}


	public static function getFirstVersion()
	{
		return '11.0.1';
	}


	public static function appendBooleanUserFieldsIfNull(\Bitrix\Main\Entity\Base $entity)
	{
		/** @var Bitrix\Main\DB\SqlHelper $sqlHelper */
		$sqlHelper = null;

		// Advanced fields for boolean user fields
		$dateFields = array();
		foreach($entity->getFields() as $field)
		{
			if ($field instanceof Bitrix\Main\Entity\ExpressionField)
			{
				$arUF = self::detectUserField($field);
				if ($arUF['isUF'])
				{
					$ufDataType = self::getUserFieldDataType($arUF);
					if ($ufDataType === 'boolean' && $arUF['ufInfo']['MULTIPLE'] !== 'Y')
					{
						if ($sqlHelper === null)
						{
							$sqlHelper = Main\Application::getConnection()->getSqlHelper();
						}

						$blPostfix = defined('self::UF_BOOLEAN_POSTFIX') ? self::UF_BOOLEAN_POSTFIX : '_BLINL';
						$dateFields[] = array(
							'def' => array(
								'data_type' => 'boolean',
								'expression' => array(
									$sqlHelper->getIsNullFunction('%s', 0), $arUF['ufInfo']['FIELD_NAME']
								)
							),
							'name' => $arUF['ufInfo']['FIELD_NAME'].$blPostfix
						);
					}
				}
			}
		}
		foreach ($dateFields as $fieldInfo)
		{
			if (!$entity->hasField($fieldInfo['name']))
			{
				$entity->addField($fieldInfo['def'], $fieldInfo['name']);
			}
		}
	}

	public static function appendDateTimeUserFieldsAsShort(\Bitrix\Main\Entity\Base $entity)
	{
		/** @global CDatabase $DB */
		global $DB;

		// Advanced fields for datetime user fields
		$dateFields = array();
		foreach($entity->getFields() as $field)
		{
			if ($field instanceof Bitrix\Main\Entity\ExpressionField)
			{
				$arUF = self::detectUserField($field);
				if ($arUF['isUF'])
				{
					$ufDataType = self::getUserFieldDataType($arUF);
					if ($ufDataType === 'datetime' && $arUF['ufInfo']['MULTIPLE'] !== 'Y')
					{
						$dateFields[] = array(
							'def' => array(
								'data_type' => 'datetime',
								'expression' => array(
									$DB->DatetimeToDateFunction('%s'), $arUF['ufInfo']['FIELD_NAME']
								)
							),
							'name' => $arUF['ufInfo']['FIELD_NAME'].self::UF_DATETIME_SHORT_POSTFIX
						);
					}
				}
			}
		}
		foreach ($dateFields as $fieldInfo)
		{
			if (!$entity->hasField($fieldInfo['name']))
			{
				$entity->addField($fieldInfo['def'], $fieldInfo['name']);
			}
		}
	}

	public static function appendMoneyUserFieldsAsSeparated(\Bitrix\Main\Entity\Base $entity)
	{
		/** @global CDatabase $DB */
		global $DB;

		// Advanced fields for datetime user fields
		$moneyFields = array();
		foreach($entity->getFields() as $field)
		{
			if (in_array($field->getName(), array('LEAD_BY', 'COMPANY_BY', 'CONTACT_BY'), true)
				&& $field instanceof Bitrix\Main\Entity\ReferenceField)
			{
				self::appendMoneyUserFieldsAsSeparated($field->getRefEntity());
			}
			else if ($field instanceof Bitrix\Main\Entity\ExpressionField)
			{
				$arUF = self::detectUserField($field);
				if ($arUF['isUF'])
				{
					$ufDataType = self::getUserFieldDataType($arUF);
					$fieldName = $arUF['ufInfo']['FIELD_NAME'];
					if ($ufDataType === 'money')
					{
						$moneyFields[] = array(
							'def' => array(
								'data_type' => 'float',
								'expression' => array(
									"(IFNULL(IF(".
									"LOCATE('|', %s) > 0, ".
									"CAST(SUBSTR(%s, 1, LOCATE('|', %s) - 1) AS DECIMAL(18,2)), ".
									"CAST(%s AS DECIMAL(18,2))".
									"), 0))", $fieldName, $fieldName, $fieldName, $fieldName
								)
							),
							'name' => $fieldName.self::UF_MONEY_NUMBER_POSTFIX
						);
						$moneyFields[] = array(
							'def' => array(
								'data_type' => 'string',
								'expression' => array(
									"(IFNULL(IF(LOCATE('|', %s) > 0, SUBSTR(%s, LOCATE('|', %s) + 1), NULL), ''))",
									$fieldName, $fieldName, $fieldName
								)
							),
							'name' => $fieldName.self::UF_MONEY_CURRENCY_POSTFIX
						);
					}
				}
			}
		}
		foreach ($moneyFields as $fieldInfo)
		{
			if (!$entity->hasField($fieldInfo['name']))
			{
				$entity->addField($fieldInfo['def'], $fieldInfo['name']);
			}
		}
	}

	public static function appendTextUserFieldsAsTrimmed(\Bitrix\Main\Entity\Base $entity)
	{
		// Advanced fields for text user fields
		$textFields = array();

		foreach ($textFields as $fieldInfo)
		{
			if (is_object($fieldInfo))
			{
				if (!$entity->hasField($fieldInfo->getName()))
				{
					$entity->addField($fieldInfo);
				}
			}
			else
			{
				if (!$entity->hasField($fieldInfo['name']))
				{
					$entity->addField($fieldInfo['def'], $fieldInfo['name']);
				}
			}
		}
	}

	public static function getFDMsMultipleTrimmed()
	{
		return array(
			array(__CLASS__, 'fdmMultipleTrimmed')
		);
	}

	public static function getFDMsMultipleTrimmedDateTime()
	{
		return array(
			array(__CLASS__, 'fdmMultipleTrimmed'),
			array(__CLASS__, 'fdmMultipleTrimmedDateTime')
		);
	}

	public static function fdmMultipleTrimmed($value, $query, $dataRow, $columnAlias)
	{
		$result = @unserialize($value, ['allowed_classes' => false]);

		return $result;
	}

	public static function fdmMultipleTrimmedDateTime($value, $query, $dataRow, $columnAlias)
	{
		$result = array();

		if (is_array($value))
		{
			foreach ($value as $v)
			{
				if (!empty($v))
				{
					try
					{
						//try new independent datetime format
						$v = new Bitrix\Main\Type\DateTime($v, \Bitrix\Main\UserFieldTable::MULTIPLE_DATETIME_FORMAT);
					}
					catch (Main\ObjectException $e)
					{
						//try site format
						try
						{
							$v = new Bitrix\Main\Type\DateTime($v);
						}
						catch (Main\ObjectException $e)
						{
							//try short format
							$v = Bitrix\Main\Type\DateTime::createFromUserTime($v);
						}
					}
					$result[] = $v;
				}
			}
		}

		return $result;
	}

	public static function getCurrentVersion()
	{
		$arModuleVersion = array();
		include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/version.php");
		return $arModuleVersion['VERSION'];
	}

	private static function getSubordinateSql(): string
	{
		$userId = $arParams['USER_ID'] ?? 0;
		$departmentIds = Department::getSubordinateIds($userId, true);
		$departmentIds = array_map('intval', $departmentIds);
		if (count($departmentIds) <= 0)
		{
			return '';
		}
		$departmentOption = CUserTypeEntity::GetList([], [
			'ENTITY_ID' => 'USER',
			'FIELD_NAME' => 'UF_DEPARTMENT',
		])->Fetch();

		if (!$departmentOption)
		{
			return '';
		}

		$fieldId = (int)$departmentOption['ID'];
		$departmentIds = implode(',', $departmentIds);
		$sql = "
			(CASE
				WHEN EXISTS
					(SELECT 'x'
					FROM b_utm_user BUF1
					WHERE BUF1.FIELD_ID = {$fieldId}
					AND BUF1.VALUE_ID = `tasks_task`.RESPONSIBLE_ID
					AND BUF1.VALUE_INT IN ({$departmentIds})
					) THEN 1
				WHEN EXISTS
					(SELECT 'x'
					FROM b_utm_user BUF2
					WHERE BUF2.FIELD_ID = {$fieldId}
					AND BUF2.VALUE_ID = `tasks_task`.CREATED_BY
					AND BUF2.VALUE_INT IN ({$departmentIds})
					) THEN 1
				WHEN EXISTS
					(SELECT 'x'
					FROM b_utm_user BUF3
					WHERE BUF3.FIELD_ID = {$fieldId}
					AND EXISTS(SELECT 'x' FROM b_tasks_member DSTM WHERE DSTM.TASK_ID = `tasks_task`.ID AND DSTM.USER_ID = BUF3.VALUE_ID)
					AND BUF3.VALUE_INT IN ({$departmentIds})
				) THEN 1
			ELSE 0
			END)
		";

		return $sql;
	}
}