<?php

namespace Bitrix\ImOpenLines;

use Bitrix\ImConnector\Library;
use Bitrix\Main\Loader;

class ReplyBlock
{
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

	public static function isBlocked(Session $session): bool
	{
		$sessionData = $session->getData();

		if (!empty($sessionData['BLOCK_DATE']) && $sessionData['BLOCK_DATE'] < new \Bitrix\Main\Type\DateTime())
		{
			return true;
		}

		return false;
	}

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