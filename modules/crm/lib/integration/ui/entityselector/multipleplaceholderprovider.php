<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Crm\Service\Container;
use Bitrix\DocumentGenerator\DataProviderManager;
use CCrmOwnerType;

final class MultiplePlaceholderProvider extends PlaceholderProvider
{
	public const ENTITY_ID = 'multiple_placeholder';
	protected const ITEM_ID_WHITE_LIST = [];
	protected const INCLUDE_UF_ITEMS_TO_WHITE_LIST = false;

	protected array $entityTypeIds = [];

	public function __construct(array $options = [])
	{
		parent::__construct();

		$this->entityTypeIds = array_filter(
			$options['entityTypeIds'] ?? [],
			static function (int $entityTypeId): bool
			{
				return CCrmOwnerType::IsDefined($entityTypeId)
					&& Container::getInstance()
						->getUserPermissions()
						->entityType()
						->canReadItems($entityTypeId)
				;
			}
		);
		$this->entityTypeIds = array_values($this->entityTypeIds);
	}

	public function isAvailable(): bool
	{
		return count($this->entityTypeIds) > 0;
	}

	protected function makeItems(): array
	{
		$dgManager = DocumentGeneratorManager::getInstance();
		$dpManager = DataProviderManager::getInstance();

		$result = [];
		array_walk($this->entityTypeIds, function (int $entityTypeId) use ($dgManager, $dpManager, &$result)
		{
			$this->entityTypeId = $entityTypeId;

			$providerClassName = $dgManager->getCrmOwnerTypeProvider($entityTypeId, false);
			if ($providerClassName !== null)
			{
				$placeholders = $dpManager->getDefaultTemplateFields($providerClassName, [], [], false);
				$placeholders = $this->filterItems($placeholders);

				$result = array_merge($result, $this->makeItemsAsTree($placeholders));
			}
		});

		return $result;
	}
}
