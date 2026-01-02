<?php declare(strict_types=1);

namespace Bitrix\AI\Repository;

use Bitrix\AI\BaseRepository;
use Bitrix\AI\Model\RoleTable;

class RoleRepository extends BaseRepository
{
	public function getRoleAvatars(): array
	{
		$query = RoleTable::query()
			->setSelect([
				'CODE',
				'AVATAR',
			])
			->exec()
		;

		$roles = [];
		while ($role = $query->fetch())
		{
			$roles[$role['CODE']] = $role['AVATAR'];
		}

		return $roles;
	}
}
