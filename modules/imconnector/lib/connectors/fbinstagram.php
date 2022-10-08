<?php
namespace Bitrix\ImConnector\Connectors;

use Bitrix\Main\UserTable;
use Bitrix\Main\Localization\Loc;

use Bitrix\ImConnector\Chat;
use Bitrix\ImConnector\Error;
use Bitrix\ImConnector\Result;
use Bitrix\ImConnector\Library;

Loc::loadMessages(__FILE__);

/**
 * Class FbInstagram
 * @package Bitrix\ImConnector\Connectors
 */
class FbInstagram extends InstagramBase
{
	//User
	/**
	 * @param array $params
	 * @param Result $result
	 * @return Result
	 */
	protected function getUserData(array $params, Result $result): Result
	{
		$result->setResult([
			'ID' => $params['ID_FB_INSTAGRAM'],
			'MD5' => $params['MD5_FB_INSTAGRAM']
		]);

		return $result;
	}

	/**
	 * @param array $oldUserFields
	 * @param array $userFields
	 * @return Result
	 */
	protected function updateUser(array $oldUserFields, array $userFields): Result
	{
		$result = new Result;
		$user = new \CUser;

		$userId = $userFields['ID'];
		$result->setResult($userId);

		if (
			$userFields['MD5'] !== md5(serialize($oldUserFields))
			&& (
				!Library::isEmpty($userFields['name'])
				|| !Library::isEmpty($userFields['picture'])
			)
		)
		{
			$fields = $this->preparationUserFields($oldUserFields, $userId);
			if(!empty($fields))
			{
				static::getApplication()->resetException();

				if (!$user->update($userId, $fields))
				{
					$error = static::getApplication()->getException();
					if ($error instanceof \CApplicationException)
					{
						$result->addError(new Error($error->getString()));
					}
				}
			}
		}

		return $result;
	}

	//Input
	/**
	 * @param $message
	 * @param $line
	 * @return Result
	 */
	public function processingInputNewMessage($message, $line): Result
	{
		$message = $this->processingLastMessage($message);

		return parent::processingInputNewMessage($message, $line);
	}

	/**
	 * @param array $chat
	 * @return array
	 */
	protected function processingChat(array $chat): array
	{
		if (!empty($chat['url']))
		{
			$chat['description'] = Loc::getMessage(
				'IMCONNECTOR_LINK_TO_ORIGINAL_POST_IN_INSTAGRAM',
				[
					'#LINK#' => $chat['url']
				]
			);

			unset($chat['url']);
		}

		return $chat;
	}
	//END Input

	//Output
	/**
	 * @param array $message
	 * @param $line
	 * @return array
	 */
	public function sendMessageProcessing(array $message, $line): array
	{
		$message = parent::sendMessageProcessing($message, $line);

		if(
			!empty($message['message']['files'])
			|| !Library::isEmpty($message['message']['text'])
		)
		{
			$usersTitle = [];
			$lastMessageId = Chat::getChatLastMessageId($message['chat']['id'], $this->idConnector);

			if (!empty($lastMessageId))
			{
				$message['extra']['last_message_id'] = $lastMessageId;
			}

			$users = [];

			if(!Library::isEmpty($message['message']['text']))
			{
				preg_match_all("/\[USER=([0-9]{1,})\](.*?)\[\/USER\]/i", $message['message']['text'], $users);
			}

			if(!empty($users[1]))
			{
				$filterUser = ['LOGIC' => 'OR'];
				foreach ($users[1] as $user)
				{
					$filterUser[] = ['=ID' => $user];
				}

				$rawUsers = UserTable::getList(
					[
						'select' => [
							'ID',
							'TITLE',
							'NAME'
						],
						'filter' => $filterUser
					]
				);

				while ($rowUser = $rawUsers->fetch())
				{
					if(!Library::isEmpty($rowUser['TITLE']))
					{
						$usersTitle[$rowUser['ID']] = $rowUser['TITLE'];
					}
					elseif(!Library::isEmpty($rowUser['NAME'])) //case for new fb instagram connector
					{
						$usersTitle[$rowUser['ID']] = $rowUser['NAME'];
					}
				}

				if(!empty($usersTitle))
				{
					$search = [];
					$replace = [];

					foreach ($users[1] as $cell=>$user)
					{
						if(!Library::isEmpty($usersTitle[$user]))
						{
							$search[] = $users[0][$cell];
							$replace[] = '@' . $usersTitle[$user];
						}
					}

					if(!empty($search) && !empty($replace))
					{
						$message['message']['text'] = str_replace($search, $replace, $message['message']['text']);
					}
				}
			}
			elseif (!empty($message['extra']['last_message_id'])) //check that it is a new version
			{
				$nickNameStartPosition = mb_strpos($message['chat']['id'], '.');
				$nickName = mb_substr($message['chat']['id'], $nickNameStartPosition + 1);
				if ($nickNameStartPosition > 0)
				{
					$message['message']['text'] = '@' . $nickName . ' ' . $message['message']['text'];
				}
			}
		}

		return $message;
	}
	//END Output
}