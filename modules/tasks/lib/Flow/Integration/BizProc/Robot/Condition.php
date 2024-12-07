<?php

namespace Bitrix\Tasks\Flow\Integration\BizProc\Robot;

use Bitrix\Main\Type\Contract\Arrayable;

class Condition implements Arrayable
{
	public function __construct(
		public string $field,
		public mixed $value,
		public string $operator = '=',
	)
	{

	}
	public function toArray(): array
	{
		return [
			'type' => 'field',
			'items' => [
				[
					0 => [
						'object' => 'Document',
						'field' => $this->field,
						'operator' => $this->operator,
						'value' => $this->value,
					],
					1 => 'AND',
				],

			],
		];
	}
}