<?php
namespace Bitrix\Crm\Conversion\Entity;

use Bitrix\Crm\Conversion\EntityConversionConfig;
use Bitrix\Crm\Relation\RelationType;
use Bitrix\Main;
use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\Type\DateTime;

/**
 * Class EntityConversionMapTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_EntityConversionMap_Query query()
 * @method static EO_EntityConversionMap_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_EntityConversionMap_Result getById($id)
 * @method static EO_EntityConversionMap_Result getList(array $parameters = [])
 * @method static EO_EntityConversionMap_Entity getEntity()
 * @method static \Bitrix\Crm\Conversion\Entity\EO_EntityConversionMap createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Conversion\Entity\EO_EntityConversionMap_Collection createCollection()
 * @method static \Bitrix\Crm\Conversion\Entity\EO_EntityConversionMap wakeUpObject($row)
 * @method static \Bitrix\Crm\Conversion\Entity\EO_EntityConversionMap_Collection wakeUpCollection($rows)
 */
class EntityConversionMapTable extends DataManager
{
	/** @var EO_EntityConversionMap|null */
	private static $lastDeleted;

	/**
	* @return string
	*/
	public static function getTableName()
	{
		return 'b_crm_conv_map';
	}
	/**
	* @return array
	*/
	public static function getMap()
	{
		return [
			(new Main\ORM\Fields\IntegerField('SRC_TYPE_ID'))
				->configurePrimary(),
			(new Main\ORM\Fields\IntegerField('DST_TYPE_ID'))
				->configurePrimary(),
			(new Main\ORM\Fields\EnumField('RELATION_TYPE'))
				->configureRequired()
				->configureValues(RelationType::getAll())
				->configureDefaultValue(RelationType::CONVERSION),
			(new Main\ORM\Fields\BooleanField('IS_CHILDREN_LIST_ENABLED'))
				->configureRequired()
				->configureValues('N', 'Y')
				->configureDefaultValue(true),
			(new Main\ORM\Fields\DatetimeField('LAST_UPDATED'))
				->configureRequired()
				->configureDefaultValue(static function()
					{
						return new DateTime();
					}
				),
			(new Main\ORM\Fields\StringField('DATA'))
		];
	}
	/**
	* @return void
	*/
	public static function upsert(array $data)
	{
		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$srcTypeID = isset($data['SRC_TYPE_ID']) ? (int)$data['SRC_TYPE_ID'] : 0;
		$dstTypeID = isset($data['DST_TYPE_ID']) ? (int)$data['DST_TYPE_ID'] : 0;

		$relationType = $data['RELATION_TYPE'] ?? RelationType::CONVERSION;
		$relationType = $sqlHelper->convertToDbString($relationType);

		$isChildrenListEnabled = $data['IS_CHILDREN_LIST_ENABLED'] ?? 'Y';
		if (!is_string($isChildrenListEnabled))
		{
			$isChildrenListEnabled = $isChildrenListEnabled ? 'Y' : 'N';
		}
		$isChildrenListEnabled = $sqlHelper->convertToDbString($isChildrenListEnabled);

		$dateField = new DatetimeField('D');
		$lastUpdated = $sqlHelper->convertToDb(new DateTime(), $dateField);

		$data = isset($data['DATA']) ? $sqlHelper->forSql($data['DATA']) : '';

		self::cleanCache();

		if($connection instanceof Main\DB\MysqlCommonConnection)
		{
			$connection->queryExecute(
				"INSERT INTO b_crm_conv_map(SRC_TYPE_ID, DST_TYPE_ID, RELATION_TYPE, IS_CHILDREN_LIST_ENABLED, LAST_UPDATED, DATA)
					VALUES({$srcTypeID}, {$dstTypeID}, {$relationType}, {$isChildrenListEnabled}, {$lastUpdated}, '{$data}')
					ON DUPLICATE KEY UPDATE RELATION_TYPE = {$relationType}, IS_CHILDREN_LIST_ENABLED = {$isChildrenListEnabled}, LAST_UPDATED = {$lastUpdated}, DATA = '{$data}'"
			);
		}
		elseif($connection instanceof Main\DB\MssqlConnection)
		{
			$dbResult = $connection->query(
				"SELECT 'X' FROM b_crm_conv_map WHERE SRC_TYPE_ID = {$srcTypeID} AND DST_TYPE_ID = {$dstTypeID}"
			);

			if(is_array($dbResult->fetch()))
			{
				$connection->queryExecute(
					"UPDATE b_crm_conv_map SET LAST_UPDATED = {$lastUpdated}, DATA = '{$data}'
						WHERE SRC_TYPE_ID = {$srcTypeID} AND DST_TYPE_ID = {$dstTypeID}"
				);
			}
			else
			{
				$connection->queryExecute(
					"INSERT INTO b_crm_conv_map(SRC_TYPE_ID, DST_TYPE_ID, LAST_UPDATED, DATA)
						VALUES({$srcTypeID}, {$dstTypeID}, {$lastUpdated}, '{$data}')"
				);
			}
		}
		elseif($connection instanceof Main\DB\OracleConnection)
		{
			$connection->queryExecute("MERGE INTO b_crm_conv_map USING (SELECT {$srcTypeID} SRC_TYPE_ID, {$dstTypeID} DST_TYPE_ID FROM dual)
				source ON
				(
					source.SRC_TYPE_ID = b_crm_conv_map.SRC_TYPE_ID
					AND source.DST_TYPE_ID = b_crm_conv_map.DST_TYPE_ID
				)
				WHEN MATCHED THEN
					UPDATE SET b_crm_conv_map.LAST_UPDATED = {$lastUpdated},
					b_crm_conv_map.DATA = '{$data}'
				WHEN NOT MATCHED THEN
					INSERT (SRC_TYPE_ID, DST_TYPE_ID, LAST_UPDATED, DATA)
					VALUES({$srcTypeID}, {$dstTypeID}, {$lastUpdated}, '{$data}')"
			);
		}
		else
		{
			$dbType = $connection->getType();
			throw new Main\NotSupportedException("The '{$dbType}' is not supported in current context");
		}
	}

	public static function deleteByEntityTypeId(int $entityTypeId): void
	{
		$listToDelete = static::getList([
			'filter' => [
				'=SRC_TYPE_ID' => $entityTypeId,
				'LOGIC' => 'OR',
				'=DST_TYPE_ID' => $entityTypeId,
			],
		]);

		while ($entityObject = $listToDelete->fetchObject())
		{
			$entityObject->delete();
		}
	}

	public static function onAfterAdd(Event $event)
	{
		$entityObject = $event->getParameter('object');
		if (!($entityObject instanceof EO_EntityConversionMap))
		{
			return;
		}

		if ($entityObject->getRelationType() === RelationType::CONVERSION)
		{
			static::removeConversionConfig($entityObject->getSrcTypeId());
		}
	}

	public static function onAfterUpdate(Event $event)
	{
		$entityObject = $event->getParameter('object');
		if (!($entityObject instanceof EO_EntityConversionMap))
		{
			return;
		}

		if ($entityObject->isRelationTypeChanged() && $entityObject->getRelationType() === RelationType::CONVERSION)
		{
			static::removeConversionConfig($entityObject->getSrcTypeId());
		}
	}

	public static function onBeforeDelete(Event $event)
	{
		$primary = $event->getParameter('primary');

		static::$lastDeleted = static::getByPrimary($primary)->fetchObject();
	}

	public static function onAfterDelete(Event $event)
	{
		if (static::$lastDeleted && static::$lastDeleted->getRelationType() === RelationType::CONVERSION)
		{
			static::removeConversionConfig(static::$lastDeleted->getSrcTypeId());
		}

		static::$lastDeleted = null;
	}

	private static function removeConversionConfig(int $srcEntityTypeId): void
	{
		EntityConversionConfig::removeByEntityTypeId($srcEntityTypeId);
	}
}
