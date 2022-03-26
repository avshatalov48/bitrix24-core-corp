<?php


namespace Bitrix\Crm\Service\Display\Field;


use Bitrix\Crm\Service\Display\Field;
use Bitrix\Crm\Service\Display\Options;

abstract class BaseSimpleField extends Field
{
	protected function getFormattedValueForKanban($fieldValue, int $itemId, Options $displayOptions)
	{
		if ($this->isMultiple())
		{
			$values = $this->getPreparedArrayValues($fieldValue);

			$result = [];
			if (!empty($values))
			{
				foreach ($values as $valueArrayItem)
				{
					$result[] = $this->render($displayOptions, $itemId, [$valueArrayItem]);
				}
			}

			return $result;
		}

		return $this->renderSingleValue($fieldValue, $itemId, $displayOptions);
	}

	protected function getFormattedValueForGrid($fieldValue, int $itemId, Options $displayOptions)
	{
		if ($this->isMultiple())
		{
			return $this->render($displayOptions, $itemId, $fieldValue);
		}

		return $this->renderSingleValue($fieldValue, $itemId, $displayOptions);
	}
}
