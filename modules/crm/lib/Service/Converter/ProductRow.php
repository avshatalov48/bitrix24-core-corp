<?php

namespace Bitrix\Crm\Service\Converter;

use Bitrix\Main\ArgumentException;

class ProductRow extends OrmObject
{
	/**
	 * @param \Bitrix\Crm\ProductRow $model
	 *
	 * @return array
	 * @throws ArgumentException
	 */
	public function toJson($model): array
	{
		if (!($model instanceof \Bitrix\Crm\ProductRow))
		{
			throw new ArgumentException('Model should be an instance of ' . \Bitrix\Crm\ProductRow::class);
		}

		$data = $this->prepareData($model->toArray());

		return $this->convertKeysToCamelCase($data);
	}
}
