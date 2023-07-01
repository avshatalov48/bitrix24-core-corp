<?php

namespace Bitrix\Crm\Entity\Index;

use Bitrix\Main\Entity;

/**
 * Class LeadTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Lead_Query query()
 * @method static EO_Lead_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Lead_Result getById($id)
 * @method static EO_Lead_Result getList(array $parameters = [])
 * @method static EO_Lead_Entity getEntity()
 * @method static \Bitrix\Crm\Entity\Index\EO_Lead createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Entity\Index\EO_Lead_Collection createCollection()
 * @method static \Bitrix\Crm\Entity\Index\EO_Lead wakeUpObject($row)
 * @method static \Bitrix\Crm\Entity\Index\EO_Lead_Collection wakeUpCollection($rows)
 */
class LeadTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_lead_index';
	}

	public static function getMap()
	{
		return [
			new Entity\IntegerField('LEAD_ID', ['primary' => true]),
			new Entity\StringField('SEARCH_CONTENT')
		];
	}
}
