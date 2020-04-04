<?php
namespace Bitrix\Crm\Integration\Channel;
class ChannelGroupInfo implements IChannelGroupInfo
{
	/** @var IChannelTracker|null */
	protected $tracker = null;
	/** @var int */
	protected $ID = 0;
	/** @var null|string */
	protected $parentID = null;
	/** @var string */
	protected $caption = '';
	/** @var int */
	protected $sort = 0;
	/** @var bool */
	protected $isDisplayable = true;
	/** @var string */
	protected $url = '';

	public function __construct($tracker, $ID, $caption = '', $sort = 1000, $isDisplayable = true, $url = '')
	{
		$this->tracker = $tracker;
		$this->ID = $ID;
		$this->caption = $caption;
		$this->sort = (int)$sort;
		$this->isDisplayable = (bool)$isDisplayable;
		$this->url = $url;
	}

	/**
	 * Get channel group ID.
	 * @return string
	 */
	public function getID()
	{
		return $this->ID;
	}
	/**
	 * Get channel group caption.
	 * @return string
	 */
	public function getCaption()
	{
		return $this->caption;
	}
	/**
	 * Get channel group gort.
	 * @return int
	 */
	public function getSort()
	{
		return $this->sort;
	}
	/**
	 * Check if channel group is displayable
	 * Items of not displayable group will be displayed at top-level as non-grouped.
	 * @return bool
	 */
	public function isDisplayable()
	{
		return $this->isDisplayable;
	}
	/**
	 * Get channel group URL.
	 * @return string
	 */
	public function getUrl()
	{
		return $this->url;
	}
	/**
	 * Sets parent group ID.
	 * @param string $parent
	 * @return void
	 */
	public function setParentID($parent)
	{
		$this->parentID = $parent;
	}
	/**
	 * Returns parent group ID.
	 * @return null|string
	 */
	public function getParentID()
	{
		return $this->parentID;
	}

	public static function sort(array $items)
	{
		usort($items, array('\Bitrix\Crm\Integration\Channel\ChannelGroupInfo', 'compareBySort'));
	}

	/**
	 * Compare
	 * @param IChannelGroupInfo $a
	 * @param IChannelGroupInfo $b
	 * @return int
	 */
	public static function compareBySort(IChannelGroupInfo $a, IChannelGroupInfo $b)
	{
		return ($a->getSort() - $b->getSort());
	}
}