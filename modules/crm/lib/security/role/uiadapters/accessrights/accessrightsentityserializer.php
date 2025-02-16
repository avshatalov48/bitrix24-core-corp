<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights;

use Bitrix\Crm\Security\EntityPermission\ApproveCustomPermsToExistRole;
use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\Permissions\Permission;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Utils\PermCodeTransformer;

class AccessRightsEntitySerializer
{
	/**
	 * @param EntityDTO[] $entities
	 * @return array[]
	 */
	public function serialize(array $entities): array
	{
		$accessRights = [];
		foreach ($entities as $entity)
		{

			$rights = [];

			foreach ($entity->permissions() as $perm)
			{
				$rightCode = PermCodeTransformer::getInstance()->makeAccessRightPermCode(
					new PermIdentifier($entity->code(), $perm->code())
				);
				if ((new ApproveCustomPermsToExistRole())->hasWaitingPermission($perm))
				{
					continue;
				}

				$permRight = $this->createRight(
					$perm->name(),
					$rightCode,
					$perm,
					$perm->variants()?->getValuesForSection()
				);

				$permRight['groupHead'] = $perm->canAssignPermissionToStages() && !empty($entity->fields());
				$rights[] = $permRight;

				if (!$perm->canAssignPermissionToStages())
				{
					continue;
				}

				foreach ($entity->fields() as $fieldName => $fieldValues)
				{
					foreach ($fieldValues as $valueCode => $valueName)
					{
						$fieldRightCode = PermCodeTransformer::getInstance()->makeAccessRightPermCode(
							new PermIdentifier($entity->code(), $perm->code(), $fieldName, $valueCode)
						);

						$right = $this->createRight(
							$valueName,
							$fieldRightCode,
							$perm,
							$perm->variants()?->getValuesForSubsection($valueCode),
							$rightCode
						);

						// prefer 'inherit' on group actions
						$right['setEmptyOnSetMinMaxValueInColumn'] = true;
						// todo compatibility, remove when fresh ui update is out
						$right['setEmptyOnGroupActions'] = true;

						$rights[] = $right;
					}
				}
			}
			if (empty($rights))
			{
				continue;
			}
			$accessRightSection = [
				'sectionTitle' => $entity->name(),
				'sectionCode' => $entity->code(),
				'rights' => $rights,
			];
			if ($entity->description())
			{
				$accessRightSection['sectionSubTitle'] = $entity->description();
			}
			if ($entity->iconCode() && $entity->iconColor())
			{
				$accessRightSection['sectionIcon'] = [
					'type' => $entity->iconCode(),
					'bgColor' => $entity->iconColor(),
				];
			}

			$accessRights[] = $accessRightSection;
		}

		return $accessRights;
	}

	private function createRight(
		string $rightName,
		string $rightCode,
		Permission $permission,
		?array $variables = null,
		?string $parentCode = null,
	): array
	{
		$result = [
			'id' => $rightCode,
			'title' => $rightName ?: $permission->name(),
			'hint' => $permission->explanation(),
			'group' => $parentCode,
		];
		$controlType = $permission->getControlMapper();
		$result['type'] = $controlType->getType();
		$result['minValue'] = $controlType->getMinValue();
		$result['maxValue'] = $controlType->getMaxValue();
		$result = array_merge($result,  $controlType->getExtraOptions());

		if (!is_null($variables))
		{
			$result['variables'] = $variables;

			$emptyValue = $this->getEmptyValue($variables);
			if ($emptyValue !== null)
			{
				$result['emptyValue'] = $emptyValue;
			}

			$nothingSelectedValue = $this->getNothingSelectedValue($variables);
			if ($nothingSelectedValue !== null)
			{
				$result['nothingSelectedValue'] = $nothingSelectedValue;
			}

			$defaultValue = $this->getDefaultValue($variables);
			if ($defaultValue !== null)
			{
				$result['defaultValue'] = $defaultValue;
			}
		}

		return $result;
	}

	private function getEmptyValue(array $variables): ?string
	{
		foreach ($variables as $variable)
		{
			if ($variable['useAsEmpty'] ?? false)
			{
				return $variable['id'];
			}
		}

		return null;
	}

	private function getNothingSelectedValue(array $variables): ?string
	{
		foreach ($variables as $variable)
		{
			if ($variable['useAsNothingSelected'] ?? false)
			{
				return $variable['id'];
			}
		}

		return null;
	}

	private function getDefaultValue(array $variables): ?string
	{
		foreach ($variables as $variable)
		{
			if ($variable['default'] ?? false)
			{
				return $variable['id'];
			}
		}

		return null;
	}
}
