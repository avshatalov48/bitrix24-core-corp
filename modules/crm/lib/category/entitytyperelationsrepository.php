<?php

namespace Bitrix\Crm\Category;

use Bitrix\Crm\Integration;
use Bitrix\Main\Loader;

/**
 * Class EntityTypeRelationsRepository
 *
 * @package Bitrix\Crm\Category
 * @internal
 */
final class EntityTypeRelationsRepository
{
	/** @var EntityTypeRelationsRepository */
	private static $instance;

	private function __construct()
	{
	}

	/**
	 * @return EntityTypeRelationsRepository
	 */
	public static function getInstance(): EntityTypeRelationsRepository
	{
		if (is_null(static::$instance))
		{
			static::$instance = new EntityTypeRelationsRepository();
		}

		return static::$instance;
	}

	/**
	 * This method is subject to change and is not covered by backwards compatibility
	 *
	 * @param int $srcEntityTypeId
	 * @param int $dstEntityTypeId
	 * @param int $categoryId
	 * @return int|null
	 *
	 * @internal
	 */
	public function getRelatedCategoryId(int $srcEntityTypeId, int $dstEntityTypeId, int $categoryId): ?int
	{
		$map = $this->getMapByEntityTypeId($srcEntityTypeId, $categoryId);

		return $map[$dstEntityTypeId] ?? null;
	}

	/**
	 * This method is subject to change and is not covered by backwards compatibility
	 *
	 * @param int $entityTypeId
	 * @param int $categoryId
	 * @return array
	 *
	 * @internal
	 */
	public function getMapByEntityTypeId(int $entityTypeId, int $categoryId): array
	{
		//@TODO
		if (
			Loader::includeModule('catalog')
			&& in_array($entityTypeId, [\CCrmOwnerType::Contact, \CCrmOwnerType::Company], true)
			&& Integration\Catalog\Contractor\CategoryRepository::isContractorCategory($entityTypeId, $categoryId)
		)
		{
			$contactCategoryId = Integration\Catalog\Contractor\CategoryRepository::getIdByEntityTypeId(
				\CCrmOwnerType::Contact
			);
			$companyCategoryId = Integration\Catalog\Contractor\CategoryRepository::getIdByEntityTypeId(
				\CCrmOwnerType::Company
			);

			return [
				\CCrmOwnerType::Contact => $contactCategoryId ?: -1,
				\CCrmOwnerType::Company => $companyCategoryId ?: -1,
			];
		}

		return [
			\CCrmOwnerType::Contact => 0,
			\CCrmOwnerType::Company => 0,
		];
	}
}
