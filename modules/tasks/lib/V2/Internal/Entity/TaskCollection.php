<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity;

/**
 * @method null|Task findOne(array $conditions)
 * @method null|Task findOneById(int $id, string $idKey = 'id')
 * @method TaskCollection findAll(array $conditions)
 * @method TaskCollection findAllByIds(array $ids, string $idKey = 'id')
 * @method Task[] getIterator()
 * @method static TaskCollection mapFromIds(array $ids, string $idKey = 'id')
 * @method TaskCollection filter(callable $callback)
 */
class TaskCollection extends AbstractEntityCollection
{
	protected static function getEntityClass(): string
	{
		return Task::class;
	}
}
