<?php

namespace Bitrix\Disk\Bitrix24Disk;
use Bitrix\Main\Type\DateTime;

/**
 * Class TreeNode
 * @package Bitrix\Disk\Bitrix24Disk
 * @internal
 */
final class TreeNode
{
	const TREE_SYMLINK_PREFIX = 's';

	/** @var int */
	public $id;
	/** @var string */
	public $name;
	/** @var int */
	public $parentId;
	/** @var int */
	public $realObjectId;
	/**
	 * The field will be filled only for symbolic links.
	 *
	 * @var DateTime
	 */
	public $createDate;

	/**
	 * @var bool
	 * @internal
	 */
	public $__isRoot = false;
	/**
	 * @var bool
	 * @internal
	 */
	public $__isReplica = false;
	/**
	 * @var string
	 * @internal
	 */
	public $__path;
	/**
	 * @var  TreeNode[]
	 * @internal
	 */
	public $__tree;
	/**
	 * @var TreeNode
	 * @internal
	 */
	public $__link;

	/**
	 * @var array
	 * @internal
	 */
	public static $__pathNodes = array();

	/**
	 * TreeNode constructor.
	 * @param int $id Id of node.
	 * @param string $name Name of node.
	 * @param int $parentId Parent id of node.
	 * @param int $realObjectId Real id of node.
	 */
	public function __construct($id, $name, $parentId, $realObjectId)
	{
		$this->id = $id;
		$this->name = $name;
		$this->parentId = $parentId;
		$this->realObjectId = $realObjectId;
	}

	/**
	 * Marks node as root. Sets path to '/'.
	 *
	 * @return void
	 */
	public function setAsRoot()
	{
		$this->__isRoot = true;
		$this->__path = '/';
	}

	/**
	 * Tells if node is link.
	 *
	 * @return bool
	 */
	public function isLink()
	{
		return $this->id != $this->realObjectId;
	}

	/**
	 * Tells if node is root.
	 *
	 * @return bool
	 */
	private function isRoot()
	{
		return $this->parentId === null && $this->__isRoot;
	}

	/**
	 * Tells if node is replica in this tree.
	 * It means, that there is the another one node which has the same realObjectId.
	 *
	 * @return bool
	 */
	public function isReplica()
	{
		return $this->__isReplica;
	}

	/**
	 * Marks node as replica.
	 * It means, that there is the another one node which has the same realObjectId.
	 *
	 * @return void
	 */
	public function markAsReplica()
	{
		$this->__isReplica = true;
	}

	/**
	 * Returns path of object.
	 * Tries to find path without first link. It may be useful when there are two folder in storage.
	 * One of them is accessible by symlink another one is accessible by real folder, but under symlink.
	 * @return null|string
	 */
	public function getPathWithoutFirstLink()
	{
		$path = null;
		if(!$this->parentId && !$path)
		{
			return null;
		}

		if(isset($this->__tree[$this->parentId]))
		{
			if($this->__tree[$this->parentId]->parentId == $this->id)
			{
				return null;
			}

			$path = $this->__tree[$this->parentId]->getPath();
		}

		if(!$path && isset($this->__tree[self::TREE_SYMLINK_PREFIX . $this->parentId]))
		{
			$path = $this->__tree[self::TREE_SYMLINK_PREFIX . $this->parentId]->getPath();
		}

		if($path === null)
		{
			return null;
		}

		return $path . $this->name . '/';
	}

	/**
	 * Returns path to node.
	 *
	 * @return null|string
	 */
	public function getPath()
	{
		if(isset($this->__path))
		{
			return $this->__path;
		}

		$path = null;
		if(isset(self::$__pathNodes[$this->id]))
		{
			return null;
		}

		self::$__pathNodes[$this->id] = $this->id;

		if($this->__link)
		{
			$this->__path = $this->__link->getPath();

			return $this->__path;
		}

		if(!$this->parentId && !$path)
		{
			return null;
		}

		if(isset($this->__tree[$this->parentId]))
		{
			if($this->__tree[$this->parentId]->parentId == $this->id)
			{
				return null;
			}

			$path = $this->__tree[$this->parentId]->getPath();
		}

		if(!$path && isset($this->__tree[self::TREE_SYMLINK_PREFIX . $this->parentId]))
		{
			$path = $this->__tree[self::TREE_SYMLINK_PREFIX . $this->parentId]->getPath();
		}

		if($path === null)
		{
			return null;
		}

		$this->__path = $path . $this->name . '/';

		return $this->__path;
	}

	private function removeLastComponent($path)
	{
		if($path === '/')
		{
			return '/';
		}

		return substr($path, 0, strrpos($path, '/', -2))?: '/';
	}

	/**
	 * Sets link on the tree, which contains list of nodes (@see TreeNode).
	 * The tree is used for building symbolic path (@see TreeNode::getPath()).
	 *
	 * @param array $tree
	 * @return void
	 */
	public function setTree(array &$tree)
	{
		$this->__tree = &$tree;
	}

	/**
	 * Sets create date. The field will be filled only for symbolic links.
	 *
	 * @param DateTime $createDate Create date.
	 * @return $this
	 */
	public function setCreateDate($createDate)
	{
		$this->createDate = $createDate;

		return $this;
	}

	/**
	 * Sets link on the current node.
	 *
	 * @param TreeNode $link
	 * @return $this
	 */
	public function setLink(TreeNode $link)
	{
		$this->__link = $link;

		return $this;
	}
}