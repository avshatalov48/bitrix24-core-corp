<?php

namespace Bitrix\StaffTrack\Controller\Trait;

use Bitrix\Main\Loader;

trait IntranetUserTrait
{
	/**
	 * @param int $userId
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function isIntranetUser(int $userId): bool
	{
		if (empty($userId))
		{
			return false;
		}

		if (Loader::includeModule('intranet') && !\Bitrix\Intranet\Util::isIntranetUser($userId))
		{
			return false;
		}

		return true;
	}
}
