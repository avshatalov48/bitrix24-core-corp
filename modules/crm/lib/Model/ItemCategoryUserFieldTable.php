<?php

namespace Bitrix\Crm\Model;

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

/**
 * Class ItemCategoryUserFieldTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ItemCategoryUserField_Query query()
 * @method static EO_ItemCategoryUserField_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ItemCategoryUserField_Result getById($id)
 * @method static EO_ItemCategoryUserField_Result getList(array $parameters = [])
 * @method static EO_ItemCategoryUserField_Entity getEntity()
 * @method static \Bitrix\Crm\Model\EO_ItemCategoryUserField createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Model\EO_ItemCategoryUserField_Collection createCollection()
 * @method static \Bitrix\Crm\Model\EO_ItemCategoryUserField wakeUpObject($row)
 * @method static \Bitrix\Crm\Model\EO_ItemCategoryUserField_Collection wakeUpCollection($rows)
 */
class ItemCategoryUserFieldTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_item_category_user_field';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new IntegerField('ENTITY_TYPE_ID'))
				->configureRequired(),
			(new IntegerField('CATEGORY_ID'))
				->configureRequired(),
			(new StringField('USER_FIELD_NAME'))
				->configureRequired()
				->configureSize(50),
		];
	}

	public static function getUserFieldsByEntityCategory(int $entityTypeId, int $categoryId): array
	{
		return static::getList([
			'select' => ['USER_FIELD_NAME'],
			'filter' => [
				'=ENTITY_TYPE_ID' => $entityTypeId,
				'=CATEGORY_ID' => $categoryId,
			],
		])->fetchCollection()->getList('USER_FIELD_NAME');
	}

	public static function deleteByCategoryId(int $categoryId): void
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		$connection->query(
			sprintf(
				'DELETE FROM %s WHERE CATEGORY_ID = %d',
				$helper->quote(static::getTableName()),
				$helper->convertToDbInteger($categoryId)
			)
		);
	}

	public static function deleteByUserFieldName(string $userFieldName, int $entityTypeId): void
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		$connection->query(
			sprintf(
				'DELETE FROM %s WHERE USER_FIELD_NAME = %s AND ENTITY_TYPE_ID = %d',
				$helper->quote(static::getTableName()),
				$helper->convertToDbString($userFieldName),
				$helper->convertToDbInteger($entityTypeId)
			)
		);
	}
}
