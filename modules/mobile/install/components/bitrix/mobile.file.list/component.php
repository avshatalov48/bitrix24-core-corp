<?
if(!defined("B_PROLOG_INCLUDED")||B_PROLOG_INCLUDED!==true)die();

$GLOBALS["APPLICATION"]->SetPageProperty("BodyClass", "fl-page");

CUtil::InitJSCore(array('ajax'));

if (intval($arParams["THUMBNAIL_SIZE"]) <= 0)
	$arParams["THUMBNAIL_SIZE"] = 100;

$arResult = array(
	"FILES" => array(),
	"ELEMENTS" => array()
);

$arResult["diskEnabled"] = (\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) && CModule::includeModule('disk'));

$varKeyDocs = (
	isset($arParams["POST_ID"]) 
	&& intval($arParams["POST_ID"]) > 0 
		? "MFU_UPLOADED_DOCS_".$GLOBALS["USER"]->GetId()."_".intval($arParams["POST_ID"]) 
		: "MFU_UPLOADED_DOCS_".$GLOBALS["USER"]->GetId()
);
$varKeyFiles = (
	isset($arParams["POST_ID"]) 
	&& intval($arParams["POST_ID"]) > 0 
		? "MFU_UPLOADED_FILES_".$GLOBALS["USER"]->GetId()."_".intval($arParams["POST_ID"]) 
		: "MFU_UPLOADED_FILES_".$GLOBALS["USER"]->GetId()
);
$varKeyImages = (
	isset($arParams["POST_ID"]) 
	&& intval($arParams["POST_ID"]) > 0 
		? "MFU_UPLOADED_IMAGES_".$GLOBALS["USER"]->GetId()."_".intval($arParams["POST_ID"]) 
		: false
);

if (
	is_array($_SESSION[$varKeyImages]) 
	&& !empty($_SESSION[$varKeyImages])
)
{
	foreach ($_SESSION[$varKeyImages] as $key => $value)
	{
		if (intval($value) <= 0)
		{
			unset($_SESSION[$varKeyImages][$key]);
		}
	}
}

if (
	is_array($_SESSION[$varKeyDocs]) 
	&& !empty($_SESSION[$varKeyDocs])
)
{
	foreach ($_SESSION[$varKeyDocs] as $key => $value)
	{
		if (intval($value) <= 0)
		{
			unset($_SESSION[$varKeyDocs][$key]);
		}
	}
}

if (
	(
		$arResult["diskEnabled"] 
		|| CModule::IncludeModule("webdav")
	)
	&& is_array($_SESSION[$varKeyDocs]) 
	&& !empty($_SESSION[$varKeyDocs])
)
{
	if ($arResult["diskEnabled"])
	{
		$storage = \Bitrix\Disk\Driver::getInstance()->getStorageByUserId($GLOBALS["USER"]->GetID());
		if ($storage)
		{
			$folder = $storage->getFolderForUploadedFiles($GLOBALS["USER"]->GetID());
			if ($folder)
			{
				$securityContext = $storage->getCurrentUserSecurityContext();
				$children = $folder->getChildren($securityContext, array('filter' => array("ID" => $_SESSION[$varKeyDocs])));
				foreach($children as $oDiskFile)
				{
					$rsFile = CFile::GetByID($oDiskFile->getFileId());
					if ($arFile = $rsFile->Fetch())
					{
						if (CFile::IsImage($arFile["FILE_NAME"], $arFile["CONTENT_TYPE"]))
						{
							$image_resize = CFile::ResizeImageGet(
								$arFile["ID"], 
								array(
									"width" => $arParams["THUMBNAIL_SIZE"], 
									"height" => $arParams["THUMBNAIL_SIZE"]
								),
								($arParams["THUMBNAIL_RESIZE_METHOD"] == "EXACT" ? BX_RESIZE_IMAGE_EXACT : BX_RESIZE_IMAGE_PROPORTIONAL),
								true,
								false,
								false
							);

							$arResult["ELEMENTS"][] = array(
								"id" => $oDiskFile->getID(),
								"src" => $image_resize["src"],
								"name" => $arFile["ORIGINAL_NAME"]
							);
						}
						else
						{
							$arResult["ELEMENTS"][] = array(
								"id" => $oDiskFile->getID(),
								"src" => "",
								"name" => $arFile["ORIGINAL_NAME"]
							);
						}
					}
				}
			}
		}
	}
	else
	{
		$data = CWebDavIblock::getRootSectionDataForUser($GLOBALS["USER"]->GetID());
		if (is_array($data))
		{
			$ibe = new CIBlockElement();
			$dbWDFile = $ibe->GetList(
				array(), 
				array(
					'ID' => $_SESSION[$varKeyDocs],
					'IBLOCK_ID' => $data["IBLOCK_ID"]
				), 
				false, 
				false,
				array('ID', 'IBLOCK_ID', 'PROPERTY_FILE')
			);
			while ($arWDFile = $dbWDFile->Fetch())
			{
				$rsFile = CFile::GetByID($arWDFile["PROPERTY_FILE_VALUE"]);
				if ($arFile = $rsFile->Fetch())
				{
					if (CFile::IsImage($arFile["FILE_NAME"], $arFile["CONTENT_TYPE"]))
					{
						$image_resize = CFile::ResizeImageGet(
							$arFile["ID"], 
							array(
								"width" => $arParams["THUMBNAIL_SIZE"], 
								"height" => $arParams["THUMBNAIL_SIZE"]
							),
							($arParams["THUMBNAIL_RESIZE_METHOD"] == "EXACT" ? BX_RESIZE_IMAGE_EXACT : BX_RESIZE_IMAGE_PROPORTIONAL),
							true,
							false,
							false
						);

						$arResult["ELEMENTS"][] = array(
							"id" => $arWDFile["ID"],
							"src" => $image_resize["src"],
							"name" => $arFile["ORIGINAL_NAME"]
						);
					}
					else
					{
						$arResult["ELEMENTS"][] = array(
							"id" => $arWDFile["ID"],
							"src" => "",
							"name" => $arFile["ORIGINAL_NAME"]
						);
					}
				}
			}
		}
	}
}
elseif (is_array($_SESSION[$varKeyFiles]))
{
	foreach($_SESSION[$varKeyFiles] as $fileID)
	{
		$rsFile = CFile::GetByID($fileID);
		if ($arFile = $rsFile->Fetch())
		{
			if (CFile::IsImage($arFile["FILE_NAME"], $arFile["CONTENT_TYPE"]))
			{
				$image_resize = CFile::ResizeImageGet(
					$arFile, 
					array(
						"width" => $arParams["THUMBNAIL_SIZE"], 
						"height" => $arParams["THUMBNAIL_SIZE"]
					),
					($arParams["THUMBNAIL_RESIZE_METHOD"] == "EXACT" ? BX_RESIZE_IMAGE_EXACT : BX_RESIZE_IMAGE_PROPORTIONAL),
					true,
					false,
					false
				);

				$arResult["FILES"][] = array(
					"id" => $fileID,
					"src" => $image_resize["src"],
					"name" => $arFile["ORIGINAL_NAME"]
				);
			}
			else
			{
				$arResult["FILES"][] = array(
					"id" => $fileID,
					"src" => "",
					"name" => $arFile["ORIGINAL_NAME"]
				);
			}
		}
	}
}

if (is_array($_SESSION[$varKeyImages]))
{
	foreach($_SESSION[$varKeyImages] as $fileID)
	{
		$rsFile = CFile::GetByID($fileID);
		if ($arFile = $rsFile->Fetch())
		{
			if (CFile::IsImage($arFile["FILE_NAME"], $arFile["CONTENT_TYPE"]))
			{
				$image_resize = CFile::ResizeImageGet(
					$arFile, 
					array(
						"width" => $arParams["THUMBNAIL_SIZE"], 
						"height" => $arParams["THUMBNAIL_SIZE"]
					),
					($arParams["THUMBNAIL_RESIZE_METHOD"] == "EXACT" ? BX_RESIZE_IMAGE_EXACT : BX_RESIZE_IMAGE_PROPORTIONAL),
					true,
					false,
					false
				);

				$arResult["FILES"][] = array(
					"id" => $fileID,
					"src" => $image_resize["src"],
					"name" => $arFile["ORIGINAL_NAME"]
				);
			}
			else
			{
				$arResult["FILES"][] = array(
					"id" => $fileID,
					"src" => "",
					"name" => $arFile["ORIGINAL_NAME"]
				);
			}
		}
	}
}

$this->IncludeComponentTemplate();
?>