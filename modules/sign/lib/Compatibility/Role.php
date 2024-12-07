<?php

namespace Bitrix\Sign\Compatibility;

final class Role
{
	public static function createByParty(?int $party): ?string
	{
		return match($party)
		{
			1 => \Bitrix\Sign\Type\Member\Role::ASSIGNEE,
			2 => \Bitrix\Sign\Type\Member\Role::SIGNER,
			default => null,
		};
	}

	/**
	 * Make role for block, according to member party, and document parties count
	 *
	 * @param int $party Member party
	 * @param int $parties Document parties count
	 *
	 * @return string|null
	 */
	public static function createForBlock(int $party, int $parties): ?string
	{
		return match ($party)
		{
			// if member party previous before last in document
			$parties - 1 => \Bitrix\Sign\Type\Member\Role::ASSIGNEE,
			// if member party is last in document
			$parties => \Bitrix\Sign\Type\Member\Role::SIGNER,
			default => null,
		};
	}
}
