<?php
namespace Bitrix\Tasks\CheckList\Internals;

/**
 * Class CompositeTreeItem
 *
 * @package Bitrix\Tasks\CheckList\Internals
 */
abstract class CompositeTreeItem extends TreeItem
{
	private $descendants = [];

	/**
	 * @param TreeItem $item
	 */
	public function add(TreeItem $item)
	{
		$this->descendants[$item->getNodeId()] = $item;
		$item->setParent($this);
	}

	/**
	 * @param TreeItem $item
	 */
	public function remove(TreeItem $item)
	{
		$id = $item->getNodeId();

		if (isset($this->descendants[$id]))
		{
			unset($this->descendants[$id]);
		}
	}

	/**
	 * @param $nodeId
	 * @return $this|TreeItem|null
	 */
	public function findChild($nodeId)
	{
		if ($this->getNodeId() == $nodeId)
		{
			return $this;
		}

		foreach ($this->descendants as $descendant)
		{
			$found = $descendant->findChild($nodeId);
			if ($found !== null)
			{
				return $found;
			}
		}

		return null;
	}

	/**
	 * @return bool
	 */
	public function isComposite()
	{
		return !empty($this->descendants);
	}

	/**
	 * @return array
	 */
	public function getDescendants()
	{
		return $this->descendants;
	}

	/**
	 * @return int
	 */
	public function getDescendantsCount()
	{
		return count($this->descendants);
	}
}