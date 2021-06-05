<?php

namespace Bitrix\ImOpenLines;

use Bitrix\ImConnector\Library;
use Bitrix\Main\Loader;

class ReplyBlock
{
	/**
	 * Adds a block to the session and chat.
	 *
	 * @param int $sessionId
	 * @param Chat $chat
	 * @param array $limit
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function add(int $sessionId, Chat $chat , array $limit): void
	{
		if (!empty($limit['BLOCK_DATE']) && !empty($limit['BLOCK_REASON']))
		{
			Model\SessionTable::update($sessionId, Array(
				'BLOCK_DATE' => $limit['BLOCK_DATE'],
				'BLOCK_REASON' => $limit['BLOCK_REASON'],
			));

			$chat->updateFieldData([Chat::FIELD_SESSION => [
				'ID' => $sessionId,
				'BLOCK_DATE' => $limit['BLOCK_DATE'],
				'BLOCK_REASON' => $limit['BLOCK_REASON'],
			]]);
		}
	}

	/**
	 * Removes a block from the session and chat.
	 *
	 * @param int $sessionId
	 * @param Chat $chat
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function delete(int $sessionId, Chat $chat): void
	{
		if ($sessionId > 0 && $chat)
		{
			Model\SessionTable::update($sessionId, Array(
				'BLOCK_DATE' => null,
				'BLOCK_REASON' => null,
			));

			$chat->updateFieldData([Chat::FIELD_SESSION => [
				'ID' => $sessionId,
				'BLOCK_DATE' => 0,
				'BLOCK_REASON' => '',
			]]);
		}
	}

	/**
	 * Checks if the session is blocked.
	 *
	 * @param Session $session
	 * @return bool
	 */
	public static function isBlocked(Session $session): bool
	{
		$result = false;
		$sessionData = $session->getData();

		if (
			!empty($sessionData['BLOCK_DATE']) &&
			$sessionData['BLOCK_DATE'] < new \Bitrix\Main\Type\DateTime()
		)
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * Gets block information from the previous session.
	 *
	 * @param array $sessionFields
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getBlockFromPreviousSession(array $sessionFields): ?array
	{
		$result = null;

		$previousSession = new Session();
		$resultPreviousSession = $previousSession->getLast($sessionFields);

		if ($resultPreviousSession->isSuccess())
		{
			$previousSessionData = $previousSession->getData();

			if (!empty($previousSessionData['BLOCK_DATE']) && !empty($previousSessionData['BLOCK_REASON']))
			{
				$result = [
					'BLOCK_DATE' => $previousSessionData['BLOCK_DATE'],
					'BLOCK_REASON' => $previousSessionData['BLOCK_REASON'],
				];
			}
		}

		return $result;
	}
}