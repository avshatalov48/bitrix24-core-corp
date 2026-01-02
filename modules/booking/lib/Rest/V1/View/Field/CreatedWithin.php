<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\View\Field;

use Bitrix\Booking\Rest\V1\View\Field;
use Bitrix\Rest\Integration\View\Attributes;
use Bitrix\Rest\Integration\View\DataType;

class CreatedWithin extends Field
{
	public function toArray(): array
	{
		return [
			'ATTRIBUTES' => $this->getAttributes(),
			'TYPE' => \Bitrix\Booking\Rest\V1\View\DataType::OBJECT,
			'FIELDS' => [
				'FROM' => [
					'TYPE' => DataType::TYPE_DATE,
					'ATTRIBUTES' => [
						Attributes::REQUIRED,
					]
				],
				'TO' => [
					'TYPE' => DataType::TYPE_DATE,
					'ATTRIBUTES' => [
						Attributes::REQUIRED,
					],
				],
			]
		];
	}
}
