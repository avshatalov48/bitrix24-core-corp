<?php
IncludeModuleLangFile(__FILE__);
if (!CModule::IncludeModule("bizproc"))
	return;

class CWebdavDocumentHistory
{
	private static $historyService = null;
	private static $glueEnabled = null;

	private static function GetHistoryService()
	{
		if ((self::$historyService == null) && (CModule::IncludeModule('bizproc')))
		{
			$runtime = CBPRuntime::GetRuntime();
			$runtime->StartRuntime();
			self::$historyService = $runtime->GetService("HistoryService");
		}
		return self::$historyService;
	}

	private static function IsGlueEnabled()
	{
		if (self::$glueEnabled == null)
			self::$glueEnabled = (COption::GetOptionString('webdav', 'bp_history_glue', "Y") == "Y");
		return self::$glueEnabled;
	}

	public static function GetHistoryState($documentID, $historyID = null, $arDocHistory = null, $arParams = array())
	{
		static $WD_HISTORYGLUE_PERIOD = null;

		static $arHistoryFields = array("ID", "DOCUMENT_ID", "MODIFIED", "DOCUMENT");

		$historyService = self::GetHistoryService();

		$result = 'N';
		if (self::IsGlueEnabled())
		{
			$result = 'Y';

			if ($WD_HISTORYGLUE_PERIOD == null)
				$WD_HISTORYGLUE_PERIOD = COption::GetOptionString('webdav', 'bp_history_glue_period', 300);

			if ($historyID == null || $arDocHistory == null)
			{
				if ($historyID == null)
				{
					$arFilter = array(
						"DOCUMENT_ID" => $documentID
					);
				}
				else
				{
					$arFilter = array(
						"ID" => $historyID
					);
				}

				$dbDoc = $historyService->GetHistoryList(
					array("ID" => "DESC"),
					$arFilter,
					false,
					false,
					$arHistoryFields
				);

				CTimeZone::Disable();
				if (!($dbDoc && $arDocHistory = $dbDoc->Fetch()))
					$result = 'N';
				if (isset($arParams['NEW']) && $arParams['NEW'] == 'Y')
				{
					$arDocHistory = $dbDoc->Fetch();
				}
				CTimeZone::Enable();
			}

			if (($result == 'Y') && isset($arDocHistory['DOCUMENT']['PROPERTIES']['FILE']['HISTORYGLUE'])) // last history record is 'glued'
			{
				$result = $arDocHistory['DOCUMENT']['PROPERTIES']['FILE']['HISTORYGLUE'];
				if (isset($arParams['CHECK_TIME']) && $arParams['CHECK_TIME'] == 'Y')
				{
					$result = "Y";
					$modifiedTS = MakeTimeStamp($arDocHistory['MODIFIED']);
					if ((time() - $modifiedTS) > $WD_HISTORYGLUE_PERIOD)
					{
						$result = 'N';
					}
				}
			}
			else
			{
				$result = 'N';
			}
		}

		return $result;
	}

	public static function IsHistoryUpdate($documentID)
	{
		static $arHistoryFields = array("ID", "DOCUMENT_ID", "MODIFIED", "DOCUMENT");

		$historyService = self::GetHistoryService();
		$result = false;

		if (self::IsGlueEnabled())
		{
			$arFilter = array(
				"DOCUMENT_ID" => $documentID,
				"USER_ID" => CUser::GetID()
			);

			$dbDoc = $historyService->GetHistoryList(
				array("ID" => "DESC"),
				$arFilter,
				false,
				false,
				$arHistoryFields
			);

			CTimeZone::Disable();
			if ($dbDoc && ($arDoc = $dbDoc->Fetch())) // get the last history record
			{
				CTimeZone::Enable();
				if (CWebdavDocumentHistory::GetHistoryState($documentID, $arDoc['ID'], $arDoc) == 'Y')
				{
					$result = $arDoc;
				}
			}
			else
			{
				CTimeZone::Enable();
			}
		}

		return $result;
	}

	public function UpdateDocumentHistory($parameterDocumentId, $historyId)
	{
		$historyService = self::GetHistoryService();
		list($moduleId, $entity, $documentId) = CBPHelper::ParseDocumentId($parameterDocumentId);
		$result = false;	
		if (($moduleId == 'webdav') && (class_exists($entity)))
		{
			$doc = call_user_func_array(array($entity, "GetDocumentForHistory"), array($documentId, $historyId, true));
			$result = $historyService->UpdateHistory($historyId, array('DOCUMENT' => $doc));
		}
		return $result;
	}

	public static function GetFileForHistory($documentId, $propertyValue, $historyIndex)
	{
		$newFileID = $propertyValue['VALUE'];
		$bNewFile = true;

		$history = self::GetHistoryService();

		$dbDoc = $history->GetHistoryList(
			array("ID" => "DESC"),
			array("DOCUMENT_ID" => $documentId),
			false,
			false,
			array("ID", "DOCUMENT_ID", "NAME", "MODIFIED", "USER_ID", "USER_NAME", "USER_LAST_NAME", "USER_LOGIN", "DOCUMENT")
		);

		$newFileHash = CWebDavBase::_get_file_hash($newFileID);
		$oldFileHash = null;

		if ($newFileHash !== null)
		{
			if ($dbDoc && ($arTmpDoc = $dbDoc->Fetch())) // skip current saving copy
			{
				while ($arDoc = $dbDoc->Fetch())
				{
					$oldFileHash = $arDoc['DOCUMENT']['PROPERTIES']['FILE']['HASH'];
					if ($oldFileHash == $newFileHash)
					{
						$bNewFile = false;
						$result = $arDoc['DOCUMENT']['PROPERTIES']['FILE'];
						break;
					}
				}
			}
		}

		if ($oldFileHash == null || $newFileHash == null)
			$bNewFile = true;	// add new copy to history

		if ($bNewFile)
		{
			$result = array(
				"VALUE" => CBPDocument::PrepareFileForHistory(
					$documentId,
					$propertyValue["VALUE"],
					$historyIndex
				),
				"DESCRIPTION" => $propertyValue["DESCRIPTION"],
				"HASH" => $newFileHash
			);
		}

		return $result;
	}

	static function OnBeforeDeleteFileFromHistory($historyId, $documentId)
	{
		static $arHistoryFields = array("ID", "DOCUMENT_ID", "DOCUMENT");

		$history = new CBPHistoryService();

		if ($documentId[0] != 'webdav')
			return true;

		$fileToDeleteHash = null;

		$dbDoc = $history->GetHistoryList(
			array("ID" => "DESC"),
			array("ID" => $historyId),
			false,
			false,
			$arHistoryFields
		);

		if ($dbDoc && ($arDoc = $dbDoc->Fetch()))
		{
			if (isset($arDoc['DOCUMENT']['PROPERTIES']['FILE']['HASH']))
				$fileToDeleteHash = $arDoc['DOCUMENT']['PROPERTIES']['FILE']['HASH'];
		}

		$result = true; // seems to be an old file without hash
		if ($fileToDeleteHash != null) // if not
		{
			$dbDoc = $history->GetHistoryList(
				array("ID" => "DESC"),
				array("DOCUMENT_ID" => $documentId),
				false,
				false,
				$arHistoryFields
			);

			if ($dbDoc)
			{
				while ($arDoc = $dbDoc->Fetch())
				{
					if ($arDoc['ID'] == $historyId)
						continue;
					if (isset($arDoc['DOCUMENT']['PROPERTIES']['FILE']['HASH']))
					{
						$result = !($arDoc['DOCUMENT']['PROPERTIES']['FILE']['HASH'] == $fileToDeleteHash); // file used also in other historyRecord
						if (!$result)
							break;
					}
				}
			}

		}
		return $result;
	}

}