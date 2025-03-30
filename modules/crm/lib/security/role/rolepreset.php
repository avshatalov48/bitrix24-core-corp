<?php

namespace Bitrix\Crm\Security\Role;

use Bitrix\Crm\CategoryIdentifier;
use Bitrix\Crm\Integration\Catalog\Contractor\CategoryRepository;
use Bitrix\Crm\Security\Role\Manage\AttrPreset\UserRoleAndHierarchy;
use Bitrix\Crm\Security\Role\Manage\Permissions\HideSum;
use Bitrix\Crm\Security\Role\Manage\Permissions\Import;
use Bitrix\Crm\Security\Role\Manage\Permissions\MyCardView;
use Bitrix\Crm\Security\Role\Manage\Permissions\Permission;
use Bitrix\Crm\Security\Role\Manage\Permissions\Transition;
use Bitrix\Crm\Security\Role\Manage\RoleManagementModelBuilder;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Localization\Loc;
use CCrmOwnerType;
use function Symfony\Component\String\s;

final class RolePreset {

	public const ADMIN = 'ADMIN';
	public const HEAD = 'HEAD';
	public const DEPUTY = 'DEPUTY';
	public const MANAGER = 'MANAGER';
	public const OBSERVER = 'OBSERVER';

	public static function GetDefaultRolesPreset(): array
	{
		return [
			self::MANAGER => [
				'NAME' => Loc::getMessage('CRM_ROLE_MANAGER'),
				'CODE' => self::MANAGER,
				'RELATION' => [
					'LEAD' => self::getManagerPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Lead)),
					'DEAL' => self::getManagerPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Deal)),
					'CONTACT' => self::getManagerPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Contact)),
					'COMPANY' => self::getManagerPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Company)),
					'QUOTE' => self::getManagerPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Quote)),
					'INVOICE' => self::getManagerPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Invoice)),

					'WEBFORM' => [
						'READ' => ['-' => 'X'],
						'WRITE' => ['-' => 'X'],
					],
					'BUTTON' => [
						'READ' => ['-' => 'X'],
						'WRITE' => ['-' => 'X'],
					],
                    'SALETARGET' => [
                        'READ' => ['-' => 'A'],
                    ],
                    'EXCLUSION' => [
                        'READ' => ['-' => 'X'],
                    ],

					'CCA' => [
						'READ' => ['-' => 'X'],
						'WRITE' => ['-' => 'X'],
					],
				],
			],
			self::HEAD => [
				'NAME' => Loc::getMessage('CRM_ROLE_HEAD'),
				'CODE' => self::HEAD,
				'RELATION' => [
					'LEAD' => self::getHeadPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Lead)),
					'DEAL' => self::getHeadPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Deal)),
					'CONTACT' => self::getHeadPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Contact)),
					'COMPANY' => self::getHeadPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Company)),
					'QUOTE' => self::getHeadPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Quote)),
					'INVOICE' => self::getHeadPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Invoice)),

					'WEBFORM' => [
						'READ' => ['-' => 'X'],
						'WRITE' => ['-' => 'X'],
					],
					'BUTTON' => [
						'READ' => ['-' => 'X'],
						'WRITE' => ['-' => 'X'],
					],
                    'SALETARGET' => [
                        'READ' => ['-' => 'X'],
                        'WRITE' => ['-' => 'X'],
                    ],
                    'EXCLUSION' => [
                        'READ' => ['-' => 'X'],
                        'WRITE' => ['-' => 'X'],
                    ],

					'CCA' => [
						'READ' => ['-' => 'X'],
						'WRITE' => ['-' => 'X'],
					],
				],
			],
			self::DEPUTY => [
				'NAME' => Loc::getMessage('CRM_ROLE_DEPUTY'),
				'CODE' => self::DEPUTY,
				'RELATION' => [
					'LEAD' => self::getDeputyPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Lead)),
					'DEAL' => self::getDeputyPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Deal)),
					'CONTACT' => self::getDeputyPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Contact)),
					'COMPANY' => self::getDeputyPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Company)),
					'QUOTE' => self::getDeputyPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Quote)),
					'INVOICE' => self::getDeputyPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Invoice)),

					'WEBFORM' => [
						'READ' => ['-' => 'X'],
						'WRITE' => ['-' => 'X'],
					],
					'BUTTON' => [
						'READ' => ['-' => 'X'],
						'WRITE' => ['-' => 'X'],
					],
                    'SALETARGET' => [
                        'READ' => ['-' => 'D'],
                        'WRITE' => ['-' => 'X'],
                    ],
                    'EXCLUSION' => [
                        'READ' => ['-' => 'X'],
                        'WRITE' => ['-' => 'X'],
                    ],

					'CCA' => [
						'READ' => ['-' => 'X'],
						'WRITE' => ['-' => 'X'],
					],
				],
			],
			self::OBSERVER => [
				'NAME' => Loc::getMessage('CRM_ROLE_OBSERVER'),
				'CODE' => self::OBSERVER,
				'RELATION' => [
					'LEAD' => self::getObserverPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Lead)),
					'DEAL' => self::getObserverPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Deal)),
					'CONTACT' => self::getObserverPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Contact)),
					'COMPANY' => self::getObserverPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Company)),
					'QUOTE' => self::getObserverPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Quote)),
					'INVOICE' => self::getObserverPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Invoice)),
				],
			],
			self::ADMIN => [
				'NAME' => Loc::getMessage('CRM_ROLE_ADMIN'),
				'CODE' => self::ADMIN,
				'RELATION' => [
					'LEAD' => self::getMaxPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Lead)),
					'DEAL' => self::getMaxPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Deal)),
					'CONTACT' => self::getMaxPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Contact)),
					'COMPANY' => self::getMaxPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Company)),
					'QUOTE' => self::getMaxPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Quote)),
					'INVOICE' => self::getMaxPermissionSetForEntity(new CategoryIdentifier(CCrmOwnerType::Invoice)),

					'WEBFORM' => [
						'READ' => ['-' => 'X'],
						'WRITE' => ['-' => 'X'],
					],
					'BUTTON' => [
						'READ' => ['-' => 'X'],
						'WRITE' => ['-' => 'X'],
					],
					'CONFIG' => [
						'WRITE' => ['-' => 'X'],
					],
                    'SALETARGET' => [
                        'READ' => ['-' => 'X'],
                        'WRITE' => ['-' => 'X'],
                    ],
                    'EXCLUSION' => [
                        'READ' => ['-' => 'X'],
                        'WRITE' => ['-' => 'X'],
                    ],

					'CCA' => [
						'READ' => ['-' => 'X'],
						'WRITE' => ['-' => 'X'],
					],
				],
			],
		];
	}

	/**
	 * Typical default permissions for ($entityTypeId + $categoryId)
	 *
	 * @param CategoryIdentifier $categoryIdentifier
	 * @return array
	 */
	public static function getDefaultPermissionSetForEntity(CategoryIdentifier $categoryIdentifier): array
	{
		return array_merge(
			\CCrmRole::GetDefaultPermissionSet(),
			self::getBasePermissionSetForEntity($categoryIdentifier)
		);
	}

	/**
	 * Permissions that must be set for new ($entityTypeId + $categoryId) by default
	 *
	 * @param CategoryIdentifier $categoryIdentifier
	 * @return array
	 */
	public static function getBasePermissionSetForEntity(CategoryIdentifier $categoryIdentifier): array
	{
		return self::getPermissionSetForEntityByCondition(
			$categoryIdentifier->getPermissionEntityCode(),
			static function(Permission $permission) use ($categoryIdentifier)
			{
				$attr = $permission->getDefaultAttribute();

				return self::changeAttributeForSpecificEntities($categoryIdentifier, $permission, $attr);
			},
			self::getSpecialSettingsValue($categoryIdentifier),
		);
	}

	public static function getSelfPermissionSetForEntity(CategoryIdentifier $categoryIdentifier): array
	{
		return self::getPermissionSetForEntityByCondition(
			$categoryIdentifier->getPermissionEntityCode(),
			static function(Permission $permission) use ($categoryIdentifier)
			{
				$attr = (
					$permission->variants()?->has(UserPermissions::PERMISSION_SELF) // permission supports 'A' value?
					|| $permission->variants()?->has(UserRoleAndHierarchy::SELF) // permission supports 'SELF' value?
				)
					? UserPermissions::PERMISSION_SELF
					: $permission->getDefaultAttribute()
				;

				return self::changeAttributeForSpecificEntities($categoryIdentifier, $permission, $attr);
			},
			self::getSpecialSettingsValue($categoryIdentifier),
		);
	}

	/**
	 * Maximal permissions that can be set for ($entityTypeId + $categoryId)
	 *
	 * @param CategoryIdentifier $categoryIdentifier
	 * @return array
	 */
	public static function getMaxPermissionSetForEntity(CategoryIdentifier $categoryIdentifier): array
	{
		return self::getPermissionSetForEntityByCondition(
			$categoryIdentifier->getPermissionEntityCode(),
			static fn(Permission $permission) => $permission->getMaxAttributeValue(),
			static fn(Permission $permission) => $permission->getMaxSettingsValue()
		);
	}

	/**
	 * Minimal permissions that can be set for ($entityTypeId + $categoryId)
	 *
	 * @param CategoryIdentifier $categoryIdentifier
	 * @return array
	 */
	public static function getMinPermissionSetForEntity(CategoryIdentifier $categoryIdentifier): array
	{
		return self::getPermissionSetForEntityByCondition(
			$categoryIdentifier->getPermissionEntityCode(),
			static fn(Permission $permission) => $permission->getMinAttributeValue(),
			static fn(Permission $permission) => $permission->getMinSettingsValue()
		);
	}

	/**
	 * Observer permissions that can be set for ($entityTypeId + $categoryId)
	 *
	 * @param CategoryIdentifier $categoryIdentifier
	 * @return array
	 */
	public static function getObserverPermissionSetForEntity(CategoryIdentifier $categoryIdentifier): array
	{
		return self::getPermissionSetForEntityByCondition(
			$categoryIdentifier->getPermissionEntityCode(),
			static fn(Permission $permission) => $permission->getObserverDefaultAttributeValue(),
			static fn(Permission $permission) => $permission->getObserverDefaultSettings(),
		);
	}

	/**
	 * Head permissions that can be set for ($entityTypeId + $categoryId)
	 *
	 * @param CategoryIdentifier $categoryIdentifier
	 * @return array
	 */
	public static function getHeadPermissionSetForEntity(CategoryIdentifier $categoryIdentifier): array
	{
		return self::getPermissionSetForEntityByCondition(
			$categoryIdentifier->getPermissionEntityCode(),
			static fn(Permission $permission) => $permission->getHeadDefaultAttributeValue(),
			static fn(Permission $permission) => $permission->getHeadDefaultSettings(),
		);
	}

	/**
	 * Deputy permissions that can be set for ($entityTypeId + $categoryId)
	 *
	 * @param CategoryIdentifier $categoryIdentifier
	 * @return array
	 */
	public static function getDeputyPermissionSetForEntity(CategoryIdentifier $categoryIdentifier): array
	{
		return self::getPermissionSetForEntityByCondition(
			$categoryIdentifier->getPermissionEntityCode(),
			static fn(Permission $permission) => $permission->getDeputyDefaultAttributeValue(),
			static fn(Permission $permission) => $permission->getDeputyDefaultSettings(),
		);
	}

	/**
	 * Manager permissions that can be set for ($entityTypeId + $categoryId)
	 *
	 * @param CategoryIdentifier $categoryIdentifier
	 * @return array
	 */
	public static function getManagerPermissionSetForEntity(CategoryIdentifier $categoryIdentifier): array
	{
		return self::getPermissionSetForEntityByCondition(
			$categoryIdentifier->getPermissionEntityCode(),
            static function(Permission $permission) use ($categoryIdentifier)
            {
                $entitiesWithImport = [
                    CcrmOwnerType::Lead,
                    CcrmOwnerType::Contact,
                    CCrmOwnerType::Company,
                ];
                $attr = $permission->getManagerDefaultAttributeValue();
                if (
                        in_array($categoryIdentifier->getEntityTypeId(), $entitiesWithImport, true)
                        && $permission->code() === (new Import())->code()
                )
                {
                    $attr = UserPermissions::PERMISSION_SELF;
                }

                return $attr;
            },
            static function(Permission $permission) use ($categoryIdentifier)
            {
                $settings = $permission->getManagerDefaultSettings();
                $entityTypeId = $categoryIdentifier->getEntityTypeId();
                $permittedEntities = [
                    CCrmOwnerType::Lead,
                    CCrmOwnerType::Deal,
                ];
                if (
                        (
                            in_array($entityTypeId, $permittedEntities, true)
                            || CcrmOwnerType::isPossibleDynamicTypeId($entityTypeId)
                        )
                        && $permission->code() === (new Transition())->code()
                )
                {
                    $settings = [Transition::TRANSITION_ANY];
                }

                return $settings;
            },
		);
	}

	public static function getDefaultPermissionSetForEntityByCode(?string $code, CategoryIdentifier $categoryIdentifier): array
	{
         if (in_array($categoryIdentifier->getEntityTypeId(), [CCrmOwnerType::Contact, CCrmOwnerType::Company], true))
        {
            $contractorCategory = CategoryRepository::getByEntityTypeId($categoryIdentifier->getEntityTypeId());
            if ($contractorCategory?->getId() === $categoryIdentifier->getCategoryId())
            {
                return match ($code)
                {
                    self::ADMIN => self::getMaxPermissionSetForEntity($categoryIdentifier),
                    self::HEAD, self::DEPUTY, self::MANAGER, self::OBSERVER => self::getMinPermissionSetForEntity($categoryIdentifier),
                    default => self::getDefaultPermissionSetForEntity($categoryIdentifier),
                };
            }
        }

		return match ($code)
		{
			self::ADMIN => self::getMaxPermissionSetForEntity($categoryIdentifier),
			self::HEAD => self::getHeadPermissionSetForEntity($categoryIdentifier),
			self::DEPUTY => self::getDeputyPermissionSetForEntity($categoryIdentifier),
			self::MANAGER => self::getManagerPermissionSetForEntity($categoryIdentifier),
			self::OBSERVER => self::getObserverPermissionSetForEntity($categoryIdentifier),
			default => self::getDefaultPermissionSetForEntity($categoryIdentifier),
		};
	}

	private static function getPermissionSetForEntityByCondition(string $permissionEntityCode, callable $getAttrValueCallback, callable $getSettingsValueCallback): array
	{
		$permissionSet = [];
		$modelBuilder = RoleManagementModelBuilder::getInstance();
		$modelBuilder->clearEntitiesCache();
		$permissionEntities = $modelBuilder->buildModels();
		foreach ($permissionEntities as $permissionEntity)
		{
			if ($permissionEntityCode === $permissionEntity->code())
			{
				foreach ($permissionEntity->permissions() as $permission)
				{
					$defaultAttr = $getAttrValueCallback($permission);
					$defaultSettings = $getSettingsValueCallback($permission);
					$permissionCode = $permission->code();
					if (!is_null($defaultAttr) || !empty($defaultSettings))
					{
						if (!isset($permissionSet[$permissionCode]))
						{
							$permissionSet[$permissionCode] = [
								'-' => []
							];
						}
						$permissionSet[$permissionCode]['-']['ATTR'] = $defaultAttr;
						$permissionSet[$permissionCode]['-']['SETTINGS'] = empty($defaultSettings) ? null : $defaultSettings;
					}
				}

				break;
			}
		}

		return $permissionSet;
	}

	/**
	 * Sets the Transition permission settings to ANY for smart process and deal
	 */
	public static function getSpecialSettingsValue(CategoryIdentifier $categoryIdentifier): \Closure
	{
		return static function (Permission $permission) use ($categoryIdentifier) {
			$settings = $permission->getDefaultSettings();
			$entityTypeId = $categoryIdentifier->getEntityTypeId();
			$permittedEntities = [
				CCrmOwnerType::Deal,
			];
			if (
				(
					in_array($entityTypeId, $permittedEntities, true)
					|| CcrmOwnerType::isPossibleDynamicTypeId($entityTypeId)
				)
				&& $permission->code() === (new Transition())->code()
			)
			{
				$settings = [Transition::TRANSITION_ANY];
			}

			return $settings;
		};
	}

	/**
	 * Sets the permissions MyCardView and HideSum to ALL for smart process and deal
	 */
	private static function changeAttributeForSpecificEntities(
		CategoryIdentifier $categoryIdentifier,
		Permission $permission,
		?string $attr
	): ?string
	{
		$entityTypeId = $categoryIdentifier->getEntityTypeId();
		$permittedEntities = [
			CCrmOwnerType::Deal,
		];
		$specificPermissions = [
			(new HideSum())->code(),
			(new MyCardView())->code(),
		];
		if (
			(
				in_array($entityTypeId, $permittedEntities, true)
				|| CcrmOwnerType::isPossibleDynamicTypeId($entityTypeId)
			)
			&& in_array($permission->code(), $specificPermissions, true)
		)
		{
			$attr = UserPermissions::PERMISSION_ALL;
		}

		return $attr;
	}
}