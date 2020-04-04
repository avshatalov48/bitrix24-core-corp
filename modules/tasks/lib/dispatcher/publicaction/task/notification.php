<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 * 
 * @access private
 *
 * Each method you put here you`ll be able to call as ENTITY_NAME.METHOD_NAME via AJAX and\or REST, so be careful.
 */

namespace Bitrix\Tasks\Dispatcher\PublicAction\Task;

//use \Bitrix\Tasks\Util\Error\Collection;

final class Notification extends \Bitrix\Tasks\Dispatcher\RestrictedAction
{
	/**
	 * Deliver all notification being throttled
	 */
	public function throttleRelease()
	{
		\CTaskNotifications::throttleRelease();
	}
}