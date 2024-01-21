<?php
namespace Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;

/**
 * Class TreatmentStatTable
 * @package Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_TreatmentByHourStat_Query query()
 * @method static EO_TreatmentByHourStat_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_TreatmentByHourStat_Result getById($id)
 * @method static EO_TreatmentByHourStat_Result getList(array $parameters = array())
 * @method static EO_TreatmentByHourStat_Entity getEntity()
 * @method static \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat createObject($setDefaultValues = true)
 * @method static \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat_Collection createCollection()
 * @method static \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat wakeUpObject($row)
 * @method static \Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\EO_TreatmentByHourStat_Collection wakeUpCollection($rows)
 */
class TreatmentByHourStatTable extends DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_imopenlines_treatment_by_hour_stat';
	}

	/**
	 * @return array
	 */
	public static function getMap(): array
	{
		return array(
			new DatetimeField('DATE', array('primary' => true)),
			new IntegerField('OPEN_LINE_ID', array('primary' => true)),
			new StringField('SOURCE_ID', array('primary' => true)),
			new IntegerField('OPERATOR_ID', array('primary' => true)),
			new IntegerField('QTY'),
		);
	}

	public static function clean(): void
	{
		\Bitrix\Main\Application::getInstance()->getConnection()->truncateTable(self::getTableName());
	}
}