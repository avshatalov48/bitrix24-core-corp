<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if (!function_exists('_getIBlockItemsCount'))
{
     function _getIBlockItemsCount($ib, $section)
     {
         $res = CIBlockElement::GetList( array(), Array("IBLOCK_ID"=>intval($ib), "SECTION_ID" => intval($section), "INCLUDE_SUBSECTIONS" => "Y", "ACTIVE" => "Y"));
         if ($res) 
         {
             $res->NavStart();
             return $res->NavRecordCount;
         } else return 0;
     }
}

if (!function_exists('_getName'))
{
    function _getName($path)
    {
        static $dav;
        if (empty($dav))
            $dav = (CWebDavBase::IsDavHeaders('check_all')?'D':'W');
        if ($dav == 'W')
        {
            $arPath = explode('/', trim($path, '/'));
            return $arPath[sizeof($arPath)-1];
        } else 
            return $path;
    }
}

if (!function_exists("_wd_aggregator_sort"))
{
    function _wd_aggregator_sort($res1, $res2)
    {
        return ($res1['NAME'] < $res2['NAME'] ? -1 : 1); 
    }
}

if (!function_exists("__wd_check_uf_use_bp_property"))
{
	function __wd_check_uf_use_bp_property($iblock_id)
	{
		$iblock_id = intval($iblock_id); 
		$db_res = CUserTypeEntity::GetList(array($by=>$order), array("ENTITY_ID" => "IBLOCK_".$iblock_id."_SECTION", "FIELD_NAME" => "UF_USE_BP"));
		if (!$db_res || !($res = $db_res->GetNext()))
		{
			$arFields = Array(
				"ENTITY_ID" => "IBLOCK_".$iblock_id."_SECTION",
				"FIELD_NAME" => "UF_USE_BP",
				"USER_TYPE_ID" => "string",
				"MULTIPLE" => "N",
				"MANDATORY" => "N", 
				"SETTINGS" => array("DEFAULT_VALUE" => "Y"));
			$arFieldName = array();
			$rsLanguage = CLanguage::GetList($by, $order, array());
			while($arLanguage = $rsLanguage->Fetch()):
				$dir = str_replace(array("\\", "//"), "/", dirname(__FILE__)); 
				$dirs = explode("/", $dir); 
				array_pop($dirs); 
				$file = trim(implode("/", $dirs)."/lang/".$arLanguage["LID"]."/include/webdav_settings.php");
				$tmp_mess = __IncludeLang($file, true);
				$arFieldName[$arLanguage["LID"]] = (empty($tmp_mess["SONET_UF_USE_BP"]) ? "Use Business Process" : $tmp_mess["SONET_UF_USE_BP"]);
			endwhile;
			$arFields["EDIT_FORM_LABEL"] = $arFieldName;
			$obUserField  = new CUserTypeEntity;
			$obUserField->Add($arFields);
			$GLOBALS["USER_FIELD_MANAGER"]->arFieldsCache = array();
		}
	}
}

if (!function_exists('_getPath'))
{
    function _getPath($path, $sef_folder)
    {
        static $dav;
        if (empty($dav))
            $dav = (CWebDavBase::IsDavHeaders('check_all')?'D':'W');
        if ($dav == 'W')
        {
            $spath = substr($path,  strlen($sef_folder)-1);
            if (empty($spath)) $spath .= '/';
            return $spath;
        } else 
            return $path;
    }
}


if (!function_exists('_uencode'))
{
    function _uencode($t)
    {
        if (SITE_CHARSET != "UTF-8")
        {
            global $APPLICATION;
            $t = $APPLICATION->ConvertCharset($t, SITE_CHARSET, "UTF-8");
        }
        return $t;
    }
}


if (!function_exists('ParseFolderTreeData'))
{
    function ParseFolderTreeData($obTree, $pathPrefix, $addDepth = 0, $addLinks = false)
    {
        if (sizeof($obTree) == 0) return array();
        $folderTree = array();
        $saveFields = array('NAME', 'PATH', 'DEPTH_LEVEL', 'TIMESTAMP_X', 'MODIFIED_BY');
        $obKeys = array_keys($obTree);
        foreach ($obKeys as $obKey)
        {
            $obFields = array_keys($obTree[$obKey]);
            foreach ($obFields as $obField)
            {
                if (array_search($obField, $saveFields) === false)
                    unset($obTree[$obKey][$obField]);
            }
            $obTree[$obKey]['DEPTH_LEVEL'] += $addDepth;
            $obTree[$obKey]['PATH'] = $pathPrefix . $obTree[$obKey]['PATH'];
            $obTree[$obKey]['NAME'] = $pathPrefix . $obTree[$obKey]['NAME'];
            if (!preg_match("'/$'", $obTree[$obKey]['PATH'])) $obTree[$obKey]['PATH'] = $obTree[$obKey]['PATH'].'/';
            $folderTree[] = $obTree[$obKey];
        }
        return $folderTree;
    }
}

if (!function_exists('MakeDavRedirect')) 
{
    function MakeDavRedirect($ob, $currentPageUrl, $baseURL, $path, $is_root = false)
    {
        global $APPLICATION, $USER;
        if ($ob->IsDavHeaders('check_all') || array_search($_SERVER['REQUEST_METHOD'], array('DELETE')) !== false)
        {
            if (!$USER->IsAuthorized())
            {
                $APPLICATION->RestartBuffer();
                CHTTP::SetStatus('401 Unauthorized');
                header('WWW-Authenticate: Basic realm="BitrixWebDav"');
                header('Content-length: 0');
                die();
            }
            if (!$ob->CheckRights($_SERVER['REQUEST_METHOD']))
            {
                $ob->SetStatus('403 Forbidden');
                ShowError(GetMessage("WD_DAV_INSUFFICIENT_RIGHTS")); 
                die();
            }
            elseif (!$ob->IsMethodAllow($_SERVER['REQUEST_METHOD']))
            {
                CHTTP::SetStatus('405 Method not allowed');
                header('Allow: ' . join(',', array_keys($ob->allow)));
                ShowError(GetMessage("WD_DAV_UNSUPORTED_METHOD")); 
                die();
            }
            else  
            {
                $APPLICATION->RestartBuffer();
                if (isset($_SERVER['HTTP_DESTINATION']))
                {
                    $_SERVER['HTTP_DESTINATION'] = urldecode($_SERVER['HTTP_DESTINATION']);
                    $pu = parse_url($_SERVER['HTTP_DESTINATION']);
                    $ob->SetBaseURL($baseURL);
                    if (strpos($pu['path'], $baseURL) === false)
                    {
                        CHTTP::SetStatus('405 Method not allowed');
                        header('Allow: ' . join(',', array_keys($ob->allow)));
                        ShowError(GetMessage("WD_DAV_UNSUPORTED_METHOD")); 
                        die();
                    }
                } else {
                    $ob->SetBaseURL(_uencode($baseURL));
                }
                $ob->SetPath($path);
                $fn = 'base_' . $_SERVER['REQUEST_METHOD'];
                call_user_func(array(&$ob, $fn));
                die();
            }
        } else {
            $ob->SetBaseURL(_uencode($baseURL));
            $ob->SetPath(_uencode(rtrim($path, '/')));
            if ($is_root) return;
            $ob->IsDir();
            if ($ob->arParams['is_file'] )
            {
                $APPLICATION->RestartBuffer();
                $ob->base_GET();
                die();
            } else {
                LocalRedirect($currentPageUrl);
            }
        }
    }
}
?>