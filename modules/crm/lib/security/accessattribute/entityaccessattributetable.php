<?php

namespace Bitrix\Crm\Security\AccessAttribute;

use Bitrix\Main;
use Bitrix\Main\ORM\Fields\ExpressionField;

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

	public static function addBatch(array $attrRows): void
	{
		foreach ($attrRows as &$data)
		{
			self::validateAttrsData($data);

			$isOpened = isset($data['IS_OPENED']) && $data['IS_OPENED'];
			$isAlwaysReadable = isset($data['IS_ALWAYS_READABLE']) && $data['IS_ALWAYS_READABLE'];

			$data['ENTITY_ID'] = (int)$data['ENTITY_ID'];
			$data['USER_ID'] = (int)$data['USER_ID'];
			$data['CATEGORY_ID'] = (int)$data['CATEGORY_ID'];
			$data['IS_OPENED'] = $isOpened ? 'Y' : 'N';
			$data['IS_ALWAYS_READABLE'] = $isAlwaysReadable ? 'Y' : 'N';
			$data['PROGRESS_STEP'] = isset($data['PROGRESS_STEP']) ? $data['PROGRESS_STEP'] : '';
		}

		static::addMulti($attrRows, true);
	}

	public static function upsert(array $data)
	{
		$entityId = isset($data['ENTITY_ID']) ? (int)$data['ENTITY_ID'] : 0;
		$userId = isset($data['USER_ID']) ? (int)$data['USER_ID'] : 0;
		$categoryId = isset($data['CATEGORY_ID']) ? (int)$data['CATEGORY_ID'] : 0;
		$isOpened = isset($data['IS_OPENED']) && $data['IS_OPENED'];
		$isAlwaysReadable = isset($data['IS_ALWAYS_READABLE']) && $data['IS_ALWAYS_READABLE'];
		$progressStep = isset($data['PROGRESS_STEP']) ? $data['PROGRESS_STEP'] : '';

		self::validateAttrsData($data);

		$result = static::getList([
			'filter' => [
				'=ENTITY_ID' => $entityId,
			],
			'select' => [
				new ExpressionField('CNT', 'COUNT(1)'),
				new ExpressionField('ID', 'MAX(ID)'),
			],
			'group' => [
				'ENTITY_ID'
			]
		])->fetch();

		$entityCount = (int)($result['CNT'] ?? 0);

		$updateFields = [
			'CATEGORY_ID' => $categoryId,
			'USER_ID' => $userId,
			'IS_OPENED' => $isOpened ? 'Y' : 'N',
			'IS_ALWAYS_READABLE' => $isAlwaysReadable ? 'Y' : 'N',
			'PROGRESS_STEP' => $progressStep,
			'ENTITY_ID' => $entityId,
		];

		if ($entityCount > 1)
		{
			static::deleteByEntity($entityId);
			static::add($updateFields);
		}
		elseif ($entityCount === 1)
		{
			static::update($result['ID'], $updateFields);
		}
		else
		{
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

	private static function validateAttrsData(array $data): void
	{
		if (!isset($data['ENTITY_ID']) || (int)$data['ENTITY_ID'] <= 0)
		{
			throw new Main\ArgumentException(
				'The parameter "ENTITY_ID" is required and must be greater than zero.',
				'data'
			);
		}

		if (!isset($data['USER_ID']) || (int)$data['USER_ID'] < 0)
		{
			throw new Main\ArgumentException(
				'The parameter "USER_ID" is required and must not be negative.',
				'data'
			);
		}

		if (!isset($data['CATEGORY_ID']) || (int)$data['CATEGORY_ID'] < 0)
		{
			throw new Main\ArgumentException(
				'The parameter "CATEGORY_ID" is required and must not be negative.',
				'data'
			);
		}
	}
}
