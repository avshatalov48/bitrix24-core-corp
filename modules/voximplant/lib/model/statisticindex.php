<?php

namespace Bitrix\Voximplant\Model;

use Bitrix\Main\Entity;
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