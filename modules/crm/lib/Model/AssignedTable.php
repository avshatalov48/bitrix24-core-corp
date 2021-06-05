<?php
namespace Bitrix\Crm\Model;

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;

class AssignedTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_assigned';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new IntegerField('ENTITY_TYPE_ID'))
				->configureRequired(),
			(new IntegerField('ENTITY_ID'))
				->configureRequired(),
			(new IntegerField('ASSIGNED_BY'))
				->configureRequired(),
		];
	}

	public static function deleteByEntityTypeId(int $entityTypeId): void
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		$connection->query(sprintf(
			'DELETE FROM %s WHERE ENTITY_TYPE_ID = %d',
			$helper->quote(static::getTableName()),
			$helper->convertToDbInteger($entityTypeId)
		));
	}

	public static function deleteByItem(int $entityTypeId, int $entityId): void
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		$connection->query(sprintf(
			'DELETE FROM %s WHERE ENTITY_TYPE_ID = %d AND ENTITY_ID = %d',
			$helper->quote(static::getTableName()),
			$helper->convertToDbInteger($entityTypeId),
			$helper->convertToDbInteger($entityId)
		));
	}

	public static function getItemIdsByAssigned(int $entityTypeId, $assigned): array
	{
		$ids = static::getList([
			'select' => ['ENTITY_ID'],
			'filter' => [
				'=ENTITY_TYPE_ID' => $entityTypeId,
				'=ASSIGNED_BY' => $assigned,
			],
		])->fetchCollection()->getList('ENTITY_ID');

		if(empty($ids))
		{
			$ids = [-1];
		}

		return $ids;
	}
}