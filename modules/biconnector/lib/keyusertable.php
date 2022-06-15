<?php
namespace Bitrix\BIConnector;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

Loc::loadMessages(__FILE__);

/**
 * Class KeyUserTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TIMESTAMP_X datetime mandatory
 * <li> CREATED_BY int mandatory
 * <li> KEY_ID int mandatory
 * <li> USER_ID string(50) mandatory
 * </ul>
 *
 * @package Bitrix\BIConnector
 **/

class KeyUserTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_biconnector_key_user';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
					'title' => Loc::getMessage('KEY_USER_ENTITY_ID_FIELD')
				]
			),
			new DatetimeField(
				'TIMESTAMP_X',
				[
					'required' => true,
					'title' => Loc::getMessage('KEY_USER_ENTITY_TIMESTAMP_X_FIELD')
				]
			),
			new IntegerField(
				'CREATED_BY',
				[
					'required' => true,
					'title' => Loc::getMessage('KEY_USER_ENTITY_CREATED_BY_FIELD')
				]
			),
			new IntegerField(
				'KEY_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('KEY_USER_ENTITY_KEY_ID_FIELD')
				]
			),
			new StringField(
				'USER_ID',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateUserId'],
					'title' => Loc::getMessage('KEY_USER_ENTITY_USER_ID_FIELD')
				]
			),
			new Reference(
				'KEY',
				'\Bitrix\BIConnector\KeyTable',
				['=this.KEY_ID' => 'ref.ID'],
				['join_type' => 'INNER']
			),
		];
	}

	/**
	 * Returns validators for USER_ID field.
	 *
	 * @return array
	 */
	public static function validateUserId()
	{
		return [
			new LengthValidator(null, 50),
		];
	}

	/**
	 * @param array $filter
	 */
	public static function deleteByFilter(array $filter)
	{
		$entity = static::getEntity();
		$sqlTableName = static::getTableName();

		$where = \Bitrix\Main\Entity\Query::buildFilterSql($entity, $filter);
		if ($where <> '')
		{
			$sql = "DELETE FROM {$sqlTableName} WHERE ".$where;
			$entity->getConnection()->queryExecute($sql);
		}
	}
}
