<?php

namespace Bitrix\Crm\Component\EntityDetails\Traits;

use Bitrix\Crm\FieldContext\ContextManager;
use Bitrix\Crm\FieldContext\Repository;

trait InitializeAdditionalFieldsData
{
	protected function getAdditionalFieldsData(): array
	{
		$entityId = $this->entityID;
		if ($entityId <= 0)
		{
			return [];
		}

		$repository = Repository::createFromId($this->factory->getEntityTypeId(), $entityId);
		if (!$repository)
		{
			return [];
		}

		$contextManager = new ContextManager();

		return [
			'context' => [
				'data' => $contextManager->getData(),
				'fields' => $repository->getFieldsData(),
			],
		];
	}
}
