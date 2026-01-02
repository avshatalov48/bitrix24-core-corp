<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\CRM\Entity;

use Bitrix\Tasks\V2\Internal\Entity\AbstractEntityCollection;

/**
 * @method null|CrmItem findOne(array $conditions)
 * @method null|CrmItem findOneById(int $id, string $idKey = 'id')
 * @method CrmItemCollection findAll(array $conditions)
 * @method CrmItemCollection findAllByIds(array $ids, string $idKey = 'id')
 * @method array getNameList()
 * @method CrmItem[] getIterator()
 * @method static CrmItemCollection mapFromIds(array $ids, string $idKey = 'id')
 * @method CrmItemCollection filter(callable $callback)
 */
class CrmItemCollection extends AbstractEntityCollection
{
	protected static function getEntityClass(): string
	{
		return CrmItem::class;
	}
}
