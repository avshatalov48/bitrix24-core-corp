<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\View\Entity;

use Bitrix\Rest\Integration\View\Attributes;
use Bitrix\Rest\Integration\View\DataType;
use Bitrix\Booking\Rest\V1\View\View;

class ResourceType extends View
{
	public function getFields(): array
	{
		return [
			'ID' => [
				'TYPE' => DataType::TYPE_INT,
				'ATTRIBUTES' => [
					Attributes::READONLY,
				],
			],
			'NAME' => [
				'TYPE' => DataType::TYPE_STRING,
				'ATTRIBUTES' => [
					Attributes::REQUIRED_ADD,
				],
			],
			'CODE' => [
				'TYPE' => DataType::TYPE_STRING,
				'ATTRIBUTES' => [
					Attributes::REQUIRED_ADD,
				],
			],
			'INFO_NOTIFICATION_DELAY' => [
				'TYPE' => DataType::TYPE_INT,
			],
			'IS_CONFIRMATION_NOTIFICATION_ON' => [
				'TYPE' => DataType::TYPE_BOOLEAN,
			],
			'TEMPLATE_TYPE_CONFIRMATION' => [
				'TYPE' => DataType::TYPE_STRING,
			],
			'CONFIRMATION_NOTIFICATION_DELAY' => [
				'TYPE' => DataType::TYPE_INT,
			],
			'CONFIRMATION_NOTIFICATION_REPETITIONS' => [
				'TYPE' => DataType::TYPE_INT,
			],
			'CONFIRMATION_NOTIFICATION_REPETITIONS_INTERVAL' => [
				'TYPE' => DataType::TYPE_INT,
			],
			'CONFIRMATION_COUNTER_DELAY' => [
				'TYPE' => DataType::TYPE_INT,
			],
			'IS_REMINDER_NOTIFICATION_ON' => [
				'TYPE' => DataType::TYPE_BOOLEAN,
			],
			'TEMPLATE_TYPE_REMINDER' => [
				'TYPE' => DataType::TYPE_STRING,
			],
			'REMINDER_NOTIFICATION_DELAY' => [
				'TYPE' => DataType::TYPE_INT,
			],
			'IS_FEEDBACK_NOTIFICATION_ON' => [
				'TYPE' => DataType::TYPE_BOOLEAN,
			],
			'TEMPLATE_TYPE_FEEDBACK' => [
				'TYPE' => DataType::TYPE_STRING,
			],
			'IS_DELAYED_NOTIFICATION_ON' => [
				'TYPE' => DataType::TYPE_BOOLEAN,
			],
			'TEMPLATE_TYPE_DELAYED' => [
				'TYPE' => DataType::TYPE_STRING,
			],
			'DELAYED_NOTIFICATION_DELAY' => [
				'TYPE' => DataType::TYPE_INT,
			],
			'DELAYED_COUNTER_DELAY' => [
				'TYPE' => DataType::TYPE_INT,
			],
		];
	}

	public function getFilterFields(): array
	{
		return [
			'MODULE_ID' => [
				'TYPE' => DataType::TYPE_STRING,
			],
			'CODE' => [
				'TYPE' => DataType::TYPE_STRING,
			],
			'NAME' => [
				'TYPE' => DataType::TYPE_STRING,
			],
			'SEARCH_QUERY' => [
				'TYPE' => DataType::TYPE_STRING,
			],
		];
	}


	public function getOrderFields(): array
	{
		return [
			'ID',
			'NAME',
			'CODE',
		];
	}
}
