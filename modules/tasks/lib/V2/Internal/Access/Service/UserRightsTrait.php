<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Access\Service;

use BackedEnum;
use Bitrix\Tasks\V2\Internal\Access\Factory\ControllerFactoryInterface;
use Bitrix\Tasks\V2\Internal\Access\Factory\Type;

trait UserRightsTrait
{
	private function getUserRightsByType(
		int $userId,
		array $rules,
		Type $type,
		ControllerFactoryInterface $controllerFactory,
	): array
	{
		$controller = $controllerFactory->create($type, $userId);
		if ($controller === null)
		{
			return [];
		}

		$ruleChunks = [];
		foreach ($rules as $name => $rule)
		{
			$key = $rule instanceof BackedEnum ? $rule->value : $rule;

			$ruleChunks[$key][] = $name;
		}

		$rules = array_fill_keys(array_keys($ruleChunks), []);

		$item = $controllerFactory->createModel($type);

		$access = $controller->batchCheck($rules, $item);

		$result = [];
		foreach ($access as $rule => $value)
		{
			$actions = $ruleChunks[$rule];
			foreach ($actions as $name)
			{
				$result[$name] = $value;
			}
		}

		return $result;
	}
}
