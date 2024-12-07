<?php

namespace Bitrix\Sign\Operation\Kanban\B2e;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;
use Bitrix\Sign\Type;

final class SendUpdateEntityPullEvent implements Contract\Operation
{
	public function __construct(
		private readonly Item\Document $document,
	)
	{
	}

	public function launch(): Main\Result
	{
		$result = new Main\Result();

		if (Type\DocumentScenario::isB2EScenario($this->document->scenario ?? '') === false)
		{
			return $result->addError(new Main\Error('Document scenario not b2e'));
		}

		if ($this->document->entityType !== Type\Document\EntityType::SMART_B2E)
		{
			return $result->addError(new Main\Error('Document entityType not b2e'));
		}

		if ($this->document->entityId === null)
		{
			return $result->addError(new Main\Error('Document entityId can\'t be null'));
		}

		if ($this->sendUpdateEntityPullEvent($this->document->entityId) === false)
		{
			return $result->addError(new Main\Error('Can\'t send update entity pull event'));
		}

		return $result;
	}

	private function sendUpdateEntityPullEvent(int $entityId): bool
	{
		if (Loader::includeModule('crm') === false)
		{
			return false;
		}

		$container = \Bitrix\Crm\Service\Container::getInstance();
		if (method_exists($container, 'getSignIntegrationKanbanPullService') === false)
		{
			return false;
		}

		return $container->getSignIntegrationKanbanPullService()->sendB2ePullUpdateEventByEntityId($entityId);
	}
}