<?php

namespace Bitrix\Sign\Access\Service;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Sign\Access\AccessController;
use Bitrix\Sign\Access\ActionDictionary;

final class AccessService
{
	public function isUserHaveAccessToB2eSign(int $userId): bool
	{
		$controller = $this->getAccessController($userId);
		if ($controller === null)
		{
			return false;
		}

		return $controller->checkAny(ActionDictionary::getB2eSectionAccessActions());
	}

	public function isCurrentUserHaveAccessToB2eSign(): bool
	{
		$userId = (int)CurrentUser::get()->getId();

		return $this->isUserHaveAccessToB2eSign($userId);
	}

	private function getAccessController(int $userId): ?AccessController
	{
		if ($userId < 1)
		{
			return null;
		}

		return new AccessController($userId);
	}
}