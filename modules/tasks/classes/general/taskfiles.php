<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 *
 * @deprecated
 * This entity is no longer supported and used by tasks module. See user fields and disk instead.
 */

use Bitrix\Tasks\Util\User;

class CTaskFiles
{
	const TEMPORARY_FILES_TTL = 86400;	// keep temporary files at least 24 hours
	const GC_PROBABILITY      = 0.01;	// probability to start garbage collection


	/**
	 * Check files accessibility by user.
	 *
	 * @param array $arFilesIds
	 * @param integer $userId
	 *
	 * @return array $arAccessMap, such as $arAccessMap = array('f' . $fileId => true/false, ...)
	 */
	public static function checkFilesAccessibilityByUser($arFilesIds, $userId)
	{
		$arAccessMap = array();
		$arFilesIds = array_unique($arFilesIds);
		$arMustBeCheckedFilesIds = $arFilesIds;	// for preventing check again already checked file id

		// Admin and B24-admin can view any file
		if (
			CTasksTools::IsAdmin($userId)
			|| CTasksTools::IsPortalB24Admin($userId)
		)
		{
			foreach ($arFilesIds as $fileId)
				$arAccessMap['f' . $fileId] = true;

			return ($arAccessMap);
		}

		// init access map to FALSE (access denied) by default
		foreach ($arFilesIds as $fileId)
			$arAccessMap['f' . $fileId] = false;

		// files that are temporary saved by user
		$arAccessibleFilesIds = self::getRegisteredTemporaryFilesList($userId);

		$arTmp = $arMustBeCheckedFilesIds;
		foreach ($arTmp as $key => $fileId)
		{
			if (in_array( (int) $fileId, $arAccessibleFilesIds, true))
			{
				$arAccessMap['f' . $fileId] = true;
				unset($arMustBeCheckedFilesIds[$key]);
			}
		}

		// user can access files, that are already attached to tasks, accessibly by user
		$arAccessibleFilesIds = self::getFilesAttachedInAccessibleTasks($userId, $arMustBeCheckedFilesIds);

		$arTmp = $arMustBeCheckedFilesIds;
		foreach ($arTmp as $key => $fileId)
		{
			if (in_array( (int) $fileId, $arAccessibleFilesIds, true))
			{
				$arAccessMap['f' . $fileId] = true;
				unset($arMustBeCheckedFilesIds[$key]);
			}
		}

		// check if file is in tasks' templates, that are accessible for this user
		if ( ! empty($arMustBeCheckedFilesIds) )
		{
			$arAccessibleFilesIds = self::getFilesAttachedInAccessibleTemplates($userId);

			foreach ($arMustBeCheckedFilesIds as $fileId)
			{
				if (in_array( (int) $fileId, $arAccessibleFilesIds, true))
					$arAccessMap['f' . $fileId] = true;
			}
		}

		return ($arAccessMap);
	}


	public static function isUserfieldFileAccessibleByUser($taskId, $fileId, $userId)
	{
		/**
		 * @global CUserTypeManager $USER_FIELD_MANAGER
		 */
		global $USER_FIELD_MANAGER;

		$isAccessible = false;

		$fileId = (int) $fileId;

		if ( ! ($fileId >= 1) )
			return (false);

		static $arOrder  = array();
		static $arSelect = array('ID');
		$arFilter = array('ID' => $taskId);
		$arParams = array('USER_ID' => $userId);

		$r = CTasks::GetList($arOrder, $arFilter, $arSelect, $arParams);
		$arTask = $r->Fetch();

		if ( ! $arTask )
			return (false);

		// We got the task, it means user have access to this task and to all files of this task

		$arUserFields = $USER_FIELD_MANAGER->GetUserFields('TASKS_TASK', $arTask['ID'], LANGUAGE_ID, $userId);

		$bFileAttachedToGivenTask = false;

		foreach($arUserFields as $arUserField)
		{
			if ($arUserField['USER_TYPE']['USER_TYPE_ID'] !== 'file')
				continue;

			if ( ! is_array($arUserField['VALUE']) )
				$arUserField['VALUE'] = array($arUserField['VALUE']);

			foreach ($arUserField['VALUE'] as $attachedFileId)
			{
				if ($fileId === (int)$attachedFileId)
				{
					$bFileAttachedToGivenTask = true;
					break;
				}
			}
		}

		if ($bFileAttachedToGivenTask)
			$isAccessible = true;

		return ($isAccessible);
	}


	public static function isFileAccessibleByUser($fileId, $userId)
	{
		$arFilesIds = array($fileId);
		$ar = self::checkFilesAccessibilityByUser($arFilesIds, $userId);

		if ($ar['f' . $fileId] === true)
			return (true);
		else
			return (false);
	}

	/**
	 * This function saves given file temporary.
	 * After it, if CTaskFiles::Add() called with given FILE_ID,
	 * this file will be marked as permamently saved.
	 *
	 * But if timeout TEMPORARY_FILES_TTL seconds occured,
	 * this files can be removed by garbage collector.
	 *
	 * Garbage collector runs at probability GC_PROBABILITY at every
	 * SaveFileTemporary() call.
	 *
	 * @param integer $userId
	 * @param string $fileName
	 * @param string $fileSize
	 * @param string $fileTmpName
	 * @param string $fileType
	 *
	 * @return int $fileId
	 */
	public static function saveFileTemporary($userId, $fileName, $fileSize, $fileTmpName, $fileType)
	{
		$userId = (int) $userId;

		$arFile = array(
			'name'      => $fileName,
			'size'      => $fileSize,
			'tmp_name'  => $fileTmpName,
			'type'      => $fileType,
			'MODULE_ID' => 'tasks'
		);


		$fileId = CFile::SaveFile($arFile, 'tasks');

		if ($fileId > 0)
			self::registerTemporaryFileInDb($userId, $fileId);

		// Run garbage collector
		if (mt_rand(1, 100000) <= (self::GC_PROBABILITY * 100000))
			self::removeExpiredTemporaryFiles();

		return ($fileId);
	}


	public static function markFileTemporary($userId, $fileId)
	{
		self::registerTemporaryFileInDb($userId, $fileId);
	}


	public static function removeTemporaryFile($userId, $fileId)
	{
		$userId = (int) $userId;
		$fileId = (int) $fileId;

		if (self::isTemporaryFileRegistered($userId, $fileId))
		{
			self::unregisterTemporaryFiles(array($fileId));
			CFile::Delete($fileId);
		}
	}


	/**
	 * Return true, when file with given file_id is registered
	 * as temporary saved file. Returns true even file age is more than
	 * self::TEMPORARY_FILES_TTL seconds, but not removed yet.
	 */
	public static function isTemporaryFileRegistered($userId, $fileId)
	{
		$arFiles = self::getRegisteredTemporaryFilesList($userId);

		return (in_array( (int) $fileId, $arFiles, true));
	}


	/**
	 * Returns ids of files, that was temporary saved. Return ids for
	 * files, that was saved more than self::TEMPORARY_FILES_TTL seconds ago too
	 * (until they be removed by self::removeOrphanedFiles()).
	 *
	 * @param integer $userId
	 * @return array of file ids
	 */
	public static function getRegisteredTemporaryFilesList($userId)
	{
		/**
		 * @global CDatabase $DB
		 */
		global $DB;

		$arFiles = array();

		$userId = (int) $userId;

		$rc = $DB->Query(
			"SELECT FILE_ID FROM b_tasks_files_temporary
			WHERE USER_ID = $userId");

		while ($ar = $rc->Fetch())
			$arFiles[] = (int) $ar['FILE_ID'];

		// For backward compatibility. Just uploaded files was registered in
		// $_SESSION["TASKS_UPLOADED_FILES"] in past
		if (isset($_SESSION['TASKS_UPLOADED_FILES']) && count($_SESSION['TASKS_UPLOADED_FILES']))
		{
			$loggedUserId = (int) User::getId();

			// this files list can be used only if logged user and checked user are equals
			if ($loggedUserId)
			{
				if ($loggedUserId === $userId)
				{
					foreach ($_SESSION['TASKS_UPLOADED_FILES'] as $fileId)
						$arFiles[] = (int) $fileId;
				}
			}
		}

		return (array_unique($arFiles));
	}


	function CheckFields(&$arFields, /** @noinspection PhpUnusedParameterInspection */ $ID = false)
	{
		/**
		 * @global CMain $APPLICATION
		 */
		global $APPLICATION;

		$arMsg = Array();

		if (!is_set($arFields, "TASK_ID"))
		{
			$arMsg[] = array("text" => GetMessage("TASKS_BAD_TASK_ID"), "id" => "ERROR_TASKS_BAD_TASK_ID");
		}
		else
		{
			/** @noinspection PhpDeprecationInspection */
			$r = CTasks::GetByID($arFields["TASK_ID"], false);
			if (!$r->Fetch())
			{
				$arMsg[] = array("text" => GetMessage("TASKS_BAD_TASK_ID_EX"), "id" => "ERROR_TASKS_BAD_TASK_ID_EX");
			}
		}

		if (!is_set($arFields, "FILE_ID"))
		{
			$arMsg[] = array("text" => GetMessage("TASKS_BAD_FILE_ID"), "id" => "ERROR_TASKS_BAD_FILE_ID");
		}
		else
		{

			$r = CFile::GetByID($arFields["FILE_ID"]);
			if (!$r->Fetch())
			{
				$arMsg[] = array("text" => GetMessage("TASKS_BAD_FILE_ID_EX"), "id" => "ERROR_TASKS_BAD_FILE_ID_EX");
			}
		}

		if (!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}

		return true;
	}


	/**
	 * @param integer $taskId
	 * @param array $arFilesIds
	 * @return bool
	 */
	public static function CheckFieldsMultiple($taskId, $arFilesIds)
	{
		/**
		 * @global CMain $APPLICATION
		 */
		global $APPLICATION;

		$arMsg = array();

		if ( ! ($taskId > 0) )
		{
			$arMsg[] = array("text" => GetMessage("TASKS_BAD_TASK_ID"), "id" => "ERROR_TASKS_BAD_TASK_ID");
		}
		else
		{
			/** @noinspection PhpDeprecationInspection */
			$r = CTasks::GetByID($taskId, false);
			if (!$r->Fetch())
			{
				$arMsg[] = array("text" => GetMessage("TASKS_BAD_TASK_ID_EX"), "id" => "ERROR_TASKS_BAD_TASK_ID_EX");
			}
		}


		if ( ! is_array($arFilesIds) )
		{
			$arMsg[] = array("text" => GetMessage("TASKS_BAD_FILE_ID"), "id" => "ERROR_TASKS_BAD_FILE_ID");
		}
		elseif ( ! empty($arFilesIds) )
		{
			$arFilesIds = array_unique($arFilesIds);
			$arNotFetchedFilesIds = $arFilesIds;

			$r = CFile::GetList(array(), array('@ID' => implode(',', $arFilesIds)));

			while ($ar = $r->Fetch())
			{
				$fileId = (int) $ar['ID'];

				$key = array_search($fileId, $arFilesIds);

				if ($key !== false)
					unset ($arNotFetchedFilesIds[$key]);
			}

			if (count($arNotFetchedFilesIds))
			{
				$arMsg[] = array("text" => GetMessage("TASKS_BAD_FILE_ID_EX"), "id" => "ERROR_TASKS_BAD_FILE_ID_EX");
			}
		}

		if (!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}

		return true;
	}


	public static function AddMultiple($taskId, $arFilesIds, $arParams = array())
	{
		global $DB;

		$taskId = (int) $taskId;
		$userId = null;

		$bCheckRightsOnFiles = true;

		if (is_array($arParams))
		{
			if (isset($arParams['USER_ID']) && ($arParams['USER_ID'] > 0))
				$userId = (int) $arParams['USER_ID'];

			if (isset($arParams['CHECK_RIGHTS_ON_FILES']))
			{
				if ( ! in_array(
						$arParams['CHECK_RIGHTS_ON_FILES'],
						array(true, false, 'Y', 'N'),
						true
				))
				{
					throw new Exception();
				}

				if (
					($arParams['CHECK_RIGHTS_ON_FILES'] === false)
					|| ($arParams['CHECK_RIGHTS_ON_FILES'] === 'N')
				)
				{
					$bCheckRightsOnFiles = false;
				}
				else
					$bCheckRightsOnFiles = true;
			}
		}

		if ($userId === null)
		{
			$userId = User::getId();
			if(!$userId)
			{
				$userId = User::getAdminId();
			}
		}

		if ( ! self::CheckFieldsMultiple($taskId, $arFilesIds) )
			return (false);

		if ($bCheckRightsOnFiles)
		{
			$ar = self::checkFilesAccessibilityByUser($arFilesIds, $userId);

			// If we have one file, that is not accessible, than emit error
			foreach ($arFilesIds as $fileId)
			{
				if (
					( ! isset($ar['f' . $fileId]) )
					|| ($ar['f' . $fileId] === false)
				)
				{
					/** @var CMain $APPLICATION */
					global $APPLICATION;
					$e = new CAdminException(array(array('text' => GetMessage('TASKS_BAD_FILE_ID_EX'), 'id' => 'ERROR_TASKS_BAD_FILE_ID_EX')));
					$APPLICATION->ThrowException($e);
					return (false);
				}
			}
		}

		$arFields = array('ID' => 1, 'FILE_ID' => false, 'TASK_ID' => (int) $taskId);

		foreach ($arFilesIds as $fileId)
		{
			$arFields['FILE_ID'] = $fileId;
			// There is duplicate key error can occured, because CTasks::Update()
			// transmit all files ids to CTaskFiles::AddMultiple().
			// So, ignore DB errors.
			// TODO: patch CTasks::Update() to transmit only new file ids, or check duplicates here.
			$DB->Add('b_tasks_file', $arFields, array(), 'tasks', $ignore_errors = true);
		}

		// Mark that attached files is not temporary now, but permament (if it was temporary)
		$arTempFiles = self::getRegisteredTemporaryFilesList($userId);
		$arTempFilesInJustAttachedToTask = array_intersect($arFilesIds, $arTempFiles);
		self::unregisterTemporaryFiles($arTempFilesInJustAttachedToTask);

		return (true);
	}


	/**
	 * Used for tasks.task.template component
	 *
	 * @deprecated
	 */
	public static function removeTemporaryStatusForFiles($arFilesIds, $userId)
	{
		if ( ! is_array($arFilesIds) )
			return (false);

		if ( ! count($arFilesIds) )
			return (null);

		$arTempFiles = self::getRegisteredTemporaryFilesList($userId);

		if (is_array($arTempFiles) && count($arTempFiles))
		{
			$arTempFilesInJustAttachedToTask = array_intersect($arFilesIds, $arTempFiles);

			if (is_array($arTempFilesInJustAttachedToTask) && count($arTempFilesInJustAttachedToTask))
			{
				self::unregisterTemporaryFiles($arTempFilesInJustAttachedToTask);
				return (true);
			}
		}

		return (null);
	}


	public function Add($arFields, $arParams = array())
	{
		/**
		 * @global CDatabase $DB
		 */
		global $DB;

		$userId = null;

		$bCheckRightsOnFiles = false;

		if (is_array($arParams))
		{
			if (isset($arParams['USER_ID']) && ($arParams['USER_ID'] > 0))
				$userId = (int) $arParams['USER_ID'];

			if (isset($arParams['CHECK_RIGHTS_ON_FILES']))
			{
				if ( ! in_array(
						$arParams['CHECK_RIGHTS_ON_FILES'],
						array(true, false, 'Y', 'N'),
						true
				))
				{
					throw new Exception();
				}

				if (
					($arParams['CHECK_RIGHTS_ON_FILES'] === false)
					|| ($arParams['CHECK_RIGHTS_ON_FILES'] === 'N')
				)
				{
					$bCheckRightsOnFiles = false;
				}
				else
					$bCheckRightsOnFiles = true;
			}
		}

		if ($userId === null)
		{
			$userId = User::getId();
			if(!$userId)
			{
				$userId = User::getAdminId();
			}
		}

		if ($this->CheckFields($arFields))
		{
			if ($bCheckRightsOnFiles)
			{
				if ( ! self::isFileAccessibleByUser( (int) $arFields['FILE_ID'], $userId) )
				{
					/**
					 * @global CMain $APPLICATION
					 */
					global $APPLICATION;
					$e = new CAdminException(array(array('text' => GetMessage('TASKS_BAD_FILE_ID_EX'), 'id' => 'ERROR_TASKS_BAD_FILE_ID_EX')));
					$APPLICATION->ThrowException($e);
					return false;
				}
			}

			$arFields["ID"] = 1;
			$ID = $DB->Add("b_tasks_file", $arFields, Array(), "tasks");

			// Mark that attached files is not temporary now, but permament (if it was temporary)
			if (self::isTemporaryFileRegistered($userId, $arFields['FILE_ID']))
				self::unregisterTemporaryFiles(array($arFields['FILE_ID']));

			return $ID;
		}

		return false;
	}


	public static function Delete($TASK_ID, $FILE_ID)
	{
		global $DB;

		$TASK_ID = (int) $TASK_ID;
		$FILE_ID = (int) $FILE_ID;

		// First, ensure that file is attached to given task
		$rsFiles = CTaskFiles::GetList(array(), array('FILE_ID' => $FILE_ID, 'TASK_ID' => $TASK_ID));
		if ( ( ! $rsFiles ) || ( ! $rsFiles->Fetch() ) )
			return (false);

		$strSql = "DELETE FROM b_tasks_file WHERE TASK_ID = ".$TASK_ID." AND FILE_ID = ".$FILE_ID;
		$result = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($result)
		{
			$rsFiles = CTaskFiles::GetList(array(), array("FILE_ID" => $FILE_ID));

			if (!$arFile = $rsFiles->Fetch())
				CFile::Delete($FILE_ID);
		}

		return $result;
	}


	public static function GetFilter($arFilter)
	{
		if (!is_array($arFilter))
			$arFilter = array();

		$arSqlSearch = array();

		foreach ($arFilter as $key => $val)
		{
			$res = CTasks::MkOperationFilter($key);
			$key = $res["FIELD"];
			$cOperationType = $res["OPERATION"];

			$key = mb_strtoupper($key);

			switch ($key)
			{
				case "TASK_ID":
				case "FILE_ID":
					$arSqlSearch[] = CTasks::FilterCreate("TF.".$key, $val, "number", $bFullJoin, $cOperationType);
					break;
			}
		}

		// Remove empty strings
		$arSqlSearchReturn = array();
		foreach ($arSqlSearch as &$str)
		{
			if ($str !== '')
				$arSqlSearchReturn[] = $str;
		}
		unset($str);

		return $arSqlSearchReturn;
	}


	/**
	 * @param $arOrder
	 * @param $arFilter
	 * @return bool|CDBResult
	 *
	 * @var CDatabase $DB
	 */
	public static function GetList($arOrder, $arFilter)
	{
		global $DB;

		$arSqlSearch = CTaskFiles::GetFilter($arFilter);

		$strSql = "
			SELECT
				TF.*
			FROM
				b_tasks_file TF
			".(sizeof($arSqlSearch) ? "WHERE ".implode(" AND ", $arSqlSearch) : "")."
		";

		if (!is_array($arOrder))
			$arOrder = Array();

		$arSqlOrder = [];
		foreach ($arOrder as $by => $order)
		{
			$by = mb_strtolower($by);
			$order = mb_strtolower($order);
			if ($order != "asc")
				$order = "desc";

			if ($by == "task")
				$arSqlOrder[] = " TF ".$order." ";
			elseif ($by == "file")
				$arSqlOrder[] = " TF.FILE_ID ".$order." ";
			elseif ($by == "rand")
				$arSqlOrder[] = CTasksTools::getRandFunction();
			else
				$arSqlOrder[] = " TF.FILE_ID ".$order." ";
		}

		$strSqlOrder = "";
		DelDuplicateSort($arSqlOrder);
		for ($i = 0, $arSqlOrderCnt = count($arSqlOrder); $i < $arSqlOrderCnt; $i++)
		{
			if ($i == 0)
				$strSqlOrder = " ORDER BY ";
			else
				$strSqlOrder .= ",";

			$strSqlOrder .= $arSqlOrder[$i];
		}

		$strSql .= $strSqlOrder;

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}


	/**
	 * @param integer $FILE_ID
	 * @return bool|CDBResult
	 *
	 * @var Cdatabase $DB
	 */
	public static function DeleteByFileID($FILE_ID)
	{
		global $DB;

		$FILE_ID = intval($FILE_ID);
		$strSql = "DELETE FROM b_tasks_file WHERE FILE_ID = ".$FILE_ID;
		$result = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($result)
		{
			CFile::Delete($FILE_ID);
		}

		return $result;
	}


	/**
	 * @param integer $TASK_ID
	 * @param array $SAVE_FILES
	 * @return bool|CDBResult
	 *
	 * @var CDatabase $DB
	 */
	public static function DeleteByTaskID($TASK_ID, $SAVE_FILES = array())
	{
		global $DB;

		$rsTaskFiles = CTaskFiles::GetList(array(), array("TASK_ID" => $TASK_ID));

		$sqrWhereAdditional = '';
		if (is_array($SAVE_FILES) && count($SAVE_FILES))
			$sqrWhereAdditional .= 'AND FILE_ID NOT IN (' . implode(',', array_map('intval', $SAVE_FILES)) . ')';

		$TASK_ID = intval($TASK_ID);
		$strSql = "DELETE FROM b_tasks_file WHERE TASK_ID = " . $TASK_ID . ' ' . $sqrWhereAdditional;
		$result = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($result)
		{
			$arFilesToDelete = array();
			while ($arTaskFiles = $rsTaskFiles->Fetch())
			{
				// Skip files, that attached to some existing tasks
				$rsFiles = CTaskFiles::GetList(array(), array("FILE_ID" => $arTaskFiles["FILE_ID"]));
				if (!$arFile = $rsFiles->Fetch())
				{
					if (!in_array($arTaskFiles["FILE_ID"], $SAVE_FILES))
					{
						$arFilesToDelete[] = $arTaskFiles["FILE_ID"];
					}
				}
			}

			foreach ($arFilesToDelete as $file)
			{
				CFile::Delete($file);
			}
		}

		return $result;
	}


	private static function getFilesAttachedInAccessibleTemplates($userId)
	{
		$arAccessibleFilesIds = array();	// Array of accessible files ids

		$rsTemplate = CTaskTemplates::GetList(
			array(),
			array('CREATED_BY' => $userId)
		);

		while ($arTemplate = $rsTemplate->Fetch())
		{
			$arTemplate['FILES'] = unserialize($arTemplate['FILES'], ['allowed_classes' => false]);

			if (is_array($arTemplate['FILES']))
			{
				foreach ($arTemplate['FILES'] as $fileId)
					$arAccessibleFilesIds[] = (int) $fileId;
			}
		}

		return (array_unique($arAccessibleFilesIds));
	}


	private static function getFilesAttachedInAccessibleTasks($userId, $arFilesIds)
	{
		$arAccessibleFilesIds = array();	// Array of accessible files ids

		$arTasksWithFiles  = array();	// Tasks with given files
		$arAccessibleTasks = array();	// Tasks that user can access
		$arTaskFiles       = array();	// Mapped FILE_ID to array of TASK_ID, that contains this file

		// Usage of 'f' prefix prevents createing indexed array,
		// but forces associative. So, PHP wouldn't fill in the gaps in
		// index values.
		// It should improves perfomance and prevent big memory usage.

		// Init $arTaskFiles
		foreach ($arFilesIds as $fileId)
			$arTaskFiles['f' . $fileId] = array();


		$rsTaskFile = self::GetList(
			array(),
			array('FILE_ID' => $arFilesIds)
		);

		while ($arTaskFile = $rsTaskFile->Fetch())
		{
			$taskId = (int) $arTaskFile['TASK_ID'];
			$fileId = (int) $arTaskFile['FILE_ID'];

			$arTasksWithFiles[] = $taskId;
			$arTaskFiles['f' . $fileId][] = $taskId;
		}

		$arTasksWithFiles = array_unique($arTasksWithFiles);

		$rsTask = CTasks::GetList(
			array(),
			array('ID' => $arTasksWithFiles),
			array('ID'),
			array('USER_ID' => $userId)
		);

		while ($arTask = $rsTask->Fetch())
			$arAccessibleTasks[] = (int) $arTask['ID'];

		// user can access files, that are already attached to tasks, accessibly by user
		foreach ($arFilesIds as $fileId)
		{
			$arTasksIds = array_unique($arTaskFiles['f' . $fileId]);

			if (count(array_intersect($arTasksIds, $arAccessibleTasks)))
				$arAccessibleFilesIds[] = (int) $fileId;
		}

		return ($arAccessibleFilesIds);
	}


	private static function unregisterTemporaryFiles($arFilesIds)
	{
		global $DB;

		if ( ! is_array($arFilesIds) )
			throw new Exception();

		$arIds = array_unique(array_map('intval', $arFilesIds));

		if (is_array($arIds) && count($arIds))
		{
			$DB->Query("DELETE FROM b_tasks_files_temporary 
				WHERE FILE_ID IN (" . implode(', ', $arIds) . ")");
		}
	}


	/**
	 * @param integer $userId
	 * @param integer $fileId
	 *
	 * @var CDatabase $DB
	 */
	private static function registerTemporaryFileInDb($userId, $fileId)
	{
		global $DB;

		$uts    = (int) time();
		$fileId = (int) $fileId;
		$userId = (int) $userId;

		$DB->query(
			"INSERT INTO b_tasks_files_temporary (USER_ID, FILE_ID, UNIX_TS)
			VALUES ($userId, $fileId, $uts)
		");
	}

	/**
	 * @var CDatabase $DB
	 */
	private static function removeExpiredTemporaryFiles()
	{
		global $DB;

		$orphanedTimestamp = (int) (time() - self::TEMPORARY_FILES_TTL);

		$DB->Query("DELETE FROM b_tasks_files_temporary 
			WHERE UNIX_TS < " . $orphanedTimestamp);
	}
}