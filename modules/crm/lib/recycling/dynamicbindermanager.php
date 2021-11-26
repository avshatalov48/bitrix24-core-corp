<?php


namespace Bitrix\Crm\Recycling;


use Bitrix\Crm\Item;
use Bitrix\Crm\Model\Dynamic\Type;
use Bitrix\Crm\RelationIdentifier;
use Bitrix\Crm\Service\Container;

class DynamicBinderManager
{
	/** @var DynamicBinderManager|null */
	protected static $instance = null;

	/** @var Type[] */
	protected $dynamicTypes = [];

	/** @var int|null */
	protected $entityId;

	/** @var int|null */
	protected $associatedEntityTypeId;

	/**
	 * @return static
	 */
	public static function getInstance(): self
	{
		if(self::$instance === null)
		{
			$instance = new self();
			$instance->setDynamicTypes(Container::getInstance()->getDynamicTypesMap()->load()->getTypes());
			self::$instance = $instance;
		}
		return self::$instance;
	}

	/**
	 * @param int $entityId
	 * @param int $associatedEntityTypeId
	 * @return $this
	 */
	public function configure(int $entityId, int $associatedEntityTypeId): self
	{
		$this->setEntityId($entityId)->setAssociatedEntityTypeId($associatedEntityTypeId);
		return $this;
	}

	/**
	 * @return array
	 */
	public function getData(): array
	{
		$this->checkConfigure();

		$slots = [];
		foreach ($this->getDynamicTypes() as $type)
		{
			$entityTypeId = $type->getEntityTypeId();
			$dynamicIds = DynamicBinder::getInstance($entityTypeId)
				->getBoundEntityIDs($this->getAssociatedEntityTypeId(), $this->getEntityId());

			if (!empty($dynamicIds))
			{
				$slots[\CCrmOwnerType::ResolveName($entityTypeId) . '_IDS'] = $dynamicIds;
			}
		}
		return $slots;
	}

	/**
	 * @param RelationIdentifier[] $relations
	 * @param array $recyclingData
	 */
	public function buildCollection(array &$relations, array $recyclingData): void
	{
		$this->checkConfigure();

		foreach ($recyclingData as $name => $ids)
		{
			if (preg_match('/DYNAMIC_(\d*)_IDS/', $name, $matches))
			{
				foreach ($ids as $id)
				{
					$relations[] = new Relation(
						$this->getAssociatedEntityTypeId(),
						$this->getEntityId(),
						$matches[1],
						$id
					);
				}
				//unset($recyclingData[$name]);
			}
		}
	}

	public function unbindEntities(array $slots): void
	{
		$this->checkConfigure();

		foreach ($this->dynamicTypes as $type)
		{
			$entityTypeId = $type->getEntityTypeId();
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);

			if (empty($slots[$entityTypeName . '_IDS']))
			{
				continue;
			}

			DynamicBinder::getInstance($entityTypeId)->unbindEntities(
				$this->getAssociatedEntityTypeId(),
				$this->getEntityId(),
				$slots[$entityTypeName . '_IDS']
			);
		}
	}

	/**
	 * @param RelationMap $map
	 */
	public function recoverBindings(RelationMap $map): void
	{
		$this->checkConfigure();

		foreach ($this->dynamicTypes as $type)
		{
			$entityTypeId = $type->getEntityTypeId();
			$ids = $map->getDestinationEntityIDs($entityTypeId);

			if (empty($ids))
			{
				continue;
			}

			$factory = Container::getInstance()->getFactory($entityTypeId);
			if ($factory)
			{
				$dynamicIds = [];
				$items = $factory->getItems([
					'select' => [
						Item::FIELD_NAME_ID,
					],
					'filter' => [
						Item::FIELD_NAME_ID => $ids,
					],
				]);
				foreach ($items as $item)
				{
					$dynamicIds[] = $item->getId();
				}

				if(!empty($dynamicIds))
				{
					DynamicBinder::getInstance($entityTypeId)->bindEntities(
						$this->getAssociatedEntityTypeId(),
						$this->getEntityId(),
						$dynamicIds
					);
				}
			}
		}
	}

	/**
	 * @throws DynamicBinderManagerException
	 */
	protected function checkConfigure(): void
	{
		if ($this->getEntityId() === null || $this->getAssociatedEntityTypeId() === null)
		{
			throw new DynamicBinderManagerException('Need use configure() method before');
		}
	}

	/**
	 * @return array
	 */
	protected function getDynamicTypes(): array
	{
		return $this->dynamicTypes;
	}

	/**
	 * @param array $dynamicTypes
	 * @return $this
	 */
	protected function setDynamicTypes(array $dynamicTypes): self
	{
		$this->dynamicTypes = $dynamicTypes;
		return $this;
	}

	/**
	 * @return int|null
	 */
	protected function getEntityId(): ?int
	{
		return $this->entityId;
	}

	/**
	 * @param int $entityId
	 * @return $this
	 */
	protected function setEntityId(int $entityId): self
	{
		$this->entityId = $entityId;
		return $this;
	}

	/**
	 * @return int|null
	 */
	protected function getAssociatedEntityTypeId(): ?int
	{
		return $this->associatedEntityTypeId;
	}

	/**
	 * @param int $associatedEntityTypeId
	 * @return $this
	 */
	protected function setAssociatedEntityTypeId(int $associatedEntityTypeId): self
	{
		$this->associatedEntityTypeId = $associatedEntityTypeId;
		return $this;
	}
}
