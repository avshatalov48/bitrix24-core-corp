<?php
namespace  Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\DateField;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;

/**
 * Class DialogStatTable
 * @package Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity
 */
class DialogStatTable extends DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_imopenlines_dialog_stat';
	}

	public static function getMap()
	{
		return array(
			new DateField('DATE',  array('primary' => true)),
			new IntegerField('OPEN_LINE_ID', array('primary' => true)),
			new StringField('SOURCE_ID', array('primary' => true)),
			new IntegerField('OPERATOR_ID', array('primary' => true)),
			new IntegerField('ANSWERED_QTY'),
			new IntegerField('SKIP_QTY'),
			new IntegerField('APPOINTED_QTY'),
			new IntegerField('AVERAGE_SECS_TO_ANSWER'),
			new IntegerField('POSITIVE_QTY'),
			new IntegerField('NEGATIVE_QTY'),
			new IntegerField('WITHOUT_MARK_QTY'),
			new ExpressionField('TOTAL_MARK_CNT', '(%s + %s + %s)', array('POSITIVE_QTY', 'NEGATIVE_QTY', 'WITHOUT_QTY')),
			new IntegerField('FIRST_TREATMENT_QTY'),
			new IntegerField('REPEATED_TREATMENT_QTY'),
			new ExpressionField('TOTAL_TREATMENT_CNT', '(%s + %s)', array('FIRST_TREATMENT_QTY', 'REPEATED_TREATMENT_QTY')),
		);
	}


	public static function clean()
	{
		$tableName = self::getTableName();
		global $DB;
		$DB->Query('TRUNCATE TABLE ' . $tableName . ';');
	}
}