<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access privatees
 */

namespace Bitrix\Tasks\Manager\Task;

final class Originator extends \Bitrix\Tasks\Manager\Task\Member
{
	public static function getLegacyFieldName()
	{
		return 'CREATED_BY';
	}
}