<?php

namespace Bitrix\Crm\Security\Role\Manage;

use Bitrix\Crm\Security\EntityPermission\DefaultPermission;
use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\Entity\PermissionEntity;
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
use Bitrix\Crm\Traits\Singleton;

/**
 * Builds a model of CRM rights containing all Entities, their rights and possible values
 * of these rights. Required to create a view for editing rights.
 */
class RoleManagementModelBuilder
{
	use Singleton;

	private ?array $entities = null;
	private ?array $models = null;
	private array $modelsByEntity = [];

	/**
	 * @return PermissionEntity[]
	 */
	public function entities(): array
	{
		if ($this->entities === null)
		{
			$this->entities = self::allEntities();
		}

		return $this->entities;
	}

	public function getEntityNamesWithPermissionClass(DefaultPermission $defaultPermission): array
	{
		$result = [];

		$entities = $this->buildModels();
		foreach ($entities as $entity)
		{
			$permissions = $entity->permissions();
			foreach ($permissions as $permission)
			{
				if ($permission::class === $defaultPermission->getPermissionClass())
				{
					$result[] = $entity->code();
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * @return EntityDTO[]
	 */
	public function buildModels(): array
	{
		if (is_array($this->models))
		{
			return $this->models;
		}

		$this->models = [];
		foreach ($this->entities() as $permEntity)
		{
			foreach ($permEntity->make() as $entity)
			{
				$this->models[] = $entity;
				foreach ($entity->permissions() as $permission)
				{
					$this->modelsByEntity[$entity->code()][$permission->code()] = $permission;
				}
			}
		}

		return $this->models;
	}

	public function getPermissionByCode(string $entityCode, string $permissionCode): ?Permission
	{
		$this->buildModels();

		return $this->modelsByEntity[$entityCode][$permissionCode] ?? null;
	}

	public static function allEntities(): array
	{
		return (new PermissionEntityBuilder())
			->includeAll()
			->build()
		;
	}

	/**
	 * @return Permission[]
	 */
	public static function allPermissions(): array
	{
		static $all = [];

		if (!empty($all))
		{
			return $all;
		}

		$all = [
			new Read(),
			new Add(),
			new Write(),
			new Delete(),
			new Export(),
			new Import(),
			new Automation(),
			new HideSum(),
			new MyCardView(),
			new Transition(),
			new Permissions\CopilotCallAssessment\Write(),
			new Permissions\CopilotCallAssessment\Read(),
		];

		return $all;
	}

	public function clearEntitiesCache(): void
	{
		$this->entities = null;
		$this->models = null;
		$this->modelsByEntity = [];
	}

	/**
	 * @return array<string, string>
	 */
	public static function permTypeControlTypeMap(): array
	{
		$result = [];
		foreach (self::allPermissions() as $permission)
		{
			$result[$permission->code()] = $permission->getControlTypeCode();
		}

		return $result;
	}

	/**
	 * @deprecated
	 */
	public static function getControlTypeByPermType(string $permType): string
	{
		return self::permTypeControlTypeMap()[$permType] ?? '';
	}
}
