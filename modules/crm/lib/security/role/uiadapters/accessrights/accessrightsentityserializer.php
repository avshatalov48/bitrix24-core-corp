<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights;

use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\Permissions\MyCardView;
use Bitrix\Crm\Security\Role\Manage\Permissions\Permission;
use Bitrix\Crm\Security\Role\Manage\Permissions\Transition;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Utils\PermCodeTransformer;
use Bitrix\Main\Access\Permission\PermissionDictionary;

class AccessRightsEntitySerializer
{
	/**
	 * @param EntityDTO[] $entities
	 * @return array
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

				$permRight = $this->createRight(
					$perm->name(),
					$rightCode,
					$perm,
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

						$rights[] = $this->createRight(
							$valueName,
							$fieldRightCode,
							$perm,
							$rightCode
						);
					}
				}


			}

			$accessRights[] = [
				'sectionTitle' => $entity->name(),
				'rights' => $rights,
			];
		}

		return $accessRights;
	}

	private function createRight(
		string $rightName,
		string $rightCode,
		Permission $permission,
		?string $parentCode = null,
	): array
	{

		switch ($permission->code())
		{
			case MyCardView::CODE:
				return [
					'id' => $rightCode,
					'title' => $rightName ? $rightName : $permission->name(),
					'group' => $parentCode,
					'type' => PermissionDictionary::TYPE_TOGGLER,
				];

			case Transition::CODE:

				$variables = [];
				foreach ($permission->variants() as $variantCode => $variantName)
				{
					$item = [
						'id' => $variantCode,
						'title' => $variantName,
					];

					if (in_array($variantCode, [Transition::TRANSITION_ANY, Transition::TRANSITION_INHERIT]))
					{
						$item['selectedAction'] = 'clear-other';
					}

					$variables[] = $item;
				}

				$variables[] = [
					'id' => 'denied',
					'title' => 'Denied',
					'selectedAction' => 'clear-other'
				];

				return [
					'id' => $rightCode,
					'title' => $rightName ? $rightName : $permission->name(),
					'group' => $parentCode,
					'type' => PermissionDictionary::TYPE_MULTIVARIABLES,
					'variables' => $variables,
					'changerOptions' => [
						'disableSelectAll' => true,
						'useSelectedActions' => true,
						'replaceNullValueTo' => '0'
					]
				];

			default:
				$variables = [];
				foreach ($permission->variants() as $variantCode => $variantName)
				{
					if ($variantCode === '' || $variantCode === null)
					{
						$variantCode = '0';
					}

					$variables[] = ['id' => $variantCode, 'title' => $variantName];
				}

				return [
					'id' => $rightCode,
					'title' => $rightName ? $rightName : $permission->name(),
					'group' => $parentCode,
					'type' => PermissionDictionary::TYPE_VARIABLES,
					'variables' => $variables,
					'changerOptions' => [
						'replaceNullValueTo' => '0'
					]
				];
		}
	}

}