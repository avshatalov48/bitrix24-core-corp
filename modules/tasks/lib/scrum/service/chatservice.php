<?php

namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Tasks\Scrum\Internal\ChatTable;

class ChatService implements Errorable
{
	const ERROR_COULD_NOT_ADD_CHAT_ID = 'TASKS_CHS_01';
	const ERROR_COULD_NOT_GET_CHATS = 'TASKS_CHS_02';

	private $errorCollection;

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
	}

	/**
	 * Creates a link with the group in order to effectively get the chat id.
	 *
	 * @param int $chatId Chat id.
	 * @param int $groupId Group id.
	 * @return bool
	 */
	public function saveChatId(int $chatId, int $groupId): bool
	{
		try
		{
			$result = ChatTable::add([
				'CHAT_ID' => $chatId,
				'GROUP_ID' => $groupId,
			]);

			if (!$result->isSuccess())
			{
				$this->errorCollection->setError(
					new Error(
						implode('; ', $result->getErrorMessages()),
						self::ERROR_COULD_NOT_ADD_CHAT_ID
					)
				);

				return false;
			}

			return true;
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_ADD_CHAT_ID
				)
			);
		}

		return false;
	}

	/**
	 * Returns chat ids by group id.
	 *
	 * @param int $groupId Group id.
	 * @return array
	 */
	public function getChatIds(int $groupId): array
	{
		$chatIds = [];

		try
		{
			$queryObject = ChatTable::getList([
				'select' => ['CHAT_ID'],
				'filter' => [
					'GROUP_ID' => $groupId,
				]
			]);
			while ($data = $queryObject->fetch())
			{
				$chatIds[] = $data['CHAT_ID'];
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_GET_CHATS
				)
			);
		}

		return $chatIds;
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}
}