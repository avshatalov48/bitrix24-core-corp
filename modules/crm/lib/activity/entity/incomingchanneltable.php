<?php

namespace Bitrix\Crm\Activity\Entity;

use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\IntegerField;

/**
 * Class IncomingChannelTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_IncomingChannel_Query query()
 * @method static EO_IncomingChannel_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_IncomingChannel_Result getById($id)
 * @method static EO_IncomingChannel_Result getList(array $parameters = [])
 * @method static EO_IncomingChannel_Entity getEntity()
 * @method static \Bitrix\Crm\Activity\Entity\EO_IncomingChannel createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Activity\Entity\EO_IncomingChannel_Collection createCollection()
 * @method static \Bitrix\Crm\Activity\Entity\EO_IncomingChannel wakeUpObject($row)
 * @method static \Bitrix\Crm\Activity\Entity\EO_IncomingChannel_Collection wakeUpCollection($rows)
 */
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
