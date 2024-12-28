<?php
namespace Bitrix\Sign\Internal;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\Type\DateTime;

/**
 * Class NodeSyncTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_NodeSync_Query query()
 * @method static EO_NodeSync_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_NodeSync_Result getById($id)
 * @method static EO_NodeSync_Result getList(array $parameters = [])
 * @method static EO_NodeSync_Entity getEntity()
 * @method static \Bitrix\Sign\Internal\NodeSync createObject($setDefaultValues = true)
 * @method static \Bitrix\Sign\Internal\NodeSyncCollection createCollection()
 * @method static \Bitrix\Sign\Internal\NodeSync wakeUpObject($row)
 * @method static \Bitrix\Sign\Internal\NodeSyncCollection wakeUpCollection($rows)
 */
class NodeSyncTable extends Entity\DataManager
{
	use DeleteByFilterTrait;

	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_sign_node_sync';
	}

	public static function getObjectClass()
	{
		return NodeSync::class;
	}

	public static function getCollectionClass()
	{
		return NodeSyncCollection::class;
	}

	/**
	 * Returns entity map definition.
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getMap(): array
	{
		return [
			'ID' => (new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
			,
			'DOCUMENT_ID' => (new IntegerField('DOCUMENT_ID'))
				->configureTitle('Document id')
				->configureRequired()
			,
			'NODE_ID' => (new IntegerField('NODE_ID'))
				->configureTitle('Node id')
				->configureRequired()
			,
			'IS_FLAT' => (new BooleanField('IS_FLAT'))
				->configureTitle('Flat node')
				->configureRequired()
			,
			'STATUS' => (new IntegerField('STATUS'))
				->configureTitle('Status')
				->configureRequired()
			,
			'PAGE' => (new IntegerField('PAGE'))
				->configureTitle('Configured')
				->configureRequired()
			,
			'DATE_CREATE' => (new Entity\DatetimeField('DATE_CREATE'))
				->configureTitle('Date create')
				->configureRequired()
				->configureDefaultValueNow()
			,
			'DATE_MODIFY' => (new Entity\DatetimeField('DATE_MODIFY'))
				->configureTitle('Date modify')
				->configureNullable()
			,
		];
	}

	public static function onBeforeUpdate(Event $event)
	{
		parent::onBeforeUpdate($event);

		$result = new EventResult();
		$result->modifyFields([
			'DATE_MODIFY' => new DateTime(),
		]);

		return $result;
	}
}
