<?
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
	public function get($userId, $groupId = 0, $type = 'view_all')
	{
		$result = new Result();

		$counterInstance = Internals\Counter::getInstance($userId, $groupId);

		$result->setData($counterInstance->getCounters($type));

		return $result;
	}
}