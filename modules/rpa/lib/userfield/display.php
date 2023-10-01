<?php

namespace Bitrix\Rpa\UserField;

use Bitrix\Rpa\Driver;
use Bitrix\Rpa\Model\Type;

class Display
{
	protected $type;
	protected $userFieldCollection;
	protected $values;
	protected $data;
	protected $processedValues;

	public function __construct(Type $type, array $values = [])
	{
		$this->type = $type;
		$this->userFieldCollection = $type->getUserFieldCollection();
		$this->values = $values;
		$this->data = [];
		$this->processedValues = [];
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

	protected function processValues()
	{
		if(empty($this->processedValues))
		{
			$view = new \Bitrix\Main\UserField\Display(\Bitrix\Main\UserField\Display::MODE_VIEW);
			$view->setAdditionalParameter('FILE_MAX_WIDTH', 300, true);
			$view->setAdditionalParameter('FILE_SHOW_POPUP', 'Y', true);
			$view->setAdditionalParameter('FILE_MAX_HEIGHT', 300, true);
			\CFile::DisableJSFunction(true);

			foreach($this->values as $id => $values)
			{
				foreach($values as $fieldName => $value)
				{
					$userField = $this->userFieldCollection->getByName($fieldName);
					if($userField)
					{
						$view->setAdditionalParameter(
							'URL_TEMPLATE',
							Driver::getInstance()->getUrlManager()->getFileUrlTemplate($this->type->getId(), $id, $userField->getName())
						);
						$view->setAdditionalParameter('printable', true);

						$field = $userField->toArray();
						$field['VALUE'] = $value;

						$view->setField($field);
						$this->processedValues[$id][$fieldName] = $view->display();
						$view->clear();
					}
				}
			}
		}

		return $this->processedValues;
	}

	public function getAllValues(): array
	{
		$this->processValues();

		return $this->processedValues;
	}

	public function getValues(int $itemId): ?array
	{
		$this->processValues();

		return $this->processedValues[$itemId] ?? null;
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