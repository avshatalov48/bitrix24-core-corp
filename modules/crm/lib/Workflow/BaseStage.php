<?php

namespace Bitrix\Crm\Workflow;

use Bitrix\Main\Localization\Loc;

/**
 * Base class for simple constant-based stage enums
 */
abstract class BaseStage
{
	/**
	 * Returns available enum codes
	 * @return string[]
	 */
	public static function getValues(): array
	{
		return array_values(static::getStages());
	}

	/**
	 * Returns enum with localyzed descriptions
	 *
	 * @psalm-suppress RedundantCastGivenDocblockType
	 * @return array<string, string> enum code => public description
	 */
	public static function getMessages(): array
	{
		$result = [];
		$className = static::className();

		foreach (static::getStages() as $name => $value)
		{
			$result[$value] = (string)Loc::getMessage(sprintf('CRM_WORKFLOW_STAGE_%s_%s', $className, $name));
		}

		return $result;
	}

	/**
	 * Returns localyzed message for specific enum value
	 * @param string $enumValue
	 * @return string
	 */
	public static function getMessage(string $enumValue): string
	{
		$messages = static::getMessages();
		return $messages[$enumValue] ?? '';
	}

	/**
	 * Makes strict check that $enumValue exists in current enum
	 * @param string $enumValue
	 * @return bool
	 */
	public static function isValid(string $enumValue): bool
	{
		return in_array($enumValue, static::getValues(), true);
	}

	/**
	 * Returns map of class constants
	 * @return array<string, string>
	 */
	protected static function getStages(): array
	{
		$result = [];
		$reflection = new \ReflectionClass(static::class);

		/** @var mixed $value */
		foreach ($reflection->getConstants() as $name => $value)
		{
			$result[$name] = (string)$value;
		}

		return $result;
	}

	/**
	 * Returns uppercased short name of called class
	 */
	protected static function className(): string
	{
		$reflection = new \ReflectionClass(static::class);
		return mb_strtoupper($reflection->getShortName());
	}
}
