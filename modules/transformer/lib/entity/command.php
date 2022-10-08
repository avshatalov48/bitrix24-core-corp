<?php

namespace Bitrix\Transformer\Entity;

use Bitrix\Main;
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
		return array(
			new Main\Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
			)),
			new Main\Entity\StringField('GUID', array('required' => true)),
			new Main\Entity\IntegerField('STATUS', array('required' => true)),
			new Main\Entity\StringField('COMMAND', array('required' => true)),
			new Main\Entity\StringField('MODULE', array('required' => true)),
			new Main\Entity\StringField('CALLBACK', array('required' => true)),
			new Main\Entity\StringField('PARAMS', array('required' => true)),
			new Main\Entity\StringField('FILE'),
			new Main\Entity\StringField('ERROR'),
			new Main\Entity\IntegerField('ERROR_CODE'),
			new Main\Entity\DatetimeField('UPDATE_TIME', array('default_value' => function()
			{
				$date = new Main\Type\DateTime();
				$date->setTime($date->format('H'), $date->format('i'), $date->format('s'));
				return $date;
			}
			)),
		);
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
		$cleanTime = new Date();
		$cleanTime->add("-{$days} day");

		$query = static::query();
		$filter = $query::filter()
			->logic('or')
			->whereNull('UPDATE_TIME')
			->where('UPDATE_TIME', '<', $cleanTime)
		;

		$records = static::getList([
			'select' => ['ID'],
			'order' => ['ID' => 'ASC'],
			'filter' => $filter,
			'limit' => $portion,
		]);

		$deleted = 0;

		while($record = $records->fetch())
		{
			$result = static::delete($record['ID']);
			if($result->isSuccess())
			{
				$deleted++;
			}
		}

		return $deleted;
	}

	public static function deleteOldAgent($days = 22, $portion = 100)
	{
		static::deleteOld($days, $portion);

		return "\\Bitrix\\Transformer\\Entity\\CommandTable::deleteOldAgent({$days}, {$portion});";
	}

}