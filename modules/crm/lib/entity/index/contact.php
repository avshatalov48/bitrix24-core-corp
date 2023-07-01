<?php

namespace Bitrix\Crm\Entity\Index;

use Bitrix\Main\Entity;

/**
 * Class ContactTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Contact_Query query()
 * @method static EO_Contact_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Contact_Result getById($id)
 * @method static EO_Contact_Result getList(array $parameters = [])
 * @method static EO_Contact_Entity getEntity()
 * @method static \Bitrix\Crm\Entity\Index\EO_Contact createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Entity\Index\EO_Contact_Collection createCollection()
 * @method static \Bitrix\Crm\Entity\Index\EO_Contact wakeUpObject($row)
 * @method static \Bitrix\Crm\Entity\Index\EO_Contact_Collection wakeUpCollection($rows)
 */
class ContactTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_contact_index';
	}

	public static function getMap()
	{
		return [
			new Entity\IntegerField('CONTACT_ID', ['primary' => true]),
			new Entity\StringField('SEARCH_CONTENT')
		];
	}
}
