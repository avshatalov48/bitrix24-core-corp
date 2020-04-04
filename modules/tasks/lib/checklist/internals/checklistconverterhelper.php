<?php
namespace Bitrix\Tasks\CheckList\Internals;

use Bitrix\Main\Application;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\NotImplementedException;
use Bitrix\Tasks\CheckList\CheckListFacade;
use Bitrix\Tasks\Util;

/**
 * Class CheckListConverterHelper
 *
 * @package Bitrix\Tasks\CheckList\Internals
 */
abstract class CheckListConverterHelper
{
	/** @var CheckListFacade $facade */
	protected static $facade;

	/**
	 * @param $entityId
	 * @return bool
	 * @throws SqlQueryException
	 * @throws NotImplementedException
	 */
	public static function checkEntityConverted($entityId)
	{
		if (Util::getOption(static::getNeedOptionName()) === 'N')
		{
			return true;
		}

		$facade = static::$facade;

		/** @var DataManager $checkListDataController */
		$checkListDataController = $facade::getCheckListDataController();
		/** @var DataManager $checkListTreeDataController */
		$checkListTreeDataController = $facade::getCheckListTreeDataController();

		$entityIdName = $facade::$entityIdName;
		$entityItemsTableName = $checkListDataController::getTableName();
		$entityItemsTreeTableName = $checkListTreeDataController::getTableName();

		$connection = Application::getConnection();

		$hasOldSeparator = (bool)$connection->query("
			SELECT 1
			FROM {$entityItemsTableName} I
			WHERE I.{$entityIdName} = {$entityId} AND TITLE = '==='
			LIMIT 1
		")->fetch();

		if ($hasOldSeparator)
		{
			return false;
		}

		$hasMultilevelLinks = (bool)$connection->query("
			SELECT 1
			FROM {$entityItemsTreeTableName} IT
				INNER JOIN {$entityItemsTableName} I ON I.ID = IT.PARENT_ID
			WHERE I.{$entityIdName} = {$entityId} AND LEVEL > 0
			LIMIT 1
		")->fetch();

		if (!$hasMultilevelLinks)
		{
			return !(bool)$connection->query("
				SELECT 1
				FROM {$entityItemsTableName}
				WHERE {$entityIdName} = {$entityId}
				LIMIT 1
			")->fetch();
		}

		return true;
	}

	/**
	 * @return string
	 */
	protected static function getNeedOptionName()
	{
		return '';
	}
}