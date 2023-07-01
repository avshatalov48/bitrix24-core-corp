<?php

namespace Bitrix\Crm\Entity\Index;

use Bitrix\Main\Entity;

/**
 * Class DealTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Deal_Query query()
 * @method static EO_Deal_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Deal_Result getById($id)
 * @method static EO_Deal_Result getList(array $parameters = [])
 * @method static EO_Deal_Entity getEntity()
 * @method static \Bitrix\Crm\Entity\Index\EO_Deal createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Entity\Index\EO_Deal_Collection createCollection()
 * @method static \Bitrix\Crm\Entity\Index\EO_Deal wakeUpObject($row)
 * @method static \Bitrix\Crm\Entity\Index\EO_Deal_Collection wakeUpCollection($rows)
 */
class DealTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_deal_index';
	}

	public static function getMap()
	{
		return [
			new Entity\IntegerField('DEAL_ID', ['primary' => true]),
			new Entity\StringField('SEARCH_CONTENT')
		];
	}
}
