<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\View;

use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Booking\Rest\V1\ErrorCode;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\Result;
use Bitrix\Rest\Integration\View\Attributes;
use Bitrix\Rest\Integration\View\Base;
use Bitrix\Rest\Integration\View\DataType;

abstract class View extends Base
{
	protected const METHOD_UNSET = 'unset';
	protected const METHOD_SET = 'set';

	public function internalizeFieldsList($arguments, $fieldsInfo = []): array
	{
		$internalizedFields['select'] = $arguments['select'] ?? [];
		$internalizedFields['filter'] = $this->internalizeListFields($arguments, $this->getFilterFields())['filter'];
		$internalizedFields['order'] = $this->internalizeListFields($arguments, $this->getPreparedOrderFields())['order'];

		return $internalizedFields;
	}

	public function getFilterFields(): array
	{
		return [];
	}

	private function getPreparedOrderFields(): array
	{
		$preparedOrderFields = [];

		foreach ($this->getOrderFields() as $orderField)
		{
			$preparedOrderFields[$orderField] = [
				'TYPE' => DataType::TYPE_STRING,
			];
		}

		return $preparedOrderFields;
	}

	public function getOrderFields(): array
	{
		return [];
	}

	/**
	 * @throws NotImplementedException
	 */
	public function internalizeArguments($name, $arguments): array
	{
		if (!$this->isMethodExists($name))
		{
			return parent::internalizeArguments($name, $arguments);
		}

		return $this->internalizeAdditionalRestMethod(
			$name,
			$this->convertKeysToSnakeCase($arguments),
		);
	}

	protected function internalizeAdditionalRestMethod(string $name, array $arguments): array
	{
		return match ($name)
		{
			self::METHOD_SET => $this->internalizeFieldsSet($arguments),
			default => $arguments,
		};
	}

	protected function internalizeFieldsSet(array $arguments): array
	{
		$itemsKey = $this->getItemsKeyFromSetArguments($arguments);
		$items = $arguments[$itemsKey];

		$fieldsFilter =
			(new FieldsFilter())
				->setIgnoredAttributes(
					[
						Attributes::READONLY,
						Attributes::REQUIRED_UPDATE,
					]
				)
		;

		$internalizedItems = [];
		foreach ($items as $item)
		{
			$internalizedItems[] =
				$this
					->internalizeFieldsRecursive(
						fields: $item,
						fieldsInfo: $this->getFields(),
						fieldsFilter: $fieldsFilter->toArray(),
					)
			;
		}

		return [
			...$arguments,
			$itemsKey => $internalizedItems
		];
	}

	public function internalizeFieldsAdd($fields, $fieldsInfo = []): array
	{
		$fieldsInfo = empty($fieldsInfo) ? $this->getFields() : $fieldsInfo;

		$fieldsFilter =
			(new FieldsFilter())
				->setIgnoredAttributes(
					[
						Attributes::READONLY,
					]
				)
		;

		return $this->internalizeFieldsRecursive(
			fields: $fields,
			fieldsInfo: $fieldsInfo,
			fieldsFilter: $fieldsFilter->toArray(),
		);
	}

	public function internalizeFieldsUpdate($fields, $fieldsInfo = []): array
	{
		$fieldsInfo = empty($fieldsInfo) ? $this->getFields() : $fieldsInfo;
		$fieldsFilter =
			(new FieldsFilter())
				->setIgnoredAttributes(
					[
						Attributes::READONLY,
						Attributes::IMMUTABLE,
					]
				)
		;


		return $this->internalizeFieldsRecursive(
			fields: $fields,
			fieldsInfo: $fieldsInfo,
			fieldsFilter: $fieldsFilter->toArray(),
		);
	}

	protected function internalizeFieldsRecursive(
		array $fields,
		array $fieldsInfo,
		array $fieldsFilter = [],
	): array
	{
		$fieldsInfo = $this->getListFieldInfo(
			$fieldsInfo,
			$fieldsFilter,
		);

		$internalizedFields = $this->internalizeFields($fields, $fieldsInfo);

		foreach ($internalizedFields as $fieldName => $fieldValue)
		{
			if (!isset($fieldsInfo[$fieldName]))
			{
				continue;
			}

			$fieldInfo = $fieldsInfo[$fieldName];
			if (!$this->hasNestedFields($fieldInfo))
			{
				continue;
			}

			$fieldType = $fieldInfo['TYPE'];
			if (
				$fieldType === \Bitrix\Booking\Rest\V1\View\DataType::OBJECT
				&& is_array($fieldValue)
			)
			{
				$internalizedFields[$fieldName] =
					$this
						->internalizeFieldsRecursive(
							$fieldValue,
							$fieldInfo['FIELDS'],
							$fieldsFilter,
						)
				;
			}

			if ($fieldType === \Bitrix\Booking\Rest\V1\View\DataType::OBJECTS_LIST)
			{
				$internalizedObjects = [];
				$objects = $fieldValue;
				foreach ($objects as $index => $object)
				{
					if (!is_array($object))
					{
						continue;
					}

					$internalizedObjects[$index] =
						$this
							->internalizeFieldsRecursive(
								$object,
								$fieldInfo['FIELDS'],
								$fieldsFilter,
							)
					;
				}

				$internalizedFields[$fieldName] = $internalizedObjects;
			}
		}

		return $internalizedFields;
	}

	public function checkArguments($name, $arguments): Result
	{
		if (!$this->isMethodExists($name))
		{
			return parent::checkArguments($name, $arguments);
		}

		return $this->checkAdditionalRestMethodArguments(
			$name,
			$this->convertKeysToSnakeCase($arguments),
		);
	}

	protected function checkAdditionalRestMethodArguments(string $name, array $arguments): Result
	{
		return match ($name)
		{
			self::METHOD_SET => $this->checkRequiredFieldsSet($arguments),
			default => new Result(),
		};
	}

	protected function checkRequiredFieldsSet(array $arguments): Result
	{
		$fieldsFilter =
			(new FieldsFilter())
				->setIgnoredAttributes(
					[
						Attributes::READONLY,
						Attributes::REQUIRED_UPDATE,
						Attributes::REQUIRED_ADD,
					]
				)
		;

		$items = $this->getItemsFromSetArguments($arguments);
		foreach ($items as $item)
		{
			if (!is_array($item))
			{
				return (new Result())->addError(ErrorBuilder::build(
					"Array's element must be array, string given",
					Exception::CODE_INVALID_ARGUMENT,
				));
			}

			$checkResult = $this
				->checkRequiredFieldsRecursive(
					$item,
					$this->getFields(),
					$fieldsFilter->toArray(),
				)
			;
			if (!$checkResult->isSuccess())
			{
				$error = ErrorCode::getRequiredFieldsError($checkResult->getErrorMessages());

				return (new Result())->addError($error);
			}
		}

		return new Result();
	}

	private function getItemsFromSetArguments(array $arguments): array
	{
		$itemsKey = $this->getItemsKeyFromSetArguments($arguments);

		return $arguments[$itemsKey];
	}

	private function getItemsKeyFromSetArguments(array $arguments): string
	{
		foreach ($arguments as $key => $argument)
		{
			if (is_array($argument))
			{
				return $key;
			}
		}

		throw new ArgumentException('No list found in arguments');
	}

	protected function checkRequiredFieldsAdd($fields): Result
	{
		$fieldsFilter =
			(new FieldsFilter())
				->setIgnoredAttributes(
					[
						Attributes::READONLY,
						Attributes::REQUIRED_UPDATE,
						\Bitrix\Booking\Rest\V1\View\Attributes::REQUIRED_SET,
					]
				)
		;

		return $this->checkRequiredFieldsRecursive(
			$fields,
			$this->getFields(),
			$fieldsFilter->toArray(),
		);
	}

	protected function checkRequiredFieldsUpdate($fields): Result
	{
		$fieldsFilter =
			(new FieldsFilter())
				->setIgnoredAttributes(
					[
						Attributes::READONLY,
						Attributes::REQUIRED_ADD,
						\Bitrix\Booking\Rest\V1\View\Attributes::REQUIRED_SET,
						Attributes::IMMUTABLE,
					]
				)
		;

		return $this->checkRequiredFieldsRecursive(
			$fields,
			$this->getFields(),
			$fieldsFilter->toArray(),
		);
	}

	public function checkFieldsList($arguments): Result
	{
		if (!isset($arguments['filter']))
		{
			return new Result();
		}

		$fieldsFilter =
			(new FieldsFilter())
				->setIgnoredAttributes(
					[
						Attributes::READONLY,
					]
				)
		;

		$checkRequiredFieldsResult = $this->checkRequiredFieldsRecursive(
			$arguments['filter'],
			$this->getFilterFields(),
			$fieldsFilter->toArray(),
		);
		if (!$checkRequiredFieldsResult->isSuccess())
		{
			$error = ErrorCode::getRequiredFieldsError($checkRequiredFieldsResult->getErrorMessages());

			return (new Result())->addError($error);
		}

		return $checkRequiredFieldsResult;
	}

	protected function checkRequiredFieldsRecursive(
		array $fields,
		array $fieldsInfo,
		array $fieldsFilter = [],
	): Result
	{
		$fieldsInfo = $this->getListFieldInfo(
			$fieldsInfo,
			$fieldsFilter,
		);

		$checkResult = $this->checkRequiredFields($fields, $fieldsInfo);
		if (!$checkResult->isSuccess())
		{
			return $checkResult;
		}

		foreach ($fields as $fieldName => $fieldValue)
		{
			if (!isset($fieldsInfo[$fieldName]))
			{
				continue;
			}

			$fieldInfo = $fieldsInfo[$fieldName];
			if (!$this->hasNestedFields($fieldInfo))
			{
				continue;
			}

			$objects = $fieldValue;
			if ($fieldInfo['TYPE'] === \Bitrix\Booking\Rest\V1\View\DataType::OBJECT)
			{
				$objects = [$fieldValue];
			}

			foreach ($objects as $object)
			{
				if (!is_array($object))
				{
					$error = ErrorBuilder::build(
						message: ErrorCode::convertToSnakeCase($fieldName),
					);

					return (new Result())->addError($error);
				}
				$checkRecursiveResult =
					$this
						->checkRequiredFieldsRecursive(
							$object,
							$fieldInfo['FIELDS'],
							$fieldsFilter,
						)
				;
				if (!$checkRecursiveResult->isSuccess())
				{
					return $checkRecursiveResult;
				}
			}
		}

		return new Result();
	}

	private function hasNestedFields(array $fieldInfo): bool
	{
		return
				in_array($fieldInfo['TYPE'], [
					\Bitrix\Booking\Rest\V1\View\DataType::OBJECT,
					\Bitrix\Booking\Rest\V1\View\DataType::OBJECTS_LIST,
				], true)
				&& !empty($fieldInfo['FIELDS'])
			;
	}

	public function externalizeResult($name, $fields): array
	{
		if (!$this->isMethodExists($name))
		{
			return parent::externalizeResult($name, $fields);
		}

		return $fields;
	}

	private function isMethodExists(string $method): bool
	{
		return in_array($method, $this->getAdditionalRestMethods(), true);
	}

	protected function getAdditionalRestMethods(): array
	{
		return [];
	}

	protected function prepareFieldAttributs($info, $attributs): array
	{
		$intersectRequired = array_intersect(
			[
				Attributes::REQUIRED,
				Attributes::REQUIRED_ADD,
				Attributes::REQUIRED_UPDATE,
				\Bitrix\Booking\Rest\V1\View\Attributes::REQUIRED_SET,
			],
			$attributs,
		);

		return [
			'TYPE' => $info['TYPE'],
			'IS_REQUIRED' => !empty($intersectRequired),
			'IS_READ_ONLY' => in_array(Attributes::READONLY, $attributs, true),
			'IS_IMMUTABLE' => in_array(Attributes::IMMUTABLE, $attributs, true),
		];
	}
}
