<?php

namespace Bitrix\Crm\Controller\Filter;

use Bitrix\Crm\Contract\FactoryInjectable;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class Factory extends \Bitrix\Main\Engine\ActionFilter\Base
{
	public function onBeforeAction(Event $event)
	{
		$entityTypeId = (int)($this->getAction()->getController()->getRequest()->get('entityTypeId') ?? 0);
		if ($entityTypeId <= 0 && Loader::includeModule('rest'))
		{
			$restData = \CRestUtil::getRequestData();
			$entityTypeId = (int)($restData['entityTypeId'] ?? 0);
		}
		$factory = Container::getInstance()->getFactory($entityTypeId);

		if ($factory)
		{
			$controller = $this->getAction()->getController();
			if ($controller instanceof FactoryInjectable)
			{
				$controller->setFactory($factory);
			}
		}
		else
		{
			$this->addError(new Error(
				Loc::getMessage('CRM_TYPE_TYPE_NOT_FOUND'),
				ErrorCode::NOT_FOUND)
			);
		}

		return new EventResult(
			$this->errorCollection->isEmpty() ? EventResult::SUCCESS : EventResult::ERROR,
			null,
			null,
			$this
		);
	}
}
