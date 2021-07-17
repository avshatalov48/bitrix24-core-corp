<?php

namespace Bitrix\Crm\Controller\Filter;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;

class Factory extends \Bitrix\Main\Engine\ActionFilter\Base
{
	public function onBeforeAction(Event $event)
	{
		$entityTypeId = $this->getAction()->getController()->getRequest()->get('entityTypeId') ?? 0;
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			$this->addError(new Error(
				Loc::getMessage('CRM_TYPE_TYPE_NOT_FOUND'),
				\Bitrix\Crm\Controller\Base::ERROR_CODE_NOT_FOUND)
			);
		}

		$this->getAction()->getController()->setFactory($factory);

		return new EventResult(
			$this->errorCollection->isEmpty() ? EventResult::SUCCESS : EventResult::ERROR,
			null,
			null,
			$this
		);
	}
}