<?php

namespace Bitrix\Sign\Type\Document;

use Bitrix\Main\Loader;
use Bitrix\Sign\Type;

/** @see \Bitrix\Sign\Document\Entity\Dummy */
final class EntityType
{
	public const SMART = 'SMART';
	public const SMART_B2E = 'SMART_B2E';

	/**
	 * @return array<self::*>
	 */
	public static function getAll(): array
	{
		return [
			self::SMART,
			self::SMART_B2E,
		];
	}

	public static function getByScenarioType(string $scenarioType): string
	{
		return match ($scenarioType)
		{
			Type\DocumentScenario::SCENARIO_TYPE_B2B => self::SMART,
			Type\DocumentScenario::SCENARIO_TYPE_B2E => self::SMART_B2E,
			default => ''
		};
	}

	public static function getEntityTypeIdByType(string $type): ?int
	{
		if (!Loader::includeModule('crm'))
		{
			return null;
		}

		return match(mb_strtoupper($type))
		{
			self::SMART => \CCrmOwnerType::SmartDocument,
			self::SMART_B2E => \CCrmOwnerType::SmartB2eDocument,
			default => null,
		};
	}
}