<?php

namespace Bitrix\Recyclebin\Internals\Models;

use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;

/**
 * Class RecyclebinTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Recyclebin_Query query()
 * @method static EO_Recyclebin_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Recyclebin_Result getById($id)
 * @method static EO_Recyclebin_Result getList(array $parameters = array())
 * @method static EO_Recyclebin_Entity getEntity()
 * @method static \Bitrix\Recyclebin\Internals\Models\EO_Recyclebin createObject($setDefaultValues = true)
 * @method static \Bitrix\Recyclebin\Internals\Models\EO_Recyclebin_Collection createCollection()
 * @method static \Bitrix\Recyclebin\Internals\Models\EO_Recyclebin wakeUpObject($row)
 * @method static \Bitrix\Recyclebin\Internals\Models\EO_Recyclebin_Collection wakeUpCollection($rows)
 */
class RecyclebinTable extends Entity\DataManager
{

	public static function getTableName()
	{
		return 'b_recyclebin';
	}

	public static function getMap()
	{
		return [
			new Entity\IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
				]
			),
			new Entity\StringField(
				'NAME',
				[
					'save_data_modification' => ['\Bitrix\Main\Text\Emoji', 'getSaveModificator'],
					'fetch_data_modification' => ['\Bitrix\Main\Text\Emoji', 'getFetchModificator'],
				]
			),
			new Entity\StringField('SITE_ID'),
			new Entity\StringField('MODULE_ID'),
			new Entity\StringField('ENTITY_ID'),
			new Entity\StringField('ENTITY_TYPE'),
			new Entity\DatetimeField('TIMESTAMP'),
			new Entity\IntegerField('USER_ID'),
			new Entity\ReferenceField('USER', '\Bitrix\Main\User', ['=this.USER_ID' => 'ref.ID']),
		];
	}

	public static function onBeforeAdd(Entity\Event $event)
	{
		$result = new Entity\EventResult;
		$result->modifyFields(
			array(
				'TIMESTAMP' => DateTime::createFromTimestamp(time())
			)
		);

		return $result;
	}
}