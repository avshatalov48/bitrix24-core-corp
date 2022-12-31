<?php

namespace Bitrix\Crm\Activity\Entity;

use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\IntegerField;

class IncomingChannelTable extends \Bitrix\Main\Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_act_incoming_channel';
	}
	public static function getMap()
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new IntegerField('ACTIVITY_ID'))
				->configureRequired(),
			(new IntegerField('RESPONSIBLE_ID'))
				->configureRequired(),
			(new BooleanField('COMPLETED'))
				->configureStorageValues('N', 'Y')
				->configureRequired(),
			new ReferenceField(
				'BINDINGS',
			\Bitrix\Crm\ActivityBindingTable::class,
				[
					'=this.ACTIVITY_ID' => 'ref.ACTIVITY_ID'
				],
				['join_type' => 'INNER']
			),
		];
	}
}
