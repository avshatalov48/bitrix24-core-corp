<?php

namespace Bitrix\Crm\Badge\Model;

use Bitrix\Crm\Badge\SourceIdentifier;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Application;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Type\DateTime;

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

	public static function deleteByEntity(ItemIdentifier $itemIdentifier): void
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();

		$sql =
			'DELETE FROM b_crm_item_badge'
			. ' WHERE ENTITY_TYPE_ID =' . $sqlHelper->convertToDbInteger($itemIdentifier->getEntityTypeId())
			. ' AND ENTITY_ID =' . $sqlHelper->convertToDbInteger($itemIdentifier->getEntityId())
		;
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
}
