<?
/*
-document	pdf
+document	rtf TODO: source document encoding
+document	odt
+document	doc
+document	docx
+spreadsheet	ods
+spreadsheet	xls
+spreadsheet	xlsx
+presentation	odp
+presentation	ppt
+presentation	pptx
*/

class CIntranetSearchConverters
{
	public static function OnSearchGetFileContent($absolute_path, $ext = '')
	{
		global $APPLICATION;
		static $arExternalConverters = false;
		if(!$arExternalConverters)
		{
			$arExternalConverters = array();
			$extensions = explode(",", COption::GetOptionString("intranet", "search_file_extensions"));
			foreach($extensions as $extension)
			{
				$command = trim(COption::GetOptionString("intranet", "search_file_extension_exe_".$extension, ""));
				if(strpos($command, "#FILE_NAME#") !== false || strpos($command, "#SHORT_FILE_NAME#") !== false )
				{
					$arExternalConverters[".".$extension] = array(
						"exe" => $command,
						"cd" => trim(COption::GetOptionString("intranet", "search_file_extension_cd_".$extension, "")),
					);
				}
			}
		}

		$io = CBXVirtualIo::GetInstance();
		$ioFile = $io->GetFile($absolute_path);
		if(
			$ioFile->IsExists()
			&& ! $ioFile->IsDirectory()
			&& ($ioFile->GetFileSize() > 0)
		)
		{
			$fs_absolute_path = $io->GetPhysicalName($absolute_path);

			//Check by file extension
			if(!$ext)
			{
				$p = strrpos($absolute_path, ".");
				if($p !== false)
				{
					$ext = substr($absolute_path, $p);
				}
			}

			if($ext)
			{
				$ext = '.' . ltrim($ext, '.');
				switch($ext)
				{
					case ".docx":
						$parser = new CIntranetSearchConverter_docx;
						$result = CIntranetSearchConverters::ProcessZipEntry($fs_absolute_path, "#^word/document\\.xml\$#", $parser);
						if($result)
							$tags = CIntranetSearchConverters::ProcessZipEntry($fs_absolute_path, "#^docProps/core\\.xml\$#", $parser, "ParseTagsZip");
						break;
					case ".odt":
					case ".odp":
						$parser = new CIntranetSearchConverter_odt;
						$result = CIntranetSearchConverters::ProcessZipEntry($fs_absolute_path, "#^content\\.xml\$#", $parser);
						if($result)
							$tags = CIntranetSearchConverters::ProcessZipEntry($fs_absolute_path, "#^meta\\.xml\$#", $parser, "ParseTagsZip");
						break;
					case ".ods":
						$parser = new CIntranetSearchConverter_ods;
						$result = CIntranetSearchConverters::ProcessZipEntry($fs_absolute_path, "#^content\\.xml\$#", $parser);
						if($result)
							$tags = CIntranetSearchConverters::ProcessZipEntry($fs_absolute_path, "#^meta\\.xml\$#", $parser, "ParseTagsZip");
						break;
					case ".rtf":
						$parser = new CIntranetSearchConverter_rtf;
						$result = $parser->ProcessFile($fs_absolute_path);
						$tags = "";
						break;
					case ".xlsx":
						$parser = new CIntranetSearchConverter_xlsx;
						$result = CIntranetSearchConverters::ProcessZipEntry($fs_absolute_path, "#^xl/sharedStrings.xml\$#", $parser);
						if($result)
							$tags = CIntranetSearchConverters::ProcessZipEntry($fs_absolute_path, "#^docProps/core\\.xml\$#", $parser, "ParseTagsZip");
						break;
					case ".pptx":
						$parser = new CIntranetSearchConverter_pptx;
						$result = CIntranetSearchConverters::ProcessZipEntry($fs_absolute_path, "#^ppt/slides/(.+)\\.xml\$#", $parser);
						if($result)
							$tags = CIntranetSearchConverters::ProcessZipEntry($fs_absolute_path, "#^docProps/core\\.xml\$#", $parser, "ParseTagsZip");
						break;
					default:
						if(array_key_exists($ext, $arExternalConverters))
						{
							$parser = new CIntranetSearchConverter;
							$result = $parser->ProcessExec($arExternalConverters[$ext], $fs_absolute_path);
						}
						else
						{
							$result = false;
						}
						$tags = "";
				}
				if(strlen($result))
				{
					$file_name = end(explode('/', $absolute_path));
					if(CUtil::DetectUTF8(urlencode($file_name)))
						$file_name = $APPLICATION->ConvertCharset($file_name, "UTF-8", LANG_CHARSET);
					return array(
						"TITLE" => $file_name,
						"CONTENT" => $APPLICATION->ConvertCharset($result, "UTF-8", LANG_CHARSET),
						"PROPERTIES" => array(
							COption::GetOptionString("search", "page_tag_property") => $APPLICATION->ConvertCharset($tags, "UTF-8", LANG_CHARSET),
						),
					);
				}
			}
		}
		return false;
	}

	function ProcessZipEntry($absolute_path, $entry_name, $processor, $method = "ParseDocZip")
	{
		if(file_exists($absolute_path) && is_file($absolute_path))
		{
			global $APPLICATION;
			//Function and security checks
			if(function_exists("zip_open"))
			{
				$hZip = zip_open($absolute_path);
				if(is_resource($hZip))
				{
					$content = "";
					while($entry = zip_read($hZip))
					{
						if(preg_match($entry_name, zip_entry_name($entry)))
						{
							zip_entry_open($hZip, $entry);
							if(zip_entry_filesize($entry))
							{
								$content .= $processor->$method($entry);
							}
							zip_entry_close($entry);
						}
					}
					zip_close($hZip);
					return $content;
				}
			}
		}
		return false;
	}
}


class CIntranetSearchConverter
{
	var $pcre_backtrack_limit = false;

	function Check_pcre_limit($max_length)
	{
		if($this->pcre_backtrack_limit === false)
			$this->pcre_backtrack_limit = intval(ini_get("pcre.backtrack_limit"));
		$max_length++;
		if($this->pcre_backtrack_limit < $max_length)
		{
			@ini_set("pcre.backtrack_limit", $max_length);
			$this->pcre_backtrack_limit = intval(ini_get("pcre.backtrack_limit"));
		}
	}

	function ProcessExec($program, $absolute_path)
	{
		if(is_array($program) && strpos($program["exe"], "catdoc") === 0 && function_exists("bx_catdoc"))
		{
			return bx_catdoc($absolute_path);
		}
		elseif(is_array($program) && strpos($program["exe"], "catppt") === 0 && function_exists("bx_catppt"))
		{
			return bx_catppt($absolute_path);
		}
		elseif(is_array($program) && strpos($program["exe"], "xls2csv") === 0 && function_exists("bx_catxls"))
		{
			return bx_catxls($absolute_path);
		}
		elseif(is_array($program) && strpos($program["exe"], "pdftotext") === 0 && function_exists("bx_catpdf"))
		{
			return bx_catpdf($absolute_path);
		}
		elseif(function_exists('exec') && file_exists($absolute_path) && is_file($absolute_path))
		{
			if (is_array($program))
			{
				$cd = $program["cd"];
				if (strlen($cd) > 0 && file_exists($cd) && is_dir($cd))
					chdir($cd);
				$program = $program["exe"];
			}

			$isWin = substr(PHP_OS, 0, 3) == 'WIN';
			$escapeArg = function($arg) use ($isWin)
			{
				// invalid symbols: !"$*@\` and \x0A and \xFF
				return $isWin ? $arg : preg_replace(
					'/([\x21\x22\x24\x2A\x40\x5C\x60\x0A\xFF])/',
					'\\\\\1',
					$arg
				);
			};

			if (strpos($program, "#FILE_NAME#") !== false)
				$program = str_replace("#FILE_NAME#", $escapeArg($absolute_path), $program);
			if (strpos($program, "#SHORT_FILE_NAME#") !== false)
			{
				$short_name = "";
				exec(sprintf(
					'""%s/bitrix/modules/intranet/tools/search.bat" "%s""',
					$_SERVER["DOCUMENT_ROOT"], $escapeArg($absolute_path)
				), $ar);
				foreach ($ar as $line)
				{
					if (preg_match("/^Short PathName : (.*)$/", rtrim($line, "\n\r"), $match))
						$short_name = $match[1];
				}
				$program = str_replace("#SHORT_FILE_NAME#", $escapeArg($short_name), $program);
			}

			exec($program, $arText, $result);

			if ($result === 0)
			{
				return implode("\n", $arText);
			}
		}
		return false;
	}

	function CheckExecutable($program)
	{
		if(function_exists('exec'))
		{

			exec($program, $arText, $result);
			if($result === 0)
			{
				return true;
			}
		}
		return false;
	}
}

class CIntranetSearchConverter_docx extends CIntranetSearchConverter
{
	function ParseDocZip($entry)
	{
		$content = "";
		while($data = zip_entry_read($entry, 102400))
		{
			$text_len = defined('BX_UTF')? mb_strlen($data, 'latin1'): strlen($data);
			$this->Check_pcre_limit($text_len);

			$data = str_replace("</w:p>", "\n", $data);//Paragraf
			$data = preg_replace("#(<.*?>)#", "", $data);
			$content .= preg_replace('#[\s\n\r]+#', ' ', $data);
		}

		$text_len = defined('BX_UTF')? mb_strlen($content, 'latin1'): strlen($content);
		$this->Check_pcre_limit($text_len);

		return preg_replace("#(<.*?>)#", "", $content);
	}

	function ParseTagsZip($entry)
	{
		$data = zip_entry_read($entry, zip_entry_filesize($entry));
		if(preg_match("#<cp:keywords>(.*)</cp:keywords>#is", $data, $match))
			return $match[1];
		else
			return "";
	}
}

class CIntranetSearchConverter_xlsx extends CIntranetSearchConverter
{
	function ParseDocZip($entry)
	{
		$content = "";
		$data = zip_entry_read($entry, zip_entry_filesize($entry));
		if($data)
		{
			$text_len = defined('BX_UTF')? mb_strlen($data, 'latin1'): strlen($data);
			$this->Check_pcre_limit($text_len);
			if(preg_match_all("#<(t|v)>(.*?)</\\1>#is", $data, $match))
			{
				$content = implode("\n", $match[2]);
			}
		}

		return $content;
	}

	function ParseTagsZip($entry)
	{
		$data = zip_entry_read($entry, zip_entry_filesize($entry));
		if(preg_match("#<cp:keywords>(.*)</cp:keywords>#is", $data, $match))
			return $match[1];
		else
			return "";
	}
}

class CIntranetSearchConverter_pptx extends CIntranetSearchConverter
{
	function ParseDocZip($entry)
	{
		$content = "";
		$data = zip_entry_read($entry, zip_entry_filesize($entry));
		if($data)
		{
			$text_len = defined('BX_UTF')? mb_strlen($data, 'latin1'): strlen($data);
			$this->Check_pcre_limit($text_len);
			if(preg_match_all("#<(a:t)>(.*?)</\\1>#is", $data, $match))
			{
				$content = implode("\n", $match[2]);
			}
		}

		return $content;
	}

	function ParseTagsZip($entry)
	{
		$data = zip_entry_read($entry, zip_entry_filesize($entry));
		if(preg_match("#<cp:keywords>(.*)</cp:keywords>#is", $data, $match))
			return $match[1];
		else
			return "";
	}
}

class CIntranetSearchConverter_odt extends CIntranetSearchConverter
{
	function ParseDocZip($entry)
	{
		$content = "";
		while($data = zip_entry_read($entry, 102400))
		{
			$text_len = defined('BX_UTF')? mb_strlen($data, 'latin1'): strlen($data);
			$this->Check_pcre_limit($text_len);

			$data = preg_replace("#(<.*?>)#", " ", $data);
			$content .= preg_replace('#[\s\n\r]+#', ' ', $data);
		}

		$text_len = defined('BX_UTF')? mb_strlen($content, 'latin1'): strlen($content);
		$this->Check_pcre_limit($text_len);

		return preg_replace("#(<.*?>)#", "", $content);
	}

	function ParseTagsZip($entry)
	{
		$data = zip_entry_read($entry, zip_entry_filesize($entry));
		if(preg_match_all("#<meta:keyword>(.*?)</meta:keyword>#is", $data, $match))
			return implode(",", $match[1]);
		else
			return "";
	}
}

class CIntranetSearchConverter_ods extends CIntranetSearchConverter_odt
{
	function ParseDocZip($entry)
	{
		$content = "";
		$data = zip_entry_read($entry, zip_entry_filesize($entry));
		if($data)
		{
			$text_len = defined('BX_UTF')? mb_strlen($data, 'latin1'): strlen($data);
			$this->Check_pcre_limit($text_len);
			if(preg_match_all("#<(text:p)>(.*?)</\\1>#is", $data, $match))
			{
				$content = implode("\n", $match[2]);
			}
		}

		return $content;
	}
}

class CIntranetSearchConverter_rtf extends CIntranetSearchConverter
{
	function ProcessFile($absolute_path)
	{
		global $APPLICATION;
		if(file_exists($absolute_path) && is_file($absolute_path))
		{
			$fp = fopen($absolute_path, 'rb');
			if($fp)
			{
				//windows-1251 is forced charset
				//make in an option ?
				return $this->ParseFile($fp, "windows-1251");
			}
		}
		return false;
	}

	function ParseFile($f, $charset = false)
	{
		global $APPLICATION;

		$result = "";

		$para_mode = 0;
		$data_skip_mode = 0;
		$groups = array();
		$groups[] = array();
		$group_count = 0;
		$group_store = 20;
		$bufptr = -1;
		$groups[0]["uc"] = 2;
		while(!feof($f))
		{
			$c = fgetc($f);
			if(feof($f))
				break;
			switch ($c)
			{
			case '\\': {
				if(!is_array($com = $this->NextCommand($f)))
					break;
				switch($com["name"])
				{
				case "RTF_SPEC_CHAR":
					if($com["numarg"] == "*" && $data_skip_mode == 0)
					{
						$data_skip_mode = $group_count;
					}
					elseif($com["numarg"] == "\r")
					{
						$result .= "\n";
					}
					elseif($com["numarg"] == "~")
					{
						$result .= "&nbsp;";

					}
					elseif($com["numarg"] == "-")
					{
						$result .= "-";/* Optional hyphen */
					}
					break;
				case "emdash":
				case "emspace":
					$result .= "&mdash;";
					break;
				case "endash":
				case "enspace":
					$result .= "&ndash;";
					break;
				case "bullet":
					$result .= "<li>";
					break;
				case "lquote":
				case "rquote":
					$result .= "'";
					break;
				case "ldblquote":
				case "rdblquote":
					$result .= "\"";
					break;
				case "zwnj":
					//add_to_buffer(&bufptr,0xfeff);
					break;
				case "RTF_CHAR":
					if($data_skip_mode == 0)
					{
						$result .= $APPLICATION->ConvertCharset(chr($com["numarg"]), $charset, "UTF-8");
					}
					break;
				case "uc":
					$groups[$group_count]["uc"] = $com["numarg"];
					break;
				case "tab":
					$result .= "\t";
					break;
				case "u":
					if($com["numarg"] < 0)
						break;
					if($data_skip_mode == 0)
						$result .= $GLOBALS["APPLICATION"]->ConvertCharset(chr($com["numarg"] & 255).chr($com["numarg"] >> 8), "UTF-16", "UTF-8");
					$i = $groups[$group_count]["uc"];

					while((--$i) >= 0)
					{
						$cc = fgetc($f);
						if($cc == "\\")
						{
							for($j = 0; $j < 3; $j++)
								$cc = fgetc($f);
						}
					}
					break;
				case "par":
					$result .= "\n";
					$para_mode = $group_count;
					break;
				case "pict":
				case "fonttbl":
				case "info":
				case "colortbl":
				case "stylesheet":
				case "listtable":
				case "listoverridetable":
				case "rsidtbl":
				case "generator":
				case "datafield":
					if($data_skip_mode == 0)
					{
						$data_skip_mode = $group_count;
					}
					break;
				case "lang":
					break;
				case "ansicpg":
					//TODO!
					//if(!$charset)
						//$charset = some_table $com["numarg"]
					break;
				default:
				}
				break;
			}
			case '{':
				$group_count++;
				$groups[] = array();
				if($para_mode)
					$result .= " ";
				$groups[$group_count] = $groups[$group_count-1];
				break;
			case '}':
				$group_count--;
				if($group_count < 0)
					$group_count=0;
				if($para_mode > 0 && $para_mode > $group_count)
				{
					$para_mode = 0;
				}
				if($data_skip_mode > $group_count)
				{
					$data_skip_mode = 0;
				}
				break;
			default:
				if($data_skip_mode == 0)
					if($c != "\n" && $c != "\r")
						$result .= $APPLICATION->ConvertCharset($c, $charset, "UTF-8");
			}
		}
		return $result;
	}

	function NextCommand($f)
	{
		$c = fgetc($f);
		$command = array(
			"name" => "",
		);
		if(preg_match("/^[a-zA-Z]+$/", $c))
		{
			$name_count = 1;
			$command["name"] = $c;
			while(preg_match("#^[a-zA-Z]+$#", $c = fgetc($f)))
			{
				if(feof($f))
					return false;
				$command["name"] .= $c;
			}
			if(preg_match("/^[0-9-]+$/", $c))
			{
				$command["numarg"] = $c;
				while(preg_match("#^[0-9-]+$#", $c = fgetc($f)))
				{
					if(feof($f))
						break;
					$command["numarg"] .= $c;
				}
				$command["numarg"] = intval($command["numarg"]);
			}
			else
			{
				$command["numarg"] = 0;
			}

			if(!($c == " " || $c == "\t"))
				fseek($f, -1, SEEK_CUR);
		}
		else
		{
			$command["name"] = $c;
			if($c == "'")
			{
				$command["name"] = "RTF_CHAR";
				$command["numarg"] = "";
				for($i = 0;$i < 2; $i++)
				{
					$c = fgetc($f);
					if(preg_match("/^[0-9a-f]+$/", $c))
					{
						if(feof($f))
							return false;
						$command["numarg"] .= $c;
					}
					else
					{
						fseek($f, -1, SEEK_CUR);
					}
				}
				$command["numarg"] = hexdec($command["numarg"]);
			}
			else
			{
				$command["name"] = "RTF_SPEC_CHAR";
				$command["numarg"] = $c;
			}
		}
		return $command;
	}
}

class CIntranetSearch
{
	var $arIndexFields = false;
	var $arIndexUserFields = false;
	var $arSites = false;

	function __construct()
	{
		return $this->CIntranetSearch();
	}

	function CIntranetSearch()
	{
		$this->arIndexFields = array(
			"ACTIVE",
			"LAST_NAME",
			"NAME",
			"SECOND_NAME",
			"EMAIL",
			"PERSONAL_PROFESSION",
			"PERSONAL_WWW",
			"PERSONAL_ICQ",
			"PERSONAL_PHONE",
			"PERSONAL_FAX",
			"PERSONAL_MOBILE",
			"PERSONAL_PAGER",
			"PERSONAL_STREET",
			"PERSONAL_MAILBOX",
			"PERSONAL_CITY",
			"PERSONAL_STATE",
			"PERSONAL_ZIP",
			"WORK_COMPANY",
			"WORK_DEPARTMENT",
			"WORK_POSITION",
			"WORK_WWW",
			"WORK_PHONE",
			"WORK_FAX",
			"WORK_PAGER",
			"WORK_STREET",
			"WORK_MAILBOX",
			"WORK_CITY",
			"WORK_STATE",
			"WORK_ZIP",
			"WORK_PROFILE",
		);

		$this->arIndexUserFields = array();
		$rsUFs = CUserTypeEntity::getList(array(), array('ENTITY_ID' => 'USER', 'IS_SEARCHABLE' => 'Y'));
		while ($arUF = $rsUFs->fetch())
			$this->arIndexUserFields[] = $arUF['FIELD_NAME'];

		static $arSites = false;
		if(!$arSites)
		{
			$arSites = array();
			$rsSites = CSite::GetList(($b=""), ($o=""), array());
			while($arSite = $rsSites->Fetch())
			{
				if (!CModule::IncludeModule("extranet") || (CExtranet::GetExtranetSiteID() != $arSite["LID"]))
					$arSites[] = $arSite["LID"];
			}
		}
		$this->arSites = $arSites;
	}

	function OnSearchReindex($NS=Array(), $oCallback=NULL, $callback_method="")
	{
		global $DB;

		if($NS["MODULE"]=="intranet" && strlen($NS["ID"])>0)
		{
			$entity = substr($NS["ID"], 0, 1);
			$ID = substr($NS["ID"], 1);
		}
		else
		{
			$entity = "U";
			$ID = 0;
		}

		$obj = new CIntranetSearch;

		if($entity === "U")
		{
			$arFilter = array(
				">ID" => $ID,
				"ACTIVE" => "Y",
			);
			//When extranet module is installed and configured
			//we should not search for users w/o departments assigned
			if(IsModuleInstalled('extranet') && strlen(COption::GetOptionString("extranet", "extranet_site")))
				$arFilter["!UF_DEPARTMENT"] = false;

			$rsUsers = CUser::GetList($by = "ID", $order = "asc", $arFilter);
			while($arUser = $rsUsers->Fetch())
			{
				$BODY = "";
				foreach($obj->arIndexFields as $key)
					$BODY .= " ".$arUser[$key];
				$BODY .= $GLOBALS["USER_FIELD_MANAGER"]->OnSearchIndex("USER", $arUser["ID"]);

				$arResult = Array(
					"ID" => "U".$arUser["ID"],
					"LAST_MODIFIED" => $arUser["DATE_REGISTER"],
					"TITLE" => CUser::FormatName(
							CSite::GetNameFormat(false),
							array(
								"NAME" => $arUser["NAME"],
								"LAST_NAME" => $arUser["LAST_NAME"],
								"SECOND_NAME" => $arUser["SECOND_NAME"],
								"LOGIN" => $arUser["LOGIN"]
							),
							false, false
						),
					"BODY" => $BODY,
					//"TAGS" => $arIBlockElement["TAGS"],
					"PARAM1" => "USER",
					//"PARAM2" => $IBLOCK_ID,
					//"DATE_FROM"=>(strlen($arIBlockElement["DATE_FROM"])>0? $arIBlockElement["DATE_FROM"] : false),
					//"DATE_TO"=>(strlen($arIBlockElement["DATE_TO"])>0? $arIBlockElement["DATE_TO"] : false),
					"SITE_ID" => $obj->arSites,
					"PERMISSIONS" => array(2),
					"URL" => "=ID=".$arUser["ID"],
				);

				$res = call_user_func(array($oCallback, $callback_method), $arResult);
				if(!$res)
					return "U".$arUser["ID"];
			}
		}

		return false;
	}

	function OnSearchGetURL($arFields)
	{
		if($arFields["MODULE_ID"] !== "intranet" || substr($arFields["URL"], 0, 1) !== "=")
			return $arFields["URL"];

		parse_str(ltrim($arFields["URL"], "="), $arr);
		if(substr($arFields["ITEM_ID"], 0, 1) === "U")
		{
			$url = COption::GetOptionString("intranet", "search_user_url");
			$url = str_replace("#USER_ID#", $arr["ID"], $url);
			foreach($arr as $k => $v)
				$url = str_replace("#".$k."#", $v, $url);
			return $url;
		}
		return "";
	}

	function OnUserUpdate($arFields)
	{
		$obj = new CIntranetSearch;
		if (!array_intersect(array_merge($obj->arIndexFields, $obj->arIndexUserFields), array_keys($arFields)))
			return;

		if(CModule::IncludeModule('search'))
		{
			$rsUser = CUser::GetByID($arFields["ID"]);
			$arOldUser = $rsUser->Fetch();
			if($arOldUser)
			{
				$arUser = $arFields;
				foreach($arOldUser as $key => $value)
					if(!array_key_exists($key, $arUser))
						$arUser[$key] = $arOldUser[$key];

				if(isset($arUser["UF_DEPARTMENT"]) && is_array($arUser["UF_DEPARTMENT"]))
					$UF_DEPARTMENT = array_pop($arUser["UF_DEPARTMENT"]);
				else
					$UF_DEPARTMENT = 0;

				if(
					array_key_exists("ACTIVE", $arUser)
					&& $arUser["ACTIVE"] !== "Y"
				)
				{	//Delete from index
					$arResult = Array(
						"TITLE" => "",
						"BODY" => "",
					);
				}
				elseif(
					IsModuleInstalled('extranet')
					&& strlen(COption::GetOptionString("extranet", "extranet_site")) >= 0
					&& intval($UF_DEPARTMENT) <= 0
				)
				{
					//When extranet module is installed and configured
					//we should not search for users w/o departments assigned
					//so delete such a user from search index
					$arResult = Array(
						"TITLE" => "",
						"BODY" => "",
					);
				}
				else
				{
					$BODY = "";
					foreach($obj->arIndexFields as $key)
						$BODY .= " ".$arUser[$key];
					$BODY .= $GLOBALS["USER_FIELD_MANAGER"]->OnSearchIndex("USER", $arUser["ID"]);

					$arResult = Array(
						"LAST_MODIFIED" => $arUser["DATE_REGISTER"],
						"TITLE" => CUser::FormatName(
							CSite::GetNameFormat(false),
							array(
								"NAME" => $arUser["NAME"],
								"LAST_NAME" => $arUser["LAST_NAME"],
								"SECOND_NAME" => $arUser["SECOND_NAME"],
								"LOGIN" => $arUser["LOGIN"],
							), false, false
						),
						"BODY" => $BODY,
						//"TAGS" => $arIBlockElement["TAGS"],
						"PARAM1" => "USER",
						//"PARAM2" => $IBLOCK_ID,
						//"DATE_FROM"=>(strlen($arIBlockElement["DATE_FROM"])>0? $arIBlockElement["DATE_FROM"] : false),
						//"DATE_TO"=>(strlen($arIBlockElement["DATE_TO"])>0? $arIBlockElement["DATE_TO"] : false),
						"SITE_ID" => $obj->arSites,
						"PERMISSIONS" => array(2),
						"URL" => "=ID=".$arUser["ID"],
					);
				}

				CSearch::Index("intranet", "U".$arUser["ID"], $arResult, true);
			}
		}
	}

	function OnUserAdd($arFields)
	{
		CIntranetSearch::OnUserUpdate($arFields);
	}

	function OnUserDelete($ID)
	{
		if(CModule::IncludeModule('search'))
		{
			CSearch::Index("intranet", "U".$ID, array(
				"TITLE" => "",
				"BODY" => "",
			), true);
		}
	}

	/*Disallow blog user entries in search index*/
	function ExcludeBlogUser($arFields)
	{
		/*
		if(is_array($arFields) && $arFields["MODULE_ID"] == "blog")
		{
			$entity = substr($arFields["ITEM_ID"], 0, 1);
			if($entity == "U" || $entity == "B")
			{
				$arFields["BODY"] = "";
				$arFields["TITLE"] = "";
			}
		}
		*/
		//Anton Ezhkov desided nothing bad in it.
		return $arFields;
	}
}
?>