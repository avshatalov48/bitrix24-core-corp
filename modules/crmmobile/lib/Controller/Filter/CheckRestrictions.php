<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Controller\Filter;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Item;
use Bitrix\CrmMobile\Entity\RestrictionManager;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

final class CheckRestrictions extends ActionFilter\Base
{
	public function onBeforeAction(Event $event): ?EventResult
	{
		foreach ($this->action->getArguments() as $argument)
		{
			if ($argument instanceof Item)
			{
				$entityTypeId = $argument->getEntityTypeId();

				if (RestrictionManager::isEntityRestricted($entityTypeId))
				{
					$this->addRestrictionError();

					return new EventResult(EventResult::ERROR, null, null, $this);
				}
			}
		}

		return null;
	}

	private function addRestrictionError(): void
	{
		$this->errorCollection[] = ErrorCode::getAccessDeniedError();
	}
}
