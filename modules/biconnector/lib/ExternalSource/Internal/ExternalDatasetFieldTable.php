<?php
namespace Bitrix\BIConnector\ExternalSource\Internal;

use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Fields\Validators\RegExpValidator;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;

/**
 * Class ExternalDatasetFieldsTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> DATASET_ID int mandatory
 * <li> TYPE string(50) mandatory
 * <li> NAME string(512) mandatory
 * <li> EXTERNAL_CODE string(512) optional
 * <li> VISIBLE bool mandatory default 'Y'
 * </ul>
 *
 * @package Bitrix\BIConnector\ExternalSource\Internal
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ExternalDatasetField_Query query()
 * @method static EO_ExternalDatasetField_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ExternalDatasetField_Result getById($id)
 * @method static EO_ExternalDatasetField_Result getList(array $parameters = [])
 * @method static EO_ExternalDatasetField_Entity getEntity()
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField createObject($setDefaultValues = true)
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldCollection createCollection()
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetField wakeUpObject($row)
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldCollection wakeUpCollection($rows)
 */

class ExternalDatasetFieldTable extends DataManager
{
	use DeleteByFilterTrait;

	public const FIELD_NAME_REGEXP = '/^[A-Z][A-Z0-9_]*$/';

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_biconnector_external_dataset_field';
	}

	public static function getObjectClass()
	{
		return ExternalDatasetField::class;
	}

	public static function getCollectionClass()
	{
		return ExternalDatasetFieldCollection::class;
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
					'title' => Loc::getMessage('EXTERNAL_DATASET_FIELDS_ENTITY_ID_FIELD'),
				]
			),
			new IntegerField(
				'DATASET_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('EXTERNAL_DATASET_FIELDS_ENTITY_DATASET_ID_FIELD'),
				]
			),
			(new ReferenceField(
				'DATASET',
				ExternalDatasetTable::class,
				Join::on('this.DATASET_ID', 'ref.ID')
			))
				->configureJoinType(Join::TYPE_LEFT)
			,
			new StringField(
				'TYPE',
				[
					'required' => true,
					'validation' => function()
					{
						return[
							new LengthValidator(null, 50),
							new Validator\FieldTypeValidator(),
						];
					},
					'title' => Loc::getMessage('EXTERNAL_DATASET_FIELDS_ENTITY_TYPE_FIELD'),
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
							new RegExpValidator(self::FIELD_NAME_REGEXP),
						];
					},
					'title' => Loc::getMessage('EXTERNAL_DATASET_FIELDS_ENTITY_NAME_FIELD'),
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
					'title' => Loc::getMessage('EXTERNAL_DATASET_FIELDS_ENTITY_EXTERNAL_CODE_FIELD'),
				]
			),
			new BooleanField(
				'VISIBLE',
				[
					'values' => ['N', 'Y'],
					'default' => 'Y',
					'title' => Loc::getMessage('EXTERNAL_DATASET_FIELDS_ENTITY_VISIBLE_FIELD'),
				]
			),
		];
	}

	/**
	 * Deletes all fields by DATASET_ID
	 *
	 * @param int $datasetId
	 * @return Result
	 */
	public static function deleteByDatasetId(int $datasetId): Result
	{
		$result = new Result();

		try
		{
			self::deleteByFilter(['=DATASET_ID' => $datasetId]);
		}
		catch (\Exception $exception)
		{
			$result->addError(new Error($exception->getMessage()));
		}

		return $result;
	}
}
