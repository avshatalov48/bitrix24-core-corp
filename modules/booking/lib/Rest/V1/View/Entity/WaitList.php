<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\View\Entity;

use Bitrix\Booking\Rest\V1\View\Field\CreatedWithin;
use Bitrix\Booking\Rest\V1\View\View;
use Bitrix\Rest\Integration\View\DataType;

class WaitList extends View
{
	public function getFields(): array
	{
		return [
			'ID' => [
				'TYPE' => DataType::TYPE_INT,
			],
			'NOTE' => [
				'TYPE' => DataType::TYPE_STRING,
			],
		];
	}

	public function getFilterFields(): array
	{
		return [
			'CREATED_WITHIN' => (new CreatedWithin())->toArray(),
		];
	}

	public function getAdditionalRestMethods(): array
	{
		return [
			'createfrombooking',
		];
	}
}
