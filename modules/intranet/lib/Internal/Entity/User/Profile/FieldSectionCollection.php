<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Entity\User\Profile;

use Bitrix\Intranet\Internal\Entity\User\Profile\FieldSection;
use Bitrix\Main\Entity\EntityCollection;

class FieldSectionCollection extends EntityCollection
{
	protected static function getEntityClass(): string
	{
		return FieldSection::class;
	}
}
