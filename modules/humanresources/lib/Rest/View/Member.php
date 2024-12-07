<?php

namespace Bitrix\HumanResources\Rest\View;

use Bitrix\Rest\Integration\View\Attributes;
use Bitrix\Rest\Integration\View\DataType;
use Bitrix\Rest\Integration\View\Base;

final class Member extends Base
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
			'ENTITY_TYPE' => [
				'TYPE' => DataType::TYPE_STRING,
				'ATTRIBUTES' => [
					Attributes::REQUIRED_ADD,
				],
			],
			'ENTITY_ID' => [
				'TYPE' => DataType::TYPE_INT,
				'ATTRIBUTES' => [
					Attributes::REQUIRED_ADD,
				],
			],
			'NODE_ID' => [
				'TYPE' => DataType::TYPE_INT,
				'ATTRIBUTES' => [
					Attributes::REQUIRED_ADD,
				],
			],
			'ADDED_BY' => [
				'TYPE' => DataType::TYPE_INT,
				'ATTRIBUTES' => [
					Attributes::READONLY,
				],
			],
			'CREATED_AT' => [
				'TYPE' => DataType::TYPE_DATETIME,
				'ATTRIBUTES' => [
					Attributes::READONLY,
				],
			],
			'UPDATED_AT' => [
				'TYPE' => DataType::TYPE_DATETIME,
				'ATTRIBUTES' => [
					Attributes::READONLY,
				],
			],
			'ROLE' => [
				'TYPE' => DataType::TYPE_LIST,
			]
		];
	}
}