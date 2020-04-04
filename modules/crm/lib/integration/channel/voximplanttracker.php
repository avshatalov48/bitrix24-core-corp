<?php
namespace Bitrix\Crm\Integration\Channel;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Voximplant;

Loc::loadMessages(__FILE__);

class VoxImplantTracker extends ChannelTracker
{
	const GROUP_ID = 'VOXIMPLANT';

	/** @var VoxImplantTracker|null  */
	private static $instance = null;
	/** @var bool|null  */
	private $isEnabled = null;
	public function __construct()
	{
		parent::__construct(ChannelType::VOXIMPLANT);
	}
	/**
	 * Get manager instance
	 * @return VoxImplantTracker
	 */
	public static function getInstance()
	{
		if(self::$instance !== null)
		{
			return self::$instance;
		}
		return (self::$instance = new VoxImplantTracker());
	}
	/**
	 * Add instance of this manager to collection
	 * @param array $instances Destination collection.
	 */
	public static function registerInstance(array &$instances)
	{
		$instance = self::getInstance();
		if ($instance->isEnabled())
			$instances[$instance->getTypeID()] = $instance;
	}
	//region IChannelTracker
	/**
	 * Check if current manager enabled.
	 * @return bool
	 */
	public function isEnabled()
	{
		if($this->isEnabled === null)
		{
			$this->isEnabled = ModuleManager::isModuleInstalled('voximplant')
				&& Loader::includeModule('voximplant');
		}
		return $this->isEnabled;
	}
	/**
	 * Check if telephony in use.
	 * @param array $params Array of channel parameters.
	 * @return bool
	 * @throws Main\LoaderException
	 */
	protected function checkPossibilityOfUsing(array $params = null)
	{
		return $this->isEnabled() && \CVoxImplantMain::hasCalls();
	}

	/**
	 * Get service URL.
	 * @param array $params Array of channel parameters.
	 * @return string
	 */
	public function getUrl(array $params = null)
	{
		return $this->isEnabled() ? \CVoxImplantMain::GetPublicFolder() : '';
	}
	/**
	 * Check if current user has permission to configure telephony.
	 * @param array $params Array of channel parameters.
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public function checkConfigurationPermission(array $params = null)
	{
		return $this->isEnabled() && Voximplant\Security\Permissions::createWithCurrentUser()->canModifyLines();
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
				Loc::getMessage('VOXIMPLANT_CHANNEL'),
				0,
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
		if(!$this->isEnabled())
		{
			return array();
		}

		$sort = 1;
		$results = array(
				new ChannelInfo(
					$this,
					ChannelType::VOXIMPLANT,
					Loc::getMessage("VOXIMPLANT_UNKNOWN_NUMBER"),
					'',
					'',
					$sort++,
					self::GROUP_ID
				)
		);
		$items = \CVoxImplantConfig::GetPortalNumbers(true, true);
		foreach($items as $number => $title)
		{
			$results[] = new ChannelInfo(
				$this,
				ChannelType::VOXIMPLANT,
				$title,
				$number,
				'',
				$sort++,
				self::GROUP_ID
			);
		}
		return $results;
	}
	/**
	 * Prepare channel caption
	 * @param array|null $params Array of channel parameters.
	 * @return string
	 */
	public function prepareCaption(array $params = null)
	{
		$caption = Loc::getMessage("VOXIMPLANT_UNKNOWN_NUMBER");
		$items = \CVoxImplantConfig::GetPortalNumbers(true, true);
		if (isset($params["ORIGIN_ID"]) && in_array($params["ORIGIN_ID"], $items))
		{
			$caption = $items[$params["ORIGIN_ID"]];
		}
		return $caption;
	}
	//endregion
}