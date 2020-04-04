<?
##############################################
# Bitrix Site Manager WebDav				 #
# Copyright (c) 2002-2010 Bitrix			 #
# http://www.bitrixsoft.com					 #
# mailto:admin@bitrixsoft.com				 #
##############################################
IncludeModuleLangFile(__FILE__);
class CWebDavVirtual extends CWebDavBase
{
	var $Type = "virtual"; 

	var $allow = array(
		"POST"		=> array("rights" => "U", "min_rights" => "R"),
		"HEAD"		=> array("rights" => "R", "min_rights" => "R"),
		"OPTIONS"	=> array("rights" => "A", "min_rights" => "A"),
		"PROPPATCH"	=> array("rights" => "U", "min_rights" => "U"),
		"PROPFIND"	=> array("rights" => "R", "min_rights" => "R"),
		"GET"		=> array("rights" => "R", "min_rights" => "R"),
		"PUT"		=> array("rights" => "U", "min_rights" => "U"),
		"LOCK"		=> array("rights" => "U", "min_rights" => "U"),
		"MOVE"		=> array("rights" => "W", "min_rights" => "U"),
		"COPY"		=> array("rights" => "W", "min_rights" => "U"),
		"UNLOCK"	=> array("rights" => "U", "min_rights" => "U"),
		"MKCOL"		=> array("rights" => "W", "min_rights" => "W"),
		"DELETE"	=> array("rights" => "W", "min_rights" => "U"),
	);
	
	var $preg_modif = "i";

	var $iblock_permission = "D";
	
	var $iblock_permission_real = "D";
	
	var $check_creator = false;
	
	var $arStructure = array();

	
	var $permission_real = "R";

	function CWebDavVirtual($arStructure, $base_url, $arParams = array())
	{
		$arParams = (is_array($arParams) ? $arParams : array());
		$this->CWebDavBase($base_url);
		
		$this->arStructure = $arStructure;
		$this->permission =  $this->permission_real ;
		if ($this->permission_real >= "W")
			$this->check_creator = false;
		$this->USER["GROUPS"] = $GLOBALS["USER"]->GetUserGroupArray();
		$this->workflow = false;

		if(!isset($arParams["CACHE_TIME"]))
			$arParams["CACHE_TIME"] = 3600;
		if ($arParams["CACHE_TYPE"] == "Y" || ($arParams["CACHE_TYPE"] == "A" && COption::GetOptionString("main", "component_cache_on", "Y") == "Y"))
			$arParams["CACHE_TIME"] = intval($arParams["CACHE_TIME"]);
		else
			$arParams["CACHE_TIME"] = 0;

		$this->CACHE_TIME = $arParams["CACHE_TIME"] = 0; 
		$cache_hash = md5(serialize($arStructure));
		$this->CACHE_PATH = str_replace(array("///", "//"), "/", "/".SITE_ID."/webdav/".$cache_hash."/"); 
		$this->CACHE_OBJ = false; 
		if ($this->CACHE_TIME > 0)
		{
			$this->CACHE_OBJ = new CPHPCache;
		}
		
		if (!$this->SetRootSection($arParams["ROOT_SECTION_ID"]))
		{
			$this->arError[] = array(
				"id" => "root_section_is_not_found", 
				"text" => GetMessage("WD_ROOT_SECTION_NOT_FOUND")); 
		}
	}
	
	function PROPPATCH(&$options)
	{
		return "403 Forbidden";
	}

	function PROPFIND(&$options, &$files, $arParams = array())
	{
		global $DB;
		$files["files"] = array();
		$arParams = (is_array($arParams) ? $arParams : array());
		$this->IsDir($options);
		$arParamsIsDir = $this->arParams; 
		$arResult = array("NAV_RESULT" => false, "RESULT" => array()); 

		if ($this->arParams["not_found"] === true)
		{
			return false;
		}
		elseif($this->arParams["is_dir"] == true) // virtual files not supported now
		{
			$files["files"]["section"] = $this->_get_fileinfo($this->arParams["item_id"]);
			if (!empty($this->arParams["item_id"]) && $this->arParams["item_id"] != "/")
				$arResult["SECTION"] = array("ID" => $this->arParams["item_id"], "NAME" => $this->arParams["item_id"]); 
			
			if (!empty($options["depth"]))
			{
				$foundID = false;
				foreach ($this->arStructure as $nodeID=>$arNode) // at first find the current 'root'
				{
					if ($arNode['PATH'] == $this->arParams["item_id"]) 
					{
						$foundID = $nodeID;
						break;
					}
				}
				$year = date("Y");
				$curNodeID = $foundID;
				$curDepth = $this->arStructure[$foundID]['DEPTH_LEVEL'];
				while (true)
				{
					$curNodeID++;
					if (!isset($this->arStructure[$curNodeID])) break;
					$arNode = $this->arStructure[$curNodeID];
					if ($arNode["DEPTH_LEVEL"]-1 == $curDepth)	 // children
					{ 
						$filename = $this->arStructure[$curNodeID]["NAME"];
						$res = array(
							"TYPE" => "FOLDER", 
							"ID" => $filename, 
							"NAME" => $filename, 
							"~TIMESTAMP_X" => isset($this->arStructure[$curNodeID]['TIMESTAMP_X'])?$this->arStructure[$curNodeID]['TIMESTAMP_X']:ConvertTimeStamp(mktime(0, 0, 0, 1, 1, $year), "FULL"), 
							"PERMISSION" => $this->permission, 
							"SHOW" => array(), 
							"PATH" => $this->arStructure[$curNodeID]["PATH"]); 
						$res["TIMESTAMP_X"] = $res["~TIMESTAMP_X"]; 
						$arResult["RESULT"][($res["TYPE"] == "FOLDER" ? "S" : "E").$filename] = $res; 
						$files['files'][] = $this->_get_fileinfo($filename);
					}
					elseif ($arNode["DEPTH_LEVEL"] <= $curDepth) // same level or upper level, break
					{	
						break; 
					}
				}
			}
		}

		if ($arParams["return"] == "array" || $arParams["return"] == "nav_result")
		{
			if ($arParams["return"] == "array")
				return $arResult; 
			$arResult["NAV_RESULT"] = new CDBResult(); 
			$arResult["NAV_RESULT"]->InitFromArray($arResult["RESULT"]); 
			return $arResult; 
		}
		
		return true; 
	}



	function GET(&$options)
	{
		return "403 Forbidden";
	}

	function PUT(&$options)
	{
		return "403 Forbidden";
	}

	function put_commit(&$options)
	{
		return false;
	}

	function LOCK(&$options)
	{
		return "403 Forbidden";
	}

	function UNLOCK(&$options)
	{
		return "403 Forbidden";
	}

	function MOVE($options)
	{
		return "403 Forbidden";
	}

	function COPY($options, $drop = false)
	{
		return "403 Forbidden";
	}
	
	function copy_commit($arFrom, $arTo, $options, $drop = false)
	{
		return false; 
	}
	
	function MKCOL($options)
	{
		$this->MakeAccessDenied();
		return "403 Forbidden";
	}

	function DELETE($options)
	{
		$this->MakeAccessDenied();
		return "403 Forbidden";
	}
	
	function checkLock($path)
	{
		return false;
	}

	function IsDir($options = array(), $replace_symbols = false)
	{
		global $DB;
		$options = (is_array($options) ? $options : array());
		$path = $this->_udecode(array_key_exists("path", $options) ? $options["path"] : $this->_path);

		foreach ($this->arStructure as $arNode)
		{
			if ($arNode['DEPTH_LEVEL'] == 0)
				$path = str_replace($arNode['NAME'], $arNode['PATH'], $path);
		}
		$path = str_replace(array('////', '///','//'),'/', $path);

		if (is_set($options, "section_id"))
			$path = $options["section_id"]; 
		elseif (is_set($options, "element_id"))
			$path = $options["element_id"]; 
		if (substr($path, 0, 1) != "/")
			$path = "/".$path; 
		$id = md5($path);

		if (in_array($id, $this->CACHE))
		{
			$this->arParams = array_merge($this->arParams, $this->CACHE[$id]);
			return $this->arParams["is_dir"];
		}
		$path_copy = $path;
		
		$path = str_replace(array("////", "///", "//"), "/", $this->real_path_full."/".$path); 
		$params = array(
			"item_id" => "/", 
			"not_found" => false, 
			"is_dir" => false, 
			"is_file" => false, 
			"parent_id" => false, 
			"base_name" => substr(strrchr($path , '/'), 1)); 
		
		$res = explode("/", $path_copy); 
		// only folders supported right now !
		$found = false;
		foreach ($this->arStructure as $nodeID => $arNode)
		{
			if ($arNode["PATH"] == $path) {
				$found = $nodeID;
				break;
			}
		}
		if ($found !== false)
		{
			$params["item_id"] = $path_copy;
			$params["is_dir"] = true;
			$res = explode('/', $this->arStructure[$found]['PATH']); 
			$name = array_pop($res);
			if ($name)
			{
				$params["dir_array"] = array(
					"ID" => $found, 
					"NAME" => $name);
				$params["parent_id"] = implode("/", $res); 
			}
		}
		else 
		{
			array_pop($res); 
			$params["not_found"] = true;
			$params["parent_id"] = implode("/", $res); 
		}
		
		$this->arParams = $params;
		$this->CACHE[$id] = $params;
		return $params["is_dir"];
	}

	function IsLocked($ID, $IBLOCK_ID, &$params)
	{
		return false;
	}

	function SetRootSection($id)
	{
		$this->arRootSection['ID'] = $id;
		return true;
	}
	
	function CheckUniqueName($basename, $section_id, &$res)
	{
		return true;
	}

	function _get_fileinfo($path)
	{
		$fspath = $path;
		if (!empty($this->real_path_full))
		{
			if (strpos($path, $this->real_path_full) === false)
				$fspath = str_replace(array("///", "//"), "/", $this->real_path_full."/".$path);
			else 
				$path = str_replace(array($this->real_path_full, "///", "//"), "/", $path);
		}
		
		$path = str_replace(array('////', '///','//'),'/', $path);

		$info = array();
		$info['path'] = $this->_slashify($path);
		if (SITE_CHARSET != "UTF-8")
		{
			$info['path'] = $GLOBALS["APPLICATION"]->ConvertCharset($info['path'], SITE_CHARSET, "UTF-8");
		}
		$info['props'] = array();
		$year = date("Y");

		$info['props'][] = $this->_mkprop('resourcetype', 'collection');
		$info['props'][] = $this->_mkprop('getcontenttype', 'httpd/unix-directory');
		
		return $info;
	}

	/*********************************************************************************/
	function GetNavChain($options = array(), $for_url = false)
	{
		static $nav_chain = array(); 
		$for_url = ($for_url === true); 
		$id = md5(serialize($options)); 
		
		if (!is_set($nav_chain, $id))
		{
			if ($this->CACHE_OBJ && $this->CACHE_OBJ->InitCache($this->CACHE_TIME, $id, $this->CACHE_PATH."nav_chain"))
			{
				$nav_chain[$id] = $this->CACHE_OBJ->GetVars();
			}
			else 
			{
				$this->IsDir($options);
				$nav_chain[$id] = array("URL" => array(), "SITE" => array()); 
				if ($this->arParams["not_found"] == false && !empty($this->arParams["item_id"]))
				{
					$res = explode("/", $this->arParams["item_id"]); 
					if (empty($res) && !empty($this->arParams["item_id"]))
						$res = array($this->arParams["item_id"]); 

					foreach ($res as $val)
					{
						if (empty($val))
							continue; 
						if (SITE_CHARSET != "UTF-8")
							$nav_chain["Y".$id][] = $GLOBALS["APPLICATION"]->ConvertCharset($val, SITE_CHARSET, "UTF-8");
						else
							$nav_chain["Y".$id][] = $val;
						$nav_chain["N".$id][] = $val;
					}

					//$arFile = array(); 
					//$section_id = $this->arParams["item_id"];
					//if ($this->arParams["is_file"])
					//{
						//$arFile = $this->arParams["element_array"]; 
						//$section_id = $arFile["IBLOCK_SECTION_ID"]; 
					//}
					//$db_res = CIBlockSection::GetNavChain($this->IBLOCK_ID, $section_id);
					//$bRootFounded = (empty($this->arRootSection) ? true : false);
					//while($res = $db_res->Fetch())
					//{
						//if (!$bRootFounded && $res["ID"] == $this->arRootSection["ID"])
						//{
							//$bRootFounded = true;
							//continue;
						//}
						//if (!$bRootFounded)
							//continue;
						
						//$nav_chain[$id]["URL"][] = $this->_uencode($res["NAME"], array("utf8" => "Y", "convert" => "full"));
						//$nav_chain[$id]["SITE"][] = $res["NAME"];
					//}
					//if (!empty($arFile))
					//{
						//$nav_chain[$id]["URL"][] = $this->_uencode($res["NAME"], array("utf8" => "Y", "convert" => "full"));
						//$nav_chain[$id]["SITE"][] = $arFile["NAME"];
					//}
				}
				if ($this->CACHE_OBJ)
				{
					$this->CACHE_OBJ->StartDataCache($this->CACHE_TIME, $id, $this->CACHE_PATH."_nav_chain");
					$this->CACHE_OBJ->EndDataCache($nav_chain[$id]);
				}
			}
		}
		$res = $nav_chain[($for_url ? "Y" : "N").$id]; 
		return (is_array($res) ? $res : array()); 
		return array(); 
	}

	
	/*********************************************************************************/
	function GetSectionsTree($options = array())
	{
		return false;
	}
	
	function ClearCache($path)
	{
		if ($path == "section")
		{
			BXClearCache(true, $this->CACHE_PATH."root_section"); 
			BXClearCache(true, $this->CACHE_PATH."section"); 
			BXClearCache(true, $this->CACHE_PATH."sections_tree"); 
			BXClearCache(true, $this->CACHE_PATH."nav_chain"); 
		}
	}

	function CheckRight($IBlockPermission, $permission)
	{
		if (is_array($IBlockPermission))
			return (isset($IBlockPermission[$permission]) ? 'Z' : 'A');
		else
			return $IBlockPermission;
	}
	
	function CheckRights($method = "", $strong = false, $path = "")
	{
		$result = true; 
		if (!parent::CheckRights($method, $strong))
		{
			$result = false; 
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage('WD_ACCESS_DENIED'), 'ACCESS_DENIED');
		}
		elseif (!empty($path))
		{
			$path = $this->_udecode($path);
			$strFileName = basename($path); 
			$extention = strtolower(strrchr($strFileName, '.')); 
			if (in_array($method, array("COPY", "MOVE", "PUT")))
			{
				if (IsFileUnsafe($strFileName) || $strFileName == "index.php")
				{
					$result = false; 
					$GLOBALS['APPLICATION']->ThrowException(GetMessage("WD_FILE_ERROR14"), "FORBIDDEN_NAME"); 
				}
			}
		}
		return $result; 
	}

	function CheckWebRights($method = "", $arParams = array(), $simple = true)
	{
		$strong = ($method !== "");

		$path = '';
		if (is_array($arParams['arElement']))
			$path = (isset($arParams['arElement']['item_id']) ? $arParams['arElement']['item_id'] : '');
		elseif (is_string($arParams['arElement']))
			$path = $arParams['arElement'];
		$result = $this->CheckRights($method, $strong, $path);
		//if ((! $result) || $simple) 
			return $result;
	}

}