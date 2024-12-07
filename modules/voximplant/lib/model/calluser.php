<?php

namespace Bitrix\Voximplant\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;

/**
 * Class CallUserTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CallUser_Query query()
 * @method static EO_CallUser_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_CallUser_Result getById($id)
 * @method static EO_CallUser_Result getList(array $parameters = [])
 * @method static EO_CallUser_Entity getEntity()
 * @method static \Bitrix\Voximplant\Model\EO_CallUser createObject($setDefaultValues = true)
 * @method static \Bitrix\Voximplant\Model\EO_CallUser_Collection createCollection()
 * @method static \Bitrix\Voximplant\Model\EO_CallUser wakeUpObject($row)
 * @method static \Bitrix\Voximplant\Model\EO_CallUser_Collection wakeUpCollection($rows)
 */
class CallUserTable extends Base
{
	const ROLE_CALLER = 'caller';
	const ROLE_CALLEE = 'callee';
	const ROLE_TRANSFEREE = 'transferee';

	const STATUS_INVITING = 'inviting';
	const STATUS_CONNECTING = 'connecting';
	const STATUS_CONNECTED = 'connected';

	public static function getTableName()
	{
		return 'b_voximplant_call_user';
	}

	public static function getMap()
	{
		return [
			new Entity\StringField('CALL_ID', [
				'required' => true,
				'primary' => true
			]),
			new Entity\IntegerField('USER_ID', [
				'required' => true,
				'primary' => true
			]),
			new Entity\StringField('ROLE'),
			new Entity\StringField('STATUS'),
			new Entity\StringField('DEVICE'),
			new Entity\DatetimeField('INSERTED', [
				'default_value' => function()
				{
					return new DateTime();
				}
			])
		];
	}

	public static function getMergeFields()
	{
		return ['CALL_ID', 'USER_ID'];
	}
}