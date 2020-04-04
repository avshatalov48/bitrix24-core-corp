<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 * @internal
 */

namespace Bitrix\Tasks\Item\Field;

use Bitrix\Tasks\Util\Type\DateTime;

class Date extends Scalar
{
	public function translateValueFromOutside($value, $key, $item)
	{
		return DateTime::createFromObjectOrString($value);
	}
}