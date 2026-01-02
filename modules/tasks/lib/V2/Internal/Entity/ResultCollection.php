<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity;

/**
 * @method null|Result findOne(array $conditions)
 * @method null|Result findOneById(int $id, string $idKey = 'id')
 * @method ResultCollection findAll(array $conditions)
 * @method ResultCollection findAllByIds(array $ids, string $idKey = 'id')
 * @method array getEmailList()
 * @method Result[] getIterator()
 * @method static ResultCollection mapFromIds(array $ids, string $idKey = 'id')
 * @method ResultCollection filter(callable $callback)
 */
class ResultCollection extends AbstractEntityCollection
{
	protected static function getEntityClass(): string
	{
		return Result::class;
	}
}
