<?php

namespace Bitrix\Intranet\Entity\Collection;

use Bitrix\Intranet\Entity\Type\Phone;

/**
 * @extends BaseCollection<Phone>
 */
class PhoneCollection extends BaseCollection
{
	protected static function getItemClassName(): string
	{
		return Phone::class;
	}
}