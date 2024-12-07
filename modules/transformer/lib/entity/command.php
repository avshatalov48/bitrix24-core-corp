<?php

namespace Bitrix\Transformer\Entity;

use Bitrix\Main;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\Type\Date;

/**
 * Class CommandTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> GUID string(32) mandatory
 * <li> STATUS int mandatory
 * <li> COMMAND string(255) mandatory
 * <li> MODULE string(255) mandatory
 * <li> CALLBACK string(255) mandatory
 * <li> PARAMS string mandatory
 * <li> FILE string
 * <li> ERROR string(255)
 * <li> ERROR_CODE string(255)
 * <li> UPDATE_TIME datetime mandatory
 * <li> CONTROLLER_URL string(255)
 * </ul>
 *
 * @package Bitrix\Transformer
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Command_Query query()
 * @method static EO_Command_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Command_Result getById($id)
 * @method static EO_Command_Result getList(array $parameters = array())
 * @method static EO_Command_Entity getEntity()
 * @method static \Bitrix\Transformer\Entity\EO_Command createObject($setDefaultValues = true)
 * @method static \Bitrix\Transformer\Entity\EO_Command_Collection createCollection()
 * @method static \Bitrix\Transformer\Entity\EO_Command wakeUpObject($row)
 * @method static \Bitrix\Transformer\Entity\EO_Command_Collection wakeUpCollection($rows)
 */

class CommandTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_transformer_command';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
			,

			(new StringField('GUID'))
				->configureRequired()
				->configureUnique()
				->configureSize(32)
			,

			(new IntegerField('STATUS'))
				->configureRequired()
			,

			(new StringField('COMMAND'))
				->configureRequired()
				->configureSize(255)
			,

			(new TextField('MODULE'))
				->configureRequired()
			,

			(new TextField('CALLBACK'))
				->configureRequired()
			,

			(new TextField('PARAMS'))
				->configureRequired()
			,

			(new StringField('FILE'))
				->configureSize(255)
			,

			(new TextField('ERROR')),

			(new IntegerField('ERROR_CODE')),

			(new DatetimeField('UPDATE_TIME'))
				->configureDefaultValue(static function() {
					$date = new Main\Type\DateTime();
					$date->setTime($date->format('H'), $date->format('i'), $date->format('s'));
					return $date;
				})
			,

			(new DatetimeField('SEND_TIME'))
				->configureNullable()
			,

			(new StringField('CONTROLLER_URL'))
				->configureSize(255)
				->configureNullable()
			,
		];
	}

	/**
	 * Deletes old records from b_transformer_command table
	 *
	 * @param int $days Records older then $days will be cleaned
	 * @param int $portion Number of records to clean at once
	 * @return int
	 */
	public static function deleteOld(int $days = 22, $portion = 100): int
	{
		$query = self::query()
			->setSelect(['ID'])
			->where(
				self::getOldRecordsFilter($days),
			)
			->addOrder('ID')
			->setLimit($portion)
		;

		$ids = $query->fetchCollection()->getIdList();
		if (empty($ids))
		{
			return 0;
		}

		$sql = new Main\DB\SqlExpression(
			'DELETE FROM ?# WHERE ID IN (' . implode(',', $ids) . ')',
			self::getTableName(),
		);

		Main\Application::getConnection()->query((string)$sql);

		self::cleanCache();

		return count($ids);
	}

	private static function getOldRecordsFilter(int $days): Main\ORM\Query\Filter\ConditionTree
	{
		$cleanTime = new Date();
		$cleanTime->add("-{$days} day");

		return self::query()::filter()
			->logic('or')
			->whereNull('UPDATE_TIME')
			->where('UPDATE_TIME', '<', $cleanTime)
		;
	}

	public static function deleteOldAgent($days = 22, $portion = 100)
	{
		$deletedCount = static::deleteOld($days, $portion);

		$isThereMoreToClean = $deletedCount >= $portion;

		if ($isThereMoreToClean)
		{
			global $pPERIOD;

			// run this agent once again after 60 seconds
			$pPERIOD = 60;
		}

		return "\\Bitrix\\Transformer\\Entity\\CommandTable::deleteOldAgent({$days}, {$portion});";
	}
}
