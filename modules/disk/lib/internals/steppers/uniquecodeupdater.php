<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internals\Steppers;

use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internals\UniqueCode;
use Bitrix\Disk\Internal\Service\UnifiedLink\Configuration;
use Bitrix\Disk\TypeFile;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlException;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Update\Stepper;

class UniqueCodeUpdater extends Stepper
{
	protected static $moduleId = 'disk';
	protected const ROWS_PER_STEP = 1000;
	protected const FILE_TYPES = [
		TypeFile::DOCUMENT,
		TypeFile::PDF,
	];

	/**
	 * @inheritDoc
	 */
	public function execute(array &$option): bool
	{
		$fileTypes = self::FILE_TYPES;

		if (empty($option))
		{
			$option['steps'] = 0;
			$option['count'] = $this->countFilesToUpdate($fileTypes);
			if ($option['count'] === 0)
			{
				$this->setFileTypeSupport($fileTypes);

				return self::FINISH_EXECUTION;
			}
		}

		$lastProcessedId = isset($option['lastProcessedId']) ? (int)$option['lastProcessedId'] : 0;

		if (!isset($option['maxIdWithoutUniqueCode']))
		{
			$option['maxIdWithoutUniqueCode'] = $this->getMaxIdWithoutUniqueCode($fileTypes, $lastProcessedId);
		}

		$filterForFilesWithoutUniqueCode = $this->getFilterForFilesWithoutUniqueCode($fileTypes, false);
		if ($lastProcessedId > 0)
		{
			$filterForFilesWithoutUniqueCode['>ID'] = $lastProcessedId;
		}

		if (isset($option['maxIdWithoutUniqueCode']))
		{
			$filterForFilesWithoutUniqueCode['<=ID'] = $option['maxIdWithoutUniqueCode'];
		}

		$rows = ObjectTable::getList([
			'select' => ['ID', 'UNIQUE_CODE'],
			'filter' => $filterForFilesWithoutUniqueCode,
			'limit' => self::ROWS_PER_STEP,
			'order' => ['ID' => 'ASC'],
		])->fetchAll();

		$fetchedIds = array_column($rows, 'ID');
		$option['lastProcessedId'] = $fetchedIds[array_key_last($fetchedIds)];

		$rows = array_filter($rows, static function ($row) {
			return empty($row['UNIQUE_CODE']);
		});

		if (empty($rows))
		{
			return self::CONTINUE_EXECUTION;
		}

		$idsToProcess = array_column($rows, 'ID');

		$updates = [];
		$uniqueCode = new UniqueCode();

		// the loop continues until unique codes are generated for all IDs
		while (!empty($idsToProcess))
		{
			$candidateCodes = [];
			foreach ($idsToProcess as $id)
			{
				// generate candidates
				$candidateCodes[$id] = $uniqueCode->generate();
			}

			// check which of the generated codes already exist in the table
			$existingCodes = [];
			$dbResult = ObjectTable::getList([
				'select' => ['UNIQUE_CODE'],
				'filter' => [
					'@UNIQUE_CODE' => array_values($candidateCodes),
				],
			]);
			foreach ($dbResult as $row)
			{
				$existingCodes[$row['UNIQUE_CODE']] = true;
			}

			$idsForNextIteration = [];
			foreach ($candidateCodes as $id => $code)
			{
				if (isset($existingCodes[$code]))
				{
					// this code is already taken, a new one will need to be generated in the next iteration
					$idsForNextIteration[] = $id;
				}
				else
				{
					// the code is unique, add it to the final update list
					$updates[$id] = $code;
				}
			}

			$idsToProcess = $idsForNextIteration;
		}

		if (empty($updates))
		{
			return self::CONTINUE_EXECUTION;
		}

		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$caseSql = '';
		foreach ($updates as $id => $code)
		{
			$caseSql .= "WHEN {$id} THEN '{$sqlHelper->forSql($code)}' ";
		}

		$idList = implode(',', array_keys($updates));
		$sql = "
			UPDATE b_disk_object
			SET UNIQUE_CODE = CASE ID {$caseSql} END
			WHERE ID IN ({$idList})
		";

		try
		{
			$connection->query($sql);
		}
		catch (SqlException $e)
		{
			Application::getInstance()->getExceptionHandler()->writeToLog($e);

			return self::FINISH_EXECUTION;
		}

		$option['steps'] += count($updates);

		if ($option['steps'] < $option['count'])
		{
			return self::CONTINUE_EXECUTION;
		}

		$this->setFileTypeSupport($fileTypes);

		return self::FINISH_EXECUTION;
	}

	public function countFilesToUpdate(array $fileTypes): int
	{
		return ObjectTable::getCount($this->getFilterForFilesWithoutUniqueCode($fileTypes));
	}

	private function getFilterForFilesWithoutUniqueCode(array $fileTypes, bool $withUniqueCodeFilter = true): array
	{
		$filter = [
			'=REAL_OBJECT_ID' => new SqlExpression('ID'),
			'=TYPE_FILE' => $fileTypes,
		];

		if ($withUniqueCodeFilter)
		{
			$filter['=UNIQUE_CODE'] = null;
		}

		return $filter;
	}

	private function setFileTypeSupport(array $fileTypes): void
	{
		foreach ($fileTypes as $fileType)
		{
			Configuration::setFileTypeSupport($fileType);
		}
	}

	private function getMaxIdWithoutUniqueCode(array $fileTypes, int $lastProcessedId): int
	{
		$filter = $this->getFilterForFilesWithoutUniqueCode($fileTypes);

		if ($lastProcessedId > 0)
		{
			$filter['>ID'] = $lastProcessedId;
		}

		$row = ObjectTable::getList([
			'select' => ['ID'],
			'filter' => $filter,
			'limit' => 1,
			'order' => ['ID' => 'DESC'],
		])->fetch();

		return $row ? (int)$row['ID'] : 0;
	}
}