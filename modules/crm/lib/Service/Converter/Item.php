<?php

namespace Bitrix\Crm\Service\Converter;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Converter;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

class Item extends Converter
{
	public function toJson($model): array
	{
		if(!($model instanceof \Bitrix\Crm\Item))
		{
			throw new ArgumentException('model should be an instance of Item');
		}

		$factory = Container::getInstance()->getFactory($model->getEntityTypeId());
		if (!$factory)
		{
			throw new InvalidOperationException('factory for ' . $model->getEntityTypeId() . 'is not found');
		}

		$data = $this->prepareData($model->getData());

		if (Container::getInstance()->getContext()->getScope() === Context::SCOPE_REST)
		{
			foreach ($factory->getFieldsCollection() as $field)
			{
				$fieldName = $field->getName();
				$value = $data[$fieldName];
				if ($field->isFileUserField() && !$field->isValueEmpty($value))
				{
					$data[$fieldName] = $this->processFile($model, $fieldName);
				}
			}
		}

		return $this->convertKeysToCamelCase($data);
	}

	protected function prepareData(array $data): array
	{
		$result = [];

		foreach ($data as $name => $value)
		{
			if (is_array($value))
			{
				$result[$name] = $this->prepareData($value);
			}
			elseif ($value instanceof Date)
			{
				$result[$name] = $this->processDate($value);
			}
			elseif (is_bool($value))
			{
				$result[$name] = $value ? 'Y' : 'N';
			}
			else
			{
				$result[$name] = $value;
			}
		}

		return $result;
	}

	protected function processDate(Date $date): string
	{
		if (
			Container::getInstance()->getContext()->getScope() === Context::SCOPE_REST
			&& Loader::includeModule('rest')
		)
		{
			if ($date instanceof DateTime)
			{
				return \CRestUtil::ConvertDateTime($date);
			}
			if ($date instanceof Date)
			{
				return \CRestUtil::ConvertDate($date);
			}
		}

		return $date->toString();
	}

	protected function processFile(\Bitrix\Crm\Item $item, string $fieldName): array
	{
		$result = [];
		$router = Container::getInstance()->getRouter();

		$value = $item->get($fieldName);
		if (is_array($value))
		{
			foreach ($value as $key => $fileId)
			{
				$result[$key] = [
					'id' => $fileId,
					'url' => $router->getFileUrl(
						$item->getEntityTypeId(),
						$item->getId(),
						$fieldName,
						$fileId
					),
				];
			}
		}
		else
		{
			$result = [
				'id' => $value,
				'url' => $router->getFileUrl(
					$item->getEntityTypeId(),
					$item->getId(),
					$fieldName,
					$value
				),
			];
		}

		return $result;
	}
}