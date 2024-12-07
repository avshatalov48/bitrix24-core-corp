<?php

namespace Bitrix\Crm\Filter;


use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Filter\Settings;

class ActivityFastSearchSettings extends Settings
{
	private ?int $parentFilterEntityTypeId = null;

	private EntityDataProvider $parentEntityDataProvider;

	function __construct(array $params)
	{
		parent::__construct($params);

		if (!isset($params['PARENT_ENTITY_DATA_PROVIDER']))
		{
			throw new ArgumentNullException('PARENT_ENTITY_DATA_PROVIDER');
		}

		$this->parentFilterEntityTypeId = $params['PARENT_FILTER_ENTITY_TYPE_ID'] ?? null;
		$this->parentEntityDataProvider = $params['PARENT_ENTITY_DATA_PROVIDER'];
	}

	public function getEntityTypeID(): int
	{
		return \CCrmOwnerType::Activity;
	}

	public function getParentFilterEntityTypeId(): ?int
	{
		return $this->parentFilterEntityTypeId;
	}

	public function getParentEntityDataProvider(): EntityDataProvider
	{
		return $this->parentEntityDataProvider;
	}
}
