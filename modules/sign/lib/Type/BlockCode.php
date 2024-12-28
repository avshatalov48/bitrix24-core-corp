<?php

namespace Bitrix\Sign\Type;

use Bitrix\Sign\Type\Member\Role;

final class BlockCode
{
	public const MY_REFERENCE = 'myreference';
	public const MY_REQUISITES = 'myrequisites';
	public const MY_SIGN = 'mysign';
	public const MY_STAMP = 'mystamp';

	public const REFERENCE = 'reference';
	public const REQUISITES = 'requisites';
	public const SIGN = 'sign';
	public const STAMP = 'stamp';

	public const DATE = 'date';
	public const TEXT = 'text';
	public const NUMBER = 'number';

	public const B2E_REFERENCE = 'b2ereference';
	public const B2E_MY_REFERENCE = 'myb2ereference';
	public const EMPLOYEE_DYNAMIC = 'employeedynamic';
	public const B2E_HCMLINK_REFERENCE = 'hcmlinkreference';

	/**
	 * @return array<self::*>
	 */
	public static function getAll(): array
	{
		return [
			self::TEXT,
			self::DATE,
			self::MY_REFERENCE,
			self::MY_REQUISITES,
			self::MY_SIGN,
			self::MY_STAMP,
			self::REQUISITES,
			self::REFERENCE,
			self::SIGN,
			self::STAMP,
			self::NUMBER,

			self::B2E_REFERENCE,
			self::B2E_MY_REFERENCE,
			self::EMPLOYEE_DYNAMIC,
			self::B2E_HCMLINK_REFERENCE,
		];
	}

	/**
	 * @return array<self::*>
	 */
	public static function getCommon(): array
	{
		return [
			self::DATE,
			self::TEXT,
			self::NUMBER,
		];
	}

	/**
	 * @param self::* $code
	 *
	 * @return bool
	 */
	public static function isCommon(string $code): bool
	{
		return in_array($code, self::getCommon(), true);
	}

	/**
	 * @param self::* $code
	 *
	 * @return bool
	 */
	public static function isSignature(string $code): bool
	{
		return in_array($code, [self::MY_SIGN, self::SIGN], true);
	}

	/**
	 * @param self::* $code
	 *
	 * @return bool
	 */
	public static function isStamp(string $code): bool
	{
		return in_array($code, [self::MY_STAMP, self::STAMP], true);
	}

	/**
	 * @param self::* $code
	 *
	 * @return bool
	 */
	public static function isReference(string $code): bool
	{
		return in_array($code, [self::MY_REFERENCE, self::REFERENCE], true);
	}

	/**
	 * @param self::* $code
	 *
	 * @return bool
	 */
	public static function isRequisites(string $code): bool
	{
		return in_array($code, [self::MY_REQUISITES, self::REQUISITES], true);
	}

	/**
	 * @param self::* $code
	 *
	 * @return bool
	 */
	public static function isB2eReference(string $code): bool
	{
		return in_array($code, [self::B2E_REFERENCE, self::B2E_MY_REFERENCE], true);
	}

	public static function getB2eReferenceCodeByRole(string $role): string
	{
		return $role === Role::ASSIGNEE ? BlockCode::B2E_MY_REFERENCE : BlockCode::B2E_REFERENCE;
	}

	public static function isMemberDynamic(string $code): bool
	{
		return $code === self::EMPLOYEE_DYNAMIC;
	}

	public static function isHcmLinkReference(string $code): bool
	{
		return $code === self::B2E_HCMLINK_REFERENCE;
	}
}
