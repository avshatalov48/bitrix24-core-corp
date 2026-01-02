<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\V2\Internal\Entity;

class InMemoryChatRepository implements ChatRepositoryInterface
{
	private ChatRepositoryInterface $chatRepository;
	private array $cache = [];

	public function __construct(ChatRepository $chatRepository)
	{
		$this->chatRepository = $chatRepository;
	}

	public function getChatIdByTaskId(int $taskId): ?int
	{
		if (isset($this->cache[$taskId]))
		{
			return $this->cache[$taskId];
		}

		$chatId = $this->chatRepository->getChatIdByTaskId($taskId);

		if ($chatId !== null)
		{
			$this->cache[$taskId] = $chatId;
		}

		return $chatId;
	}

	public function save(int $chatId, int $taskId): void
	{
		if (isset($this->cache[$taskId]))
		{
			unset($this->cache[$taskId]);
		}

		$this->chatRepository->save($chatId, $taskId);

		$this->cache[$taskId] = $chatId;
	}

	public function delete(int $taskId): void
	{
		$this->chatRepository->delete($taskId);

		if (isset($this->cache[$taskId]))
		{
			unset($this->cache[$taskId]);
		}
	}
}
