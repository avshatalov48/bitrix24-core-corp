<?php

namespace Bitrix\Mobile\Field\Type;

class DateTimeField extends DateField
{
	public const TYPE = 'datetime';

	/**
	 * @inheritDoc
	 */
	public function getData(): array
	{
		$data = parent::getData();

		$data['enableTime'] = true;

		return $data;
	}
}
