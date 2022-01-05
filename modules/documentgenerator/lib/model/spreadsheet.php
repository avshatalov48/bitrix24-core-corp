<?php

namespace Bitrix\DocumentGenerator\Model;

use Bitrix\Main;

/**
 * Class SpreadsheetTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Spreadsheet_Query query()
 * @method static EO_Spreadsheet_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Spreadsheet_Result getById($id)
 * @method static EO_Spreadsheet_Result getList(array $parameters = array())
 * @method static EO_Spreadsheet_Entity getEntity()
 * @method static \Bitrix\DocumentGenerator\Model\EO_Spreadsheet createObject($setDefaultValues = true)
 * @method static \Bitrix\DocumentGenerator\Model\EO_Spreadsheet_Collection createCollection()
 * @method static \Bitrix\DocumentGenerator\Model\EO_Spreadsheet wakeUpObject($row)
 * @method static \Bitrix\DocumentGenerator\Model\EO_Spreadsheet_Collection wakeUpCollection($rows)
 */
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