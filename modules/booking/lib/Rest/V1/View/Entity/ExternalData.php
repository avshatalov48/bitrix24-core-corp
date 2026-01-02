<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\View\Entity;

use Bitrix\Booking\Rest\V1\View\Attributes;
use Bitrix\Rest\Integration\View\DataType;
use Bitrix\Booking\Rest\V1\View\View;

class ExternalData extends View
{
	public function getFields(): array
	{
		return [
			'MODULE_ID' => [
				'TYPE' => DataType::TYPE_STRING,
				'ATTRIBUTES' => [
					Attributes::REQUIRED_SET,
				],
			],
			'ENTITY_TYPE_ID' => [
				'TYPE' => DataType::TYPE_STRING,
				'ATTRIBUTES' => [
					Attributes::REQUIRED_SET,
				],
			],
			'VALUE' => [
				'TYPE' => DataType::TYPE_STRING,
				'ATTRIBUTES' => [
					Attributes::REQUIRED_SET,
				],
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
