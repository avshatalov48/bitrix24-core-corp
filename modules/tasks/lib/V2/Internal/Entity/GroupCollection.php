<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity;

/**
 * @method null|Group findOne(array $conditions)
 * @method null|Group findOneById(int $id, string $idKey = 'id')
 * @method GroupCollection findAll(array $conditions)
 * @method GroupCollection findAllByIds(array $ids, string $idKey = 'id')
 * @method array getEmailList()
 * @method Group[] getIterator()
 * @method static GroupCollection mapFromIds(array $ids, string $idKey = 'id')
 * @method GroupCollection filter(callable $callback)
 */
class GroupCollection extends AbstractEntityCollection
{
	protected static function getEntityClass(): string
	{
		return Group::class;
	}
}
