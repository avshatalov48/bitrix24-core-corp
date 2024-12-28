<?php

namespace Bitrix\Sign\Type;

use Bitrix\Sign\Access\Permission\SignPermissionDictionary;

class BlankScenario
{
	public const B2B = 'b2b';
	public const B2E = 'b2e';

	public static function getAll(): array
	{
		return [
			self::B2B,
			self::B2E
		];
	}

	public static function getMap(): array
	{
		return [
			self::B2B => 0,
			self::B2E => 1
		];
	}

	public static function getScenarioById(int $id): string
	{
		$scenarioByIdMap = array_flip(self::getMap());
		if (!array_key_exists($id, $scenarioByIdMap))
		{
			return '';
		}

		return $scenarioByIdMap[$id];
	}

	public static function getSignTemplatesPermissionByScenario(string $scenario): int
	{
		return  (int)[
			self::B2B => SignPermissionDictionary::SIGN_TEMPLATES,
			self::B2E => SignPermissionDictionary::SIGN_B2E_TEMPLATES,
		][$scenario];
	}

	public static function isValid(string $value): bool
	{
		return in_array($value, self::getAll(), true);
	}
}
