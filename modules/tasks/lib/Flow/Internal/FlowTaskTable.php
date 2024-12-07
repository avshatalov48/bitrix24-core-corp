<?php

namespace Bitrix\Tasks\Flow\Internal;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Internals\TaskTable;

/**
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_FlowTask_Query query()
 * @method static EO_FlowTask_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_FlowTask_Result getById($id)
 * @method static EO_FlowTask_Result getList(array $parameters = [])
 * @method static EO_FlowTask_Entity getEntity()
 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowTask createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowTask_Collection createCollection()
 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowTask wakeUpObject($row)
 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowTask_Collection wakeUpCollection($rows)
 */
final class FlowTaskTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_tasks_flow_task';
	}

	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public static function getMap(): array
	{
		return array_merge(
			self::getScalarMap(),
			self::getReferenceMap(),
		);
	}

	private static function getScalarMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new IntegerField('FLOW_ID'))
				->configureRequired(),
			(new IntegerField('TASK_ID'))
				->configureRequired(),
		];
	}

	/**
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	private static function getReferenceMap(): array
	{
		return [
			(new OneToMany('TASK', TaskTable::class, 'FLOW_TASK'))
				->configureJoinType(Join::TYPE_INNER),
			(new Reference('FLOW', FlowTable::getEntity(), Join::on('this.FLOW_ID', 'ref.ID')))
				->configureJoinType(Join::TYPE_INNER),
		];
	}

	public static function deleteRelation(int $taskId): void
	{
		if ($taskId <= 0)
		{
			return;
		}

		$table = self::getTableName();
		$connection = Application::getConnection();
		$field = $connection->getSqlHelper()->quote('TASK_ID');
		$connection->query("delete from {$table} where {$field} = {$taskId}");
	}

	public static function insertIgnore(int $flowId, int $taskId): void
	{
		if ($flowId <= 0 || $taskId <= 0)
		{
			return;
		}

		$helper =  Application::getConnection()->getSqlHelper();
		$sql = $helper->getInsertIgnore(
			self::getTableName(),
			' (FLOW_ID, TASK_ID) ',
			" VALUES ({$flowId}, {$taskId})"
		);

		Application::getConnection()->query($sql);
	}
}
