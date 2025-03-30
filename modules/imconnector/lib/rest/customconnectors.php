<?php
namespace Bitrix\ImConnector\Rest;

use Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Engine\Response\Converter
;

use Bitrix\Rest\AppTable,
	Bitrix\Rest\OAuth\Auth,
	Bitrix\Rest\Sqs as RestSqs,
	Bitrix\Rest\RestException,
	Bitrix\Rest\AuthTypeException,
	Bitrix\Rest\Exceptions\ArgumentException,
	Bitrix\Rest\Exceptions\ArgumentTypeException,
	Bitrix\Rest\Exceptions\ArgumentNullException
;
use Bitrix\ImConnector\Library,
	Bitrix\ImConnector\CustomConnectors as CC,
	Bitrix\ImConnector\Model\CustomConnectorsTable
;


if (Loader::includeModule('rest'))
{
	Loc::loadMessages(__FILE__);
	Library::loadMessages();

	/**
	 * Class CustomConnectors
	 * @package Bitrix\ImConnector\Rest
	 */
	class CustomConnectors extends \IRestService
	{
		/**
		 * @return array
		 */
		public static function onRestServiceBuildDescription(): array
		{
			return [
				Library::SCOPE_REST_IMCONNECTOR => [
					'imconnector.register' => [
						'callback' => [__CLASS__, 'register'],
						'options' => []
					],
					'imconnector.unregister' => [
						'callback' => [__CLASS__, 'unRegister'],
						'options' => []
					],
					'imconnector.send.messages' => [
						'callback' => [__CLASS__, 'sendMessages'],
						'options' => []
					],
					'imconnector.update.messages' => [
						'callback' => [__CLASS__, 'updateMessages'],
						'options' => []
					],
					'imconnector.delete.messages' => [
						'callback' => [__CLASS__, 'deleteMessages'],
						'options' => []
					],
					'imconnector.send.status.delivery' => [
						'callback' => [__CLASS__, 'sendStatusDelivery'],
						'options' => []
					],
					'imconnector.send.status.reading' => [
						'callback' => [__CLASS__, 'sendStatusReading'],
						'options' => []
					],
					'imconnector.set.error' => [
						'callback' => [__CLASS__, 'setErrorConnector'],
						'options' => []
					],
					'imconnector.chat.name.set' => [
						'callback' => [__CLASS__, 'setChatName'],
						'options' => []
					],
					\CRestUtil::EVENTS => [
						'OnImConnectorLineDelete' => [
							'imconnector',
							Library::EVENT_DELETE_LINE,
							[__CLASS__, 'OnDeleteLine'],
							[
								"category" => RestSqs::CATEGORY_DEFAULT,
							]
						],
						'OnImConnectorMessageAdd' => [
							'imconnector',
							Library::EVENT_SEND_MESSAGE_CUSTOM_CONNECTOR,
							[__CLASS__, 'OnSendMessageCustom'],
							[
								"category" => RestSqs::CATEGORY_DEFAULT,
							]
						],
						'OnImConnectorMessageUpdate' => [
							'imconnector',
							Library::EVENT_UPDATE_MESSAGE_CUSTOM_CONNECTOR,
							[__CLASS__, 'OnUpdateMessageCustom'],
							[
								"category" => RestSqs::CATEGORY_DEFAULT,
							]
						],
						'OnImConnectorMessageDelete' => [
							'imconnector',
							Library::EVENT_DELETE_MESSAGE_CUSTOM_CONNECTOR,
							[__CLASS__, 'OnDeleteMessageCustom'],
							[
								"category" => RestSqs::CATEGORY_DEFAULT,
							]
						],
						/*'OnImConnectorStatusAdd' => [
							'imconnector',
							Library::EVENT_STATUS_ADD,
							[__CLASS__, 'OnStatusCustom'],
							[
								"category" => RestSqs::CATEGORY_DEFAULT,
							]
						],
						'OnImConnectorStatusUpdate' => [
							'imconnector',
							Library::EVENT_STATUS_UPDATE,
							[__CLASS__, 'OnStatusCustom'],
							[
								"category" => RestSqs::CATEGORY_DEFAULT,
							]
						],*/
						'OnImConnectorStatusDelete' => [
							'imconnector',
							Library::EVENT_STATUS_DELETE,
							[__CLASS__, 'OnStatusCustom'],
							[
								"category" => RestSqs::CATEGORY_DEFAULT,
							]
						],
						'OnImConnectorDialogStart' => [
							'imconnector',
							Library::EVENT_DIALOG_START,
							[__CLASS__, 'OnStartDialog'],
							[
								"category" => RestSqs::CATEGORY_DEFAULT,
							]
						],
						'OnImConnectorDialogFinish' => [
							'imconnector',
							Library::EVENT_DIALOG_FINISH,
							[__CLASS__, 'OnFinishDialog'],
							[
								"category" => RestSqs::CATEGORY_DEFAULT,
							]
						],
					],
					\CRestUtil::PLACEMENTS => [
						Helper::PLACEMENT_SETTING_CONNECTOR => [],
					],
				],
			];
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
		 */
		public static function OnStartDialog($params, $arHandler)
		{
			$appId = null;
			$parameters = $params[0]->getParameters();

			if (!empty($parameters['CONNECTOR']))
			{
				$appId = Helper::getAppRestConnector($parameters['CONNECTOR']);
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
		 */
		public static function OnFinishDialog($params, $arHandler)
		{
			$appId = null;
			$parameters = $params[0]->getParameters();

			if (!empty($parameters['CONNECTOR']))
			{
				$appId = Helper::getAppRestConnector($parameters['CONNECTOR']);
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
		public static function register($params, $n, \CRestServer $server): array
		{
			if ($server->getAuthType() !== Auth::AUTH_TYPE)
			{
				throw new AuthTypeException("Application context required");
			}

			$converter = new Converter(Converter::TO_UPPER | Converter::KEYS | Converter::RECURSIVE);
			$params = $converter->process($params);

			$clientId = $server->getClientId();
			$row = AppTable::getByClientId($clientId);
			$appId = $row['ID'];

			if (mb_strpos($params['ID'], '.') !== false)
			{
				$result = [
					'result' => false,
					'error' => Library::ERROR_IMCONNECTOR_REST_APPLICATION_REGISTRATION_ERROR_POINT,
					'error_description' => Loc::getMessage('IMCONNECTOR_REST_APPLICATION_REGISTRATION_ERROR_POINT')
				];
			}
			elseif (
				!empty($params['ID'])
				&& !empty($params['NAME'])
				&& !empty($params['ICON']['DATA_IMAGE'])
				&& !empty($appId)
				&& !empty($params['PLACEMENT_HANDLER'])
			)
			{
				$registerParams = [
					'ID' => mb_strtolower($params['ID']),
					'NAME' => $params['NAME'],
					'ICON' => $params['ICON'],
					'COMPONENT' => Library::COMPONENT_NAME_REST,
					'REST_APP_ID' => $appId,
					'PLACEMENT_HANDLER' => $params['PLACEMENT_HANDLER']
				];

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
					$result = [
						'result' => true
					];

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
					$result = [
						'result' => false,
						'error' => Library::ERROR_IMCONNECTOR_REST_APPLICATION_REGISTRATION_ERROR,
						'error_description' => Loc::getMessage('IMCONNECTOR_REST_APPLICATION_REGISTRATION_ERROR')
					];
				}
			}
			elseif (empty($params['ID']))
			{
				$result = [
					'result' => false,
					'error' => Library::ERROR_IMCONNECTOR_REST_CONNECTOR_ID_REQUIRED,
					'error_description' => Loc::getMessage('IMCONNECTOR_REST_CONNECTOR_ID_REQUIRED')
				];
			}
			elseif (empty($params['NAME']))
			{
				$result = [
					'result' => false,
					'error' => Library::ERROR_IMCONNECTOR_REST_NAME_REQUIRED,
					'error_description' => Loc::getMessage('IMCONNECTOR_REST_NAME_REQUIRED')
				];
			}
			elseif (empty($params['ICON']['DATA_IMAGE']))
			{
				$result = [
					'result' => false,
					'error' => Library::ERROR_IMCONNECTOR_REST_ICON_REQUIRED,
					'error_description' => Loc::getMessage('IMCONNECTOR_REST_ICON_REQUIRED')
				];
			}
			elseif (empty($appId))
			{
				$result = [
					'result' => false,
					'error' => Library::ERROR_IMCONNECTOR_REST_NO_APPLICATION_ID,
					'error_description' => Loc::getMessage('IMCONNECTOR_REST_NO_APPLICATION_ID')
				];
			}
			elseif (empty($params['PLACEMENT_HANDLER']))
			{
				$result = [
					'result' => false,
					'error' => Library::ERROR_IMCONNECTOR_REST_NO_PLACEMENT_HANDLER,
					'error_description' => Loc::getMessage('IMCONNECTOR_REST_NO_PLACEMENT_HANDLER')
				];
			}
			else
			{
				$result = [
					'result' => false,
					'error' => Library::ERROR_IMCONNECTOR_REST_GENERAL_CONNECTOR_REGISTRATION_ERROR,
					'error_description' => Loc::getMessage('IMCONNECTOR_REST_GENERAL_CONNECTOR_REGISTRATION_ERROR')
				];
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
		public static function unRegister($params, $n, \CRestServer $server): array
		{
			if ($server->getAuthType() !== Auth::AUTH_TYPE)
			{
				throw new AuthTypeException("Application context required");
			}

			$params = array_change_key_case($params, CASE_UPPER);

			$clientId = $server->getClientId();
			$row = AppTable::getByClientId($clientId);
			$appId = $row['ID'];

			if (!empty($appId))
			{
				if (!empty($params['ID']) && Helper::unRegisterApp([
					'ID' => $params['ID'],
					'REST_APP_ID' => $appId,
				]))
				{
					$result = [
						'result' => true
					];
				}
				else
				{
					$result = [
						'result' => false,
						'error' => Library::ERROR_IMCONNECTOR_REST_APPLICATION_UNREGISTRATION_ERROR,
						'error_description' => Loc::getMessage('IMCONNECTOR_REST_APPLICATION_UNREGISTRATION_ERROR')
					];
				}
			}
			else
			{
				$result = [
					'result' => false,
					'error' => Library::ERROR_IMCONNECTOR_REST_NO_APPLICATION_ID,
					'error_description' => Loc::getMessage('IMCONNECTOR_REST_NO_APPLICATION_ID')
				];
			}

			return $result;
		}

		/**
		 * @param $params
		 * @param $n
		 * @param \CRestServer $server
		 * @return array
		 * @throws ArgumentNullException
		 * @throws ArgumentTypeException
		 * @throws ArgumentException
		 * @throws AuthTypeException
		 * @throws RestException
		 */
		public static function sendMessages($params, $n, \CRestServer $server): array
		{
			$result = [];

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
				if (!empty($resultSend->getErrors()))
				{
					foreach ($resultSend->getErrors() as $error)
					{
						throw new RestException($error->getMessage(), $error->getCode(), \CRestServer::STATUS_WRONG_REQUEST);
					}
				}

				$result['SUCCESS'] = false;
			}

			$resultsData = $resultSend->getData();
			foreach ($resultsData['RESULT'] as $keyData => $resultData)
			{
				foreach ($resultData['eventResult'] as $eventResult)
				{
					/** @var \Bitrix\Main\EventResult $eventResult */
					if ($eventResult->getModuleId() === 'imopenlines')
					{
						$parameters = $eventResult->getParameters();
						if (isset($parameters['SESSION']))
						{
							$resultsData['RESULT'][$keyData]['session'] = $parameters['SESSION'];
						}
					}
				}
				unset($resultsData['RESULT'][$keyData]['eventResult']);
			}
			$result['DATA'] = $resultsData;

			return $result;
		}

		/**
		 * @param $params
		 * @param $n
		 * @param \CRestServer $server
		 * @return array
		 * @throws ArgumentNullException
		 * @throws AuthTypeException
		 * @throws RestException
		 */
		public static function updateMessages($params, $n, \CRestServer $server): array
		{
			$result = [];

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
				if (!empty($resultSend->getErrors()))
				{
					foreach ($resultSend->getErrors() as $error)
					{
						throw new RestException($error->getMessage(), $error->getCode(), \CRestServer::STATUS_WRONG_REQUEST);
					}
				}

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
		 * @throws RestException
		 */
		public static function deleteMessages($params, $n, \CRestServer $server): array
		{
			$result = [];

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
				if (!empty($resultSend->getErrors()))
				{
					foreach ($resultSend->getErrors() as $error)
					{
						throw new RestException($error->getMessage(), $error->getCode(), \CRestServer::STATUS_WRONG_REQUEST);
					}
				}

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
		 * @throws RestException
		 */
		public static function sendStatusDelivery($params, $n, \CRestServer $server): array
		{
			$result = [];

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
				if (!empty($resultSend->getErrors()))
				{
					foreach ($resultSend->getErrors() as $error)
					{
						throw new RestException($error->getMessage(), $error->getCode(), \CRestServer::STATUS_WRONG_REQUEST);
					}
				}

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
		 * @throws RestException
		 */
		public static function sendStatusReading($params, $n, \CRestServer $server): array
		{
			$result = [];

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
				if (!empty($resultSend->getErrors()))
				{
					foreach ($resultSend->getErrors() as $error)
					{
						throw new RestException($error->getMessage(), $error->getCode(), \CRestServer::STATUS_WRONG_REQUEST);
					}
				}

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
		 * @throws RestException
		 */
		public static function setErrorConnector($params, $n, \CRestServer $server): array
		{
			$result = [];

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
				if (!empty($resultSend->getErrors()))
				{
					foreach ($resultSend->getErrors() as $error)
					{
						throw new RestException($error->getMessage(), $error->getCode(), \CRestServer::STATUS_WRONG_REQUEST);
					}
				}

				$result['SUCCESS'] = false;
			}

			$result['DATA'] = $resultSend->getData();

			return $result;
		}

		public static function setChatName($params, $n, \CRestServer $server): array
		{
			$result = [];

			$params = array_change_key_case($params, CASE_UPPER);

			if ($server->getAuthType() !== Auth::AUTH_TYPE)
			{
				throw new AuthTypeException("Application context required");
			}

			if (empty($params['CONNECTOR']))
			{
				throw new ArgumentNullException("CONNECTOR");
			}

			if (empty($params['LINE']))
			{
				throw new ArgumentNullException("LINE");
			}

			if (empty($params['CHAT_ID']))
			{
				throw new ArgumentNullException("CHAT_ID");
			}

			if (empty($params['NAME']))
			{
				throw new ArgumentNullException("NAME");
			}

			if (
				empty($params['USER_ID'])
				&& !\Bitrix\ImConnector\Connector::isChatGroup($params['CONNECTOR'])
			)
			{
				throw new ArgumentNullException('USER_ID');
			}

			$converter = new Converter(Converter::TO_LOWER | Converter::KEYS | Converter::RECURSIVE);
			$params = $converter->process($params);
			$resultSend = CC::setChatName($params['connector'], $params['line'], $params);

			if ($resultSend->isSuccess())
			{
				$result['SUCCESS'] = true;
			}
			else
			{
				if (!empty($resultSend->getErrors()))
				{
					foreach ($resultSend->getErrors() as $error)
					{
						throw new RestException($error->getMessage(), $error->getCode(), \CRestServer::STATUS_WRONG_REQUEST);
					}
				}

				$result['SUCCESS'] = false;
			}

			$result['DATA'] = $resultSend->getData();
			$dataResult = $result['DATA']['RESULT'] ?? null;

			if ($dataResult instanceof \Bitrix\Main\Result)
			{
				$renameResultData = $dataResult->getData();
				foreach ($renameResultData['RESULT'] as $data)
				{
					if (!($data instanceof \Bitrix\Main\EventResult))
					{
						continue;
					}

					$eventParams = $data->getParameters();
					if (!($eventParams instanceof \Bitrix\Main\Result) || $eventParams->isSuccess())
					{
						continue;
					}

					foreach ($eventParams->getErrors() as $error)
					{
						throw new RestException(
							$error->getMessage(),
							$error->getCode(),
							\CRestServer::STATUS_WRONG_REQUEST
						);
					}
				}
			}

			return $result;
		}

		/**
		 * Event handler for 'rest:OnRestAppUpdate'.
		 * @param $arParams
		 * @return void
		 */
		public static function onRestAppUpdate($arParams): void
		{
			if (!empty($arParams['APP_ID']))
			{
				$res = AppTable::getById($arParams['APP_ID']);
				$app = $res->fetch();
				if (
					!$app
					|| $app['ACTIVE'] != 'Y'
					|| $app['INSTALLED'] != 'Y'
				)
				{
					self::onRestAppDelete($arParams);
				}
			}
		}

		/**
		 * Event handler for 'rest:OnRestAppDelete'.
		 * @param $arParams
		 * @return void
		 */
		public static function onRestAppDelete($arParams): void
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