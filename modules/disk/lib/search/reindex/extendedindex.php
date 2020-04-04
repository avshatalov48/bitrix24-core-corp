<?php

namespace Bitrix\Disk\Search\Reindex;

use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Configuration;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\Index\ObjectExtendedIndexTable;
use Bitrix\Disk\Internals\ObjectTable;

class ExtendedIndex extends Stepper
{
	public static function processWithStatusExtended()
	{
		if (!Configuration::allowIndexFiles() || !Configuration::allowUseExtendedFullText())
		{
			return ExtendedIndex::class . "::processWithStatusExtended();";
		}

		$portion = 30;
		$maxExecutionTime = 10;
		$startTime = time();

		$rows = ObjectExtendedIndexTable::getList([
			'select' => ['OBJECT_ID'],
			'filter' => [
			  '=STATUS' => ObjectExtendedIndexTable::STATUS_SHORT,
			],
			'limit' => $portion,
			'order' => ['UPDATE_TIME' => 'DESC']
		]);

		$indexManager = Driver::getInstance()->getIndexManager();
		foreach ($rows as $row)
		{
			if (time() - $startTime > $maxExecutionTime)
			{
				break;
			}

			$baseObject = BaseObject::loadById($row['OBJECT_ID']);
			if (!$baseObject)
			{
				ObjectExtendedIndexTable::delete($row['OBJECT_ID']);
			}

			if ($baseObject instanceof Folder)
			{
				$indexManager->indexFolderWithExtendedIndex($baseObject);
			}
			elseif ($baseObject instanceof File)
			{
				$indexManager->indexFileWithExtendedIndex($baseObject);
			}
		}

		return ExtendedIndex::class . "::processWithStatusExtended();";
	}

	/**
	 * @inheritDoc
	 */
	public static function getName()
	{
		return 'ExtendedIndexStepper';
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
		;

		$lastId = null;
		$steps = 0;
		foreach ($objectRows as $objectRow)
		{
			$object = BaseObject::buildFromArray($objectRow);
			if ($object instanceof Folder)
			{
				$indexManager->indexFolderWithExtendedIndex($object);
			}
			elseif ($object instanceof File)
			{
				$indexManager->indexFileWithExtendedIndex($object);
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
}