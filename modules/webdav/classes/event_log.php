<?php
##############################################
# Bitrix Site Manager Forum					 #
# Copyright (c) 2002-2011 Bitrix			 #
# http://www.bitrixsoft.com					 #
# mailto:admin@bitrixsoft.com				 #
##############################################
IncludeModuleLangFile(__FILE__);

class CWebDavEventLog
{
	var $events = array(
		"OnFileDelete" => array('action' => 'DELETE', 'object' => 'ELEMENT'),
		"OnFolderDelete" => array('action' => 'DELETE', 'object' => 'SECTION'),
		"OnFileTrash" => array('action' => 'TRASH', 'object' => 'ELEMENT'),
		"OnFolderTrash" => array('action' => 'TRASH', 'object' => 'SECTION'),
		"OnFileRestore" => array('action' => 'RESTORE', 'object' => 'ELEMENT'),
		"OnFolderRestore" => array('action' => 'RESTORE', 'object' => 'SECTION'),
		"OnFileMove" => array('action' => 'MOVE', 'object' => 'ELEMENT'),
		"OnFolderMove" => array('action' => 'MOVE', 'object' => 'SECTION'),
		"OnFileRename" => array('action' => 'RENAME', 'object' => 'ELEMENT'),
		"OnFolderRename" => array('action' => 'RENAME', 'object' => 'SECTION'),
		"OnFileAdd" => array('action' => 'ADD', 'object' => 'ELEMENT'),
		"OnFolderAdd" => array('action' => 'ADD', 'object' => 'SECTION'),
		"OnFileUpdate" => array('action' => 'UPDATE', 'object' => 'ELEMENT')
	);
	var $iblock_id;
	var $SectionURL;
	
	static function Log($object, $action, $id, $description)
	{
		if (!COption::GetOptionString("webdav", "webdav_log", "N") == "Y")
			return;
			
		$type = CWebDavEventLog::_name($object, $action);		
		CEventLog::Log("NOTICE", $type, "webdav", $id, $description);
	}

	function InitLogEvents(&$webdav)
	{
		static $loaded = false;

		if ($webdav->Type === "iblock" && !$loaded)
		{
			$loaded = true;
			$this->iblock_id = $webdav->IBLOCK_ID;

			$event_names = array_keys($this->events);
			foreach ($event_names as $k)
				AddEventHandler("webdav", $k, array(&$this, $k));
		}
	}

	static function _name($object, $action='')
	{
		if (is_array($object))
		{
			$action = $object['action'];
			$object = $object['object'];
		}
		return "WEBDAV_".strtoupper($object)."_".strtoupper($action);
	}

	function MakeSectionURL($IBlockSectionID)
	{
		$rsIBlock = CIBlockSection::GetList(array(), array("=ID"=>$IBlockSectionID), false, array("IBLOCK_SECTION_ID", "NAME"));
		if ($arIBlock = $rsIBlock->GetNext())
		{			
			if ($arIBlock["IBLOCK_SECTION_ID"] == NULL)
			{
				$this->SectionURL.= $arIBlock["NAME"];
				return;
			}
			$this->MakeSectionURL($arIBlock["IBLOCK_SECTION_ID"]);
			$this->SectionURL.="/".$arIBlock["NAME"];	
		}
	}
	
	function __call($name, $arguments) 
	{
		if (isset($this->events[$name]))	
		{	
			$eventParams =& $arguments['0'];
			$arEvent = $this->events[$name];
			$description['ID'] = $eventParams['ELEMENT']['id'];
			if ($name == "OnFileRename" || $name == "OnFolderRename")
			{
				$description["NAME_BEFORE"] = $eventParams['ELEMENT']['name'];
				$description['NAME'] = $eventParams['OPERATION']['TO'];
			}
			else
				$description['NAME'] = $eventParams['ELEMENT']['name'];

			$description['URL'] = $eventParams['ELEMENT']['url'];
			$res = CIBlock::GetByID($this->iblock_id);
			if ($arEvent['object'] == 'ELEMENT')
			{		
				if($ar_res = $res->GetNext())
					$description['ELEMENT_NAME'] = $ar_res['ELEMENT_NAME'];
				$rsIBlock = CIBlockElement::GetList(array(), array("=ID"=>$description['ID']), false, false, array("LIST_PAGE_URL"));
				$arIBlock = $rsIBlock->GetNext();
			}
			else
			{
				if($ar_res = $res->GetNext())
					$description['SECTION_NAME'] = $ar_res['SECTION_NAME'];
				$rsIBlock = CIBlockSection::GetList(array(), array("=ID"=>$description['ID']), false, array("LIST_PAGE_URL"));
				$arIBlock = $rsIBlock->GetNext();
			}
			$description ["IBLOCK_PAGE_URL"] = $arIBlock["LIST_PAGE_URL"];
			$description['IBLOCK_NAME'] = $ar_res['NAME'];			
			if ($name == "OnFolderMove" || $name == "OnFileMove")
			{
				$this->SectionURL = "";
				$this->MakeSectionURL($eventParams['OPERATION']['TO']);
				$description['MOVE_TO'] = $this->SectionURL;
				if ($description['MOVE_TO'] == "")
					$description['MOVE_TO'] = $description['IBLOCK_NAME'];
			}
			
			$this->Log($arEvent['object'], $arEvent['action'], $this->iblock_id, serialize($description)); 
		}
	}
}

class CEventWebDav
{
	function MakeWebDavObject()
	{
		$obj = new CEventWebDav;
		return $obj;
	}

	function GetFilter()
	{
		$arFilter["DOCUMENTS"] = GetMessage("LOG_TYPE_DOCUMENTS");		
		return	$arFilter;
	}
	
	function GetAuditTypes()
	{
		return array(
			"WEBDAV_ELEMENT_DELETE" => "[WEBDAV_ELEMENT_DELETE] ".GetMessage("LOG_TYPE_ELEMENT_DELETE"),
			"WEBDAV_SECTION_DELETE" => "[WEBDAV_SECTION_DELETE] ".GetMessage("LOG_TYPE_SECTION_DELETE"),
			"WEBDAV_ELEMENT_TRASH" => "[WEBDAV_ELEMENT_TRASH] ".GetMessage("LOG_TYPE_ELEMENT_TRASH"),
			"WEBDAV_SECTION_TRASH" => "[WEBDAV_SECTION_TRASH] ".GetMessage("LOG_TYPE_SECTION_TRASH"),
			"WEBDAV_ELEMENT_RESTORE" => "[WEBDAV_ELEMENT_RESTORE] ".GetMessage("LOG_TYPE_ELEMENT_RESTORE"),
			"WEBDAV_SECTION_RESTORE" => "[WEBDAV_SECTION_RESTORE] ".GetMessage("LOG_TYPE_SECTION_RESTORE"),
			"WEBDAV_ELEMENT_MOVE" => "[WEBDAV_ELEMENT_MOVE] ".GetMessage("LOG_TYPE_ELEMENT_MOVE"),
			"WEBDAV_SECTION_MOVE" => "[WEBDAV_SECTION_MOVE] ".GetMessage("LOG_TYPE_SECTION_MOVE"),
			"WEBDAV_ELEMENT_RENAME" => "[WEBDAV_ELEMENT_RENAME] ".GetMessage("LOG_TYPE_ELEMENT_RENAME"),
			"WEBDAV_SECTION_RENAME" => "[WEBDAV_SECTION_RENAME] ".GetMessage("LOG_TYPE_SECTION_RENAME"),
			"WEBDAV_ELEMENT_ADD" => "[WEBDAV_ELEMENT_ADD] ".GetMessage("LOG_TYPE_ELEMENT_ADD"),
			"WEBDAV_SECTION_ADD" => "[WEBDAV_SECTION_ADD] ".GetMessage("LOG_TYPE_SECTION_ADD"),
			"WEBDAV_ELEMENT_UPDATE" => "[WEBDAV_ELEMENT_UPDATE] ".GetMessage("LOG_TYPE_ELEMENT_UPDATE") 
		); 
	}
	
	function GetEventInfo($row)
	{
		$DESCRIPTION = unserialize($row['DESCRIPTION']);

		$IblockURL = '';
		if (isset($DESCRIPTION['URL']) && !empty($DESCRIPTION['URL']))
			$IblockURL = $DESCRIPTION['URL'];

		if (strpos($row['AUDIT_TYPE_ID'], "SECTION"))
		{
			if (empty($IblockURL) && isset($DESCRIPTION["ID"]))
			{
				$rsElement = CIBlockSection::GetList(array(), array("=ID"=>$DESCRIPTION["ID"]), false,	array("SECTION_PAGE_URL"));
				if ($arElement = $rsElement->GetNext())
					$IblockURL = $arElement["SECTION_PAGE_URL"];
			}
			switch($row['AUDIT_TYPE_ID'])
			{	
				case "WEBDAV_SECTION_ADD":
					$EventPrint = GetMessage("LOG_WEBDAV_SECTION_ADD");
					break;
				case "WEBDAV_SECTION_DELETE":
					$EventPrint = GetMessage("LOG_WEBDAV_SECTION_DELETE");
					break;
				case "WEBDAV_SECTION_TRASH":
					$EventPrint = GetMessage("LOG_WEBDAV_SECTION_TRASH");
					break;
				case "WEBDAV_SECTION_RESTORE":
					$EventPrint = GetMessage("LOG_WEBDAV_SECTION_RESTORE");
					break;	
				case "WEBDAV_SECTION_MOVE":
					$EventPrint = GetMessage("LOG_WEBDAV_SECTION_MOVE", array("#MOVE_TO#" => $DESCRIPTION['MOVE_TO']));
					break;
				case "WEBDAV_SECTION_RENAME":
					$EventPrint = GetMessage("LOG_WEBDAV_SECTION_RENAME", array("#NAME_BEFORE#" => $DESCRIPTION['NAME_BEFORE']));
					break;		
			}
		}
		else
		{
// elements
			if (empty($IblockURL) && isset($DESCRIPTION["ID"]))
			{
				$rsElement = CIBlockElement::GetList(array(), array("=ID"=>$DESCRIPTION["ID"]), false, false, array("DETAIL_PAGE_URL"));
				if ($arElement = $rsElement->GetNext())
					$IblockURL = $arElement["DETAIL_PAGE_URL"];
			}
			switch($row['AUDIT_TYPE_ID'])
			{	
				case "WEBDAV_ELEMENT_ADD":
					$EventPrint = GetMessage("LOG_WEBDAV_ELEMENT_ADD");
					break;
				case "WEBDAV_ELEMENT_DELETE":
					$EventPrint = GetMessage("LOG_WEBDAV_ELEMENT_DELETE");
					break;
				case "WEBDAV_ELEMENT_TRASH":
					$EventPrint = GetMessage("LOG_WEBDAV_ELEMENT_TRASH");
					break;;
				case "WEBDAV_ELEMENT_RESTORE":
					$EventPrint = GetMessage("LOG_WEBDAV_ELEMENT_RESTORE");
					break;
				case "WEBDAV_ELEMENT_MOVE":
					$EventPrint = GetMessage("LOG_WEBDAV_ELEMENT_MOVE", array("#MOVE_TO#" => $DESCRIPTION['MOVE_TO']));
					break;	
				case "WEBDAV_ELEMENT_RENAME":
					$EventPrint = GetMessage("LOG_WEBDAV_ELEMENT_RENAME", array("#NAME_BEFORE#" => $DESCRIPTION['NAME_BEFORE']));
					break;	
				case "WEBDAV_ELEMENT_UPDATE":
					$EventPrint = GetMessage("LOG_WEBDAV_ELEMENT_UPDATE");
					break;
			}		
		}

		// iblock path		
		if (isset($DESCRIPTION["IBLOCK_PAGE_URL"]))
			$resIblock = "<a href =".$DESCRIPTION["IBLOCK_PAGE_URL"].">".$DESCRIPTION['IBLOCK_NAME']." (".$row["SITE_ID"].")</a>";
		else
			$resIblock = $DESCRIPTION['IBLOCK_NAME'];

		return array(
					"eventType" => $EventPrint,
					"eventName" => $DESCRIPTION['NAME'],
					"eventURL" => $IblockURL, 
					"pageURL" => $resIblock
				);	   
	}
	
	function GetFilterSQL($var)
	{
		$ar[] = array("MODULE_ID" => "webdav");
		return $ar;
	}
}