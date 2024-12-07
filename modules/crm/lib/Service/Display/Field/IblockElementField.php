<?php

namespace Bitrix\Crm\Service\Display\Field;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Display\Options;
use Bitrix\Main\Loader;

class IblockElementField extends BaseLinkedEntitiesField
{
	public const TYPE = 'iblock_element';

	public function loadLinkedEntities(array &$linkedEntitiesValues, array $linkedEntity): void
	{
		$linkedEntitiesId = ($linkedEntity['ID'] ?? []);
		$fieldType = $this->getType();
		$linkedEntitiesValues[$fieldType] = Container::getInstance()
			->getIBlockElementBroker()
			->getBunchByIds($linkedEntitiesId)
		;
	}

	protected function getFormattedValueForKanban($fieldValue, int $itemId, Options $displayOptions)
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
		if (
			!isset($linkedEntitiesValues[$elementId])
			|| !is_array($linkedEntitiesValues[$elementId])
		)
		{
			return '';
		}

		$formattedValue = $this->sanitizeString((string)$linkedEntitiesValues[$elementId]['NAME']);
		if ($this->isMobileContext())
		{
			return $formattedValue;
		}

		$linkedEntitiesValue = $linkedEntitiesValues[$elementId];
		$detailUrl = $linkedEntitiesValue['DETAIL_PAGE_URL'] ?? null;
		if (!$detailUrl && Loader::includeModule('lists'))
		{
			$urlTemplate = \CList::getUrlByIblockId($linkedEntitiesValue['IBLOCK_ID'] ?? 0);
			if (!empty($urlTemplate) && is_string($urlTemplate))
			{
				$detailUrl = str_replace(
					['#section_id#', '#element_id#'],
					[
						(int)($linkedEntitiesValue['IBLOCK_SECTION_ID'] ?? 0),
						(int)$linkedEntitiesValue['ID']
					],
					$urlTemplate
				);
			}
		}

		if ($detailUrl)
		{
			$formattedValue = '<a href="' . $detailUrl . '">' . $formattedValue . '</a>';
		}

		return $formattedValue;
	}

	protected function getFormattedValueForExport($fieldValue, int $itemId, Options $displayOptions)
	{
		$results = [];
		$linkedEntitiesValues = $this->getLinkedEntitiesValues();

		if (!$this->isMultiple())
		{
			return $linkedEntitiesValues[$fieldValue]['NAME'];
		}

		$fieldValue = is_array($fieldValue) ? $fieldValue : [$fieldValue];
		foreach ($fieldValue as $elementId)
		{
			$results[] = $linkedEntitiesValues[$elementId]['NAME'];
		}

		return implode($displayOptions->getMultipleFieldsDelimiter(), $results);
	}

	protected function getFormattedValueForMobile($fieldValue, int $itemId, Options $displayOptions): array
	{
		if ($this->isMultiple())
		{
			$elementIds = [];

			foreach ((array)$fieldValue as $value)
			{
				$elementIds[] = (int)$value;
			}

			return [
				'value' => $elementIds,
				'config' => $this->getPreparedConfig($elementIds),
			];
		}

		$elementId = (int)$fieldValue;

		return [
			'value' => $elementId,
			'config' => $this->getPreparedConfig([$elementId]),
		];
	}

	/**
	 * @param array $elementIds
	 * @return array[]
	 */
	protected function getPreparedConfig(array $elementIds): array
	{
		$entityList = [];

		$elements = $this->getLinkedEntitiesValues();

		foreach ($elementIds as $elementId)
		{
			if (!empty($elements[$elementId]))
			{
				$entityList[] = [
					'id' => $elementId,
					'title' => $elements[$elementId]['NAME'],
				];
			}
		}
		return [
			'entityList' => $entityList,
			'selectorType' => $this->getSelectorType(),
		];
	}

	protected function getSelectorType(): string
	{
		return 'iblock-element-user-field';
	}
}
