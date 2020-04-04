<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Item;

use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\Item;

class Collection extends \Bitrix\Tasks\Util\Collection
{
	private $idIndex = null;

	/**
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return Item
	 */
	protected static function getItemClass()
	{
		throw new \Bitrix\Main\NotImplementedException('No default item class');
	}

	protected function onChange()
	{
		$this->idIndex = null;
		parent::onChange();
	}

	public function getItemById($id)
	{
		$index = $this->getIdCache();

		return $index[$id][1];
	}

	public function getItemIndexById($id)
	{
		$index = $this->getIdCache();

		return $index[$id][0];
	}

	public function containsId($id)
	{
		return array_key_exists($id, $this->getIdCache());
	}

	private function getIdCache()
	{
		if($this->idIndex === null)
		{
			$this->idIndex = array();
			foreach($this->values as $k => $item)
			{
				$itemId = $item->getId();
				if($itemId)
				{
					$this->idIndex[$itemId] = array($k, $item);
				}
			}
		}

		return $this->idIndex;
	}
}