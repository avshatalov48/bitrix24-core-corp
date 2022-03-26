<?php

namespace Bitrix\Voximplant\Model;

use Bitrix\Main\Entity\ExpressionField;

/**
 * Class UserTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_User_Query query()
 * @method static EO_User_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_User_Result getById($id)
 * @method static EO_User_Result getList(array $parameters = array())
 * @method static EO_User_Entity getEntity()
 * @method static \Bitrix\Voximplant\Model\EO_User createObject($setDefaultValues = true)
 * @method static \Bitrix\Voximplant\Model\EO_User_Collection createCollection()
 * @method static \Bitrix\Voximplant\Model\EO_User wakeUpObject($row)
 * @method static \Bitrix\Voximplant\Model\EO_User_Collection wakeUpCollection($rows)
 */
class UserTable extends \Bitrix\Main\UserTable
{
	public static function getMap()
	{
		$result = parent::getMap();

		$result[] = new ExpressionField(
			'IS_BUSY',
			'case when exists (
				select 
					\'x\' 
				from
					b_voximplant_call c
					inner join b_voximplant_call_user cu on cu.CALL_ID = c.CALL_ID
				where
					c.LAST_PING > date_sub(now(), interval 7 minute) 
					AND c.STATUS in (\'waiting\', \'connecting\', \'connected\')
					AND cu.USER_ID = %s  
					AND 
						(
							cu.STATUS = \'connected\'
							OR
							cu.INSERTED > date_sub(now(), interval 2 minute)
						)
				) then \'Y\' else \'N\' end',
			['ID'],
			['data_type' => 'boolean', 'values' => ['N', 'Y']]
		);

		return $result;
	}
}