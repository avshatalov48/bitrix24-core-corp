<?php

namespace Bitrix\Tasks\Ui\Filter\Convert;

use Bitrix\Main\UI\Filter\DateType;

final class Filter
{
	public static function process($userId)
	{
		global $DB;

		$gridOptionsRes = $DB->query("
			SELECT * FROM `b_user_option`
			WHERE 
				`USER_ID`={$userId} 
				AND `CATEGORY` = 'main.ui.filter'
				AND `NAME` = 'TASKS_GRID_ROLE_ID_4096_0_ADVANCED_N'
		");

		$gridOptions = $gridOptionsRes->Fetch();
		$gridOptions['VALUE'] = unserialize($gridOptions['VALUE'], ['allowed_classes' => false]);

		$dbRes = $DB->query(
			"	SELECT ID, NAME, PARENT, SERIALIZED_FILTER
				FROM b_tasks_filters
				WHERE USER_ID = " . (int)$userId . "
				ORDER BY NAME, ID"
		);

		if ($dbRes)
		{
			while ($arData = $dbRes->fetch())
			{
				$fields = self::prepareFilter(unserialize($arData['SERIALIZED_FILTER'], ['allowed_classes' => false]));
				if(!$fields)
					continue;

				$gridOptions['VALUE']['filters']['exported_filter_'.microtime(true).'_'.$arData['ID']]= array(
					'name'=>$arData['NAME'],
					'fields'=>$fields,
					'filter_rows'=>join(',',array_keys($fields))
				);
				$DB->query(
					"UPDATE `b_user_option` SET
						`VALUE` = '".$DB->ForSql(serialize($gridOptions['VALUE']))."' WHERE
						`ID` = '".$DB->ForSql($gridOptions["ID"])."'"
				);


			}
		}
	}

	public static function prepareFilter($filter)
	{
		$filter = self::normalizeFilter($filter);

		$filter = self::processFilter($filter);

		if(array_key_exists('-1',$filter))
			return array();

		foreach(array_keys($filter) as $key)
		{
			if(mb_strpos($key, '!') !== false && $key != '!STATUS')
			{
				return array();
			}
		}

		if($filter['::LOGIC'] == 'OR' && count($filter)>2)
			return array();

		unset($filter['::LOGIC']);

		if(array_key_exists('%TITLE', $filter))
		{
			$filter['TITLE']=$filter['%TITLE'];
			unset($filter['%TITLE']);
		}


		if(array_key_exists('RESPONSIBLE_ID', $filter))
		{
			$filter['RESPONSIBLE_ID']=(array)$filter['RESPONSIBLE_ID'];
			$userNames = \Bitrix\Tasks\Util\User::getUserName($filter['RESPONSIBLE_ID']);

			foreach($filter['RESPONSIBLE_ID'] as $k=>$userId)
			{
				$filter['RESPONSIBLE_ID_label'][$k] = $userNames[$userId];
			}
		}
		if(array_key_exists('CREATED_BY', $filter))
		{
			$filter['CREATED_BY']=(array)$filter['CREATED_BY'];
			$userNames = \Bitrix\Tasks\Util\User::getUserName($filter['CREATED_BY']);
			foreach($filter['CREATED_BY'] as $k=>$userId)
			{
				$filter['RESPONSIBLE_ID_label'][$k] = $userNames[$userId];
			}
		}
		if(array_key_exists('AUDITOR', $filter))
		{
			$filter['AUDITOR']=(array)$filter['AUDITOR'];
			$userNames = \Bitrix\Tasks\Util\User::getUserName($filter['AUDITOR']);
			foreach($filter['AUDITOR'] as $k=>$userId)
			{
				$filter['AUDITOR_label'][$k] = $userNames[$userId];
			}
		}
		if(array_key_exists('ACCOMPLICE', $filter))
		{
			$filter['ACCOMPLICE']=(array)$filter['ACCOMPLICE'];
			$userNames = \Bitrix\Tasks\Util\User::getUserName($filter['ACCOMPLICE']);
			foreach($filter['ACCOMPLICE'] as $k=>$userId)
			{
				$filter['ACCOMPLICE_label'][$k] = $userNames[$userId];
			}
		}

		if(array_key_exists('GROUP_ID', $filter))
		{
			$filter['GROUP_ID']=(array)$filter['GROUP_ID'];
			foreach($filter['GROUP_ID'] as $k=>$groupId)
			{
				$group = \Bitrix\Tasks\Integration\SocialNetwork\Group::getData(array($groupId));
				$groupName = htmlspecialcharsbx($group[$groupId]['NAME']);
				$filter['GROUP_ID_label'][$k] = $groupName;
			}
		}


		return $filter;
	}

	private static function processFilter($filter, $depth = 0)
	{
		for($i=1;$i<100;$i++)
		{
			if(array_key_exists('::SUBFILTER-'.$i, $filter))
			{
				$result = self::processFilter($filter['::SUBFILTER-'.$i], $depth+1);
				unset($filter['::SUBFILTER-'.$i]);
				unset($result['::LOGIC']);
				$filter += $result;

			}
			else
			{
				continue;
			}
		}

		if($depth > 0 && $filter['::LOGIC'] == 'OR' && count($filter)>2)
		{
			$filter += array(-1=>-1);
		}

		return $filter;
	}

	private static function normalizeFilter($filter)
	{
		$newFilter = array();
		foreach($filter as $key=>$val)
		{
			$key = trim($key);
			if(mb_substr($key, 0, 12) == '::SUBFILTER-')
			{
				$val = self::normalizeFilter($val);
			}

			if(!array_key_exists(trim($key), $newFilter))
			{
				$newFilter[trim($key)]=$val;
			}
			else
			{
				if($filter['::LOGIC']=='OR')
				{
					if (is_array($newFilter[trim($key)]))
					{
						if(is_array($val))
						{
							$newFilter[trim($key)] += $val;
						}
						else
						{
							$newFilter[trim($key)][] = $val;
						}
					}
					else
					{
						$newFilter[trim($key)] = array(
							$newFilter[trim($key)],
							$val
						);
					}
				}
			}

			if($key == '!STATUS')
			{
				$newFilter['STATUS'] = static::prepareNegativeStatusField($val);
				unset($newFilter[$key]);
			}

			if(mb_strpos($key, 'META:') !== false)
			{
				$f = static::prepareMetaField($key, $val);
				if(!empty($f) && is_array($f))
				{
					$newFilter = array_merge($newFilter, $f);
				}
				unset($newFilter[$key]);
			}
		}

		return $newFilter;
	}

	private static function prepareMetaField($key, $val)
	{
		if(mb_substr($key, 0, 2) == '#R')
		{
			return static::prepareDateField($key, $val);
		}
		else if(mb_strpos($key, 'META:') !== false)
		{
			$key = str_replace('META:','',$key);
			$key = str_replace('_TS','',$key);
			$val = date('d.m.Y H:i:s', $val);

			if(mb_strpos($key, '>') !== false)
			{
				$key .= '_from';
			}
			else if(mb_strpos($key, '<') !== false)
			{
				$key .= '_to';
			}
			else
			{
				$key = str_replace(array('<','>'),'',$key);
				return array(
					$key.'_datesel'=>Datetype::EXACT,
					$key.'_from'=>$val,
					$key.'_to'=>$val,
				);
			}

			$key = str_replace(array('<','>'),'',$key);

			return array($key=>$val);
		}
	}

	private static function prepareDateField($key, $val)
	{
		$newPreset = array();

		$res = \CTasks::MkOperationFilter($key);
		$cOperationType = $res["OPERATION"];

		$fieldName = mb_substr($res["FIELD"], 5, -3);	// Cutoff prefix "META:" and suffix "_TS"
		$operationCode = (int)mb_substr($cOperationType, 1);

		switch($operationCode)
		{
			case \CTaskFilterCtrl::OP_DATE_TODAY:
				$op = Datetype::CURRENT_DAY;
				break;
			case \CTaskFilterCtrl::OP_DATE_YESTERDAY:
				$op = Datetype::YESTERDAY;
				break;
			case \CTaskFilterCtrl::OP_DATE_TOMORROW:
				$op = Datetype::TOMORROW;
				break;
			case \CTaskFilterCtrl::OP_DATE_CUR_WEEK:
				$op = Datetype::CURRENT_WEEK;
				break;
			case \CTaskFilterCtrl::OP_DATE_PREV_WEEK:
				$op = Datetype::LAST_7_DAYS;
				break;
			case \CTaskFilterCtrl::OP_DATE_NEXT_WEEK:
				$op = Datetype::NEXT_WEEK;
				break;
			case \CTaskFilterCtrl::OP_DATE_CUR_MONTH:
				$op = Datetype::CURRENT_MONTH;
				break;
			case \CTaskFilterCtrl::OP_DATE_PREV_MONTH:
				$op = Datetype::LAST_MONTH;
				break;
			case \CTaskFilterCtrl::OP_DATE_NEXT_MONTH:
				$op = Datetype::NEXT_MONTH;
				break;
			case \CTaskFilterCtrl::OP_DATE_LAST_DAYS:
				$op = Datetype::PREV_DAYS;
				return array(
					$fieldName.'_datesel'=>$op,
					$fieldName.'_days'=>$val
				);
				break;
			case \CTaskFilterCtrl::OP_DATE_NEXT_DAYS:
				$op = Datetype::NEXT_DAYS;

				return array(
					$fieldName.'_datesel'=>$op,
					$fieldName.'_days'=>$val
				);
				break;
			default:
				\CTaskAssert::logFatal('Unknown OP_DATE type: '.$operationCode);

				break;
		}

		return array($fieldName.'_datesel'=>$op);
	}

	private static function prepareNegativeStatusField($statuses)
	{
		$availableStatuses = array(-3,-2,-1,1,2,3,4,5,6,7);
		return array_diff($availableStatuses, (array)$statuses);
	}
}