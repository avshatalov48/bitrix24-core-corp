<?php
namespace Bitrix\ImConnector\Rest;

use Bitrix\Main\Loader,
	Bitrix\Main\Data\Cache
;

use Bitrix\Rest\OAuth\Auth,
	Bitrix\Rest\AuthTypeException,
	Bitrix\Rest\Exceptions\ArgumentNullException
;

use Bitrix\ImConnector\Library,
	Bitrix\ImConnector\InfoConnectors,
	Bitrix\ImConnector\Status as StatusConnector
;

if(Loader::includeModule('rest'))
{
	/**
	 * Class Status
	 * @package Bitrix\ImConnector\Rest
	 */
	class Status extends \IRestService
	{
		/**
		 * @return array
		 */
		public static function onRestServiceBuildDescription(): array
		{
			return [
				Library::SCOPE_REST_IMCONNECTOR => [
					'imconnector.activate' => [
						'callback' => [__CLASS__, 'activate'],
						'options' => []
					],
					'imconnector.status' => [
						'callback' => [__CLASS__, 'getStatus'],
						'options' => []
					],
					'imconnector.connector.data.set' => [
						'callback' => [__CLASS__, 'connectorDataSet'],
						'options' => []
					],
				],
			];
		}

		/**
		 * Reset cache components
		 *
		 * @param $connector
		 * @param $line
		 */
		protected static function cleanCache($connector, $line): void
		{
			$cacheId = serialize([$connector, $line]);

			$cache = Cache::createInstance();
			$cache->clean($cacheId, Library::CACHE_DIR_COMPONENT);
			$cache->clean($line, Library::CACHE_DIR_INFO_CONNECTORS_LINE);

			InfoConnectors::addSingleLineUpdateAgent($line);
		}

		/**
		 * Connector activation and deactivation.
		 *
		 * @param $params
		 * @param $n
		 * @param \CRestServer $server
		 * @return bool
		 * @throws ArgumentNullException
		 * @throws AuthTypeException
		 */
		public static function activate($params, $n, \CRestServer $server): bool
		{
			$result = true;

			$params = array_change_key_case($params, CASE_UPPER);

			if ($server->getAuthType() !== Auth::AUTH_TYPE)
			{
				throw new AuthTypeException("Application context required");
			}

			if (!isset($params['CONNECTOR']))
			{
				throw new ArgumentNullException("CONNECTOR");
			}

			if (!isset($params['LINE']))
			{
				throw new ArgumentNullException("LINE");
			}

			if (!isset($params['ACTIVE']))
			{
				throw new ArgumentNullException("ACTIVE");
			}

			if (!empty($params['ACTIVE']))
			{
				$status = StatusConnector::getInstance($params['CONNECTOR'], (int)$params['LINE']);
				$status
					->setActive(true)
					->setConnection(true)
					->setRegister(true)
					->setError(false);

				$app = \Bitrix\Rest\AppTable::getByClientId($server->getClientId());
				if ($app['CODE'])
				{
					$id = uniqid($app['CODE'], true);
					AddEventToStatFile(
						'imconnector',
						'activateRestConnector',
						$id,
						$app['CODE'],
						'appCode'
					);
					AddEventToStatFile(
						'imconnector',
						'activateRestConnector',
						$id,
						$params['CONNECTOR'],
						'connectorCode'
					);
				}
			}
			else
			{
				$result = StatusConnector::delete($params['CONNECTOR'], (int)$params['LINE']);
			}

			self::cleanCache($params['CONNECTOR'], $params['LINE']);

			return $result;
		}

		/**
		 * @param $params
		 * @param $n
		 * @param \CRestServer $server
		 * @return array
		 * @throws ArgumentNullException
		 * @throws AuthTypeException
		 */
		public static function getStatus($params, $n, \CRestServer $server): array
		{
			$params = array_change_key_case($params, CASE_UPPER);

			if ($server->getAuthType() !== Auth::AUTH_TYPE)
			{
				throw new AuthTypeException("Application context required");
			}

			if (!isset($params['CONNECTOR']))
			{
				throw new ArgumentNullException("CONNECTOR");
			}

			$status = StatusConnector::getInstance($params['CONNECTOR'], (int)$params['LINE']);

			return [
				'LINE' => $status->getLine(),
				'CONNECTOR' => $status->getconnector(),
				'ERROR' => $status->getError(),
				'CONFIGURED' => $status->isConfigured(),
				'STATUS' => $status->isStatus(),
			];
		}

		/**
		 * Set infoConnection for current connector in line
		 *
		 * @param $params
		 * @param $n
		 * @param \CRestServer $server
		 *
		 * @return bool
		 * @throws ArgumentNullException
		 * @throws AuthTypeException
		 */
		public static function connectorDataSet($params, $n, \CRestServer $server): bool
		{
			$params = array_change_key_case($params, CASE_UPPER);

			if ($server->getAuthType() !== Auth::AUTH_TYPE)
			{
				throw new AuthTypeException("Application context required");
			}

			if (!isset($params['CONNECTOR']))
			{
				throw new ArgumentNullException("CONNECTOR");
			}

			if (!isset($params['LINE']))
			{
				throw new ArgumentNullException("LINE");
			}

			if (!isset($params['DATA']))
			{
				throw new ArgumentNullException("DATA");
			}

			$params['DATA'] = array_change_key_case($params['DATA'], CASE_UPPER);

			$data = [
				'connector_id' => $params['CONNECTOR']
			];

			if (!empty($params['DATA']['ID']))
			{
				$data['id'] = $params['DATA']['ID'];
			}
			if (!empty($params['DATA']['URL']))
			{
				$data['url'] = $params['DATA']['URL'];
			}
			if (!empty($params['DATA']['URL_IM']))
			{
				$data['url_im'] = $params['DATA']['URL_IM'];
			}
			if (!empty($params['DATA']['NAME']))
			{
				$data['name'] = $params['DATA']['NAME'];
			}

			$status = StatusConnector::getInstance($params['CONNECTOR'], (int)$params['LINE']);
			$oldData = $status->getData();

			if (!empty($oldData) && is_array($oldData))
			{
				$data = array_merge($oldData, $data);
			}

			$status->setData($data);
			self::cleanCache($params['CONNECTOR'], $params['LINE']);

			return true;
		}
	}
}