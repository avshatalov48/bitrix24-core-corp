<?php
declare(strict_types=1);

namespace Bitrix\Disk\Internal\Service\UnifiedLink;

use Bitrix\Disk\Internals\UniqueCode;
use Bitrix\Main\Application;
use Throwable;

class UniqueCodeBackfiller
{
	/** @var array<int, string> */
	private array $processedFileIds = [];

	public function backfillUniqueCode(int $fileId): bool
	{
		if (!isset($this->processedFileIds[$fileId]))
		{
			$connection = Application::getConnection();
			$uniqueCode = (new UniqueCode())->generate();
			$sqlHelper = $connection->getSqlHelper();

			try
			{
				$connection->queryExecute(
					"UPDATE b_disk_object
						SET UNIQUE_CODE = '" . $sqlHelper->forSql($uniqueCode) . "'
						WHERE ID = {$fileId} AND (UNIQUE_CODE IS NULL OR UNIQUE_CODE = '')"
				);

				$this->processedFileIds[$fileId] = $uniqueCode;

				return true;
			}
			catch (Throwable)
			{
				$this->processedFileIds[$fileId] = '';

				return false;
			}
		}

		return !empty($this->getBackfilledUniqueCode($fileId));
	}

	public function getBackfilledUniqueCode(int $fileId): ?string
	{
		return $this->processedFileIds[$fileId] ?? null;
	}
}
