<?php

namespace Bitrix\Crm\Timeline\Entity;

use Bitrix\Crm\Timeline\Entity\Object\Timeline;
use Bitrix\Main;
use Bitrix\Main\Entity;

/**
 * Class TimelineTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Timeline_Query query()
 * @method static EO_Timeline_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Timeline_Result getById($id)
 * @method static EO_Timeline_Result getList(array $parameters = [])
 * @method static EO_Timeline_Entity getEntity()
 * @method static \Bitrix\Crm\Timeline\Entity\Object\Timeline createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Timeline\Entity\EO_Timeline_Collection createCollection()
 * @method static \Bitrix\Crm\Timeline\Entity\Object\Timeline wakeUpObject($row)
 * @method static \Bitrix\Crm\Timeline\Entity\EO_Timeline_Collection wakeUpCollection($rows)
 */
class TimelineTable  extends Entity\DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_timeline';
	}

	/**
	 * @return array
	 */
	public static function getMap()
	{
		return [
			(new Main\ORM\Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new Main\ORM\Fields\IntegerField('TYPE_ID'))
				->configureRequired(),
			(new Main\ORM\Fields\IntegerField('TYPE_CATEGORY_ID')),
			(new Main\ORM\Fields\DatetimeField('CREATED'))
				->configureRequired(),
			(new Main\ORM\Fields\IntegerField('AUTHOR_ID')),
			(new Main\ORM\Fields\IntegerField('ASSOCIATED_ENTITY_ID')),
			(new Main\ORM\Fields\IntegerField('ASSOCIATED_ENTITY_TYPE_ID')),
			(new Main\ORM\Fields\StringField('ASSOCIATED_ENTITY_CLASS_NAME')),
			(new Main\ORM\Fields\TextField('COMMENT')),
			(new Main\ORM\Fields\ArrayField('SETTINGS'))
				->configureSerializeCallback([self::class, 'serializeSettings'])
				->configureUnserializeCallback([self::class, 'unserializeSettings']),
			(new Main\ORM\Fields\TextField('SOURCE_ID')),
			new Main\ORM\Fields\Relations\Reference(
				'BINDINGS',
				TimelineBindingTable::class,
				['=this.ID' => 'ref.OWNER_ID'],
				['join_type' => 'INNER']
			)
		];
	}

	public static function deleteByFilter(array $filter)
	{
		$values = array();

		if(isset($filter['TYPE_ID']))
		{
			$typeID = (int)$filter['TYPE_ID'];
			$values[] = "TYPE_ID = {$typeID}";
		}

		if(isset($filter['ASSOCIATED_ENTITY_TYPE_ID']) && isset($filter['ASSOCIATED_ENTITY_ID']))
		{
			$entityTypeID = (int)$filter['ASSOCIATED_ENTITY_TYPE_ID'];
			$values[] = "ASSOCIATED_ENTITY_TYPE_ID = {$entityTypeID}";

			$entityID = (int)$filter['ASSOCIATED_ENTITY_ID'];
			$values[] = "ASSOCIATED_ENTITY_ID = {$entityID}";
		}

		Main\Application::getConnection()->queryExecute("DELETE from b_crm_timeline WHERE ".implode(' AND ', $values));

		self::cleanCache();
	}

	public static function onAfterDelete(Entity\Event $event)
	{
		$primary = $event->getParameter('primary');
		NoteTable::deleteByItemId(NoteTable::NOTE_TYPE_HISTORY, $primary['ID']);
	}

	public static function getObjectClass()
	{
		return Timeline::class;
	}

	public static function serializeSettings($value)
	{
		$value = self::encodeEmoji($value);
		return serialize($value);
	}

	public static function unserializeSettings($value)
	{
		$value = unserialize($value, ['allowed_classes' => [\DateTime::class, \Bitrix\Main\Type\DateTime::class, \Bitrix\Main\Type\Date::class]]);

		return self::decodeEmoji($value);
	}

	private static function encodeEmoji($value)
	{
		if (is_array($value))
		{
			foreach ($value as $k=>$v)
			{
				$value[$k] = self::encodeEmoji($v);
			}
		}
		if (is_string($value))
		{
			$value = \Bitrix\Main\Text\Emoji::encode($value);
		}

		return $value;
	}

	private static function decodeEmoji($value)
	{
		if (is_array($value))
		{
			foreach ($value as $k=>$v)
			{
				$value[$k] = self::decodeEmoji($v);
			}
		}
		if (is_string($value))
		{
			$value = \Bitrix\Main\Text\Emoji::decode($value);
		}

		return $value;
	}
}