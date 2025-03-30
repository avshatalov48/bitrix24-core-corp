<?php

namespace Bitrix\Crm\Agent\Security;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Agent\Security\Service\PermissionExtender\ConfigExtender;
use Bitrix\Crm\Agent\Security\Service\RoleCollectionSeparator;
use Bitrix\Crm\Agent\Security\Service\RoleSeparator;
use Bitrix\Crm\Feature\PermissionsLayoutV2;
use Bitrix\Crm\Security\Role\GroupCodeGenerator;
use Bitrix\Crm\Security\Role\Manage\Entity\AutomatedSolutionConfig;
use Bitrix\Crm\Security\Role\Manage\Entity\AutomatedSolutionList;
use Bitrix\Crm\Security\Role\Manage\Entity\ButtonConfig;
use Bitrix\Crm\Security\Role\Manage\Entity\WebFormConfig;
use Bitrix\Crm\Security\Role\Model\EO_Role;
use Bitrix\Crm\Security\Role\Model\EO_Role_Collection;
use Bitrix\Crm\Security\Role\Model\RolePermissionTable;
use Bitrix\Crm\Security\Role\Model\RoleTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\Result;
use Bitrix\Crm\Security\Role\Model\EO_RolePermission_Collection;
use Bitrix\Crm\Security\Role\Utils\RolePermissionLogContext;
use Bitrix\Main\ORM\Objectify\Values;

final class SeparateRolesAgent extends AgentBase
{
	public static function activateNewPermissionsInterface(): bool
	{
		(new PermissionsLayoutV2())->enableWithoutAgent();

		return self::doRun();
	}

	public static function doRun(): bool
	{
		$roleCollection = self::findAllRoles();
		if ($roleCollection->isEmpty())
		{
			return false;
		}
		RolePermissionLogContext::getInstance()->set([
			'scenario' => 'separate roles agent',
		]);

		$separators = [
			(new RoleSeparator\PermissionType('WEBFORM', GroupCodeGenerator::getCrmFormGroupCode()))
				->expandBy(new ConfigExtender(WebFormConfig::CODE))
			,
			(new RoleSeparator\PermissionType('BUTTON', GroupCodeGenerator::getWidgetGroupCode()))
				->expandBy(new ConfigExtender(ButtonConfig::CODE))
			,
			...self::createAutomatedSolutionSeparators(),
			new RoleSeparator\CustomSectionList(),
		];

		$roleCollectionSeparator = new RoleCollectionSeparator($roleCollection, $separators);
		$separateResult = $roleCollectionSeparator->separate();

		if (!$separateResult->getSeparatedRoles()->isEmpty())
		{
			array_map(static fn (EO_Role $role): Result => $role->save(), $separateResult->getSeparatedRoles()->getAll());
			self::deleteEmptyRoles($separateResult->getChangedRoles());
		}
		RolePermissionLogContext::getInstance()->clear();

		if (!$separateResult->getPermissionsToRemove()->isEmpty())
		{
			self::deleteEmptyPermissions($separateResult->getPermissionsToRemove());
		}

		\CCrmRole::ClearCache();

		return false;
	}

	private static function createAutomatedSolutionSeparators(): array
	{
		$manager = Container::getInstance()->getAutomatedSolutionManager();
		$solutions = $manager->getExistingAutomatedSolutions();

		$separators = [];
		foreach ($solutions as $solution)
		{
			$solutionId = $solution['ID'];
			$typeIds = $solution['TYPE_IDS'] ?? [];
			$entityTypeIds = [];

			foreach ($typeIds as $typeId)
			{
				$entityTypeId = Container::getInstance()->getType($typeId)?->getEntityTypeId();
				if (\CCrmOwnerType::IsDefined($entityTypeId))
				{
					$entityTypeIds[] = $entityTypeId;
				}
			}

			if (empty($entityTypeIds))
			{
				continue;
			}

			$separators[] = (new RoleSeparator\CustomSection($solutionId, $entityTypeIds))
				->expandBy(new ConfigExtender(AutomatedSolutionConfig::generateEntity($solutionId)))
			;
		}

		return $separators;
	}

	private static function findAllRoles(): EO_Role_Collection
	{
		/** @throws ArgumentException */
		$nullOrEmpty = static function (string $fieldName): ConditionTree {
			return (new ConditionTree())
				->logic(ConditionTree::LOGIC_OR)
				->whereNull($fieldName)
				->where($fieldName, '')
			;
		};

		$roleCollection = RoleTable::query()
			->setSelect(['*', 'PERMISSIONS'])
			->where('IS_SYSTEM', 'N')
			->where($nullOrEmpty('GROUP_CODE'))
			->where($nullOrEmpty('CODE'))
			->fetchCollection()
		;

		$roleCollection->fillRelations();

		return $roleCollection;
	}

	private static function deleteEmptyRoles(EO_Role_Collection $changedRoles): void
	{
		foreach ($changedRoles as $role)
		{
			$existedValueblePermission = RolePermissionTable::query()
				->where('ROLE_ID', $role->getId())
				->whereIn('PERM_TYPE', [
					'READ',
					'ADD',
					'WRITE',
					'DELETE',
					'EXPORT',
					'IMPORT',
					'AUTOMATION',
				])
				->whereIn('ATTR', [
					UserPermissions::PERMISSION_SELF,
					UserPermissions::PERMISSION_DEPARTMENT,
					UserPermissions::PERMISSION_SUBDEPARTMENT,
					UserPermissions::PERMISSION_OPENED,
					UserPermissions::PERMISSION_ALL,
					UserPermissions::PERMISSION_CONFIG,
				])
				->setLimit(1)
				->setSelect(['ID'])
				->fetch()
			;
			if (!$existedValueblePermission)
			{
				RolePermissionLogContext::getInstance()->set([
					'scenario' => 'separate roles, delete empty role',
				]);
				\Bitrix\Crm\Security\Role\Repositories\PermissionRepository::getInstance()->deleteRole($role->getId());
				RolePermissionLogContext::getInstance()->clear();
			}
		}
	}

	private static function deleteEmptyPermissions(EO_RolePermission_Collection $emptyPermissions): void
	{
		$logContext = RolePermissionLogContext::getInstance();
		$logContext->set([
			'scenario' => 'separate roles agent, delete empty permissions',
		]);
		$logContext->disableOrmEventsLog();
		foreach ($emptyPermissions as $emptyPermission)
		{
			Container::getInstance()->getLogger('Permissions')->info(
				"Deleted empty permissions in role #{ROLE_ID}",
				$logContext->appendTo($emptyPermission->collectValues(Values::ALL, \Bitrix\Main\ORM\Fields\FieldTypeMask::SCALAR))
			);
			RolePermissionTable::delete($emptyPermission->getId());
		}
		$logContext->enableOrmEventsLog();
		$logContext->clear();
	}
}
