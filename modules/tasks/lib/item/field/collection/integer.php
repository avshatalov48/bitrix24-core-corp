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

namespace Bitrix\Tasks\Item\Field\Collection;

use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\Util;

class Integer extends \Bitrix\Tasks\Item\Field\Collection\Scalar
{
	protected function clearArray($value)
	{
		// 0 is also allowed!
		return array_values(array_unique(array_map('intval', $value)));
	}
}