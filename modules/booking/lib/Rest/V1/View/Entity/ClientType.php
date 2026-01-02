<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\View\Entity;

use Bitrix\Rest\Integration\View\DataType;
use Bitrix\Booking\Rest\V1\View\View;

class ClientType extends View
{
	public function getFields(): array
	{
		return [
			'CODE' => [
				'TYPE' => DataType::TYPE_STRING,
			],
			'MODULE' => [
				'TYPE' => DataType::TYPE_STRING,
			],
		];
	}
}
