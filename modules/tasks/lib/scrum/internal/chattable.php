<?php

namespace Bitrix\Tasks\Scrum\Internal;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Fields;

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