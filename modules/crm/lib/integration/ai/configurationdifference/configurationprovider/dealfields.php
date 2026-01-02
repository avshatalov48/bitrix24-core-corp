<?php

namespace Bitrix\Crm\Integration\AI\ConfigurationDifference\ConfigurationProvider;

use Bitrix\Crm\Entity\EntityEditorConfig;
use Bitrix\Crm\Entity\EntityEditorConfigScope;
use Bitrix\Crm\Integration\AI\ConfigurationDifference\Contract\ConfigurationProvider;
use Bitrix\Crm\Integration\AI\ConfigurationDifference\DifferenceItem;
use Bitrix\Crm\Integration\AI\ConfigurationDifference\DifferenceItemCollection;
use Bitrix\Crm\Integration\UI\EntityEditor\Configuration;
use Bitrix\Crm\Integration\UI\EntityEditor\DefaultEntityConfig\DealDefaultEntityConfig;

class DealFields implements ConfigurationProvider
{
	public function __construct(
		private readonly int $userId,
	)
	{
	}

	public function name(): string
	{
		return 'deal_fields';
	}

	public function default(): DifferenceItemCollection
	{
		$configData = (new DealDefaultEntityConfig())->get();
		$config = Configuration::fromArray($configData);

		$collection = new DifferenceItemCollection();
		foreach ($config->getElements() as $element)
		{
			$collection->push(new DifferenceItem($element->getName(), []));
		}

		return $collection;
	}

	public function actual(): DifferenceItemCollection
	{
		$entityEditorConfig = new EntityEditorConfig(
			entityTypeID: \CCrmOwnerType::Deal,
			userID: $this->userId,
			scope: EntityEditorConfigScope::COMMON,
			extras: [
				'CATEGORY_ID' => 0,
			],
		);

		$config = $entityEditorConfig->getConfiguration(useDefaultIfNotExists: true);
		if ($config === null)
		{
			return new DifferenceItemCollection();
		}

		$collection = new DifferenceItemCollection();
		foreach ($config->getElements() as $element)
		{
			$collection->push(new DifferenceItem($element->getName(), []));
		}

		return $collection;
	}

	public function fields(): array
	{
		return [];
	}
}
