<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Service;

use Bitrix\AI\ShareRole\Repository\FavoriteRepository;

class FavoriteService
{
	public function __construct(
		protected FavoriteRepository $favoriteRepository
	)
	{
	}

	public function isFavoriteRole(int $userId, string $roleCode): bool
	{
		$data = $this->favoriteRepository->getFavoriteFlag($userId, $roleCode);

		return !empty($data['ID']);
	}
}
