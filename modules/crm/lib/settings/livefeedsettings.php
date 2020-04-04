<?php
namespace Bitrix\Crm\Settings;
use Bitrix\Main;
class LiveFeedSettings
{
	/** @var LiveFeedSettings  */
	private static $current = null;
	/** @var BooleanSetting  */
	private $enableLiveFeedMerge = null;

	function __construct()
	{
		$this->enableLiveFeedMerge = new BooleanSetting('enable_livefeed_merge', false);
	}
	/**
	 * @return LiveFeedSettings
	 */
	public static function getCurrent()
	{
		if(self::$current === null)
		{
			self::$current = new LiveFeedSettings();
		}
		return self::$current;
	}
	/**
	 * @return bool
	 */
	public function isLiveFeedMergeEnabled()
	{
		return $this->enableLiveFeedMerge->get();
	}
	/**
	 * @param bool $enabled Enabled Flag
	 * @return void
	 */
	public function enableLiveFeedMerge($enabled)
	{
		$this->enableLiveFeedMerge->set($enabled);
	}
}