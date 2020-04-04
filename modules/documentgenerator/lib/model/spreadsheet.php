<?php

namespace Bitrix\DocumentGenerator\Model;

use Bitrix\Main;

class SpreadsheetTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_documentgenerator_spreadsheet';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Main\Entity\IntegerField('FIELD_ID'),
			new Main\Entity\StringField('TITLE'),
			new Main\Entity\StringField('PLACEHOLDER'),
			new Main\Entity\StringField('ENTITY_NAME'),
			new Main\Entity\StringField('VALUE'),
			new Main\Entity\IntegerField('SORT'),
		];
	}
}