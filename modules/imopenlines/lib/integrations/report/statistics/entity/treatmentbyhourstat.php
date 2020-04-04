<?php
namespace Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;

/**
 * Class TreatmentStatTable
 * @package Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity
 */
class TreatmentByHourStatTable extends DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_imopenlines_treatment_by_hour_stat';
	}

	/**
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			new DatetimeField('DATE', array('primary' => true)),
			new IntegerField('OPEN_LINE_ID', array('primary' => true)),
			new StringField('SOURCE_ID', array('primary' => true)),
			new IntegerField('OPERATOR_ID', array('primary' => true)),
			new IntegerField('QTY'),
		);
	}

	public static function clean()
	{
		$tableName = self::getTableName();
		global $DB;
		$DB->Query('TRUNCATE TABLE ' . $tableName . ';');
	}
}