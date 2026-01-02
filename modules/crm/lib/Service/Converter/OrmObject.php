<?php

namespace Bitrix\Crm\Service\Converter;

use Bitrix\Crm\Service\Converter;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Objectify\EntityObject;

class OrmObject extends Converter
{
	/**
	 * @param EntityObject $model
	 * @return array
	 * @throws ArgumentException
	 */
	public function toJson($model): array
	{
		if(!($model instanceof EntityObject))
		{
			throw new ArgumentException('model should be an instance of EntityObject');
		}

		return $this->convertKeysToCamelCase($this->toArray($model));
	}

	/**
	 * Same as `toJson`, but without camel-casing field names
	 * @see self::toJson()
	 */
	final public function toArray(EntityObject $model): array
	{
		return $this->prepareData($model->collectValues());
	}
}
