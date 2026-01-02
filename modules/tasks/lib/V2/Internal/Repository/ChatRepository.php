<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\V2\Internal\Model\TaskChatTable;

class ChatRepository implements ChatRepositoryInterface
{
	public function getChatIdByTaskId(int $taskId): ?int
	{
		$result = TaskChatTable::query()
			->setSelect(['CHAT_ID'])
			->where('TASK_ID', '=', $taskId)
			->setLimit(1)
			->fetch()
		;

		return (isset($result['CHAT_ID'])) ? (int)$result['CHAT_ID'] : null;
	}

	public function save(int $chatId, int $taskId): void
	{
		TaskChatTable::add([
			'TASK_ID' => $taskId,
			'CHAT_ID' => $chatId,
		]);
	}

	public function delete(int $taskId): void
	{
		TaskChatTable::deleteByFilter([
			'TASK_ID' => $taskId,
		]);
	}
}
