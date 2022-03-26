<?php


namespace Bitrix\Crm\Service\Display\Field;


use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Display\Options;

class EnumerationField extends BaseLinkedEntitiesField
{
	protected const TYPE = 'enumeration';

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
		if (
			!isset($linkedEntitiesValues[$elementId])
			|| !is_array($linkedEntitiesValues[$elementId])
		)
		{
			return '';
		}

		return htmlspecialcharsbx($linkedEntitiesValues[$elementId]['VALUE']);
	}
}
