<?php

namespace Bitrix\StaffTrack\Trait;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\StaffTrack\Internals\Exception\IntranetUserException;
use Bitrix\StaffTrack\Internals\Exception\UserNotFoundException;

trait CurrentUserTrait
{
	/**
	 * @return int
	 * @throws IntranetUserException
	 * @throws LoaderException
	 * @throws UserNotFoundException
	 */
	private function getCurrentUserId(): int
	{
		$result = CurrentUser::get()->getId();

		if ($result === null)
		{
			throw new UserNotFoundException();
		}

		if (Loader::includeModule('intranet') && !\Bitrix\Intranet\Util::isIntranetUser())
		{
			throw new IntranetUserException();
		}

		return $result;
	}
}