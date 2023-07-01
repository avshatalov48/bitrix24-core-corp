<?php

namespace Bitrix\Crm\Entity\Index;

use Bitrix\Main\Entity;

/**
 * Class CompanyTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Company_Query query()
 * @method static EO_Company_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Company_Result getById($id)
 * @method static EO_Company_Result getList(array $parameters = [])
 * @method static EO_Company_Entity getEntity()
 * @method static \Bitrix\Crm\Entity\Index\EO_Company createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Entity\Index\EO_Company_Collection createCollection()
 * @method static \Bitrix\Crm\Entity\Index\EO_Company wakeUpObject($row)
 * @method static \Bitrix\Crm\Entity\Index\EO_Company_Collection wakeUpCollection($rows)
 */
class CompanyTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_company_index';
	}

	public static function getMap()
	{
		return [
			new Entity\IntegerField('COMPANY_ID', ['primary' => true]),
			new Entity\StringField('SEARCH_CONTENT')
		];
	}
}
