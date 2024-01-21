<?php

namespace Bitrix\Mobile\Field\Type;

class EnumerationField extends BaseField
{
	public const TYPE = 'enumeration';

	/**
	 * @inheritDoc
	 */
	public function getFormattedValue()
	{
		return $this->value;
	}

	/**
	 * @inheritDoc
	 */
	public function getData(): array
	{
		$data = parent::getData();

		$items = [];
		foreach ($data['fieldInfo']['ENUM'] as $enum)
		{
			$items[] = [
				'value' => $enum['ID'],
				'name' => $enum['VALUE'],
			];
		}
		$data['items'] = $items;

		return $data;
	}
}
