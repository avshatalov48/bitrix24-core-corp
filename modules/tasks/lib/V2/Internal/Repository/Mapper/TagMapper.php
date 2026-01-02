<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper;

use Bitrix\Tasks\V2\Internal\Entity;

class TagMapper
{
	public function mapToCollection(array $tags): Entity\TagCollection
	{
		$entities = [];
		foreach ($tags as $tag)
		{
			$entities[] = $this->mapToEntity($tag);
		}

		return new Entity\TagCollection(...$entities);
	}

	public function mapToEntity(array $tag): Entity\Tag
	{
		return new Entity\Tag(
			id: (int)($tag['ID'] ?? null),
			name: (string)($tag['NAME'] ?? null),
			owner: new Entity\User(id: (int)($tag['USER_ID'] ?? 0)),
			group: new Entity\Group(id: (int)($tag['GROUP_ID'] ?? 0)),
			task: new Entity\Task(id: (int)($tag['TASK_ID'] ?? 0)),
		);
	}
}
