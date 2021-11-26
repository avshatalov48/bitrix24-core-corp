<?php

namespace Bitrix\Crm\Security\AccessAttribute;

use Bitrix\Main;

abstract class EntityAccessAttributeTable extends Main\ORM\Data\DataManager
{
	/**
	 * @inheritdoc
	 */
	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', ['autocomplete' => true, 'primary' => true]),
			new Main\Entity\IntegerField('ENTITY_ID', ['required' => true]),
			new Main\Entity\IntegerField('CATEGORY_ID', ['required' => true]),
			new Main\Entity\IntegerField('USER_ID', ['required' => true]),
			new Main\Entity\StringField('IS_OPENED', ['size' => 1, 'required' => true]),
			new Main\Entity\StringField('IS_ALWAYS_READABLE', ['size' => 1, 'required' => true]),
			new Main\Entity\StringField('PROGRESS_STEP', ['size' => 50]),
		];
	}

	public static function upsert(array $data)
	{
		$entityId = isset($data['ENTITY_ID']) ? (int)$data['ENTITY_ID'] : 0;
		$userId = isset($data['USER_ID']) ? (int)$data['USER_ID'] : 0;
		$categoryId = isset($data['CATEGORY_ID']) ? (int)$data['CATEGORY_ID'] : 0;
		$isOpened = isset($data['IS_OPENED']) && $data['IS_OPENED'];
		$isAlwaysReadable = isset($data['IS_ALWAYS_READABLE']) && $data['IS_ALWAYS_READABLE'];
		$progressStep = isset($data['PROGRESS_STEP']) ? $data['PROGRESS_STEP'] : '';

		if ($entityId <= 0)
		{
			throw new Main\ArgumentException('The parameter "ENTITY_ID" must be greater than zero.', 'data');
		}

		if ($userId < 0)
		{
			throw new Main\ArgumentException('The parameter "USER_ID" must not be negative.', 'data');
		}

		if ($categoryId < 0)
		{
			throw new Main\ArgumentException('The parameter "CATEGORY_ID" must not be negative.', 'data');
		}

		$existedEntity = static::getList([
			'filter' => [
				'=ENTITY_ID' => $entityId,
			],
			'select' => [
				'ID'
			]
		])->fetch();

		$updateFields = [
			'CATEGORY_ID' => $categoryId,
			'USER_ID' => $userId,
			'IS_OPENED' => $isOpened ? 'Y' : 'N',
			'IS_ALWAYS_READABLE' => $isAlwaysReadable ? 'Y' : 'N',
			'PROGRESS_STEP' => $progressStep,
		];
		if ($existedEntity)
		{
			static::update($existedEntity['ID'], $updateFields);
		}
		else
		{
			$updateFields['ENTITY_ID'] = $entityId;
			static::add($updateFields);
		}
	}

	public static function deleteByEntity(int $entityID)
	{
		if ($entityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'entityID');
		}
		$tableName = static::getTableName();

		$connection = Main\Application::getConnection();
		$connection->queryExecute(
			"DELETE FROM {$tableName} WHERE ENTITY_ID = {$entityID}"
		);
	}
}