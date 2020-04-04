<?php
namespace Bitrix\Crm\Integration\Channel;
class ChannelInfo implements IChannelInfo
{
	/** @var IChannelTracker|null */
	protected $tracker = null;
	/** @var int */
	protected $typeID = 0;
	/** @var string */
	protected $origin = '';
	/** @var string */
	protected $component = '';
	/** @var string */
	protected $caption = '';
	/** @var int */
	protected $sort = 0;
	/** @var string */
	protected $groupID = '';

	public function __construct($tracker, $typeID, $caption = '', $origin = '', $component = '', $sort = 1000, $groupID = '')
	{
		$this->tracker = $tracker;
		$this->typeID = $typeID;
		$this->caption = $caption;
		$this->origin = $origin;
		$this->component = $component;
		$this->sort = (int)$sort;
		$this->groupID = $groupID;
	}
	//region IChannelInfo
	/**
	 * Get Channel Type ID.
	 * @return ChannelType
	 */
	public function getChannelTypeID()
	{
		return $this->typeID;
	}
	/**
	 * Get channel caption.
	 * @return string
	 */
	public function getCaption()
	{
		return $this->caption;
	}
	/**
	 * Get sorting
	 * @return int
	 */
	public function getSort()
	{
		return $this->sort;
	}
	/**
	 * Get group ID
	 * @return string
	 */
	public function getGroupID()
	{
		return $this->groupID;
	}
	/**
	 * Get Channel Origin (Identifier, GUID or other sign).
	 * @return string
	 */
	public function getChannelOrigin()
	{
		return $this->origin;
	}
	/**
	 * Get Channel Component.
	 * @return string
	 */
	public function getChannelComponent()
	{
		return $this->component;
	}
	/**
	 * Check if channel enabled.
	 * @return bool
	 */
	public function isEnabled()
	{
		return $this->tracker->isEnabled();
	}
	/**
	 * Check if channel in use.
	 * @return bool
	 */
	public function isInUse()
	{
		return $this->tracker->isInUse(
			array(
				'ORIGIN_ID' => $this->origin,
				'COMPONENT_ID' => $this->component
			)
		);
	}
	/**
	 * Check if current user has permission to configure this channel.
	 * @return bool
	 */
	public function checkConfigurationPermission()
	{
		return $this->tracker->checkConfigurationPermission(
			array(
				'ORIGIN_ID' => $this->origin,
				'COMPONENT_ID' => $this->component
			)
		);
	}
	/**
	 * Get channel URL.
	 * @return string
	 */
	public function getConfigurationUrl()
	{
		return $this->tracker->getUrl(
			array(
				'ORIGIN_ID' => $this->origin,
				'COMPONENT_ID' => $this->component
			)
		);
	}
	/**
	 * Get channel unique key.
	 * @return string
	 */
	public function getKey()
	{
		return self::prepareKey(
			$this->typeID,
			array(
				'ORIGIN_ID' => $this->origin,
				'COMPONENT_ID' => $this->component
			)
		);
	}
	//endregion
	/**
	 * Compare items by sort field
	 * @param ChannelInfo $first
	 * @param ChannelInfo $second
	 * @return int
	 */
	public static function compareBySort(ChannelInfo $first, ChannelInfo $second)
	{
		$firstSort = $first->sort;
		$firstGroupSort = -1;
		if($first->groupID !== '')
		{
			$firstGroup = ChannelTrackerManager::getGroupInfo($first->groupID);
			if($firstGroup !== null)
			{
				$firstGroupSort = $firstGroup->getSort();
			}
		}

		$secondSort = $second->sort;
		$secondGroupSort = -1;
		if($second->groupID !== '')
		{
			$secondGroup = ChannelTrackerManager::getGroupInfo($second->groupID);
			if($secondGroup !== null)
			{
				$secondGroupSort = $secondGroup->getSort();
			}
		}

		if($firstGroupSort > 0 && $secondGroupSort > 0 && $firstGroupSort !== $secondGroupSort)
		{
			return ($firstGroupSort - $secondGroupSort);
		}

		if($firstGroupSort > 0)
		{
			$firstSort += $firstGroupSort;
		}

		if($secondGroupSort > 0)
		{
			$secondSort += $secondGroupSort;
		}

		return ($firstSort - $secondSort);
	}
	/**
	 * Get channel unique key.
	 * @param int $typeID Channel Type ID.
	 * @param array|null $params Channel Params.
	 * @return string
	 */
	public static function prepareKey($typeID, array $params = null)
	{
		$typeName = ChannelType::resolveName($typeID);
		if($typeName === '')
		{
			return '';
		}

		if($params === null || empty($params))
		{
			return $typeName;
		}

		$pieces = array($typeName);

		$originID = isset($params['ORIGIN_ID']) ? $params['ORIGIN_ID'] : '';
		if($originID !== '')
		{
			$pieces[] = $originID;
		}

		$componentID = isset($params['COMPONENT_ID']) ? $params['COMPONENT_ID'] : '';
		if($componentID !== '')
		{
			$pieces[] = $componentID;
		}

		return implode('|', $pieces);
	}
	/**
	 * Externalize
	 * @return array
	 */
	public function externalize()
	{
		return array(
			'typeID' => $this->typeID,
			'origin' => $this->origin,
			'component' => $this->component,
			'caption' => $this->caption,
			'sort' => $this->sort,
			'groupID' => $this->groupID
		);
	}
}