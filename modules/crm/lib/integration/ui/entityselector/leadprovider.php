<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use Bitrix\Crm\Integration\UI\EntitySelector\Traits\FilterByEmails;
use Bitrix\Crm\LeadTable;
use CCrmOwnerType;

class LeadProvider extends EntityProvider
{
	protected static LeadTable|string $dataClass = LeadTable::class;

	use FilterByEmails;

	public function __construct(array $options = [])
	{
		parent::__construct($options);

		$this->setEmailOnlyMode($options['onlyWithEmail'] ?? false);
	}

	protected function getEntityTypeId(): int
	{
		return CCrmOwnerType::Lead;
	}

	protected function getAdditionalFilter(): array
	{
		$filter = [];

		return array_merge($filter, $this->getEmailFilters());
	}

	protected function fetchEntryIds(array $filter): array
	{
		$collection = static::$dataClass::getList([
			'select' => ['ID'],
			'filter' => array_merge($filter, $this->getAdditionalFilter()),
		])->fetchCollection();

		return $collection->getIdList();
	}
	protected function getDefaultItemAvatar(): ?string
	{
		return '/bitrix/images/crm/entity_provider_icons/lead.svg';
	}
}
