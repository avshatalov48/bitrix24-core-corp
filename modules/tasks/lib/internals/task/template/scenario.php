<?php
namespace Bitrix\Tasks\Internals\Task\Template;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

/**
 * Class ScenarioTable
 *
 * Fields:
 * <ul>
 * <li> TEMPLATE_ID int mandatory
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
 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_Scenario createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_Scenario_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_Scenario wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_Scenario_Collection wakeUpCollection($rows)
 */

class ScenarioTable extends DataManager
{
	public const SCENARIO_DEFAULT = 'default';
	public const SCENARIO_CRM = 'crm';

	/**
	 * Returns valid scenarios
	 * @return string[]
	 */
	public static function getValidScenarios(): array
	{
		return [
			self::SCENARIO_DEFAULT,
			self::SCENARIO_CRM,
		];
	}

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_template_scenario';
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
				'TEMPLATE_ID',
				[
					'primary' => true,
				]
			),
			new StringField(
				'SCENARIO',
				[
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
	 * @param int $templateId
	 * @param string $scenario
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	public static function insertIgnore(int $templateId, string $scenario): void
	{
		if (!self::isValidScenario($scenario))
		{
			throw new ArgumentException('Invalid scenario');
		}
		$helper =  Application::getConnection()->getSqlHelper();
		$scenario = $helper->forSql($scenario);
		$sql = $helper->getInsertIgnore(
			self::getTableName(),
			' (TEMPLATE_ID, SCENARIO) ',
			" VALUES ({$templateId}, '$scenario')"
		);

		Application::getConnection()->query($sql);
	}

	/**
	 * @param string $scenario
	 * @return bool
	 */
	public static function isValidScenario(string $scenario): bool
	{
		return in_array($scenario, self::getValidScenarios(), true);
	}
}