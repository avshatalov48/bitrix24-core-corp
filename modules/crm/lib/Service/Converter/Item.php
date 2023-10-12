<?php

namespace Bitrix\Crm\Service\Converter;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Converter;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\InvalidOperationException;

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

		$data = $model->getData();

		$data = $factory->getFieldsCollection()->removeHiddenValues($data);

		if (Container::getInstance()->getContext()->getScope() === Context::SCOPE_REST)
		{
			foreach ($factory->getFieldsCollection() as $field)
			{
				$fieldName = $field->getName();
				$value = $data[$fieldName] ?? null;
				if ($field->isFileUserField() && !$field->isValueEmpty($value))
				{
					$data[$fieldName] = $this->processFile($model, $fieldName);
				}
			}
		}

		$data['ENTITY_TYPE_ID'] = $model->getEntityTypeId();

		$data = $this->prepareData($data);

		return $this->convertKeysToCamelCase($data);
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
					'ID' => $fileId,
					'URL' => $router->getFileUrl(
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
				'ID' => $value,
				'URL' => $router->getFileUrl(
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
