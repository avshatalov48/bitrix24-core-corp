<?php

namespace Bitrix\Sign\Type\Member;

final class Role
{
	public const EDITOR = 'editor';
	public const REVIEWER = 'reviewer';
	public const ASSIGNEE = 'assignee';
	public const SIGNER = 'signer';

	private const ROLE_TO_INT_MAP = [
		Role::SIGNER => 0,
		Role::ASSIGNEE => 1,
		Role::REVIEWER => 2,
		Role::EDITOR => 3,
	];

	/**
	 * @return array<self::*, int>
	 */
	public static function toIntMap(): array
	{
		return [
			self::SIGNER => 0,
			self::ASSIGNEE => 1,
			self::REVIEWER => 2,
			self::EDITOR => 3,
		];
	}

	public static function tryFromInt(int $number): ?string
	{
		return array_flip(static::toIntMap())[$number] ?? null;
	}

	/**
	 * @return array<self::*>
	 */
	public static function getAll(): array
	{
		return [
			self::EDITOR,
			self::REVIEWER,
			self::ASSIGNEE,
			self::SIGNER,
		];
	}

	public static function isValid(string $role): bool
	{
		return in_array($role, self::getAll(), true);
	}

	public static function convertRoleToInt(string $role): int
	{
		return Role::ROLE_TO_INT_MAP[$role];
	}

	public static function convertIntToRole(int $roleNumber): string
	{
		return array_flip(Role::ROLE_TO_INT_MAP)[$roleNumber];
	}
}
