<?php

namespace Bitrix\Crm\Badge\Model;

use Bitrix\Crm\Badge\SourceIdentifier;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Application;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Type\DateTime;
use CCrmOwnerType;

/**
 * Class BadgeTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Badge_Query query()
 * @method static EO_Badge_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Badge_Result getById($id)
 * @method static EO_Badge_Result getList(array $parameters = [])
 * @method static EO_Badge_Entity getEntity()
 * @method static \Bitrix\Crm\Badge\Model\EO_Badge createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Badge\Model\EO_Badge_Collection createCollection()
 * @method static \Bitrix\Crm\Badge\Model\EO_Badge wakeUpObject($row)
 * @method static \Bitrix\Crm\Badge\Model\EO_Badge_Collection wakeUpCollection($rows)
 */
class BadgeTable extends \Bitrix\Main\Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_item_badge';
	}

	public static function getMap()
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new DatetimeField('CREATED_DATE'))
				->configureDefaultValue(static fn(): DateTime => new DateTime())
				->configureRequired(),
			(new StringField('TYPE'))
				->configureRequired(),
			(new StringField('VALUE'))
				->configureRequired(),
			(new IntegerField('ENTITY_TYPE_ID'))
				->configureRequired(),
			(new IntegerField('ENTITY_ID'))
				->configureRequired(),
			(new StringField('SOURCE_PROVIDER_ID'))
				->configureRequired(),
			(new IntegerField('SOURCE_ENTITY_TYPE_ID'))
				->configureRequired(),
			(new IntegerField('SOURCE_ENTITY_ID'))
				->configureRequired(),
		];
	}

	public static function deleteByEntity(ItemIdentifier $itemIdentifier, string $type = null, string $value = null): void
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();

		$sql =
			'DELETE FROM b_crm_item_badge'
			. ' WHERE ENTITY_TYPE_ID =' . $sqlHelper->convertToDbInteger($itemIdentifier->getEntityTypeId())
			. ' AND ENTITY_ID =' . $sqlHelper->convertToDbInteger($itemIdentifier->getEntityId())
		;

		if (isset($type))
		{
			$sql .= ' AND TYPE =' . $sqlHelper->convertToDbString($type);
		}

		if (isset($value))
		{
			$sql .= ' AND VALUE =' . $sqlHelper->convertToDbString($value);
		}

		Application::getConnection()->query($sql);
	}

	public static function deleteBySource(SourceIdentifier $sourceItemIdentifier): void
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();

		$sql =
			'DELETE FROM b_crm_item_badge'
			. ' WHERE SOURCE_PROVIDER_ID =' . $sqlHelper->convertToDbString($sourceItemIdentifier->getProviderId())
			. ' AND SOURCE_ENTITY_TYPE_ID =' . $sqlHelper->convertToDbInteger($sourceItemIdentifier->getEntityTypeId())
			. ' AND SOURCE_ENTITY_ID =' . $sqlHelper->convertToDbInteger($sourceItemIdentifier->getEntityId())
		;
		Application::getConnection()->query($sql);
	}

	public static function deleteByIdentifiersAndType(
		ItemIdentifier $itemIdentifier,
		SourceIdentifier $sourceItemIdentifier,
		string $type
	): void
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();

		$sql =
			'DELETE FROM b_crm_item_badge'
			. ' WHERE ENTITY_TYPE_ID=' . $sqlHelper->convertToDbInteger($itemIdentifier->getEntityTypeId())
			. ' AND ENTITY_ID=' . $sqlHelper->convertToDbInteger($itemIdentifier->getEntityId())
			. ' AND SOURCE_PROVIDER_ID=' . $sqlHelper->convertToDbString($sourceItemIdentifier->getProviderId())
			. ' AND SOURCE_ENTITY_TYPE_ID=' . $sqlHelper->convertToDbInteger($sourceItemIdentifier->getEntityTypeId())
			. ' AND SOURCE_ENTITY_ID=' . $sqlHelper->convertToDbInteger($sourceItemIdentifier->getEntityId())
			. ' AND TYPE=' . $sqlHelper->convertToDbString($type)
		;
		Application::getConnection()->query($sql);
	}

	public static function deleteByAllIdentifier(
		ItemIdentifier $itemIdentifier,
		SourceIdentifier $sourceItemIdentifier,
		string $type,
		string $value
	): void
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();

		$sql =
			'DELETE FROM b_crm_item_badge'
			. ' WHERE ENTITY_TYPE_ID=' . $sqlHelper->convertToDbInteger($itemIdentifier->getEntityTypeId())
			. ' AND ENTITY_ID=' . $sqlHelper->convertToDbInteger($itemIdentifier->getEntityId())
			. ' AND SOURCE_PROVIDER_ID=' . $sqlHelper->convertToDbString($sourceItemIdentifier->getProviderId())
			. ' AND SOURCE_ENTITY_TYPE_ID=' . $sqlHelper->convertToDbInteger($sourceItemIdentifier->getEntityTypeId())
			. ' AND SOURCE_ENTITY_ID=' . $sqlHelper->convertToDbInteger($sourceItemIdentifier->getEntityId())
			. ' AND TYPE=' . $sqlHelper->convertToDbString($type)
			. ' AND VALUE=' . $sqlHelper->convertToDbString($value)
		;
		Application::getConnection()->query($sql);
	}

	public static function isActivityHasBadge(int $activityId): bool
	{
		$row = static::getList([
			'select' => ['ID'],
			'filter' => [
				'=SOURCE_PROVIDER_ID' => SourceIdentifier::CRM_OWNER_TYPE_PROVIDER,
				'SOURCE_ENTITY_TYPE_ID' => CCrmOwnerType::Activity,
				'SOURCE_ENTITY_ID' => $activityId
			],
			'limit' => 1
		])->fetch();

		return is_array($row) && isset($row['ID']);
	}
}
