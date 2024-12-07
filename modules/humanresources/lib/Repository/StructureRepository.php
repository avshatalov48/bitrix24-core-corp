<?php

namespace Bitrix\HumanResources\Repository;

use Bitrix\HumanResources\Exception\CreationFailedException;
use Bitrix\HumanResources\Exception\DeleteFailedException;
use Bitrix\HumanResources\Exception\UpdateFailedException;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Item\Structure;
use Bitrix\HumanResources\Model\StructureTable;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\StructureType;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Contract;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

class StructureRepository implements Contract\Repository\StructureRepository
{
	private Contract\Util\CacheManager $cacheManager;

	public function __construct()
	{
		$this->cacheManager = Container::getCacheManager();
		$this->cacheManager->setTtl(86400*7);
	}

	/**
	 * @throws \Bitrix\HumanResources\Exception\CreationFailedException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function create(Structure $structure): Structure
	{
		$currentUserId = CurrentUser::get()->getId();

		$structureEntity = StructureTable::getEntity()->createObject();
		$result = $structureEntity->setName($structure->name)
			->setType($structure->type->name)
			->setCreatedBy($currentUserId)
			->setXmlId($structure->xmlId)
			->save()
		;

		if (!$result->isSuccess())
		{
			throw (new CreationFailedException())
				->setErrors($result->getErrorCollection());
		}

		$structure->id = $result->getId();
		return $structure;
	}

	/**
	 * @throws WrongStructureItemException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function list(int $limit = 50, int $offset = 0): Item\Collection\StructureCollection
	{
		$structureCollection = new Item\Collection\StructureCollection();

		if ($offset < 0 || $limit < 1)
		{
			return $structureCollection;
		}

		$models = StructureTable::query()
			->setSelect(['*'])
			->setLimit($limit)
			->setOffset($offset)
			->fetchCollection()
		;

		foreach ($models as $model)
		{
			$structureCollection->add($this->mapModelToItem($model));
		}

		return $structureCollection;
	}

	/**
	 * @param \Bitrix\HumanResources\Type\StructureType $type
	 *
	 * @return \Bitrix\HumanResources\Item\Collection\StructureCollection
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException|\Bitrix\HumanResources\Exception\WrongStructureItemException
	 */
	public function findAllByType(StructureType $type): Item\Collection\StructureCollection
	{
		$structureCollection = new Item\Collection\StructureCollection();
		$structures = StructureTable::query()
			->where('TYPE', $type->name)
			->fetchCollection();

		if (!$structures->isEmpty())
		{
			foreach ($structures as $structure)
			{
				$item = $this->mapModelToItem($structure);
				$structureCollection->add($item);
			}
		}

		return $structureCollection;
	}

	/**
	 * @param Structure $structure
	 *
	 * @return Structure
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function update(Structure $structure): Structure
	{
		if (!$structure->id)
		{
			return $structure;
		}

		$structureEntity = StructureTable::getById($structure->id)->fetchObject();

		if ($structure->name)
		{
			$structureEntity->setName($structure->name);
		}

		if ($structure->type)
		{
			$structureEntity->setType($structure->type->name);
		}

		$result = $structureEntity->save();
		if (!$result->isSuccess())
		{
			throw (new UpdateFailedException())
				->setErrors($result->getErrorCollection())
			;
		}
		$this->removeStructureCache($structure);

		return $structure;
	}

	/**
	 * @param \Bitrix\HumanResources\Model\Structure $structure
	 *
	 * @return Structure
	 */
	private function mapModelToItem(\Bitrix\HumanResources\Model\Structure $structure): Structure
	{
		return new Structure(
			name: $structure->getName(),
			type: StructureType::tryFrom($structure->getType()),
			id: $structure->getId(),
			xmlId: $structure->getXmlId(),
			createdBy: $structure->getCreatedBy(),
			createdAt: $structure->getCreatedAt(),
			updatedAt: $structure->getUpdatedAt()
		);
	}

	public function delete(Structure $structure): void
	{
		if (!$structure->id)
		{
			return;
		}

		$result = StructureTable::delete($structure->id);
		if (!$result->isSuccess())
		{
			throw (new DeleteFailedException())
				->setErrors($result->getErrorCollection())
			;
		}
		$this->removeStructureCache($structure);
	}

	private function removeStructureCache(Structure $structure): void
	{
		$nodeCacheKey = sprintf(self::STRUCTURE_XML_ID_CACHE_KEY, $structure->xmlId);
		$this->cacheManager->clean($nodeCacheKey);

		$nodeCacheKey = sprintf(self::STRUCTURE_ID_CACHE_KEY, $structure->id);
		$this->cacheManager->clean($nodeCacheKey);
	}

	/**
	 * @param string $xmlId
	 *
	 * @return Structure|null
	 */
	public function getByXmlId(string $xmlId): ?Structure
	{
		$cacheKey = sprintf(self::STRUCTURE_XML_ID_CACHE_KEY, $xmlId);

		$cacheValue = $this->cacheManager->getData($cacheKey);
		if ($cacheValue)
		{
			$cacheValue['type'] = StructureType::tryFrom($cacheValue['type']);
			$cacheValue['createdAt'] = null;
			$cacheValue['updatedAt'] = null;
			return new Structure(...$cacheValue);
		}

		try
		{
			$structure = StructureTable::query()
				->setSelect(['*'])
				->where('XML_ID', $xmlId)
				->fetchObject();

			$structureItem = $structure ? $this->mapModelToItem($structure) : null;
			if ($structureItem)
			{
				$this->cacheManager->setData($cacheKey, $structureItem);

				return $structureItem;
			}

			return null;
		}
		catch (ObjectPropertyException|ArgumentException|SystemException)
		{
			return null;
		}
	}

	/**
	 * @param int $id
	 *
	 * @return Structure|null
	 */
	public function getById(int $id): ?Structure
	{
		try
		{
			$cacheKey = sprintf(self::STRUCTURE_ID_CACHE_KEY, $id);

			$cacheValue = $this->cacheManager->getData($cacheKey);
			if ($cacheValue)
			{
				$cacheValue['type'] = StructureType::tryFrom($cacheValue['type']);
				$cacheValue['createdAt'] = null;
				$cacheValue['updatedAt'] = null;
				return new Structure(...$cacheValue);
			}

			$structure = StructureTable::getById($id)->fetchObject();

			$structureItem = $structure ? $this->mapModelToItem($structure) : null;
			if ($structureItem)
			{
				$this->cacheManager->setData($cacheKey, $structureItem);

				return $structureItem;
			}

			return null;
		}
		catch (ObjectPropertyException|ArgumentException|SystemException)
		{
			return null;
		}
	}
}