<?php

namespace Bitrix\Crm\UserField\DisplayStrategy;

use Bitrix\Crm\Service\Display\Options;
use Bitrix\Crm\Service\Display\Field;
use Bitrix\Crm\Service\Display;

class BulkStrategy extends BaseStrategy
{
	/** @var Options */
	protected $displayOptions;

	public function setUserFields(array $userFields)
	{
		$this->userFields = $userFields;
	}

	protected function getUserFields(): array
	{
		return $this->userFields;
	}

	public function setDisplayOptions(Options $options): BulkStrategy
	{
		$this->displayOptions = $options;

		return $this;
	}

	public function processValues(array $items): array
	{
		$fields = [];
		foreach($this->getUserFields() as $fieldId => $userFieldData)
		{
			$fields[$fieldId] = Field::createFromUserField($fieldId, $userFieldData);
		}
		$display = new Display($this->entityTypeId, $fields, $this->displayOptions);
		$display->setItems($items);

		return $display->getAllValues();
	}
}
