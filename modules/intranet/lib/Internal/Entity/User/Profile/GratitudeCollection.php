<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Entity\User\Profile;

use Bitrix\Intranet\Internal\Entity\User\Profile\Gratitude;
use Bitrix\Main\Entity\EntityCollection;

class GratitudeCollection extends EntityCollection
{
	protected static function getEntityClass(): string
	{
		return Gratitude::class;
	}
}
