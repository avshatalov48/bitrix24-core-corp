<?php
namespace Bitrix\BIConnector\ExternalSource\Internal;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class ExternalSourceRestTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ExternalSourceRest_Query query()
 * @method static EO_ExternalSourceRest_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ExternalSourceRest_Result getById($id)
 * @method static EO_ExternalSourceRest_Result getList(array $parameters = [])
 * @method static EO_ExternalSourceRest_Entity getEntity()
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceRest createObject($setDefaultValues = true)
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceRestCollection createCollection()
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceRest wakeUpObject($row)
 * @method static \Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceRestCollection wakeUpCollection($rows)
 */
class ExternalSourceRestTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_biconnector_external_source_rest';
	}

	public static function getObjectClass()
	{
		return ExternalSourceRest::class;
	}

	public static function getCollectionClass()
	{
		return ExternalSourceRestCollection::class;
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
					'title' => Loc::getMessage('EXTERNAL_SOURCE_REST_ID_FIELD'),
				]
			),
			new IntegerField(
				'CONNECTOR_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('EXTERNAL_SOURCE_ENTITY_REST_CONNECTOR_ID_FIELD'),
				]
			),
			(new ReferenceField(
				'CONNECTOR',
				ExternalSourceRestConnectorTable::class,
				Join::on('this.CONNECTOR_ID', 'ref.ID')
			))
				->configureJoinType(Join::TYPE_LEFT)
			,
			new IntegerField(
				'SOURCE_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('EXTERNAL_SOURCE_ENTITY_REST_SOURCE_ID_FIELD'),
				]
			),
			(new ReferenceField(
				'SOURCE',
				ExternalSourceTable::class,
				Join::on('this.SOURCE_ID', 'ref.ID')
			))
				->configureJoinType(Join::TYPE_INNER)
			,
		];
	}
}
