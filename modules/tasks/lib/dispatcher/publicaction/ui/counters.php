<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 *
 * Each method you put here you`ll be able to call as ENTITY_NAME.METHOD_NAME via AJAX and\or REST, so be careful.
 */

namespace Bitrix\Tasks\Dispatcher\PublicAction\Ui;

use Bitrix\Tasks\Util\Result;
use Bitrix\Tasks\Internals;

final class Counters extends \Bitrix\Tasks\Dispatcher\PublicAction
{
	/**
	 * @param $userId
	 * @param int $groupId
	 * @param string $type
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 *
	 * Access rights willbe check into the Counter
	 */
	public function get($userId, $groupId = 0, $type = 'view_all')
	{
		$result = new Result();

		$type = ($type ?: 'view_all');
		$counterInstance = Internals\Counter::getInstance((int) $userId);
		$result->setData($counterInstance->getCounters($type, (int) $groupId));

		return $result;
	}
}