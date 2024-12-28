<?php
namespace Bitrix\BIConnector\ExternalSource\Internal;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\EntityError;
use Bitrix\Main\Type\DateTime;

/**
 * Class ExternalSourceTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TYPE string(50) mandatory
 * <li> CODE string(512) mandatory
 * <li> TITLE string(512) mandatory
 * <li> DESCRIPTION text optional
 * <li> ACTIVE bool ('N', 'Y') optional default 'Y'
 * <li> DATE_CREATE datetime mandatory
 * <li> DATE_UPDATE datetime optional
 * <li> CREATED_BY_ID int mandatory
 * <li> UPDATED_BY_ID int optional
 * </ul>
 *
 * @package Bitrix\BIConnector\ExternalSource\Internal
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ExternalSource_Query query()
 * @method static EO_ExternalSource_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ExternalSource_Result getById($id)
 * @method static EO_ExternalSource_Result getList(array $parameters = [])
 * @method static EO_ExternalSource_Entity getEntity()
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource createObject($setDefaultValues = true)
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceCollection createCollection()
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalSource wakeUpObject($row)
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceCollection wakeUpCollection($rows)
 */

class ExternalSourceTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_biconnector_external_source';
	}

	public static function getObjectClass()
	{
		return ExternalSource::class;
	}

	public static function getCollectionClass()
	{
		return ExternalSourceCollection::class;
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
					'title' => Loc::getMessage('EXTERNAL_SOURCE_ENTITY_ID_FIELD'),
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
					'title' => Loc::getMessage('EXTERNAL_SOURCE_ENTITY_TYPE_FIELD'),
				]
			),
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
					'title' => Loc::getMessage('EXTERNAL_SOURCE_ENTITY_CODE_FIELD'),
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
					'title' => Loc::getMessage('EXTERNAL_SOURCE_ENTITY_TITLE_FIELD'),
				]
			),
			new TextField(
				'DESCRIPTION',
				[
					'title' => Loc::getMessage('EXTERNAL_SOURCE_ENTITY_DESCRIPTION_FIELD'),
				]
			),
			new BooleanField(
				'ACTIVE',
				[
					'values' => ['N', 'Y'],
					'default' => 'Y',
					'title' => Loc::getMessage('EXTERNAL_SOURCE_ENTITY_ACTIVE_FIELD'),
				]
			),
			new DatetimeField(
				'DATE_CREATE',
				[
					'required' => true,
					'title' => Loc::getMessage('EXTERNAL_SOURCE_ENTITY_DATE_CREATE_FIELD'),
					'default_value' => fn() => new DateTime()
				]
			),
			new DatetimeField(
				'DATE_UPDATE',
				[
					'title' => Loc::getMessage('EXTERNAL_SOURCE_ENTITY_DATE_UPDATE_FIELD'),
				]
			),
			new IntegerField(
				'CREATED_BY_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('EXTERNAL_SOURCE_ENTITY_CREATED_BY_ID_FIELD'),
				]
			),
			new IntegerField(
				'UPDATED_BY_ID',
				[
					'title' => Loc::getMessage('EXTERNAL_SOURCE_ENTITY_UPDATED_BY_ID_FIELD'),
				]
			),
		];
	}

	public static function onBeforeDelete(Event $event): EventResult
	{
		$result = new EventResult();

		$primary = $event->getParameter('id');
		$id = $primary['ID'];

		$relations = ExternalSourceDatasetRelationTable::getBySourceId($id);
		if ($relations)
		{
			$result->addError(new EntityError(Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_EXTERNAL_SOURCE_DELETE_RELATION_ERROR')));
		}

		return $result;
	}

	public static function onAfterDelete(Event $event): void
	{
		$primary = $event->getParameter('id');
		$id = $primary['ID'];

		ExternalSourceSettingsTable::deleteByFilter([
			'=SOURCE_ID' => $id,
		]);
	}
}
