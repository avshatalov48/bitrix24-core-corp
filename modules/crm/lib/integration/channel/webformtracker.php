<?php
namespace Bitrix\Crm\Integration\Channel;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\WebForm;
use Bitrix\Crm\WebForm\Preset;
use Bitrix\Crm\WebForm\Internals\FormTable as WebFormTable;

Loc::loadMessages(__FILE__);

class WebFormTracker extends ChannelTracker
{
	const GROUP_ID = 'WEBFORM';

	/** @var WebFormTracker|null  */
	private static $instance = null;

	public function __construct()
	{
		parent::__construct(ChannelType::WEBFORM);
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
	 * @return WebFormTracker
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new WebFormTracker();
		}
		return self::$instance;
	}
	protected static function checkInstalledPresets()
	{
		if(Preset::checkVersion())
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
		$dbResult = WebFormTable::getList(
			array(
				'filter' => array('=ACTIVE' => 'Y'),
				'select' => array('ID'),
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
		return WebForm\Manager::checkReadPermission();
	}
	/**
	 * Get channel page URL.
	 * @param array $params Array of channel parameters.
	 * @return string
	 */
	public function getUrl(array $params = null)
	{
		return WebForm\Manager::getUrl();
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
				Loc::getMessage('WEBFORM_CHANNEL'),
				10000,
				true,
				WebForm\Manager::getUrl()
			)
		);
	}
	/**
	 * Create channel info items.
	 * @return IChannelInfo[]
	 */
	public function prepareChannelInfos()
	{
		$sort = 1;
		$results = array();
		$dbResult = WebFormTable::getList(array(
			'filter' => array('=ACTIVE' => 'Y'),
			'select' => array('ID', 'NAME')));
		while ($form = $dbResult->fetch())
		{
			$results[] = new ChannelInfo(
				$this,
				ChannelType::WEBFORM,
				$form['NAME'],
				$form['ID'],
				'',
				$sort,
				self::GROUP_ID
			);
			$sort++;
		}

		if(empty($results))
		{
			$results[] = new ChannelInfo(
				$this,
				ChannelType::WEBFORM,
				Loc::getMessage('WEBFORM_CHANNEL'),
				'',
				'',
				$sort,
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
		if(!$this->isEnabled())
		{
			return '';
		}

		if(!is_array($params))
		{
			$params = array();
		}

		$originID = isset($params['ORIGIN_ID']) ? (int)$params['ORIGIN_ID'] : 0;
		if($originID <= 0)
		{
			return Loc::getMessage('WEBFORM_CHANNEL');
		}

		$dbResult = WebFormTable::getList(
			array(
				'select' => array('ID', 'NAME'),
				'filter' => array('ID' => $originID)
			)
		);

		$fields = $dbResult->fetch();
		$name = is_array($fields) && isset($fields['NAME']) ? $fields['NAME'] : '';

		$prefix = Loc::getMessage('WEBFORM_CHANNEL');
		return $name !== '' ? "{$prefix}: {$name}" : $prefix;
	}
	//endregion
}