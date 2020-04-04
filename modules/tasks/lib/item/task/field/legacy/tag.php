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

namespace Bitrix\Tasks\Item\Task\Field\Legacy;

use Bitrix\Tasks\Internals\Task\TagTable;
use Bitrix\Tasks\Item\Result;
use Bitrix\Tasks\Util\Collection;

class Tag extends \Bitrix\Tasks\Item\Field\Collection
{
	/**
	 * @param string $key
	 * @param \Bitrix\Tasks\Item $item
	 * @param array $parameters
	 * @return array|mixed
	 */
	public function getValue($key, $item, array $parameters = array())
	{
		$result = array();
		foreach($item['SE_TAG'] as $tag)
		{
			$result[] = $tag['NAME'];
		}

		return $this->createValue($result, $key, $item);
	}

	/**
	 * @param mixed $value
	 * @param string $key
	 * @param \Bitrix\Tasks\Item $item
	 * @param array $parameters
	 * @return mixed
	 */
	public function setValue($value, $key, $item, array $parameters = array())
	{
		$item['SE_TAG'] = $value;
		$item->setFieldModified('SE_TAG');

		return $value;
	}
}