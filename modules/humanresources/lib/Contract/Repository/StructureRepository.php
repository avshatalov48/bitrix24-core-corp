<?php

namespace Bitrix\HumanResources\Contract\Repository;

use Bitrix\HumanResources\Type\StructureType;
use Bitrix\HumanResources\Item;

interface StructureRepository
{
	public const STRUCTURE_XML_ID_CACHE_KEY = 'structure/xml_id/%s';
	public const STRUCTURE_ID_CACHE_KEY = 'structure/id/%d';

	/**
	 * @param \Bitrix\HumanResources\Item\Structure $structure
	 *
	 * @throws \Bitrix\HumanResources\Exception\CreationFailedException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 *
	 * @return \Bitrix\HumanResources\Item\Structure
	 */
	public function create(Item\Structure $structure): Item\Structure;

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function delete(Item\Structure $structure): void;


	/**
	 * @param \Bitrix\HumanResources\Item\Structure $structure
	 *
	 * @return \Bitrix\HumanResources\Item\Structure
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function update(Item\Structure $structure): Item\Structure;

	public function list(int $limit = 50, int $offset = 0): Item\Collection\StructureCollection;

	/**
	 * @param \Bitrix\HumanResources\Type\StructureType $type
	 *
	 * @return \Bitrix\HumanResources\Item\Collection\StructureCollection
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findAllByType(StructureType $type): Item\Collection\StructureCollection;

	/**
	 * @param string $xmlId
	 *
	 * @return \Bitrix\HumanResources\Item\Structure|null
	 */
	public function getByXmlId(string $xmlId): ?Item\Structure;

	/**
	 * @param int $id
	 *
	 * @return \Bitrix\HumanResources\Item\Structure|null
	 */
	public function getById(int $id): ?Item\Structure;
}