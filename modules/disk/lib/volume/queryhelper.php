<?php

namespace Bitrix\Disk\Volume;

use Bitrix\Main\Application;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internals\ObjectPathTable;
use \Bitrix\Main\Entity\Query;


class QueryHelper
{
	/**
	 * Gets select fields as SQL code.
	 * @param array $selectFields Selected fields.
	 * @return string
	 */
	public static function prepareSelect(array $selectFields)
	{
		$select = array();
		foreach ($selectFields as $alias => $statement)
		{
			$select[] = "$statement as $alias";
		}
		$selectSql = '';
		if (count($select))
		{
			$selectSql = ', '. implode(', ', $select);
		}

		return $selectSql;
	}


	/**
	 * Get filter parameters as SQL code.
	 * @param array $filterFields Gets filter parameters.
	 * @param array $filterAlias Aliases for the filter fields.
	 * @return string
	 */
	public static function prepareWhere(array $filterFields, array $filterAlias = array())
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();

		$where = array();
		$logic = 'AND';
		// todo: wrap it into recursive
		foreach ($filterFields as $key => $val)
		{
			if ($key === 'LOGIC')
			{
				$logic = $val;
				continue;
			}
			$operator = '=';
			if (!is_numeric($key))
			{
				if (preg_match("/^([=<>!@%]+)([^=<>!@%]+)$/", $key, $parts))
				{
					list(, $operator, $key) = $parts;
				}
				if (is_array($val) && !isset($val['LOGIC']))
				{
					if ($operator === '=')
					{
						$operator = '@';
					}
					elseif ($operator === '!')
					{
						$operator = '!@';
					}
				}
				if (isset($filterAlias[$key]))
				{
					$key = $filterAlias[$key];
				}
			}
			switch ($operator)
			{
				case '!':
					$where[] = "$key != '". $sqlHelper->forSql($val). "'";
					break;

				case '%':
					$where[] = "$key LIKE '%". $sqlHelper->forSql($val). "%'";
					break;

				case '!%':
					$where[] = "$key NOT LIKE '%". $sqlHelper->forSql($val). "%'";
					break;

				case '@':
				{
					if (is_array($val) && count($val) > 0)
					{
						$val = array_map(array($sqlHelper, 'forSql'), $val);
						$where[] = "$key IN('".implode("', '", $val)."')";
					}
					elseif (is_string($val) && strlen($val) > 0)
					{
						$where[] = "$key IN(".$val.')';
					}
					break;
				}

				case '!@':
				{
					if (is_array($val) && count($val) > 0)
					{
						$val = array_map(array($sqlHelper, 'forSql'), $val);
						$where[] = "$key NOT IN('".implode("', '", $val)."')";
					}
					elseif (is_string($val) && strlen($val) > 0)
					{
						$where[] = "$key NOT IN(".$val.')';
					}
					break;
				}

				default:
				{
					if (is_array($val))// && isset($val['LOGIC']))// && $val['LOGIC'] === 'OR')// OR condition
					{
						$subLogic = 'AND';
						if (isset($val['LOGIC']) && $val['LOGIC'] === 'OR')
						{
							$subLogic = 'OR';
							unset($val['LOGIC']);
						}

						//$val = array_pop($val);

						$condition = array();
						foreach ($val as $k => $v)
						{
							$subOperator = '=';
							if (preg_match("/^([=<>!@%]+)([^=<>!@%]+)$/", $k, $parts))
							{
								list(, $subOperator, $k) = $parts;
							}
							if (isset($filterAlias[$k]))
							{
								$k = $filterAlias[$k];
							}
							switch ($subOperator)
							{
								case '!':
									$condition[] = "$k != '".$sqlHelper->forSql($v)."'";
									break;
								case '%':
									$condition[] = "$k LIKE '%".$sqlHelper->forSql($v)."%'";
									break;
								case '!%':
									$condition[] = "$k NOT LIKE '%".$sqlHelper->forSql($v)."%'";
									break;
								default:
									$condition[] = "$k $subOperator '".$sqlHelper->forSql($v)."'";
							}
						}
						$where[] = '('.implode(" $subLogic ", $condition).')';
					}
					else
					{
						$where[] = "$key $operator '".$sqlHelper->forSql($val)."'";
					}
					break;
				}
			}
		}

		$whereSql = '';
		if (count($where))
		{
			$whereSql = ' AND '. implode(" $logic ", $where);
		}

		return $whereSql;
	}


	/**
	 * Gets group by fields as SQL code.
	 * @param array $groupByFields Group by fields.
	 * @return string
	 */
	public static function prepareGroupBy(array $groupByFields)
	{
		$groupBySql = '';
		if (count($groupByFields))
		{
			$groupBySql = ', '. implode(', ', $groupByFields);
		}

		return $groupBySql;
	}


	/**
	 * Gets sort order fields as SQL code.
	 * @param string[] $orderFields Order fields set.
	 * @param string[] $fieldAlias Aliases for the order fields.
	 * @return string
	 */
	public static function prepareOrder(array $orderFields, array $fieldAlias = array())
	{
		$order = array();
		foreach ($orderFields as $field => $direction)
		{
			if (isset($fieldAlias[$field]))
			{
				$field = $fieldAlias[$field];
			}
			$order[] = "$field $direction";
		}
		$orderSql = '';
		if (count($order))
		{
			$orderSql = ' '. implode(', ', $order);
		}

		return $orderSql;
	}

	/**
	 * Gets fields to insert as SQL code.
	 * @param string[] $columns Insert fields.
	 * @param array $selectFields Selected fields.
	 * @return string
	 */
	public static function prepareInsert(array $columns, array $selectFields = array())
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();

		$columns = array_flip($columns);

		foreach ($selectFields as $alias => $statement)
		{
			$columns[$alias] = '';
		}

		if (isset($columns['CREATE_TIME']))
		{
			$columns['CREATE_TIME'] = new \Bitrix\Main\Type\DateTime();
		}

		$tableName = \Bitrix\Disk\Internals\VolumeTable::getTableName();
		list($columnList, , ) = $sqlHelper->prepareInsert($tableName, $columns);

		return $columnList;
	}


	/**
	 * Gets fields for UPDATE query based on SELECT as SQL code.
	 * @param string[] $columns Update fields.
	 * @param array $selectFields Selected fields.
	 * @param string $tableAlias Destination table alias.
	 * @param string $selectAlias Source query alias.
	 * @return string
	 */
	public static function prepareUpdateOnSelect(array $columns, array $selectFields, $tableAlias = 'dest', $selectAlias = 'src')
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();

		foreach ($selectFields as $alias => $statement)
		{
			if (!in_array($alias, $columns))
			{
				$columns[] = $alias;
			}
		}

		$tableName = \Bitrix\Disk\Internals\VolumeTable::getTableName();
		$tableFields = Application::getConnection()->getTableFields($tableName);

		$columnList = array();
		foreach ($columns as $columnName)
		{
			if (isset($tableFields[$columnName]))
			{
				$columnName = $sqlHelper->quote($columnName);
				$columnList[] = "$tableAlias.$columnName = $selectAlias.$columnName";
			}
		}

		return implode(', ', $columnList);
	}


	/**
	 * Gets SQL query code for folder tree structure.
	 * @param int $parentId Top parent folder.
	 * @return string
	 */
	public static function prepareFolderTreeQuery($parentId)
	{
		$subQuery = new Query(ObjectPathTable::getEntity());
		$subQuery
			->registerRuntimeField('folder', array(
				'data_type' => '\Bitrix\Disk\Internals\ObjectTable',
				'reference' => array(
					'=this.OBJECT_ID' => 'ref.ID',
				),
				'join_type' => 'INNER',
			))
			->addSelect('OBJECT_ID')
			->addFilter('=folder.TYPE', ObjectTable::TYPE_FOLDER)// type folder
			//->addFilter('=folder.DELETED_TYPE', ObjectTable::DELETED_TYPE_NONE)// not deleted
			->addFilter('=folder.ID', new \Bitrix\Main\DB\SqlExpression('disk_internals_object_path_folder.REAL_OBJECT_ID'))// not link
			->addFilter('=PARENT_ID', $parentId)
		;

		return $subQuery->getQuery();
	}
}
