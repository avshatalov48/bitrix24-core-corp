<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\View\Field;

use Bitrix\Booking\Rest\V1\View\Field;
use Bitrix\Rest\Integration\View\Attributes;
use Bitrix\Rest\Integration\View\DataType;

class DatePeriod extends Field
{
	public function toArray(): array
	{
		return [
			'ATTRIBUTES' => $this->getAttributes(),
			'TYPE' => \Bitrix\Booking\Rest\V1\View\DataType::OBJECT,
			'FIELDS' => [
				'FROM' => $this->getDateFields(),
				'TO' => $this->getDateFields(),
			],
		];
	}

	private function getDateFields(): array
	{
		return [
			'TYPE' => \Bitrix\Booking\Rest\V1\View\DataType::OBJECT,
			'ATTRIBUTES' => [
				Attributes::REQUIRED,
			],
			'FIELDS' => [
				'TIMESTAMP' => [
					'TYPE' => DataType::TYPE_INT,
					'ATTRIBUTES' => [
						Attributes::REQUIRED,
					],
				],
				'TIMEZONE' => [
					'TYPE' => DataType::TYPE_STRING,
					'ATTRIBUTES' => [
						Attributes::REQUIRED,
					],
				]
			]
		];
	}
}
