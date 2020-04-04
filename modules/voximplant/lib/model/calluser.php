<?php

namespace Bitrix\Voximplant\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;

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