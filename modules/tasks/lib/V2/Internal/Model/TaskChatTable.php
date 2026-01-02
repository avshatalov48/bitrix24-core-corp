<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\IntegerField;

/**
 * Class TaskChatTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_TaskChat_Query query()
 * @method static EO_TaskChat_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_TaskChat_Result getById($id)
 * @method static EO_TaskChat_Result getList(array $parameters = [])
 * @method static EO_TaskChat_Entity getEntity()
 * @method static \Bitrix\Tasks\V2\Internal\Model\EO_TaskChat createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\V2\Internal\Model\EO_TaskChat_Collection createCollection()
 * @method static \Bitrix\Tasks\V2\Internal\Model\EO_TaskChat wakeUpObject($row)
 * @method static \Bitrix\Tasks\V2\Internal\Model\EO_TaskChat_Collection wakeUpCollection($rows)
 */
class TaskChatTable extends DataManager
{
	use DeleteByFilterTrait;

	public static function getTableName(): string
	{
		return 'b_tasks_task_chat';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('TASK_ID'))
				->configurePrimary(),
			(new IntegerField('CHAT_ID'))
				->configureRequired(),
		];
	}
}
