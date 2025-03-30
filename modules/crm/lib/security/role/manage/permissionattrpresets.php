<?php

namespace Bitrix\Crm\Security\Role\Manage;

use Bitrix\Crm\Security\Role\Manage\AttrPreset\UserRoleAndHierarchy;
use Bitrix\Crm\Security\Role\Manage\Permissions\Add;
use Bitrix\Crm\Security\Role\Manage\Permissions\Automation;
use Bitrix\Crm\Security\Role\Manage\Permissions\Delete;
use Bitrix\Crm\Security\Role\Manage\Permissions\Export;
use Bitrix\Crm\Security\Role\Manage\Permissions\HideSum;
use Bitrix\Crm\Security\Role\Manage\Permissions\Import;
use Bitrix\Crm\Security\Role\Manage\Permissions\MyCardView;
use Bitrix\Crm\Security\Role\Manage\Permissions\Permission;
use Bitrix\Crm\Security\Role\Manage\Permissions\Read;
use Bitrix\Crm\Security\Role\Manage\Permissions\Transition;
use Bitrix\Crm\Security\Role\Manage\Permissions\Write;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlMapper\DependentVariables;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlMapper\Toggler;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlMapper\Variables;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Variants;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Localization\Loc;

class PermissionAttrPresets
{
	/**
	 * @return Permission[]
	 */
	public static function crmEntityPreset(): array
	{
		$hierarchy = (new UserRoleAndHierarchy())->exclude(UserRoleAndHierarchy::THIS_ROLE);
		$variants = $hierarchy->getVariants();

		$withoutUserRoleDependentVariables = (new DependentVariables\UserRoleAndHierarchyAsAttributes())
			->setHierarchy($hierarchy)
			->addSelectedVariablesAlias(
				[
					UserRoleAndHierarchy::SELF,
					UserRoleAndHierarchy::DEPARTMENT,
					UserRoleAndHierarchy::SUBDEPARTMENTS,
					UserRoleAndHierarchy::OPEN,
					UserRoleAndHierarchy::ALL,
				],
				Loc::getMessage('CRM_SECURITY_ROLE_PERMS_TYPE_X'),
			)
		;

		return [
			new Read($variants, $withoutUserRoleDependentVariables),
			new Add($variants, $withoutUserRoleDependentVariables),
			new Write($variants, $withoutUserRoleDependentVariables),
			new Delete($variants, $withoutUserRoleDependentVariables),
			new Export($variants, $withoutUserRoleDependentVariables),
			new Import($variants, $withoutUserRoleDependentVariables),
			new MyCardView(self::allowedYesNo(), (new Toggler())->setDefaultValue(
				(new MyCardView())->getDefaultAttribute() === UserPermissions::PERMISSION_ALL
			)),
		];
	}

	public static function crmEntityPresetAutomation(): array
	{
		return array_merge(
			self::crmEntityPreset(),
			[
				new Automation(self::readWrite()),
			]
		);
	}

	public static function crmEntityKanbanHideSum(): array
	{
		$control = (new Variables())->addAttrMapping(HideSum::INHERIT, null);

		return [
			new HideSum(self::hideSum(), $control),
		];
	}

	public static function crmStageTransition(array $stages = []): array
	{
		$stageIds = array_keys($stages);

		$variants = new Variants();
		$variants->add(
			Transition::TRANSITION_INHERIT,
			(string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_TYPE_TRANSITION_INHERITED'),
			[
				'conflictsWith' => array_merge($stageIds, [Transition::TRANSITION_ANY, Transition::TRANSITION_BLOCKED]),
				'hideInSection' => true,
				'useAsEmptyInSubsection' => true,
				'secondary' => true,
			]
		);
		$variants->add(
			Transition::TRANSITION_ANY,
			(string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_TYPE_TRANSITION_ANY'),
			[
				'conflictsWith' => array_merge(
					$stageIds,
					[Transition::TRANSITION_INHERIT, Transition::TRANSITION_BLOCKED],
				),
				'defaultInSection' => (new Transition())->getDefaultSettings() === [Transition::TRANSITION_ANY],
			]
		);
		$variants->add(
			Transition::TRANSITION_BLOCKED,
			(string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_TYPE_TRANSITION_BLOCKED_MSGVER_1'),
			[
				'conflictsWith' => array_merge($stageIds, [Transition::TRANSITION_ANY, Transition::TRANSITION_INHERIT]),
				'useAsEmptyInSection' => true,
				'useAsNothingSelectedInSubsection' => true,
				'defaultInSection' => (new Transition())->getDefaultSettings() === [Transition::TRANSITION_BLOCKED],
			]
		);
		foreach ($stages as $stageId => $stageName)
		{
			$variants->add(
				$stageId,
				$stageName,
				[
					'hideInSubsection' => $stageId,
					'conflictsWith' => [
						Transition::TRANSITION_ANY,
						Transition::TRANSITION_INHERIT,
						Transition::TRANSITION_BLOCKED,
					],
				]
			);
		}

		return [
			new Transition($variants),
		];
	}

	public static function userHierarchy(): Variants
	{
		$variants = Variants::createFromArray([
			BX_CRM_PERM_SELF => (string)GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_A'),
			BX_CRM_PERM_DEPARTMENT => (string)GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_D'),
			BX_CRM_PERM_SUBDEPARTMENT => (string)GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_F'),
			BX_CRM_PERM_ALL => (string)GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_X'),
		]);

		$variants->add(
			'',
			(string)GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_'),
			[
				'useAsEmptyInSection' => true,
			]
		);
		$variants->moveToTopOfList('');

		return $variants;
	}

	public static function userHierarchyAndOpen(): Variants
	{
		$variants = Variants::createFromArray([
			BX_CRM_PERM_SELF => (string)GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_A'),
			BX_CRM_PERM_DEPARTMENT => (string)GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_D'),
			BX_CRM_PERM_SUBDEPARTMENT => (string)GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_F'),
			BX_CRM_PERM_OPEN => (string)GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_O'),
			BX_CRM_PERM_ALL => (string)GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_X'),
		]);

		$variants->add(
			'',
			(string)GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_'),
			[
				'useAsEmptyInSection' => true,
			]
		);
		$variants->moveToTopOfList('');

		return $variants;
	}

	public static function switchAll(): Variants
	{
		$variants = new Variants();

		$variants->add(
			'',
			(string)GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_'),
			[
				'useAsEmptyInSection' => true,
			]
		);

		$variants->add(
			BX_CRM_PERM_ALL,
			(string)GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_X'),
		);

		return $variants;
	}

	private static function hideSum(): Variants
	{
		$variants = new Variants();

		$variants->add(
			'',
			(string)GetMessage('CRM_SECURITY_ROLE_PERMS_HIDE_SUM'),
			[
				'useAsEmptyInSection' => true,
				'useAsNothingSelectedInSubsection' => true,
				'defaultInSection' => (new HideSum())->getDefaultAttribute() === UserPermissions::PERMISSION_NONE,
			],
		);

		$variants->add(
			BX_CRM_PERM_ALL,
			(string)GetMessage('CRM_SECURITY_ROLE_PERMS_SHOW_SUM'),
			['defaultInSection' => (new HideSum())->getDefaultAttribute() === UserPermissions::PERMISSION_ALL],
		);

		$variants->add(
			HideSum::INHERIT,
			(string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_HIDE_SUM_INHERIT'),
			[
				'hideInSection' => true,
				'useAsEmptyInSubsection' => true,
			]
		);

		return $variants;
	}

	public static function readWrite(): Variants
	{
		$variants = new Variants();

		$variants->add(
			'',
			(string)GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_AUTOMATION_NONE'),
			['useAsEmptyInSection' => true],
		);

		$variants->add(BX_CRM_PERM_ALL, (string)GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_AUTOMATION_ALL'));

		return $variants;
	}

	public static function allowedYesNo(): Variants
	{
		$variants = new Variants();

		$variants->add(
			'',
			(string)GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_ALLOWED_NO'),
			['useAsEmptyInSection' => true],
		);

		$variants->add(BX_CRM_PERM_ALL, (string)GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_ALLOWED_YES'));

		return $variants;
	}
}
