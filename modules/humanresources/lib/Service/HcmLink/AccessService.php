<?php

namespace Bitrix\HumanResources\Service\HcmLink;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main;
use Bitrix\Sign;

class AccessService
{
	public function __construct() {}

	public function canRead(): bool
	{
		$user = CurrentUser::get();
		if (!$user->getId())
		{
			return false;
		}

		if (Main\Loader::includeModule('sign'))
		{
			$accessController = (new Sign\Access\AccessController($user->getId()));

			return $accessController->check(Sign\Access\ActionDictionary::ACTION_B2E_DOCUMENT_ADD);
		}

		return $user->isAdmin();
	}
}