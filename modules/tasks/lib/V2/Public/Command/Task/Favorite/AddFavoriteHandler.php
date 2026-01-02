<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Favorite;

use Bitrix\Tasks\V2\Internal\Repository\FavoriteTaskRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Service\Task\FavoriteService;

class AddFavoriteHandler
{
	public function __construct(
		private readonly FavoriteTaskRepositoryInterface $favoriteTaskRepository,
		private readonly FavoriteService $favoriteService,
	)
	{

	}

	public function __invoke(AddFavoriteCommand $command): void
	{
		$isFavorite = $this->favoriteTaskRepository->getByPrimary($command->taskId, $command->userId);
		if ($isFavorite)
		{
			return;
		}

		$this->favoriteService->add($command->taskId, $command->userId);
	}
}
