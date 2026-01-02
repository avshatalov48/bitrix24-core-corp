<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity\Task;

use Bitrix\Tasks\V2\Internal\Entity\AbstractEntityCollection;

/**
 * @method null|Timer findOne(array $conditions)
 * @method null|Timer findOneById(int $id, string $idKey = 'id')
 * @method TimerCollection findAll(array $conditions)
 * @method TimerCollection findAllByIds(array $ids, string $idKey = 'id')
 * @method Timer[] getIterator()
 */
class TimerCollection extends AbstractEntityCollection
{
	protected static function getEntityClass(): string
	{
		return Timer::class;
	}
}
