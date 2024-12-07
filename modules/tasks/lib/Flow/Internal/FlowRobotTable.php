<?php

namespace Bitrix\Tasks\Flow\Internal;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Internal\Entity\FlowRobot;
use Bitrix\Tasks\Flow\Internal\Entity\FlowRobotCollection;

/**
 * Class FlowRobotTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_FlowRobot_Query query()
 * @method static EO_FlowRobot_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_FlowRobot_Result getById($id)
 * @method static EO_FlowRobot_Result getList(array $parameters = [])
 * @method static EO_FlowRobot_Entity getEntity()
 * @method static \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Flow\Internal\Entity\FlowRobotCollection createCollection()
 * @method static \Bitrix\Tasks\Flow\Internal\Entity\FlowRobot wakeUpObject($row)
 * @method static \Bitrix\Tasks\Flow\Internal\Entity\FlowRobotCollection wakeUpCollection($rows)
 */
class FlowRobotTable extends DataManager
{
	use DeleteByFilterTrait;

	public static function getObjectClass(): string
	{
		return FlowRobot::class;
	}

	public static function getCollectionClass(): string
	{
		return FlowRobotCollection::class;
	}

	public static function getTableName(): string
	{
		return 'b_tasks_flow_auto_created_robot';
	}

	/**
	 * @throws ArgumentTypeException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public static function getMap(): array
	{
		return array_merge(
			self::getScalarMap(),
		);
	}

	/**
	 * @throws ArgumentTypeException
	 * @throws SystemException
	 */
	public static function getScalarMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),

			(new IntegerField('FLOW_ID'))
				->configureRequired(),

			(new IntegerField('STAGE_ID'))
				->configureRequired(),

			(new IntegerField('BIZ_PROC_TEMPLATE_ID'))
				->configureRequired(),

			(new StringField('STAGE_TYPE'))
				->configureRequired()
				->addValidator(new LengthValidator(null, 255)),

			(new StringField('ROBOT'))
				->configureRequired()
				->addValidator(new LengthValidator(null, 255)),
		];
	}
}