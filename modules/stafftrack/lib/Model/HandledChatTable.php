<?php

namespace Bitrix\StaffTrack\Model;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;

/**
 * Class HandledChatTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CHAT_ID int mandatory
 * </ul>
 *
 * @package Bitrix\Stafftrack
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_HandledChat_Query query()
 * @method static EO_HandledChat_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_HandledChat_Result getById($id)
 * @method static EO_HandledChat_Result getList(array $parameters = [])
 * @method static EO_HandledChat_Entity getEntity()
 * @method static \Bitrix\StaffTrack\Model\EO_HandledChat createObject($setDefaultValues = true)
 * @method static \Bitrix\StaffTrack\Model\EO_HandledChat_Collection createCollection()
 * @method static \Bitrix\StaffTrack\Model\EO_HandledChat wakeUpObject($row)
 * @method static \Bitrix\StaffTrack\Model\EO_HandledChat_Collection wakeUpCollection($rows)
 */
class HandledChatTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_stafftrack_handled_chat';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
					'title' => Loc::getMessage('HANDLED_CHAT_ENTITY_ID_FIELD'),
				]
			),

			new IntegerField(
				'CHAT_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('HANDLED_CHAT_ENTITY_CHAT_ID_FIELD'),
				]
			),
		];
	}
}