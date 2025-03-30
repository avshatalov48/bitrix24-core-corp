<?php

namespace Bitrix\Sign\Type;

use Bitrix\Sign\Item;

final class DocumentScenario
{
	public const SCENARIO_TYPE_B2B = 'b2b';
	public const SCENARIO_TYPE_B2E = 'b2e';

	public const SIMPLE_SIGN_ONE_PARTY_MANY_MEMBERS = 'SimpleSign:OneParty.ManyMembers';
	public const SIMPLE_SIGN_MANY_PARTIES_ONE_MEMBERS = 'SimpleSign:ManyParties.OneMember';
	public const DSS_ONE_PARTY_MANY_MEMBERS = 'Dss:OneParty.ManyMembers';
	public const DSS_SECOND_PARTY_MANY_MEMBERS = 'Dss:SecondParty.ManyMembers';

	public static function resolveByParties(int $parties, int $lastPartySignerCount, bool $dss = false): ?string
	{
		if ($dss)
		{
			switch($parties) {
				case 1: return self::DSS_ONE_PARTY_MANY_MEMBERS;
				case 2: return self::DSS_SECOND_PARTY_MANY_MEMBERS;
				default: return null;
			}
		}

		if ($parties === 1 && $lastPartySignerCount > 0)
		{
			return self::SIMPLE_SIGN_ONE_PARTY_MANY_MEMBERS;
		}

		if ($parties > 1 && $lastPartySignerCount === 1)
		{
			return self::SIMPLE_SIGN_MANY_PARTIES_ONE_MEMBERS;
		}

		return null;
	}

	/**
	 * @return array<self::*>
	 */
	public static function getAll(): array
	{
		return [
			self::SIMPLE_SIGN_ONE_PARTY_MANY_MEMBERS,
			self::SIMPLE_SIGN_MANY_PARTIES_ONE_MEMBERS,
			self::DSS_ONE_PARTY_MANY_MEMBERS,
			self::DSS_SECOND_PARTY_MANY_MEMBERS,
		];
	}

	/**
	 * @return array<string>
	 */
	public static function getB2BScenarios(): array
	{
		return [
			self::SIMPLE_SIGN_ONE_PARTY_MANY_MEMBERS,
			self::SIMPLE_SIGN_MANY_PARTIES_ONE_MEMBERS,
		];
	}

	/**
	 * @return array<string>
	 */
	public static function getB2EScenarios(): array
	{
		return [
			self::DSS_ONE_PARTY_MANY_MEMBERS,
			self::DSS_SECOND_PARTY_MANY_MEMBERS,
		];
	}

	public static function isB2eScenarioByDocument(?Item\Document $document): bool
	{
		return self::isB2eScenario($document?->scenario);
	}

	public static function isB2bScenarioByDocument(?Item\Document $document): bool
	{
		return self::isB2BScenario($document?->scenario);
	}

	/**
	 * @param ?string $scenario
	 * @return bool
	 */
	public static function isB2EScenario(?string $scenario): bool
	{
		if ($scenario === null)
		{
			return false;
		}

		return in_array($scenario, self::getB2EScenarios(), true);
	}

	public static function isB2BScenario(?string $scenario): bool
	{
		if ($scenario === null)
		{
			return false;
		}

		return in_array($scenario, self::getB2BScenarios(), true);
	}
}
