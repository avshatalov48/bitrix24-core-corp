<?php

namespace Bitrix\Crm\Service\Display\Field;

use Bitrix\Crm\Service\Display\Options;
use Bitrix\Main\Text\HtmlFilter;

class CrmStatusField extends BaseSimpleField
{
	public const TYPE = 'crm_status';

	protected function getFormattedValueForKanban($fieldValue, int $itemId, Options $displayOptions)
	{
		$this->setWasRenderedAsHtml(true);
		$statuses = $this->getStatuses();

		$isMultiple = $this->isMultiple();
		$result = ($isMultiple ? [] : '');

		foreach ((array)$fieldValue as $value)
		{
			if (isset($statuses[$value]))
			{
				$encodedStatusValue = HtmlFilter::encode($statuses[$value]);
				if (!$isMultiple)
				{
					return $encodedStatusValue;
				}

				$result[] = $encodedStatusValue;
			}
		}

		return $result;
	}

	protected function getFormattedValueForGrid($fieldValue, int $itemId, Options $displayOptions)
	{
		$results = $this->getFormattedValueForKanban($fieldValue, $itemId, $displayOptions);
		return (
		is_array($results)
			? implode($displayOptions->getMultipleFieldsDelimiter(), $results)
			: $results
		);
	}

	protected function getFormattedValueForMobile($fieldValue, int $itemId, Options $displayOptions): array
	{
		$statuses = $this->getStatuses();

		return [
			'value' => $fieldValue,
			'config' => $this->getPreparedConfig($statuses),
		];
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
		$entityType = $this->getDisplayParam('ENTITY_TYPE');

		return ($entityType ? \CCrmStatus::GetStatusList($entityType) : []);
	}
}
