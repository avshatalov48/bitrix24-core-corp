<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use Bitrix\Crm\Service\Container;

class DynamicProvider extends EntityProvider
{
	public function __construct(array $options = [])
	{
		parent::__construct($options);

		$this->options['dynamicTypeId'] = (int)($options['entityTypeId'] ?? 0);
	}

	protected function getEntityTypeName(): string
	{
		return 'dynamic';
	}

	protected function getEntityTypeId(): int
	{
		return $this->getOption('dynamicTypeId');
	}

	protected function getEntityTypeNameForMakeItemMethod()
	{
		return mb_strtolower(\CCrmOwnerType::ResolveName($this->getEntityTypeId()));
	}

	protected function fetchEntryIds(array $filter): array
	{
		$factory = Container::getInstance()->getFactory($this->getEntityTypeId());
		if ($factory)
		{
			$items = $factory->getItemsFilteredByPermissions([
				'select' => ['ID'],
				'filter' => $filter,
			]);

			$result = [];
			foreach ($items as $item)
			{
				$result[] = $item->getId();
			}

			return $result;
		}

		return [];
	}
}
