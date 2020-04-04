<?php
namespace Bitrix\ImConnector\Rest;

use \Bitrix\Main\Loader,
	\Bitrix\Main\Data\Cache;

use \Bitrix\Rest\OAuth\Auth,
	\Bitrix\Rest\AuthTypeException,
	\Bitrix\Main\ArgumentNullException;

use \Bitrix\ImConnector\Library,
	\Bitrix\ImConnector\InfoConnectors,
	\Bitrix\ImConnector\Status as StatusConnector;

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
		public static function onRestServiceBuildDescription()
		{
			return array(
				Library::SCOPE_REST_IMCONNECTOR => array(
					'imconnector.activate' => array(
						'callback' => array(__CLASS__, 'activate'),
						'options' => array()
					),
					'imconnector.status' => array(
						'callback' => array(__CLASS__, 'getStatus'),
						'options' => array()
					),
					'imconnector.connector.data.set' => array(
						'callback' => array(__CLASS__, 'connectorDataSet'),
						'options' => array()
					),
				),
			);
		}

		/**
		 * Reset cache components
		 *
		 * @param $connector
		 * @param $line
		 */
		protected static function cleanCache($connector, $line)
		{
			$cacheId = serialize(array($connector, $line));

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
		public static function activate($params, $n, \CRestServer $server)
		{
			$result = true;

			$params = array_change_key_case($params, CASE_UPPER);

			if($server->getAuthType() !== Auth::AUTH_TYPE)
			{
				throw new AuthTypeException("Application context required");
			}

			if(!isset($params['CONNECTOR']))
			{
				throw new ArgumentNullException("CONNECTOR");
			}

			if(!isset($params['LINE']))
			{
				throw new ArgumentNullException("LINE");
			}

			if(!isset($params['ACTIVE']))
			{
				throw new ArgumentNullException("ACTIVE");
			}

			if(!empty($params['ACTIVE']))
			{
				$status = StatusConnector::getInstance($params['CONNECTOR'], $params['LINE']);
				$status->setActive(true);
				$status->setConnection(true);
				$status->setRegister(true);
				$status->setError(false);
			}
			else
			{
				$result = StatusConnector::delete($params['CONNECTOR'], $params['LINE']);
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
		public static function getStatus($params, $n, \CRestServer $server)
		{
			$params = array_change_key_case($params, CASE_UPPER);

			if($server->getAuthType() !== Auth::AUTH_TYPE)
			{
				throw new AuthTypeException("Application context required");
			}

			if(!isset($params['CONNECTOR']))
			{
				throw new ArgumentNullException("CONNECTOR");
			}

			$status = StatusConnector::getInstance($params['CONNECTOR'], $params['LINE']);

			$result = array(
				'LINE' => $status->getLine(),
				'CONNECTOR' => $status->getconnector(),
				'ERROR' => $status->getError(),
				'CONFIGURED' => $status->isConfigured(),
				'STATUS' => $status->isStatus(),
			);

			return $result;
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
		public static function connectorDataSet($params, $n, \CRestServer $server)
		{
			$params = array_change_key_case($params, CASE_UPPER);

			if($server->getAuthType() !== Auth::AUTH_TYPE)
			{
				throw new AuthTypeException("Application context required");
			}

			if(!isset($params['CONNECTOR']))
			{
				throw new ArgumentNullException("CONNECTOR");
			}

			if(!isset($params['LINE']))
			{
				throw new ArgumentNullException("LINE");
			}

			if(!isset($params['DATA']))
			{
				throw new ArgumentNullException("DATA");
			}

			$params['DATA'] = array_change_key_case($params['DATA'], CASE_UPPER);

			$data = [
				'connector_id' => $params['CONNECTOR']
			];

			if(!empty($params['DATA']['ID']))
				$data['id'] = $params['DATA']['ID'];
			if(!empty($params['DATA']['URL']))
				$data['url'] = $params['DATA']['URL'];
			if(!empty($params['DATA']['URL_IM']))
				$data['url_im'] = $params['DATA']['URL_IM'];
			if(!empty($params['DATA']['NAME']))
				$data['name'] = $params['DATA']['NAME'];

			$status = StatusConnector::getInstance($params['CONNECTOR'], $params['LINE']);
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