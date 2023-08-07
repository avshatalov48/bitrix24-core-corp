<?php

namespace Bitrix\Crm\Service\Display\Field;

use Bitrix\Crm\Entity\CommentsHelper;
use Bitrix\Crm\Format\TextHelper;
use Bitrix\Crm\Model\FieldContentTypeTable;
use Bitrix\Crm\Service\Display\Options;

class TextField extends BaseLinkedEntitiesField
{
	public const TYPE = 'text';

	private const CONTENT_TYPE_BB_CODE = 'BBCodeText';
	private const CONTENT_TYPE_TEXT = 'Text';

	public function useLinkedEntities(): bool
	{
		return in_array($this->getId(), CommentsHelper::getFieldsWithFlexibleContentType($this->entityTypeId), true);
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

		$wasRenderedAsHtml = true;

		$contentTypeId = $this->getContentTypeId($itemId);
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

	protected function getFormattedValueForMobile($fieldValue, int $itemId, Options $displayOptions): array
	{
		$result = parent::getFormattedValueForMobile($fieldValue, $itemId, $displayOptions);

		$contentTypeId = $this->getContentTypeId($itemId);
		$result['config']['contentTypeId'] = $contentTypeId;

		if ($contentTypeId === \CCrmContentType::Html)
		{
			$contentTypeId = \CCrmContentType::BBCode;
			if (is_array($result['value']))
			{
				foreach ($result['value'] as &$item)
				{
					$item = $this->convertHtmlToBbCode($item);
				}
				unset($item);
			}
			else
			{
				$result['value'] = $this->convertHtmlToBbCode($result['value']);
			}
		}

		$result['config']['readOnlyElementType'] = (
			$contentTypeId === \CCrmContentType::BBCode
				? self::CONTENT_TYPE_BB_CODE
				: self::CONTENT_TYPE_TEXT
		);

		return $result;
	}

	protected function getContentTypeId(int $itemId): int
	{
		$contentTypeId = $this->getLinkedEntitiesValues()[$itemId][$this->getId()] ?? \CCrmContentType::Undefined;

		return (\CCrmContentType::IsDefined($contentTypeId) ? $contentTypeId : \CCrmContentType::Html);
	}

	protected function convertHtmlToBbCode(string $string): string
	{
		return CommentsHelper::normalizeComment($string);
	}
}
