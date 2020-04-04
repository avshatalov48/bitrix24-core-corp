<?php
##############################################
# Bitrix Site Manager WebDav #
# Copyright (c) 2002-2010 Bitrix #
# http://www.bitrixsoft.com #
# mailto:admin@bitrixsoft.com #
##############################################
IncludeModuleLangFile(__FILE__);

class CWebDavFile extends CWebDavBase
{
	var $base_url;

	var $Type = "folder";

	var $real_path = "";

	var $real_path_full = "";

	var $arFilePermissions = array(
		"WRITE" => array(
			".php" => "X",
			".htaccess" => "X",
			".html" => "X",
			".htm" => "X",
			".mht" => "X",
			".xhtml" => "X",
			".js" => "X"),
		"READ" => array(
			".php" => "X",
			".htaccess" => "X")
		);
	var $arFileForbiddenExtentions = array(
		"WRITE" => array(),
		"READ" => array()
		);
	var $allow = array(
		"POST"		=> array("rights" => "U", "min_rights" => "R"),
		"HEAD"		=> array("rights" => "R", "min_rights" => "R"),
		"OPTIONS"	=> array("rights" => "A", "min_rights" => "A"),
		"PROPPATCH"	=> array("rights" => "U", "min_rights" => "U"),
		"PROPFIND"	=> array("rights" => "R", "min_rights" => "R"),
		"GET"		=> array("rights" => "R", "min_rights" => "R"),
		"PUT"		=> array("rights" => "U", "min_rights" => "U"),
		"LOCK"		=> array("rights" => "U", "min_rights" => "U"),
		"UNLOCK"	=> array("rights" => "U", "min_rights" => "U"),
		"MOVE"		=> array("rights" => "W", "min_rights" => "U"),
		"COPY"		=> array("rights" => "W", "min_rights" => "U"),
		"MKCOL"		=> array("rights" => "U", "min_rights" => "U"),
		"DELETE"	=> array("rights" => "W", "min_rights" => "U"),
		"UNDELETE"	=> array("rights" => "W", "min_rights" => "W"),
	);

	function CWebDavFile($arParams, $base_url)
	{
		$io = self::GetIo();
		$arParams = (is_array($arParams) ? $arParams : array());
		$this->RegisterVirtualIOCompatibility();

		$arParams["FOLDER"] = $io->CombinePath("/", trim($arParams["FOLDER"]));
		$this->real_path = $arParams["FOLDER"];
		$this->real_path_full = $io->CombinePath($_SERVER['DOCUMENT_ROOT'], $arParams["FOLDER"]);

		$this->CWebDavBase($base_url);

		if (! $io->DirectoryExists($this->real_path_full))
		{
			$this->arError[] = array(
				"id" => "folder is not exists",
				"text" => GetMessage("WD_FILE_ERROR1"));
		}
		elseif (substr($this->real_path, 0, 7) == "/bitrix")
		{
			$this->arError[] = array(
				"id" => "forbidden folder",
				"text" => GetMessage("WD_FILE_ERROR15"));
		}

		$this->permission = $GLOBALS['APPLICATION']->GetFileAccessPermission($arParams["FOLDER"]);

		foreach ($this->arFilePermissions as $right => $perms)
		{
			foreach ($this->arFilePermissions[$right] as $ext => $perms)
			{
				if ($this->permission < $perms)
				{
					$this->arFileForbiddenExtentions[$right][] = $ext;
				}
			}
		}

		if (!$GLOBALS["USER"]->IsAdmin())
		{
			$res = GetScriptFileExt();
			foreach ($res as $ext)
			{
				$this->arFileForbiddenExtentions["WRITE"][] = ".".$ext;
			}
		}

		$this->workflow = false;
	}

	function RegisterVirtualIOCompatibility($baseDir='')
	{
		static $IOCompartible = 'BX_IO_Compartible';
		if (defined($IOCompartible))
			return false;

		define($IOCompartible, 'Y');
		AddEventHandler('main', 'BXVirtualIO_ConvertCharset', array($this, 'VirtualIO_ConvertCharset'));

		return true;
	}

	function VirtualIO_ConvertCharset($arParams=array())
	{
		static $resultCache = array();
		static $bxLang = null;
		$io = self::GetIo();

		if ($bxLang === null) // from VirtualIO
		{
			if (defined('BX_UTF'))
				$bxLang = "utf-8";
			elseif (defined("SITE_CHARSET") && (strlen(SITE_CHARSET) > 0))
				$bxLang = SITE_CHARSET;
			elseif (defined("LANG_CHARSET") && (strlen(LANG_CHARSET) > 0))
				$bxLang = LANG_CHARSET;
			else
				$bxLang = "windows-1251";

			$bxLang = strtolower($bxLang);
		}

		if (
			isset($arParams['original'])
			&& (strpos($arParams['original'], $this->real_path_full) === 0)
			&& ($arParams['converted'] !== $arParams['original'])
		)
		{
			if (!isset($resultCache[$arParams['original']]))
			{
				$libPath = substr($arParams['original'], strlen($this->real_path_full)+1);
				$libPath = explode('/', $libPath);

				$pathLength = sizeof($libPath);
				for ($i=0; $i<$pathLength; $i++)
				{
					$p = $libPath[$i];
					if ($i > 0)
					{
						$parent = array_slice($libPath, 0, $i);
						$parent = $io->CombinePath($this->real_path_full, implode('/', $parent));
					}
					else
					{
						$parent = $this->real_path_full;
					}


					if ($arParams['direction'] == CBXVirtualIoFileSystem::directionEncode) // bx to system
					{
						if (file_exists($io->CombinePath($parent, $p)))
						{
							$libPath[$i] = $p;
						}
						else
						{
							$pc = CBXVirtualIoFileSystem::ConvertCharset($p, CBXVirtualIoFileSystem::directionEncode, true);
							if (file_exists($io->CombinePath($parent, $pc)))
							{
								$libPath[$i] = $pc;
							}
							else
							{
								$libPath[$i] = $pc; // creating new ?
							}
						}
					}
					elseif ($arParams['direction'] == CBXVirtualIoFileSystem::directionDecode) /// system to bx
					{
						$decoded = CBXVirtualIoFileSystem::ConvertCharset($p, CBXVirtualIoFileSystem::directionDecode, true);
						$restored = CBXVirtualIoFileSystem::ConvertCharset($decoded, CBXVirtualIoFileSystem::directionEncode, true);
						if ($restored == $p)
						{
							$reverse = CBXVirtualIoFileSystem::ConvertCharset($p, CBXVirtualIoFileSystem::directionEncode, true);
							$restored_reverse = CBXVirtualIoFileSystem::ConvertCharset($reverse, CBXVirtualIoFileSystem::directionDecode, true);
							if (
								($restored_reverse == $p)
								&& ($bxLang != "windows-1251")
							)
							{
								$libPath[$i] = $p;
							}
							else
							{
								$libPath[$i] = $decoded;
							}
						}
						else // conversion failed, assume original was correct
						{
							$libPath[$i] = $p;
						}
					}
					else
					{
						die('invalid encoding direction');
					}
				}
				$resultCache[$arParams['original']] = $io->CombinePath($this->real_path_full, implode('/', $libPath));
			}
			return $resultCache[$arParams['original']];
		}
		return false;
	}

	function PROPPATCH(&$options)
	{
		$this->IsDir($options);
		if ($this->arParams["not_found"] === true)
		{
			return "404 Not Found";
		}
		$res = $this->_get_props($this->arParams["item_id"]);
		foreach ($options["props"] as $key => $prop)
		{
			if ($prop["ns"] == "DAV:")
			{
				$options["props"][$key]['status'] = "403 Forbidden";
			}
			else
			{
				if (isset($prop["val"]))
				{
					$res[$prop["name"].$prop["ns"]] = array(
						"name" => $prop["name"],
						"ns" => $prop["ns"],
						"value" => $prop["val"]);
				}
				else
				{
					unset($res[$prop["name"].$prop["ns"]]);
				}
			}
		}
		$this->_set_props($this->arParams["item_id"], $res);
		return "";
	}

	function CheckUniqueName($basename, $section_id, &$res)
	{
		$section_id = trim($section_id);
		$basename = trim($basename);
		if (empty($basename))
			return false;
		$this->IsDir(array("path" => $section_id . "/" . $basename));
		return $this->arParams["not_found"];
	}


	function _getShowParams($res)
	{
		$result = array();

		if ($this->permission > "W")
		{
			$result = array(
				"EDIT" => "Y",
				"DELETE" => "Y",
			);

			if ($res['LOCK_STATUS'] == 'green')
			{
				$result['LOCK'] = 'Y';
			}
			else
			{
				$result['UNLOCK'] = 'Y';
			}

			if (isset($res["PROPS"]["UNDELETEBX:"])) // in trash
			{
				$res["SHOW"]["UNDELETE"] = "Y";
			}
		}
		elseif ($this->permission >= "W")
		{
			$result = array(
				"EDIT" => "Y",
				"DELETE" => "Y"
			);

			if ($res['LOCK_STATUS'] == 'green')
			{
				$result['LOCK'] = 'Y';
			}
			elseif ($res['LOCK_STATUS'] == 'yellow')
			{
				$result['UNLOCK'] = 'Y';
			}

			if (isset($res["PROPS"]["UNDELETEBX:"])) // in trash
			{
				$result["UNDELETE"] = "Y";
				$result["DELETE"] = "N";
			}
		}

		return $result;
	}

	function PROPFIND(&$options, &$files, $arParams = array())
	{
		global $by, $order, $USER;
		$io = self::GetIo();
		if (!function_exists("__sort_array_folder_and_file"))
		{
			function __sort_array_folder_and_file($res1, $res2)
			{
				global $by, $order;
				InitSorting();
				if (empty($by))
				{
					$by = "NAME"; $order = "ASC";
				}
				$by = strtoupper($by);
				$order = strtoupper($order);

				if ($res1["~TYPE"] == "FOLDER" && $res2["~TYPE"] == "FILE")
					return -1;
				elseif ($res1["~TYPE"] == "FILE" && $res2["~TYPE"] == "FOLDER")
					return 1;
				else
				{
					$by = (is_set($res1, $by) ? $by : "NAME");

					$ord = $order;
					if ($by == "TIMESTAMP_X")
						$ord = ($order == "ASC" ? "DESC" : "ASC");

					if ($ord == "ASC")
						return ($res1[$by] < $res2[$by] ? -1 : 1);
					else
						return ($res1[$by] < $res2[$by] ? 1 : -1);
				}
			}
		}
		$this->IsDir($options);
		$files['files'] = array();
		$arResult = array("NAV_RESULT" => false, "RESULT" => array());

		if (empty($options["FILTER"]))
		{
			if ($this->arParams["not_found"] === true)
			{
				return false;
			}
			elseif($this->arParams["is_dir"] != true)
			{
				//$files["files"]["E".$res["ID"]] = $this->_get_fileinfo($this->arParams["item_id"]);
				$files["files"]["E"] = $this->_get_fileinfo($this->arParams["item_id"]);
			}
			else
			{
				$files["files"]["section"] = $this->_get_fileinfo($this->arParams["item_id"]);

				if (
					!empty($this->arParams["item_id"])
					&& $this->arParams["item_id"] != "/"
				)
					$arResult["SECTION"] = array("ID" => $this->arParams["item_id"], "NAME" => $this->arParams["item_id"]);

				//$path = $this->_slashify($io->CombinePath($this->real_path_full, $this->arParams["item_id"]));
				$path = CWebDavBase::CleanRelativePathString($this->arParams["item_id"], $this->real_path_full);
				if($path === false)
				{
					return false;
				}
				$path = $this->_slashify($path);

				if (!empty($options["depth"]))
				{
					$dir = $io->GetDirectory($path);
					if ($dir->IsExists())
					{
						$this->arParams["item_id"] = $this->_slashify(str_replace("//", "/", $this->arParams["item_id"]));

						$tzOffset = CTimeZone::GetOffset();

						$arChildren = $dir->GetChildren();
						foreach($arChildren as $node)
						{

							$filename = $node->GetName();
							$filePath = $io->CombinePath($this->arParams["item_id"], $filename);

							$res = array(
								"~TYPE" => "FOLDER",
								"TYPE" => "S",
								"ID" => $filePath,
								"NAME" => $filename,
								"TIMESTAMP_X" => $node->GetModificationTime() + $tzOffset,
								"PERMISSION" => $this->permission,
								"PATH" => $filePath,
								"REAL_PATH" => $path.$filename,
								"FILE_SIZE" => 0
							);

							if ($this->MetaNames($res))
							{
								if (! $node->IsDirectory())
								{
									$ext = strtolower(strrchr($filename , '.'));
									if (in_array($ext, $this->arFileForbiddenExtentions["READ"]))
										continue;

									$res["~TYPE"] = "FILE";
									$res["TYPE"] = "E";
									$res["LOCK_STATUS"] = "green";
									$res["EXTENTION"] = $ext;
									$res["FILE_SIZE"] = $node->GetFileSize();

									$res["FILE_ARRAY"] = array(
										"TIMESTAMP_X" => $res["TIMESTAMP_X"],
										"MODULE_ID" => "webdav",
										"HEIGHT" => 0,
										"WIDTH" => 0,
										"FILE_SIZE" => $res["FILE_SIZE"],
										"CONTENT_TYPE" => ($node->IsReadable() ? $this->_mimetype($path.$filename) : 'application/x-non-readable'),
										"SUBDIR" => $io->CombinePath("/", $this->real_path, $this->arParams["item_id"]),
										"FILE_NAME" => $filename,
										"ORIGINAL_NAME" => $filename,
										"DESCRIPTION" => ""
									);
								}
								$res["PROPS"] = $this->_get_props($filePath);

								$res["LOCK_STATUS"] = 'green';
								if (is_array($res['PROPS']['LOCK']))
								{
									$userLogin = $GLOBALS['USER']->GetLogin();
									$now = time();
									foreach($res['PROPS']['LOCK'] as $arLock)
									{
										if (
											$arLock['exclusivelock'] == 1
											&& $arLock['expires'] >= $now
											&& $arLock['created'] <= $now
										)
										{
											$res['LOCK_STATUS'] = (($userLogin == $arLock['owner']) ? 'yellow' : 'red');
											$rsUser = CUser::GetByLogin($arLock['owner']);
											$arUser = $rsUser->GetNext();
											$res['LOCKED_USER_NAME'] = '('.$arUser['LOGIN'].')';
											if (strlen($arUser['NAME']) > 0 && strlen($arUser['LAST_NAME']) > 0)
												$res['LOCKED_USER_NAME'] .= ' '.$arUser['NAME'].' '.$arUser['LAST_NAME'];
											break;
										}
									}
								}

								$res['SHOW'] = $this->_getShowParams($res);

								$arResult["RESULT"][($res["TYPE"] == "FOLDER" ? "S" : "E").$filename] = $res;

								$files['files'][] = $this->_get_fileinfo($this->arParams["item_id"].$filename);
							}
						}
					}
				}
			}
		}
		else // search
		{
			$arSearchResults = array();
			if (
				IsModuleInstalled('search')
				&& CModule::IncludeModule('search')
			)
			{
				$arSearchParams = array(
					"MODULE_ID" => "main",
					"URL" => $this->base_url.'%'
				);

				if (
					isset($options["FILTER"]["content"])
					&& strlen($options["FILTER"]["content"])>0
				)
				{
					$arSearchParams += array(
						"QUERY" => $options["FILTER"]["content"]
					);
				}

				$obSearch = new CSearch;
				$obSearch->Search($arSearchParams);
				if ($obSearch->errorno != 0)
				{
					$arResult["ERROR_MESSAGE"] = $obSearch->error;
				}
				else
				{
					while($arSearchResultItem = $obSearch->GetNext())
					{
						$arSearchResults[] = $arSearchResultItem['ITEM_ID'];
					}
				}

				$tzOffset = CTimeZone::GetOffset();

				foreach ($arSearchResults as $sSearchItem)
				{
					$file = array_pop(explode("|", $sSearchItem));

					$filename = GetFileName($file);
					$sFullFileName = $io->CombinePath($_SERVER['DOCUMENT_ROOT'], $file);
					if (strpos($sFullFileName, $this->real_path_full) === 0)
					{
						$filePath = CWebDavBase::ConvertPathToRelative($sFullFileName, $this->real_path_full);
					}

					$filePath = CWebDavBase::CleanRelativePathString($filePath, $this->real_path_full);
					if($filePath === false)
					{
						return false;
					}

					/*$sFullFileName = $io->CombinePath($_SERVER['DOCUMENT_ROOT'], $file);
					$filename = array_pop(explode("/", $file));
					$path = implode("/", array_slice(explode("/", $sFullFileName), 0 , -1)) . "/";
					$filePath = $io->CombinePath($path, $filename);*/

					$oFile = $io->GetFile($filePath);

					$res = array(
						"ID" => $file,
						"NAME" => $filename,
						"TIMESTAMP_X" => $oFile->GetModificationTime() + $tzOffset,
						"PERMISSION" => $this->permission,
						"PATH" => substr($file, strlen($this->real_path)),
						"REAL_PATH" => $filePath,
						"FILE_SIZE" => 0
					);
					$res['SHOW'] = $this->_getShowParams($res);

					if ($this->MetaNames($res))
					{
						$res["PROPS"] = $this->_get_props(substr($file, strlen($this->real_path)));
						if (!isset($res["PROPS"]["UNDELETEBX:"]))
						{
							if ($oFile->IsExists())
							{
								$ext = strtolower(strrchr($filename , '.'));
								if (in_array($ext, $this->arFileForbiddenExtentions["READ"]))
									continue;

								$fileSize = $oFile->GetFileSize();

								$res["~TYPE"] = "FILE";
								$res["TYPE"] = "E";
								$res["LOCK_STATUS"] = "green";
								$res["EXTENTION"] = $ext;
								$res["FILE_SIZE"] = $fileSize;
								$res["FILE_ARRAY"] = array(
									"TIMESTAMP_X" => $res["TIMESTAMP_X"],
									"MODULE_ID" => "webdav",
									"HEIGHT" => 0,
									"WIDTH" => 0,
									"FILE_SIZE" => $fileSize,
									"CONTENT_TYPE" => ($oFile->IsReadable() ? $this->_mimetype($filePath) : 'application/x-non-readable'),
									"SUBDIR" => implode("/", array_slice(explode("/", $file), 0 , -1)),
									"FILE_NAME" => $filename,
									"ORIGINAL_NAME" => $filename,
									"DESCRIPTION" => "");
							}
							$arResult["RESULT"][($res["TYPE"] == "FOLDER" ? "S" : "E").$filename] = $res;
						}
					}
				}
			}
		}

		if ($arParams["return"] == "nav_result" || $arParams["return"] == "array")
		{
			uasort($arResult["RESULT"], "__sort_array_folder_and_file");
			$arResult["NAV_RESULT"] = new CDBResult();
			$arResult["NAV_RESULT"]->InitFromArray($arResult["RESULT"]);
			$arResult["NAV_RESULT"] = new CDBResultWebDAVFiles($arResult["NAV_RESULT"]);

			return $arResult;
		}

		return true;
	}

	function GET(&$options)
	{
		$io = self::GetIo();
		if (count($this->arParams) <= 0)
		{
			$this->IsDir($options);
		}
		if ($this->arParams["not_found"])
		{
			return false;
		}
		elseif ($this->arParams["is_dir"])
		{
			return true;
		}

		$fspath = $options["path"];
		//if (strpos($fspath, $this->real_path_full) === false)
		//{
			//$fspath = $this->arParams["item_id"];
			//$fspath = $io->CombinePath($this->real_path_full, $this->arParams["item_id"]);
		//}
		if (strpos($fspath, $this->real_path_full) === 0)
		{
			$fspath = CWebDavBase::ConvertPathToRelative($fspath, $this->real_path_full);
		}
		$fspath = CWebDavBase::CleanRelativePathString($fspath, $this->real_path_full);
		if($fspath === false)
		{
			return false;
		}

		$oFile = $io->GetFile($fspath);

		$options["mimetype"] = $this->_mimetype($fspath);
		$options["mtime"] = $oFile->GetModificationTime() + CTimeZone::GetOffset();
		$options["size"] = $oFile->GetFileSize();
		$options["stream"] = $oFile->Open("rb");

		if (empty($options["mimetype"]))
			$options["mimetype"] = $this->get_mime_type($this->arParams["file_name"]);

		return true;
	}


	function Undelete($options)
	{
		$io = self::GetIo();
		if(!$this->CheckRights("UNDELETE", true, $options["path"]))
		{
			$this->ThrowAccessDenied();
			return "403 Forbidden";
		}

		if (! isset($options['dest_url']))
		{
			$arParams = $this->_get_props($options['path']);
			$options["dest_url"] = $arParams["UNDELETEBX:"]['value'];
		}

		if (! isset($options['dest_url']))
			return "404 Not Found";

		$this->IsDir(array("path" => $options["dest_url"]), $this->replace_symbols);
		$options["dest_url"] = $this->_udecode($options["dest_url"]);
		$arTo = $this->arParams;

		if ($arTo["not_found"])
		{
			do {
				$this->IsDir(array('path' => rtrim($this->arParams['parent_id'], '/')));
			} while (($this->arParams["parent_id"] != '') && ($this->arParams["not_found"]));

			if ($this->arParams['not_found'])
			{
				$options['dest_url'] = $io->CombinePath("/", $arTo['base_name']);
			}
			else
			{
				$options['dest_url'] = $io->CombinePath($this->arParams["item_id"], $arTo["base_name"]);
			}
		}

		$this->IsDir($options);
		$bIsDir = ($this->arParams['is_dir']);

		if ($this->arParams["not_found"] === true)
		{
			return "404 Not Found";
		}
		else
		{
			$result = $this->MOVE($options);
			if (in_array(intval($result), array(201, 204)))   // remove the "UNDELETE" in props
			{
				if (! $bIsDir)
				{
					$options["props"] = array(array("ns"=>"BX:","name"=>"UNDELETE"));
					$options["path"] = $options["dest_url"];
					$this->PROPPATCH($options);
				}
				else
				{
					$params = $this->GetFilesAndFolders($options['dest_url']);
					if (!empty($params))
					{
						$tmpParams = $this->arParams;
						sort($params, SORT_STRING);
						foreach ($params as $file)
						{
							$localpath = str_replace(array($this->real_path_full, "///", "//"), "/", $file);
							$arUndeleteOptions = array("props" => array( array("ns"=>"BX:", "name"=>"UNDELETE") ), "path" => $localpath);
							$this->PROPPATCH($arUndeleteOptions);
						}
						$this->arParams = $tmpParams;
					}
				}
				return "204 No Content";
			}
			else
			{
				return "404 Not Found";
			}
		}
	}

	function PUT(&$options)
	{
		$io = self::GetIo();
		$this->IsDir($options);
		if ($this->arParams["is_dir"] == true)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage('WD_FILE_ERROR14'), 'FORBIDDEN_NAME');
			return "409 Conflict";
		}
		$options["~path"] = $this->_udecode($options["path"]);
		if ($this->arParams["not_found"] && !$this->CheckRights("PUT", true, $this->arParams["base_name"]) ||
			!$this->arParams["not_found"] && !$this->CheckRights("PUT", true, $this->arParams["item_id"]))
		{
			$this->ThrowAccessDenied();
			return false;
		}

		/*$fspath = $io->CombinePath($this->real_path_full, $this->arParams["item_id"]);
		if ($this->arParams["not_found"])
		{
			$fspath = $io->CombinePath($this->real_path_full, $this->arParams["parent_id"], $this->arParams["base_name"]);
		}*/

		$fspath = $this->arParams["item_id"];
		if ($this->arParams["not_found"])
		{
			$fspath = $io->CombinePath($this->arParams["parent_id"], $this->arParams["base_name"]);
		}

		$fspath = CWebDavBase::CleanRelativePathString($fspath, $this->real_path_full);
		if($fspath === false)
		{
			return false;
		}

		$oFile = $io->GetFile($fspath);
		if (COption::GetOptionInt("main", "disk_space") > 0)
		{
			CDiskQuota::updateDiskQuota("file", $oFile->GetFileSize(), "delete");
		}

		$options["new"] = $this->arParams["not_found"];
		$options["fspath"] = $fspath;

		if ($options["fopen"] != "N")
		{
			$fp = $oFile->Open("w");
			return $fp;
		}
		return true;
	}

	function Reindex($file)
	{
		$io = self::GetIo();
		if (CModule::IncludeModule('search'))
		{
			$path = $file;
			if (strpos($file, $this->real_path_full) !== false)
			{
				$path = substr($file, strlen($this->real_path_full)+1);
			}
			$path = $io->CombinePath($this->real_path, $path);
			$url = $this->base_url . substr($file, strlen($this->real_path_full));

			if (strpos(strtolower(PHP_OS), 'win') !== false)
				usleep(1000); // pass thru windows write cache
			clearstatcache();

			$searchID = CSearch::ReIndexFile(Array(SITE_ID, $path));
			if (intval($searchID) > 0)
			{
				CSearch::Update($searchID, array('URL' => $url));
			}
		}
	}

	function put_commit(&$options)
	{
		$io = self::GetIo();
		$file = $options["~path"];
		/*if (strpos($file, $this->real_path_full) === false)
		{
			$file = $io->CombinePath($this->real_path_full, $file);
		}*/

		if (strpos($file, $this->real_path_full) === 0)
		{
			$file = CWebDavBase::ConvertPathToRelative($file, $this->real_path_full);
		}
		$file = CWebDavBase::CleanRelativePathString($file, $this->real_path_full);
		if($file === false)
		{
			return false;
		}

		$this->Reindex($file);

		if (COption::GetOptionInt("main", "disk_space") > 0)
		{
			$oFile = $io->GetFile($file);
			CDiskQuota::updateDiskQuota("file", $oFile->GetFileSize(), "add");
		}

		return true;
	}

	function checkLock($path)
	{
		$result = false;
		$is_dir = false;
		$ID = 0;
		$strProps = "";

		$this->IsDir(array("path" => $path));
		if ($this->arParams["not_found"] === true)
		{
			return "404 Not Found";
		}

		$arProps = $this->_get_props($this->arParams["item_id"]);

		if (
			!empty($arProps["LOCK"])
			&& is_array($arProps["LOCK"])
		)
		{
			$res = reset($arProps["LOCK"]);

			$result = array(
				"type"	  => "write",
				"scope"   => $res["exclusivelock"] ? "exclusive" : "shared",
				"depth"   => 0,
				"owner"   => $res["owner"],
				"token" => $res["token"],
				"created" => $res["created"],
				"modified" => $res["modified"],
				"expires" => $res["expires"]
			);
		}

		return $result;
	}

	function CheckRight($IBlockPermission, $permission)
	{
		if (is_array($IBlockPermission))
			return (isset($IBlockPermission[$permission]) ? 'Z' : 'A');
		else
			return $IBlockPermission;
	}

	function LOCK(&$options)
	{
		$this->IsDir();
		if (
			$this->arParams["is_dir"]
			&& !empty($options["depth"])
		)
			return "409 Conflict";

		$options["timeout"] = time() + 300;

		$arProps = $this->_get_props($this->arParams["parent_id"].$this->arParams["base_name"]);
		if (isset($options["update"]))
		{
			$token = $options["update"];
			if (
				array_key_exists("LOCK", $arProps) &&
				array_key_exists($token, $arProps["LOCK"]) &&
				(array_key_exists("owner", $arProps["LOCK"][$token]) && strlen($arProps["LOCK"][$token]["owner"]) > 0) &&
				(array_key_exists("exclusivelock", $arProps["LOCK"][$token]) && strlen($arProps["LOCK"][$token]["exclusivelock"]) > 0)
				)
			{
				$arProps["LOCK"][$token]["expires"] = $options["timeout"];
				$arProps["LOCK"][$token]["modified"] = time();

				$options["owner"] = $GLOBALS['USER']->GetLogin();
				$options["scope"] = $arProps["LOCK"][$token]["exclusivelock"] ? "exclusive" : "shared";
				$options["type"]  = $arProps["LOCK"][$token]["exclusivelock"] ? "write"		: "read";
				return true;
			}
			else
			{
				return false;
			}
		}

		$arProps["LOCK"][$options["locktoken"]] = array(
			"created" => time(),
			"modified" => time(),
			"owner" => $GLOBALS['USER']->GetLogin(),
			"token" => $options["locktoken"],
			"expires" => $options["timeout"],
			"exclusivelock" => ($options["scope"] === "exclusive" ? 1 : 0)
		);

		$this->_set_props($this->arParams["parent_id"].$this->arParams["base_name"], $arProps);
		return "200 OK";
	}

	function UNLOCK(&$options)
	{
		$this->IsDir();
		if (!$this->arParams["not_found"])
		{
			$arProps = $this->_get_props($this->arParams["item_id"]);
			unset($arProps["LOCK"]);
			$this->_set_props($this->arParams["item_id"], $arProps);
		}
		return "204 No Content";
	}

	function MOVE(&$options)
	{
		if (!$this->CheckRights("MOVE", true, $options["dest_url"])):
			$this->ThrowAccessDenied();
			return "403 Forbidden";
		endif;
		return $this->COPY($options, true);
	}

	function COPY(&$options, $drop = false)
	{
		$io = self::GetIo();
		$options["dest_url"] = $this->MetaNamesReverse(explode("/", $options["dest_url"]));
		if (!$this->CheckRights("COPY", true, $options["dest_url"])):
			$this->ThrowAccessDenied();
			return "403 Forbidden";
		elseif ($_SERVER['REQUEST_METHOD'] == "MOVE" && !empty($_SERVER["CONTENT_LENGTH"])):
			return "415 Unsupported media type";
		elseif ($options["path"] == $options["dest_url"]):
//			 $GLOBALS["APPLICATION"]->ThrowException(GetMessage("WD_FILE_ERROR2"), "EMPTY_DESTINATION_URL");
			return "204 No Content";
		elseif (empty($options["dest_url"])):
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("WD_FILE_ERROR2"), "EMPTY_DESTINATION_URL");
			return "502 Bad gateway";
		endif;
		$arDataFrom = array();
		$arDataTo = array();

		$from_parent_id = 0;
		$to_parent_id = 0;

		$from_ID = 0; $from_basename = "";
		$to_ID = 0; $to_basename = "";

		$is_dir = false;

		////////////// CHECK FROM
		$this->IsDir($options);
		$arFrom = $this->arParams;
		if ($this->arParams["is_dir"] === true):
			$from_ID = $this->arParams["item_id"];
			$is_dir = true;
		elseif ($this->arParams["is_file"] === true):
			$from_ID = $this->arParams["item_id"];
		else:
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("WD_FILE_ERROR3"), "DESTINATION_FILE_OR_FOLDER_IS_NOT_FOUND");
			return "404 Not Found";
		endif;

		$from_basename = $this->arParams["basename"];
		$from_parent_id = $this->arParams["parent_id"];

		if ($_SERVER['REQUEST_METHOD'] == "MOVE" && $is_dir && ($options["depth"] != "infinity")):
			return "400 Bad request";
		endif;

		////////////// CHECK TO

		$this->IsDir(array("path" => $options["dest_url"]), $this->replace_symbols);
		$options["dest_url"] = $this->_udecode($options["dest_url"]);
		$arTo = $this->arParams;
		if ($this->arParams["is_dir"] === true || $this->arParams["is_file"] === true):
			$to_ID = $this->arParams["item_id"];
		endif;

		if ($from_ID == $options["dest_url"]):
			return "204 No Content";
		elseif ($to_ID && $options['rename']):
			$nameSuffix = 1;
			do
			{
				$tmpName = $options["dest_url"]." (". $nameSuffix++ .")";
				$this->IsDir(array("path" => $tmpName), $this->replace_symbols);
				$arTo = $this->arParams;
			} while ($this->arParams["is_dir"] === true || $this->arParams["is_file"] === true);
			$options['dest_url'] = $tmpName;
			$to_ID = 0;
		elseif ($to_ID && !$options["overwrite"]):
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("WD_FILE_ERROR4"), "FILE_OR_FOLDER_ALREADY_EXISTS");
			return '412 Precondition failed';
		elseif ($is_dir && $this->arParams["is_file"]):
			$GLOBALS["APPLICATION"]->ThrowException(str_replace("#FOLDER#", $this->arParams["item_id"], GetMessage("WD_FILE_ERROR5")), "FOLDER_IS_EXISTS");
			return "400 bad Request";
		elseif ($arFrom['is_file'] && $arTo["is_dir"]):
			$GLOBALS["APPLICATION"]->ThrowException(str_replace("#FOLDER#", $this->arParams["item_id"], GetMessage("WD_FILE_ERROR5")), "FOLDER_IS_EXISTS");
			return "400 bad Request";
		endif;

		if (rtrim($arTo['parent_id'], '/') == $this->GetMetaID('TRASH'))
		{
			$arCheckTrashElement = $this->_get_props($arFrom["item_id"]);
			if ( ! isset($arCheckTrashElement["UNDELETEBX:"]))
			{
				$this->ThrowAccessDenied();
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("WD_ACCESS_DENIED"), "BAD_NAME");
				return "403 Forbidden";
			}
		}

		$__from = preg_replace("/\/+/is", "/", strtolower($from_ID)."/");
		$__to = preg_replace("/\/+/is", "/", strtolower(substr($options["dest_url"], 0, strlen($from_ID) + 1)."/"));
		if ($is_dir && $__from == $__to):
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("WD_FILE_ERROR100"), "SECTION_IS_NOT_UPDATED");
			return "400 Bad Request";
		endif;

		$to_parent_id = $this->arParams["parent_id"];
		$to_basename = $this->arParams["basename"];

		/*$from_url = $io->CombinePath($this->real_path_full, $from_ID);
		$to_url = $io->CombinePath($this->real_path_full, $options["dest_url"]);*/

		$from_url = CWebDavBase::CleanRelativePathString($from_ID, $this->real_path_full);
		$to_url = CWebDavBase::CleanRelativePathString($options["dest_url"], $this->real_path_full);
		if($from_url === false || $to_url === false)
		{
			return "400 Bad Request";
		}

		if (!$is_dir)
		{
			if ($to_ID && $options["overwrite"])
				$this->DELETE(array("path" => $options["dest_url"]));

			if (!$io->Copy($from_url, $to_url))
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#FILE#", $this->arParams["item_id"], GetMessage("WD_FILE_ERROR6")), "FILE_IS_NOT_COPIED");
				return "500 Internal server error";
			}
			else
			{
				$this->_copy_props($from_ID, $options["dest_url"], $options["overwrite"]);
				$file = $to_url;
				$oFile = $io->GetFile($file);
				$path = str_replace(array($_SERVER['DOCUMENT_ROOT'], "///", "//"), "/", $file);

				if(CModule::IncludeModule("search"))
				{
					if (rtrim($arTo['parent_id'], '/') == $this->GetMetaID('TRASH'))
					{
						CSearch::DeleteIndex('main', SITE_ID.'|'.$path);
					}
					else
					{
						$this->Reindex($file);
					}
				}

				if (COption::GetOptionInt("main", "disk_space") > 0)
				{
					CDiskQuota::updateDiskQuota("file", $oFile->GetFileSize(), "add");
				}
			}
		}
		else
		{
			$params = $this->GetFilesAndFolders($from_ID);
			if (empty($params))
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("WD_FILE_ERROR3"), "DESTINATION_FILE_OR_FOLDER_IS_NOT_EXISTS");
				return "500 Internal Server Error";
			}

			sort($params, SORT_STRING);

			foreach ($params as $file)
			{
				$file = CWebDavBase::CleanRelativePathString($file);
				if($file === false)
				{
					return "409 Conflict";
				}
				$bDir = $io->DirectoryExists($file);
				if ($bDir)
				{
					$file = $this->_slashify($file);
				}

				$destfile = str_replace($from_url, $to_url, $file);

				if ($bDir)
				{
					if (
						! $io->DirectoryExists($destfile)
						&& ! $io->CreateDirectory($destfile)
					)
					{
						$GLOBALS["APPLICATION"]->ThrowException(str_replace("#FOLDER#", str_replace($this->real_path_full, "", $destfile), GetMessage("WD_FILE_ERROR7")), "FOLDER_IS_NOT_CREATED");
						return "409 Conflict";
					}
					else
					{
						$this->_copy_props(str_replace(array($this->real_path_full, "///", "//"), "/", $file), str_replace(array($this->real_path_full, "///", "//"), "/", $destfile), $drop);
					}
				}
				else
				{
					if ($io->FileExists($destfile))
					{
						if (!$options["overwrite"])
						{
							$GLOBALS["APPLICATION"]->ThrowException(str_replace("#FILE#", str_replace($this->real_path_full, "", $destfile), GetMessage("WD_FILE_ERROR8")), "FILE_ALREADY_EXISTS");
							return "409 Conflict";
						}
						$this->DELETE(array("path" => str_replace(array($this->real_path_full, "///", "//"), "/", $destfile)));
					}
					if (!$io->Copy($file, $destfile))
					{
						$GLOBALS["APPLICATION"]->ThrowException(str_replace("#FILE#", str_replace($this->real_path_full, "", $destfile), GetMessage("WD_FILE_ERROR6")), "FILE_IS_NOT_COPIED");
						return "409 Conflict";
					}
					else
					{

						//$this->_copy_props(str_replace(array($this->real_path_full, "///", "//"), "/", $file), str_replace(array($this->real_path_full, "///", "//"), "/", $destfile), $drop);
						$this->_copy_props(str_replace($this->real_path_full, "/", $file), str_replace($this->real_path_full, "/", $destfile), $drop);
						$file = $destfile;

						$this->Reindex($file);
						if (COption::GetOptionInt("main", "disk_space") > 0)
						{
							$oFile = $io->GetFile($file);
							CDiskQuota::updateDiskQuota("file", $oFile->GetFileSize(), "add");
						}
					}
				}
			}
		}

		clearstatcache();

		if ($drop)
		{
			$result = $this->DELETE(array("path" => $from_ID, "force" => true));
			if (intval($result) != 204)
				return $result;
		}
		return (!$to_ID ? "201 Created" : "204 No Content");
	}

	function MKCOL($options)
	{
		$io = self::GetIo();
		$this->IsDir($options);
		$result = '201 Created';

		if ($this->arParams["is_file"])
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("WD_FILE_ERROR11"), "FOLDER_CAN_NOT_REPLACE_FILE");
			$result = '405 Method not allowed';
		}
		elseif ($_SERVER['REQUEST_METHOD'] == "MKCOL" && !empty($_SERVER['CONTENT_LENGTH']))
		{
			$result = '415 Unsupported media type';
		}
		else
		{
			if ($this->arParams["not_found"])
			{
				$path = $io->CombinePath($this->arParams["parent_id"], $this->arParams["base_name"]);
			}
			else
			{
				$path = $this->arParams['item_id'];
			}
			$res = explode("/", $path);
			$res = array_filter(!empty($res) && is_array($res) ? $res : array($path));

			$path = $io->CombinePath($this->real_path_full, $path);
			$name = trim(array_pop($res));
			$parent = $io->CombinePath($this->real_path_full, implode("/", $res));

			if (! $io->DirectoryExists($parent))
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("WD_FILE_ERROR9"), "PARENT_FOLDER_IS_NOT_FOUNDED");
				$result = '409 Conflict';
			}
			elseif ($io->FileExists($parent))
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("WD_FILE_ERROR10"), "FOLDER_CAN_NOT_BE_CREATED_IN_THE_FILE");
				$result = '403 Forbidden';
			}
			else
			{
				$stat = $io->CreateDirectory($io->CombinePath($parent, $name));
				if (!$stat)
				{
					$GLOBALS["APPLICATION"]->ThrowException(GetMessage("WD_FILE_ERROR12"));
					$result = '403 Forbidden';
				}
			}
		}
		return $result;
	}

	function GetMetaID($sMetaName)
	{
		$io = self::GetIo();
		if (isset($this->meta_names[$sMetaName]))
		{
			//$file = $io->CombinePath($this->real_path_full, $this->meta_names[$sMetaName]['name']);
			$file = CWebDavBase::CleanRelativePathString($this->meta_names[$sMetaName]['name'], $this->real_path_full);
			if($file === false)
			{
				return null;
			}
			if ($io->FileExists($file))
			{
				return "/".$this->meta_names[$sMetaName]['name'];
			}
			else
			{
				$stat = $io->CreateDirectory($file);
				return "/".$this->meta_names[$sMetaName]['name'];
			}
		}
		return null;
	}

	function _move_to_trash($options, $arParams)
	{
		$io = self::GetIo();
		if ($arParams['is_dir'])
		{
			$params = $this->GetFilesAndFolders($arParams["item_id"]);
			if (!empty($params))
			{
				$tmpParams = $this->arParams;
				sort($params, SORT_STRING);
				foreach ($params as $file)
				{
					$localpath = str_replace(array($this->real_path_full, "///", "//"), "/", $file);
					$arUndeleteOptions = array(
						"props" => array(
							array(
								"ns" => "BX:",
								"name" => "UNDELETE",
								"val" => $localpath
							)
						),
						"path" => $localpath
					);
					$this->PROPPATCH($arUndeleteOptions);
				}
				$this->arParams = $tmpParams;
			}
			$destName = $arParams['base_name'] . " " . $this->CorrectName(ConvertTimeStamp(time(), "FULL"));
		}
		else
		{
			$arTo = $this->arParams;
			$arUndeleteOptions = array(
				"path" => $arTo["item_id"],
				"props" => array(
					array(
						"ns" => "BX:",
						"name" => "UNDELETE",
						"val" => $arTo["item_id"]
					)
				)
			);
			$this->PROPPATCH($arUndeleteOptions);
			$destName = substr($arTo['base_name'], 0, -strlen($arTo['file_extention'])) . " " . $this->CorrectName(ConvertTimeStamp(time(), "FULL")) . $arTo['file_extention'];
		}

		$options['dest_url'] = $io->CombinePath("/", $this->meta_names['TRASH']['name'], $destName);
		$options['rename'] = true;
		$status = $this->MOVE($options);
		if (intval($status) == 201)
		{
			return "204 No Content";
		}
		else
		{
			$GLOBALS["APPLICATION"]->ResetException();
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("WD_FILE_ERROR16"), "FILE_OR_FOLDER_TRASH_ERROR");
			return $status;
		}
	}

	function DELETE($options)
	{
		$io = self::GetIo();
		if (isset($options['path']))
			$options['path'] = $this->_udecode($options['path']);
		$this->IsDir($options);
		if ($this->arParams["not_found"])
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("WD_FILE_ERROR3"), "DESTINATION_FILE_OR_FOLDER_IS_NOT_FOUND");
			return "404 Not found";
		}

		if(!$this->CheckRights("DELETE", true, $options["path"]))
		{
			$this->ThrowAccessDenied();
			return "403 Forbidden";
		}

		$quota = false;
		if (COption::GetOptionInt("main", "disk_space") > 0)
			$quota = new CDiskQuota();

		$trashPath = $this->GetMetaID("TRASH");
		$arPath = explode("/", $this->arParams["item_id"]);
		if (!$this->arParams["is_dir"])
		{

			//$file = $io->CombinePath($this->real_path_full, $this->arParams["item_id"]);
			//$path = $io->CombinePath($this->real_path, $this->arParams["item_id"]);

			$file = CWebDavBase::CleanRelativePathString($this->arParams["item_id"], $this->real_path_full);
			$path = CWebDavBase::CleanRelativePathString($this->arParams["item_id"], $this->real_path);
			if($file === false || $path === false)
			{
				return "404 Not found";
			}

			$arPath = explode("/", $this->arParams["item_id"]);
			if (($arPath[1] != $this->meta_names["TRASH"]["name"]) && (!isset($options['force'])))		   // not in trash yet
			{
				return $this->_move_to_trash($options, $this->arParams);
			} else {											 // in trash or options[force]
				$oFile = $io->GetFile($file);
				$file_size = $oFile->GetFileSize();
				if ($io->Delete($file))
				{
					$this->_delete_props($this->arParams['item_id']);
					$GLOBALS["APPLICATION"]->RemoveFileAccessPermission(Array(SITE_ID, $path));

					if (CModule::IncludeModule("search"))
						CSearch::DeleteIndex("main", SITE_ID."|".$path);
					if ($quota)
						$quota->updateDiskQuota("file", $file_size, "delete");
				}
				else
				{
					$GLOBALS["APPLICATION"]->ThrowException(GetMessage("WD_FILE_ERROR3"), "DESTINATION_FILE_OR_FOLDER_IS_NOT_FOUND");
					return "404 Not found";
				}
			}
		}
		else
		{
			if (($arPath[1] != $this->meta_names["TRASH"]["name"]) && (!isset($options['force'])))
			{
				return $this->_move_to_trash($options, $this->arParams);
			}
			else
			{
				$params = $this->GetFilesAndFolders($this->arParams["item_id"]);
				if (empty($params))
					return true;

				rsort($params, SORT_STRING);
				foreach ($params as $file)
				{

					$path = str_replace($this->real_path_full, "", $file);
					$path = $io->CombinePath("/", $path);
					$file = $io->CombinePath($this->real_path_full, $path);
					if(!$io->ValidatePathString($file))
					{
						return "404 Not found";
					}

					if ($io->FileExists($file))
					{
						//$path = str_replace($_SERVER['DOCUMENT_ROOT'], "", $file);
						$oFile = $io->GetFile($file);
						$file_size = $oFile->GetFileSize();
						if ($io->Delete($file))
						{
							$this->_delete_props(str_replace(array($this->real_path_full, "///", "//"), "/", $file));
							$GLOBALS["APPLICATION"]->RemoveFileAccessPermission(Array(SITE_ID, $path));
							if (CModule::IncludeModule("search"))
								CSearch::DeleteIndex("main", SITE_ID."|".$path);
							if ($quota)
								$quota->updateDiskQuota("file", $file_size, "delete");
						}
						else
						{
							$GLOBALS["APPLICATION"]->ThrowException(GetMessage("WD_FILE_ERROR3"), "DESTINATION_FILE_OR_FOLDER_IS_NOT_FOUND");
							return "404 Not found";
						}
					}
					elseif ($io->DirectoryExists($file))
					{
						$path = str_replace($_SERVER['DOCUMENT_ROOT'], "", $file);
						if ($io->Delete($file))
						{
							$this->_delete_props(str_replace(array($this->real_path_full, "///", "//"), "/", $file));
							$GLOBALS["APPLICATION"]->RemoveFileAccessPermission(Array(SITE_ID, $path));
						}
						else
						{
							$GLOBALS["APPLICATION"]->ThrowException(GetMessage("WD_FILE_ERROR3"), "DESTINATION_FILE_OR_FOLDER_IS_NOT_FOUND");
							return "404 Not found";
						}
					}
				}

				if ($path == $trashPath) //  all the trash was cleaned
				{
					$trashID = $this->GetMetaID('TRASH');
				}
			}
		}
		clearstatcache();
		return "204 No Content";
	}

	function IsDir($options = array())
	{
		global $DB;
		$io = self::GetIo();
		$options = (is_array($options) ? $options : array());
		$path = $this->_udecode(array_key_exists("path", $options) ? $options["path"] : $this->_path);

		if (is_set($options, "section_id"))
			$path = $options["section_id"];
		elseif (is_set($options, "element_id"))
			$path = $options["element_id"];

		if (substr($path, 0, 1) != "/")
			$path = "/".$path;

		$path = $this->MetaNamesReverse(explode("/", $path));
		$id = md5($path);

		if (in_array($id, $this->CACHE))
		{
			$this->arParams = array_merge($this->arParams, $this->CACHE[$id]);
			return $this->arParams["is_dir"];
		}
		$path = $io->CombinePath("/", $path);
		if(!$io->ValidatePathString($path))
		{
			return false;
		}
		$path_copy = $path;

		$path = $io->CombinePath($this->real_path_full, $path);
		$params = array(
			"item_id" => "/",
			"not_found" => false,
			"is_dir" => false,
			"is_file" => false,
			"parent_id" => false,
			"base_name" => substr(strrchr($path , '/'), 1)
		);

		$res = array_filter(explode("/", $path_copy));
		if ($io->DirectoryExists($path))
		{
			$params["item_id"] = $path_copy;
			$params["is_dir"] = true;
			$name = array_pop($res);
			if ($name)
			{
				$params["dir_array"] = array(
					"ID" => $params["item_id"],
					"IBLOCK_SECTION_ID" => implode("/", $res),
					"NAME" => $name
				);
				$params["parent_id"] = implode("/", $res);
			}
		}
		elseif ($io->FileExists($path))
		{
			$ioFile = $io->GetFile($path);
			$params["item_id"] = $path_copy;
			$params["is_file"] = true;
			$params["element_array"] = array(
				"ID" => $params["item_id"],
				"NAME" => array_pop($res),
				"TIMESTAMP_X" => $ioFile->GetModificationTime() + CTimeZone::GetOffset()
			);

			$params["element_array"]["EXTENTION"] = strtolower(strrchr($params["element_array"]["NAME"] , '.'));
			if ($params["element_array"]["EXTENTION"] == $params["element_array"]["NAME"])
				$params["element_array"]["EXTENTION"] = "";

			$params["parent_id"] = implode("/", $res);
			$params["element_name"] = $params["element_array"]["NAME"];
			$params["file_name"] = $params["element_array"]["NAME"];
			$params["file_extention"] = strtolower(strrchr($params["element_array"]["NAME"] , '.'));
		}
		else
		{
			array_pop($res);
			$params["not_found"] = true;
			$params["parent_id"] = '/'.implode("/", $res);
		}

		$params["element_array"]["SHOW"] = ($this->permission >= "W" ? array("EDIT" => "Y", "DELETE" => "Y") : array());

		if ($params["parent_id"])
		{
			$params["parent_id"] = $this->_slashify($io->CombinePath("/", $params["parent_id"]));
		}

		$this->arParams = $params;
		$this->CACHE[$id] = $params;
		return $params["is_dir"];
	}

	function IsTrashEmpty()
	{
		$io = self::GetIo();
		$trashSectionPath = $this->GetMetaID('TRASH');
		$status = true;
		$realTrashPath = $io->CombinePath($this->real_path_full, $trashSectionPath);
		if ($io->DirectoryExists($realTrashPath))
		{
			$oDir = $io->GetDirectory($realTrashPath);

			$arChildren = $oDir->GetChildren();
			$status = (sizeof($arChildren) <= 0);
		}
		return $status;
	}

	function GetNavChain($options = array(), $utf = false)
	{
		static $nav_chain = array();

		$utf = ($utf === true ? "Y" : "N");
		$this->IsDir($options, $this->replace_symbols);

		if (
			$this->arParams["not_found"] === true
			&& empty($this->arParams["item_id"])
		)
		{
			return array();
		}

		$id = md5($this->arParams["item_id"]);
		if (!is_set($nav_chain, $utf.$id))
		{
			$nav_chain["Y".$id] = array();
			$nav_chain["N".$id] = array();
			$res = explode("/", $this->arParams["item_id"]);
			if (empty($res) && !empty($this->arParams["item_id"]))
				$res = array($this->arParams["item_id"]);

			foreach ($res as $val)
			{
				if (empty($val))
					continue;

				foreach($this->meta_names as $metaName => $metaArr)
				{
					if ($val == $metaArr["name"])
						$val = $metaArr["alias"];
				}

				if (SITE_CHARSET != "UTF-8")
					$nav_chain["Y".$id][] = $GLOBALS["APPLICATION"]->ConvertCharset($val, SITE_CHARSET, "UTF-8");
				else
					$nav_chain["Y".$id][] = $val;
				$nav_chain["N".$id][] = $val;
			}
		}
		return $nav_chain[$utf.$id];
	}

	function GetSectionsTree($options = array()) // DEPRECATED FROM 11.5.0
	{
		$io = self::GetIo();
		if (!function_exists("__get_folder_tree"))
		{
			function __get_folder_tree($path, $path_chunc, $deep, &$arSection)
			{
				static $io = false;

				if ($io === false)
					$io = CBXVirtualIo::GetInstance();

				if (!is_array($arSection))
					$arSection = array();

				if($path === false)
				{
					return false;
				}

				if ($io->DirectoryExists($path))
				{
					$oDir = $io->GetDirectory($path);
					$arChildren = $oDir->GetChildren();
					foreach($arChildren as $node)
					{
						$deep++;
						$filename = $node->GetName();
						$tmp_path = $node->GetPathWithName();

						if ($io->DirectoryExists($tmp_path))
						{
							$filename = $this->MetaNamesReverse($filename);
							$arSection[] = array(
								"ID" => str_replace($path_chunc, "", $tmp_path),
								"DEPTH_LEVEL" => $deep,
								"NAME" => $filename);
							__get_folder_tree($tmp_path, $path_chunc, $deep, $arSection);
						}
					}
				}
			}
		}

		static $sections = array();

		$this->IsDir($options, $this->replace_symbols);

		if ($this->arParams["not_found"] || !$this->arParams["is_dir"])
			return array();

		$id = md5($this->arParams["item_id"]);

		if (!is_set($sections, $id))
		{
			//$path = $io->CombinePath($this->real_path_full, $this->arParams["item_id"]);
			$path = CWebDavBase::CleanRelativePathString($this->arParams["item_id"], $this->real_path_full);
			$arResult = null;
			__get_folder_tree($path, $this->real_path_full, 0, $arResult);
			$sections[$id] = $arResult;
		}

		return $sections[$id];
	}

	function GetFilesAndFolders($path = false)
	{
		$io = self::GetIo();
		if (!function_exists("__get_files_and_folders"))
		{
			function __get_files_and_folders($path, &$arSection)
			{
				static $io = false;

				if ($io === false)
					$io = CBXVirtualIo::GetInstance();

				if (!is_array($arSection))
					$arSection = array();

				$oDir = $io->GetDirectory($path);
				if ($oDir->IsExists())
				{
					$arSection[] = $path;
					$arChildren = $oDir->GetChildren();
					foreach($arChildren as $node)
					{
						$tmp_path = $node->GetPathWithName();
						$arSection[] = $tmp_path;

						if ($node->IsDirectory())
							__get_files_and_folders($tmp_path, $arSection);
					}
				}
				elseif ($io->FileExists($path))
				{
					$arSection[] = $path;
				}
			}
		}

		$result = array();

		if ($path)
		{
			//$path = $io->CombinePath($this->real_path_full, $path);
			$path = CWebDavBase::CleanRelativePathString($path, $this->real_path_full);
			if($path === false)
			{
				return false;
			}
			__get_files_and_folders($path, $result);
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
		return $result;
	}

	function CheckRights($method, $strong, &$path)
	{
		$result = true;
		if (!parent::CheckRights($method))
		{
			$result = false;
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage('WD_ACCESS_DENIED'), 'ACCESS_DENIED');
		}
		elseif ($path <> '')
		{
			$io = CBXVirtualIo::GetInstance();
			$path = $io->CombinePath($this->_udecode($path));
			$strFileName = GetFileName($path);
			$extention = ".".strtolower(GetFileExtension($strFileName));
			if (in_array($method, array("COPY", "MOVE", "PUT")))
			{
				if (!$GLOBALS["USER"]->IsAdmin() && HasScriptExtension($strFileName))
				{
					$result = false;
					$GLOBALS['APPLICATION']->ThrowException(GetMessage("WD_FILE_ERROR13"), "FORBIDDEN_EXTENTION");
				}
				elseif (IsFileUnsafe($strFileName) || $strFileName == "index.php")
				{
					$result = false;
					$GLOBALS['APPLICATION']->ThrowException(GetMessage("WD_FILE_ERROR14"), "FORBIDDEN_NAME");
				}
				elseif (!$io->ValidatePathString($io->CombinePath("/", $path)) || !$io->ValidateFilenameString($strFileName))
				{
					$result = false;
					$GLOBALS['APPLICATION']->ThrowException(GetMessage("WD_FILE_ERROR14"), "FORBIDDEN_NAME");
				}
				elseif (in_array($extention, $this->arFileForbiddenExtentions["WRITE"]))
				{
					$result = false;
					$GLOBALS['APPLICATION']->ThrowException(GetMessage("WD_FILE_ERROR13"), "FORBIDDEN_EXTENTION");
				}
			}
			elseif (in_array($extention, $this->arFileForbiddenExtentions["READ"]))
			{
				$result = false;
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage('WD_ACCESS_DENIED'), 'ACCESS_DENIED');
			}
		}

		return $result;
	}

	function __prop_file_name($ID, $oldMode=false)
	{
		static $bDirPathChecked = false;
		if (!$bDirPathChecked)
		{
			CheckDirPath($GLOBALS["WEBDAV"]["PATH"]);
			$bDirPathChecked = true;
		}
		if (substr($ID, 0, 1) !== '/')
			$ID = '/'.$ID;
		$id = md5(rtrim(($oldMode ? $this->base_url_full : $this->base_url).$ID, "/"));
		$file = $GLOBALS["WEBDAV"]["PATH"]."props".$id;
		return $file;
	}

	function _get_props($ID)
	{
		$res = @file_get_contents($this->__prop_file_name($ID));

		if ($res == false)
			$res = @file_get_contents($this->__prop_file_name($ID, true));

		$res = @unserialize($res);
		$res = (is_array($res) ? $res : array());
		return $res;
	}

	function _set_props($ID, $vals)
	{
		if (empty($vals))
		{
			@unlink($this->__prop_file_name($ID));
		}
		else
		{
			@file_put_contents($this->__prop_file_name($ID), @serialize($vals));
		}
	}

	function _copy_props($fromID, $toID, $move=false)
	{
		$pathFrom = $this->__prop_file_name($fromID);
		if (file_exists($pathFrom))
		{
			$pathTo = $this->__prop_file_name($toID);
			if (!$move)
				@copy($pathFrom, $pathTo);
			else
				@rename($pathFrom, $pathTo);
		}
	}

	function _delete_props($ID)
	{
		$this->_set_props($ID, array());
	}

	function _mimetype($fspath)
	{
		$io = self::GetIo();
		if ($io->DirectoryExists($fspath))
		{
			return 'httpd/unix-directory';
		}
		else if (function_exists('mime_content_type') && $io->FileExists($fspath))
		{
			$mime_type = mime_content_type(CBXVirtualIoFileSystem::ConvertCharset($fspath));
		}

		if (empty($mime_type))
		{
			$mime_type = $this->get_mime_type($fspath);
		}

		return $mime_type;
	}

	function _get_path($path)
	{
		$io = self::GetIo();
		if (is_integer($path) && $path <= 0 || $path == "0")
			return "/";
		$res = explode("/", $path);
		if (empty($res))
			$res[] = $path;
		$res = $this->_slashify($io->CombinePath('/', (! empty($res) ? implode("/", $res) : '')));
		return $res;
	}

	function _get_fileinfo($path)
	{
		$io = self::GetIo();
		if (strpos($path, $this->real_path_full) === 0)
		{
			$path = CWebDavBase::ConvertPathToRelative($path, $this->real_path_full);
		}
		$path = CWebDavBase::CleanRelativePathString($path);
		if($path === false)
		{
			return false;
		}
		$fspath = $io->CombinePath($this->real_path_full, $path);


		//$fspath = $path;
		//if (strpos($path, $this->real_path_full) === false)
			//$fspath = str_replace(array("///", "//"), "/", $this->real_path_full."/".$path);
		//else
			//$path = str_replace(array($this->real_path_full, "///", "//"), "/", $path);

		$bDir = $io->DirectoryExists($fspath);

		$info = array();
		$info['path'] = ($bDir ? $this->_slashify($path) : $path);
		$info['path'] = $this->MetaNamesReverse(explode('/', $info['path']), 'name', 'alias');

		if (SITE_CHARSET != "UTF-8")
		{
			$info['path'] = $GLOBALS["APPLICATION"]->ConvertCharset($info['path'], SITE_CHARSET, "UTF-8");
		}

		$info['props'] = array();

		if ($bDir)
			$ioObj = $io->GetDirectory($fspath);
		else
			$ioObj = $io->GetFile($fspath);

		$tzOffset = CTimeZone::GetOffset();
		$info['props'][] = $this->_mkprop('creationdate', $ioObj->GetCreationTime() + $tzOffset);
		$info['props'][] = $this->_mkprop('getlastmodified', $ioObj->GetModificationTime() + $tzOffset);

		if ($bDir)
		{
			$info['props'][] = $this->_mkprop('resourcetype', 'collection');
			$info['props'][] = $this->_mkprop('getcontenttype', 'httpd/unix-directory');
		}
		else
		{
			$info['props'][] = $this->_mkprop('resourcetype', '');
			if ($ioObj->IsReadable())
				$info['props'][] = $this->_mkprop('getcontenttype', $this->_mimetype($fspath));
			else
				$info['props'][] = $this->_mkprop('getcontenttype', 'application/x-non-readable');
			$info['props'][] = $this->_mkprop('getcontentlength', $ioObj->GetFileSize());
		}

		$arProps = $this->_get_props($path);
		if (is_array($arProps))
		{
			foreach ($arProps as $name => $prop)
			{
				if($name != "LOCK")
				{
					$info["props"][] = CWebDavBase::_mkprop($prop["ns"], $prop["name"], $prop["value"]);
				}
			}
		}

		return $info;
	}
}

class CDBResultWebDAVFiles extends CDBResult
{
	function CDBResultWebDAVFiles($res)
	{
		parent::CDBResult($res);
	}

	function Fetch()
	{
		global $DB;
		if ($res = parent::Fetch())
		{
			$res["~TIMESTAMP_X"] = $res["TIMESTAMP_X"];
			$res["TIMESTAMP_X"] = ConvertTimeStamp($res["~TIMESTAMP_X"], "FULL");
		}
		return $res;
	}

	function GetNext()
	{
		global $DB;
		if ($res = parent::GetNext())
		{
			$result = $res;
			foreach ($res as $key => $val)
			{
				if (substr($key, 0, 2) == "~~")
				{
					unset($result[$key]);
					$result[substr($key, 1)] = $val;
				}
			}
		}
		return $result;
	}
}