<?php
namespace Bitrix\Crm\Integration\Channel;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\ImOpenLines;
use Bitrix\ImConnector;

Loc::loadMessages(__FILE__);

class IMOpenLineTracker extends ChannelTracker
{
	/** @var IMOpenLineTracker|null  */
	private static $instance = null;

	/** @var bool|null  */
	private $isEnabled = null;
	/** @var array|null  */
	private $items = null;
	/** @var array|null  */
	private $connectorInfos = null;
	const CHAT_CONNECTOR = 'livechat';

	public function __construct()
	{
		parent::__construct(ChannelType::IMOPENLINE);
	}

	/**
	 * Get manager instance
	 * @return IMOpenLineTracker
	 */
	public static function getInstance()
	{
		if(self::$instance !== null)
		{
			return self::$instance;
		}
		return (self::$instance = new IMOpenLineTracker());
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
	 * Check if IM Open Lines enabled.
	 * @return bool
	 */
	public function isEnabled()
	{
		if($this->isEnabled === null)
		{
			$this->isEnabled = ModuleManager::isModuleInstalled('imconnector')
				&& ModuleManager::isModuleInstalled('imopenlines')
				&& Loader::includeModule('imconnector')
				&& Loader::includeModule('imopenlines');
		}
		return $this->isEnabled;
	}
	/**
	 * Check if IM Open Lines in use.
	 * @param array $params Array of channel parameters.
	 * @return bool
	 */
	protected function checkPossibilityOfUsing(array $params = null)
	{
		return $this->isEnabled() && ImOpenLines\Helper::isAvailable();
	}
	/**
	 * Check if current user has permission to configure IM Open Lines.
	 * @param array $params Array of channel parameters.
	 * @return bool
	 */
	public function checkConfigurationPermission(array $params = null)
	{
		return $this->isEnabled() && ImOpenLines\Security\Helper::canCurrentUserModifyLine();
	}
	/**
	 * Get IM Open Lines URL.
	 * @param array $params Array of channel parameters
	 * @return string
	 */
	public function getUrl(array $params = null)
	{
		if(!$this->isEnabled())
		{
			return '';
		}

		if(!is_array($params))
		{
			$params = array();
		}

		$originID = isset($params['ORIGIN_ID']) ? $params['ORIGIN_ID'] : false;
		$componentID = isset($params['COMPONENT_ID']) ? $params['COMPONENT_ID'] : false;
		return $originID > 0 ? ImOpenLines\Helper::getConnectorUrl($componentID, $originID) : ImOpenLines\Helper::getAddUrl();
	}
	/**
	 * Create channel group info items.
	 * @return IChannelGroupInfo[]
	 */
	public function prepareChannelGroupInfos()
	{
		$sort = 1000;
		$result = array(
			"IMOPENLINE" => new ChannelGroupInfo(
				$this,
				"IMOPENLINE",
				Loc::getMessage('IMOPENLINE_CHANNEL'),
				$sort,
				false,
				ImOpenLines\Helper::getListUrl()
			)
		);

		foreach($this->getItems() as $item)
		{
			$itemID = (int)$item['ID'];
			$itemName = isset($item['LINE_NAME']) ? $item['LINE_NAME'] : "[{$itemID}]";
			$groupID = "IMOPENLINE_{$itemID}";
			$result[$groupID] = new ChannelGroupInfo(
				$this,
				$groupID,
				$itemName,
				($sort += 100),
				true,
				ImOpenLines\Helper::getConnectorUrl('', $itemID)
			);
			$result[$groupID]->setParentID("IMOPENLINE");
		}
		return $result;
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
		$items = $this->getItems();
		foreach($items as $item)
		{
			$groupID = "IMOPENLINE_{$item['ID']}";
			foreach($this->getConnectorInfos() as $connectorID => $connectorInfo)
			{
				$connectorName = $connectorInfo['NAME'];
				$results[] = new ImOpenLineChannelInfo(
					$connectorName,
					(int)$item['ID'],
					$connectorID,
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
		if(!$this->isEnabled())
		{
			return '';
		}

		if(!is_array($params))
		{
			$params = array();
		}

		$originID = isset($params['ORIGIN_ID']) ? $params['ORIGIN_ID'] : '';
		if($originID === '')
		{
			return '';
		}

		$items = $this->getItems();
		if(isset($items[$originID]))
		{
			$item = $items[$originID];
			$itemName = isset($item['LINE_NAME']) ? $item['LINE_NAME'] : "{$originID}";

			$componentID = isset($params['COMPONENT_ID']) ? $params['COMPONENT_ID'] : '';
			if($componentID === '')
			{
				return $itemName;
			}

			$connectors = $this->getConnectorInfos();
			$connectorName = isset($connectors[$componentID]) ? $connectors[$componentID]['NAME'] : "{$componentID}";
			return "{$itemName} {$connectorName}";
		}

		return '';
	}
	//endregion
	public function getItems()
	{
		if($this->items !== null)
		{
			return $this->items;
		}

		$this->items = array();
		if($this->isEnabled())
		{
			$entity = new ImOpenLines\Config();
			$items = $entity->getList(
				array(
					'select' => array('ID', 'LINE_NAME'),
					'filter' => array('ACTIVE' => 'Y'),
					'order' => array('ID' => 'ASC'),
					'limit' => 50
				)
			);

			foreach($items as $item)
			{
				$this->items[$item['ID']] = $item;
			}
		}
		return $this->items;
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

		if(!$this->isEnabled())
		{
			return ($this->connectorInfos = array());
		}

		$botSort = 10;
		$this->connectorInfos = array();
		foreach(ImConnector\Connector::getListActiveConnector(false, true) as $k => $v)
		{
			if($k === 'livechat')
			{
				$sort = 1;
			}
			elseif($k === 'viber')
			{
				$sort = 2;
			}
			elseif($k === 'facebook')
			{
				$sort = 3;
			}
			elseif($k === 'facebookcomments')
			{
				$sort = 4;
			}
			elseif($k === 'vkgroup')
			{
				$sort = 5;
			}
			elseif($k === 'telegrambot')
			{
				$sort = 6;
			}
			elseif($k === 'instagram')
			{
				$sort = 7;
			}
			elseif(strpos($k, 'botframework.') === 0)
			{
				$sort = $botSort;
				$botSort++;
			}
			else
			{
				$sort = 100 + count($this->connectorInfos);
			}

			$this->connectorInfos[$k] = array('ID' => $k, 'NAME' => $v, 'SORT' => $sort);
		}
		uasort($this->connectorInfos, array('\Bitrix\Crm\Integration\Channel\IMOpenLineTracker', 'compareConnectorInfoBySort'));
		return $this->connectorInfos;
	}
	/**
	 * Compare connector info by sort field.
	 * @param array $a First connector info.
	 * @param array $b Second connector info.
	 * @return int
	 */
	public static function compareConnectorInfoBySort(array $a, array $b)
	{
		return ($a['SORT'] - $b['SORT']);
	}
	/**
	 * Check if IM Open Lines connector is enabled
	 * @param string $connectorID Connector ID.
	 * @return bool
	 */
	public function isConnectorEnabled($connectorID)
	{
		$infos = $this->getConnectorInfos();
		return isset($infos[$connectorID]);
	}
	/**
	 * Get Open Line Connector name
	 * @param string $connectorID Connector ID.
	 * @return string
	 */
	public function getConnectorCaption($connectorID)
	{
		$infos = $this->getConnectorInfos();
		return isset($infos[$connectorID]) ? $infos[$connectorID]['NAME'] : "[{$connectorID}]";
	}
	/**
	 * Check if IM Open Lines connector in use
	 * @param string $connectorID Connector ID.
	 * @param int $lineID Line ID.
	 * @return bool
	 */
	public function isConnectorInUse($connectorID, $lineID = 0)
	{
		if(!$this->isEnabled())
		{
			return false;
		}

		$infos = $this->getConnectorInfos();
		if(!isset($infos[$connectorID]))
		{
			return false;
		}

		if($connectorID === 'livechat')
		{
			return ImOpenLines\Helper::isLiveChatAvailable();
		}

		$status = ImConnector\Status::getInstance($connectorID, $lineID);
		return $status->isStatus();
	}
	/**
	 * Check if current user has permission to configure  IM Open Lines feature.
	 * @param string $connectorID Connector ID.
	 * @return bool
	 */
	public function checkConnectorConfigurationPermission($connectorID)
	{
		return $this->isEnabled() && ImOpenLines\Security\Helper::canCurrentUserModifyConnector();
	}
	/**
	 * Get IM Open Lines feature URL.
	 * @param string $connectorID Connector ID.
	 * @return string
	 * @throws Main\LoaderException
	 */
	public function getConnectorUrl($connectorID)
	{
		return $this->isEnabled() ? ImOpenLines\Helper::getListUrl() : '';
	}
}