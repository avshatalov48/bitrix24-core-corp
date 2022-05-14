<?php

namespace Bitrix\Tasks\Scrum\Internal;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Fields;

/**
 * Class ChatTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Chat_Query query()
 * @method static EO_Chat_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Chat_Result getById($id)
 * @method static EO_Chat_Result getList(array $parameters = [])
 * @method static EO_Chat_Entity getEntity()
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Chat createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Chat_Collection createCollection()
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Chat wakeUpObject($row)
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_Chat_Collection wakeUpCollection($rows)
 */
class ChatTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_tasks_scrum_chat';
	}

	/**
	 * @return Fields\IntegerField[]
	 */
	public static function getMap()
	{
		$chatId = new Fields\IntegerField('CHAT_ID');
		$chatId->configurePrimary(true);

		$groupId = new Fields\IntegerField('GROUP_ID');
		$groupId->configurePrimary(true);

		return [
			$chatId,
			$groupId,
		];
	}
}