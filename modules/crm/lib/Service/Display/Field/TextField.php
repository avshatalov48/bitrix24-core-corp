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
				$hasParagraph = preg_match('/\[p]/i', $fieldValue) === 1;
				$useTypography = $hasParagraph;

				$value = TextHelper::sanitizeHtml(TextHelper::convertBbCodeToHtml($fieldValue, $useTypography));
				if ($useTypography)
				{
					$value = "<div class='crm-bbcode-container --{$this->getContext()}'>{$value}</div>";
				}

				$wasRenderedAsHtml = true;
			}
		}
		elseif (str_ends_with($this->getId(), '_COMMENTS'))
		{
			$hasParagraph = preg_match('/\[p]/i', $value) === 1;
			$useTypography = $hasParagraph;

			$value = TextHelper::sanitizeHtml(TextHelper::convertBbCodeToHtml($value));
			if ($useTypography && !$this->isMobileContext())
			{
				$value = "<div class='crm-bbcode-container'>{$value}</div>";
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

		if ($this->getId() === 'COMMENTS' && $contentTypeId === \CCrmContentType::Html)
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
		// Temporarily removes [p] for mobile compatibility
		else if ($this->getId() === 'COMMENTS' && $contentTypeId === \CCrmContentType::BBCode)
		{
			if (is_array($result['value']))
			{
				foreach ($result['value'] as &$item)
				{
					$item = TextHelper::removeParagraphs($item);
				}
				unset($item);
			}
			else
			{
				$result['value'] = TextHelper::removeParagraphs($result['value']);
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
