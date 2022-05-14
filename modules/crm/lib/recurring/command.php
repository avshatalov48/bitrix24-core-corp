<?php
namespace Bitrix\Crm\Recurring;

use Bitrix\Main\Error,
	Bitrix\Main\DB,
	Bitrix\Main\Result;

class Command
{
	/**
	 * @param string $type
	 * @param string $operation
	 * @param array $data
	 *
	 */
	public static function execute($type = "", $operation = "", array $data = array())
	{
		$result = new Result();

		/** @var Entity\Base $entity */
		$entity = static::loadEntity($type);
		if (!$entity)
		{
			$result->addError(new Error("Entity type is not allowed for recurring"));
			return $result;
		}

		if (!method_exists($entity, $operation))
		{
			$result->addError(new Error("Method is not allowed for recurring entity"));
		}

		return call_user_func_array(array($entity, $operation), $data);
	}

	/**
	 * @param $type
	 *
	 * @return bool
	 */
	private static function isEntityExist($type)
	{
		return in_array($type, [Manager::INVOICE, Manager::DEAL]);
	}

	/**
	 * @param $type
	 *
	 * @return Entity\Base | null
	 */
	public static function loadEntity($type)
	{
		$className = __NAMESPACE__."\\Entity\\".$type;
		if (!class_exists($className) || !self::isEntityExist($type))
		{
			return null;
		}

		return call_user_func("{$className}::getInstance");
	}
}