<?php

namespace Bitrix\Crm\Service\Display\Field;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Display\Options;

class EnumerationField extends BaseLinkedEntitiesField
{
	public const TYPE = 'enumeration';

	public function loadLinkedEntities(array &$linkedEntitiesValues, array $linkedEntity): void
	{
		$linkedEntitiesId = $linkedEntity['ID'];
		$fieldType = $this->getType();
		$linkedEntitiesValues[$fieldType] = Container::getInstance()
			->getEnumerationBroker()
			->getBunchByIds($linkedEntitiesId)
		;
	}

	protected function getFormattedValueForKanban($fieldValue, ?int $itemId = null, ?Options $displayOptions = null)
	{
		$this->setWasRenderedAsHtml(true);

		$results = [];
		$fieldValue = is_array($fieldValue) ? $fieldValue : [$fieldValue];
		foreach ($fieldValue as $elementId)
		{
			if (!$this->isMultiple())
			{
				return $this->getPreparedValue($elementId);
			}

			$preparedValue = $this->getPreparedValue($elementId);
			if ($preparedValue !== '')
			{
				$results[] = $preparedValue;
			}
		}

		return $results;
	}

	/**
	 * @param int|string $elementId
	 * @return string
	 */
	protected function getPreparedValue($elementId): string
	{
		$linkedEntitiesValues = $this->getLinkedEntitiesValues();
		if (!isset($linkedEntitiesValues[$elementId]['VALUE']))
		{
			return '';
		}

		return $this->sanitizeString((string)$linkedEntitiesValues[$elementId]['VALUE']);
	}

	protected function getFormattedValueForMobile($fieldValue, int $itemId, Options $displayOptions): array
	{
		$values = [];
		$items = [];

		if (!empty($fieldValue))
		{
			foreach ((array)$fieldValue as $value)
			{
				if ($value)
				{
					$values[] = $value;
					$items[] = [
						'value' => $value,
						'name' => $this->getPreparedValue($value),
					];
				}
			}
		}

		return [
			'value' => $this->isMultiple() ? $values : ($values[0] ?? null),
			'config' => [
				'items' => $items,
			],
		];
	}
}
