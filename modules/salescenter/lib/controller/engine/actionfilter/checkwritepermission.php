<?php

declare(strict_types = 1);

namespace Bitrix\SalesCenter\Controller\Engine\ActionFilter;

use Bitrix\Main;
use Bitrix\SalesCenter;

class CheckWritePermission extends Main\Engine\ActionFilter\Base
{
	public function onBeforeAction(Main\Event $event): ?Main\EventResult
	{
		if (!$this->hasPermission())
		{
			$this->addError(new Main\Error(
				Main\Localization\Loc::getMessage('SALESCENTER_CONTROLLER_ENGINE_ACTIONFILTER_CHECK_WRITE_PERMISSION')
			));

			return new Main\EventResult(Main\EventResult::ERROR, null, null, $this);
		}

		return null;
	}

	protected function hasPermission(): bool
	{
		return SalesCenter\Integration\SaleManager::getInstance()->isFullAccess(true);
	}
}