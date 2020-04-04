<?php

namespace Bitrix\ImOpenLines\Log;

use Bitrix\Imopenlines\Model\ExecLogTable;
use Bitrix\Main\Type\DateTime;

class ExecLog
{
	/**
	 * @param string $execFunction
	 * @param bool $isSuccess
	 *
	 * @return \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function setExecFunction($execFunction, $isSuccess = true)
	{
		$execCollection = self::getExecByFunction($execFunction);
		$fields = array(
			'LAST_EXEC_TIME' => new DateTime(),
			'IS_SUCCESS' => $isSuccess ? 'Y' : 'N'
		);

		if ($exec = $execCollection->Fetch())
		{
			$result = ExecLogTable::update($exec['ID'], $fields);
		}
		else
		{
			$fields['EXEC_FUNCTION'] = $execFunction;
			$result = ExecLogTable::add($fields);
		}

		return $result;
	}

	/**
	 * @param string $execFunction
	 *
	 * @return \Bitrix\Main\ORM\Query\Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getExecByFunction($execFunction)
	{
		$filter = array('=EXEC_FUNCTION' => $execFunction);

		$result = ExecLogTable::getList(
			array(
				'order' => array('LAST_EXEC_TIME' => 'DESC'),
				'filter' => $filter,
				'limit' => 1,
				'cache' => array(
					'ttl' => 86400
				)
			)
		);

		return $result;
	}

	/**
	 * Check that correct time of function exec has come
	 *
	 * @param $execFunction
	 * @param int $execPeriod
	 *
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function isTimeToExec($execFunction, $execPeriod = 86400)
	{
		$result = true;
		$exec = self::getExecByFunction($execFunction)->Fetch();

		if (!empty($exec['LAST_EXEC_TIME']))
		{
			$datetime = new DateTime($exec['LAST_EXEC_TIME']);
			$result = (time() - $datetime->getTimestamp() >= $execPeriod);
		}

		return $result;
	}
}