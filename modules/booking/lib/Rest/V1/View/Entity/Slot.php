<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\View\Entity;

use Bitrix\Booking\Rest\V1\View\Attributes;
use Bitrix\Booking\Rest\V1\View\View;
use Bitrix\Rest\Integration\View\DataType;

class Slot extends View
{
	public function getFields(): array
	{
		return [
			'ID' => [
				'TYPE' => DataType::TYPE_INT,
				'ATTRIBUTES' => [
					\Bitrix\Rest\Integration\View\Attributes::READONLY,
				],
			],
			'FROM' => [
				'TYPE' => DataType::TYPE_INT,
				'ATTRIBUTES' => [
					Attributes::REQUIRED_SET,
				],
			],
			'TO' => [
				'TYPE' => DataType::TYPE_INT,
				'ATTRIBUTES' => [
					Attributes::REQUIRED_SET,
				],
			],
			'TIMEZONE' => [
				'TYPE' => DataType::TYPE_STRING,
				'ATTRIBUTES' => [
					Attributes::REQUIRED_SET,
				],
			],
			'WEEK_DAYS' => [
				'TYPE' => DataType::TYPE_LIST,
				'ATTRIBUTES' => [
					Attributes::REQUIRED_SET,
				],
			],
			'SLOT_SIZE' => [
				'TYPE' => DataType::TYPE_INT,
				'ATTRIBUTES' => [
					Attributes::REQUIRED_SET,
				],
			]
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
