<?php

namespace Bitrix\HumanResources\Repository;

use Bitrix\HumanResources\Exception\CreationFailedException;
use Bitrix\HumanResources\Exception\DeleteFailedException;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Model\NodeSettingsTable;
use Bitrix\HumanResources\Type\NodeSettingsType;
use Bitrix\Main\Application;

class NodeSettingsRepository
{
	/**
	 * @param array $nodeSettings
	 * @return Item\NodeSettings
	 */
	protected function convertModelArrayToItem(array $nodeSettings): Item\NodeSettings
	{
		return new Item\NodeSettings(
			nodeId: $nodeSettings['NODE_ID'],
			settingsType: NodeSettingsType::tryFrom($nodeSettings['SETTINGS_TYPE']),
			settingsValue: $nodeSettings['SETTINGS_VALUE'],
			id: $nodeSettings['ID'],
			createdAt: $nodeSettings['CREATED_AT'],
			updatedAt: $nodeSettings['UPDATED_AT'],
		);
	}

	/**
	 * @param int $nodeId
	 * @param NodeSettingsType[] $settingsTypes
	 * @return Item\Collection\NodeSettingsCollection
	 * @throws \Bitrix\HumanResources\Exception\WrongStructureItemException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getByNodeAndTypes(int $nodeId, array $settingsTypes = []): Item\Collection\NodeSettingsCollection
	{
		$query = NodeSettingsTable::query()
			->setSelect(['*'])
			->where('NODE_ID', $nodeId)
		;

		if (!empty($settingsTypes))
		{
			$query->whereIn('SETTINGS_TYPE', array_map(
				static fn(NodeSettingsType $type) => $type->value,
				$settingsTypes
			));
		}

		$entityCollection = $query->fetchAll();
		$itemsCollection = new Item\Collection\NodeSettingsCollection();

		foreach ($entityCollection as $entity)
		{
			$nodeSettings = $this->convertModelArrayToItem($entity);
			$itemsCollection->add($nodeSettings);
		}

		return $itemsCollection;
	}

	/**
	 * @param Item\NodeSettings $nodeSettings
	 * @return Item\NodeSettings
	 * @throws CreationFailedException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function create(Item\NodeSettings $nodeSettings): Item\NodeSettings
	{
		$nodeSettingsEntity = NodeSettingsTable::getEntity()->createObject();

		$nodeSettingsCreateResult = $nodeSettingsEntity
			->setNodeId($nodeSettings->nodeId)
			->setSettingsType($nodeSettings->settingsType->value)
			->setSettingsValue($nodeSettings->settingsValue)
			->save()
		;

		if (!$nodeSettingsCreateResult->isSuccess())
		{
			throw (new CreationFailedException())
				->setErrors($nodeSettingsCreateResult->getErrorCollection());
		}

		$nodeSettings->id = $nodeSettingsCreateResult->getId();

		return $nodeSettings;
	}

	/**
	 * @param Item\Collection\NodeSettingsCollection $nodeSettingsCollection
	 * @return Item\Collection\NodeSettingsCollection
	 * @throws CreationFailedException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function createByCollection(
		Item\Collection\NodeSettingsCollection $nodeSettingsCollection,
	): Item\Collection\NodeSettingsCollection
	{
		$connection = Application::getConnection();
		try
		{
			$connection->startTransaction();
			foreach ($nodeSettingsCollection as $item)
			{
				$this->create($item);
			}
			$connection->commitTransaction();
		}
		catch (\Exception $exception)
		{
			$connection->rollbackTransaction();
			throw $exception;
		}

		return $nodeSettingsCollection;
	}

	/**
	 * @param int $nodeId
	 * @param NodeSettingsType $settingsType
	 * @return void
	 * @throws DeleteFailedException
	 */
	public function removeByTypeAndNodeId(int $nodeId, NodeSettingsType $settingsType): void
	{
		try
		{
			NodeSettingsTable::deleteByFilter([
				'=NODE_ID' => $nodeId,
				'@SETTINGS_TYPE' => $settingsType->value,
			]);
		}
		catch (\Exception)
		{
			throw new DeleteFailedException();
		}
	}
}
