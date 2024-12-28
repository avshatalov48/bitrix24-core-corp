<?php

namespace Bitrix\HumanResources\Model\HcmLink\Index;

use Bitrix\Main\Entity;

/**
 * Class PersonTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Person_Query query()
 * @method static EO_Person_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Person_Result getById($id)
 * @method static EO_Person_Result getList(array $parameters = [])
 * @method static EO_Person_Entity getEntity()
 * @method static \Bitrix\HumanResources\Model\HcmLink\Index\EO_Person createObject($setDefaultValues = true)
 * @method static \Bitrix\HumanResources\Model\HcmLink\Index\EO_Person_Collection createCollection()
 * @method static \Bitrix\HumanResources\Model\HcmLink\Index\EO_Person wakeUpObject($row)
 * @method static \Bitrix\HumanResources\Model\HcmLink\Index\EO_Person_Collection wakeUpCollection($rows)
 */
class PersonTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_hr_hcmlink_person_index';
	}

	public static function getMap()
	{
		return [
			new Entity\IntegerField('PERSON_ID', ['primary' => true]),
			new Entity\StringField('SEARCH_CONTENT')
		];
	}
}