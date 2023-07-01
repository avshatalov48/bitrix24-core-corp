<?php

namespace Bitrix\Tasks\Internals\Task\Placeholder;

use Bitrix\Tasks\Internals\Task\Placeholder\Exception\PlaceholderNotFoundException;
use Bitrix\Tasks\Internals\Task\Placeholder\Placeholder\Placeholder;

class PlaceholderFactory
{
	private const PLACEHOLDER_SUFFIX = 'Placeholder';

	/**
	 * @throws PlaceholderNotFoundException
	 */
	public static function create(string $placeholderName, $placeholderValue): Placeholder
	{
		$class = self::getPlaceholderClass($placeholderName);
		return new $class($placeholderValue);
	}

	/**
	 * @throws PlaceholderNotFoundException
	 */
	private static function getPlaceholderClass(string $placeholderName): string
	{
		$className = str_replace(['_', '%'], '', $placeholderName) . self::PLACEHOLDER_SUFFIX;
		$fullClassName = __NAMESPACE__ . '\\' . self::PLACEHOLDER_SUFFIX . '\\' . $className;

		if (class_exists($fullClassName))
		{
			return $fullClassName;
		}

		throw new PlaceholderNotFoundException($placeholderName);
	}
}