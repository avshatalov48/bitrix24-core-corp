<?php

namespace Bitrix\Crm\Binding;

use Bitrix\Crm\ContactTable;
use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;

class EntityContactTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_entity_contact';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ENTITY_TYPE_ID'))
				->configurePrimary(),
			(new IntegerField('ENTITY_ID'))
				->configurePrimary(),
			(new IntegerField('CONTACT_ID'))
				->configurePrimary(),
			(new Reference('CONTACT', ContactTable::class, Join::on('this.CONTACT_ID', 'ref.ID'))),
			(new IntegerField('SORT'))
				->configureRequired()
				->configureDefaultValue(0),
			(new IntegerField('ROLE_ID'))
				->configureRequired()
				->configureDefaultValue(EntityBinding::ROLE_UNDEFINED),
			(new BooleanField('IS_PRIMARY'))
				->configureRequired()
				->configureStorageValues('N', 'Y')
				->configureDefaultValue('N'),
		];
	}

	public static function deleteByItem(int $entityTypeId, int $entityId): void
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		/** @noinspection SqlResolve */
		$connection->query(sprintf(
			'DELETE FROM %s WHERE ENTITY_TYPE_ID = %d AND ENTITY_ID = %d',
			$helper->quote(static::getTableName()),
			$helper->convertToDbInteger($entityTypeId),
			$helper->convertToDbInteger($entityId)
		));
	}

	public static function getContactIds(int $entityTypeId, int $entityId): array
	{
		return static::getList([
			'select' => ['CONTACT_ID'],
			'order' => [
				'SORT' => 'ASC',
			],
			'filter' => [
				'=ENTITY_TYPE_ID' => $entityTypeId,
				'=ENTITY_ID' => $entityId,
			],
		])->fetchCollection()->getContactIdList();
	}
}