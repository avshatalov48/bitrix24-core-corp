<?php
namespace Bitrix\Crm\Integration\Channel;

class ChannelTrackerAggregator
{
	/** @var IChannelTracker|null  */
	private static $managers = null;

	/**
	 * Get all registered channel trackers
	 * @return IChannelTracker[]
	 */
	public static function getAllTrackers()
	{
		if(self::$managers === null)
		{
			self::$managers = array();

			EmailTracker::registerInstance(self::$managers);
			VoxImplantTracker::registerInstance(self::$managers);
			IMOpenLineTracker::registerInstance(self::$managers);
			WebFormTracker::registerInstance(self::$managers);
			SiteButtonTracker::registerInstance(self::$managers);
			ExternalTracker::registerInstance(self::$managers);
			LeadImportTracker::registerInstance(self::$managers);
		}
		return self::$managers;
	}
	/**
	 * Get channel info items.
	 * @return IChannelInfo[]
	 */
	public static function getInfos()
	{
		$results = array();
		foreach(self::getAllTrackers() as $tracker)
		{
			$results = array_merge($results, $tracker->prepareChannelInfos());
		}
		return $results;
	}
	public static function prepareChannelKey($typeID, array $params = null)
	{
		return ChannelInfo::prepareKey($typeID, $params);
	}
	public static function prepareChannelCaption($typeID, array $params = null)
	{
		$trackers = self::getAllTrackers();
		return isset($trackers[$typeID]) ? $trackers[$typeID]->prepareCaption($params) : '';
	}
}