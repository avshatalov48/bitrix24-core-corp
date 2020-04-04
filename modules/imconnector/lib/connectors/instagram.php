<?php
namespace Bitrix\ImConnector\Connectors;

use \Bitrix\Main\UserTable,
	\Bitrix\Main\Loader,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\ImConnector\Chat,
	\Bitrix\ImConnector\Status,
	\Bitrix\ImConnector\Output,
	\Bitrix\ImConnector\Library,
	\Bitrix\ImConnector\Connector,
	\Bitrix\ImOpenLines\Model\SessionTable;

Loc::loadMessages(__FILE__);

/**
 * Class Instagram
 * @package Bitrix\ImConnector\Connectors
 */
class Instagram
{
	/**
	 * @param $value
	 * @param $connector
	 *
	 * @return mixed
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function sendMessageProcessing($value, $connector)
	{
		if(($connector == Library::ID_INSTAGRAM_CONNECTOR || $connector == Library::ID_FBINSTAGRAM_CONNECTOR) && !Library::isEmpty($value['message']['text']))
		{
			$usersTitle = array();
			$lastMessageId = Chat::getChatLastMessageId($value['chat']['id'], $connector);

			if (!empty($lastMessageId))
			{
				$value['extra']['last_message_id'] = $lastMessageId;
			}

			preg_match_all("/\[USER=([0-9]{1,})\](.*?)\[\/USER\]/i", $value['message']['text'], $users);
			if(!empty($users[1]))
			{
				$filterUser = array(
					'LOGIC' => 'OR'
				);
				foreach ($users[1] as $user)
					$filterUser[] = array('=ID' => $user);

				$rawUsers = UserTable::getList(
					array(
						'select' => array(
							'ID',
							'TITLE',
							'NAME'
						),
						'filter' => $filterUser
					)
				);

				while ($rowUser = $rawUsers->fetch())
				{
					if(!Library::isEmpty($rowUser['TITLE']))
						$usersTitle[$rowUser['ID']] = $rowUser['TITLE'];
					elseif(!Library::isEmpty($rowUser['NAME'])) //case for new fb instagram connector
						$usersTitle[$rowUser['ID']] = $rowUser['NAME'];
				}

				if(!empty($usersTitle))
				{
					$search = array();
					$replace = array();

					foreach ($users[1] as $cell=>$user)
					{
						if(!Library::isEmpty($usersTitle[$user]))
						{
							$search[] = $users[0][$cell];
							$replace[] = '@' . $usersTitle[$user];
						}
					}

					if(!empty($search) && !empty($replace))
						$value['message']['text'] = str_replace($search, $replace, $value['message']['text']);
				}
			}
			elseif (!empty($value['extra']['last_message_id'])) //check that it is a new version
			{
				$chatData = explode('.', $value['chat']['id']);
				if (!empty($chatData[1]))
				{
					$value['message']['text'] = '@' . $chatData[1] . ' ' . $value['message']['text'];
				}
			}
		}

		return $value;
	}

	/**
	 * Agent
	 *
	 * @return string
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function initializeReceiveMessages()
	{
		if(Loader::includeModule('imconnector') && defined('\Bitrix\ImConnector\Library::ID_INSTAGRAM_CONNECTOR') && Connector::isConnector(Library::ID_INSTAGRAM_CONNECTOR, true))
		{
			$statuses = Status::getInstanceAllLine(Library::ID_INSTAGRAM_CONNECTOR);

			if(!empty($statuses))
			{
				foreach ($statuses as $line=>$status)
				{
					if($status->isStatus())
					{
						$connectorOutput = new Output(Library::ID_INSTAGRAM_CONNECTOR, $line);

						$connectorOutput->initializeReceiveMessages($status->getData());
					}
				}
			}
		}

		return '\Bitrix\ImConnector\Connectors\Instagram::initializeReceiveMessages();';
	}

	/**
	 * @param $message
	 * @param $connector
	 * @param $line
	 * @throws \Exception
	 */
	public static function newMediaProcessing($message, $connector, $line)
	{
		if($connector == Library::ID_INSTAGRAM_CONNECTOR)
		{
			$status = Status::getInstance($connector, $line);

			if(!($data = $status->getData()))
				$data = array();

			$dataOld = $data;

			if(!empty($message['chat']['id']))
			{
				if(is_object($message['message']['date']))
					$datetime = $message['message']['date']->getTimestamp();
				else
					$datetime = $message['message']['date'];

				if(empty($data[$message['chat']['id']]))
					$data[$message['chat']['id']] = array(
						'datetime' => $datetime,
						'comments' => array()
					);
				else
					$data[$message['chat']['id']]['datetime'] = $datetime;

				if(count($data)>Library::INSTAGRAM_MAX_COUNT)
				{
					uasort(
						$data,
						function ($a, $b)
						{
							if ($a['datetime'] == $b['datetime'])
								return 0;
							return ($a['datetime'] > $b['datetime']) ? -1 : 1;
						}
					);

					$data = array_slice($data, 0, Library::INSTAGRAM_MAX_COUNT, true);
				}
			}

			if(!empty($data) && $dataOld!==$data)
			{
				$status->setData($data);
				Status::save();
			}
		}
	}

	/**
	 * @param $message
	 * @param $connector
	 * @param $line
	 * @throws \Exception
	 */
	public static function newCommentProcessing($message, $connector, $line)
	{
		if($connector == Library::ID_INSTAGRAM_CONNECTOR)
		{
			$status = Status::getInstance($connector, $line);

			if(!($data = $status->getData()))
				$data = array();

			$dataOld = $data;

			if(!empty($message['chat']['id']))
			{
				if(empty($data[$message['chat']['id']]['comments']) || !in_array($message['message']['id'], $data[$message['chat']['id']]['comments']))
				{
					$data[$message['chat']['id']]['comments'][] = $message['message']['id'];
				}
			}

			if(!empty($data) && $dataOld!==$data)
			{
				$status->setData($data);
				Status::save();
			}
		}
	}

	/**
	 * @param $message
	 * @param $connector
	 * @param $line
	 * @throws \Exception
	 */
	public static function newCommentDeliveryProcessing($message, $connector, $line)
	{
		if($connector == Library::ID_INSTAGRAM_CONNECTOR)
		{
			$status = Status::getInstance($connector, $line);

			if(!($data = $status->getData()))
				$data = array();

			$dataOld = $data;

			if(!empty($message['chat']['id']))
			{
				if(empty($data[$message['chat']['id']]['comments']) || !in_array($message['message']['id'], $data[$message['chat']['id']]['comments']))
				{
					foreach ($message['message']['id'] as $messageId)
						$data[$message['chat']['id']]['comments'][] = $messageId;
				}
			}

			if(!empty($data) && $dataOld!==$data)
			{
				$status->setData($data);
				Status::save();
			}
		}
	}

	/**
	 * Agent for movement from old instagram connector to new
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function initPrepareActionsForNewConnector()
	{
		$instagramConnected = false;

		if(Loader::includeModule('imopenlines'))
		{
			$configManager = new \Bitrix\ImOpenLines\Config();
			$configList = $configManager->getList(array());

			foreach ($configList as $config)
			{
				$connectorList = Connector::getListConnectedConnector($config['ID']);
				$instagramConnected = array_key_exists(Library::ID_INSTAGRAM_CONNECTOR, $connectorList);

				if ($instagramConnected)
					break;
			}
		}

		if ($instagramConnected)
		{
			self::sendNewConnectorInfoMessage();
		}
		else
		{
			self::disableOldConnector();
		}
	}

	/**
	 * Send info messages about new connector for all users, who should to know about it
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected static function sendNewConnectorInfoMessage()
	{
		$result = false;

		$mailingUsers = [];
		if(Loader::includeModule('imopenlines'))
		{
			$operators = SessionTable::getList(
				array(
					'select' => array('OPERATOR_ID'),
					'group' => array('OPERATOR_ID')
				)
			);
			while ($operator = $operators->fetch())
				$mailingUsers[] = $operator['OPERATOR_ID'];
		}

		$admins = [];
		if(Loader::includeModule('bitrix24'))
		{
			$admins = \CBitrix24::getAllAdminId();
		}
		else
		{
			$users = \CUser::GetList($by="ID", $order="asc", ['GROUPS_ID' => [1], "ACTIVE" => "Y"], ['SELECT'=>['ID']]);

			while ($user = $users->Fetch())
			{
				$admins[] = $user['ID'];
			}
		}

		$mailingUsers = array_unique(array_merge($mailingUsers, $admins));

		foreach ($mailingUsers as $user)
		{
			self::sendInfoNotify($user);

			$result = true;
		}

		return $result;
	}

	/**
	 * Send notify message for user
	 *
	 * @param $userId
	 *
	 * @return bool|int
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected static function sendInfoNotify($userId)
	{
		$result = false;

		if(Loader::includeModule('im') && Loader::includeModule('imopenlines'))
		{
			$connectorSettingUrl =  \Bitrix\ImOpenLines\Common::getPublicFolder() . "connector/?ID=fbinstagram";
			$notifyFields = array(
				"TO_USER_ID" => $userId,
				"NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
				"NOTIFY_MODULE" => "imconnector",
				"NOTIFY_EVENT" => "default",
				"NOTIFY_TAG" => "CONNECTOR|FBINSTAGRAM|".$userId."|NOTIFICATION",
				"NOTIFY_MESSAGE" => Loc::getMessage(
					"CONNECTORS_INSTAGRAM_NEW_CONNECTOR_NOTIFY_MESSAGE",
					array('#CONNECTOR_URL#' => $connectorSettingUrl)
				),
				"NOTIFY_MESSAGE_OUT" => Loc::getMessage(
					"CONNECTORS_INSTAGRAM_NEW_CONNECTOR_NOTIFY_MESSAGE_OUT",
					array('#CONNECTOR_URL#' => $connectorSettingUrl)
				),
				"RECENT_ADD" => "Y"
			);

			$result = \CIMNotify::Add($notifyFields);
		}

		return $result;
	}

	/**
	 * Disable old instagram connector from options
	 *
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	protected static function disableOldConnector()
	{
		$connectors = Connector::getListConnectorActive();

		if($key = array_search(Library::ID_INSTAGRAM_CONNECTOR, $connectors))
		{
			unset($connectors[$key]);
			\Bitrix\Main\Config\Option::set('imconnector', 'list_connector', implode(",", $connectors));
		}
	}
}