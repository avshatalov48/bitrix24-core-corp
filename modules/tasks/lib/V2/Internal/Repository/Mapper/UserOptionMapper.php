<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper;
use Bitrix\Tasks\V2\Internal\Entity;

class UserOptionMapper
{
	public function mapToCollection(array $userOptions): Entity\Task\UserOptionCollection
	{
		$entities = [];
		foreach ($userOptions as $userOption)
		{
			$entities[] = $this->mapToEntity($userOption);
		}

		return new Entity\Task\UserOptionCollection(...$entities);
	}

	public function mapToEntity(array $userOption): Entity\Task\UserOption
	{
		return new Entity\Task\UserOption(
			id: $userOption['ID'] ?? null,
			userId: $userOption['USER_ID'] ?? null,
			taskId: $userOption['TASK_ID'] ?? null,
			code: $userOption['OPTION_CODE'] ?? null,
		);
	}

	public function mapFromEntity(Entity\Task\UserOption $userOption): array
	{
		$data = [];
		if ($userOption->id)
		{
			$data['ID'] = $userOption->id;
		}

		if ($userOption->userId)
		{
			$data['USER_ID'] = $userOption->userId;
		}

		if ($userOption->taskId)
		{
			$data['TASK_ID'] = $userOption->taskId;
		}

		if ($userOption->code)
		{
			$data['OPTION_CODE'] = $userOption->code;
		}

		return $data;
	}
}