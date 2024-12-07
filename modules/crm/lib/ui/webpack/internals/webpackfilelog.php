<?php
namespace Bitrix\Crm\UI\Webpack\Internals;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

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