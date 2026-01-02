<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task;

use Bitrix\Tasks\Integration\Socialnetwork\Task;
use Bitrix\Tasks\V2\Internal\Service\EventService;
use Bitrix\Tasks\V2\Internal\Repository\FavoriteTaskRepositoryInterface;

class FavoriteService
{
	public function __construct(
		private readonly FavoriteTaskRepositoryInterface $favoriteTaskRepository,
		private readonly EventService $eventService,
	)
	{

	}

	public function add(int $taskId, int $userId): void
	{
		$this->favoriteTaskRepository->add(
			taskId: $taskId,
			userId: $userId,
		);

		$this->eventService->send('OnTaskToggleFavorite', [
			'taskId' => $taskId,
			'userId' => $userId,
			'isFavorite' => true,
		]);

		$this->notifyLiveFeed(
			taskId: $taskId,
			userId: $userId,
			isFavorite: true,
		);
	}

	public function delete(int $taskId, int $userId): void
	{
		$this->favoriteTaskRepository->delete(
			taskId: $taskId,
			userId: $userId,
		);

		$this->eventService->send('OnTaskToggleFavorite', [
			'taskId' => $taskId,
			'userId' => $userId,
			'isFavorite' => false,
		]);

		$this->notifyLiveFeed(
			taskId: $taskId,
			userId: $userId,
			isFavorite: false,
		);
	}

	protected function notifyLiveFeed(int $taskId, int $userId, bool $isFavorite): void
	{
		Task::toggleFavorites([
			'TASK_ID' => $taskId,
			'USER_ID' => $userId,
			'OPERATION' => $isFavorite ? 'ADD' : 'DELETE',
		]);
	}
}
