<?php
namespace Bitrix\BIConnector\ExternalSource\Internal;

use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;

/**
 * Class ExternalDatasetSettingsTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> DATASET_ID int mandatory
 * <li> TYPE string(50) mandatory
 * <li> FORMAT string(50) mandatory
 * </ul>
 *
 * @package Bitrix\BIConnector\ExternalSource\Internal
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ExternalDatasetFieldFormat_Query query()
 * @method static EO_ExternalDatasetFieldFormat_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ExternalDatasetFieldFormat_Result getById($id)
 * @method static EO_ExternalDatasetFieldFormat_Result getList(array $parameters = [])
 * @method static EO_ExternalDatasetFieldFormat_Entity getEntity()
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat createObject($setDefaultValues = true)
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormatCollection createCollection()
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormat wakeUpObject($row)
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldFormatCollection wakeUpCollection($rows)
 */

class ExternalDatasetFieldFormatTable extends DataManager
{
	use DeleteByFilterTrait;

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_biconnector_external_dataset_field_format';
	}

	public static function getObjectClass()
	{
		return ExternalDatasetFieldFormat::class;
	}

	public static function getCollectionClass()
	{
		return ExternalDatasetFieldFormatCollection::class;
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
					'title' => Loc::getMessage('EXTERNAL_DATASET_SETTINGS_ENTITY_ID_FIELD'),
				]
			),
			new IntegerField(
				'DATASET_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('EXTERNAL_DATASET_SETTINGS_ENTITY_DATASET_ID_FIELD'),
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
					'title' => Loc::getMessage('EXTERNAL_DATASET_SETTINGS_ENTITY_TYPE_FIELD'),
				]
			),
			new StringField(
				'FORMAT',
				[
					'required' => true,
					'validation' => function()
					{
						return[
							new LengthValidator(null, 50),
						];
					},
					'title' => Loc::getMessage('EXTERNAL_DATASET_SETTINGS_ENTITY_FORMAT_FIELD'),
				]
			),
		];
	}

	/**
	 * Deletes all settings by DATASET_ID
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
