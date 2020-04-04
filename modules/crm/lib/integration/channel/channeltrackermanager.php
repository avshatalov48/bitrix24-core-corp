<?php
namespace Bitrix\Crm\Integration\Channel;

class ChannelTrackerManager
{
	/** @var IChannelTracker|null */
	private static $trackers = null;
	/** @var IChannelInfo[]|null */
	private static $infos = null;
	/** @var IChannelGroupInfo[]|null */
	private static $groupInfos = null;
	/**
	 * Get all registered Channel Trackers
	 * @return IChannelTracker[]
	 */
	public static function getAllTrackers()
	{
		if(self::$trackers === null)
		{
			self::$trackers = array();

			EmailTracker::registerInstance(self::$trackers);
			VoxImplantTracker::registerInstance(self::$trackers);
			IMOpenLineTracker::registerInstance(self::$trackers);
			WebFormTracker::registerInstance(self::$trackers);
//			SiteButtonTracker::registerInstance(self::$trackers);
			ExternalTracker::registerInstance(self::$trackers);
//			LeadImportTracker::registerInstance(self::$trackers);
		}
		return self::$trackers;
	}
	/**
	 * Get Channel Tracker by Type ID.
	 * @param int $typeID Channel Type ID.
	 * @return IChannelTracker|null
	 */
	public static function getTrackerByType($typeID)
	{
		$trackers = self::getAllTrackers();
		return isset($trackers[$typeID]) ? $trackers[$typeID] : null;
	}
	/**
	 * Initialize enabled trackers for using by user.
	 * @return void
	 */
	public static function initializeUserContext()
	{
		foreach(self::getAllTrackers() as $tracker)
		{
			if($tracker->isEnabled())
			{
				$tracker->initializeUserContext();
			}
		}
	}
	/**
	 * Get Channel Info items.
	 * @return IChannelInfo[]
	 */
	public static function getInfos()
	{
		if(self::$infos === null)
		{
			self::$infos = array();
			foreach(self::getAllTrackers() as $tracker)
			{
				self::$infos = array_merge(self::$infos, $tracker->prepareChannelInfos());
			}
			@usort(self::$infos, array('\Bitrix\Crm\Integration\Channel\ChannelInfo', 'compareBySort'));
		}
		return self::$infos;
	}
	/**
	 * Get Channel Group Info items.
	 * @return IChannelGroupInfo[]
	 */
	public static function getGroupInfos()
	{
		if(self::$groupInfos === null)
		{
			/** @var IChannelGroupInfo[] $infos */
			$infos = array();
			foreach(self::getAllTrackers() as $tracker)
			{
				$infos = array_merge($infos, $tracker->prepareChannelGroupInfos());
			}

			ChannelGroupInfo::sort($infos);

			self::$groupInfos = array();
			foreach($infos as $info)
			{
				/** @var IChannelGroupInfo $info */
				self::$groupInfos[$info->getID()] = $info;
			}
		}
		return self::$groupInfos;
	}

	/**
	 * Get group info by ID.
	 * @param string $groupID Group ID.
	 * @return IChannelGroupInfo|null
	 */
	public static function getGroupInfo($groupID)
	{
		$infos = self::getGroupInfos();
		return isset($infos[$groupID]) ? $infos[$groupID] : null;
	}
	/**
	 * Get Channel Key.
	 * @param int $typeID Channel Type ID.
	 * @param array|null $params Channel Params.
	 * @return string
	 */
	public static function prepareChannelKey($typeID, array $params = null)
	{
		return ChannelInfo::prepareKey($typeID, $params);
	}
	/**
	 * Get Channel Caption.
	 * @param int $typeID Channel Type ID.
	 * @param array|null $params Channel Params.
	 * @return string
	 */
	public static function prepareChannelCaption($typeID, array $params = null)
	{
		$tracker = self::getTrackerByType($typeID);
		return $tracker !== null ? $tracker->prepareCaption($params) : '';
	}
}