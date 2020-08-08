<?php
namespace Bitrix\Crm\Agent\Files;

use Bitrix\Main;
use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Main\Application;


final class ClearUnregisteredLogo extends AgentBase
{
	private static $limit = 100;
	private static $timeLimit = 20;

	/**
	 * @param int $delay
	 */
	public static function activate($delay = 10)
	{
		if(!is_int($delay))
		{
			$delay = (int)$delay;
		}
		if($delay < 0)
		{
			$delay = 0;
		}

		\CAgent::AddAgent(
			get_called_class().'::run();',
			'crm',
			'N',
			0,
			'',
			'Y',
			\ConvertTimeStamp(time() + \CTimeZone::GetOffset() + $delay, 'FULL')
		);
	}

	/**
	 * Deletes files that were left after contact|company update.
	 *
	 *
	 * @return string
	 */
	public static function doRun()
	{
		$limit = (int)static::$limit;
		if ($limit <= 0)
		{
			$limit = 100;
		}
		$timeLimit = (int)static::$timeLimit;
		if (defined('START_EXEC_TIME') && START_EXEC_TIME > 0)
		{
			$startTime = (int)START_EXEC_TIME;
		}
		else
		{
			$startTime = time();
		}

		$connection = Application::getConnection();

		$workLoad = false;

		$sqlDisk = $sqlActivityDisk = '';
		$diskAvailable = Main\ModuleManager::isModuleInstalled('disk') &&
						 Main\Loader::includeModule('disk');

		if ($diskAvailable)
		{
			$sqlDisk = "
				AND ID NOT IN (SELECT FILE_ID FROM b_disk_version ) 
				AND ID NOT IN (SELECT FILE_ID FROM b_disk_object 
								WHERE TYPE = '".\Bitrix\Disk\Internals\ObjectTable::TYPE_FILE."' 
										AND ID = REAL_OBJECT_ID AND FILE_ID IS NOT NULL)
			";

			// activity disk file
			$sqlActivityDisk = "
				AND ID NOT IN(
					SELECT FILE_ID FROM b_disk_object 
					WHERE TYPE = '".\Bitrix\Disk\Internals\ObjectTable::TYPE_FILE."' 
						AND ID IN(SELECT ELEMENT_ID FROM b_crm_act_elem where STORAGE_TYPE_ID = '".\Bitrix\Crm\Integration\StorageType::Disk."')
				)
			";
		}



		$ufFieldSql = '';
		$ufFieldQueries = static::prepareUserFieldQuery();
		foreach ($ufFieldQueries as $queryStr)
		{
			$ufFieldSql .= " AND ID NOT IN({$queryStr}) ";
		}

		for ($i = 2, $auxiliaries = ['1 as n']; $i <= 50; $i++)
		{
			$auxiliaries[] = $i;
		}
		$auxiliarySql = implode(' union select ', $auxiliaries);

		$storageTypeFile = \Bitrix\Crm\Integration\StorageType::File;

		$result = $connection->query("
			select ID
			from b_file 
			where 
				MODULE_ID = 'crm'
				-- image
				and CONTENT_TYPE like 'image/%'
				-- activity file
				AND ID NOT IN (SELECT ELEMENT_ID FROM b_crm_act_elem where STORAGE_TYPE_ID = '{$storageTypeFile}') 
				-- activity disk file
				{$sqlActivityDisk}
				-- event file
				and id not in (select  
								CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(src.fids, ' ', NS.n), ' ', -1) AS UNSIGNED) as FILE_ID
							from (
								select {$auxiliarySql}
							) NS
							inner join
							(
								select
									@xml := replace(replace(replace(replace(e.FILES,'a:','<a><len>'),';}','</i></a>'),':{i:','</len><i>'),';i:','</i><i>') as xml,
									CAST(ExtractValue(@xml, '/a/len') AS UNSIGNED) as len,
									ExtractValue(@xml, '/a/i[position() mod 2 = 0]') as fids,
									e.ID
								from 
									b_crm_event e
								where 
									e.FILES IS NOT NULL
							) src 
							ON NS.n <= src.len
						) 

				-- disk
				{$sqlDisk}

				-- logo
				and id not in(select cast(photo AS UNSIGNED) from b_crm_contact where photo is not null)
				and id not in(select cast(logo AS UNSIGNED) from b_crm_company where logo is not null)
			
				-- uf field
				{$ufFieldSql}
			
				-- only logo
				and exists(
					select 'x' from b_file f
					where
						f.ORIGINAL_NAME = ORIGINAL_NAME
						and f.FILE_SIZE = FILE_SIZE
						and f.id in(select cast(photo AS UNSIGNED) from b_crm_contact where photo is not NULL
									UNION
									select cast(logo AS UNSIGNED) from b_crm_company where logo is not NULL)
				)
			
			LIMIT {$limit}
		");

		while ($row = $result->fetch())
		{
			$workLoad = true;

			\CFile::Delete($row['ID']);

			if ($timeLimit > 0)
			{
				if ((time() - $startTime) >= $timeLimit)
				{
					break;
				}
			}
		}

		return $workLoad;
	}


	/**
	 * Gets SQL query code to userfield table.
	 *
	 * @return string[]
	 */
	private static function prepareUserFieldQuery()
	{
		$diskAvailable = \Bitrix\Main\ModuleManager::isModuleInstalled('disk') &&
						 \Bitrix\Main\Loader::includeModule('disk');

		$userTypeField = array(\CUserTypeFile::USER_TYPE_ID);
		if ($diskAvailable)
		{
			$userTypeField[] = \Bitrix\Disk\Uf\FileUserType::USER_TYPE_ID;
			$userTypeField[] = \Bitrix\Disk\Uf\VersionUserType::USER_TYPE_ID;
		}

		$userFieldList = \Bitrix\Main\UserFieldTable::getList(array(
			'filter' => array(
				'ENTITY_ID' => 'CRM_%',
				'=USER_TYPE_ID' => $userTypeField,
			),
			'select' => array(
				'ID',
				'ENTITY_ID',
				'USER_TYPE_ID',
				'FIELD_NAME',
				'MULTIPLE',
			),
		));
		$userFieldInformation[] = array();
		if ($userFieldList->getSelectedRowsCount() > 0)
		{
			foreach ($userFieldList as $userField)
			{
				$userFieldInformation[] = $userField;
			}
		}
		if (empty($userFieldInformation))
		{
			return [];
		}

		$connection = \Bitrix\Main\Application::getConnection();

		$tablesInformation = array();
		$result = $connection->query("
			SELECT TABLE_NAME
			FROM information_schema.TABLES 
			WHERE 
				TABLE_SCHEMA = '".$connection->getDatabase()."'
				AND (
					TABLE_NAME LIKE 'b_uts_crm_%' OR
					TABLE_NAME LIKE 'b_utm_crm_%' OR
					TABLE_NAME LIKE 'b_uts_order' OR
					TABLE_NAME LIKE 'b_utm_order'
				)
		");
		while ($row = $result->fetch())
		{
			$tablesInformation[] = $row['TABLE_NAME'];
		}
		if (empty($tablesInformation))
		{
			return [];
		}


		$querySql = [];

		foreach ($userFieldInformation as $userField)
		{
			$ufName = $userField['ENTITY_ID'];
			$ufType = $userField['USER_TYPE_ID'];

			if ($userField['MULTIPLE'] === 'Y')
			{
				$ufId = $userField['ID'];
				$utmEntityTableName = 'b_utm_'.mb_strtolower($ufName);

				if (in_array($utmEntityTableName, $tablesInformation))
				{
					if (
						$diskAvailable &&
						$ufType === \Bitrix\Disk\Uf\FileUserType::USER_TYPE_ID
					)
					{
						$querySql[] = "
							SELECT files.FILE_ID as FILE_ID
							FROM
								{$utmEntityTableName} ufsrc
								INNER JOIN b_disk_attached_object attached
									ON attached.ID = ufsrc.VALUE_INT
									AND ufsrc.FIELD_ID = '{$ufId}'
								INNER JOIN b_disk_object files
									ON files.ID = attached.OBJECT_ID 
									AND files.ID = files.REAL_OBJECT_ID
									AND files.TYPE = '".\Bitrix\Disk\Internals\ObjectTable::TYPE_FILE."'
						";
					}

					elseif (
						$diskAvailable &&
						$ufType === \Bitrix\Disk\Uf\VersionUserType::USER_TYPE_ID
					)
					{
						$querySql[] = "
							SELECT versions.FILE_ID as FILE_ID
							FROM
								{$utmEntityTableName} ufsrc
								INNER JOIN b_disk_attached_object attached
									ON attached.ID = ufsrc.VALUE_INT
									AND ufsrc.FIELD_ID = '{$ufId}'
								INNER JOIN b_disk_version versions
									ON versions.ID = attached.VERSION_ID 
								INNER JOIN b_disk_object files
									ON files.ID = versions.OBJECT_ID
									AND files.ID = attached.OBJECT_ID 
									AND files.ID = files.REAL_OBJECT_ID
									AND files.TYPE = '".\Bitrix\Disk\Internals\ObjectTable::TYPE_FILE."'
						";
					}

					elseif (
						$ufType === \CUserTypeFile::USER_TYPE_ID
					)
					{
						$querySql[] = "
							SELECT ufsrc.VALUE_INT as FILE_ID
							FROM {$utmEntityTableName} ufsrc
							WHERE ufsrc.FIELD_ID = '{$ufId}' AND ufsrc.VALUE_INT IS NOT NULL 
						";
					}
				}
			}
			else
			{
				$ufEntityTableFieldName = $userField['FIELD_NAME'];
				$utsEntityTableName = 'b_uts_'.mb_strtolower($ufName);

				if (in_array($utsEntityTableName, $tablesInformation))
				{
					if (
						$diskAvailable &&
						$ufType === \Bitrix\Disk\Uf\FileUserType::USER_TYPE_ID
					)
					{
						$querySql[] = "
							SELECT files.FILE_ID as FILE_ID
							FROM
								{$utsEntityTableName} ufsrc
								INNER JOIN b_disk_attached_object attached
									ON attached.ID = cast(ufsrc.{$ufEntityTableFieldName} as UNSIGNED)
									and ufsrc.{$ufEntityTableFieldName} REGEXP '^[0-9]+$'
								INNER JOIN b_disk_object files
									ON files.ID = attached.OBJECT_ID 
									AND files.ID = files.REAL_OBJECT_ID
									AND files.TYPE = '".\Bitrix\Disk\Internals\ObjectTable::TYPE_FILE."'
						";
					}

					elseif (
						$diskAvailable &&
						$ufType === \Bitrix\Disk\Uf\VersionUserType::USER_TYPE_ID
					)
					{
						$querySql[] = "
							SELECT versions.FILE_ID as FILE_ID
							FROM
								{$utsEntityTableName} ufsrc
								INNER JOIN b_disk_attached_object attached
									ON attached.ID = cast(ufsrc.{$ufEntityTableFieldName} as UNSIGNED)
									and ufsrc.{$ufEntityTableFieldName} REGEXP '^[0-9]+$'
								INNER JOIN b_disk_version versions
									ON versions.ID = attached.VERSION_ID 
								INNER JOIN b_disk_object files
									ON files.ID = versions.OBJECT_ID
									AND files.ID = attached.OBJECT_ID
									AND files.ID = files.REAL_OBJECT_ID
									AND files.TYPE = '".\Bitrix\Disk\Internals\ObjectTable::TYPE_FILE."'
						";
					}

					elseif (
						$ufType === \CUserTypeFile::USER_TYPE_ID
					)
					{
						$querySql[] = "
							SELECT CAST(ufsrc.{$ufEntityTableFieldName} as UNSIGNED) as FILE_ID
							FROM {$utsEntityTableName} ufsrc 
							WHERE ufsrc.{$ufEntityTableFieldName} IS NOT NULL AND ufsrc.{$ufEntityTableFieldName} REGEXP '^[0-9]+$'
						";
					}
				}
			}
		}

		return $querySql;
	}
}