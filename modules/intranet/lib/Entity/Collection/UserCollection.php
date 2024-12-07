<?php

namespace Bitrix\Intranet\Entity\Collection;

use Bitrix\Intranet\Entity\User;

/**
 * @extends BaseCollection<User>
 */
class UserCollection extends BaseCollection
{
	protected static function getItemClassName(): string
	{
		return User::class;
	}
}