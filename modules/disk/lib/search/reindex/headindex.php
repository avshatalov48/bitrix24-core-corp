<?php

namespace Bitrix\Disk\Search\Reindex;

use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

class HeadIndex extends Stepper
{
	/**
	 * @inheritDoc
	 */
	public static function getName()
	{
		return 'HeadIndexStepper';
	}

	/**
	 * @inheritDoc
	 */
	protected function getCount()
	{
		return ObjectTable::getCount();
	}

	protected function processStep($lastId)
	{
		$objectRows = ObjectTable::getList([
			'select' => ['*'],
			'filter' => [
				'>ID' => $lastId,
			],
			'order' => ['ID' => 'ASC'],
			'offset' => 0,
			'limit' => $this->getPortionSize(),
		]);

		$indexManager = Driver::getInstance()->getIndexManager();
		$indexManager
			->disableUsingSearchModule()
			->disableUsingExtendedFullText()
		;

		$lastId = null;
		$steps = 0;
		foreach ($objectRows as $objectRow)
		{
			$object = BaseObject::buildFromArray($objectRow);
			if ($object instanceof Folder)
			{
				$indexManager->indexFolder($object);
			}
			elseif ($object instanceof File)
			{
				$indexManager->indexFile($object);
			}

			$lastId = $objectRow['ID'];
			$steps++;
		}
		$indexManager->initDefaultConfiguration();

		return [
			'lastId' => $lastId,
			'steps' => $steps
		];
	}

	public static function handleFinishExecution()
	{
		$connection = Application::getConnection();
		if (!$connection->getTableField(ObjectTable::getTableName(), 'SEARCH_INDEX'))
		{
			return;
		}

		$limit = ModuleManager::isModuleInstalled('bitrix24')? 500 : 60000;
		if (ObjectTable::getCount() < $limit)
		{
			$connection->dropColumn(ObjectTable::getTableName(), 'SEARCH_INDEX');

			return;
		}

		static::addNotifyToAdmin();
	}

	protected static function addNotifyToAdmin()
	{
		\CAdminNotify::add(array(
			"MESSAGE" => Loc::getMessage("DISK_HEADINDEX_DELETE_COLUMN_OBJECT_SEARCH_INDEX"),
			"TAG" => "disk_delete_column_object_search_index",
			"MODULE_ID" => "disk"
		));
	}
}