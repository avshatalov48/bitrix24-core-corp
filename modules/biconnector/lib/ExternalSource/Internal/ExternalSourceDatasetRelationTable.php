<?php
namespace Bitrix\BIConnector\ExternalSource\Internal;

use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;

/**
 * Class ExternalSourceDatasetRelationTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> SOURCE_ID int mandatory
 * <li> DATASET_ID int mandatory
 * </ul>
 *
 * @package Bitrix\BIConnector\ExternalSource\Internal
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ExternalSourceDatasetRelation_Query query()
 * @method static EO_ExternalSourceDatasetRelation_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ExternalSourceDatasetRelation_Result getById($id)
 * @method static EO_ExternalSourceDatasetRelation_Result getList(array $parameters = [])
 * @method static EO_ExternalSourceDatasetRelation_Entity getEntity()
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation createObject($setDefaultValues = true)
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelationCollection createCollection()
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelation wakeUpObject($row)
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceDatasetRelationCollection wakeUpCollection($rows)
 */

class ExternalSourceDatasetRelationTable extends DataManager
{
	use DeleteByFilterTrait;

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_biconnector_external_source_dataset_relation';
	}

	public static function getObjectClass()
	{
		return ExternalSourceDatasetRelation::class;
	}

	public static function getCollectionClass()
	{
		return ExternalSourceDatasetRelationCollection::class;
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
					'title' => Loc::getMessage('EXTERNAL_SOURCE_DATASET_RELATION_ENTITY_ID_FIELD'),
				]
			),
			new IntegerField(
				'SOURCE_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('EXTERNAL_SOURCE_DATASET_RELATION_ENTITY_SOURCE_ID_FIELD'),
				]
			),
			(new ReferenceField(
				'SOURCE',
				ExternalSourceTable::class,
				Join::on('this.SOURCE_ID', 'ref.ID')
			))
				->configureJoinType(Join::TYPE_LEFT)
			,
			new IntegerField(
				'DATASET_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('EXTERNAL_SOURCE_DATASET_RELATION_ENTITY_DATASET_ID_FIELD'),
				]
			),
			(new ReferenceField(
				'DATASET',
				ExternalDatasetTable::class,
				Join::on('this.DATASET_ID', 'ref.ID')
			))
				->configureJoinType(Join::TYPE_LEFT)
			,
		];
	}

	/**
	 * Adds relation between source and dataset.
	 *
	 * @param int $sourceId
	 * @param int $datasetId
	 * @return Result
	 * @throws ArgumentException
	 */
	public static function addRelation(int $sourceId, int $datasetId): Result
	{
		$result = new Result();

		if ($sourceId < 0)
		{
			throw new ArgumentException('Must be greater than zero.', 'sourceId');
		}

		if ($datasetId < 0)
		{
			throw new ArgumentException('Must be greater than zero.', 'datasetId');
		}

		$addResult = self::add([
			'SOURCE_ID' => $sourceId,
			'DATASET_ID' => $datasetId,
		]);
		if (!$addResult->isSuccess())
		{
			$result->addErrors($addResult->getErrors());
		}

		return $result;
	}

	/**
	 * Deletes relation between source and dataset by dataset id
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

	/**
	 * Gets all relation by source id
	 *
	 * @param int $sourceId
	 * @return array
	 */
	public static function getBySourceId(int $sourceId): array
	{
		return self::getList([
			'filter' => [
				'=SOURCE_ID' => $sourceId,
			],
		])->fetchAll();
	}

	/**
	 * Gets all relation by dataset id
	 *
	 * @param int $sourceId
	 * @return array
	 */
	public static function getByDatasetId(int $sourceId): array
	{
		return self::getList([
			'filter' => [
				'=DATASET_ID' => $sourceId,
			],
		])->fetchAll();
	}
}
