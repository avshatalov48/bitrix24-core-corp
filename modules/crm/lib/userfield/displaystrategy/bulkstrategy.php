<?php

namespace Bitrix\Crm\UserField\DisplayStrategy;

use Bitrix\Crm\Service\Display\Options;
use Bitrix\Crm\Service\Display\Field;
use Bitrix\Crm\Service\Display;

class BulkStrategy extends BaseStrategy
{
	/** @var Options */
	protected $displayOptions;
	protected $context;

	public function setDisplayOptions(Options $options): BulkStrategy
	{
		$this->displayOptions = $options;

		return $this;
	}

	public function processValues(array $items): array
	{
		$fields = [];
		$context = $this->getContext();
		foreach($this->getUserFields() as $fieldId => $userFieldData)
		{
			$fields[$fieldId] = (
				Field::createFromUserField($fieldId, $userFieldData)
					->setContext($context)
			);
		}
		$display = new Display($this->entityTypeId, $fields, $this->displayOptions);
		$display->setItems($items);

		return $display->getAllValues();
	}

	/**
	 * @return string|null
	 */
	public function getContext(): ?string
	{
		return $this->context;
	}

	/**
	 * @param string $context
	 * @return $this
	 */
	public function setContext(string $context): BaseStrategy
	{
		$this->context = $context;
		return $this;
	}
}
