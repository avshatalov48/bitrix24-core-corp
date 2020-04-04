<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CWebDavLogDeletedElementBase
{
	const TABLE_NAME = 'b_webdav_storage_delete_log';

	protected static $tableColumns = array(
		'ID' => 'ID',
		'IBLOCK_ID' => 'IBLOCK_ID',
		'SECTION_ID' => 'SECTION_ID',
		'ELEMENT_ID' => 'ELEMENT_ID',
		'IS_DIR' => 'IS_DIR',
		'VERSION' => 'VERSION',
		'USER_ID' => 'USER_ID',
	);

	protected static $maxLengthBatch = 2048;

	public static function add(array $fields)
	{
		$t = static::TABLE_NAME;
		if(empty($fields['VERSION']))
		{
			$fields['VERSION'] = time();
		}

		static::filterFields($fields);

		//todo version is long int
		list($cols, $vals) = static::getDb()->prepareInsert($t, $fields);

		return static::getDb()->query("INSERT INTO {$t} ({$cols}) VALUES({$vals})");
	}

	public static function addBatch(array $items)
	{
		if(empty($items))
		{
			return;
		}
		foreach ($items as $item)
		{
			static::add($item);
		}
		unset($item);
	}

	public static function getList(array $order = array(), array $filter = array())
	{
		$t = static::TABLE_NAME;

		static::filterFields($order);
		static::filterFields($filter);

		$sqlWhere = array();
		foreach ($filter as $field => $value)
		{
			switch($field)
			{
				case 'IBLOCK_ID':
				case 'IS_DIR':
				case 'SECTION_ID':
				case 'USER_ID':
					$value = (int)$value;
					$sqlWhere[] = $field . '=' . $value;
					break;
				case 'ELEMENT_ID':
					$value = static::getDb()->forSql($value);
					$sqlWhere[] = $field . '=' . '\'' . $value . '\'';
					break;
				case 'VERSION':
					//todo version is long int
					$value = (int)$value;
					$sqlWhere[] = $field . '>=' . $value;
					break;
			}
		}
		unset($value);

		if($sqlWhere)
		{
			$sqlWhere = ' WHERE ' . implode(' AND ', $sqlWhere);
		}
		else
		{
			$sqlWhere = '';
		}

		$sqlOrder = '';
		if($order)
		{
			$sqlOrder = array();
			foreach ($order as $by => $ord)
			{
				$by = strtoupper($by);
				$sqlOrder[] = $by . ' ' . (strtoupper($ord) == 'DESC' ? 'DESC' : 'ASC');
			}
			unset($by);
			$sqlOrder = ' ORDER BY ' . implode(', ', $sqlOrder);
		}

		return static::getDb()->query("SELECT * FROM {$t} {$sqlWhere} {$sqlOrder}");
	}

	public static function isAlreadyRemoved(array $fields)
	{
		if(!($query = static::getList(array('VERSION' => 'DESC'), $fields)))
		{
			return false;
		}
		$last = $query->fetch();

		return $last['VERSION'];
	}

	public function delete()
	{}

	/**
	 * @return CDatabase
	 */
	protected static function getDb()
	{
		global $DB;

		return $DB;
	}

	/**
	 * @param $fields
	 * @return void
	 */
	protected static function filterFields(&$fields)
	{
		$fields = array_intersect_key($fields, static::$tableColumns);

		return;
	}
}
