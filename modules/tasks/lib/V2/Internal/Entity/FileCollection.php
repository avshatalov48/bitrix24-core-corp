<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity;

/**
 * @method null|File findOne(array $conditions)
 * @method null|File findOneById(int $id, string $idKey = 'id')
 * @method FileCollection findAll(array $conditions)
 * @method FileCollection findAllByIds(array $ids, string $idKey = 'id')
 */
class FileCollection extends AbstractEntityCollection
{
	protected static function getEntityClass(): string
	{
		return File::class;
	}
}