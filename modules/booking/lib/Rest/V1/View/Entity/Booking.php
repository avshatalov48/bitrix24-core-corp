<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\View\Entity;

use Bitrix\Booking\Rest\V1\ErrorCode;
use Bitrix\Booking\Rest\V1\View\Field\CreatedWithin;
use Bitrix\Booking\Rest\V1\View\Field\DatePeriod;
use Bitrix\Booking\Rest\V1\View\FieldsFilter;
use Bitrix\Booking\Rest\V1\View\View;
use Bitrix\Main\Result;
use Bitrix\Rest\Integration\View\Attributes;
use Bitrix\Rest\Integration\View\DataType;

class Booking extends View
{
	private const CREATE_FROM_WAIT_LIST = 'createfromwaitlist';

	public function getFields(): array
	{
		return [
			'ID' => [
				'TYPE' => DataType::TYPE_INT,
				'ATTRIBUTES' => [
					Attributes::READONLY,
				],
			],
			'RESOURCE_IDS' => [
				'TYPE' => DataType::TYPE_LIST,
				'ATTRIBUTES' => [
					Attributes::REQUIRED_ADD,
				]
			],
			'DATE_PERIOD' => (new DatePeriod([Attributes::REQUIRED_ADD]))->toArray(),
			'NAME' => [
				'TYPE' => DataType::TYPE_STRING,
			],
			'DESCRIPTION' => [
				'TYPE' => DataType::TYPE_STRING,
			],
		];
	}

	public function getFilterFields(): array
	{
		return [
			'RESOURCE_ID' => [
				'TYPE' => DataType::TYPE_LIST,
			],
			'WITHIN' => [
				'TYPE' => \Bitrix\Booking\Rest\V1\View\DataType::OBJECT,
				'FIELDS' => [
					'DATE_FROM' => [
						'TYPE' => DataType::TYPE_INT,
						'ATTRIBUTES' => [
							Attributes::REQUIRED,
						],
					],
					'DATE_TO' => [
						'TYPE' => DataType::TYPE_INT,
						'ATTRIBUTES' => [
							Attributes::REQUIRED,
						],
					],
				]
			],
			'CREATED_WITHIN' => (new CreatedWithin())->toArray(),
			'CLIENT' => [
				'TYPE' => \Bitrix\Booking\Rest\V1\View\DataType::OBJECT,
				'FIELDS' => [
					'ENTITIES' => [
						'TYPE' => \Bitrix\Booking\Rest\V1\View\DataType::OBJECTS_LIST,
						'ATTRIBUTES' => [
							Attributes::REQUIRED,
						],
						'FIELDS' => [
							'MODULE' => [
								'TYPE' => DataType::TYPE_STRING,
								'ATTRIBUTES' => [
									Attributes::REQUIRED,
								],
							],
							'CODE' => [
								'TYPE' => DataType::TYPE_STRING,
								'ATTRIBUTES' => [
									Attributes::REQUIRED,
								],
							],
							'ID' => [
								'TYPE' => DataType::TYPE_STRING,
								'ATTRIBUTES' => [
									Attributes::REQUIRED,
								],
							],
						]
					]
				]
			],
		];
	}

	protected function internalizeAdditionalRestMethod(string $name, array $arguments): array
	{
		return match ($name)
		{
			self::CREATE_FROM_WAIT_LIST => $this->internalizeCreateFromWaitListFields($arguments),
		};
	}

	private function internalizeCreateFromWaitListFields(array $arguments): array
	{
		$fieldsFilter =
			(new FieldsFilter())
				->setIgnoredAttributes(
					[
						Attributes::HIDDEN,
						Attributes::READONLY,
						Attributes::REQUIRED_UPDATE,
						\Bitrix\Booking\Rest\V1\View\Attributes::REQUIRED_SET,
					]
				)
		;

		return [
			'WAIT_LIST_ID' => $arguments['WAIT_LIST_ID'],
			'FIELDS' => $this->internalizeFieldsRecursive(
				fields: $arguments['FIELDS'],
				fieldsInfo: $this->getCreateFromWaitListFields(),
				fieldsFilter: $fieldsFilter->toArray(),
			)
		];
	}

	protected function checkAdditionalRestMethodArguments(string $name, array $arguments): Result
	{
		return match ($name)
		{
			self::CREATE_FROM_WAIT_LIST => $this->checkRequiredFieldsCreateFromWaitList($arguments),
			default => new Result(),
		};
	}

	protected function checkRequiredFieldsCreateFromWaitList(array $arguments): Result
	{
		$fieldsFilter =
			(new FieldsFilter())
				->setIgnoredAttributes(
					[
						Attributes::HIDDEN,
						Attributes::READONLY,
						Attributes::REQUIRED_UPDATE,
						\Bitrix\Booking\Rest\V1\View\Attributes::REQUIRED_SET,
					]
				)
		;

		$checkResult = $this->checkRequiredFieldsRecursive(
			fields: $arguments['FIELDS'],
			fieldsInfo: $this->getCreateFromWaitListFields(),
			fieldsFilter: $fieldsFilter->toArray(),
		);

		if (!$checkResult->isSuccess())
		{
			$error = ErrorCode::getRequiredFieldsError($checkResult->getErrorMessages());

			return (new Result())->addError($error);
		}

		return new Result();
	}

	private function getCreateFromWaitListFields(): array
	{
		return [
			'RESOURCE_IDS' => [
				'TYPE' => DataType::TYPE_LIST,
				'ATTRIBUTES' => [
					Attributes::REQUIRED_ADD,
				],
			],
			'DATE_PERIOD' => (new DatePeriod([Attributes::REQUIRED_ADD]))->toArray(),
		];
	}

	public function getOrderFields(): array
	{
		return [
			'ID',
			'DATE_FROM',
			'DATE_TO',
		];
	}

	public function getAdditionalRestMethods(): array
	{
		return [
			self::CREATE_FROM_WAIT_LIST,
		];
	}
}
