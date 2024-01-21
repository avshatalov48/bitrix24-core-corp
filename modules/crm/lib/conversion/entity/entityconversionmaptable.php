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

		$srcTypeID = (int)($data['SRC_TYPE_ID'] ?? 0);
		$dstTypeID = (int)($data['DST_TYPE_ID'] ?? 0);

		$relationType = $data['RELATION_TYPE'] ?? RelationType::CONVERSION;

		$isChildrenListEnabled = $data['IS_CHILDREN_LIST_ENABLED'] ?? 'Y';
		if (!is_string($isChildrenListEnabled))
		{
			$isChildrenListEnabled = $isChildrenListEnabled ? 'Y' : 'N';
		}

		$lastUpdated = new DateTime();
		$data = $data['DATA'] ?? '';

		self::cleanCache();

		$sql = $sqlHelper->prepareMerge(
			'b_crm_conv_map',
			[
				'DST_TYPE_ID',
				'SRC_TYPE_ID',
			],
			[
				'SRC_TYPE_ID' => $srcTypeID,
				'DST_TYPE_ID' => $dstTypeID,
				'RELATION_TYPE' => $relationType,
				'IS_CHILDREN_LIST_ENABLED' => $isChildrenListEnabled,
				'LAST_UPDATED' => $lastUpdated,
				'DATA' => $data,
			],
			[
				'RELATION_TYPE' => $relationType,
				'IS_CHILDREN_LIST_ENABLED' => $isChildrenListEnabled,
				'LAST_UPDATED' => $lastUpdated,
				'DATA' => $data,
			]
		);

		$connection->queryExecute($sql[0]);
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
