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

namespace Bitrix\Tasks\Dispatcher\PublicAction\Integration;

use Bitrix\Tasks;
use Bitrix\Tasks\Util\Result;
use \Bitrix\Tasks\Dispatcher\PublicAction;

final class Intranet extends PublicAction
{
	public static function absence(array $userIds)
	{
		$list = \Bitrix\Tasks\Util\User::isAbsence($userIds);

		return $list;
	}
}