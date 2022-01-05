<?php

namespace Bitrix\DocumentGenerator\Model;

use Bitrix\DocumentGenerator\DataProvider;
use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class FieldTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Field_Query query()
 * @method static EO_Field_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Field_Result getById($id)
 * @method static EO_Field_Result getList(array $parameters = array())
 * @method static EO_Field_Entity getEntity()
 * @method static \Bitrix\DocumentGenerator\Model\EO_Field createObject($setDefaultValues = true)
 * @method static \Bitrix\DocumentGenerator\Model\EO_Field_Collection createCollection()
 * @method static \Bitrix\DocumentGenerator\Model\EO_Field wakeUpObject($row)
 * @method static \Bitrix\DocumentGenerator\Model\EO_Field_Collection wakeUpCollection($rows)
 */
class FieldTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_documentgenerator_field';
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
			new Main\Entity\IntegerField('TEMPLATE_ID'),
			new Main\Entity\StringField('TITLE'),
			new Main\Entity\StringField('PLACEHOLDER', [
				'required' => true,
			]),
			new Main\Entity\StringField('PROVIDER', [
				'validation' => function()
				{
					return [
						function($value)
						{
							if(DataProviderManager::checkProviderName($value) || empty($value))
							{
								return true;
							}
							else
							{
								return Loc::getMessage('DOCUMENTGENERATOR_MODEL_FIELD_CLASS_VALIDATION', ['#CLASSNAME#' => $value, '#PARENT#' => DataProvider::class]);
							}
						}
					];
				}
			]),
			new Main\Entity\StringField('PROVIDER_NAME'),
			new Main\Entity\StringField('VALUE'),
			new Main\Entity\BooleanField('REQUIRED', [
				'values' => array('N', 'Y'),
				'default_value' => 'N',
			]),
			new Main\Entity\BooleanField('HIDE_ROW', [
				'values' => array('N', 'Y'),
				'default_value' => 'N',
			]),
			new Main\Entity\StringField('TYPE'),
			new Main\Entity\DatetimeField('CREATE_TIME', [
				'required' => true,
				'default_value' => function(){return new Main\Type\DateTime();},
			]),
			new Main\Entity\DatetimeField('UPDATE_TIME'),
			new Main\Entity\IntegerField('CREATED_BY'),
			new Main\Entity\IntegerField('UPDATED_BY'),
		];
	}
}