<?php

namespace Bitrix\Intranet\Table;

use Bitrix\Intranet\Model\InvitationLink;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

/**
 * Class InvitationLinkTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_InvitationLink_Query query()
 * @method static EO_InvitationLink_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_InvitationLink_Result getById($id)
 * @method static EO_InvitationLink_Result getList(array $parameters = [])
 * @method static EO_InvitationLink_Entity getEntity()
 * @method static \Bitrix\Intranet\Model\InvitationLink createObject($setDefaultValues = true)
 * @method static \Bitrix\Intranet\Table\EO_InvitationLink_Collection createCollection()
 * @method static \Bitrix\Intranet\Model\InvitationLink wakeUpObject($row)
 * @method static \Bitrix\Intranet\Table\EO_InvitationLink_Collection wakeUpCollection($rows)
 */
class InvitationLinkTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_intranet_invitation_link';
	}

	public static function getObjectClass(): string
	{
		return InvitationLink::class;
	}

	public static function getMap()
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
			,
			(new StringField('ENTITY_TYPE'))
				->configureRequired()
				->configureSize(15)
			,
			(new IntegerField('ENTITY_ID'))
				->configureRequired()
			,
			(new StringField('CODE'))
				->configureRequired()
				->configureSize(64)
			,
			(new IntegerField('CREATED_BY'))
				->configureNullable()
			,
			(new DatetimeField('CREATED_AT'))
				->configureNullable()
			,
			(new DatetimeField('EXPIRED_AT'))
				->configureNullable()
			,
		];
	}
}