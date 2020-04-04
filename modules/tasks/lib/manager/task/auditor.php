<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 */

namespace Bitrix\Tasks\Manager\Task;

final class Auditor extends \Bitrix\Tasks\Manager\Task\Member
{
	public static function getLegacyFieldName()
	{
		return 'AUDITORS';
	}

	public static function getIsMultiple()
	{
		return true;
	}
}