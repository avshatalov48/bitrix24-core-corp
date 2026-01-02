<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internals\Steppers;

use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internals\UniqueCode;
use Bitrix\Disk\TypeFile;
use Bitrix\Disk\Internal\Service\UnifiedLink\Configuration;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlException;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Update\Stepper;

class BoardsUniqueCodeUpdater extends Stepper
{
	protected static $moduleId = 'disk';
	protected const ROWS_PER_STEP = 50;
	protected const FILE_TYPE = TypeFile::FLIPCHART;

	/**
	 * @inheritDoc
	 */
	public function execute(array &$option): bool
	{
		if (empty($option))
		{
			$option['steps'] = 0;
			$option['count'] = $this->countFilesToUpdate();
			if ($option['count'] === 0)
			{
				Configuration::setFileTypeSupport(self::FILE_TYPE);
				return self::FINISH_EXECUTION;
			}
		}

		$rows = ObjectTable::getList([
			'select' => ['ID'],
			'filter' => $this->getFilterForFilesWithoutUniqueCode(),
			'limit' => self::ROWS_PER_STEP,
		])->fetchAll();

		if (empty($rows))
		{
			Configuration::setFileTypeSupport(self::FILE_TYPE);
			return self::FINISH_EXECUTION;
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
				]
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
			Configuration::setFileTypeSupport(self::FILE_TYPE);
			return self::FINISH_EXECUTION;
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

		Configuration::setFileTypeSupport(self::FILE_TYPE);
		return self::FINISH_EXECUTION;
	}

	public function countFilesToUpdate(): int
	{
		return ObjectTable::getCount($this->getFilterForFilesWithoutUniqueCode());
	}

	private function getFilterForFilesWithoutUniqueCode(): array
	{
		return [
			'=REAL_OBJECT_ID' => new SqlExpression('ID'),
			'=TYPE_FILE' => self::FILE_TYPE,
			'=UNIQUE_CODE' => null,
		];
	}
}