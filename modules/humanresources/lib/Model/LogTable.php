<?php

namespace Bitrix\HumanResources\Model;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Type\DateTime;

/**
 * Class LogTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Log_Query query()
 * @method static EO_Log_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Log_Result getById($id)
 * @method static EO_Log_Result getList(array $parameters = [])
 * @method static EO_Log_Entity getEntity()
 * @method static \Bitrix\HumanResources\Model\Log createObject($setDefaultValues = true)
 * @method static \Bitrix\HumanResources\Model\EO_Log_Collection createCollection()
 * @method static \Bitrix\HumanResources\Model\Log wakeUpObject($row)
 * @method static \Bitrix\HumanResources\Model\EO_Log_Collection wakeUpCollection($rows)
 */
class LogTable extends ORM\Data\DataManager
{
	public static function getObjectClass(): string
	{
		return Log::class;
	}

	public static function getTableName(): string
	{
		return 'b_hr_log';
	}

	/**
	 * @return array
	 * @throws Main\SystemException
	 */
	public static function getMap()
	{
		return [
			new IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
					'title' => 'ID',
				]
			),
			new DatetimeField(
				'DATE_CREATE',
				[
					'default' => function()
					{
						return new DateTime();
					},
					'title' => 'DATE_CREATE',
				]
			),
			new StringField(
				'MESSAGE',
				[
					'validation' => function()
					{
						return[
							new LengthValidator(null, 255),
						];
					},
					'title' => 'MESSAGE',
				]
			),
			new StringField(
				'ENTITY_TYPE',
				[
					'validation' => function()
					{
						return[
							new LengthValidator(null, 50),
						];
					},
					'title' => 'ENTITY_TYPE',
				]
			),
			new IntegerField(
				'ENTITY_ID',
				[
					'title' => 'ENTITY_ID',
				]
			),
			new IntegerField(
				'USER_ID',
				[
					'title' => 'USER_ID',
				]
			),
		];
	}
}