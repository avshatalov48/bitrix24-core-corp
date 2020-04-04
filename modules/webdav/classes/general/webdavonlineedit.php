<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CWebDavLogOnlineEditBase
{
	const TABLE_NAME = 'b_webdav_file_online_edit';
	const TABLE_ALIAS = 'FON';
	const GOOGLE_SERVICE_NAME = 'g';
	const SKYDRIVE_SERVICE_NAME = 's';
	const LOCAL_SERVICE_NAME = 'l';
	const DEFAULT_SERVICE_NAME = 'd';
	protected static $tableColumns = array(
		'ID' => 'ID',
		'IBLOCK_ID' => 'IBLOCK_ID',
		'SECTION_ID' => 'SECTION_ID',
		'ELEMENT_ID' => 'ELEMENT_ID',
		'USER_ID' => 'USER_ID',
		'OWNER_ID' => 'OWNER_ID',
		'SERVICE' => 'SERVICE',
		'SERVICE_FILE_ID' => 'SERVICE_FILE_ID',
		'SERVICE_FILE_LINK' => 'SERVICE_FILE_LINK',
		'CREATED_TIMESTAMP' => 'CREATED_TIMESTAMP',
	);
	protected static $selectColumns = array(
		'ID' => 'ID',
		'IBLOCK_ID' => 'IBLOCK_ID',
		'SECTION_ID' => 'SECTION_ID',
		'ELEMENT_ID' => 'ELEMENT_ID',
		'USER_ID' => 'USER_ID',
		'OWNER_ID' => 'OWNER_ID',
		'SERVICE' => 'SERVICE',
		'SERVICE_FILE_ID' => 'SERVICE_FILE_ID',
		'SERVICE_FILE_LINK' => 'SERVICE_FILE_LINK',
		'CREATED_TIMESTAMP' => 'CREATED_TIMESTAMP',
		'USER' => 'USER',
	);

	/**
	 * @param array $fields
	 * @return bool|CDBResult
	 */
	public static function add(array $fields)
	{
		$t = static::TABLE_NAME;
		static::filterFields($fields);

		list($cols, $vals) = static::getDb()->prepareInsert($t, $fields);

		return static::getDb()->query("INSERT INTO {$t} ({$cols}) VALUES({$vals})");
	}

	/**
	 * @param array $order
	 * @param array $filter
	 * @param array $select
	 * @return bool|CDBResult
	 */
	public static function getList(array $order = array(), array $filter = array(), array $select = array())
	{
		$t = static::TABLE_NAME;
		$a = static::TABLE_ALIAS;
		static::filterFields($order);
		static::filterFields($filter);

		$sqlSelect = '';
		$sqlFrom = $t . ' ' . $a;
		foreach (array_keys(static::$tableColumns) as $column)
		{
			$sqlSelect .= (empty($sqlSelect)? '':', ') . $a . '.' . $column;
		}
		unset($column);

		if($select)
		{
			foreach ($select as $field)
			{
				$field = strtoupper($field);
				switch($field)
				{
					case 'USER':
						$sqlSelect .= ", UC.NAME USER_NAME, UC.LAST_NAME USER_LAST_NAME, UC.SECOND_NAME USER_SECOND_NAME, UC.EMAIL USER_EMAIL, UC.ID USER_ID, UC.LOGIN USER_LOGIN, UC.PERSONAL_GENDER USER_GENDER";
						$sqlFrom .= "\tLEFT JOIN b_user UC ON UC.ID={$a}.USER_ID\n";
						break;
				}
			}
			unset($field);
		}
		
		$where = $filter;
		$sqlWhere = array();
		foreach ($where as $field => $value)
		{
			switch($field)
			{
				case 'ID':
				case 'IBLOCK_ID':
				case 'SECTION_ID':
				case 'ELEMENT_ID':
				case 'USER_ID':
				case 'OWNER_ID':
					if($value === null)
					{
						continue;
					}
					$value = (int)$value;
					$sqlWhere[] = $field . '=' . $value;
					break;
				case 'SERVICE_FILE_ID':
				case 'SERVICE':
					$value = static::getDb()->forSql($value);
					$sqlWhere[] = $field . '=' . '\'' . $value . '\'';
					break;
				case 'CREATED_TIMESTAMP':
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

		return static::getDb()->query("SELECT {$sqlSelect} FROM {$sqlFrom} {$sqlWhere} {$sqlOrder}");
	}

	/**
	 * @param $filter
	 * @return bool|CDBResult
	 */
	public static function delete($filter)
	{
		$t = static::TABLE_NAME;
		static::filterFields($filter);
		$sqlWhere = array();
		foreach ($filter as $field => $value)
		{
			switch($field)
			{
				case 'ID':
				case 'IBLOCK_ID':
				case 'SECTION_ID':
				case 'ELEMENT_ID':
				case 'USER_ID':
				case 'OWNER_ID':
					if($value === null)
					{
						continue;
					}
					$value = (int)$value;
					$sqlWhere[] = $field . '=' . $value;
					break;
				case 'SERVICE_FILE_ID':
				case 'SERVICE':
					$value = static::getDb()->forSql($value);
					$sqlWhere[] = $field . '=' . '\'' . $value . '\'';
					break;
				case 'CREATED_TIMESTAMP':
					$value = (int)$value;
					$sqlWhere[] = $field . '>=' . $value;
					break;
			}
		}
		unset($value);

		if(empty($sqlWhere))
		{
			return false;
		}
		$sqlWhere = GetFilterSqlSearch($sqlWhere);

		return static::getDb()->query("DELETE FROM {$t} WHERE {$sqlWhere}");
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

	/**
	 * @return CDatabase
	 */
	protected static function getDb()
	{
		global $DB;

		return $DB;
	}

	/**
	 * @param array $element
	 * @param bool  $showUser
	 * @param null  $serviceName
	 * @return array
	 */
	public static function getOnlineSessions(array $element, $showUser = false, $serviceName = null)
	{
		//sort by CREATED_TIMESTAMP to get last link
		$filter = array(
			'IBLOCK_ID' => $element['IBLOCK_ID'],
			'SECTION_ID' => $element['SECTION_ID'],
			'ELEMENT_ID' => $element['ELEMENT_ID'],
		);
		if(!is_null($serviceName))
		{
			$filter['SERVICE'] = $serviceName;
		}
		$select = array();
		if($showUser)
		{
			$select = array('USER');
		}
		$onlineSessions = CWebDavLogOnlineEdit::getList(array(), $filter, $select);
		if(!$onlineSessions)
		{
			return array();
		}
		$sessions = array();
		while($session = $onlineSessions->fetch())
		{
			$sessions[] = $session;
		}
		unset($session);

		return $sessions;
	}

	/**
	 * @param array $element
	 * @param bool  $showUser
	 * @param null  $serviceName
	 * @return array
	 */
	public static function getOnlineLastSession(array $element, $showUser = false, $serviceName = null)
	{
		//sort by CREATED_TIMESTAMP to get last link
		$filter = array(
			'IBLOCK_ID' => $element['IBLOCK_ID'],
			'SECTION_ID' => $element['SECTION_ID'],
			'ELEMENT_ID' => $element['ELEMENT_ID'],
		);
		if(!is_null($serviceName))
		{
			$filter['SERVICE'] = $serviceName;
		}
		$select = array();
		if($showUser)
		{
			$select = array('USER');
		}
		
		$onlineSession = CWebDavLogOnlineEdit::getList(array('CREATED_TIMESTAMP' => 'DESC'), $filter, $select);
		if($onlineSession)
		{
			$onlineSession = $onlineSession->fetch();
		}

		return is_array($onlineSession)? $onlineSession : array();
	}

	/**
	 * @param array $element
	 * @return bool
	 */
	public static function getOnlineService(array $element)
	{
		$session = static::getOnlineLastSession($element);

		return !empty($session['SERVICE'])? $session['SERVICE'] : false;
	}

	/**
	 * @param array $element
	 * @return array
	 */
	public static function getOnlineUsers(array $element)
	{
		return static::getOnlineSessions($element, true);
	}
}
