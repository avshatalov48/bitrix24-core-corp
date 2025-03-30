<?php

namespace Bitrix\HumanResources\Access;

use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Config;
use Bitrix\Main\Localization\Loc;

class SectionDictionary
{
	private const ACCESS_RIGHTS = 1;
	private const COMPANY_STRUCTURE = 2;
	private const BINDING_TO_STRUCTURE = 3;

	/**
	 * returns an array of sections with permissions
	 * @return array<int, array<int>>
	 */
	public static function getMap(): array
	{
		$accessRights = [
			PermissionDictionary::HUMAN_RESOURCES_USERS_ACCESS_EDIT,
		];

		if (Config\Storage::instance()->isHRInvitePermissionAvailable())
		{
			$accessRights[] = PermissionDictionary::HUMAN_RESOURCES_USER_INVITE;
		}

		return [
			self::COMPANY_STRUCTURE => [
				PermissionDictionary::HUMAN_RESOURCES_STRUCTURE_VIEW,
				PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_CREATE,
				PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_DELETE,
				PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_EDIT,
				PermissionDictionary::HUMAN_RESOURCES_EMPLOYEE_ADD_TO_DEPARTMENT,
				PermissionDictionary::HUMAN_RESOURCES_EMPLOYEE_REMOVE_FROM_DEPARTMENT,
			],
			self::ACCESS_RIGHTS => $accessRights,
		];
	}

	/**
	 * returns an array of all SectionDictionary constants
	 * @return array<array-key, string>
	 */
	public static function getConstants(): array
	{
		$class = new \ReflectionClass(self::class);
		return array_flip($class->getConstants());
	}

	public static function getTitle(int $value): string
	{
		$sectionsList = self::getConstants();

		if (!array_key_exists($value, $sectionsList))
		{
			return '';
		}

		$rephrasedSectionCode = self::getRephrasedSection($value);
		return Loc::getMessage($rephrasedSectionCode ?? 'HUMAN_RESOURCES_CONFIG_SECTIONS_' . $sectionsList[$value]) ?? '';
	}

	private static function getRephrasedSection(int $key): ?string
	{
		return match ($key) {
			self::BINDING_TO_STRUCTURE => 'HUMAN_RESOURCES_CONFIG_SECTIONS_BINDING_TO_STRUCTURE_MSGVER_1',
			self::ACCESS_RIGHTS => 'HUMAN_RESOURCES_CONFIG_SECTIONS_ACCESS_RIGHTS_MSGVER_1',
			default => null,
		};
	}
}