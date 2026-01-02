<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity\CheckList;

use Bitrix\Tasks\V2\Internal\Entity\AbstractEntityCollection;

class UserOptionCollection extends AbstractEntityCollection
{
	protected static function getEntityClass(): string
	{
		return UserOption::class;
	}
}
