<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Controller\User;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;

use Bitrix\Intranet\Dto\User\MiniProfile\UserMiniProfileDto;
use Bitrix\Intranet\Result\Service\User\MiniProfileDataResult;
use Bitrix\Intranet\Service\ServiceContainer;

class MiniProfile extends Controller
{
	public function loadAction(int $userId): UserMiniProfileDto | array
	{
		$currentUserId = (int)$this->getCurrentUser()?->getId();
		if (!$currentUserId)
		{
			$this->addError(new Error('', 'ACCESS_DENIED'));

			return [];
		}

		$result = ServiceContainer::getInstance()->getUserMiniProfileService()
			->getData($currentUserId, $userId)
		;

		if (!$result instanceof MiniProfileDataResult)
		{
			$this->addErrors($result->getErrors());

			return [];
		}

		return $result->userMiniProfileDto;
	}
}
