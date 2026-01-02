<?php
namespace Bitrix\Crm\UI\Webpack\Internals;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

/**
 * Class WebPackFileLogTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_WebPackFileLog_Query query()
 * @method static EO_WebPackFileLog_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_WebPackFileLog_Result getById($id)
 * @method static EO_WebPackFileLog_Result getList(array $parameters = [])
 * @method static EO_WebPackFileLog_Entity getEntity()
 * @method static \Bitrix\Crm\UI\Webpack\Internals\EO_WebPackFileLog createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\UI\Webpack\Internals\EO_WebPackFileLog_Collection createCollection()
 * @method static \Bitrix\Crm\UI\Webpack\Internals\EO_WebPackFileLog wakeUpObject($row)
 * @method static \Bitrix\Crm\UI\Webpack\Internals\EO_WebPackFileLog_Collection wakeUpCollection($rows)
 */
class WebPackFileLogTable extends  Entity\DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_webpack_file_log';
	}

	/**
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
			new IntegerField(
				'FILE_ID',
				[
					'primary' => true,
					'title' => 'FILE_ID',
				]
			),
			new StringField(
				'ENTITY_TYPE',
				[
					'validation' => function () {
						return [
							new LengthValidator(null, 15),
						];
					},
					'title' => 'ENTITY_TYPE',
					'required' => true,
				]
			),
			new IntegerField(
				'ENTITY_ID',
				[
					'title' => 'ENTITY_ID',
					'required' => true,
				]
			),
		];
	}
}