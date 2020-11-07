<?php
namespace Bitrix\ImConnector\Connectors;

use \Bitrix\Main\UserTable,
	\Bitrix\Main\Localization\Loc;
use \Bitrix\ImConnector\Chat,
	\Bitrix\ImConnector\Library;

Loc::loadMessages(__FILE__);

/**
 * Class FbInstagram
 * @package Bitrix\ImConnector\Connectors
 */
class FbInstagram extends Base
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
		if(($connector == Library::ID_FBINSTAGRAM_CONNECTOR) && !Library::isEmpty($value['message']['text']))
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
				$nickNameStartPosition = mb_strpos($value['chat']['id'], '.');
				$nickName = mb_substr($value['chat']['id'], $nickNameStartPosition + 1);
				if ($nickNameStartPosition > 0)
				{
					$value['message']['text'] = '@' . $nickName . ' ' . $value['message']['text'];
				}
			}
		}

		return $value;
	}
}