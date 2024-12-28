<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Repository;

use Bitrix\AI\BaseRepository;
use Bitrix\AI\Model\RoleFavoriteTable;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\DeleteResult;

class FavoriteRepository extends BaseRepository
{
	public function getFavoriteFlag(int $userId, string $roleCode): bool|array
	{
		return RoleFavoriteTable::query()
			->setSelect(['ID'])
			->where('USER_ID', $userId)
			->where('ROLE_CODE', $roleCode)
			->fetch()
		;
	}

	public function addFavoriteForUser(int $userId, string $roleCode): AddResult
	{
		return RoleFavoriteTable::add(['ROLE_CODE' => $roleCode, 'USER_ID' => $userId]);
	}

	public function removeFavoriteForUser(int $userId, string $roleCode): ?DeleteResult
	{
		$favoriteRoleEntry = RoleFavoriteTable::query()
			->setSelect(['ID'])
			->where('USER_ID', $userId)
			->where('ROLE_CODE', $roleCode)
			->fetch()
		;

		if (!isset($favoriteRoleEntry['ID']))
		{
			return null;
		}

		return RoleFavoriteTable::delete($favoriteRoleEntry['ID']);
	}
}
