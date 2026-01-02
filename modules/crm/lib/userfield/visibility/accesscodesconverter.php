<?php

namespace Bitrix\Crm\UserField\Visibility;

use Bitrix\Crm\Integration\HumanResources\DepartmentQueries;
use Bitrix\Crm\Integration\HumanResources\HumanResources;
use Bitrix\Main\Application;
use Bitrix\Main\UserField\Access\Permission\UserFieldPermissionTable;
use COption;

final class AccessCodesConverter
{
	public const IS_CONVERTING_OPTION_NAME = 'userfield_permissions_is_converting';

	public const LIMIT = 100;

	public static function isConversionInProgress(): bool
	{
		return COption::GetOptionString('crm', self::IS_CONVERTING_OPTION_NAME, 'N') === 'Y';
	}

	public function hasUnconvertedAccessCodes(): bool
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();
		$tableName = UserFieldPermissionTable::getTableName();

		return (bool)$connection->queryScalar(
			'SELECT 1 FROM ' . $helper->quote($tableName)
			. ' WHERE ' . $helper->getIlikeOperator('ACCESS_CODE', "'DR%'"),
		);
	}

	public function execute(): void
	{
		if (!HumanResources::getInstance()->isUsed())
		{
			return;
		}

		$items = $this->getItems();

		$accessCodes = array_map(
			static fn(string $code) => str_replace('DR', 'D', $code),
			array_unique(array_column($items, 'ACCESS_CODE')),
		);

		if (empty($accessCodes))
		{
			return;
		}

		$departmentsQueries = DepartmentQueries::getInstance();
		$humanResources = HumanResources::getInstance();
		foreach ($items as $item)
		{
			$department = $departmentsQueries->getDepartmentByAccessCode($item['ACCESS_CODE']);
			if (!$department)
			{
				continue;
			}

			UserFieldPermissionTable::add([
				'ENTITY_TYPE_ID' => $item['ENTITY_TYPE_ID'],
				'USER_FIELD_ID' => $item['USER_FIELD_ID'],
				'ACCESS_CODE' => $humanResources->buildAccessCode('SNDR', $department->id),
				'PERMISSION_ID' => $item['PERMISSION_ID'],
				'VALUE' => $item['VALUE'],
			]);
		}

		$this->removeItems(array_column($items, 'ID'));
	}

	private function getItems(): array
	{
		return UserFieldPermissionTable::query()
			->setSelect(['*'])
			->whereLike('ACCESS_CODE', 'DR%')
			->setLimit(self::LIMIT)
			->fetchAll()
		;
	}

	private function removeItems(array $ids): void
	{
		UserFieldPermissionTable::deleteList([
			['@ID' => $ids],
		]);
	}
}
