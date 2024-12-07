<?php

namespace Bitrix\Tasks\Internals\Task\Template;

use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\SystemException;

Loc::loadMessages(__FILE__);

/**
 * Class TaskTemplateAccessTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> GROUP_CODE string(50) mandatory
 * <li> ENTITY_ID int mandatory
 * <li> TASK_ID int mandatory
 * </ul>
 *
 * @package Bitrix\Tasks
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Access_Query query()
 * @method static EO_Access_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Access_Result getById($id)
 * @method static EO_Access_Result getList(array $parameters = [])
 * @method static EO_Access_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_Access createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_Access_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_Access wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_Access_Collection wakeUpCollection($rows)
 */
class AccessTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_tasks_task_template_access';
	}

	public static function getClass(): string
	{
		return static::class;
	}

	/**
	 * @throws ArgumentTypeException
	 * @throws SystemException
	 */
	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
				->configureTitle(Loc::getMessage('TASK_TEMPLATE_ACCESS_ENTITY_ID_FIELD')),

			(new StringField('GROUP_CODE'))
				->configureRequired()
				->addValidator(new LengthValidator(null, 50))
				->configureTitle(Loc::getMessage('TASK_TEMPLATE_ACCESS_ENTITY_GROUP_CODE_FIELD')),

			(new IntegerField('ENTITY_ID'))
				->configureRequired()
				->configureTitle(Loc::getMessage('TASK_TEMPLATE_ACCESS_ENTITY_ENTITY_ID_FIELD')),

			(new IntegerField('TASK_ID'))
				->configureRequired()
				->configureTitle(Loc::getMessage('TASK_TEMPLATE_ACCESS_ENTITY_TASK_ID_FIELD')),
		];
	}
}