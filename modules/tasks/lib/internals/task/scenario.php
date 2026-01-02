<?php

namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main\ORM\Data\AddStrategy\Trait\AddInsertIgnoreTrait;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Tasks\Internals\Task\Scenario\Scenario;
use Bitrix\Tasks\V2\Internal\DI\Container;

/**
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Scenario_Query query()
 * @method static EO_Scenario_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Scenario_Result getById($id)
 * @method static EO_Scenario_Result getList(array $parameters = [])
 * @method static EO_Scenario_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\Scenario\Scenario createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Scenario_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\Scenario\Scenario wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Scenario_Collection wakeUpCollection($rows)
 */

class ScenarioTable extends DataManager
{
	use AddInsertIgnoreTrait;

	public static function getObjectClass(): string
	{
		return Scenario::class;
	}

	public static function getTableName(): string
	{
		return 'b_tasks_scenario';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('TASK_ID'))
				->configurePrimary(),
			(new StringField('SCENARIO'))
				->configureRequired()
				->configureDefaultValue(\Bitrix\Tasks\V2\Internal\Entity\Task\Scenario::Default->value)
				->addValidator(static::getScenarioValidator()),
		];
	}

	private static function getScenarioValidator(): callable
	{
		return static fn(string $value): string|bool =>
			Container::getInstance()->getScenarioService()->isValid($value)
				? true
				: 'Invalid scenario';
	}
}