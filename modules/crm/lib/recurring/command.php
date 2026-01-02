<?php
namespace Bitrix\Crm\Recurring;

use Bitrix\Main\Error;
use Bitrix\Main\Result;

class Command
{
	public static function execute(string $type = '', string $operation = '', array $data = [])
	{
		$result = new Result();

		$entity = static::loadEntity($type);
		if (!$entity)
		{
			$result->addError(new Error('Entity type is not allowed for recurring'));

			return $result;
		}

		if (!method_exists($entity, $operation))
		{
			$result->addError(new Error('Method is not allowed for recurring entity'));
		}

		return call_user_func_array([$entity, $operation], $data);
	}

	public static function loadEntity(string $type): ?Entity\Base
	{
		$className = __NAMESPACE__ . '\\Entity\\' . $type;
		if (class_exists($className) && self::isEntityExist($type))
		{
			return call_user_func("{$className}::getInstance");
		}

		return null;
	}

	private static function isEntityExist(string $type): bool
	{
		return in_array($type, Manager::getEntityTypeList(), true);
	}
}