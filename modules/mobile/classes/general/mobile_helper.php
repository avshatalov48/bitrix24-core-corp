<?

use Bitrix\Socialnetwork\LogTable;
use Bitrix\Main\Loader;

class CMobileHelper
{
	public static function InitFileStorage()
	{
		static $bInited = false;

		$arResult = array();

		if (!$bInited)
		{
			$bDiskEnabled = (
				\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false)
				&& CModule::includeModule('disk')
			);

			if ($bDiskEnabled)
			{
				$storage = \Bitrix\Disk\Driver::getInstance()->getStorageByUserId($GLOBALS["USER"]->GetID());
				if (!$storage)
				{
					$arResult = array(
						"ERROR_CODE" => "NO_DISC_STORAGE",
						"ERROR_MESSAGE" => "No disk storage"
					);
				}
				else
				{
					$folder = $storage->getFolderForUploadedFiles($GLOBALS["USER"]->GetID());
					if (!$folder)
					{
						$arResult = array(
							"ERROR_CODE" => "NO_DISC_FOLDER",
							"ERROR_MESSAGE" => "No disk folder"
						);
					}
					else
					{
						$arResult = array(
							"DISC_STORAGE" => $storage,
							"DISC_FOLDER" => $folder
						);
					}
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
					$arResult = array(
						"ERROR_CODE" => "NO_WEBDAV_SECTION",
						"ERROR_MESSAGE" => "No webdav section"
					);
				}
				else
				{
					$arResult = array(
						"WEBDAV_DATA" => $data,
						"WEBDAV_IBLOCK_OBJECT" => $ob
					);
				}
			}

			$bInited = true;
		}

		return $arResult;
	}

	public static function SaveFile($arFile, $arFileStorage)
	{
		$arResult = array();

		if (empty($arFile))
		{
			$arResult = array(
				"ERROR_CODE" => "EMPTY_FILE",
				"ERROR_MESSAGE" => "File is empty"
			);
		}

		if (!empty($arFileStorage["DISC_FOLDER"]))
		{
			$file = $arFileStorage["DISC_FOLDER"]->uploadFile(
				$arFile,
				array(
					'NAME' => $arFile["name"],
					'CREATED_BY' => $GLOBALS["USER"]->GetID()
				),
				array(),
				true
			);

			$arResult["ID"] = $file->getId();
		}
		elseif (
			!empty($arFileStorage["WEBDAV_DATA"])
			&& !empty($arFileStorage["WEBDAV_IBLOCK_OBJECT"])
		)
		{
			$dropTargetID = $arFileStorage["WEBDAV_IBLOCK_OBJECT"]->GetMetaID("DROPPED");
			$arParent = $arFileStorage["WEBDAV_IBLOCK_OBJECT"]->GetObject(array("section_id" => $dropTargetID));
			if (!$arParent["not_found"])
			{
				$path = $arFileStorage["WEBDAV_IBLOCK_OBJECT"]->_get_path($arParent["item_id"], false);
				$tmpName = str_replace(array(":", ".", "/", "\\"), "_", ConvertTimeStamp(time(), "FULL"));
				$tmpOptions = array("path" => str_replace("//", "/", $path."/".$tmpName));
				$arParent = $arFileStorage["WEBDAV_IBLOCK_OBJECT"]->GetObject($tmpOptions);
				if ($arParent["not_found"])
				{
					$rMKCOL = $arFileStorage["WEBDAV_IBLOCK_OBJECT"]->MKCOL($tmpOptions);
					if (intval($rMKCOL) == 201)
					{
						$arFileStorage["WEBDAV_DATA"]["SECTION_ID"] = $arFileStorage["WEBDAV_IBLOCK_OBJECT"]->arParams["changed_element_id"];
					}
				}
				else
				{
					$arFileStorage["WEBDAV_DATA"]["SECTION_ID"] = $arParent['item_id'];
					if (!$arFileStorage["WEBDAV_IBLOCK_OBJECT"]->CheckUniqueName($tmpName, $arFileStorage["WEBDAV_DATA"]["SECTION_ID"], $tmpRes))
					{
						$path = $arFileStorage["WEBDAV_IBLOCK_OBJECT"]->_get_path($arFileStorage["WEBDAV_DATA"]["SECTION_ID"], false);
						$tmpName = randString(6);
						$tmpOptions = array("path" => str_replace("//", "/", $path."/".$tmpName));
						$rMKCOL = $arFileStorage["WEBDAV_IBLOCK_OBJECT"]->MKCOL($tmpOptions);
						if (intval($rMKCOL) == 201)
						{
							$arFileStorage["WEBDAV_DATA"]["SECTION_ID"] = $arFileStorage["WEBDAV_IBLOCK_OBJECT"]->arParams["changed_element_id"];
						}
					}
				}
			}

			$options = array(
				"new" => true,
				'dropped' => true,
				"arFile" => $arFile,
				"arDocumentStates" => false,
				"arUserGroups" => array_merge($arFileStorage["WEBDAV_IBLOCK_OBJECT"]->USER["GROUPS"], array("Author")),
				"FILE_NAME" => $arFile["name"],
				"IBLOCK_ID" => $arFileStorage["WEBDAV_DATA"]["IBLOCK_ID"],
				"IBLOCK_SECTION_ID" => $arFileStorage["WEBDAV_DATA"]["SECTION_ID"],
				"USER_FIELDS" => array()
			);

			$GLOBALS['USER_FIELD_MANAGER']->EditFormAddFields($arFileStorage["WEBDAV_IBLOCK_OBJECT"]->GetUfEntity(), $options['USER_FIELDS']);

			$GLOBALS["DB"]->StartTransaction();

			if (!$arFileStorage["WEBDAV_IBLOCK_OBJECT"]->put_commit($options))
			{
				$arResult = array(
					"ERROR_CODE" => "error_put",
					"ERROR_MESSAGE" => $arFileStorage["WEBDAV_IBLOCK_OBJECT"]->LAST_ERROR
				);
				$GLOBALS["DB"]->Rollback();
			}
			else
			{
				$GLOBALS["DB"]->Commit();
				$arResult["ID"] = $options['ELEMENT_ID'];
			}
		}
		else
		{
			$arResult["ID"] = CFile::SaveFile($arFile, $arFile["MODULE_ID"]);
		}

		return $arResult;
	}

	public static function SendPullComment($type, $arFields)
	{
		if (!CModule::IncludeModule("pull"))
		{
			return;
		}

		if ($type == "blog")
		{
			$arCommentParams = Array(
				"ID" => $arFields["COMMENT_ID"],
				"ENTITY_XML_ID" => "BLOG_".$arFields["POST_ID"],
				"FULL_ID" => array(
					"BLOG_".$arFields["POST_ID"],
					$arFields["COMMENT_ID"]
				),
				"ACTION" => "REPLY",
				"APPROVED" => "Y",
				"PANELS" => array(
					"EDIT" => "N",
					"MODERATE" => "N",
					"DELETE" => "N"
				),
				"NEW" => "Y",
				"AUTHOR" => array(
					"ID" => $GLOBALS["USER"]->GetID(),
					"NAME" => $arFields["arAuthor"]["NAME_FORMATED"],
					"URL" => $arFields["arAuthor"]["url"],
					"E-MAIL" => $arFields["arComment"]["AuthorEmail"],
					"AVATAR" => $arFields["arAuthor"]["PERSONAL_PHOTO_resized"]["src"],
					"IS_EXTRANET" => (is_array($GLOBALS["arExtranetUserID"]) && in_array($GLOBALS["USER"]->GetID(), $GLOBALS["arExtranetUserID"])),
				),
				"POST_TIMESTAMP" => $arFields["arComment"]["DATE_CREATE_TS"],
				"POST_TIME" => $arFields["arComment"]["DATE_CREATE_TIME"],
				"POST_DATE" => $arFields["arComment"]["DateFormated"],
				"POST_MESSAGE_TEXT" => $arFields["arComment"]["TextFormated"],
				"POST_MESSAGE_TEXT_MOBILE" => $arFields["arComment"]["TextFormatedMobile"],
				"URL" => array(
					"LINK" => str_replace(
						array("##comment_id#", "#comment_id#"),
						array("", $arFields["COMMENT_ID"]),
						$arFields["arUrl"]["LINK"]
					),
					"EDIT" => "__blogEditComment('".$arFields["COMMENT_ID"]."', '".$arFields["POST_ID"]."');",
					"MODERATE" => str_replace(
						array("#source_post_id#", "#post_id#", "#comment_id#", "&".bitrix_sessid_get()),
						array($arFields["POST_ID"], $arFields["POST_ID"], $arFields["COMMENT_ID"], ""),
						($arFields["arComment"]["CAN_SHOW"] == "Y"
							? $arFields["arUrl"]["SHOW"]
							: ($arFields["arComment"]["CAN_HIDE"] == "Y"
								? $arFields["arUrl"]["HIDE"]
								: ""
							)
						)
					),
					"DELETE" => str_replace(
						array("#source_post_id#", "#post_id#", "#comment_id#", "&".bitrix_sessid_get()),
						array($arFields["POST_ID"], $arFields["POST_ID"], $arFields["COMMENT_ID"], ""),
						$arFields["arUrl"]["DELETE"]
					)
				),
				"AFTER" => "",
				"BEFORE_ACTIONS_MOBILE" => "",
				"AFTER_MOBILE" => ""
			);

			if ($arFields["SHOW_RATING"] == "Y")
			{
				ob_start();
				$GLOBALS["APPLICATION"]->IncludeComponent(
					"bitrix:rating.vote", $arFields["RATING_TYPE"],
					Array(
						"ENTITY_TYPE_ID" => "BLOG_COMMENT",
						"ENTITY_ID" => $arFields["arComment"]["ID"],
						"OWNER_ID" => $arFields["arComment"]["AUTHOR_ID"],
						"USER_VOTE" => $arFields["arRating"][$arFields["arComment"]["ID"]]["USER_VOTE"],
						"USER_HAS_VOTED" => $arFields["arRating"][$arFields["arComment"]["ID"]]["USER_HAS_VOTED"],
						"TOTAL_VOTES" => $arFields["arRating"][$arFields["arComment"]["ID"]]["TOTAL_VOTES"],
						"TOTAL_POSITIVE_VOTES" => $arFields["arRating"][$arFields["arComment"]["ID"]]["TOTAL_POSITIVE_VOTES"],
						"TOTAL_NEGATIVE_VOTES" => $arFields["arRating"][$arFields["arComment"]["ID"]]["TOTAL_NEGATIVE_VOTES"],
						"TOTAL_VALUE" => $arFields["arRating"][$arFields["arComment"]["ID"]]["TOTAL_VALUE"],
						"PATH_TO_USER_PROFILE" => $arFields["arUrl"]["USER"]
					),
					false,
					array("HIDE_ICONS" => "Y")
				);
				$arCommentParams["BEFORE_ACTIONS"] = ob_get_clean();

				ob_start();
				$GLOBALS["APPLICATION"]->IncludeComponent(
					"bitrix:rating.vote", "mobile_comment_".$arFields["RATING_TYPE"],
					Array(
						"ENTITY_TYPE_ID" => "BLOG_COMMENT",
						"ENTITY_ID" => $arFields["arComment"]["ID"],
						"OWNER_ID" => $arFields["arComment"]["AUTHOR_ID"],
						"USER_VOTE" => $arFields["arRating"][$arFields["arComment"]["ID"]]["USER_VOTE"],
						"USER_HAS_VOTED" => $arFields["arRating"][$arFields["arComment"]["ID"]]["USER_HAS_VOTED"],
						"TOTAL_VOTES" => $arFields["arRating"][$arFields["arComment"]["ID"]]["TOTAL_VOTES"],
						"TOTAL_POSITIVE_VOTES" => $arFields["arRating"][$arFields["arComment"]["ID"]]["TOTAL_POSITIVE_VOTES"],
						"TOTAL_NEGATIVE_VOTES" => $arFields["arRating"][$arFields["arComment"]["ID"]]["TOTAL_NEGATIVE_VOTES"],
						"TOTAL_VALUE" => $arFields["arRating"][$arFields["arComment"]["ID"]]["TOTAL_VALUE"],
						"PATH_TO_USER_PROFILE" => $arFields["arUrl"]["USER"]
					),
					false,
					array("HIDE_ICONS" => "Y")
				);
				$arCommentParams["BEFORE_ACTIONS_MOBILE"] = ob_get_clean();
			}

			$arComment["UF"] = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("BLOG_COMMENT", $arFields["arComment"]["ID"], LANGUAGE_ID);
			$arUFResult = self::BuildUFFields($arComment["UF"]);
			$arCommentParams["AFTER"] .= $arUFResult["AFTER"];
			$arCommentParams["AFTER_MOBILE"] .= $arUFResult["AFTER_MOBILE"];

			if($arFields["arComment"]["CAN_EDIT"] == "Y")
			{
				ob_start();

				?><script>
					top.text<?=$arFields["arComment"]["ID"]?> = text<?=$arFields["arComment"]["ID"]?> = '<?=CUtil::JSEscape(\Bitrix\Main\Text\Emoji::decode(htmlspecialcharsBack($arFields["arComment"]["POST_TEXT"])))?>';
					top.title<?=$arFields["arComment"]["ID"]?> = title<?=$arFields["arComment"]["ID"]?> = '<?=(isset($arFields["arComment"]["TITLE"]) ? CUtil::JSEscape(\Bitrix\Main\Text\Emoji::decode($arFields["arComment"]["TITLE"])) : '')?>';
					top.arComFiles<?=$arFields["arComment"]["ID"]?> = [];<?
				?></script><?
				$arCommentParams["AFTER"] .= ob_get_clean();
			}

			CPullWatch::AddToStack('UNICOMMENTSBLOG_'.$arFields["POST_ID"],
				array(
					'module_id' => 'unicomments',
					'command' => 'comment',
					'params' => $arCommentParams
				)
			);
		}
	}

	public static function BuildUFFields($arUF)
	{
		$arResult = array(
			"AFTER" => "",
			"AFTER_MOBILE" => ""
		);

		if (
			is_array($arUF)
			&& count($arUF) > 0
		)
		{
			ob_start();

			$eventHandlerID = false;
			$eventHandlerID = AddEventHandler("main", "system.field.view.file", Array("CSocNetLogTools", "logUFfileShow"));

			foreach ($arUF as $FIELD_NAME => $arUserField)
			{
				if(!empty($arUserField["VALUE"]))
				{
					$GLOBALS["APPLICATION"]->IncludeComponent(
						"bitrix:system.field.view",
						$arUserField["USER_TYPE"]["USER_TYPE_ID"],
						array(
							"arUserField" => $arUserField,
							"MOBILE" => "Y"
						),
						null,
						array("HIDE_ICONS"=>"Y")
					);
				}
			}
			if (
				$eventHandlerID !== false
				&& intval($eventHandlerID) > 0
			)
			{
				RemoveEventHandler('main', 'system.field.view.file', $eventHandlerID);
			}

			$arResult["AFTER_MOBILE"] = ob_get_clean();

			ob_start();

			$eventHandlerID = false;
			$eventHandlerID = AddEventHandler("main", "system.field.view.file", Array("CSocNetLogTools", "logUFfileShow"));

			foreach ($arUF as $FIELD_NAME => $arUserField)
			{
				if(!empty($arUserField["VALUE"]))
				{
					$GLOBALS["APPLICATION"]->IncludeComponent(
						"bitrix:system.field.view",
						$arUserField["USER_TYPE"]["USER_TYPE_ID"],
						array(
							"arUserField" => $arUserField
						),
						null,
						array("HIDE_ICONS"=>"Y")
					);
				}
			}
			if (
				$eventHandlerID !== false
				&& intval($eventHandlerID) > 0
			)
			{
				RemoveEventHandler('main', 'system.field.view.file', $eventHandlerID);
			}

			$arResult["AFTER"] .= ob_get_clean();
		}

		return $arResult;
	}

	public static function getUFForPostForm($arParams)
	{
		$arFileData = array();

		$arUF = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields($arParams["ENTITY_TYPE"], $arParams["ENTITY_ID"], LANGUAGE_ID);
		$ufCode = $arParams["UF_CODE"];
		$previewImageSize = (isset($arParams['PREVIEW_IMAGE_SIZE']) && (int)$arParams['PREVIEW_IMAGE_SIZE'] > 0 ? (int)$arParams['PREVIEW_IMAGE_SIZE'] : 144);

		if (
			!empty($arUF[$ufCode])
			&& !empty($arUF[$ufCode]["VALUE"])
		)
		{
			if ($arParams["IS_DISK_OR_WEBDAV_INSTALLED"])
			{
				if (
					\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false)
					&& CModule::IncludeModule('disk')
				)
				{
					$userFieldManager = \Bitrix\Disk\Driver::getInstance()->getUserFieldManager();
					$userFieldManager->loadBatchAttachedObject($arUF[$ufCode]["VALUE"]);

					foreach($arUF[$ufCode]["VALUE"] as $attachedId)
					{
						$attachedObject = $userFieldManager->getAttachedObjectById($attachedId);
						if($attachedObject)
						{
							$file = $attachedObject->getObject();
							if (!$file)
							{
								continue;
							}

							$fileName = $file->getName();

							$fileUrl = \Bitrix\Disk\UrlManager::getUrlUfController('download', array('attachedId' => $attachedId));
							$fileUrl = str_replace("/bitrix/tools/disk/uf.php", SITE_DIR."mobile/ajax.php", $fileUrl);
							$fileUrl = $fileUrl.(mb_strpos($fileUrl, "?") === false ? "?" : "&")."mobile_action=disk_uf_view&filename=".$fileName;

							if (
								\Bitrix\Disk\TypeFile::isImage($file)
								&& ($realFile = $file->getFile())
							)
							{
								$previewImageUrl = \Bitrix\Disk\UrlManager::getUrlUfController(
									'show',
									array(
										'attachedId' => $attachedId,
										'width' => $previewImageSize,
										'height' => $previewImageSize,
										'exact' => 'Y',
										'signature' => \Bitrix\Disk\Security\ParameterSigner::getImageSignature($attachedId, $previewImageSize, $previewImageSize)
									)
								);
							}
							else
							{
								$previewImageUrl = false;
							}

							$icon = CMobileHelper::mobileDiskGetIconByFilename($fileName);
							$iconUrl = CComponentEngine::makePathFromTemplate('/bitrix/components/bitrix/mobile.disk.file.detail/images/'.$icon);

							$fileFata = array(
								'type' => $file->getExtension(),
								'ufCode' => $ufCode,
								'id' => $attachedId,
								'objectId' => \Bitrix\Disk\Uf\FileUserType::NEW_FILE_PREFIX.$file->GetId(),
								'extension' => $file->getExtension(),
								'name' => $fileName,
								'url' => $fileUrl,
								'iconUrl' => $iconUrl
							);

							if ($previewImageUrl)
							{
								$fileFata['previewImageUrl'] = CHTTP::URN2URI($previewImageUrl);
							}

							$arFileData[] = $fileFata;
						}
					}
				}
				else // webdav
				{
					$data = CWebDavIblock::getRootSectionDataForUser($GLOBALS["USER"]->GetID());
					if (is_array($data))
					{
						$ibe = new CIBlockElement();
						$dbWDFile = $ibe->GetList(
							array(),
							array(
								'ID' => $arUF[$ufCode]["VALUE"],
								'IBLOCK_ID' => $data["IBLOCK_ID"]
							),
							false,
							false,
							array('ID', 'IBLOCK_ID', 'PROPERTY_FILE')
						);
						while ($arWDFile = $dbWDFile->Fetch())
						{
							if ($arFile = CFile::GetFileArray($arWDFile["PROPERTY_FILE_VALUE"]))
							{
								if (CFile::IsImage($arFile["FILE_NAME"], $arFile["CONTENT_TYPE"]))
								{
									$imageResized = CFile::ResizeImageGet(
										$arFile["ID"],
										array(
											"width" => $previewImageSize,
											"height" => $previewImageSize
										),
										BX_RESIZE_IMAGE_EXACT,
										false,
										true
									);
									$previewImageUrl = $imageResized["src"];
								}
								else
								{
									$previewImageUrl = false;
								}

								$fileExtension = GetFileExtension($arFile["FILE_NAME"]);

								$fileData = array(
									'type' => $fileExtension,
									'ufCode' => $ufCode,
									'id' => $arWDFile["ID"],
									'extension' => $fileExtension,
									'name' => $arFile["FILE_NAME"],
									'url' => $arFile["SRC"],
								);

								if ($previewImageUrl)
								{
									$fileData['previewImageUrl'] = CHTTP::URN2URI($previewImageUrl);
								}

								$arFileData[] = $fileData;
							}
						}
					}
				}
			}
			else // get just files
			{
				$dbRes = CFile::GetList(
					array(),
					array(
						"@ID" => implode(",", $arUF[$ufCode]["VALUE"])
					)
				);

				while ($arFile = $dbRes->GetNext())
				{
					if (CFile::IsImage($arFile["FILE_NAME"], $arFile["CONTENT_TYPE"]))
					{
						$imageResized = CFile::ResizeImageGet(
							$arFile["ID"],
							array(
								"width" => $previewImageSize,
								"height" => $previewImageSize
							),
							BX_RESIZE_IMAGE_EXACT,
							false,
							true
						);
						$previewImageUrl = $imageResized["src"];
					}
					else
					{
						$previewImageUrl = false;
					}

					$fileExtension = GetFileExtension($arFile["FILE_NAME"]);

					$fileData = array(
						'type' => $fileExtension,
						'ufCode' => $ufCode,
						'id' => $arFile["ID"],
						'extension' => $fileExtension,
						'name' => $arFile["FILE_NAME"],
						'downloadUrl' => $arFile["SRC"],
					);

					if ($previewImageUrl)
					{
						$fileData['previewImageUrl'] = CHTTP::URN2URI($previewImageUrl);
					}

					$arFileData[] = $fileData;
				}
			}
		}

		return $arFileData;
	}

	public static function mobileDiskGetIconByFilename($name)
	{
		if(CFile::isImage($name))
		{
			return 'img.png';
		}
		$icons = [
			'pdf' => 'pdf.png',
			'jpg' => 'img.png',
			'doc' => 'doc.png',
			'docx' => 'doc.png',
			'ppt' => 'ppt.png',
			'pptx' => 'ppt.png',
			'rar' => 'rar.png',
			'xls' => 'xls.png',
			'csv' => 'xls.png',
			'xlsx' => 'xls.png',
			'zip' => 'zip.png',
			'txt' => 'txt.png',
			'avi' => 'movie.png',
			'mov' => 'movie.png',
			'mpeg' => 'movie.png',
			'mp4' => 'movie.png',
		];
		$ext = mb_strtolower(getFileExtension($name));

		return isset($icons[mb_strtolower($ext)]) ? $icons[$ext] : 'blank.png';
	}

	public static function getDeviceResizeWidth()
	{
		$max_dimension = false;

		if (
			CModule::IncludeModule('mobileapp')
			&& CMobile::getInstance()->getApiVersion() > 1
		)
		{
			$max_dimension = max(array(intval(CMobile::getInstance()->getDevicewidth()), intval(CMobile::getInstance()->getDeviceheight())));

			if ($max_dimension < 650)
			{
				$max_dimension = 650;
			}
			elseif ($max_dimension < 1300)
			{
				$max_dimension = 1300;
			}
			else
			{
				$max_dimension = 2050;
			}
		}

		return $max_dimension;
	}

	public static function getPageAdditionals()
	{
		global $APPLICATION;

		$arCSSListNew = $APPLICATION->sPath2css;
		$arCSSNew = array();

		foreach ($arCSSListNew as $i => $css_path)
		{
			if(
				mb_strtolower(mb_substr($css_path, 0, 7)) != 'http://'
				&& mb_strtolower(mb_substr($css_path, 0, 8)) != 'https://'
			)
			{
				$css_file = (
				($p = mb_strpos($css_path, "?")) > 0
					? mb_substr($css_path, 0, $p)
					: $css_path
				);

				if(file_exists($_SERVER["DOCUMENT_ROOT"].$css_file))
				{
					$arCSSNew[] = $css_path;
				}
			}
			else
			{
				$arCSSNew[] = $css_path;
			}
		}

		$arCSSNew = array_unique($arCSSNew);

		$arHeadScriptsNew = $APPLICATION->arHeadScripts;

		if(!$APPLICATION->oAsset->optimizeJs())
		{
			$arHeadScriptsNew = array_merge(CJSCore::GetScriptsList(), $arHeadScriptsNew);
		}

		$arAdditionalData["CSS"] = array();
		foreach($arCSSNew as $style)
		{
			$arAdditionalData["CSS"][] = CUtil::GetAdditionalFileURL($style);
		}

		$arAdditionalData['SCRIPTS'] = array();
		$arHeadScriptsNew = array_unique($arHeadScriptsNew);

		foreach($arHeadScriptsNew as $script)
		{
			$arAdditionalData["SCRIPTS"][] = CUtil::GetAdditionalFileURL($script);
		}

		return $arAdditionalData;
	}

	public static function createLink($tag)
	{
		global $USER;

		$link = SITE_DIR.'mobile/log/?ACTION=CONVERT';
		$result = false;
		$unique = false;
		$uniqueParams = "{}";

		if (
			mb_substr($tag, 0, 10) == 'BLOG|POST|'
			|| mb_substr($tag, 0, 18) == 'BLOG|POST_MENTION|'
			|| mb_substr($tag, 0, 11) == 'BLOG|SHARE|'
			|| mb_substr($tag, 0, 17) == 'BLOG|SHARE2USERS|'
			|| mb_substr($tag, 0, 25) == 'RATING_MENTION|BLOG_POST|'
		)
		{
			$params = explode("|", $tag);
			$result = $link."&ENTITY_TYPE_ID=BLOG_POST&ENTITY_ID=".$params[2];
		}
		elseif (
			mb_substr($tag, 0, 13) == 'BLOG|COMMENT|'
			|| mb_substr($tag, 0, 21) == 'BLOG|COMMENT_MENTION|'
		)
		{
			$params = explode("|", $tag);
			if (!empty($params[3]))
			{
				$result = $link."&ENTITY_TYPE_ID=BLOG_COMMENT&ENTITY_ID=".$params[3].'#com'.$params[3];
			}
			else
			{
				$result = $link."&ENTITY_TYPE_ID=BLOG_POST&ENTITY_ID=".$params[2];
			}
		}
		else if (mb_substr($tag, 0, 28) == 'RATING_MENTION|BLOG_COMMENT|')
		{
			$params = explode("|", $tag);
			$result = $link."&ENTITY_TYPE_ID=BLOG_COMMENT&ENTITY_ID=".$params[2];
		}
		else if (mb_substr($tag, 0, 10) == 'RATING|IM|')
		{
			$params = explode("|", $tag);
			return "BX.MobileTools.openChat(".($params[2] == 'P'? $params[3]: "'chat".$params[3]."'").");";
		}
		else if (mb_substr($tag, 0, 11) == 'IM|MENTION|')
		{
			$params = explode("|", $tag);
			return "BX.MobileTools.openChat('chat".$params[2]."');";
		}
		else if (mb_substr($tag, 0, 10) == 'RATING|DL|')
		{
			$params = explode("|", $tag);
			$result = $link."&ENTITY_TYPE_ID=".$params[2]."&ENTITY_ID=".$params[3];
		}
		else if (
			mb_substr($tag, 0, 13) === 'FORUM|COMMENT'
			|| mb_substr($tag, 0, 26) === 'RATING_MENTION|FORUM_POST|'
			|| mb_substr($tag, 0, 18) === 'RATING|FORUM_POST|'
		)
		{
			$params = explode("|", $tag);
			if (
				!empty($params[1])
				&& !empty($params[2])
				&& Loader::includeModule('socialnetwork')
			)
			{
				$liveFeedEntity = Bitrix\SocialNetwork\Livefeed\Provider::init([
					'ENTITY_TYPE' => \Bitrix\Socialnetwork\Livefeed\Provider::DATA_ENTITY_TYPE_FORUM_POST,
					'ENTITY_ID' => $params[2]
				]);

				$suffix = $liveFeedEntity->getSuffix();
				if ($suffix === 'TASK')
				{
					$res = LogTable::getList(array(
						'filter' => array(
							'ID' => $liveFeedEntity->getLogId()
						),
						'select' => [ 'ENTITY_ID', 'EVENT_ID', 'SOURCE_ID' ]
					));
					if($logEntryFields = $res->fetch())
					{
						if ($logEntryFields['EVENT_ID'] === 'crm_activity_add')
						{
							if (
								Loader::includeModule('crm')
								&& ($activityFields = \CCrmActivity::getById($logEntryFields['ENTITY_ID'], false))
								&& $activityFields['TYPE_ID'] == \CCrmActivityType::Task
							)
							{
								$taskId = (int)$activityFields['ASSOCIATED_ENTITY_ID'];
							}
						}
						else
						{
							$taskId = (int)$logEntryFields['SOURCE_ID'];
						}

						if ($taskId > 0)
						{
							return self::getTaskLink($taskId);
						}
					}
				}
			}

			if (!$result)
			{
				$result = $link."&ENTITY_TYPE_ID=FORUM_POST&ENTITY_ID=".$params[2];
			}
		}
		else if (mb_substr($tag, 0, 7) == 'RATING|')
		{
			$params = explode("|", $tag);
			if ($params[1] == 'TASK')
			{
				if (!empty(($taskId = $params[2]) && Loader::includeModule('tasks')))
				{
					return self::getTaskLink($taskId);
				}
			}
			elseif ($params[1] == 'BLOG_COMMENT')
			{
				$result = $link."&ENTITY_TYPE_ID=".$params[1]."&ENTITY_ID=".$params[2].'#com'.$params[2];
			}
			else
			{
				$result = $link."&ENTITY_TYPE_ID=".$params[1]."&ENTITY_ID=".$params[2];
			}
		}
		else if (mb_substr($tag, 0, 15) == 'CALENDAR|INVITE' ||
			mb_substr($tag, 0, 16) == 'CALENDAR|COMMENT' ||
			mb_substr($tag, 0, 15) == 'CALENDAR|STATUS'
		)
		{
			$params = explode("|", $tag);
			if (count($params) >= 5 && $params[4] == 'cancel')
				$result = false;
			else
				$result = SITE_DIR.'mobile/calendar/view_event.php?event_id='.$params[2];
		}
		else if (mb_substr($tag, 0, 21) == 'FORUM|COMMENT_MENTION')
		{
			$params = explode("|", $tag);
			$result = $link."&ENTITY_TYPE_ID=LOG_COMMENT&ENTITY_ID=".$params[2];
		}
		else if (mb_substr($tag, 0, 7) == 'VOTING|')
		{
			$params = explode("|", $tag);
			$result = $link."&ENTITY_TYPE_ID=VOTING&ENTITY_ID=".$params[1];
		}
		else if (
			mb_substr($tag, 0, 13) == 'PHOTO|COMMENT'
			|| mb_substr($tag, 0, 12) == 'WIKI|COMMENT'
		)
		{
			$params = explode("|", $tag);
			$result = $link."&ENTITY_TYPE_ID=IBLOCK_ELEMENT&ENTITY_ID=".$params[2];
		}
		else if (
			mb_substr($tag, 0, 34) == 'INTRANET_NEW_USER|COMMENT_MENTION|'
			|| mb_substr($tag, 0, 22) == 'LISTS|COMMENT_MENTION|'
			|| mb_substr($tag, 0, 27) == 'RATING_MENTION|LOG_COMMENT|'
		)
		{
			$params = explode("|", $tag);
			$result = $link."&ENTITY_TYPE_ID=LOG_COMMENT&ENTITY_ID=".$params[2];
		}
		else if (
			mb_substr($tag, 0, 12) == 'SONET|EVENT|'
		)
		{
			$params = explode("|", $tag);
			$result = $link."&ENTITY_TYPE_ID=LOG_ENTRY&ENTITY_ID=".$params[2];
		}
		else if (
			mb_substr($tag, 0, 11) == 'TASKS|TASK|' || mb_substr($tag, 0, 14) == 'TASKS|COMMENT|'
		)
		{
			// the format is:
			// for task modifications:
			// TASKS|TASK|%task_id%|%user_id%
			// for task comments:
			// TASKS|TASK_COMMENT|%task_id%|%user_id%|%comment_id%

			$params = explode("|", $tag);
			if (!empty(($taskId = $params[2]) && Loader::includeModule('tasks')))
			{
				return self::getTaskLink($taskId);
			}

			// after task detail page supports reloading only by TASK_ID, use the following:
			//$result = SITE_DIR.'mobile/tasks/snmrouter/?routePage=__ROUTE_PAGE__&USER_ID='.intval($GLOBALS['USER']->GetId());
			//$uniqueParams = "{task_id:".intval($params[2]).", params_emitter: 'tasks_list'}";
			//$unique = true;
		}
		else if (
			mb_substr($tag, 0, 6) == 'ROBOT|'
		)
		{
			$params = explode("|", $tag);
			if ($params[1] == 'CRM' && isset($params[3]))
			{
				list($entityTypeName, $entityId) = explode('_', $params[3]);
				$entityTypeName = mb_strtolower($entityTypeName);
				$entityId = (int)$entityId;

				if ($entityTypeName === 'lead' || $entityTypeName === 'deal')
				{
					$result = SITE_DIR.'mobile/crm/'.$entityTypeName.'/?page=view&'.$entityTypeName.'_id='.$entityId;
				}
			}
		}
		else if (
			mb_strpos($tag, 'BIZPROC|TASK|') === 0
		)
		{
			$params = explode("|", $tag);
			if (isset($params[2]))
			{
				$result = SITE_DIR.'mobile/bp/detail.php?task_id='.(int)$params[2];
			}
		}

		if ($result)
		{
			if ($unique)
			{
				$result = "BXMobileApp.PageManager.loadPageUnique({'url' : '".$result."','bx24ModernStyle' : true, 'data': ".$uniqueParams."});";
			}
			else
			{
				$result = "BXMobileApp.PageManager.loadPageBlank({url: '".$result."', 'unique': ".($unique? 'true': 'false').", 'bx24ModernStyle': true})";
			}
		}
		return $result;
	}

	public static function getUserInfo($userId)
	{
		if (!intval($userId))
			return;

		$dbUser = CUser::GetList("", "", array("ID_EQUAL_EXACT" => $userId), array("FIELDS" => array("NAME", "LAST_NAME", "SECOND_NAME", "LOGIN", "PERSONAL_PHOTO")));
		if ($arUser = $dbUser->Fetch())
		{
			$userPhoto = CFile::ResizeImageGet(
				$arUser["PERSONAL_PHOTO"], array('width' => 50, 'height' => 50), BX_RESIZE_IMAGE_EXACT);

			$userName = CUser::FormatName(
				CSite::GetNameFormat(false),
				array(
					'LOGIN' => isset($arUser['LOGIN']) ? $arUser['LOGIN'] : '',
					'NAME' => isset($arUser['NAME']) ? $arUser['NAME'] : '',
					'LAST_NAME' => isset($arUser['LAST_NAME']) ? $arUser['LAST_NAME'] : '',
					'SECOND_NAME' => isset($arUser['SECOND_NAME']) ? $arUser['SECOND_NAME'] : ''
				),
				true, false
			);

			return array(
				"id" => $userId,
				"name" => $userName,
				"avatar" => $userPhoto["src"]
			);
		}

		return;
	}

	/**
	 * @param $taskId
	 * @return string
	 */
	public static function getTaskLink($taskId): string
	{
		$taskId = (int)$taskId;

		try
		{
			if (!Loader::includeModule('tasks'))
			{
				return '';
			}
			$taskData = \CTaskItem::getInstanceFromPool($taskId, $GLOBALS["USER"]->GetID())->getData(false);

			$creatorIcon = Bitrix\Tasks\UI\Avatar::getPerson($taskData['CREATED_BY_PHOTO']);
			$responsibleIcon = Bitrix\Tasks\UI\Avatar::getPerson($taskData['RESPONSIBLE_PHOTO']);
			$title = addslashes(htmlspecialcharsbx($taskData['TITLE']));

			$taskInfoParameter = "{title: '{$title}', creatorIcon: '{$creatorIcon}', responsibleIcon: '{$responsibleIcon}'}";

			return "BXMobileApp.Events.postToComponent('taskbackground::task::open',"
				. '['
					. "{id: {$taskId}, taskId: {$taskId}, title: 'TASK', taskInfo: {$taskInfoParameter}},"
					. "{taskId: {$taskId}, getTaskInfo: true}"
				. ']'
			. ');';
		}
		catch (TasksException $exception)
		{
			return '';
		}
	}

	/**
	 * @param int $taskId
	 *
	 * @return string
	 * @throws CTaskAssertException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function getParamsToCreateTaskLink(int $taskId): string
	{
		try
		{
			if (!\Bitrix\Main\Loader::includeModule('tasks'))
			{
				return '';
			}
			$taskData = \CTaskItem::getInstanceFromPool($taskId, $GLOBALS["USER"]->GetID())->getData(false);

			$creatorIcon = \Bitrix\Tasks\UI\Avatar::getPerson($taskData['CREATED_BY_PHOTO']);
			$responsibleIcon = \Bitrix\Tasks\UI\Avatar::getPerson($taskData['RESPONSIBLE_PHOTO']);
			$title = addslashes(htmlspecialcharsbx($taskData['TITLE']));

			$taskDataParams = [
				[
					'id' => $taskId,
					'taskId' => $taskId,
					'title' => 'TASK',
					'taskInfo' => [
						'title' => $title,
						'creatorIcon' => $creatorIcon,
						'responsibleIcon' => $responsibleIcon,
					],
				],
				[
					'taskId' => $taskId,
					'getTaskInfo' => true,
				],
			];

			$taskDataParams = \Bitrix\Main\Web\Json::encode($taskDataParams);

			return $taskDataParams;
		}
		catch (\TasksException $exception)
		{
			return '';
		}
	}

	/**
	 * @param $text
	 * @param $tag
	 * @return string
	 */
	public static function prepareNotificationText($text, $tag)
	{
		$preparedText = $text;

		if (mb_strpos($tag, 'TASKS|TASK|') === 0 || mb_strpos($tag, 'TASKS|COMMENT|') === 0)
		{
			$preparedText = strip_tags($text, '<br>');
		}

		return $preparedText;
	}

	public static function getCurrentSiteData()
	{
		$result = array(
			'SITE_ID' => SITE_ID,
			'SITE_DIR' => SITE_DIR
		);
		if (
			Loader::includeModule('extranet')
			&& !CExtranet::isIntranetUser()
		) // current extranet user
		{
			$extranetSiteId = \CExtranet::getExtranetSiteId();
			if ($extranetSiteId)
			{
				$res = \CSite::getById($extranetSiteId);
				if(
					($extranetSiteFields = $res->fetch())
					&& ($extranetSiteFields["ACTIVE"] != "N")
				)
				{
					$result = array(
						'SITE_ID' => $extranetSiteId,
						'SITE_DIR' => $extranetSiteFields["DIR"]
					);
				}
			}
		}

		return $result;
	}
}
?>