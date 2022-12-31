<?php

namespace Bitrix\Crm\Security\Controller;

use Bitrix\Crm;
use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\Security\AccessAttribute\Manager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\NotSupportedException;

abstract class Base extends Crm\Security\Controller
{
	protected $cachedAttrs = [];

	public function isEntityTypeSupported(int $entityTypeId): bool
	{
		return ($this->getEntityTypeID() === $entityTypeId);
	}

	public function isPermissionEntityTypeSupported($entityType): bool
	{
		if ($this->hasCategories())
		{
			return (new PermissionEntityTypeHelper($this->getEntityTypeId()))->doesPermissionEntityTypeBelongToEntity($entityType);
		}

		return \CCrmOwnerType::ResolveName($this->getEntityTypeId()) === $entityType;
	}

	/**
	 * @inheritDoc
	 */
	public function getPermissionAttributes(string $permissionEntityType, array $entityIDs): array
	{
		$entityTypeId = $this->getEntityTypeId();
		if (!isset($this->cachedAttrs[$entityTypeId]))
		{
			$this->cachedAttrs[$entityTypeId] = [];
		}

		$entityIDs = array_unique(array_filter(array_map('intval', $entityIDs)));
		if (empty($entityIDs))
		{
			return [];
		}

		$result = [];
		$notCachedIds = [];
		foreach ($entityIDs as $entityId)
		{
			if (!array_key_exists($entityId, $this->cachedAttrs[$entityTypeId]))
			{
				$notCachedIds[] = $entityId;
			}
		}
		if (!empty($notCachedIds))
		{
			foreach ($this->loadPermissionAttributes($notCachedIds) as $loadedEntityId => $loadedEntityAttrs)
			{
				$this->cachedAttrs[$entityTypeId][$loadedEntityId] = $loadedEntityAttrs;
			}
		}
		foreach ($entityIDs as $entityId)
		{
			if (!empty($this->cachedAttrs[$entityTypeId][$entityId]))
			{
				$result[$entityId] = $this->cachedAttrs[$entityTypeId][$entityId];
			}
		}

		return $result;
	}

	public function clearPermissionAttributesCache(int $entityId): void
	{
		$entityTypeId = $this->getEntityTypeId();
		unset($this->cachedAttrs[$entityTypeId][$entityId]);
	}

	abstract public function getEntityTypeId(): int;

	public function getEntityFields($entityId): ?array
	{
		$dataClass = $this->getDataClass();

		$data = $dataClass::getList([
			'filter' => ['=ID' => $entityId],
			'select' => $this->getSelectFields(),
		])->fetch();

		return is_array($data) ? $data : null;
	}

	protected function getDataClass(): string
	{
		$dataClass = '';
		$factory = Crm\Service\Container::getInstance()->getFactory($this->getEntityTypeId());
		if ($factory)
		{
			$dataClass = $factory->getDataClass();
		}
		if (!$dataClass)
		{
			throw new NotSupportedException('No DataManager class found for entity type ' . $this->getEntityTypeId());
		}

		return $dataClass;
	}

	protected function loadPermissionAttributes(array $entityIDs): array
	{
		$observerMap =
			$this->isObservable()
				? Crm\Observer\ObserverManager::getEntityBulkObserverIDs($this->getEntityTypeId(), $entityIDs)
				: [];
		$dataClass = $this->getDataClass();

		$dbResult = $dataClass::getList(
			[
				'filter' => ['@ID' => $entityIDs],
				'select' => $this->getSelectFields(),
			]
		);

		$results = [];
		while ($fields = $dbResult->Fetch())
		{
			$ID = $fields['ID'];
			if (isset($observerMap[$ID]))
			{
				$fields['OBSERVER_IDS'] = $observerMap[$ID];
			}
			$results[$ID] = $this->preparePermissionAttributes($fields);
		}

		return $results;
	}

	abstract protected function getSelectFields(): array;

	/**
	 * Prepare Persistent Entity Permission Attributes.
	 * @param array $fields Source Entity Fields.
	 * @return array|null
	 */
	protected function prepareAccessAttributes(array $fields): ?array
	{
		return [
			'USER_ID' => $this->extractAssignedByFromFields($fields),
			'IS_OPENED' => isset($fields['OPENED']) && $fields['OPENED'] === 'Y',
			'IS_ALWAYS_READABLE' => $this->extractIsAlwaysReadableFromFields($fields),
			'PROGRESS_STEP' => $this->extractProgressStepFromFields($fields),
			'CATEGORY_ID' => $this->extractCategoryFromFields($fields),
		];
	}

	protected function extractAssignedByFromFields(array $fields): int
	{
		$assignedById = isset($fields['ASSIGNED_BY_ID']) ? (int)$fields['ASSIGNED_BY_ID'] : 0;
		if ($assignedById < 0)
		{
			$assignedById = 0;
		}

		return $assignedById;
	}

	protected function extractIsAlwaysReadableFromFields(array $fields): bool
	{
		return false;
	}

	protected function extractProgressStepFromFields(array $fields): string
	{
		return '';
	}

	protected function extractCategoryFromFields(array $fields): int
	{
		return 0;
	}

	protected function preparePermissionAttributes(array $fields): array
	{
		$results = [];
		$assignedByID = isset($fields['ASSIGNED_BY_ID']) ? (int)$fields['ASSIGNED_BY_ID'] : 0;
		if ($assignedByID > 0)
		{
			$results[] = "U{$assignedByID}";

			$userAttrs = \Bitrix\Crm\Service\Container::getInstance()
				->getUserPermissions($assignedByID)
				->getAttributesProvider()
				->getEntityAttributes()
			;

			if (isset($userAttrs['INTRANET']) && is_array($userAttrs['INTRANET']))
			{
				$results = array_merge($results, $userAttrs['INTRANET']);
			}
		}

		if (isset($fields['OPENED']) && $fields['OPENED'] === 'Y')
		{
			$results[] = 'O';
		}

		if ($this->extractIsAlwaysReadableFromFields($fields))
		{
			$results[] = \CCrmPerms::ATTR_READ_ALL;
		}

		if ($this->hasProgressSteps())
		{
			$progressAttr = $this->prepareProgressStepAttribute($fields);
			if ($progressAttr !== '')
			{
				$results[] = $progressAttr;
			}
		}

		if ($this->isObservable() && isset($fields['OBSERVER_IDS']) && is_array($fields['OBSERVER_IDS']))
		{
			foreach ($fields['OBSERVER_IDS'] as $observerID)
			{
				$results[] = "CU{$observerID}";
			}
		}

		return $results;
	}

	public function hasCategories(): bool
	{
		return false;
	}

	//region ProgressSteps
	public function hasProgressSteps(): bool
	{
		return false;
	}

	public function extractCategoryId(string $permissionEntityType): int
	{
		if ($this->hasCategories())
		{
			return (new PermissionEntityTypeHelper($this->getEntityTypeId()))->extractCategoryFromPermissionEntityType($permissionEntityType);
		}

		return 0;
	}

	public function getProgressSteps($permissionEntityType): array
	{
		return [];
	}

	public function tryParseProgressStep($attribute, &$value): bool
	{
		return false;
	}

	public function prepareProgressStepAttribute(array $fields): string
	{
		return '';
	}
	//endregion

	//region Observable
	public function isObservable(): bool
	{
		return false;
	}

	//endregion

	public function register(string $permissionEntityType, int $entityId, ?RegisterOptions $options = null): void
	{
		$this->clearPermissionAttributesCache($entityId);

		if ($entityId <= 0)
		{
			throw new ArgumentException('Entity ID must be greater than zero.', 'entityId');
		}
		if ($options)
		{
			$this->registerByCompatibleController($permissionEntityType, $entityId, $options);
		}

		$fields = $this->getFields($entityId, $options);
		if (!is_array($fields))
		{
			return;
		}

		$ownerAttributes = $this->prepareAccessAttributes($fields);
		if (!is_array($ownerAttributes))
		{
			return;
		}
		$ownerAttributes['ENTITY_ID'] = $entityId;

		$entityType = \CCrmOwnerType::ResolveName($this->getEntityTypeId());

		/** @var $class \Bitrix\Crm\Security\AccessAttribute\EntityAccessAttributeTable */
		$class = Manager::getEntityDataClass($entityType);

		$class::upsert($ownerAttributes);
	}

	public function unregister(string $permissionEntityType, int $entityId): void
	{
		if ($entityId <= 0)
		{
			throw new ArgumentException('Entity ID must be greater than zero.', 'entityId');
		}
		$this->unregisterByCompatibleController($permissionEntityType, $entityId);

		$entityType = \CCrmOwnerType::ResolveName($this->getEntityTypeId());

		/** @var $class \Bitrix\Crm\Security\AccessAttribute\EntityAccessAttributeTable */
		$class = Manager::getEntityDataClass($entityType);
		$class::deleteByEntity($entityId);

		$this->clearPermissionAttributesCache($entityId);
	}

	protected function getFields(int $entityId, RegisterOptions $options = null): ?array
	{
		$fields = null;
		if ($options && !empty($options->getEntityFields()))
		{
			$fields = $options->getEntityFields();
		}
		if (!is_array($fields))
		{
			$fields = $this->getEntityFields($entityId);
		}

		if (!is_array($fields))
		{
			return null;
		}

		return $fields;
	}

	public function getTableName(): string
	{
		$entityTypeName = \CCrmOwnerType::ResolveName($this->getEntityTypeId());

		return Manager::getEntity($entityTypeName)->getDBTableName();
	}

	protected abstract static function getEnabledFlagOptionName(): string;

	public static function isEnabled(): bool
	{
		$name = static::getEnabledFlagOptionName();
		if ($name === '')
		{
			return false;
		}

		return (Option::get('crm', $name, 'Y') === 'Y');
	}

	public static function setEnabled(bool $enabled): void
	{
		$name = static::getEnabledFlagOptionName();
		if ($name === '')
		{
			return;
		}


		if ($enabled === self::isEnabled())
		{
			return;
		}

		if ($enabled)
		{
			Option::delete('crm', ['name' => $name]);
		}
		else
		{
			Option::set('crm', $name, 'N');
		}
	}

	public function getQueryBuilder(): QueryBuilder
	{
		return new QueryBuilder\ControllerBased($this);
	}

	/**
	 * Save attributes by compatible controller
	 *
	 * @param string $permissionEntityType
	 * @param int $entityId
	 * @param RegisterOptions|null $options
	 * @throws \Bitrix\Main\NotSupportedException
	 */
	protected function registerByCompatibleController(string $permissionEntityType, int $entityId, ?RegisterOptions $options): void
	{
		$controller = Crm\Security\Manager::getCompatibleController();
		$controller->register($permissionEntityType, $entityId, $options);
	}

	/**
	 * Remove attributes by compatible controller
	 *
	 * @param string $permissionEntityType
	 * @param int $entityId
	 * @param RegisterOptions|null $options
	 * @throws \Bitrix\Main\NotSupportedException
	 */
	protected function unregisterByCompatibleController(string $permissionEntityType, int $entityId): void
	{
		$controller = Crm\Security\Manager::getCompatibleController();
		$controller->unregister($permissionEntityType, $entityId);
	}

	private function makeObserversAttributes(array $observerIds, array $ownerAttributes): array
	{
		$observerIds = array_unique($observerIds);
		$observerAttributes = [];
		foreach ($observerIds as $obsId)
		{
			if ($obsId === (int) $ownerAttributes['USER_ID'])
			{
				continue;
			}
			$attr = $ownerAttributes;
			$attr['USER_ID'] = $obsId;
			$observerAttributes[] = $attr;
		}
		return $observerAttributes;
	}
}
