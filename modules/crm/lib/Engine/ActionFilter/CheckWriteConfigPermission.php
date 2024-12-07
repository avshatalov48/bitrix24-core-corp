<?php

namespace Bitrix\Crm\Engine\ActionFilter;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Application;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

final class CheckWriteConfigPermission extends ActionFilter\Base
{
	protected UserPermissions $userPermissions;

	public function __construct()
	{
		parent::__construct();
		$this->userPermissions = Container::getInstance()->getUserPermissions();
	}

	public function onBeforeAction(Event $event): ?EventResult
	{
		if (!$this->userPermissions->canWriteConfig())
		{
			$this->errorCollection[] = ErrorCode::getAccessDeniedError();

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}
}
