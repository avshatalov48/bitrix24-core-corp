<?php

namespace Bitrix\Crm\Category;

use Bitrix\Crm\Entry\AddException;
use Bitrix\Crm\Model\ItemCategoryUserFieldTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\DB\Result;
use Bitrix\Main\Entity\AddResult;
use CCrmOwnerType;

class ItemCategoryUserField
{
	// TODO: temporary list of allowed entity types
	//		 in future we'll use only $this->factory->isCategoriesSupported
	private const ALLOWED_ENTITY_TYPES = [
		CCrmOwnerType::Contact,
		CCrmOwnerType::Company
	];

	/**
	 * @var int
	 */
	private $entityTypeId;

	/**
	 * @var Factory
	 */
	private $factory;

	public function __construct(int $entityTypeId)
	{
		$this->entityTypeId = $entityTypeId;
		$this->factory = Container::getInstance()->getFactory($entityTypeId);
	}

	public static function get(int $itemCategoryUserFieldId): ?array
	{
		/** @var Result $dbResult */
		$dbResult = ItemCategoryUserFieldTable::getById($itemCategoryUserFieldId);
		$fields = $dbResult->fetch();

		return is_array($fields) ? $fields : null;
	}

	public function add(int $categoryId, string $fieldName)
	{
		if(!$this->isSupported())
		{
			return null;
		}

		$this->assertValidCategory($categoryId);

		$data = [
			'ENTITY_TYPE_ID'  => $this->entityTypeId,
			'CATEGORY_ID'     => $categoryId,
			'USER_FIELD_NAME' => $fieldName,
		];

		/** @var AddResult|null $result */
		$result = null;
		try
		{
			$result = ItemCategoryUserFieldTable::add($data);
		}
		catch(\Exception $exception)
		{
			throw new AddException($this->entityTypeId, [$exception->getMessage()], 0, '', 0, $exception);
		}

		if(!$result->isSuccess())
		{
			throw new AddException($this->entityTypeId, $result->getErrorMessages());
		}

		return $result->getId();
	}

	public function deleteByCategoryId(int $categoryId): void
	{
		if(!$this->isSupported())
		{
			return;
		}

		ItemCategoryUserFieldTable::deleteByCategoryId($categoryId);
	}

	public function deleteByName(string $fieldName): void
	{
		if(!$this->isSupported())
		{
			return;
		}

		ItemCategoryUserFieldTable::deleteByUserFieldName($fieldName, $this->entityTypeId);
	}

	public function filter(int $categoryId, array $fields): array
	{
		if(!$this->isSupported())
		{
			return $fields;
		}

		$allowedUserFields = ItemCategoryUserFieldTable::getUserFieldsByEntityCategory($this->entityTypeId, $categoryId);
		if(empty($allowedUserFields))
		{
			return [];
		}

		return array_filter(
			$fields,
			static function($userFieldName) use ($allowedUserFields) {
				return in_array($userFieldName, $allowedUserFields);
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	private function isSupported(): bool
	{
		return isset($this->factory)
			&& $this->factory->isCategoriesSupported() === true
			&& in_array($this->entityTypeId, self::ALLOWED_ENTITY_TYPES);
	}

	private function assertValidCategory(int $categoryId): void
	{
		if($categoryId > 0 && !$this->factory->isCategoryExists($categoryId))
		{
			throw new AddException(
				$this->entityTypeId,
				[sprintf("Category with ID %d is not exist.", $categoryId)]
			);
		}
	}
}
