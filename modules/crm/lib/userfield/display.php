<?php

namespace Bitrix\Crm\UserField;

use \Bitrix\Crm\Service\Factory;

class Display
{
	/** @var Factory */
	protected $factory;
	protected $values;
	protected $processedValues;
	protected $userFields;

	public function __construct(Factory $factory, array $values = [])
	{
		$this->factory = $factory;
		$this->values = $values;
		$this->processedValues = [];

		global $USER_FIELD_MANAGER;
		$this->userFields = $USER_FIELD_MANAGER->getUserFields($this->factory->getUserFieldEntityId(), 0, LANGUAGE_ID);
	}

	public function addValues(int $itemId, array $values): Display
	{
		if(!isset($this->values[$itemId]))
		{
			$this->values[$itemId] = [];
		}

		$this->values[$itemId] = array_merge($this->values[$itemId], $values);

		return $this;
	}

	protected function processValues(): array
	{
		$view = new \Bitrix\Main\UserField\Display(\Bitrix\Main\UserField\Display::MODE_VIEW);
		$view->setAdditionalParameter('FILE_MAX_WIDTH', 300, true);
		$view->setAdditionalParameter('FILE_SHOW_POPUP', 'Y', true);
		$view->setAdditionalParameter('FILE_MAX_HEIGHT', 300, true);

		foreach($this->values as $id => $values)
		{
			if (!empty($this->processedValues[$id]))
			{
				continue;
			}

			foreach($values as $fieldName => $value)
			{
				if(!empty($this->getUserFields()[$fieldName]))
				{
					$userField = $this->getUserFields()[$fieldName];
					$userField['VALUE'] = $value;

					$view->setField($userField);
					$this->processedValues[$id][$fieldName] = $view->display();
					$view->clear();
				}
			}
		}

		return $this->processedValues;
	}

	protected function getUserFields(): array
	{
		return $this->userFields;
	}

	public function getAllValues(): array
	{
		return $this->processValues();
	}

	public function getValues(int $itemId): ?array
	{
		return $this->processValues()[$itemId];
	}

	public function getValue(int $itemId, string $fieldName): ?string
	{
		$values = $this->getValues($itemId);
		if(!$values)
		{
			return null;
		}

		return $values[$fieldName];
	}
}