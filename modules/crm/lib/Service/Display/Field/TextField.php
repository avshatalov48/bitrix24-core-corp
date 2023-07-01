<?php

namespace Bitrix\Crm\Service\Display\Field;

use Bitrix\Crm\Entity\FieldContentType;
use Bitrix\Crm\Format\TextHelper;
use Bitrix\Crm\Model\FieldContentTypeTable;
use Bitrix\Crm\Service\Display\Options;

class TextField extends BaseLinkedEntitiesField
{
	public const TYPE = 'text';

	public function useLinkedEntities(): bool
	{
		return in_array($this->getId(), FieldContentType::getFieldsWithFlexibleContentType($this->entityTypeId), true);
	}

	public function prepareLinkedEntities(array &$linkedEntities, $fieldValue, int $itemId, string $fieldId): void
	{
		$fieldType = $this->getType();
		$linkedEntities[$fieldType]['ITEM_IDS'][$itemId] = $itemId;
	}

	public function loadLinkedEntities(array &$linkedEntitiesValues, array $linkedEntity): void
	{
		$itemIds = $linkedEntity['ITEM_IDS'];
		$fieldType = $this->getType();
		$linkedEntitiesValues[$fieldType] = FieldContentTypeTable::loadForMultipleItems($this->entityTypeId, $itemIds);
	}

	protected function renderSingleValue($fieldValue, int $itemId, Options $displayOptions): string
	{
		$value = parent::renderSingleValue($fieldValue, $itemId, $displayOptions);

		$contentTypeId = $this->getLinkedEntitiesValues()[$itemId][$this->getId()] ?? \CCrmContentType::Undefined;
		if (!\CCrmContentType::IsDefined($contentTypeId))
		{
			$contentTypeId = \CCrmContentType::Html;
		}

		$wasRenderedAsHtml = true;

		if ($contentTypeId === \CCrmContentType::BBCode)
		{
			$wasRenderedAsHtml = false;

			if ($this->isKanbanContext() || $this->isGridContext())
			{
				$value = TextHelper::sanitizeHtml(TextHelper::convertBbCodeToHtml($fieldValue));

				$wasRenderedAsHtml = true;
			}
		}
		else
		{
			$value = TextHelper::sanitizeHtml($value);
		}

		$this->setWasRenderedAsHtml($wasRenderedAsHtml);

		return $value;
	}
}
