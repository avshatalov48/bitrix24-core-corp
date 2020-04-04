<?
class CWebdavUpdateAgent1002
{
	var $optionID = "update_agent_10.0.2";
	var $stepCount = 100;

	function _checkAndClean(&$arFileElement, $entityID)
	{
		$document_id = array("webdav",$entityID, $arFileElement["ID"]);
		$history = new CBPHistoryService();
		$db_res = $history->GetHistoryList(
			array(strtoupper($by) => strtoupper($order)),
			array("DOCUMENT_ID" => $document_id),
			false,
			false,
			array("ID", "MODIFIED")
		);
		$modTime = MakeTimeStamp($arFileElement["TIMESTAMP_X"]);
		if ($db_res)
		{
			while ($arRes = $db_res->Fetch())
			{
				if (abs(MakeTimeStamp($arRes['MODIFIED']) - $modTime) < 2) // hostory record is within 2 seconds from element modification time
				{
					$history->DeleteHistory($arRes['ID']);
				}
			}
		}
	}

	function Run()
	{
		if (! CModule::IncludeModule('bizproc'))
		{
			return false;
		}
		$status = COption::GetOptionString("webdav", $this->optionID, "empty");
		if ($status == "empty" || $status == "done" || strpos($status, ",") === false)
			return false;
		list($startIBID, $startFileID) = explode(",", $status);
		
		$arElementFilter = array(
				"ACTIVE" => "",
				"SHOW_HISTORY" => "Y"
				);
		$arElementFields = array(
				"ID",
				"TIMESTAMP_X",
				"DATE_CREATE",
				"PROPERTY_FILE",
				);
		$itemsDone = 0;
		$lastIBID = 0;
		$lastElementID = 0;

		$oIB = new CIBlock;
		$oIBP = new CIBlockProperty;
		$oIBE = new CIBlockElement;

		$rIB = $oIB->GetList(array('ID' => 'ASC'), array("CHECK_PERMISSIONS" => "N"));
		while ($rIB && $arIB = $rIB->Fetch())
		{
			$iIB = $arIB['ID'];
			$rProperty = $oIB->GetProperties($iIB, array(), array('CODE' => 'WEBDAV_INFO', 'CHECK_PERMISSIONS' => 'N'));
			if ($rProperty && $arProperty = $rProperty->Fetch())
			{
				if ($iIB < $startIBID)
					continue;
				$lastIBID = $iIB;

				$rFiles = $oIBE->GetList(Array("ID" => "ASC"), $arElementFilter + array('IBLOCK_ID' => $iIB), false, false, $arElementFields);
				if ($rFiles)
				{
					while ($arFileElement = $rFiles->GetNext())
					{
						if ($arFileElement['ID'] < $startFileID)
							continue;
						$lastElementID = $arFileElement['ID'];

						$this->_checkAndClean($arFileElement, "CIBlockDocumentWebdav");
						$this->_checkAndClean($arFileElement, "CIBlockDocumentWebdavSocnet");

						$itemsDone+=1;

						if ($itemsDone > $this->stepCount) break 2;
					}
				}
			}
		}

		if ($itemsDone <= $this->stepCount) 
			$status = "done";
		else
			$status = $lastIBID.",".$lastElementID;

		COption::SetOptionString("webdav", $this->optionID, $status);
		return true;
	}
}
?>
