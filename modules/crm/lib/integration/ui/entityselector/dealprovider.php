<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use Bitrix\Crm\DealTable;
use Bitrix\Crm\Relation\EntityRelationTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use CCrmOwnerType;

class DealProvider extends EntityProvider
{
	protected static DealTable|string $dataClass = DealTable::class;

	public function getRecentItemIds(string $context): array
	{
		if ($this->notLinkedOnly)
		{
			$ids = $this->getNotLinkedEntityIds();
		}
		else
		{
			$ids = parent::getRecentItemIds($context);
		}

		return $ids;
	}

	protected function getTabIcon(): string
	{
		return 'o-handshake';
	}

	protected function getEntityTypeId(): int
	{
		return CCrmOwnerType::Deal;
	}

	protected function fetchEntryIds(array $filter): array
	{
		$collection = static::$dataClass::getList([
			'select' => ['ID'],
			'filter' => $filter,
		])->fetchCollection();

		return $collection->getIdList();
	}

	protected function getDefaultItemAvatar(): ?string
	{
		return '/bitrix/images/crm/entity_provider_icons/deal.svg';
	}

	protected function getAdditionalFilter(): array
	{
		$filter = [];

		if ($this->notLinkedOnly)
		{
			$filter = $this->getNotLinkedFilter();
		}

		return $filter;
	}
}
