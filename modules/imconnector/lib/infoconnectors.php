<?php

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

		return '';
	}

	/**
	 * Method for agent, which add connectors info for certain line
	 *
	 * @param int $lineId
	 *
	 * @return string
	 */
	public static function infoConnectorsLineAddAgent($lineId)
	{
		$lineId = (int)$lineId;
		if ($lineId > 0)
		{
			self::addInfoConnectors($lineId);
		}

		return '';
	}

	/**
	 * Method for agent, which check and update connectors info for certain line
	 *
	 * @param int $lineId
	 *
	 * @return string
	 */
	public static function infoConnectorsLineUpdateAgent($lineId): string
	{
		$lineId = (int)$lineId;
		if ($lineId > 0)
		{
			$result = self::updateInfoConnectors($lineId);

			if (
				$result instanceof \Bitrix\Main\ORM\Data\UpdateResult
				&& $result->isSuccess()
				&& Loader::includeModule('crm')
			)
			{
				\CAgent::AddAgent(
					'\\Bitrix\\Crm\\SiteButton\\Manager::updateScriptCacheAgent();',
					'crm',
					'N',
					60,
					'',
					'Y',
					\ConvertTimeStamp(time()+\CTimeZone::GetOffset()+1800, 'FULL')
				);
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
		$isCalledRecursively = (int)$isCalledRecursively;
		$method = '\Bitrix\ImConnector\InfoConnectors::infoConnectorsUpdateAgent('.$isCalledRecursively.');';
		\CAgent::AddAgent(
			$method,
			'imconnector',
			'N',
			$interval,
			'',
			'Y',
			\ConvertTimeStamp((time() + \CTimeZone::GetOffset() + $interval), 'FULL')
		);
	}

	/**
	 * Add short time non period agent to add connectors info for certain line
	 *
	 * @param int $lineId
	 * @param int $interval
	 */
	public static function addSingleLineAddAgent($lineId, $interval = Library::INSTANT_AGENT_EXEC_INTERVAL)
	{
		$method = '\Bitrix\ImConnector\InfoConnectors::infoConnectorsLineAddAgent('.(int)$lineId.');';
		\CAgent::AddAgent(
			$method,
			'imconnector',
			'N',
			$interval,
			'',
			'Y',
			\ConvertTimeStamp((time() + \CTimeZone::GetOffset() + $interval), 'FULL')
		);
	}

	/**
	 * Add short time non period agent to update connectors info for certain line
	 *
	 * @param $lineId
	 * @param int $interval
	 * @return void
	 */
	public static function addSingleLineUpdateAgent($lineId, $interval = Library::INSTANT_AGENT_EXEC_INTERVAL)
	{
		$method = '\Bitrix\ImConnector\InfoConnectors::infoConnectorsLineUpdateAgent('.(int)$lineId.');';
		\CAgent::AddAgent(
			$method,
			'imconnector',
			'N',
			$interval,
			'',
			'Y',
			\ConvertTimeStamp((time() + \CTimeZone::GetOffset() + $interval), 'FULL')
		);
	}

	/**
	 * Event handler for add/delete connector statuses
	 *
	 * @param \Bitrix\Main\Event $event
	 * @return void
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
	 * @return void
	 */
	public static function onUpdateStatusConnector(\Bitrix\Main\Event $event)
	{
		$parameters = $event->getParameters();

		if (
			(
				$parameters['fields']['ACTIVE'] == 'Y'
				&& $parameters['fields']['CONNECTION'] == 'Y'
				&& $parameters['fields']['REGISTER'] == 'Y'
				&& $parameters['fields']['ERROR'] == 'N'
			)
			||
			(
				$parameters['fields']['ERROR'] == 'Y'
				&& $parameters['fields']['ACTIVE'] == 'Y'
			)
		)
		{
			self::addSingleLineUpdateAgent($parameters['line']);
		}
	}

	/**
	 * Event handler for imopenline create
	 *
	 * @param \Bitrix\Main\Event $event
	 * @return void
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
	 * @return void
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
	 * @param int $lineId
	 *
	 * @return array
	 */
	public static function infoConnectorsLine($lineId)
	{
		$cache = Cache::createInstance();
		$result = [];

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
	 * @param int $lineId
	 *
	 * @return \Bitrix\Main\ORM\Query\Result
	 */
	public static function getInfoConnectorsById($lineId)
	{
		$lineId = (int)$lineId;
		$filter  = array(
			'LINE_ID' => $lineId
		);

		return self::getInfoConnectors($filter);
	}

	/**
	 * Return list of expired info connector data from InfoConnector table
	 *
	 * @return \Bitrix\Main\ORM\Query\Result
	 */
	public static function getExpiredInfoConnectors()
	{
		$filter = array(
			'<EXPIRES' => \Bitrix\Main\Type\DateTime::createFromTimestamp(time())
		);

		return self::getInfoConnectors($filter);
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
	 */
	public static function addInfoConnectors($lineId)
	{
		$connectorsInfoCount = InfoConnectorsTable::getByPrimary(['LINE_ID' => $lineId])->getSelectedRowsCount();
		$result = false;

		if ($connectorsInfoCount == 0)
		{
			$cacheTime = (int)Library::CACHE_TIME_INFO_CONNECTORS_LINE;
			$timeExpires = \Bitrix\Main\Type\DateTime::createFromTimestamp(time() + $cacheTime);

			$data = Connector::getOutputInfoConnectorsLine($lineId);
			$dataEncoded = Json::encode($data);
			$hashDataEncoded = md5($dataEncoded);

			$result = InfoConnectorsTable::add(
				[
					'LINE_ID' => $lineId,
					'DATA' => $dataEncoded,
					'EXPIRES' => $timeExpires,
					'DATA_HASH' => $hashDataEncoded,
				]
			);

			$cacheData = [
				'DATA' => $dataEncoded,
				'EXPIRES' => $timeExpires,
				'DATA_HASH' => $hashDataEncoded,
			];

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
	 */
	public static function updateInfoConnectors($lineId)
	{
		$connectorRes = InfoConnectorsTable::getByPrimary(['LINE_ID' => $lineId]);
		$connectorsInfoCount = $connectorRes->getSelectedRowsCount();
		$result = false;

		if ($connectorsInfoCount === 1)
		{
			$cacheTime = (int)Library::CACHE_TIME_INFO_CONNECTORS_LINE;
			$timeExpires = \Bitrix\Main\Type\DateTime::createFromTimestamp(time() + $cacheTime);

			$data = Connector::getOutputInfoConnectorsLine($lineId);
			$dataEncoded = Json::encode($data);
			$hashDataEncoded = md5($dataEncoded);

			$connectorData = $connectorRes->fetch();

			$result = InfoConnectorsTable::update(
				$lineId,
				[
					'DATA' => $dataEncoded,
					'EXPIRES' => $timeExpires,
					'DATA_HASH' => $hashDataEncoded,
				]
			);

			$cacheData = [
				'DATA' => $dataEncoded,
				'EXPIRES' => $timeExpires,
				'DATA_HASH' => $hashDataEncoded,
			];

			self::rewriteInfoConnectorsLineCache($lineId, $cacheData);

			if ($connectorData['DATA_HASH'] === $hashDataEncoded)
			{
				$result = false;
			}
		}

		return $result;
	}

	/**
	 * Method for deleting single connection info element for single line
	 *
	 * @param $lineId
	 *
	 * @return \Bitrix\Main\ORM\Data\DeleteResult|bool
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