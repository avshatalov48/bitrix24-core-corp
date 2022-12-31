<?php
namespace Bitrix\ImConnector\Rest;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentTypeException;
use \Bitrix\Main\Loader,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\Main\ArgumentNullException,
	\Bitrix\Main\Engine\Response\Converter;

use \Bitrix\Rest\AppTable,
	\Bitrix\Rest\OAuth\Auth,
	\Bitrix\Rest\Sqs as RestSqs,
	\Bitrix\Rest\AuthTypeException,
	\Bitrix\Rest\RestException;

use \Bitrix\ImConnector\Library,
	\Bitrix\ImConnector\CustomConnectors as CC,
	\Bitrix\ImConnector\Model\CustomConnectorsTable;

Loc::loadMessages(__FILE__);
Library::loadMessages();

if (Loader::includeModule('rest'))
{
	/**
	 * Class CustomConnectors
	 * @package Bitrix\ImConnector\Rest
	 */
	class CustomConnectors extends \IRestService
	{
		/**
		 * @return array
		 */
		public static function onRestServiceBuildDescription()
		{
			return array(
				Library::SCOPE_REST_IMCONNECTOR => array(
					'imconnector.register' => array(
						'callback' => array(__CLASS__, 'register'),
						'options' => array()
					),
					'imconnector.unregister' => array(
						'callback' => array(__CLASS__, 'unRegister'),
						'options' => array()
					),
					'imconnector.send.messages' => array(
						'callback' => array(__CLASS__, 'sendMessages'),
						'options' => array()
					),
					'imconnector.update.messages' => array(
						'callback' => array(__CLASS__, 'updateMessages'),
						'options' => array()
					),
					'imconnector.delete.messages' => array(
						'callback' => array(__CLASS__, 'deleteMessages'),
						'options' => array()
					),
					'imconnector.send.status.delivery' => array(
						'callback' => array(__CLASS__, 'sendStatusDelivery'),
						'options' => array()
					),
					'imconnector.send.status.reading' => array(
						'callback' => array(__CLASS__, 'sendStatusReading'),
						'options' => array()
					),
					'imconnector.set.error' => array(
						'callback' => array(__CLASS__, 'setErrorConnector'),
						'options' => array()
					),
					\CRestUtil::EVENTS => array(
						'OnImConnectorLineDelete' => array(
							'imconnector',
							Library::EVENT_DELETE_LINE,
							array(__CLASS__, 'OnDeleteLine'),
							array(
								"category" => RestSqs::CATEGORY_DEFAULT,
							)
						),
						'OnImConnectorMessageAdd' => array(
							'imconnector',
							Library::EVENT_SEND_MESSAGE_CUSTOM_CONNECTOR,
							array(__CLASS__, 'OnSendMessageCustom'),
							array(
								"category" => RestSqs::CATEGORY_DEFAULT,
							)
						),
						'OnImConnectorMessageUpdate' => array(
							'imconnector',
							Library::EVENT_UPDATE_MESSAGE_CUSTOM_CONNECTOR,
							array(__CLASS__, 'OnUpdateMessageCustom'),
							array(
								"category" => RestSqs::CATEGORY_DEFAULT,
							)
						),
						'OnImConnectorMessageDelete' => array(
							'imconnector',
							Library::EVENT_DELETE_MESSAGE_CUSTOM_CONNECTOR,
							array(__CLASS__, 'OnDeleteMessageCustom'),
							array(
								"category" => RestSqs::CATEGORY_DEFAULT,
							)
						),
						/*'OnImConnectorStatusAdd' => array(
							'imconnector',
							Library::EVENT_STATUS_ADD,
							array(__CLASS__, 'OnStatusCustom'),
							array(
								"category" => RestSqs::CATEGORY_DEFAULT,
							)
						),
						'OnImConnectorStatusUpdate' => array(
							'imconnector',
							Library::EVENT_STATUS_UPDATE,
							array(__CLASS__, 'OnStatusCustom'),
							array(
								"category" => RestSqs::CATEGORY_DEFAULT,
							)
						),*/
						'OnImConnectorStatusDelete' => array(
							'imconnector',
							Library::EVENT_STATUS_DELETE,
							array(__CLASS__, 'OnStatusCustom'),
							array(
								"category" => RestSqs::CATEGORY_DEFAULT,
							)
						),
					),
					\CRestUtil::PLACEMENTS => array(
						Helper::PLACEMENT_SETTING_CONNECTOR => array(),
					),
				),
			);
		}

		/**
		 * @param $params
		 * @param $arHandler
		 * @return mixed
		 */
		public static function OnDeleteLine($params, $arHandler)
		{
			$parameters = $params[0]->getParameters();

			return $parameters['LINE_ID'];
		}

		/**
		 * @param $params
		 * @param $arHandler
		 * @return mixed
		 * @throws RestException
		 */
		public static function OnStatusCustom($params, $arHandler)
		{
			$appId = null;
			$parameters = $params[0]->getParameters();

			if (!empty($parameters['connector']))
			{
				$appId = Helper::getAppRestConnector($parameters['connector']);
			}

			if (empty($appId) || ($arHandler['APP_ID'] != $appId && $arHandler['APP_CODE'] != $appId))
			{
				throw new RestException('Wrong app!', "WRONG_APP_ID", \CRestServer::STATUS_WRONG_REQUEST);
			}

			return $parameters;
		}

		/**
		 * @param $params
		 * @param $arHandler
		 * @return mixed
		 * @throws RestException
		 */
		public static function OnSendMessageCustom($params, $arHandler)
		{
			$appId = null;

			$parameters = $params[0]->getParameters();

			if (!empty($parameters['CONNECTOR']))
			{
				$appId = Helper::getAppRestConnector($parameters['CONNECTOR']);
			}

			if (!empty($appId) & ($arHandler['APP_ID'] == $appId || $arHandler['APP_CODE'] == $appId))
			{
				if (isset($parameters['DATA']))
				{
					$parameters['MESSAGES'] = $parameters['DATA'];
					unset($parameters['DATA']);
				}
			}
			else
			{
				throw new RestException('Wrong app!', "WRONG_APP_ID", \CRestServer::STATUS_WRONG_REQUEST);
			}

			return $parameters;
		}

		/**
		 * @param $params
		 * @param $arHandler
		 * @return mixed
		 * @throws RestException
		 */
		public static function OnUpdateMessageCustom($params, $arHandler)
		{
			$appId = null;

			$parameters = $params[0]->getParameters();

			if (!empty($parameters['CONNECTOR']))
			{
				$appId = Helper::getAppRestConnector($parameters['CONNECTOR']);
			}

			if (!empty($appId) & ($arHandler['APP_ID'] == $appId || $arHandler['APP_CODE'] == $appId))
			{
				if (isset($parameters['DATA']))
				{
					$parameters['MESSAGES'] = $parameters['DATA'];
					unset($parameters['DATA']);
				}
			}
			else
			{
				throw new RestException('Wrong app!', "WRONG_APP_ID", \CRestServer::STATUS_WRONG_REQUEST);
			}

			return $parameters;
		}

		/**
		 * @param $params
		 * @param $arHandler
		 * @return mixed
		 * @throws RestException
		 */
		public static function OnDeleteMessageCustom($params, $arHandler)
		{
			$appId = null;

			$parameters = $params[0]->getParameters();

			if (!empty($parameters['CONNECTOR']))
			{
				$appId = Helper::getAppRestConnector($parameters['CONNECTOR']);
			}

			if (!empty($appId) & ($arHandler['APP_ID'] == $appId || $arHandler['APP_CODE'] == $appId))
			{
				if (isset($parameters['DATA']))
				{
					$parameters['MESSAGES'] = $parameters['DATA'];
					unset($parameters['DATA']);
				}
			}
			else
			{
				throw new RestException('Wrong app!', "WRONG_APP_ID", \CRestServer::STATUS_WRONG_REQUEST);
			}

			return $parameters;
		}

		/**
		 * @param $params
		 * @param $n
		 * @param \CRestServer $server
		 * @return array
		 * @throws AuthTypeException
		 */
		public static function register($params, $n, \CRestServer $server)
		{
			if ($server->getAuthType() !== Auth::AUTH_TYPE)
			{
				throw new AuthTypeException("Application context required");
			}

			$result = array(
				'result' => false
			);

			$converter = new Converter(Converter::TO_UPPER | Converter::KEYS | Converter::RECURSIVE);
			$params = $converter->process($params);

			$clientId = $server->getClientId();
			$row = AppTable::getByClientId($clientId);
			$appId = $row['ID'];

			if (mb_strpos($params['ID'], '.') !== false)
			{
				$result = array(
					'result' => false,
					'error' => Library::ERROR_IMCONNECTOR_REST_APPLICATION_REGISTRATION_ERROR_POINT,
					'error_description' => Loc::getMessage('IMCONNECTOR_REST_APPLICATION_REGISTRATION_ERROR_POINT')
				);
			}
			else if (
				!empty($params['ID'])
				&& !empty($params['NAME'])
				&& !empty($params['ICON']['DATA_IMAGE'])
				&& !empty($appId)
				&& !empty($params['PLACEMENT_HANDLER'])
			)
			{
				$registerParams = array(
					'ID' => mb_strtolower($params['ID']),
					'NAME' => $params['NAME'],
					'ICON' => $params['ICON'],
					'COMPONENT' => Library::COMPONENT_NAME_REST,
					'REST_APP_ID' => $appId,
					'PLACEMENT_HANDLER' => $params['PLACEMENT_HANDLER']
				);

				if (isset($params['ICON_DISABLED']))
				{
					$registerParams['ICON_DISABLED'] = $params['ICON_DISABLED'];
				}
				if (isset($params['DEL_EXTERNAL_MESSAGES']))
				{
					$registerParams['DEL_EXTERNAL_MESSAGES'] = $params['DEL_EXTERNAL_MESSAGES'];
				}
				if (isset($params['EDIT_INTERNAL_MESSAGES']))
				{
					$registerParams['EDIT_INTERNAL_MESSAGES'] = $params['EDIT_INTERNAL_MESSAGES'];
				}
				if (isset($params['DEL_INTERNAL_MESSAGES']))
				{
					$registerParams['DEL_INTERNAL_MESSAGES'] = $params['DEL_INTERNAL_MESSAGES'];
				}
				if (isset($params['NEWSLETTER']))
				{
					$registerParams['NEWSLETTER'] = $params['NEWSLETTER'];
				}
				if (isset($params['NEED_SYSTEM_MESSAGES']))
				{
					$registerParams['NEED_SYSTEM_MESSAGES'] = $params['NEED_SYSTEM_MESSAGES'];
				}
				if (isset($params['NEED_SIGNATURE']))
				{
					$registerParams['NEED_SIGNATURE'] = $params['NEED_SIGNATURE'];
				}
				if (isset($params['CHAT_GROUP']))
				{
					$registerParams['CHAT_GROUP'] = $params['CHAT_GROUP'];
				}
				if (isset($params['COMMENT']))
				{
					$registerParams['COMMENT'] = $params['COMMENT'];
				}

				if (Helper::registerApp($registerParams))
				{
					$result = array(
						'result' => true
					);

					if ($row['CODE'])
					{
						$id = uniqid($row['CODE'], true);
						AddEventToStatFile(
							'imconnector',
							'registerRestConnector',
							$id,
							$row['CODE'],
							'appCode'
						);
						AddEventToStatFile(
							'imconnector',
							'registerRestConnector',
							$id,
							$registerParams['ID'],
							'connectorCode'
						);
					}
				}
				else
				{
					$result = array(
						'result' => false,
						'error' => Library::ERROR_IMCONNECTOR_REST_APPLICATION_REGISTRATION_ERROR,
						'error_description' => Loc::getMessage('IMCONNECTOR_REST_APPLICATION_REGISTRATION_ERROR')
					);
				}
			}
			else if (empty($params['ID']))
			{
				$result = array(
					'result' => false,
					'error' => Library::ERROR_IMCONNECTOR_REST_CONNECTOR_ID_REQUIRED,
					'error_description' => Loc::getMessage('IMCONNECTOR_REST_CONNECTOR_ID_REQUIRED')
				);
			}
			else if (empty($params['NAME']))
			{
				$result = array(
					'result' => false,
					'error' => Library::ERROR_IMCONNECTOR_REST_NAME_REQUIRED,
					'error_description' => Loc::getMessage('IMCONNECTOR_REST_NAME_REQUIRED')
				);
			}
			else if (empty($params['ICON']['DATA_IMAGE']))
			{
				$result = array(
					'result' => false,
					'error' => Library::ERROR_IMCONNECTOR_REST_ICON_REQUIRED,
					'error_description' => Loc::getMessage('IMCONNECTOR_REST_ICON_REQUIRED')
				);
			}
			else if (empty($appId))
			{
				$result = array(
					'result' => false,
					'error' => Library::ERROR_IMCONNECTOR_REST_NO_APPLICATION_ID,
					'error_description' => Loc::getMessage('IMCONNECTOR_REST_NO_APPLICATION_ID')
				);
			}
			else if (empty($params['PLACEMENT_HANDLER']))
			{
				$result = array(
					'result' => false,
					'error' => Library::ERROR_IMCONNECTOR_REST_NO_PLACEMENT_HANDLER,
					'error_description' => Loc::getMessage('IMCONNECTOR_REST_NO_PLACEMENT_HANDLER')
				);
			}
			else
			{
				$result = array(
					'result' => false,
					'error' => Library::ERROR_IMCONNECTOR_REST_GENERAL_CONNECTOR_REGISTRATION_ERROR,
					'error_description' => Loc::getMessage('IMCONNECTOR_REST_GENERAL_CONNECTOR_REGISTRATION_ERROR')
				);
			}

			return $result;
		}

		/**
		 * @param $params
		 * @param $n
		 * @param \CRestServer $server
		 * @return array
		 * @throws AuthTypeException
		 */
		public static function unRegister($params, $n, \CRestServer $server)
		{
			if ($server->getAuthType() !== Auth::AUTH_TYPE)
			{
				throw new AuthTypeException("Application context required");
			}

			$result = array(
				'result' => false
			);

			$params = array_change_key_case($params, CASE_UPPER);

			$clientId = $server->getClientId();
			$row = AppTable::getByClientId($clientId);
			$appId = $row['ID'];

			if (!empty($appId))
			{
				if (!empty($params['ID']) && Helper::unRegisterApp(array(
					'ID' => $params['ID'],
					'REST_APP_ID' => $appId,
				)))
				{
					$result = array(
						'result' => true
					);
				}
				else
				{
					$result = array(
						'result' => false,
						'error' => Library::ERROR_IMCONNECTOR_REST_APPLICATION_UNREGISTRATION_ERROR,
						'error_description' => Loc::getMessage('IMCONNECTOR_REST_APPLICATION_UNREGISTRATION_ERROR')
					);
				}
			}
			else
			{
				$result = array(
					'result' => false,
					'error' => Library::ERROR_IMCONNECTOR_REST_NO_APPLICATION_ID,
					'error_description' => Loc::getMessage('IMCONNECTOR_REST_NO_APPLICATION_ID')
				);
			}

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
		public static function sendMessages($params, $n, \CRestServer $server)
		{
			$result = array();

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

			if (!isset($params['MESSAGES']))
			{
				throw new ArgumentNullException("MESSAGES");
			}

			$converter = new Converter(Converter::TO_LOWER | Converter::KEYS | Converter::RECURSIVE);
			$params['MESSAGES'] = $converter->process($params['MESSAGES']);

			if (!is_array($params['MESSAGES']))
			{
				throw new ArgumentTypeException("MESSAGES", 'array');
			}

			foreach ($params['MESSAGES'] as $message)
			{
				if (!is_array($message))
				{
					throw new ArgumentException('The MESSAGES parameter must be an array of messages (arrays)');
				}

				if (!isset($message['user'], $message['message'], $message['chat']))
				{
					throw new ArgumentException('The incorrect structure of a message inside MESSAGES parameter.');
				}
			}

			$resultSend = CC::sendMessages($params['CONNECTOR'], $params['LINE'], $params['MESSAGES']);

			if ($resultSend->isSuccess())
			{
				$result['SUCCESS'] = true;
			}
			else
			{
				$result['SUCCESS'] = false;
			}

			$result['DATA'] = $resultSend->getData();

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
		public static function updateMessages($params, $n, \CRestServer $server)
		{
			$result = array();

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

			if (!isset($params['MESSAGES']))
			{
				throw new ArgumentNullException("MESSAGES");
			}

			$converter = new Converter(Converter::TO_LOWER | Converter::KEYS | Converter::RECURSIVE);
			$params['MESSAGES'] = $converter->process($params['MESSAGES']);

			$resultSend = CC::updateMessages($params['CONNECTOR'], $params['LINE'], $params['MESSAGES']);

			if ($resultSend->isSuccess())
			{
				$result['SUCCESS'] = true;
			}
			else
			{
				$result['SUCCESS'] = false;
			}

			$result['DATA'] = $resultSend->getData();

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
		public static function deleteMessages($params, $n, \CRestServer $server)
		{
			$result = array();

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

			if (!isset($params['MESSAGES']))
			{
				throw new ArgumentNullException("MESSAGES");
			}

			$converter = new Converter(Converter::TO_LOWER | Converter::KEYS | Converter::RECURSIVE);
			$params['MESSAGES'] = $converter->process($params['MESSAGES']);

			$resultSend = CC::deleteMessages($params['CONNECTOR'], $params['LINE'], $params['MESSAGES']);

			if ($resultSend->isSuccess())
			{
				$result['SUCCESS'] = true;
			}
			else
			{
				$result['SUCCESS'] = false;
			}

			$result['DATA'] = $resultSend->getData();

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
		public static function sendStatusDelivery($params, $n, \CRestServer $server)
		{
			$result = array();

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

			if (!isset($params['MESSAGES']))
			{
				throw new ArgumentNullException("MESSAGES");
			}

			$converter = new Converter(Converter::TO_LOWER | Converter::KEYS | Converter::RECURSIVE);
			$params['MESSAGES'] = $converter->process($params['MESSAGES']);

			$resultSend = CC::sendStatusDelivery($params['CONNECTOR'], $params['LINE'], $params['MESSAGES']);

			if ($resultSend->isSuccess())
			{
				$result['SUCCESS'] = true;
			}
			else
			{
				$result['SUCCESS'] = false;
			}

			$result['DATA'] = $resultSend->getData();

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
		public static function sendStatusReading($params, $n, \CRestServer $server)
		{
			$result = array();

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

			if (!isset($params['MESSAGES']))
			{
				throw new ArgumentNullException("MESSAGES");
			}

			$converter = new Converter(Converter::TO_LOWER | Converter::KEYS | Converter::RECURSIVE);
			$params['MESSAGES'] = $converter->process($params['MESSAGES']);

			$resultSend = CC::sendStatusReading($params['CONNECTOR'], $params['LINE'], $params['MESSAGES']);

			if ($resultSend->isSuccess())
			{
				$result['SUCCESS'] = true;
			}
			else
			{
				$result['SUCCESS'] = false;
			}

			$result['DATA'] = $resultSend->getData();

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
		public static function setErrorConnector($params, $n, \CRestServer $server)
		{
			$result = array();

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

			$resultSend = CC::deactivateConnectors($params['CONNECTOR'], $params['LINE']);

			if ($resultSend->isSuccess())
			{
				$result['SUCCESS'] = true;
			}
			else
			{
				$result['SUCCESS'] = false;
			}

			$result['DATA'] = $resultSend->getData();

			return $result;
		}

		public static function OnRestAppDelete($arParams): void
		{
			if (!empty($arParams['APP_ID']))
			{
				$raw = CustomConnectorsTable::getList([
					'select' => ['ID', 'REST_APP_ID', 'ID_CONNECTOR'],
					'filter' => [
						'=REST_APP_ID' => $arParams['APP_ID']
					]
				]);
				while ($row = $raw->fetch())
				{
					Helper::unRegisterApp([
						'ID' => $row['ID_CONNECTOR'],
						'REST_APP_ID' => $row['REST_APP_ID'],
					]);
				}
			}
		}
	}
}