<?php
namespace Bitrix\BIConnector\ExternalSource\Internal;

use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\SecretField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class ExternalSourceSettingsTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> SOURCE_ID int mandatory
 * <li> CODE string(512) mandatory
 * <li> VALUE text optional
 * <li> NAME string(512) mandatory
 * <li> TYPE string(50) mandatory
 * </ul>
 *
 * @package Bitrix\BIConnector\ExternalSource\Internal
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ExternalSourceSettings_Query query()
 * @method static EO_ExternalSourceSettings_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ExternalSourceSettings_Result getById($id)
 * @method static EO_ExternalSourceSettings_Result getList(array $parameters = [])
 * @method static EO_ExternalSourceSettings_Entity getEntity()
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings createObject($setDefaultValues = true)
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettingsCollection createCollection()
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettings wakeUpObject($row)
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettingsCollection wakeUpCollection($rows)
 */

class ExternalSourceSettingsTable extends DataManager
{
	use DeleteByFilterTrait;

	public const SETTING_TYPE_STRING = 'STRING';

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_biconnector_external_source_settings';
	}

	public static function getObjectClass()
	{
		return ExternalSourceSettings::class;
	}

	public static function getCollectionClass()
	{
		return ExternalSourceSettingsCollection::class;
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
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
					'title' => Loc::getMessage('EXTERNAL_SOURCE_SETTINGS_ENTITY_ID_FIELD'),
				]
			),
			new IntegerField(
				'SOURCE_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('EXTERNAL_SOURCE_SETTINGS_ENTITY_SOURCE_ID_FIELD'),
				]
			),
			(new ReferenceField(
				'SOURCE',
				ExternalSourceTable::class,
				Join::on('this.SOURCE_ID', 'ref.ID')
			))
				->configureJoinType(Join::TYPE_LEFT)
			,
			new StringField(
				'CODE',
				[
					'required' => true,
					'validation' => function()
					{
						return[
							new LengthValidator(null, 512),
						];
					},
					'title' => Loc::getMessage('EXTERNAL_SOURCE_SETTINGS_ENTITY_CODE_FIELD'),
				]
			),
			new SecretField(
				'VALUE',
				[
					'title' => Loc::getMessage('EXTERNAL_SOURCE_SETTINGS_ENTITY_VALUE_FIELD'),
				]
			),
			new StringField(
				'NAME',
				[
					'required' => true,
					'validation' => function()
					{
						return[
							new LengthValidator(null, 512),
						];
					},
					'title' => Loc::getMessage('EXTERNAL_SOURCE_SETTINGS_ENTITY_NAME_FIELD'),
				]
			),
			new StringField(
				'TYPE',
				[
					'required' => true,
					'validation' => function()
					{
						return[
							new LengthValidator(null, 50),
						];
					},
					'title' => Loc::getMessage('EXTERNAL_SOURCE_SETTINGS_ENTITY_TYPE_FIELD'),
				]
			),
		];
	}
}
