<?php

namespace Bitrix\ImMobile;

use Bitrix\Main\Loader;
use Bitrix\Im;

class User
{
	public static function getCurrent(): ?Im\V2\Entity\User\User
	{
		if (!Loader::includeModule('im'))
		{
			return null;
		}

		return Im\V2\Entity\User\User::getCurrent();
	}
}
