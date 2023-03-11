<?php
namespace Bitrix\ImConnector\InteractiveMessage;

use Bitrix\Main\Loader;
use Bitrix\Im\Model\ChatTable;
use Bitrix\ImOpenLines\Chat;
use Bitrix\ImConnector\Connector;

/**
 * Class Base
 * @package Bitrix\ImConnector\InteractiveMessage
 */
class Output
{
	/**
	 * @var self[]
	 */
	protected static $instances = [];

	protected $idConnector = '';
	protected $idChat = 0;

	/** @var array */
	protected $keyboardData = [];

	/**
	 * Output constructor.
	 * @param int $idChat
	 * @param string $idConnector
	 */
	protected function __construct($idChat, $idConnector)
	{
		$this->idChat = $idChat;
		$this->idConnector = $idConnector;
	}

	protected function __clone()
	{
	}

	/**
	 * @param int $chatId
	 * @param array $params
	 * @return self
	 */
	public static function getInstance($chatId = 0, $params = []): self
	{
		if(!isset(static::$instances[$chatId]))
		{
			static::$instances[$chatId] = self::init($chatId, $params['connectorId']);
		}

		return static::$instances[$chatId];
	}

	/**
	 * @param int $chatId
	 * @param Output $interactiveMessage
	 */
	public static function setInstance(int $chatId, Output $interactiveMessage): void
	{
		static::$instances[$chatId] = $interactiveMessage;
	}

	/**
	 * @param int $chatId
	 * @param string $connectorId
	 * @return Output
	 */
	private static function init($chatId = 0, $connectorId = ''): self
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
				$chatData = ChatTable::getList([
					'select' => ['ENTITY_ID'],
					'filter' => [
						'=ENTITY_TYPE' => 'LINES',
						'=ID' => $chatId,
					],
					'limit' => 1,
					'cache' => ['ttl' => 86400]
				])->fetch();
				if ($chatData && isset($chatData['ENTITY_ID']))
				{
					$connectorId = Chat::parseLinesChatEntityId($chatData['ENTITY_ID'])['connectorId'];
				}
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

		return new $class($chatId, $connectorId);
	}

	/**
	 * Processing an outgoing message to the connector.
	 *
	 * @param array $messageFields
	 * @param string $connectorId
	 *
	 * @return array
	 */
	public static function processSendingMessage(array $messageFields, string $connectorId): array
	{
		if(!empty($messageFields['im']['chat_id']) && $messageFields['im']['chat_id'] > 0)
		{
			$messageFields['message'] =
				self::getInstance($messageFields['im']['chat_id'], ['connectorId' => $connectorId])
					->nativeMessageProcessing($messageFields['message']);
		}

		return $messageFields;
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
	 * Sets an array of catalog products external (facebook) ID's for a message.
	 *
	 * @param array $ids External (facebook) ids of catalog products.
	 */
	public function setProductIds(array $ids = []): void
	{
	}

	/**
	 * Checks if an interactive message is available for the open line.
	 *
	 * @param int $lineId Open line id.
	 * @return bool
	 */
	public function isAvailable(int $lineId): bool
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
	 * Add keyboard data.
	 *
	 * @param array $data
	 * @return self
	 */
	public function setKeyboardData($data = []): self
	{
		$this->keyboardData = $data;

		return $this;
	}

	/**
	 * Is data loading keyboard.
	 *
	 * @return bool
	 */
	public function isLoadedKeyboard(): bool
	{
		return
			!empty($this->keyboardData)
			&& is_array($this->keyboardData);
	}

	/**
	 * The transformation of the description of the outgoing message in native format if possible.
	 *
	 * @param $message
	 * @return array
	 */
	public function nativeMessageProcessing($message): array
	{
		return $message;
	}
}