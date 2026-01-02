<?php

namespace Bitrix\Call;

use Bitrix\Call\Model\CallChatEntityTable;
use Bitrix\Call\Model\EO_CallChatEntity;
use Bitrix\Main\Type\DateTime;

class CallChatEntity extends EO_CallChatEntity
{
	public static function create($chatData): self
	{
		$chatEntity = new static();
		$chatEntity
			->setChatId($chatData['ID'])
			->setCallTokenVersion($chatData['TOKEN_VERSION'])
			->save()
		;

		return $chatEntity;
	}

	public static function updateVersion($chatId): self
	{
		$chatEntity = self::find($chatId);

		if ($chatEntity)
		{
			$tokenVersion = $chatEntity->getCallTokenVersion();

			$chatEntity
				->setCallTokenVersion($tokenVersion + 1)
				->save()
			;
			return $chatEntity;
		}

		return self::create([
			'ID' => $chatId,
			'TOKEN_VERSION' => 2,
		]);
	}

	public static function find(int $chatId): ?self
	{
		$query = CallChatEntityTable::query()
			->setSelect(['*'])
			->where('CHAT_ID', $chatId)
			->setLimit(1)
		;

		return $query->exec()->fetchObject();
	}

	public static function findAll(array $chatIds): array
	{
		$query = CallChatEntityTable::query()
			->setSelect(['*'])
			->whereIn('CHAT_ID', $chatIds)
		;

		$result = [];
		$chatEntities = $query->exec()->fetchAll();
		foreach ($chatEntities as $chat)
		{
			$result[$chat['ID']] = $chat['CALL_TOKEN_VERSION'];
		}

		return $result;
	}
}
