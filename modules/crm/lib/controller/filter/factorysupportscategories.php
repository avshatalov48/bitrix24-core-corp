<?php

namespace Bitrix\Crm\Controller\Filter;

use Bitrix\Crm\Contract\FactoryInjectable;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class FactorySupportsCategories extends \Bitrix\Main\Engine\ActionFilter\Base
{
	public function onBeforeAction(Event $event)
	{
		$factory = $this->tryGetFactoryFromController() ?? $this->tryGetFactoryFromRequest();

		if (!$factory || !$factory->isCategoriesSupported())
		{
			$entityTypeId = $factory ? $factory->getEntityTypeId() : null;

			$this->addError(ErrorCode::getEntityTypeNotSupportedError($entityTypeId));
		}

		return new EventResult(
			$this->errorCollection->isEmpty() ? EventResult::SUCCESS : EventResult::ERROR,
			null,
			null,
			$this
		);
	}

	protected function tryGetFactoryFromController(): ?\Bitrix\Crm\Service\Factory
	{
		$controller = $this->getAction()->getController();
		if ($controller instanceof FactoryInjectable)
		{
			return $controller->getFactory();
		}

		return null;
	}

	protected function tryGetFactoryFromRequest(): ?\Bitrix\Crm\Service\Factory
	{
		$entityTypeId = $this->getAction()->getController()->getRequest()->get('entityTypeId') ?? 0;

		return Container::getInstance()->getFactory($entityTypeId);
	}
}
