<?php


namespace Bitrix\Crm\Service\Display\Field;


use Bitrix\Crm\Service\Container;

class IblockSectionField extends IblockElementField
{
	protected const TYPE = 'iblock_section';

	public function loadLinkedEntities(array &$linkedEntitiesValues, array $linkedEntity): void
	{
		$linkedEntitiesId = ($linkedEntity['ID'] ?? []);
		$fieldType = $this->getType();
		$linkedEntitiesValues[$fieldType] = Container::getInstance()
			->getIBlockSectionBroker()
			->getBunchByIds($linkedEntitiesId)
		;
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

		return htmlspecialcharsbx($linkedEntitiesValues[$elementId]['NAME']);
	}
}
