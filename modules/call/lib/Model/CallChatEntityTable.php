<?php

namespace Bitrix\Call\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Call\CallChatEntity;

/**
 * Class CallTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CallChatEntity_Query query()
 * @method static EO_CallChatEntity_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_CallChatEntity_Result getById($id)
 * @method static EO_CallChatEntity_Result getList(array $parameters = [])
 * @method static EO_CallChatEntity_Entity getEntity()
 * @method static \Bitrix\Call\CallChatEntity createObject($setDefaultValues = true)
 * @method static \Bitrix\Call\Model\EO_CallChatEntity_Collection createCollection()
 * @method static \Bitrix\Call\CallChatEntity wakeUpObject($row)
 * @method static \Bitrix\Call\Model\EO_CallChatEntity_Collection wakeUpCollection($rows)
 */
class CallChatEntityTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_call_chat_entity';
	}

	public static function getObjectClass(): string
	{
		return CallChatEntity::class;
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),

			(new IntegerField('CHAT_ID'))
				->configureRequired(),

			(new IntegerField('CALL_TOKEN_VERSION'))
				->configureRequired(),
		];
	}
}