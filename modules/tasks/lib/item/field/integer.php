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

class Integer extends Scalar
{
	public function translateValueFromOutside($value, $key, $item) // from external level to business level
	{
		return $value === null ? null : intval($value);
	}
}