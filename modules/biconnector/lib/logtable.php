<?php
namespace Bitrix\BIConnector;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

/**
 * Class LogTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TIMESTAMP_X datetime mandatory
 * <li> KEY_ID int mandatory
 * <li> SOURCE_ID string(50) mandatory
 * <li> ROW_NUM int mandatory
 * <li> REAL_TIME double mandatory
 * </ul>
 *
 * @package Bitrix\BIConnector
 **/

class LogTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_biconnector_log';
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
					'title' => Loc::getMessage('LOG_ENTITY_ID_FIELD'),
				]
			),
			new DatetimeField(
				'TIMESTAMP_X',
				[
					'required' => true,
					'title' => Loc::getMessage('LOG_ENTITY_TIMESTAMP_X_FIELD'),
				]
			),
			new IntegerField(
				'KEY_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('LOG_ENTITY_KEY_ID_FIELD'),
				]
			),
			new StringField(
				'SERVICE_ID',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateServiceId'],
					'title' => Loc::getMessage('LOG_ENTITY_SOURCE_ID_FIELD'),
				]
			),
			new StringField(
				'SOURCE_ID',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateSourceId'],
					'title' => Loc::getMessage('LOG_ENTITY_SERVICE_ID_FIELD'),
				]
			),
			new StringField(
				'FIELDS',
				[
					'title' => Loc::getMessage('LOG_ENTITY_FIELDS_FIELD'),
				]
			),
			new StringField(
				'FILTERS',
				[
					'title' => Loc::getMessage('LOG_ENTITY_FILTERS_FIELD'),
				]
			),
			new IntegerField(
				'ROW_NUM',
				[
					'title' => Loc::getMessage('LOG_ENTITY_ROW_NUM_FIELD'),
				]
			),
			new FloatField(
				'REAL_TIME',
				[
					'title' => Loc::getMessage('LOG_ENTITY_REAL_TIME_FIELD'),
				]
			),
		];
	}

	/**
	 * Returns validators for SOURCE_ID field.
	 *
	 * @return array
	 */
	public static function validateSourceId(): array
	{
		return [
			new LengthValidator(null, 150),
		];
	}

	/**
	 * Returns validators for SERVICE_ID field.
	 *
	 * @return array
	 */
	public static function validateServiceId(): array
	{
		return [
			new LengthValidator(null, 150),
		];
	}

	/**
	 * Deletes records with a direct query to the database by filter.
	 *
	 * @param  array $filter Delete condition.
	 * @return void
	 * @see \Bitrix\Main\Entity\Query::buildFilterSql
	 */
	public static function deleteByFilter(array $filter)
	{
		$entity = static::getEntity();
		$sqlTableName = static::getTableName();

		$where = \Bitrix\Main\Entity\Query::buildFilterSql($entity, $filter);
		if ($where <> '')
		{
			$sql = 'DELETE FROM ' . $sqlTableName . ' WHERE ' . $where;
			$entity->getConnection()->queryExecute($sql);
		}
	}

	/**
	 * Agent deletes log records older than 30 days.
	 *
	 * @return string
	 */
	public static function cleanUpAgent()
	{
		$date = new \Bitrix\Main\Type\DateTime();
		$date->add('-30D');

		static::deleteByFilter([
			'<TIMESTAMP_X' => $date,
		]);

		return '\\Bitrix\\BIConnector\\LogTable::cleanUpAgent();';
	}
}
