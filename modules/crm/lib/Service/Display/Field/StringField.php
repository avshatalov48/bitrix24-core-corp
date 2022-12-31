<?php

namespace Bitrix\Crm\Service\Display\Field;

use Bitrix\Crm\Service\Display\Options;

class StringField extends BaseSimpleField
{
	public const TYPE = 'string';

	protected bool $hasHtmlTags = false;

	public function prepareField(): void
	{
		$valueType = $this->getDisplayParam(
			'VALUE_TYPE',
			\Bitrix\Crm\Field::VALUE_TYPE_PLAIN_TEXT
		);

		if ($valueType === \Bitrix\Crm\Field::VALUE_TYPE_HTML)
		{
			$this->setWasRenderedAsHtml(true);
		}
	}

	protected function getFormattedValueForGrid($fieldValue, int $itemId, Options $displayOptions)
	{
		$isMultiple = $this->isMultiple();

		if ($isMultiple && is_array($fieldValue))
		{
			$values = $this->getPreparedArrayValues($fieldValue);
			return $this->render($displayOptions, $itemId, $values);
		}

		return $this->renderSingleValue($fieldValue, $itemId, $displayOptions);
	}

	protected function renderSingleValue($fieldValue, int $itemId, Options $displayOptions): string
	{
		$value = parent::renderSingleValue($fieldValue, $itemId, $displayOptions);

		if (
			$this->wasRenderedAsHtml
			&& ($this->isMobileContext() || $this->isExportContext())
		)
		{
			$strippedValue = strip_tags($value);
			if ($strippedValue !== $value)
			{
				$this->hasHtmlTags = true;
				$value = $strippedValue;
			}
		}

		if (!$this->isMobileContext())
		{
			$value = preg_replace('/\s+/', ' ', $value);
		}

		return trim($value);
	}

	protected function getMobileConfig($fieldValue, int $itemId, Options $displayOptions): array
	{
		$config = parent::getMobileConfig($fieldValue, $itemId, $displayOptions);

		$config['editable'] = !$this->hasHtmlTags;

		return $config;
	}
}
