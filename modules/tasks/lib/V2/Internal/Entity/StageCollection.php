<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity;

/**
 * @method Stage[] getIterator()
 */
class StageCollection extends AbstractEntityCollection
{
	protected static function getEntityClass(): string
	{
		return Stage::class;
	}
}
