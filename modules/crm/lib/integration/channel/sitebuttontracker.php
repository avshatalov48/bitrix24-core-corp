<?php
namespace Bitrix\Crm\Integration\Channel;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\SiteButton;
use Bitrix\Crm\SiteButton\Preset;

Loc::loadMessages(__FILE__);

class SiteButtonTracker extends ChannelTracker
{
	const GROUP_ID = 'SITEBUTTON';

	/** @var SiteButtonTracker|null  */
	private static $instance = null;
	public function __construct()
	{
		parent::__construct(ChannelType::SITEBUTTON);
	}
	/**
	 * Add instance of this manager to collection
	 * @param array $instances Destination collection.
	 */
	public static function registerInstance(array &$instances)
	{
		$instance = self::getInstance();
		$instances[$instance->getTypeID()] = $instance;
	}
	/**
	 * Get manager instance
	 * @return SiteButtonTracker
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new SiteButtonTracker();
		}
		return self::$instance;
	}
	protected static function checkInstalledPresets()
	{
		if (Preset::checkVersion())
		{
			$preset = new Preset();
			$preset->install();

		}
	}
	//region IChannelTracker
	/**
	 * Check if channel is enabled.
	 * @return bool
	 */
	public function isEnabled()
	{
		return true;
	}
	/**
	 * Initialize tracker for using by user.
	 * @return void
	 */
	public function initializeUserContext()
	{
		self::checkInstalledPresets();
	}
	/**
	 * Check if channel is in use.
	 * @param array $params Array of channel parameters.
	 * @return bool
	 */
	protected function checkPossibilityOfUsing(array $params = null)
	{
		$dbResult = SiteButton\Internals\ButtonTable::getList(
			array(
				'select' => array('ID'),
				'filter' => array('=ACTIVE' => 'Y'),
				'limit' => 1
			)
		);
		return is_array($dbResult->fetch());
	}
	/**
	 * Check if current user has permission to configure channel.
	 * @param array $params Array of channel parameters.
	 * @return bool
	 */
	public function checkConfigurationPermission(array $params = null)
	{
		return SiteButton\Manager::checkReadPermission();
	}
	/**
	 * Get channel page URL.
	 * @param array $params Array of channel parameters.
	 * @return string
	 */
	public function getUrl(array $params = null)
	{
		return SiteButton\Manager::getUrl();
	}
	/**
	 * Create channel group info items.
	 * @return IChannelGroupInfo[]
	 */
	public function prepareChannelGroupInfos()
	{
		return array(
			self::GROUP_ID => new ChannelGroupInfo(
				$this,
				self::GROUP_ID,
				Loc::getMessage('SITEBUTTON_CHANNEL'),
				11000,
				false
			)
		);
	}
	/**
	 * Create channel info items.
	 * @return IChannelInfo[]
	 */
	public function prepareChannelInfos()
	{
		return array(
			new ChannelInfo(
				$this,
				ChannelType::SITEBUTTON,
				Loc::getMessage('SITEBUTTON_CHANNEL'),
				'',
				'',
				0,
				self::GROUP_ID
			)
		);
	}
	/**
	 * Prepare channel caption
	 * @param array|null $params Array of channel parameters.
	 * @return string
	 */
	public function prepareCaption(array $params = null)
	{
		return Loc::getMessage('SITEBUTTON_CHANNEL');
	}
	//endregion
}