<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Disk\Entity;

use Bitrix\Tasks\V2\Internal\Entity\AbstractEntityCollection;

/**
 * @method null|DiskFile findOne(array $conditions)
 * @method null|DiskFile findOneById(int $id, string $idKey = 'id')
 * @method DiskFileCollection findAll(array $conditions)
 * @method DiskFileCollection findAllByIds(array $ids, string $idKey = 'id')
 */
class DiskFileCollection extends AbstractEntityCollection
{
	protected static function getEntityClass(): string
	{
		return DiskFile::class;
	}
}