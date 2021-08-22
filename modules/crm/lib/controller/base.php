<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Crm\Field;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

abstract class Base extends Controller
{
	public const ERROR_CODE_ACCESS_DENIED = 'ACCESS_DENIED';
	public const ERROR_CODE_NOT_FOUND = 'NOT_FOUND';

	protected function init(): void
	{
		parent::init();

		Container::getInstance()->getLocalization()->loadMessages();
	}

	public function getAutoWiredParameters(): array
	{
		return [
			new ExactParameter(
				\Bitrix\Crm\Model\Dynamic\Type::class,
				'type',
				static function($className, $typeId)
				{
					return Container::getInstance()->getType($typeId);
				}
			),
		];
	}

	protected function convertKeysToUpper(array $data): array
	{
		$converter = new Converter(
			Converter::TO_UPPER
			| Converter::KEYS
			| Converter::TO_SNAKE_DIGIT
			| Converter::RECURSIVE
		);

		return $converter->process($data);
	}

	protected function convertValuesToUpper(array $data): array
	{
		$converter = new Converter(Converter::TO_UPPER | Converter::VALUES | Converter::TO_SNAKE);

		return $converter->process($data);
	}

	public function convertKeysToCamelCase($data): array
	{
		$converter = Container::getInstance()->getOrmObjectConverter();

		return $converter->convertKeysToCamelCase($data);
	}

	protected function removeDotsFromKeys(array $data): array
	{
		$result = [];

		foreach($data as $name => $value)
		{
			if(is_array($value))
			{
				$value = $this->removeDotsFromKeys($value);
			}
			$result[str_replace('.', '', $name)] = $value;
		}

		return $result;
	}

	protected function removeDotsFromValues(array $data): array
	{
		$result = [];

		foreach($data as $name => $value)
		{
			if(is_array($value))
			{
				$value = $this->removeDotsFromValues($value);
			}
			$result[$name] = str_replace('.', '', $value);
		}

		return $result;
	}

	public function prepareDateTimeFieldsForFilter(
		array &$filter,
		Field\Collection $fields
	): void
	{
		foreach($filter as $name => &$value)
		{
			if(is_array($value))
			{
				$this->prepareDateTimeFieldsForFilter($value, $fields);
				continue;
			}
			foreach ($fields as $field)
			{
				if($this->isCorrectFieldName($name, $field->getName()))
				{
					$type = $field->getType();
					if ($type === Field::TYPE_DATE)
					{
						$value = \CRestUtil::unConvertDate($value);
						break;
					}
					if ($type === Field::TYPE_DATETIME)
					{
						$value = \CRestUtil::unConvertDateTime($value);
						break;
					}
				}
			}
		}
	}

	protected function isCorrectFieldName($filterName, $field): bool
	{
		static $prefixes = [
			'' => true, '=' => true, '%' => true, '>' => true, '<' => true, '@' => true, '!=' => true,
			'!%' => true, '><' => true, '>=' => true, '<=' => true, '=%' => true, '%=' => true,
			'!><' => true, '!=%' => true, '!%=' => true,
		];

		return isset($prefixes[str_replace($field, '', $filterName)]);
	}

	protected function uploadFile(Field $field, $fileContent): ?int
	{
		if (empty($fileContent))
		{
			return null;
		}

		$fileArray = \CRestUtil::saveFile($fileContent);
		if (!$fileArray)
		{
			$this->addError(new Error(Loc::getMessage('CRM_CONTROLLER_BASE_UPLOAD_FILE_ERROR')));
			return null;
		}

		return Container::getInstance()->getFileUploader()->saveFileTemporary($field, $fileArray);
	}
}