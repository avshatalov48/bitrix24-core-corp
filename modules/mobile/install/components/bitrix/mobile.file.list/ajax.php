<?
define("NO_KEEP_STATISTIC", true);
define("BX_STATISTIC_BUFFER_USED", false);
define("NO_LANG_FILES", true);
define("NOT_CHECK_PERMISSIONS", true);
define("BX_PUBLIC_TOOLS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/bx_root.php");

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

if(CModule::IncludeModule("compression"))
{
	CCompress::Disable2048Spaces();
}

$file_id = intval($_POST["file_id"]);
$element_id = intval($_POST["element_id"]);
$action = $_POST["action"];

if (!$GLOBALS["USER"]->IsAuthorized())
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'CURRENT_USER_NOT_AUTH'));
	die();
}

if (check_bitrix_sessid())
{
	if ($action == "delete")
	{
		if ($file_id <= 0)
		{
			echo CUtil::PhpToJsObject(Array('ERROR' => 'NO_FILE'));
			die();
		}

		$bFileFound = false;

		// UF
		$varKey = (
			isset($_REQUEST["post_id"]) 
			&& intval($_REQUEST["post_id"]) > 0 
				? "MFU_UPLOADED_FILES_".$GLOBALS["USER"]->GetId()."_".intval($_REQUEST["post_id"]) 
				: "MFU_UPLOADED_FILES_".$GLOBALS["USER"]->GetId()
		);
		if (in_array($file_id, $_SESSION[$varKey]))
		{
			$rsFile = CFile::GetByID($file_id);
			if ($arFile = $rsFile->Fetch())
			{
				$bFileFound = true;
				if (
					!isset($_REQUEST["post_id"]) 
					|| intval($_REQUEST["post_id"]) <= 0
				)
				{
					CFile::Delete($file_id);
				}

				foreach($_SESSION[$varKey] as $key => $session_file_id)
				{
					if ($session_file_id == $file_id)
					{
						unset($_SESSION[$varKey][$key]);
						break;
					}
				}
				
				echo CUtil::PhpToJsObject(Array('SUCCESS' => 'Y', "FILE_ID" => $file_id));
			}
		}

		if (
			isset($_REQUEST["post_id"]) 
			&& intval($_REQUEST["post_id"]) > 0
		)
		{
			// Blog Images
			$varKey = "MFU_UPLOADED_IMAGES_".$GLOBALS["USER"]->GetId()."_".intval($_REQUEST["post_id"]);
			if (in_array($file_id, $_SESSION[$varKey]))
			{
				$rsFile = CFile::GetByID($file_id);
				if ($arFile = $rsFile->Fetch())
				{
					$bFileFound = true;

					if (CModule::IncludeModule('blog'))
					{
						$rsBlogImage = CBlogImage::GetList(
							array(),
							array("FILE_ID" => $file_id)
						);
						if ($arBlogImage = $rsBlogImage->Fetch())
						{
							CBlogImage::Delete($arBlogImage["ID"]);
							BXClearCache(true, "/blog/socnet_post/".intval($_REQUEST["post_id"])."/");
							BXClearCache(true, "/blog/socnet_post/gen/".intval($_REQUEST["post_id"])."/");
						}
					}

					CFile::Delete($file_id);

					foreach($_SESSION[$varKey] as $key => $session_file_id)
					{
						if ($session_file_id == $file_id)
						{
							unset($_SESSION[$varKey][$key]);
							break;
						}
					}

					echo CUtil::PhpToJsObject(Array('SUCCESS' => 'Y', "FILE_ID" => $file_id));
				}
			}
		}

		if (!$bFileFound)
		{
			echo CUtil::PhpToJsObject(Array('ERROR' => 'NO_FILE'));
			die();
		}
	}
	elseif ($action == "delete_element")
	{
		$diskEnabled = (\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) && CModule::includeModule('disk'));
		$varKey = (
			isset($_REQUEST["post_id"]) 
			&& intval($_REQUEST["post_id"]) > 0 
				? "MFU_UPLOADED_DOCS_".$GLOBALS["USER"]->GetId()."_".intval($_REQUEST["post_id"]) 
				: "MFU_UPLOADED_DOCS_".$GLOBALS["USER"]->GetId()
		);
		if ($diskEnabled)
		{
			$storage = \Bitrix\Disk\Driver::getInstance()->getStorageByUserId($GLOBALS["USER"]->GetID());
			if (!$storage)
			{
				echo CUtil::PhpToJsObject(Array('ERROR' => 'NO_DISK_STORAGE'));
				die();
			}

			$folder = $storage->getFolderForUploadedFiles($GLOBALS["USER"]->GetID());
			if (!$folder)
			{
				echo CUtil::PhpToJsObject(Array('ERROR' => 'NO_DISK_FOLDER'));
				die();
			}
		}
		elseif (CModule::IncludeModule("webdav"))
		{
			$data = CWebDavIblock::getRootSectionDataForUser($GLOBALS["USER"]->GetID());
			if (is_array($data))
			{
				$ob = new CWebDavIblock($data["IBLOCK_ID"], "", array(
					"ROOT_SECTION_ID" => $data["SECTION_ID"],
					"DOCUMENT_TYPE" => array("webdav", 'CIBlockDocumentWebdavSocnet', 'iblock_'.$data['SECTION_ID'].'_user_'.intval($GLOBALS["USER"]->GetID()))
				));		
			}

			if (!$ob)
			{
				echo CUtil::PhpToJsObject(Array('ERROR' => 'NO_WEBDAV_INIT'));
				die();
			}
		}


		if (
			$element_id <= 0
			|| !in_array($element_id, $_SESSION[$varKey])
		)
		{
			echo CUtil::PhpToJsObject(Array('ERROR' => 'NO_ELEMENT'));
			die();
		}

		if (
			!isset($_REQUEST["post_id"]) 
			|| intval($_REQUEST["post_id"]) <= 0
		)
		{
			if ($storage && $folder)
			{
				$securityContext = $storage->getCurrentUserSecurityContext();
				$children = $folder->getChildren($securityContext, array('filter' => array("ID" => $element_id)));
				foreach($children as $oDiskFile)
				{
					$res = $oDiskFile->delete($GLOBALS["USER"]->GetId());
					if (!$res)
					{
						echo CUtil::PhpToJsObject(Array('ERROR' => 'ERROR_DISK_FILE_DELETE'));
						die();
					}
				}
			}
			elseif ($ob)
			{
				$res = $ob->delete(array('element_id' => $element_id));
				if (intval($res) != 204)
				{
					echo CUtil::PhpToJsObject(Array('ERROR' => $ob->LAST_ERROR));
					die();
				}
			}
		}			

		foreach($_SESSION[$varKey] as $key => $session_element_id)
		{
			if ($session_element_id == $element_id)
			{
				unset($_SESSION[$varKey][$key]);
				break;
			}
		}

		echo CUtil::PhpToJsObject(Array('SUCCESS' => 'Y', "ELEMENT_ID" => $element_id));
	}	
}
else
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'SESSION_ERROR'));
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
die();
?>