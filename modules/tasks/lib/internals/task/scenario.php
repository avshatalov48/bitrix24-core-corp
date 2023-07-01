<?php
namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

/**
 * Class ScenarioTable
 *
 * Fields:
 * <ul>
 * <li> TASK_ID int mandatory
 * <li> SCENARIO string(20) optional default 'default'
 * </ul>
 *
 * @package Bitrix\Tasks
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Scenario_Query query()
 * @method static EO_Scenario_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Scenario_Result getById($id)
 * @method static EO_Scenario_Result getList(array $parameters = [])
 * @method static EO_Scenario_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\EO_Scenario createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Scenario_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\EO_Scenario wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Scenario_Collection wakeUpCollection($rows)
 */

class ScenarioTable extends DataManager
{
	public const SCENARIO_DEFAULT = 'default';
	public const SCENARIO_CRM = 'crm';
	public const SCENARIO_MOBILE = 'mobile';

	/**
	 * Returns valid scenarios
	 * @return string[]
	 */
	public static function getValidScenarios(): array
	{
		return [
			self::SCENARIO_DEFAULT,
			self::SCENARIO_CRM,
			self::SCENARIO_MOBILE,
		];
	}

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_scenario';
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
				'TASK_ID',
				[
					'primary' => true,
				]
			),
			new StringField(
				'SCENARIO',
				[
					'required' => true,
					'default' => self::SCENARIO_DEFAULT,
					'validation' => function() {
						return [
							function(string $value) {
								if (!self::isValidScenario($value))
								{
									return 'Invalid scenario';
								}
								return true;
							}
						];
					},
				]
			),
		];
	}

	/**
	 * @param int $taskId
	 * @param array $scenarios
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	public static function insertIgnore(int $taskId, array $scenarios): void
	{
		foreach (self::filterByValidScenarios($scenarios) as $scenario)
		{
			$scenario = Application::getConnection()->getSqlHelper()->forSql($scenario);
			$sql = "
				INSERT IGNORE INTO ". self::getTableName() ."
				(`TASK_ID`, `SCENARIO`)
				VALUES
				($taskId, '$scenario')
			";
			Application::getConnection()->query($sql);
		}
	}

	public static function isValidScenario(string $scenario): bool
	{
		return in_array($scenario, self::getValidScenarios(), true);
	}

	public static function filterByValidScenarios(array $params): array
	{
		$filtered = [];
		foreach ($params as $param)
		{
			if (self::isValidScenario($param))
			{
				$filtered[] = $param;
			}
		}
		return array_unique($filtered);
	}
}