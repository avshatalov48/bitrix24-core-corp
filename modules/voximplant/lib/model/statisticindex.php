<?php

namespace Bitrix\Voximplant\Model;

use Bitrix\Main\Entity;
/**
 * Class StatisticIndexTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_StatisticIndex_Query query()
 * @method static EO_StatisticIndex_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_StatisticIndex_Result getById($id)
 * @method static EO_StatisticIndex_Result getList(array $parameters = [])
 * @method static EO_StatisticIndex_Entity getEntity()
 * @method static \Bitrix\Voximplant\Model\EO_StatisticIndex createObject($setDefaultValues = true)
 * @method static \Bitrix\Voximplant\Model\EO_StatisticIndex_Collection createCollection()
 * @method static \Bitrix\Voximplant\Model\EO_StatisticIndex wakeUpObject($row)
 * @method static \Bitrix\Voximplant\Model\EO_StatisticIndex_Collection wakeUpCollection($rows)
 */
class StatisticIndexTable extends Base
{
	/**
	 * @inheritdoc
	 */
	public static function getTableName()
	{
		return 'b_voximplant_statistic_index';
	}

	/**
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			new Entity\IntegerField('STATISTIC_ID', array(
				'primary' => true
			)),
			new Entity\TextField('CONTENT')
		);
	}

	protected static function getMergeFields()
	{
		return array('STATISTIC_ID');
	}
}