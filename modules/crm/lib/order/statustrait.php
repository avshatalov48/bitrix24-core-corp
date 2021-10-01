<?php

namespace Bitrix\Crm\Order;

use Bitrix\Main;
use Bitrix\Crm;

Main\Localization\Loc::loadMessages(__FILE__);

/**
 * Class OrderStatus
 * @package Bitrix\Crm\Order
 */
trait StatusTrait
{
	/**
	 * @throws Main\NotImplementedException
	 * @return mixed
	 */
	public static function getFinalUnsuccessfulStatus()
	{
		throw new Main\NotImplementedException();
	}

	/**
	 * @param $statusID
	 * @return string
	 * @throws Main\NotImplementedException
	 */
	public static function getSemanticID($statusID)
	{
		if ($statusID === static::getFinalStatus())
		{
			return Crm\PhaseSemantics::SUCCESS;
		}

		if ($statusID === static::getFinalUnsuccessfulStatus())
		{
			return Crm\PhaseSemantics::FAILURE;
		}

		return (static::getStatusSort($statusID) > static::getFinalStatusSort())
			? Crm\PhaseSemantics::FAILURE : Crm\PhaseSemantics::PROCESS;
	}

	/**
	 * @param $statusID
	 * @return string
	 * @throws Main\NotImplementedException
	 */
	public static function getStatusSemantics($statusID)
	{
		if ($statusID === static::getFinalStatus())
		{
			return 'success';
		}

		if ($statusID ===  static::getFinalUnsuccessfulStatus())
		{
			return 'failure';
		}

		return (static::getStatusSort($statusID) > static::getFinalStatusSort()) ? 'apology' : 'process';
	}

	/**
	 * @param $statusId
	 * @return int
	 */
	protected static function getStatusSort($statusId)
	{
		static $results = [];

		if(isset($results[$statusId]))
		{
			return $results[$statusId];
		}

		$results[$statusId] = -1;
		$statusID = strval($statusId);

		if($statusID !== '')
		{
			/** @var Main\DB\Result $dbRes */
			$dbRes = static::getList(array(
				'select' => ['SORT'],
				'filter' => ['=ID' => $statusId],
			));

			if ($data = $dbRes->fetch())
			{
				$results[$statusId] = $data['SORT'];
			}
		}

		return $results[$statusId];
	}

	/**
	 * @return int
	 */
	public static function getFinalStatusSort()
	{
		return static::getStatusSort(static::getFinalStatus());
	}

	/**
	 * @return array|null
	 */
	public static function getSemanticProcessStatuses()
	{
		static $result = null;

		if ($result !== null)
		{
			return $result;
		}

		$result = [];
		$finalSort = static::getFinalStatusSort();

		/** @var Main\DB\Result $dbRes */
		$dbRes = static::getList([
			'select' => ['ID', 'SORT'],
			'filter' => ['=TYPE' => static::TYPE]
		]);
		while ($status = $dbRes->fetch())
		{
			if ($status['SORT'] < $finalSort)
			{
				$result[] = $status['ID'];
			}
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws Main\NotImplementedException
	 */
	public static function getSemanticInfo()
	{
		return [
			'ID' => static::NAME,
			'NAME' => Main\Localization\Loc::getMessage('CRM_STATUS_TYPE_'.static::NAME),
			'SEMANTIC_INFO' => [
				'START_FIELD' => static::getInitialStatus(),
				'FINAL_SUCCESS_FIELD' => static::getFinalStatus(),
				'FINAL_UNSUCCESS_FIELD' => static::getFinalUnsuccessfulStatus(),
				'FINAL_SORT' => static::getFinalStatusSort()
			]
		];
	}

	/**
	 * @param bool $clearCache
	 *
	 * @return array
	 */
	public static function getListInCrmFormat($clearCache = false)
	{
		static $result = [];

		if (isset($result[static::TYPE]) && !$clearCache)
		{
			return $result[static::TYPE];
		}

		$result[static::TYPE] = [];

		/** @var Main\DB\Result $dbRes */
		$dbRes = static::getList([
			'select' => ['*', 'NAME' => 'Bitrix\Sale\Internals\StatusLangTable:STATUS.NAME'],
			'filter' => [
				'=Bitrix\Sale\Internals\StatusLangTable:STATUS.LID' => LANGUAGE_ID
			],
			'order' => ['SORT' => 'ASC']
		]);

		while ($status = $dbRes->fetch())
		{
			$result[static::TYPE][$status['ID']] = self::convertToCrmFormat($status);
		}

		$statusIdList = array_diff(static::getAllStatuses(), array_keys($result[static::TYPE]));

		if ($statusIdList)
		{
			$dbRes = static::getList([
				'select' => ['*', 'NAME' => 'Bitrix\Sale\Internals\StatusLangTable:STATUS.NAME'],
				'filter' => [
					'@ID' => $statusIdList
				],
				'order' => ['SORT' => 'ASC']
			]);

			while ($status = $dbRes->fetch())
			{
				if (!isset($result[static::TYPE][$status['ID']]))
				{
					$result[static::TYPE][$status['ID']] = self::convertToCrmFormat($status);
				}
			}

			uasort($result[static::TYPE], function ($a, $b) {return ($a['SORT'] < $b['SORT']) ? -1 : 1;});
		}

		Crm\Color\PhaseColorScheme::fillDefaultColors($result[static::TYPE]);

		return $result[static::TYPE];
	}

	/**
	 * @param $status
	 * @return array
	 */
	private static function convertToCrmFormat($status)
	{
		$defaultList = static::getDefaultStatuses();

		$result = [
			'ID' => self::ord($status['ID']),
			'ENTITY_ID' => static::NAME,
			'STATUS_ID' => $status['ID'],
			'NAME' => $status['NAME'],
			'NAME_INIT' => $status['NAME'],
			'SORT' => $status['SORT'],
			'SYSTEM' => 'N',
			'COLOR' => $status['COLOR'],
		];

		if (isset($defaultList[$status['ID']]))
		{
			$result['NAME_INIT'] = $defaultList[$status['ID']]['NAME'];
			$result['SYSTEM'] = 'Y';
		}

		return $result;
	}

	/**
	 * @param $string
	 * @return int
	 */
	private static function ord($string)
	{
		$ord = "";
		$len = mb_strlen($string);
		if ($len <= 0)
		{
			return 0;
		}

		for ($i = 0; $i < $len; $i++)
		{
			$ord .= ord($string[$i]);
		}

		return (int)$ord;
	}
}
