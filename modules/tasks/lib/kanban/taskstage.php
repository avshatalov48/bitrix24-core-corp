<?php

namespace Bitrix\Tasks\Kanban;

use Bitrix\Main\ORM\Data\AddStrategy\Trait\AddMergeTrait;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;

/**
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_TaskStage_Query query()
 * @method static EO_TaskStage_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_TaskStage_Result getById($id)
 * @method static EO_TaskStage_Result getList(array $parameters = [])
 * @method static EO_TaskStage_Entity getEntity()
 * @method static \Bitrix\Tasks\Kanban\EO_TaskStage createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Kanban\EO_TaskStage_Collection createCollection()
 * @method static \Bitrix\Tasks\Kanban\EO_TaskStage wakeUpObject($row)
 * @method static \Bitrix\Tasks\Kanban\EO_TaskStage_Collection wakeUpCollection($rows)
 */
class TaskStageTable extends DataManager
{
	use AddMergeTrait;
	use DeleteByFilterTrait;

	public static function getTableName(): string
	{
		return 'b_tasks_task_stage';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),

			(new IntegerField('TASK_ID'))
				->configureRequired(),

			(new IntegerField('STAGE_ID'))
				->configureRequired(),

			(new Reference('STAGE', StagesTable::getEntity(), Join::on('this.STAGE_ID', 'ref.ID')))
				->configureJoinType(Join::TYPE_LEFT),
		];
	}
}