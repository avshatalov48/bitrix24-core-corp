<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity;

class UserFieldCollection extends AbstractEntityCollection
{
	protected static function getEntityClass(): string
	{
		return UserField::class;
	}
}
