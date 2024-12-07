<?php

namespace Bitrix\Crm\Service\Communication\Controller;

use Bitrix\Crm\Service\Communication\Category\Category;
use Bitrix\Crm\Service\Communication\Category\CategoryInterface;
use Bitrix\Crm\Service\Communication\Entity\CommunicationCategoryTable;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Application;
use Bitrix\Main\DB\Result;
use Bitrix\Main\Entity\AddResult;
use ReflectionClass;

final class CategoryController
{
	use Singleton;

	public function get(string $moduleId, string $code): ?Category
	{
		$category = CommunicationCategoryTable::getRow([
			'select' => [
				'ID',
				'MODULE_ID',
				'CODE',
				'HANDLER_CLASS',
			],
			'filter' => [
				'=MODULE_ID' => $moduleId,
				'=CODE' => $code,
			],
		]);

		if (!$category)
		{
			return null;
		}

		return new Category(
			$category['ID'],
			$category['MODULE_ID'],
			$category['CODE'],
			$category['HANDLER_CLASS'],
		);
	}

	public function add(string $code, string $moduleId, string $handlerClass, ?int $sort = null): AddResult
	{
		$reflect = new ReflectionClass($handlerClass);
		if (!$reflect->implementsInterface(CategoryInterface::class))
		{
			throw new \Bitrix\Main\NotImplementedException(
				$handlerClass . ' does not implement ' . CategoryInterface::class
			);
		}

		$fields = [
			'MODULE_ID' => $moduleId,
			'CODE' => $code,
			'HANDLER_CLASS' => $handlerClass,
		];

		if ($sort !== null)
		{
			$fields['SORT'] = $sort;
		}

		return CommunicationCategoryTable::add($fields);
	}

	public function delete(string $moduleId, string $code): Result
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();

		$sql = 'DELETE FROM ' . CommunicationCategoryTable::getTableName()
			. ' WHERE MODULE_ID =' . $sqlHelper->convertToDbString($moduleId)
			. ' AND CODE =' . $sqlHelper->convertToDbString($code)
		;

		return Application::getConnection()->query($sql);
	}
}
