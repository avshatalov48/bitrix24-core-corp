<?php

namespace Bitrix\ImConnector;

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM;
use Bitrix\Main\Event;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Type\DateTime;
use Bitrix\ImConnector\Model\InfoConnectorsTable;
use Bitrix\ImConnector\Model\StatusConnectorsTable;

class InfoConnectors
{
	/**
	 * Method for agent which add data about all lines connections info, which haven't them
	 *
	 * @return string
	 */
	public static function infoConnectorsAddAgent(): string
	{
		if (Loader::includeModule('imopenlines'))
		{
			$lines = \Bitrix\ImOpenLines\Model\ConfigTable::getList([
				'filter' => [
					'ACTIVE' => 'Y'
				]
			]);
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
	public static function infoConnectorsUpdateAgent($isCalledRecursively = 0): string
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
			return __METHOD__ . '();';
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
	public static function infoConnectorsLineAddAgent($lineId): string
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

			if ($result->isSuccess())
			{
				self::addSiteButtonUpdaterAgent();
			}
		}

		return '';
	}

	/**
	 * Method for agent, which update SiteButton.
	 *
	 * @return void
	 */
	public static function addSiteButtonUpdaterAgent(): void
	{
		if (Loader::includeModule('crm'))
		{
			/** @see \Bitrix\Crm\SiteButton\Manager::updateScriptCacheAgent */
			\CAgent::AddAgent(
				'\\Bitrix\\Crm\\SiteButton\\Manager::updateScriptCacheAgent();',
				'crm',
				'N',
				60,
				'',
				'Y',
				\ConvertTimeStamp(time()+\CTimeZone::GetOffset()+1800, 'FULL')
			);

			global $APPLICATION;
			if ($APPLICATION instanceof \CMain)
			{
				$APPLICATION->resetException();
			}
		}
	}

	/**
	 * Add short time non period agent to update connectors info for all lines
	 *
	 * @param int $isCalledRecursively
	 * @param int $interval
	 * @return void
	 */
	public static function addAllLinesUpdateAgent($isCalledRecursively = 0, $interval = Library::LOCAL_AGENT_EXEC_INTERVAL): void
	{
		/** @see self::infoConnectorsUpdateAgent */
		$method = __CLASS__ . '::infoConnectorsUpdateAgent('.(int)$isCalledRecursively.');';
		\CAgent::AddAgent(
			$method,
			'imconnector',
			'N',
			$interval,
			'',
			'Y',
			\ConvertTimeStamp((time() + \CTimeZone::GetOffset() + $interval), 'FULL')
		);

		global $APPLICATION;
		if ($APPLICATION instanceof \CMain)
		{
			$APPLICATION->resetException();
		}
	}

	/**
	 * Add short time non period agent to add connectors info for certain line
	 *
	 * @param int $lineId
	 * @param int $interval
	 * @return void
	 */
	public static function addSingleLineAddAgent($lineId, $interval = Library::INSTANT_AGENT_EXEC_INTERVAL): void
	{
		/** @see self::infoConnectorsLineAddAgent */
		$method = __CLASS__ . '::infoConnectorsLineAddAgent('.(int)$lineId.');';
		\CAgent::AddAgent(
			$method,
			'imconnector',
			'N',
			$interval,
			'',
			'Y',
			\ConvertTimeStamp((time() + \CTimeZone::GetOffset() + $interval), 'FULL')
		);

		global $APPLICATION;
		if ($APPLICATION instanceof \CMain)
		{
			$APPLICATION->resetException();
		}
	}

	/**
	 * Add short time non period agent to update connectors info for certain line
	 *
	 * @param $lineId
	 * @param int $interval
	 * @return void
	 */
	public static function addSingleLineUpdateAgent($lineId, $interval = Library::INSTANT_AGENT_EXEC_INTERVAL): void
	{
		/** @see self::infoConnectorsLineUpdateAgent */
		$method = __CLASS__ . '::infoConnectorsLineUpdateAgent('.(int)$lineId.');';
		\CAgent::AddAgent(
			$method,
			'imconnector',
			'N',
			$interval,
			'',
			'Y',
			\ConvertTimeStamp((time() + \CTimeZone::GetOffset() + $interval), 'FULL')
		);

		global $APPLICATION;
		if ($APPLICATION instanceof \CMain)
		{
			$APPLICATION->resetException();
		}
	}

	/**
	 * Event handler for add/delete connector statuses
	 * @event 'imconnector:OnDeleteStatusConnector'
	 * @param Event $event
	 * @return void
	 */
	public static function onChangeStatusConnector(Event $event): void
	{
		$parameters = $event->getParameters();

		Application::getInstance()->addBackgroundJob(
			[__CLASS__, 'updateInfoConnectors'],
			[$parameters['line']],
			Application::JOB_PRIORITY_LOW
		);

		self::addSiteButtonUpdaterAgent();
	}

	/**
	 * Event handler for update connector status
	 * @event 'imconnector:OnUpdateStatusConnector'
	 *
	 * @param Event $event
	 * @return void
	 */
	public static function onUpdateStatusConnector(Event $event): void
	{
		$parameters = $event->getParameters();

		if (
			(
				isset($parameters['fields']['ACTIVE'])
				&& isset($parameters['fields']['CONNECTION'])
				&& isset($parameters['fields']['REGISTER'])
				&& isset($parameters['fields']['ERROR'])
				&& $parameters['fields']['ACTIVE'] == 'Y'
				&& $parameters['fields']['CONNECTION'] == 'Y'
				&& $parameters['fields']['REGISTER'] == 'Y'
				&& $parameters['fields']['ERROR'] == 'N'
			)
			||
			(
				isset($parameters['fields']['ERROR'])
				&& isset($parameters['fields']['ACTIVE'])
				&& $parameters['fields']['ERROR'] == 'Y'
				&& $parameters['fields']['ACTIVE'] == 'Y'
			)
		)
		{
			Application::getInstance()->addBackgroundJob(
				[__CLASS__, 'updateInfoConnectors'],
				[$parameters['line']],
				Application::JOB_PRIORITY_LOW
			);

			self::addSiteButtonUpdaterAgent();
		}
	}

	/**
	 * Event handler for imopenline create.
	 * @event `imopenlines:OnImopenlineCreate`
	 *
	 * @param Event $event
	 * @return void
	 */
	public static function onImopenlineCreate(Event $event): void
	{
		$parameters = $event->getParameters();

		self::addInfoConnectors($parameters['line']);
	}

	/**
	 * Event handler for imopenline delete.
	 * @event `imopenlines:OnImopenlineDelete`
	 *
	 * @param Event $event
	 * @return void
	 */
	public static function onImopenlineDelete(Event $event): void
	{
		$parameters = $event->getParameters();

		self::deleteInfoConnectors($parameters['line']);
	}

	/**
	 * Return all info connectors elements
	 *
	 * @param array $filter
	 *
	 * @return ORM\Query\Result
	 */
	public static function getInfoConnectors(array $filter = []): ORM\Query\Result
	{
		return InfoConnectorsTable::getList(['filter' => $filter]);
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
		static $cache = [];
		if (isset($cache[$lineId]))
		{
			return $cache[$lineId];
		}

		$result = [];

		if ($infoConnectors = self::getInfoConnectorsById($lineId))
		{
			$result = $infoConnectors->fetch();
		}

		$cache[$lineId] = $result;

		return $result;
	}

	/**
	 * Return info connectors data by line id
	 *
	 * @param int $lineId
	 *
	 * @return ORM\Query\Result
	 */
	public static function getInfoConnectorsById($lineId): ORM\Query\Result
	{
		return self::getInfoConnectors(['=LINE_ID' => (int)$lineId]);
	}

	/**
	 * Return list of expired info connector data from InfoConnector table
	 *
	 * @return ORM\Query\Result
	 */
	public static function getExpiredInfoConnectors(): ORM\Query\Result
	{
		return self::getInfoConnectors(['<EXPIRES' => DateTime::createFromTimestamp(time())]);
	}

	/**
	 * Return all info connectors elements array
	 *
	 * @param array $filter
	 *
	 * @return array<int, array>
	 */
	public static function getInfoConnectorsList(array $filter = [])
	{
		static $cache = [];
		$cacheKey = md5(serialize($filter));

		if (!isset($cache[$cacheKey]))
		{
			$result = [];
			$infoConnectors = self::getInfoConnectors($filter);
			while ($info = $infoConnectors->fetch())
			{
				$result[$info['LINE_ID']] = $info;
			}

			$cache[$cacheKey] = $result;
		}

		return $cache[$cacheKey];
	}

	/**
	 * Method is adding single connection info element for single line.
	 *
	 * @param int $lineId
	 * @return Result
	 */
	public static function addInfoConnectors($lineId): Result
	{
		return self::refreshInfoConnectors((int)$lineId);
	}

	/**
	 * Method for update single connection info element for single line
	 *
	 * @param $lineId
	 * @return Result
	 */
	public static function updateInfoConnectors($lineId): Result
	{
		$statusCount = StatusConnectorsTable::getCount(['LINE' => $lineId]);
		if ($statusCount == 0)
		{
			self::deleteInfoConnectors($lineId);
			return new Result();
		}

		return self::refreshInfoConnectors((int)$lineId);
	}

	/**
	 * Method for update single connection info element for single line
	 *
	 * @param int $lineId
	 * @return Result
	 */
	public static function refreshInfoConnectors(int $lineId): Result
	{
		$result = new Result();

		if ($data = Connector::getOutputInfoConnectorsLine($lineId))
		{
			$result->setResult($data);

			$dataEncoded = Json::encode($data);
			$hashDataEncoded = md5($dataEncoded);
			$timeExpires = DateTime::createFromTimestamp(time() + (int)Library::CACHE_TIME_INFO_CONNECTORS_LINE);

			$connectorsInfo = [
				'DATA' => $dataEncoded,
				'EXPIRES' => $timeExpires,
				'DATA_HASH' => $hashDataEncoded,
			];

			$connectorRes = InfoConnectorsTable::getByPrimary($lineId);
			$connectorsInfoCount = $connectorRes->getSelectedRowsCount();
			if ($connectorsInfoCount)
			{
				$updateResult = InfoConnectorsTable::update($lineId, $connectorsInfo);
				if (!$updateResult->isSuccess())
				{
					$result->addErrors($updateResult->getErrors());
				}
			}
			else
			{
				$connectorsInfo['LINE_ID'] = $lineId;
				$addResult = InfoConnectorsTable::add($connectorsInfo);
				if (!$addResult->isSuccess())
				{
					$result->addErrors($addResult->getErrors());
				}
			}
		}
		else
		{
			self::deleteInfoConnectors($lineId);
		}

		return $result;
	}

	/**
	 * Method for deleting single connection info element for single line
	 *
	 * @param $lineId
	 *
	 * @return bool
	 */
	public static function deleteInfoConnectors($lineId): bool
	{
		$result = InfoConnectorsTable::delete($lineId);
		self::clearInfoConnectorsLineCache($lineId);

		return $result->isSuccess();
	}

	/**
	 * Rewrite info connectors line local cache
	 *
	 * @param $lineId
	 * @param $data
	 */
	private static function rewriteInfoConnectorsLineCache($lineId, $data): void
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
	private static function clearInfoConnectorsLineCache($lineId): void
	{
		$cache = Cache::createInstance();
		$cache->clean($lineId, Library::CACHE_DIR_INFO_CONNECTORS_LINE);
	}
}