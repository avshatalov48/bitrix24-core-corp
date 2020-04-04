<?php
namespace Bitrix\Crm\Integration\Channel;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

class LeadImportTracker extends ChannelTracker
{
	const GROUP_ID = 'LEAD_IMPORT';
	/** @var LeadImportTracker|null  */
	private static $instance = null;
	public function __construct()
	{
		parent::__construct(ChannelType::LEAD_IMPORT);
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
	 * @return LeadImportTracker
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new LeadImportTracker();
		}
		return self::$instance;
	}
	//region IChannelTracker
	/**
	 * Check if Lead import is enabled.
	 * @return bool
	 */
	public function isEnabled()
	{
		return true;
	}
	/**
	 * Check if Lead import is in use.
	 * @param array $params Array of channel parameters.
	 * @return bool
	 */
	protected function checkPossibilityOfUsing(array $params = null)
	{
		return true;
	}
	/**
	 * Check if current user has permission to configure Lead import.
	 * @param array $params Array of channel parameters.
	 * @return bool
	 */
	public function checkConfigurationPermission(array $params = null)
	{
		return \CCrmLead::CheckImportPermission();
	}
	/**
	 * Get Lead import page URL.
	 * @param array $params Array of channel parameters.
	 * @return string
	 * @throws Main\ArgumentNullException
	 */
	public function getUrl(array $params = null)
	{
		return Option::get('crm', 'path_to_lead_import', '/crm/lead/import/');
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
				Loc::getMessage('LEAD_IMPORT_CHANNEL'),
				15000,
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
				ChannelType::LEAD_IMPORT,
				Loc::getMessage('LEAD_IMPORT_CHANNEL'),
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
		return Loc::getMessage('LEAD_IMPORT_CHANNEL');
	}
	//endregion
}