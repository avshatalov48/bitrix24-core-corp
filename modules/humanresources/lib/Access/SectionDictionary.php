<?php

namespace Bitrix\HumanResources\Access;

use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Enum\Access\RoleCategory;
use Bitrix\HumanResources\Config;
use Bitrix\Main\Localization\Loc;

class SectionDictionary
{
	private const ACCESS_RIGHTS = 1;
	private const COMPANY_STRUCTURE = 2;
	private const TEAMS = 3;

	/**
	 * returns an array of sections with permissions
	 * @return array<int, array<int>>
	 */
	public static function getMap(RoleCategory $category): array
	{
		if ($category->value === RoleCategory::Department->value)
		{
			return self::getDepartmentMap();
		}

		return self::getTeamMap();
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

	/**
	 * @param self::ACCESS_RIGHTS|self::COMPANY_STRUCTURE|self::TEAMS $value
	 *
	 * @return array{type: string, bgColor: string}|null
	 */
	public static function getIcon(int $value): ?array
	{
		return match ($value)
		{
			self::ACCESS_RIGHTS => [
				'type' => 'administrator',
				'bgColor' => '--ui-color-palette-orange-50',
			],
			self::COMPANY_STRUCTURE => [
				'type' => 'persons-2',
				'bgColor' => '--ui-color-accent-turquoise',
			],
			self::TEAMS => [
				'type' => 'my-plan',
				'bgColor' => '--ui-color-tag-2',
			],
		};
	}

	private static function getRephrasedSection(int $key): ?string
	{
		return match ($key) {
			self::ACCESS_RIGHTS => 'HUMAN_RESOURCES_CONFIG_SECTIONS_ACCESS_RIGHTS_MSGVER_2',
			self::COMPANY_STRUCTURE => 'HUMAN_RESOURCES_CONFIG_SECTIONS_COMPANY_STRUCTURE_MSGVER_1',
			self::TEAMS => 'HUMAN_RESOURCES_CONFIG_SECTIONS_TEAMS_MSGVER_1',
			default => null,
		};
	}

	private static function getDepartmentMap(): array
	{
		$companyStructure = [
			PermissionDictionary::HUMAN_RESOURCES_STRUCTURE_VIEW,
			PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_CREATE,
			PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_DELETE,
			PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_EDIT,
			PermissionDictionary::HUMAN_RESOURCES_EMPLOYEE_ADD_TO_DEPARTMENT,
			PermissionDictionary::HUMAN_RESOURCES_EMPLOYEE_REMOVE_FROM_DEPARTMENT,
		];

		if (Config\Feature::instance()->isDepartmentSettingsAvailable())
		{
			$companyStructure[] = PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_SETTINGS_EDIT;
		}

		$companyStructure[] = PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_CHAT_EDIT;
		$companyStructure[] = PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_CHANNEL_EDIT;
		$companyStructure[] = PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_COLLAB_EDIT;

		$accessRights = [
			PermissionDictionary::HUMAN_RESOURCES_USERS_ACCESS_EDIT,
		];

		if (Config\Feature::instance()->isHRInvitePermissionAvailable())
		{
			$companyStructure[] = PermissionDictionary::HUMAN_RESOURCES_USER_INVITE;
		}

		if (Config\Feature::instance()->isHRFirePermissionAvailable())
		{
			$accessRights[] = PermissionDictionary::HUMAN_RESOURCES_FIRE_EMPLOYEE;
		}

		$sections[self::COMPANY_STRUCTURE] = $companyStructure;
		$sections[self::ACCESS_RIGHTS] = $accessRights;

		return $sections;
	}

	private static function getTeamMap(): array
	{
		$sections = [];

		$teamsPermissions = [
			PermissionDictionary::HUMAN_RESOURCES_TEAM_VIEW,
			PermissionDictionary::HUMAN_RESOURCES_TEAM_CREATE,
			PermissionDictionary::HUMAN_RESOURCES_TEAM_DELETE,
			PermissionDictionary::HUMAN_RESOURCES_TEAM_EDIT,
			PermissionDictionary::HUMAN_RESOURCES_TEAM_MEMBER_ADD,
			PermissionDictionary::HUMAN_RESOURCES_TEAM_MEMBER_REMOVE,
			PermissionDictionary::HUMAN_RESOURCES_TEAM_CHAT_EDIT,
			PermissionDictionary::HUMAN_RESOURCES_TEAM_CHANNEL_EDIT,
			PermissionDictionary::HUMAN_RESOURCES_TEAM_COLLAB_EDIT,
			PermissionDictionary::HUMAN_RESOURCES_TEAM_SETTINGS_EDIT,
		];

		if (Config\Feature::instance()->isCrossFunctionalTeamsAvailable())
		{
			$sections[self::TEAMS] = $teamsPermissions;
			$sections[self::ACCESS_RIGHTS] = [
				PermissionDictionary::HUMAN_RESOURCES_TEAM_ACCESS_EDIT
			];
		}

		return $sections;
	}
}