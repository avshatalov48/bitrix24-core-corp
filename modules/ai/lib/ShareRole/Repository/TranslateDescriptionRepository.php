<?php

namespace Bitrix\AI\ShareRole\Repository;

use Bitrix\AI\BaseRepository;
use Bitrix\AI\Model\RoleTranslateDescriptionTable;

class TranslateDescriptionRepository extends BaseRepository
{
	public function deleteByRoleId(int $roleId): void
	{
		RoleTranslateDescriptionTable::deleteByFilter([
			'=ROLE_ID' => $roleId,
		]);
	}

	public function addDescriptionsForRole(int $roleId, array $names): void
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

		RoleTranslateDescriptionTable::addMulti($data, true);
	}
}
