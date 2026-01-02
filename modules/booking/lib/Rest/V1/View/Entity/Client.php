<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\View\Entity;

use Bitrix\Booking\Rest\V1\View\Attributes;
use Bitrix\Rest\Integration\View\DataType;
use Bitrix\Booking\Rest\V1\View\View;

class Client extends View
{
	public function getFields(): array
	{
		return [
			'ID' => [
				'TYPE' => DataType::TYPE_INT,
				'ATTRIBUTES' => [
					Attributes::REQUIRED_SET,
				],
			],
			'TYPE' => [
				'TYPE' => \Bitrix\Booking\Rest\V1\View\DataType::OBJECT,
				'ATTRIBUTES' => [
					Attributes::REQUIRED_SET,
				],
				'FIELDS' => [
					'MODULE' => [
						'TYPE' => DataType::TYPE_STRING,
						'ATTRIBUTES' => [
							Attributes::REQUIRED_SET,
						],
					],
					'CODE' => [
						'TYPE' => DataType::TYPE_STRING,
						'ATTRIBUTES' => [
							Attributes::REQUIRED_SET,
						],
					],
				]
			],
		];
	}

	public function getAdditionalRestMethods(): array
	{
		return [
			self::METHOD_UNSET,
			self::METHOD_SET,
		];
	}
}
