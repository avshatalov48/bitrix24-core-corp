<?php

namespace Bitrix\Intranet\Entity\Collection;

use Bitrix\Intranet\Entity\Type\Email;

/**
 * @extends BaseCollection<Email>
 */
class EmailCollection extends BaseCollection
{
	protected static function getItemClassName(): string
	{
		return Email::class;
	}
}