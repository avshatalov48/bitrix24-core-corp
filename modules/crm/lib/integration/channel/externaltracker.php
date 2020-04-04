<?php
namespace Bitrix\Crm\Integration\Channel;

use Bitrix\Crm\Rest\CCrmExternalChannelActivityType;
use Bitrix\Crm\Rest\CCrmExternalChannelType;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Activity\Provider\ExternalChannel;

Loc::loadMessages(__FILE__);

class ExternalTracker extends ChannelTracker
{
	/** @var ExternalTracker[]|null  */
	private static $instances = null;
	/** @var array|null */
	private $connectorInfos = null;
	/** @var array|null */
	private $types = null;
	/** @var array|null */
	private static $supportedTypeIDs = null;
	/** @var array|null */
	private static $supportedTypeMap = null;

	/**
	 * @param int $typeID Type ID.
	 * @return ExternalTracker
	 */
	public static function getInstance($typeID)
	{
		if(self::$instances === null || !isset(self::$instances[$typeID]))
		{
			if(self::$instances === null)
			{
				self::$instances = array();
			}

			if(!isset(self::$instances[$typeID]))
			{
				self::$instances[$typeID] = new ExternalTracker($typeID);
			}
		}
		return self::$instances[$typeID];
	}
	/**
	 * Convert Channel type ID to external type ID.
	 * @param int $typeID Channel Type ID.
	 * @return int
	 */
	public function resolveExternalChannelTypeID($typeID)
	{
		$map = self::getSupportedTypeMap();
		return isset($map[$typeID]) ? $map[$typeID] : '';
	}
	/**
	 * Add instance of this manager to collection
	 * @param array $instances Destination collection.
	 */
	public static function registerInstance(array &$instances)
	{
		foreach(array_keys(self::getSupportedTypeMap()) as $groupID)
		{
			$instances[$groupID] = new ExternalTracker($groupID);
		}
	}
	/**
	 * Get all active connector info array.
	 * @return array
	 */
	public function getConnectorInfos()
	{
		if($this->connectorInfos !== null)
		{
			return $this->connectorInfos;
		}
		return ($this->connectorInfos = ExternalChannel::getListActiveConnector(self::resolveExternalChannelTypeID($this->getTypeID())));
	}
	/**
	 * Get all type Activity.
	 * @return array
	 */
	public function getTypes()
	{
		if($this->types !== null)
		{
			return $this->types;
		}
		else
		{
			foreach(CCrmExternalChannelActivityType::getAllDescriptions() as $id=>$name)
			{
				$this->types[CCrmExternalChannelActivityType::resolveName($id)] = $name;
			}
			return $this->types;
		}
	}
	/**
	 * Get supported type IDs.
	 * @return array
	 */
	protected static function getSupportedTypeIDs()
	{
		if(self::$supportedTypeIDs !== null)
		{
			return self::$supportedTypeIDs;
		}

		self::$supportedTypeIDs =
			array(
				ChannelType::EXTERNAL_CUSTOM,
				ChannelType::EXTERNAL_BITRIX,
				ChannelType::EXTERNAL_ONE_C,
				ChannelType::EXTERNAL_WORDPRESS,
				ChannelType::EXTERNAL_DRUPAL,
				ChannelType::EXTERNAL_JOOMLA,
				ChannelType::EXTERNAL_MAGENTO
			);

		return self::$supportedTypeIDs;
	}
	/**
	 * Get supported type map.
	 * @return array
	 */
	protected static function getSupportedTypeMap()
	{
		if(self::$supportedTypeMap !== null)
		{
			return self::$supportedTypeMap;
		}

		return(
			self::$supportedTypeMap = array(
				ChannelType::EXTERNAL_CUSTOM => CCrmExternalChannelType::CustomName,
				ChannelType::EXTERNAL_BITRIX => CCrmExternalChannelType::BitrixName,
				ChannelType::EXTERNAL_ONE_C => CCrmExternalChannelType::OneCName,
				ChannelType::EXTERNAL_WORDPRESS => CCrmExternalChannelType::WordpressName,
				ChannelType::EXTERNAL_DRUPAL => CCrmExternalChannelType::DrupalName,
				ChannelType::EXTERNAL_JOOMLA => CCrmExternalChannelType::JoomlaName,
				ChannelType::EXTERNAL_MAGENTO => CCrmExternalChannelType::MagentoName
			)
		);
	}

	protected static function getTypeSort($typeID)
	{
		if(!is_numeric($typeID))
		{
			return false;
		}

		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		$result = array_search($typeID, self::getSupportedTypeIDs(), true);
		return $result !== false ? $result : -1;
	}

	//region IChannelTracker
	/**
	 * Check if External Tracker is enabled.
	 * @return bool
	 */
	public function isEnabled()
	{
		return ExternalChannel::isActive();
	}
	/**
	 * Check if External Tracker is in use.
	 * @param array $params Array of channel parameters.
	 * @return bool
	 */
	protected function checkPossibilityOfUsing(array $params = null)
	{
		return ExternalChannel::isInUse(self::resolveExternalChannelTypeID($this->getTypeID()));
	}
	/**
	 * Check if current user has permission to configure External Tracker.
	 * @return bool
	 */
	public function checkConfigurationPermission(array $params = null)
	{
		return \CCrmPerms::IsAdmin(\CCrmSecurityHelper::GetCurrentUserID());
	}
	/**
	 * Get External Tracker URL.
	 * @param array $params Array of channel parameters.
	 * @return string
	 */
	public function getUrl(array $params = null)
	{
		return ExternalChannel::getRenderUrl(self::resolveExternalChannelTypeID($this->getTypeID()));
	}
	/**
	 * Create channel group info items.
	 * @return IChannelGroupInfo[]
	 */
	public function prepareChannelGroupInfos()
	{
		$groupID = ChannelType::resolveName($this->typeID);
		$sort = self::getTypeSort($this->typeID);
		if($sort < 0)
		{
			$sort = 1000;
		}
		$caption = CCrmExternalChannelType::getDescription(
			CCrmExternalChannelType::resolveID(self::resolveExternalChannelTypeID($this->typeID))
		);

		return array(
			$groupID => new ChannelGroupInfo(
				$this,
				$groupID,
				$caption,
				(12000 + $sort),
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
		$results = array();
		$types = $this->getTypes();

		$groupID = ChannelType::resolveName($this->typeID);
		foreach($this->getConnectorInfos() as $connector)
		{
			foreach($types as $typeID => $typeName)
			{
				$results[] = new ChannelInfo(
					$this,
					$this->getTypeID(),
					"{$connector['NAME']} {$typeName}",
					$connector['ORIGINATOR_ID'],
					$typeID,
					$sort,
					$groupID
				);
				$sort++;
			}
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
		if(!is_array($params))
		{
			$params = array();
		}

		$originID = isset($params['ORIGIN_ID']) ? $params['ORIGIN_ID'] : '';
		if($originID === '')
		{
			return '';
		}

		$types = $this->getTypes();
		$connectors = $this->getConnectorInfos();
		if(isset($connectors[$originID]))
		{
			$connector = $connectors[$originID];
			$connectorName = $connector['NAME'];

			$componentID = isset($params['COMPONENT_ID']) ? $params['COMPONENT_ID'] : '';
			if($componentID === '')
			{
				return $connectorName;
			}

			$typeName = $types[$componentID];
			return "{$connectorName} {$typeName}";
		}

		return '';
	}
	//endregion
}
