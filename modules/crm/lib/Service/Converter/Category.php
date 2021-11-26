<?php

namespace Bitrix\Crm\Service\Converter;

use Bitrix\Crm\Category\Entity;
use Bitrix\Crm\Service\Converter;
use Bitrix\Main\ArgumentException;

class Category extends Converter
{
	/**
	 * @param Entity\Category $model
	 *
	 * @return array
	 * @throws ArgumentException
	 */
	public function toJson($model): array
	{
		if (!($model instanceof Entity\Category))
		{
			throw new ArgumentException('Model should be an instance of ' . Entity\Category::class);
		}

		$data = $this->prepareData($model->getData());

		return $this->convertKeysToCamelCase($data);
	}
}
