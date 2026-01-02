<?php

namespace Bitrix\Intranet\Internal\Entity\Collection;

use Bitrix\Intranet\Internal\Entity\UserFieldSection;
use Bitrix\Main\Entity\EntityCollection;

class UserFieldSectionCollection extends EntityCollection
{
	protected static function getEntityClass(): string
	{
		return UserFieldSection::class;
	}
}
