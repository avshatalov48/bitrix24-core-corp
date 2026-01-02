<?php

namespace Bitrix\Crm\Integration\AI\ContextCollector\UserFieldsReceiveStrategy;

use Bitrix\Crm\Entity\EntityEditorConfig;
use Bitrix\Crm\Field\Collection;
use Bitrix\Crm\Integration\AI\ContextCollector\UserFieldsReceiveStrategy;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Loader;

final class ViaCardView implements UserFieldsReceiveStrategy
{
	public function __construct(
		private readonly ?Factory $factory,
		private readonly int $categoryId,
		private readonly int $userId,
	)
	{
	}

	public function getAll(): Collection
	{
		$resultCollection = new Collection();

		if ($this->factory === null || !Loader::includeModule('ui'))
		{
			return $resultCollection;
		}

		$entityEditorConfig = EntityEditorConfig::createWithCurrentScope(
			entityTypeId: $this->factory->getEntityTypeId(),
			extras: [
				'CATEGORY_ID' => $this->categoryId,
				'DEAL_CATEGORY_ID' => $this->categoryId,
				'USER_ID' => $this->userId,
			],
		);

		$configuration = $entityEditorConfig->getConfiguration();
		if ($configuration === null)
		{
			return $resultCollection;
		}

		$originalUserFields = $this->factory->getUserFieldsCollection();
		if ($originalUserFields->isEmpty())
		{
			return $resultCollection;
		}

		foreach ($configuration->getElements() as $fieldFromConfiguration)
		{
			if (!$fieldFromConfiguration->isShowAlways())
			{
				continue;
			}

			$originalField = $originalUserFields->getField($fieldFromConfiguration->getName());
			if ($originalField === null)
			{
				continue;
			}

			$resultCollection->push($originalField);
		}

		return $resultCollection;
	}
}
