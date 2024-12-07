<?php

namespace Bitrix\Sign\Item\Connector;

use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Item;

class Field
{
	public function __construct(
		public string $name,
		public null|int|array|string|float|DateTime|Item\Fs\File $data,
		public ?string $label = null,
	)
	{
	}

	public static function isValueTypeSupported($data): bool
	{
		return
			is_string($data)
			|| is_int($data)
			|| is_float($data)
			|| is_array($data)
			|| $data instanceof DateTime
			|| $data instanceof Item\Fs\File
			|| $data === null
			|| is_bool($data)
		;
	}
}