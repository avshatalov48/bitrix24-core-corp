<?php
namespace Bitrix\Voximplant\Model;

use Bitrix\Main\Entity;

/**
 * Class IvrTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Ivr_Query query()
 * @method static EO_Ivr_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Ivr_Result getById($id)
 * @method static EO_Ivr_Result getList(array $parameters = [])
 * @method static EO_Ivr_Entity getEntity()
 * @method static \Bitrix\Voximplant\Model\EO_Ivr createObject($setDefaultValues = true)
 * @method static \Bitrix\Voximplant\Model\EO_Ivr_Collection createCollection()
 * @method static \Bitrix\Voximplant\Model\EO_Ivr wakeUpObject($row)
 * @method static \Bitrix\Voximplant\Model\EO_Ivr_Collection wakeUpCollection($rows)
 */
class IvrTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_voximplant_ivr';
	}
	
	public static function getMap()
	{
		return array(
			'ID' => new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true
			)),
			'NAME' => new Entity\StringField('NAME', array(
				'size' => '255'
			)),
			'FIRST_ITEM_ID' => new Entity\IntegerField('FIRST_ITEM_ID'),
		);
	}
}