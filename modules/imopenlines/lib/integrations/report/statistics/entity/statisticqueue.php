<?php
namespace Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\Entity\TextField;

class StatisticQueueTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_imopenlines_statistic_queue';
	}

	public static function getMap()
	{
		return array(
			new IntegerField('ID', array('primary' => true, 'autocomplete' => true)),
			new IntegerField('SESSION_ID'),
			new StringField('STATISTIC_KEY'),
			new DatetimeField('DATE_QUEUE'),
			new TextField('PARAMS', array('serialized' => true)),
		);
	}

	public static function clean()
	{
		$tableName = self::getTableName();
		global $DB;
		$DB->Query('TRUNCATE TABLE ' . $tableName . ';');
	}
}