<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity;

/**
 * @method null|Tag findOne(array $conditions)
 * @method null|Tag findOneById(int $id, string $idKey = 'id')
 * @method TagCollection findAll(array $conditions)
 * @method TagCollection findAllByIds(array $ids, string $idKey = 'id')
 * @method array getNameList()
 * @method Tag[] getIterator()
 * @method static TagCollection mapFromIds(array $ids, string $idKey = 'id')
 * @method TagCollection filter(callable $callback)
 */
class TagCollection extends AbstractEntityCollection
{
	protected static function getEntityClass(): string
	{
		return Tag::class;
	}
}
