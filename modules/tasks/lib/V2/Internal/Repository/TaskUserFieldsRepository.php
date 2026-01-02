<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\Provider\TaskProvider;
use Bitrix\Tasks\V2\Internal\Entity\UserField;
use Bitrix\Tasks\V2\Internal\Entity\UserFieldCollection;

class TaskUserFieldsRepository
{
	public function getByTaskId(int $taskId): UserFieldCollection
	{
		global $DB, $USER_FIELD_MANAGER;

		$provider = new TaskProvider($DB, $USER_FIELD_MANAGER);

		$data =  $provider->getList(
			arFilter: ['ID' => $taskId],
			arSelect: ['UF_*'],
			arParams: ['CHECK_PERMISSIONS' => 'N'],
		)->Fetch();

		if (!$data)
		{
			return new UserFieldCollection(...[]);
		}

		return new UserFieldCollection(...$this->getAutoFields($data));
	}

	private function getAutoFields(array $data): array
	{
		$autoFields = [];

		foreach ($data as $key => $value)
		{
			if (str_starts_with($key, 'UF_AUTO_'))
			{
				$autoFields[] = new UserField($key, $value);
			}
		}

		return $autoFields;
	}
}
