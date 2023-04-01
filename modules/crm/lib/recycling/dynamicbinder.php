<?php


namespace Bitrix\Crm\Recycling;


use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\Binding\EntityContactTable;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\NotSupportedException;

class DynamicBinder extends BaseBinder
{
	/** @var DynamicBinder[]|null */
	protected static $instances = [];

	protected $entityTypeId;

	/**
	 * @param int $entityTypeId
	 * @return DynamicBinder
	 */
	public static function getInstance(int $entityTypeId): self
	{
		if(!isset(self::$instances[$entityTypeId]))
		{
			$instance = new self();
			$instance->setEntityTypeId($entityTypeId);
			self::$instances[$entityTypeId] = $instance;
		}
		return self::$instances[$entityTypeId];
	}

	/**
	 * @param int $associatedEntityTypeID
	 * @param int $associatedEntityID
	 * @return int[]
	 * @throws NotSupportedException
	 */
	public function getBoundEntityIDs($associatedEntityTypeID, $associatedEntityID): array
	{
		$results = [];

		if($associatedEntityTypeID === \CCrmOwnerType::Company)
		{
			$factory = Container::getInstance()->getFactory($this->getEntityTypeId());
			if($factory)
			{
				$items = $factory->getItems([
					'select' => [
						Item::FIELD_NAME_ID,
					],
					'filter' => [
						Item::FIELD_NAME_COMPANY_ID => $associatedEntityID,
					],
				]);
				foreach($items as $item)
				{
					$results[] = $item->getId();
				}
			}

			return $results;
		}

		if ($associatedEntityTypeID === \CCrmOwnerType::Contact)
		{
			return EntityContactTable::getEntityIds($this->getEntityTypeId(), $associatedEntityID);
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($associatedEntityTypeID);
		throw new NotSupportedException("Entity '{$entityTypeName}' not supported in current context.");
	}

	/**
	 * @param int $associatedEntityTypeID
	 * @param int $associatedEntityID
	 * @param int[] $entityIDs
	 * @throws NotSupportedException
	 */
	public function unbindEntities($associatedEntityTypeID, $associatedEntityID, array $entityIDs): void
	{
		if(empty($entityIDs))
		{
			return;
		}

		if($associatedEntityTypeID === \CCrmOwnerType::Company)
		{
			$items = $this->getItems($entityIDs);

			foreach($items as $item)
			{
				$item->setCompanyId(0)->save();
			}

			return;
		}

		if($associatedEntityTypeID === \CCrmOwnerType::Contact)
		{
			$items = $this->getItems($entityIDs, ['withContacts' => true]);

			foreach($items as $item)
			{
				$contacts = EntityBinding::prepareEntityBindings(
					$associatedEntityTypeID,
					[$associatedEntityID]
				);

				$item->unbindContacts($contacts);
				$item->save();
			}
			return;
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($associatedEntityTypeID);
		throw new NotSupportedException("Entity '{$entityTypeName}' not supported in current context.");
	}

	/**
	 * @param int $associatedEntityTypeID
	 * @param int $associatedEntityID
	 * @param int[] $entityIDs
	 * @throws NotSupportedException
	 */
	public function bindEntities($associatedEntityTypeID, $associatedEntityID, array $entityIDs)
	{
		if(empty($entityIDs))
		{
			return;
		}

		$registrar = Container::getInstance()->getRelationRegistrar();

		if($associatedEntityTypeID === \CCrmOwnerType::Company)
		{
			$items = $this->getItems($entityIDs);
			$companyIdentifier = new ItemIdentifier(\CCrmOwnerType::Company, $associatedEntityID);

			foreach($items as $item)
			{
				$item->setCompanyId($associatedEntityID)->save();

				$registrar->registerBind($companyIdentifier, ItemIdentifier::createByItem($item));
			}

			return;
		}

		if($associatedEntityTypeID === \CCrmOwnerType::Contact)
		{
			$items = $this->getItems($entityIDs, ['withContacts' => true]);
			$contactIdentifier = new ItemIdentifier(\CCrmOwnerType::Contact, $associatedEntityID);

			$contacts = EntityBinding::prepareEntityBindings(
				$associatedEntityTypeID,
				[$associatedEntityID]
			);

			foreach($items as $item)
			{
				$item->bindContacts($contacts);
				$item->save();

				$registrar->registerBind($contactIdentifier, ItemIdentifier::createByItem($item));
			}

			return;
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($associatedEntityTypeID);
		throw new NotSupportedException("Entity '{$entityTypeName}' not supported in current context.");
	}

	/**
	 * @return int
	 */
	public function getEntityTypeId(): int
	{
		return $this->entityTypeId;
	}

	/**
	 * @param int $entityTypeId
	 */
	public function setEntityTypeId(int $entityTypeId): void
	{
		$this->entityTypeId = $entityTypeId;
	}

	/**
	 * @param int[] $ids
	 * @param array $params
	 * @return Item[]
	 */
	protected function getItems(array $ids, array $params = []): array
	{
		$select = ['*'];
		if (isset($params['withContacts']))
		{
			$select[] = Item::FIELD_NAME_CONTACTS;
		}

		return Container::getInstance()
			->getFactory($this->getEntityTypeId())
			->getItems([
				'select' => $select,
				'filter' => [
					Item::FIELD_NAME_ID => $ids,
				],
			]);
	}
}
