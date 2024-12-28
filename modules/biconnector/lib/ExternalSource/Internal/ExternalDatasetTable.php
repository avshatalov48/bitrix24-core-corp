<?php
namespace Bitrix\BIConnector\ExternalSource\Internal;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\EntityError;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Fields\Validators\RegExpValidator;
use Bitrix\Main\Type\DateTime;

/**
 * Class ExternalDatasetTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TYPE string(50) mandatory
 * <li> NAME string(512) mandatory
 * <li> DESCRIPTION text optional
 * <li> EXTERNAL_CODE string(512) optional
 * <li> DATE_CREATE datetime mandatory
 * <li> DATE_UPDATE datetime optional
 * <li> CREATED_BY_ID int mandatory
 * <li> UPDATED_BY_ID int optional
 * <li> EXTERNAL_ID int optional
 * </ul>
 *
 * @package Bitrix\BIConnector\ExternalSource\Internal
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ExternalDataset_Query query()
 * @method static EO_ExternalDataset_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ExternalDataset_Result getById($id)
 * @method static EO_ExternalDataset_Result getList(array $parameters = [])
 * @method static EO_ExternalDataset_Entity getEntity()
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset createObject($setDefaultValues = true)
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetCollection createCollection()
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset wakeUpObject($row)
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetCollection wakeUpCollection($rows)
 */

class ExternalDatasetTable extends DataManager
{
	public const TABLE_NAME_REGEXP = '/^[a-z][a-z0-9_]*$/';

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_biconnector_external_dataset';
	}

	public static function getObjectClass()
	{
		return ExternalDataset::class;
	}

	public static function getCollectionClass()
	{
		return ExternalDatasetCollection::class;
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
					'title' => Loc::getMessage('EXTERNAL_DATASET_ENTITY_ID_FIELD'),
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
							new Validator\TypeValidator(),
						];
					},
					'title' => Loc::getMessage('EXTERNAL_DATASET_ENTITY_TYPE_FIELD'),
				]
			),
			new StringField(
				'NAME',
				[
					'required' => true,
					'validation' => function()
					{
						return[
							new LengthValidator(null, 250),
							new RegExpValidator(self::TABLE_NAME_REGEXP)
						];
					},
					'title' => Loc::getMessage('EXTERNAL_DATASET_ENTITY_NAME_FIELD'),
				]
			),
			new TextField(
				'DESCRIPTION',
				[
					'title' => Loc::getMessage('EXTERNAL_DATASET_ENTITY_DESCRIPTION_FIELD'),
				]
			),
			new StringField(
				'EXTERNAL_CODE',
				[
					'validation' => function()
					{
						return[
							new LengthValidator(null, 512),
						];
					},
					'title' => Loc::getMessage('EXTERNAL_DATASET_ENTITY_EXTERNAL_CODE_FIELD'),
				]
			),
			new StringField(
				'EXTERNAL_NAME',
				[
					'validation' => fn() => [new LengthValidator(null, 512)],
					'title' => Loc::getMessage('EXTERNAL_DATASET_ENTITY_EXTERNAL_NAME_FIELD'),
				]
			),
			new DatetimeField(
				'DATE_CREATE',
				[
					'required' => true,
					'title' => Loc::getMessage('EXTERNAL_DATASET_ENTITY_DATE_CREATE_FIELD'),
					'default_value' => fn() => new DateTime()
				]
			),
			new DatetimeField(
				'DATE_UPDATE',
				[
					'title' => Loc::getMessage('EXTERNAL_DATASET_ENTITY_DATE_UPDATE_FIELD'),
				]
			),
			new IntegerField(
				'CREATED_BY_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('EXTERNAL_DATASET_ENTITY_CREATED_BY_ID_FIELD'),
				]
			),
			new IntegerField(
				'UPDATED_BY_ID',
				[
					'title' => Loc::getMessage('EXTERNAL_DATASET_ENTITY_UPDATED_BY_ID_FIELD'),
				]
			),
			new IntegerField(
				'EXTERNAL_ID',
				[
					'title' => Loc::getMessage('EXTERNAL_DATASET_SETTINGS_ENTITY_EXTERNAL_ID_FIELD'),
				]
			),
		];
	}

	/**
	 * Deletes related fields, settings and relation with source
	 *
	 * @param Event $event
	 * @return EventResult
	 */
	public static function onBeforeDelete(Event $event): EventResult
	{
		$result = new EventResult();

		$primary = $event->getParameter('id');
		$id = $primary['ID'];

		$deleteFieldsResult = ExternalDatasetFieldTable::deleteByDatasetId($id);
		if (!$deleteFieldsResult->isSuccess())
		{
			$result->addError(new EntityError($deleteFieldsResult->getError()?->getMessage()));
		}

		$deleteSettingsResult = ExternalDatasetFieldFormatTable::deleteByDatasetId($id);
		if (!$deleteSettingsResult->isSuccess())
		{
			$result->addError(new EntityError($deleteSettingsResult->getError()?->getMessage()));
		}

		$deleteRelationResult = ExternalSourceDatasetRelationTable::deleteByDatasetId($id);
		if (!$deleteRelationResult->isSuccess())
		{
			$result->addError(new EntityError($deleteRelationResult->getError()?->getMessage()));
		}

		return $result;
	}
}
