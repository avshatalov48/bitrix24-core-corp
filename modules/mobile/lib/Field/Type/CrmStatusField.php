<?php

namespace Bitrix\Mobile\Field\Type;

use Bitrix\Main\Loader;

class CrmStatusField extends BaseField
{
	public const TYPE = 'crm_status';

	/**
	 * @inheritDoc
	 */
	public function getFormattedValue()
	{
		if (!Loader::includeModule('crm'))
		{
			return null;
		}

		return $this->getValue();
	}

	public function getData(): array
	{
		$data = parent::getData();

		$statuses = $this->getStatuses();
		$preparedConfig = $this->getPreparedConfig($statuses);

		return array_merge($data, $preparedConfig);
	}

	/**
	 * @param array $values
	 * @return array[]
	 */
	protected function getPreparedConfig(array $statuses): array
	{
		$items = [];

		foreach ($statuses as $id => $name)
		{
			$items[] = [
				'value' => $id,
				'name' => $name,
			];
		}

		return [
			'items' => $items,
		];
	}

	/**
	 * @return array
	 */
	protected function getStatuses(): array
	{
		$entityType = $this->getUserFieldInfo()['SETTINGS']['ENTITY_TYPE'];

		return ($entityType ? \CCrmStatus::GetStatusList($entityType) : []);
	}
}
