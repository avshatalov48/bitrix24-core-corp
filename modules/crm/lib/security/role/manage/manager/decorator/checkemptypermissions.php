<?php

namespace Bitrix\Crm\Security\Role\Manage\Manager\Decorator;

use Bitrix\Crm\Security\Role\Manage\DTO\PermissionModel;
use Bitrix\Crm\Security\Role\Manage\DTO\RoleSelectionManager\CreateSettingsDto;
use Bitrix\Crm\Security\Role\Manage\RoleManagementModelBuilder;
use Bitrix\Crm\Security\Role\Manage\RoleSelectionManager;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Utils\PermCodeTransformer;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Uri;

class CheckEmptyPermissions implements RoleSelectionManager
{
	public function __construct(
		private readonly RoleSelectionManager $manager,
	)
	{
	}

	public static function create(?CreateSettingsDto $settingsDto): ?self
	{
		return null;
	}

	public function buildModels(): array
	{
		return $this->manager->buildModels();
	}

	public function preSaveChecks(array $userGroups): Result
	{
		$result = $this->manager->preSaveChecks($userGroups);

		foreach ($userGroups as $userGroup)
		{
			if (!$userGroup->isNew())
			{
				continue;
			}

			$hasRights = false;
			foreach ($userGroup->accessRights ?? [] as $accessRight)
			{
				try
				{
					$identifier = PermCodeTransformer::getInstance()->decodeAccessRightCode($accessRight->id);
				}
				catch (ArgumentException $e)
				{
					continue;
				}

				$permission = RoleManagementModelBuilder::getInstance()->getPermissionByCode(
					$identifier->entityCode,
					$identifier->permCode,
				);

				if ($permission === null)
				{
					continue;
				}

				$control = $permission->getControlMapper();
				$value = is_array($accessRight->value) ? $accessRight->value : [$accessRight->value];

				if ($value === [\Bitrix\Crm\Security\Role\Manage\AttrPreset\UserRoleAndHierarchy::INHERIT])
				{
					continue;
				}
				$attr = $control->getAttrFromUiValue($value);
				$settings = $control->getSettingsFromUiValue($value);

				if (!\Bitrix\Crm\Security\Role\Utils\RolePermissionChecker::isPermissionEmpty(new PermissionModel(
					$identifier->entityCode,
					$identifier->permCode,
					(string)$identifier->field,
					$identifier->fieldValue,
					$attr,
					$settings
				)))
				{
					$hasRights = true;
					break;
				}
			}

			if (!$hasRights)
			{
				$message = Loc::getMessage('CRM_SECURITY_ENTITIES_SELECTION_EMPTY_ACCESS_RIGHT_ERROR', [
					'#ROLE_TITLE#' => htmlspecialcharsbx($userGroup->title),
				]);

				$result->addError(new Error($message, 'EMPTY_PERMISSIONS'));
			}
		}

		return $result;
	}

	public function hasPermissionsToEditRights(): bool
	{
		return $this->manager->hasPermissionsToEditRights();
	}

	public function prohibitToSaveRoleWithoutAtLeastOneRight(): bool
	{
		return $this->manager->prohibitToSaveRoleWithoutAtLeastOneRight();
	}

	public function needShowRoleWithoutRights(): bool
	{
		return $this->manager->needShowRoleWithoutRights();
	}

	public function getSliderBackUrl(): ?Uri
	{
		return $this->manager->getSliderBackUrl();
	}

	public function getUrl(): ?Uri
	{
		return $this->manager->getUrl();
	}

	public function isAvailableTool(): bool
	{
		return $this->manager->isAvailableTool();
	}

	public function printInaccessibilityContent(): void
	{
		$this->manager->printInaccessibilityContent();
	}

	public function getGroupCode(): ?string
	{
		return $this->manager->getGroupCode();
	}

	public function getMenuId(): ?string
	{
		return $this->manager->getMenuId();
	}
}
