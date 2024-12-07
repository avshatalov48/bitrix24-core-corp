<?php

namespace Bitrix\Crm\Integration\Disk;

use Bitrix\Crm\Traits\Singleton;
use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\File;
use Bitrix\Disk\Internals\AttachedObjectTable;
use Bitrix\Main\Loader;

class DiskRepository
{
	use Singleton;

	private static array $loadedFiles = [];

	private function __construct()
	{
		Loader::requireModule('disk');
	}

	public function existsByEntity(int $entityId, string $entityType): bool
	{
		$hasObjectTable = AttachedObjectTable::query()
			->setSelect(['ID'])
			->where('ENTITY_ID', $entityId)
			->where('ENTITY_TYPE', $entityType)
			->setLimit(1)
			->fetch();


		return (bool)$hasObjectTable;
	}

	public function getFileById($id): ?File
	{
		if (!isset(self::$loadedFiles[$id]))
		{
			self::$loadedFiles[$id] = File::loadById($id, ['STORAGE']);
		}

		return self::$loadedFiles[$id];
	}

	public function detachByAttachedObjectIds(array $needToDetach): void
	{
		AttachedObject::detachByFilter(['ID' => $needToDetach]);
	}

	public function detachAttachedObjectByQuote(int $quoteId): void
	{
		AttachedObject::detachByFilter([
			'=ENTITY_TYPE' => QuoteConnector::className(),
			'ENTITY_ID' => $quoteId
		]);
	}
}