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

namespace Bitrix\Tasks\Item\Task\Template\Field\Legacy;

use Bitrix\Tasks\Util\Type;

class Tag extends \Bitrix\Tasks\Item\Field\Collection
{
	public function getValue($key, $item, array $parameters = array())
	{
		$result = array();
		foreach($item['SE_TAG'] as $tag)
		{
			$result[] = $tag['NAME'];
		}

		return $this->createValue($result, $key, $item);
	}

	public function setValue($value, $key, $item)
	{
		$item['SE_TAG'] = $value;
		$item->setFieldModified('SE_TAG');
	}
}