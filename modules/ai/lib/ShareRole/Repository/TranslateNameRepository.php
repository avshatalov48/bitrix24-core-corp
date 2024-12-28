<?php

namespace Bitrix\AI\ShareRole\Repository;

use Bitrix\AI\BaseRepository;
use Bitrix\AI\Model\RoleTranslateNameTable;

class TranslateNameRepository extends BaseRepository
{
	public function deleteByRoleId(int $roleId): void
	{
		RoleTranslateNameTable::deleteByFilter([
			'=ROLE_ID' => $roleId,
		]);
	}

	public function addNamesForRole(int $roleId, array $names): void
	{
		$data = [];
		foreach ($names as $lang => $text)
		{
			$data[] = [
				'ROLE_ID' => $roleId,
				'LANG' => $lang,
				'TEXT' => $text,
			];
		}

		if (empty($data))
		{
			return;
		}

		RoleTranslateNameTable::addMulti($data, true);
	}
}
