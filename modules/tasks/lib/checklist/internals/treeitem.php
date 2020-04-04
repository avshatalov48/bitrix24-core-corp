<?php
namespace Bitrix\Tasks\CheckList\Internals;

/**
 * Class TreeItem
 *
 * @package Bitrix\Tasks\CheckList\Internals
 */
abstract class TreeItem
{
	private $nodeId;
	private $parent;

	/**
	 * TreeItem constructor.
	 *
	 * @param $nodeId
	 */
	public function __construct($nodeId)
	{
		$this->setNodeId($nodeId);
	}

	/**
	 * @param $nodeId
	 * @return TreeItem|null
	 */
	public function findChild($nodeId)
	{
		return ($this->nodeId == $nodeId? $this : null);
	}

	/**
	 * @return mixed
	 */
	public function getNodeId()
	{
		return $this->nodeId;
	}

	/**
	 * @param mixed $nodeId
	 */
	public function setNodeId($nodeId)
	{
		$this->nodeId = $nodeId;
	}

	/**
	 * @return mixed
	 */
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * @param TreeItem $parent
	 */
	public function setParent(TreeItem $parent)
	{
		$this->parent = $parent;
	}
}