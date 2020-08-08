<?php
namespace Bitrix\ImConnector\InteractiveMessage;

use \Bitrix\Main\Loader;
use \Bitrix\Im\Model\ChatTable;
use \Bitrix\ImOpenLines\Chat;
use \Bitrix\ImConnector\Connector;

/**
 * Class Base
 * @package Bitrix\ImConnector\InteractiveMessage
 */
class Output
{
	/**
	 * @var array Output
	 */
	protected static $instances = [];

	/**
	 * @param int $chatId
	 * @param array $params
	 * @return Output
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getInstance($chatId = 0, $params = []): Output
	{
		if(!isset(static::$instances[$chatId]))
		{
			static::$instances[$chatId] = self::initialization($chatId, $params['connectorId']);
		}

		return static::$instances[$chatId];
	}

	/**
	 * @param int $chatId
	 * @param string $connectorId
	 * @return Output
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private static function initialization($chatId = 0, $connectorId = ''): Output
	{
		$class = __CLASS__;

		if(
			$chatId > 0
		)
		{
			if(
				empty($connectorId) &&
				Loader::includeModule('im') &&
				Loader::includeModule('imopenlines')
			)
			{
				$chatEntityId = ChatTable::getList([
					'select' => ['ENTITY_ID'],
					'filter' => [
						'=ENTITY_TYPE' => 'LINES',
						'=ID' => $chatId,
					],
					'limit' => 1,
					'cache' => ['ttl' => 86400]
				])->fetch()['ENTITY_ID'];

				$connectorId = Chat::parseLinesChatEntityId($chatEntityId)['connectorId'];
			}

			if(
				!empty($connectorId) &&
				Connector::isConnector($connectorId)
			)
			{
				$connectorId = Connector::getConnectorRealId($connectorId);
				$className = "Bitrix\\ImConnector\\InteractiveMessage\\Connectors\\" . $connectorId . "\\Output";
				if(class_exists($className))
				{
					$class = $className;
				}
			}
		}

		return new $class;
	}

	/**
	 * Processing an outgoing message to the connector.
	 *
	 * @param array $messageFields
	 * @param string $connectorId
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function sendMessageProcessing($messageFields, $connectorId): array
	{
		if(!empty($messageFields['im']['chat_id']) && $messageFields['im']['chat_id'] > 0)
		{
			$messageFields['message'] = self::getInstance($messageFields['im']['chat_id'], ['connectorId' => $connectorId])->nativeMessageProcessing($messageFields['message']);
		}

		return $messageFields;
	}

	/**
	 * Output constructor.
	 */
	protected function __construct()
	{
	}

	protected function __clone()
	{
	}

	/**
	 * Setting the list of id forms to send.
	 *
	 * @param array $ids
	 * @return bool
	 */
	public function setFormIds($ids = []): bool
	{
		return false;
	}

	/**
	 * Setting the custom imessage application params.
	 *
	 * @param array $params
	 * @return bool
	 */
	public function setAppParams($params = []): bool
	{
		return false;
	}

	/**
	 * Setting the OAuth application params.
	 *
	 * @param array $params
	 * @return bool
	 */
	public function setOauthParams($params = []): bool
	{
		return false;
	}

	/**
	 * Setting payment data to be sent in a native message.
	 *
	 * @param array $data
	 * @return bool
	 */
	public function setPaymentData($data = []): bool
	{
		return false;
	}

	/**
	 * The transformation of the description of the outgoing message in native format if possible.
	 *
	 * @param $message
	 * @return array
	 */
	protected function nativeMessageProcessing($message): array
	{
		return $message;
	}
}