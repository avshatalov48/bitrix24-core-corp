<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Entity\User\Profile;

use Bitrix\Intranet\Internal\Entity\IdentifiableEntityCollection;

class BaseInfoCollection extends IdentifiableEntityCollection
{
	protected static function getEntityClass(): string
	{
		return BaseInfo::class;
	}
}
