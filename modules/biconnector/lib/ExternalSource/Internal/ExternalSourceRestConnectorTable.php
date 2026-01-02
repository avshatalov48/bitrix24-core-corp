<?php
namespace Bitrix\BIConnector\ExternalSource\Internal;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\Type\DateTime;

/**
 * Class ExternalSourceRestConnectorTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ExternalSourceRestConnector_Query query()
 * @method static EO_ExternalSourceRestConnector_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ExternalSourceRestConnector_Result getById($id)
 * @method static EO_ExternalSourceRestConnector_Result getList(array $parameters = [])
 * @method static EO_ExternalSourceRestConnector_Entity getEntity()
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceRestConnector createObject($setDefaultValues = true)
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceRestConnectorCollection createCollection()
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceRestConnector wakeUpObject($row)
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceRestConnectorCollection wakeUpCollection($rows)
 */
class ExternalSourceRestConnectorTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_biconnector_external_source_rest_connector';
	}

	public static function getObjectClass()
	{
		return ExternalSourceRestConnector::class;
	}

	public static function getCollectionClass()
	{
		return ExternalSourceRestConnectorCollection::class;
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
					'title' => Loc::getMessage('EXTERNAL_SOURCE_REST_CONNECTOR_ID_FIELD'),
				]
			),
			new StringField(
				'TITLE',
				[
					'required' => true,
					'validation' => function()
					{
						return[
							new LengthValidator(null, 512),
						];
					},
					'title' => Loc::getMessage('EXTERNAL_SOURCE_REST_CONNECTOR_TITLE_FIELD'),
				]
			),
			new DatetimeField(
				'DATE_CREATE',
				[
					'required' => true,
					'title' => Loc::getMessage('EXTERNAL_SOURCE_REST_CONNECTOR_DATE_CREATE_FIELD'),
					'default_value' => fn() => new DateTime()
				]
			),
			new StringField(
				'LOGO',
				[
					'required' => true,
					'title' => Loc::getMessage('EXTERNAL_SOURCE_REST_CONNECTOR_LOGO_FIELD'),
				]
			),
			new StringField(
				'DESCRIPTION',
				[
					'title' => Loc::getMessage('EXTERNAL_SOURCE_REST_CONNECTOR_DESCRIPTION_FIELD'),
				]
			),
			new StringField(
				'APP_ID',
				[
					'required' => true,
					'validation' => function()
					{
						return[
							new LengthValidator(null, 128),
						];
					},
					'title' => Loc::getMessage('EXTERNAL_SOURCE_REST_CONNECTOR_APP_ID_FIELD'),
				]
			),
			new IntegerField(
				'SORT',
				[
					'required' => true,
					'title' => Loc::getMessage('EXTERNAL_SOURCE_REST_CONNECTOR_APP_ID_FIELD'),
					'default_value' => 100
				]
			),
			new StringField(
				'URL_CHECK',
				[
					'required' => true,
					'validation' => function()
					{
						return[
							new LengthValidator(null, 2048),
						];
					},
					'title' => Loc::getMessage('EXTERNAL_SOURCE_ENTITY_REST_URL_CHECK_FIELD'),
				]
			),
			new StringField(
				'SETTINGS',
				[
					'required' => true,
					'title' => Loc::getMessage('EXTERNAL_SOURCE_ENTITY_REST_SETTINGS'),
				]
			),
			new StringField(
				'URL_DATA',
				[
					'required' => true,
					'validation' => function()
					{
						return[
							new LengthValidator(null, 2048),
						];
					},
					'title' => Loc::getMessage('EXTERNAL_SOURCE_ENTITY_REST_URL_DATA_FIELD'),
				]
			),
			new StringField(
				'URL_TABLE_LIST',
				[
					'required' => true,
					'validation' => function()
					{
						return[
							new LengthValidator(null, 2048),
						];
					},
					'title' => Loc::getMessage('EXTERNAL_SOURCE_ENTITY_REST_URL_TABLE_LIST_FIELD'),
				]
			),
			new StringField(
				'URL_TABLE_DESCRIPTION',
				[
					'required' => true,
					'validation' => function()
					{
						return[
							new LengthValidator(null, 2048),
						];
					},
					'title' => Loc::getMessage('EXTERNAL_SOURCE_ENTITY_REST_URL_TABLE_DESCRIPTION_FIELD'),
				]
			),
		];
	}
}
