<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 *
 * @access private
 */

namespace Bitrix\Tasks\Item;

class Result extends \Bitrix\Tasks\Util\Result
{
	protected $instance = null;

	/**
	 * @param \Bitrix\Tasks\Item $instance
	 */
	public function setInstance($instance)
	{
		if(is_object($instance))
		{
			$this->instance = $instance;
		}
	}

	/**
	 * @return \Bitrix\Tasks\Item
	 */
	public function getInstance()
	{
		return $this->instance;
	}
}