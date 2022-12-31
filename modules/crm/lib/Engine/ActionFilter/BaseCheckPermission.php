<?php

declare(strict_types = 1);

namespace Bitrix\Crm\Engine\ActionFilter;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

abstract class BaseCheckPermission extends ActionFilter\Base
{
	protected UserPermissions $userPermissions;

	public function __construct()
	{
		parent::__construct();
		$this->userPermissions = Container::getInstance()->getUserPermissions();
	}

	public function onBeforeAction(Event $event): ?EventResult
	{
		foreach ($this->action->getArguments() as $argument)
		{
			if ($argument instanceof Item)
			{
				$entityTypeId = $argument->getEntityTypeId();
				$entityId = $argument->getId();
				$categoryId = $argument->isCategoriesSupported() ? $argument->getCategoryId() : null;

				if (!$this->checkItemPermission($entityTypeId, $entityId, $categoryId))
				{
					$this->addPermissionError();

					return new EventResult(EventResult::ERROR, null, null, $this);
				}
			}
		}

		return null;
	}

	abstract protected function checkItemPermission(
		int $entityTypeId,
		int $entityId = 0,
		?int $categoryId = null
	): bool;

	protected function addPermissionError(): void
	{
		$this->errorCollection[] = ErrorCode::getAccessDeniedError();
	}
}
