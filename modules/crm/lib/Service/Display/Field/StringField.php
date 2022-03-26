<?php

namespace Bitrix\Crm\Service\Display\Field;

use Bitrix\Crm\Service\Display\Options;

class StringField extends BaseSimpleField
{
	protected const TYPE = 'string';

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
}
