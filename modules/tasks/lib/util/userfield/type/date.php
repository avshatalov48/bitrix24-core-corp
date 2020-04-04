<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 */

namespace Bitrix\Tasks\Util\UserField\Type;

final class Date extends DateTime
{
	protected static function getFormatName()
	{
		return 'SHORT';
	}

	protected static function getFormatValue()
	{
		return 'YYYY-MM-DD';
	}
}