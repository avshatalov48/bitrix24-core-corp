<?php

namespace Bitrix\HumanResources\Model;

use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime;
use \Bitrix\Main\Text\Emoji;

/**
 * Class StructureTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Node_Query query()
 * @method static EO_Node_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Node_Result getById($id)
 * @method static EO_Node_Result getList(array $parameters = [])
 * @method static EO_Node_Entity getEntity()
 * @method static \Bitrix\HumanResources\Model\Node createObject($setDefaultValues = true)
 * @method static \Bitrix\HumanResources\Model\NodeCollection createCollection()
 * @method static \Bitrix\HumanResources\Model\Node wakeUpObject($row)
 * @method static \Bitrix\HumanResources\Model\NodeCollection wakeUpCollection($rows)
 */
class NodeTable extends ORM\Data\DataManager
{
	public static function getObjectClass(): string
	{
		return Node::class;
	}

	public static function getCollectionClass(): string
	{
		return NodeCollection::class;
	}

	public static function getTableName(): string
	{
		return 'b_hr_structure_node';
	}

	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
				->configureTitle('ID')
			,
			(new ORM\Fields\StringField('NAME'))
				->addSaveDataModifier(fn($value) => Emoji::encode($value))
				->addFetchDataModifier(fn($value) => Emoji::decode($value))
				->configureTitle('NAME')
			,
			(new ORM\Fields\EnumField('TYPE'))
				->configureValues(NodeEntityType::values())
				->configureTitle('TYPE')
			,
			(new ORM\Fields\IntegerField('STRUCTURE_ID'))
				->configureTitle('Structure id')
			,
			(new ORM\Fields\IntegerField('PARENT_ID'))
				->configureTitle('PARENT_ID')
			,
			(new ORM\Fields\IntegerField('CREATED_BY'))
				->configureTitle('CREATED_BY')
			,
			(new ORM\Fields\DatetimeField('CREATED_AT'))
				->configureDefaultValue(new DateTime())
				->configureTitle('CREATED_AT')
			,
			(new ORM\Fields\DatetimeField('UPDATED_AT'))
				->configureDefaultValue(new DateTime())
				->configureTitle('UPDATED_AT')
			,
			(new ORM\Fields\StringField('XML_ID'))
				->configureTitle('XML_ID')
				->configureNullable()
				->configureUnique()
			,
			(new ORM\Fields\BooleanField('ACTIVE'))
				->configureTitle('ACTIVE')
				->configureStorageValues('N', 'Y')
				->configureDefaultValue('Y')
			,
			(new ORM\Fields\BooleanField('GLOBAL_ACTIVE'))
				->configureTitle('GLOBAL_ACTIVE')
				->configureStorageValues('N', 'Y')
				->configureDefaultValue('Y')
			,
			(new ORM\Fields\IntegerField('SORT'))
				->configureTitle('SORT')
				->configureDefaultValue('0')
			,
			(new ORM\Fields\StringField('DESCRIPTION'))
				->addSaveDataModifier(fn($value) => Emoji::encode($value))
				->addFetchDataModifier(fn($value) => Emoji::decode($value))
				->configureTitle('DESCRIPTION')
				->configureDefaultValue(null)
				->configureNullable()
			,
			(new ORM\Fields\StringField('COLOR_NAME'))
				->configureTitle('Color for org chart')
				->configureDefaultValue(null)
				->configureNullable()
			,
			(new ORM\Fields\Relations\OneToMany(
			'ACCESS_CODE',
				NodeBackwardAccessCodeTable::class,
				'NODE'
			))
			,
			(new ORM\Fields\Relations\OneToMany(
			'CHILD_NODES',
				NodePathTable::class,
				'CHILD_NODE'
			))
			,
			(new ORM\Fields\Relations\OneToMany(
			'PARENT_NODES',
				NodePathTable::class,
				'PARENT_NODE'
			))
			,
		];
	}

	public static function onAfterDelete(Event $event): void
	{
		$data = $event->getParameters();
		$nodeId = $data["primary"]["ID"];

		NodePathTable::deleteList(['=CHILD_ID' => $nodeId]);
		NodePathTable::deleteList(['=PARENT_ID' => $nodeId]);
		NodeBackwardAccessCodeTable::deleteList(['=NODE_ID' => $nodeId]);
		Container::getNodeMemberRepository()->removeAllMembersByNodeId($nodeId);
		NodeSettingsTable::deleteByFilter(['=NODE_ID' => $nodeId]);
	}

	public static function deleteList(array $filter)
	{
		$entity = static::getEntity();
		$connection = $entity->getConnection();

		return $connection->query(sprintf(
			'DELETE FROM %s WHERE %s',
			$connection->getSqlHelper()->quote($entity->getDbTableName()),
			Query::buildFilterSql($entity, $filter)
		));
	}
}