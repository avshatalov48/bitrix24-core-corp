<?php
namespace Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\Entity\TextField;

/**
 * Class StatisticQueueTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_StatisticQueue_Query query()
 * @method static EO_StatisticQueue_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_StatisticQueue_Result getById($id)
 * @method static EO_StatisticQueue_Result getList(array $parameters = array())
 * @method static EO_StatisticQueue_Entity getEntity()
 * @method static \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue createObject($setDefaultValues = true)
 * @method static \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue_Collection createCollection()
 * @method static \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue wakeUpObject($row)
 * @method static \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_StatisticQueue_Collection wakeUpCollection($rows)
 */
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