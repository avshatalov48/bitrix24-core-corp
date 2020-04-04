<?php
/**
 * Created by PhpStorm.
 * User: varfolomeev
 * Date: 25.09.2018
 * Time: 17:27
 */

namespace Bitrix\ImConnector;

use \Bitrix\Main\Loader;
use \Bitrix\Main\Web\Json;
use \Bitrix\Main\Data\Cache;
use \Bitrix\ImConnector\Model\InfoConnectorsTable;

class InfoConnectors
{
	/**
	 * Method for agent which add data about all lines connections info, which haven't them
	 *
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function infoConnectorsAddAgent()
	{
		if (Loader::includeModule('imopenlines'))
		{
			$lines = \Bitrix\ImOpenLines\Model\ConfigTable::getList(
				array(
					'filter' => array(
						'ACTIVE' => 'Y'
					)
				)
			);
			$baseInterval = Library::LOCAL_AGENT_EXEC_INTERVAL;

			while ($line = $lines->fetch())
			{
				self::addSingleLineAddAgent($line['ID'], $baseInterval);
				$baseInterval += Library::LOCAL_AGENT_EXEC_INTERVAL;
			}
		}

		return '';
	}

	/**
	 * Method for agent, which check and update connectors info for all lines
	 *
	 * @param int $isCalledRecursively
	 *
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function infoConnectorsUpdateAgent($isCalledRecursively = 0)
	{
		$infoConnectors = self::getExpiredInfoConnectors();
		$connectorInfo = $infoConnectors->fetch();

		if (!empty($connectorInfo))
		{
			self::updateInfoConnectors($connectorInfo['LINE_ID']);

			if (!empty($infoConnectors->fetch()))
			{
				self::addAllLinesUpdateAgent(1);
			}
		}

		if ($isCalledRecursively == 0)
		{
			return '\Bitrix\ImConnector\InfoConnectors::infoConnectorsUpdateAgent();';
		}
	}

	/**
	 * Method for agent, which add connectors info for certain line
	 *
	 * @param $lineId
	 *
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function infoConnectorsLineAddAgent($lineId)
	{
		$lineId = intval($lineId);
		if ($lineId > 0)
		{
			self::addInfoConnectors($lineId);
		}

		return '';
	}

	/**
	 * Method for agent, which check and update connectors info for certain line
	 *
	 * @param $lineId
	 *
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function infoConnectorsLineUpdateAgent($lineId)
	{
		$lineId = intval($lineId);
		if ($lineId > 0)
		{
			self::updateInfoConnectors($lineId);

			if (Loader::includeModule('crm'))
			{
				\Bitrix\Crm\SiteButton\Manager::updateScriptCache();
			}
		}

		return '';
	}

	/**
	 * Add short time non period agent to update connectors info for all lines
	 *
	 * @param int $isCalledRecursively
	 * @param int $interval
	 */
	public static function addAllLinesUpdateAgent($isCalledRecursively = 0, $interval = Library::LOCAL_AGENT_EXEC_INTERVAL)
	{
		$isCalledRecursively = intval($isCalledRecursively);
		$method = '\Bitrix\ImConnector\InfoConnectors::infoConnectorsUpdateAgent('.$isCalledRecursively.');';
		\CAgent::AddAgent($method,
						  'imconnector',
						  'N',
						  $interval,
						  '',
						  'Y',
						  ConvertTimeStamp((time() + \CTimeZone::GetOffset() + $interval), 'FULL')
		);
	}

	/**
	 * Add short time non period agent to add connectors info for certain line
	 *
	 * @param $lineId
	 * @param int $interval
	 */
	public static function addSingleLineAddAgent($lineId, $interval = Library::INSTANT_AGENT_EXEC_INTERVAL)
	{
		$method = '\Bitrix\ImConnector\InfoConnectors::infoConnectorsLineAddAgent('.intval($lineId).');';
		\CAgent::AddAgent($method,
						  'imconnector',
						  'N',
						  $interval,
						  '',
						  'Y',
						  ConvertTimeStamp((time() + \CTimeZone::GetOffset() + $interval), 'FULL')
		);
	}

	/**
	 * Add short time non period agent to update connectors info for certain line
	 *
	 * @param $lineId
	 * @param int $interval
	 */
	public static function addSingleLineUpdateAgent($lineId, $interval = Library::INSTANT_AGENT_EXEC_INTERVAL)
	{
		$method = '\Bitrix\ImConnector\InfoConnectors::infoConnectorsLineUpdateAgent('.intval($lineId).');';
		\CAgent::AddAgent($method,
						  'imconnector',
						  'N',
						  $interval,
						  '',
						  'Y',
						  ConvertTimeStamp((time() + \CTimeZone::GetOffset() + $interval), 'FULL')
		);
	}

	/**
	 * Event handler for add/delete connector statuses
	 *
	 * @param \Bitrix\Main\Event $event
	 */
	public static function onChangeStatusConnector(\Bitrix\Main\Event $event)
	{
		$parameters = $event->getParameters();
		self::addSingleLineUpdateAgent($parameters['line']);
	}

	/**
	 * Event handler for update connector status
	 *
	 * @param \Bitrix\Main\Event $event
	 */
	public static function onUpdateStatusConnector(\Bitrix\Main\Event $event)
	{
		$parameters = $event->getParameters();

		if (($parameters['fields']['ACTIVE'] == 'Y' &&
			$parameters['fields']['CONNECTION'] == 'Y' &&
			$parameters['fields']['REGISTER'] == 'Y' &&
			$parameters['fields']['ERROR'] == 'N') ||
			($parameters['fields']['ERROR'] == 'Y' &&
			$parameters['fields']['ACTIVE'] == 'Y'))
		{
			self::addSingleLineUpdateAgent($parameters['line']);
		}
	}

	/**
	 * Event handler for imopenline create
	 *
	 * @param \Bitrix\Main\Event $event
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function onImopenlineCreate(\Bitrix\Main\Event $event)
	{
		$parameters = $event->getParameters();

		self::addInfoConnectors($parameters['line']);
	}

	/**
	 * Event handler for imopenline delete
	 *
	 * @param \Bitrix\Main\Event $event
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function onImopenlineDelete(\Bitrix\Main\Event $event)
	{
		$parameters = $event->getParameters();

		self::deleteInfoConnectors($parameters['line']);
	}

	/**
	 * Return all info connectors elements
	 *
	 * @param array $filter
	 *
	 * @return \Bitrix\Main\ORM\Query\Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getInfoConnectors($filter = array())
	{
		$result = InfoConnectorsTable::getList(
			array(
				'filter' => $filter
			)
		);

		return $result;
	}

	/**
	 * Return info connectors line data with cache
	 *
	 * @param $lineId
	 *
	 * @return array|\Bitrix\Main\ORM\Query\Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function infoConnectorsLine($lineId)
	{
		$cache = Cache::createInstance();
		$result = array();

		if ($cache->initCache(Library::CACHE_TIME_INFO_CONNECTORS_LINE, $lineId, Library::CACHE_DIR_INFO_CONNECTORS_LINE))
		{
			$result = $cache->getVars();
		}
		elseif($cache->startDataCache())
		{
			$infoConnectors = self::getInfoConnectorsById($lineId);

			if ($infoConnectors->getSelectedRowsCount() == 0)
			{
				$cache->abortDataCache();
			}
			else
			{
				$result = $infoConnectors->fetch();
				$cache->endDataCache($result);
			}
		}

		return $result;
	}

	/**
	 * Return info connectors data by line id
	 *
	 * @param $lineId
	 *
	 * @return \Bitrix\Main\ORM\Query\Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getInfoConnectorsById($lineId)
	{
		$lineId = intval($lineId);
		$filter  = array(
			'LINE_ID' => $lineId
		);

		$result = self::getInfoConnectors($filter);

		return $result;
	}

	/**
	 * Return list of expired info connector data from InfoConnector table
	 *
	 * @return \Bitrix\Main\ORM\Query\Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getExpiredInfoConnectors()
	{
		$filter = array(
			'<EXPIRES' => \Bitrix\Main\Type\DateTime::createFromTimestamp(time())
		);

		$result = self::getInfoConnectors($filter);

		return $result;
	}

	/**
	 * Return all info connectors elements array
	 *
	 * @param array $filter
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getInfoConnectorsList($filter = array())
	{
		$result = array();
		$infoConnectors = self::getInfoConnectors($filter);
		while ($info = $infoConnectors->fetch())
		{
			$result[$info['LINE_ID']] = $info;
		}

		return $result;
	}

	/**
	 * Method for adding single connection info element for single line
	 *
	 * @param $lineId
	 *
	 * @return \Bitrix\Main\ORM\Data\AddResult|bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function addInfoConnectors($lineId)
	{
		$connectorsInfoCount = self::getInfoConnectorsById($lineId)->getSelectedRowsCount();
		$result = false;

		if ($connectorsInfoCount == 0)
		{
			$cacheTime = intval(Library::CACHE_TIME_INFO_CONNECTORS_LINE);
			$timeExpires = \Bitrix\Main\Type\DateTime::createFromTimestamp(time() + $cacheTime);

			$data = Connector::getOutputInfoConnectorsLine($lineId);
			$dataEncoded = Json::encode($data);

			$result = InfoConnectorsTable::add(
				array(
					'LINE_ID' => $lineId,
					'DATA' => $dataEncoded,
					'EXPIRES' => $timeExpires,
				)
			);

			$cacheData = array(
				'DATA' => $dataEncoded,
				'EXPIRES' => $timeExpires,
			);

			self::rewriteInfoConnectorsLineCache($lineId, $cacheData);
		}

		return $result;
	}

	/**
	 * Method for update single connection info element for single line
	 *
	 * @param $lineId
	 *
	 * @return \Bitrix\Main\ORM\Data\UpdateResult|bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function updateInfoConnectors($lineId)
	{
		$connectorsInfoCount = self::getInfoConnectorsById($lineId)->getSelectedRowsCount();
		$result = false;

		if ($connectorsInfoCount == 1)
		{
			$cacheTime = intval(Library::CACHE_TIME_INFO_CONNECTORS_LINE);
			$timeExpires = \Bitrix\Main\Type\DateTime::createFromTimestamp(time() + $cacheTime);

			$data = Connector::getOutputInfoConnectorsLine($lineId);
			$dataEncoded = Json::encode($data);

			$result = InfoConnectorsTable::update(
				$lineId,
				array(
					'DATA' => $dataEncoded,
					'EXPIRES' => $timeExpires,
				)
			);

			$cacheData = array(
				'DATA' => $dataEncoded,
				'EXPIRES' => $timeExpires,
			);

			self::rewriteInfoConnectorsLineCache($lineId, $cacheData);
		}

		return $result;
	}

	/**
	 * Method for deleting single connection info element for single line
	 *
	 * @param $lineId
	 *
	 * @return \Bitrix\Main\ORM\Data\DeleteResult|bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function deleteInfoConnectors($lineId)
	{
		$connectorsInfoCount = self::getInfoConnectorsById($lineId)->getSelectedRowsCount();
		$result = false;

		if ($connectorsInfoCount == 1)
		{
			$result = InfoConnectorsTable::delete($lineId);
			self::clearInfoConnectorsLineCache($lineId);
		}

		return $result;
	}

	/**
	 * Rewrite info connectors line local cache
	 *
	 * @param $lineId
	 * @param $data
	 */
	public static function rewriteInfoConnectorsLineCache($lineId, $data)
	{
		$cache = Cache::createInstance();
		$cache->clean($lineId, Library::CACHE_DIR_INFO_CONNECTORS_LINE);
		$data['LINE_ID'] = $lineId;
		$cache->startDataCache(Library::CACHE_TIME_INFO_CONNECTORS_LINE, $lineId, Library::CACHE_DIR_INFO_CONNECTORS_LINE);
		$cache->endDataCache($data);
	}

	/**
	 * Clear info connectors line local cache
	 *
	 * @param $lineId
	 */
	public static function clearInfoConnectorsLineCache($lineId)
	{
		$cache = Cache::createInstance();
		$cache->clean($lineId, Library::CACHE_DIR_INFO_CONNECTORS_LINE);
	}
}