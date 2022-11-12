<?
define("NO_KEEP_STATISTIC", true);
define("BX_STATISTIC_BUFFER_USED", false);
define("NO_LANG_FILES", true);
define("NOT_CHECK_PERMISSIONS", true);
define("BX_PUBLIC_TOOLS", true);

$site_id = isset($_REQUEST["site"]) && is_string($_REQUEST["site"]) ? trim($_REQUEST["site"]) : "";
$site_id = mb_substr(preg_replace("/[^a-z0-9_]/i", "", $site_id), 0, 2);
global $APPLICATION, $USER, $DB;
define("SITE_ID", $site_id);
define("SITE_TEMPLATE_ID", "mobile_app");


require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/bx_root.php");

$action = isset($_REQUEST["action"]) && is_string($_REQUEST["action"]) ? trim($_REQUEST["action"]) : "";

$lng = isset($_REQUEST["lang"]) && is_string($_REQUEST["lang"]) ? trim($_REQUEST["lang"]) : "";
$lng = mb_substr(preg_replace("/[^a-z0-9_]/i", "", $lng), 0, 2);

$ls = isset($_REQUEST["ls"]) && is_string($_REQUEST["ls"]) ? trim($_REQUEST["ls"]) : "";
$ls_arr = isset($_REQUEST["ls_arr"])? $_REQUEST["ls_arr"]: "";

$as = isset($_REQUEST["as"]) ? intval($_REQUEST["as"]) : 58;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
global $CACHE_MANAGER, $USER_FIELD_MANAGER;
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

$rsSite = CSite::GetByID($site_id);
if ($arSite = $rsSite->Fetch())
{
	define("LANGUAGE_ID", $arSite["LANGUAGE_ID"]);
}
else
{
	define("LANGUAGE_ID", "en");
}

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/mobile.socialnetwork.log.entry/include.php");

__IncludeLang(__DIR__."/lang/".$lng."/ajax.php");

if(CModule::IncludeModule("socialnetwork"))
{
	$bCurrentUserIsAdmin = CSocNetUser::IsCurrentUserModuleAdmin();

	// write and close session to prevent lock;
	session_write_close();

	$arResult = array();

	if (!$USER->IsAuthorized())
	{
		$arResult[0] = "*";
	}
	elseif (!check_bitrix_sessid())
	{
		$arResult[0] = "*";
	}
	elseif (in_array($action, array("add_comment", "edit_comment", "delete_comment", "file_comment_upload")))
	{
		$log_id = $_REQUEST["log_id"];
		if ($arLog = CSocNetLog::GetByID($log_id))
		{
			$log_entity_type = $arLog["ENTITY_TYPE"];
			$arListParams = (mb_strpos($log_entity_type, "CRM") === 0 && IsModuleInstalled("crm")
				? array("IS_CRM" => "Y", "CHECK_CRM_RIGHTS" => "Y")
				: array("CHECK_RIGHTS" => "Y", "USE_SUBSCRIBE" => "N")
			);
		}
		else
		{
			$log_id = 0;
		}

		if (
			intval($log_id) <= 0
			|| !($rsLog = CSocNetLog::GetList(array(), array("ID" => $log_id), false, false, array(), $arListParams))
			|| !($arLog = $rsLog->Fetch())
		)
		{
			$arResult["strMessage"] = GetMessage("Log event not found");
		}

		if (!isset($arResult["strMessage"]))
		{
			$liveFeedCommentsParams = \Bitrix\Socialnetwork\ComponentHelper::getLFCommentsParams([
				"ID" => $arLog["ID"],
				"EVENT_ID" => $arLog["EVENT_ID"],
				"ENTITY_TYPE" => $arLog["ENTITY_TYPE"],
				"ENTITY_ID" => $arLog["ENTITY_ID"],
				"SOURCE_ID" => $arLog["SOURCE_ID"],
				"PARAMS" => $arLog["PARAMS"]
			]);

			$entity_xml_id = $liveFeedCommentsParams['ENTITY_XML_ID'];

			$arCommentEvent = CSocNetLogTools::FindLogCommentEventByLogEventID($arLog["EVENT_ID"]);
			if (!$arCommentEvent)
			{
				$arResult["strMessage"] = GetMessage("Comment event not found");
			}
		}

		if (!isset($arResult["strMessage"]))
		{
			$feature = CSocNetLogTools::FindFeatureByEventID($arCommentEvent["EVENT_ID"]);

			if (
				array_key_exists("OPERATION_ADD", $arCommentEvent)
				&& $arCommentEvent["OPERATION_ADD"] == "log_rights"
			)
			{
				$bCanAddComments = CSocNetLogRights::CheckForUser($log_id, $USER->GetID());
			}
			elseif (
				$feature
				&& array_key_exists("OPERATION_ADD", $arCommentEvent)
				&& $arCommentEvent["OPERATION_ADD"] <> ''
			)
			{
				$bCanAddComments = CSocNetFeaturesPerms::CanPerformOperation(
					$USER->GetID(),
					$arLog["ENTITY_TYPE"],
					$arLog["ENTITY_ID"],
					($feature == "microblog" ? "blog" : $feature),
					$arCommentEvent["OPERATION_ADD"],
					$bCurrentUserIsAdmin
				);
			}
			else
			{
				$bCanAddComments = true;
			}

			if (!$bCanAddComments)
			{
				$arResult["strMessage"] = GetMessage("SONET_LOG_COMMENT_NO_PERMISSIONS");
			}
		}

		if (!isset($arResult["strMessage"]))
		{
			$editCommentID = ($_REQUEST["action"] == 'edit_comment' ? intval($_REQUEST["edit_id"]) : false);
			$deleteCommentID = ($_REQUEST["action"] == 'delete_comment' ? intval($_REQUEST["delete_id"]) : false);

			if (
				$editCommentID > 0
				|| $deleteCommentID > 0
			)
			{
				if ($arComment = CSocNetLogComponent::getCommentByRequest(
					($editCommentID > 0 ? $editCommentID : $deleteCommentID),
					$log_id,
					($editCommentID > 0 ? "edit" : "delete"),
					true,
					false
				))
				{
					if ($editCommentID > 0)
					{
						$editCommentID = $arComment["ID"];
					}
					else
					{
						$deleteCommentID = $arComment["ID"];
					}
				}
				else
				{
					if ($editCommentID > 0)
					{
						$editCommentID = $arComment["ID"];
					}
					else
					{
						$deleteCommentID = $arComment["ID"];
					}

					$deleteCommentID = 0;
				}
			}

			if (
				$editCommentID > 0
				|| $deleteCommentID <= 0
			)
			{
				if ($action == "file_comment_upload")
				{
					$arFileStorage = CMobileHelper::InitFileStorage();

					if (isset($arFileStorage["ERROR_CODE"]))
					{
						$arResult["strMessage"] = (!empty($arFileStorage["ERROR_MESSAGE"]) ? $arFileStorage["ERROR_MESSAGE"] : "Cannot init storage");
					}

					if (!isset($arResult["strMessage"]))
					{
						$moduleId = "uf";

						$arFile = $_FILES["file"];
						$arFile["MODULE_ID"] = $moduleId;

						$ufCode = (
							isset($arFileStorage["DISC_FOLDER"])
							|| isset($arFileStorage["WEBDAV_DATA"])
								? "UF_SONET_COM_DOC"
								: "UF_SONET_COM_FILE"
						);

						$arPostFields = $USER_FIELD_MANAGER->GetUserFields("SONET_COMMENT", 0, LANGUAGE_ID);
						if (empty($arPostFields[$ufCode]))
						{
							$arResult["strMessage"] = "Userfield not exists";
						}
					}

					if (!isset($arResult["strMessage"]))
					{
						$pos = mb_strpos($arFile["name"], '?');
						if ($pos !== false)
						{
							$arFile["name"] = mb_substr($arFile["name"], 0, $pos);
						}

						$res = ''.CFile::CheckImageFile(
							$arFile,
							(
								intval($arPostFields[$ufCode]['SETTINGS']['MAX_ALLOWED_SIZE']) > 0
									? $arPostFields[$ufCode]['SETTINGS']['MAX_ALLOWED_SIZE']
									: 5000000
							),
							0,
							0
						);

						if ($res !== '')
						{
							$arResult["strMessage"] = "Incorrect file";
						}
					}

					if (!isset($arResult["strMessage"]))
					{
						$arSaveResult = CMobileHelper::SaveFile($arFile, $arFileStorage);
						if (
							!$arSaveResult
							|| !isset($arSaveResult["ID"])
						)
						{
							$arResult["strMessage"] = "Can't save file";
						}
					}

					if (!isset($arResult["strMessage"]))
					{
						if (isset($arFileStorage["DISC_FOLDER"]))
						{
							$comment_text = "[DISK FILE ID=n".$arSaveResult["ID"]."]";
						}
						elseif (isset($arFileStorage["WEBDAV_DATA"]))
						{
							$comment_text = "[DOCUMENT ID=".$arSaveResult["ID"]."]";
						}
						else
						{
							$comment_text = ".";
						}
					}
				}
				else
				{
					$arParams = array(
						"PATH_TO_USER_BLOG_POST" => $_REQUEST["p_ubp"],
						"PATH_TO_GROUP_BLOG_POST" => $_REQUEST["p_gbp"],
						"PATH_TO_USER_MICROBLOG_POST" => $_REQUEST["p_umbp"],
						"PATH_TO_GROUP_MICROBLOG_POST" => $_REQUEST["p_gmbp"],
						"BLOG_ALLOW_POST_CODE" => $_REQUEST["bapc"]
					);

					$comment_text = $_REQUEST["message"];
					CUtil::decodeURIComponent($comment_text);
					$comment_text = trim($comment_text);
				}

				if (!isset($arResult["strMessage"]))
				{
					if ($comment_text == '')
					{
						$arResult["strMessage"] = GetMessage("SONET_LOG_COMMENT_EMPTY");
					}
				}

				if (!isset($arResult["strMessage"]))
				{
					$arAllow = array(
						"HTML" => "N",
						"ANCHOR" => "Y",
						"LOG_ANCHOR" => "N",
						"BIU" => "N",
						"IMG" => "N",
						"LIST" => "N",
						"QUOTE" => "N",
						"CODE" => "N",
						"FONT" => "N",
						"UPLOAD" => $arForum["ALLOW_UPLOAD"],
						"NL2BR" => "N",
						"SMILES" => "N"
					);

					if (
						$editCommentID > 0
						&& $arComment
					)
					{
						$arFields = array(
							"MESSAGE" => $comment_text,
							"TEXT_MESSAGE" => $comment_text,
							"EVENT_ID" => $arComment["EVENT_ID"]
						);

						CSocNetLogComponent::checkEmptyUFValue('UF_SONET_COM_DOC');

						$USER_FIELD_MANAGER->EditFormAddFields("SONET_COMMENT", $arFields);

						if (
							!empty($_POST["attachedFilesRaw"])
							&& is_array($_POST["attachedFilesRaw"])
						)
						{
							CSocNetLogComponent::saveRawFilesToUF(
								$_POST["attachedFilesRaw"],
								(
									IsModuleInstalled("webdav")
									|| IsModuleInstalled("disk")
										? "UF_SONET_COM_DOC"
										: "UF_SONET_COM_FILE"
								),
								$arFields
							);
						}

						$comment = CSocNetLogComments::Update($editCommentID, $arFields, true);
					}
					else
					{
						$arFields = array(
							"ENTITY_TYPE" => $arLog["ENTITY_TYPE"],
							"ENTITY_ID" => $arLog["ENTITY_ID"],
							"EVENT_ID" => $arCommentEvent["EVENT_ID"],
							"=LOG_DATE" => $DB->CurrentTimeFunction(),
							"MESSAGE" => $comment_text,
							"TEXT_MESSAGE" => $comment_text,
							"URL" => $source_url,
							"MODULE_ID" => false,
							"LOG_ID" => $arLog["ID"],
							"USER_ID" => $USER->GetID(),
							"PATH_TO_USER_BLOG_POST" => $arParams["PATH_TO_USER_BLOG_POST"],
							"PATH_TO_GROUP_BLOG_POST" => $arParams["PATH_TO_GROUP_BLOG_POST"],
							"PATH_TO_USER_MICROBLOG_POST" => $arParams["PATH_TO_USER_MICROBLOG_POST"],
							"PATH_TO_GROUP_MICROBLOG_POST" => $arParams["PATH_TO_GROUP_MICROBLOG_POST"],
							"BLOG_ALLOW_POST_CODE" => $arParams["BLOG_ALLOW_POST_CODE"],
						);

						if ($arSaveResult)
						{
							$arFields[$ufCode] = array(
								(isset($arFileStorage["DISC_FOLDER"]) ? "n".$arSaveResult["ID"] : $arSaveResult["ID"])
							);
						}
						else
						{
							$USER_FIELD_MANAGER->EditFormAddFields("SONET_COMMENT", $arFields);
						}

						if(
							empty($arFields["UF_SONET_COM_URL_PRV"])
							&& ($urlPreviewValue = \Bitrix\Socialnetwork\ComponentHelper::getUrlPreviewValue($arFields["MESSAGE"]))
						)
						{
							$arFields["UF_SONET_COM_URL_PRV"] = $urlPreviewValue;
						}

						$GLOBALS[$ufCode] = $arFields[$ufCode];
						$comment = CSocNetLogComments::Add($arFields, true, false);
						unset($GLOBALS[$ufCode]);

						CSocNetLog::CounterIncrement($comment, false, false, "LC");
					}

					if (
						!is_array($comment)
						&& intval($comment) > 0
					)
					{
						$arResult["SUCCESS"] = "Y";
						$arResult["commentID"] = $comment;
						$arResult["arCommentFormatted"] = __SLMAjaxGetComment($comment, $arParams);
						if ($arComment = CSocNetLogComments::GetByID($comment))
						{
							$strAfter = "";

							$arResult["arCommentFormatted"]["SOURCE_ID"] = ($arComment["SOURCE_ID"] > 0 ? $arComment["SOURCE_ID"] : $arComment["ID"]);

							if (
								$arComment["RATING_TYPE_ID"] <> ''
								&& intval($arComment["RATING_ENTITY_ID"]) > 0
							)
							{
								$arResult["arCommentFormatted"]["EVENT"]["RATING_TYPE_ID"] = $arComment["RATING_TYPE_ID"];
								$arResult["arCommentFormatted"]["EVENT"]["RATING_ENTITY_ID"] = $arComment["RATING_ENTITY_ID"];
								$arResult["arCommentFormatted"]["EVENT"]["RATING_USER_VOTE_VALUE"] = $arComment["RATING_USER_VOTE_VALUE"];
								$arResult["arCommentFormatted"]["EVENT"]["RATING_TOTAL_POSITIVE_VOTES"] = $arComment["RATING_TOTAL_POSITIVE_VOTES"];
							}

							$arComment["UF"] = $USER_FIELD_MANAGER->GetUserFields("SONET_COMMENT", $arComment["ID"], LANGUAGE_ID);
							if (!empty($arComment["UF"]["UF_SONET_COM_URL_PRV"]))
							{
								unset($arComment["UF"]["UF_SONET_COM_URL_PRV"]);
							}

							$arUFResult = CMobileHelper::BuildUFFields($arComment["UF"]);
							$arResult["arCommentFormatted"]["UF_FORMATTED"] = $arUFResult["AFTER_MOBILE"];

							$arCommentEvent = CSocNetLogTools::FindLogCommentEventByID($arComment["EVENT_ID"]);
							if (
								!empty($arCommentEvent["METHOD_CANEDIT"])
								&& intval($arComment["SOURCE_ID"]) > 0
								&& intval($arLog["SOURCE_ID"]) > 0
							)
							{
								$canEdit = call_user_func($arCommentEvent["METHOD_CANEDIT"], array(
									"LOG_SOURCE_ID" => $arLog["SOURCE_ID"],
									"COMMENT_SOURCE_ID" => $arComment["SOURCE_ID"],
									"USER_ID" => $USER->getId()
								));
							}
							else
							{
								$canEdit = ($arResult["arCommentFormatted"]["USER_ID"] == $USER->GetId());
							}

							$arResult["arCommentFormatted"]["CAN_EDIT"] = $arResult["arCommentFormatted"]["CAN_DELETE"] = (
								$canEdit
									? "Y"
									: "N"
							);

							$arResult["arCommentFormatted"]["EVENT"]["ID"] = $arComment["ID"];
							$arResult["arCommentFormatted"]["EVENT"]["LOG_ID"] = $arComment["LOG_ID"];

							$strAfter .= $arUFResult["AFTER"];

							$commentId = ($arComment["SOURCE_ID"] > 0 ? $arComment["SOURCE_ID"] : $comment);
							$timestamp = MakeTimeStamp(array_key_exists("LOG_DATE_FORMAT", $arComment) && !empty($arComment["LOG_DATE_FORMAT"]) ? $arComment["LOG_DATE_FORMAT"] : $arComment["LOG_DATE"]);
							$eventHandlerID = AddEventHandler('main', 'system.field.view.file', Array('CBlogTools', 'blogUFfileShow'));

							$postContentTypeId = $commentContentTypeId = '';
							$contentId = \Bitrix\Socialnetwork\Livefeed\Provider::getContentId($arLog);

							if (
								!empty($contentId['ENTITY_TYPE'])
								&& ($postProvider = \Bitrix\Socialnetwork\Livefeed\Provider::getProvider($contentId['ENTITY_TYPE']))
								&& ($commentProvider = $postProvider->getCommentProvider())
							)
							{
								$postContentTypeId = $postProvider->getContentTypeId();
								$commentProviderClassName = get_class($commentProvider);
								$reflectionClass = new \ReflectionClass($commentProviderClassName);

								$canGetCommentContent = ($reflectionClass->getMethod('initSourceFields')->class == $commentProviderClassName);
								if ($canGetCommentContent)
								{
									$commentContentTypeId = $commentProvider->getContentTypeId();
								}
							}

							$records = array(
								$commentId => array(
									"ID" => $commentId,
									"RATING_VOTE_ID" => $arComment["RATING_TYPE_ID"].'_'.$commentId.'-'.(time()+rand(0, 1000)),
									"NEW" => "Y",
									"APPROVED" => "Y",
									"POST_TIMESTAMP" => $timestamp,
									"AUTHOR" => array(
										"ID" => $USER->getID(),
										"NAME" => $arComment["~CREATED_BY_NAME"],
										"LAST_NAME" => $arComment["~CREATED_BY_LAST_NAME"],
										"SECOND_NAME" => $arComment["~CREATED_BY_SECOND_NAME"],
										"PERSONAL_GENDER" => $arComment["~CREATED_BY_PERSONAL_GENDER"],
										"AVATAR" => $arResult["arCommentFormatted"]["AVATAR_SRC"]
									),
									"FILES" => false,
									"POST_CONTENT_TYPE_ID" => $postContentTypeId,
									"COMMENT_CONTENT_TYPE_ID" => $commentContentTypeId,
									"UF" => $arComment["UF"],
									"~POST_MESSAGE_TEXT" => $arComment["~TEXT_MESSAGE"],
									"WEB" => array(
										"CLASSNAME" => "",
										"POST_MESSAGE_TEXT" =>
											isset($arResult["arCommentFormatted"])
											&& isset($arResult["arCommentFormatted"]["MESSAGE_FORMAT"])
												? $arResult["arCommentFormatted"]["MESSAGE_FORMAT"]
												: $arComment["MESSAGE"]
									),
									"MOBILE" => array(
										"CLASSNAME" => "",
										"POST_MESSAGE_TEXT" => (
										isset($arResult["arCommentFormatted"])
										&& isset($arResult["arCommentFormatted"]["MESSAGE_FORMAT_MOBILE"])
											? $arResult["arCommentFormatted"]["MESSAGE_FORMAT_MOBILE"]
											: $arComment["MESSAGE"]
										)
									)
								)
							);

							if (
								!empty($arComment["~TEXT_MESSAGE"])
								&& \Bitrix\Main\ModuleManager::isModuleInstalled('disk')
							)
							{
								$inlineDiskObjectIdList = $inlineDiskAttachedObjectIdList = array();

								// parse inline disk object ids
								if (preg_match_all("#\\[disk file id=(n\\d+)\\]#is".BX_UTF_PCRE_MODIFIER, $arComment["~TEXT_MESSAGE"], $matches))
								{
									$inlineDiskObjectIdList = array_map(function($a) { return intval(mb_substr($a, 1)); }, $matches[1]);
								}

								// parse inline disk attached object ids
								if (preg_match_all("#\\[disk file id=(\\d+)\\]#is".BX_UTF_PCRE_MODIFIER, $arComment["~TEXT_MESSAGE"], $matches))
								{
									$inlineDiskAttachedObjectIdList = array_map(function($a) { return intval($a); }, $matches[1]);
								}

								// get inline attached images;
								$inlineDiskAttachedObjectIdImageList = array();
								if (
									(
										!empty($inlineDiskObjectIdList)
										|| !empty($inlineDiskAttachedObjectIdList)
									)
									&& \Bitrix\Main\Loader::includeModule('disk')
								)
								{
									$filter = array(
										'=OBJECT.TYPE_FILE' => \Bitrix\Disk\TypeFile::IMAGE
									);

									$subFilter = [];
									if (!empty($inlineDiskObjectIdList))
									{
										$subFilter['@OBJECT_ID'] = $inlineDiskObjectIdList;
									}
									elseif (!empty($inlineDiskAttachedObjectIdList))
									{
										$subFilter['@ID'] = $inlineDiskAttachedObjectIdList;
									}

									if(count($subFilter) > 1)
									{
										$subFilter['LOGIC'] = 'OR';
										$filter[] = $subFilter;
									}
									else
									{
										$filter = array_merge($filter, $subFilter);
									}

									$res = \Bitrix\Disk\Internals\AttachedObjectTable::getList(array(
										'filter' => $filter,
										'select' => array('ID', 'ENTITY_ID')
									));
									while ($attachedObjectFields = $res->fetch())
									{
										if (intval($attachedObjectFields['ENTITY_ID']) == $commentId)
										{
											$inlineDiskAttachedObjectIdImageList[] = intval($attachedObjectFields['ID']);
										}
									}
								}

								// find all inline images and remove them from UF
								if (!empty($inlineDiskAttachedObjectIdImageList))
								{
									if (
										!empty($records[$commentId]["UF"])
										&& !empty($records[$commentId]["UF"]["UF_SONET_COM_DOC"])
										&& !empty($records[$commentId]["UF"]["UF_SONET_COM_DOC"]['VALUE'])
									)
									{
										$records[$commentId]["WEB"]["UF"] = $records[$commentId]["UF"];
										$records[$commentId]["MOBILE"]["UF"] = $records[$commentId]["UF"];
										$records[$commentId]["MOBILE"]["UF"]["UF_SONET_COM_DOC"]['VALUE_INLINE'] = $inlineDiskAttachedObjectIdImageList;
									}
								}
							}

							$res = $APPLICATION->IncludeComponent(
								"bitrix:main.post.list",
								"",
								array(
									"TEMPLATE_ID" => '',
									"RATING_TYPE_ID" => $arComment["RATING_TYPE_ID"],
									"ENTITY_XML_ID" => $entity_xml_id,
									"POST_CONTENT_TYPE_ID" => $postContentTypeId,
									"COMMENT_CONTENT_TYPE_ID" => $commentContentTypeId,
									"RECORDS" => $records,
									"NAV_STRING" => "",
									"NAV_RESULT" => "",
									"PREORDER" => "N",
									"RIGHTS" => array(
										"MODERATE" => "N",
										"EDIT" => "N",
										"DELETE" => "N"
									),
									"VISIBLE_RECORDS_COUNT" => 1,

									"ERROR_MESSAGE" => "",
									"OK_MESSAGE" => "",
									"RESULT" => $commentId,
									"PUSH&PULL" => array(
										"ACTION" => "REPLY",
										"ID" => $commentId
									),
									"MODE" => "PULL_MESSAGE",
									"VIEW_URL" => "",
									"EDIT_URL" => "",
									"MODERATE_URL" => "",
									"DELETE_URL" => "",
									"AUTHOR_URL" => "",

									"AVATAR_SIZE" => $arParams["AVATAR_SIZE_COMMENT"],
									"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
									"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],

									"DATE_TIME_FORMAT" => "",
									"LAZYLOAD" => "",

									"NOTIFY_TAG" => "",
									"NOTIFY_TEXT" => "",
									"SHOW_MINIMIZED" => "Y",
									"SHOW_POST_FORM" => "Y",

									"IMAGE_SIZE" => "",
									"mfi" => ""
								),
								array(),
								null
							);
							RemoveEventHandler('main', 'system.field.view.file', $eventHandlerID);
							$arResult = array_merge($arResult, $res["JSON"]);
						}
					}
					elseif (
						isset($comment["MESSAGE"])
						&& $comment["MESSAGE"] <> ''
					)
					{
						$arResult["strMessage"] = $comment["MESSAGE"];
						$arResult["commentText"] = $comment_text;
					}
				}
			}
			elseif ($deleteCommentID > 0)
			{
				// we already have $arComment and check for permissions
				if (CSocNetLogComments::Delete($deleteCommentID, true))
				{
					$APPLICATION->IncludeComponent(
						"bitrix:main.post.list",
						"",
						array(
							"ENTITY_XML_ID" => $entity_xml_id,
							"PUSH&PULL" => array(
								"ID" => ($arComment["SOURCE_ID"] > 0 ? $arComment["SOURCE_ID"] : $arComment["ID"]),
								"ACTION" => "DELETE"
							)
						)
					);

					$arResult["commentID"] = $deleteCommentID;
				}
				else
				{
					$arResult["strMessage"] = GetMessage("SONET_LOG_COMMENT_CANT_DELETE");
				}
			}
		}
	}
	elseif ($action == "get_comment")
	{
		$arResult["arCommentFormatted"] = __SLMAjaxGetComment($_REQUEST["cid"], $arParams, true);
	}
	elseif ($action == "get_comment_data")
	{
		$log_id = (
			isset($_REQUEST["log_id"])
				? intval($_REQUEST["log_id"])
				: 0
		);

		$comment_id = (
			isset($_REQUEST["cid"])
				? intval($_REQUEST["cid"])
				: 0
		);

		$arComment = CSocNetLogComponent::getCommentByRequest(
			$comment_id,
			$log_id,
			"edit",
			true,
			false
		);

		if (isset($arComment["ID"]))
		{
			$comment_id = $arComment["ID"];

			if ($arLog = CSocNetLog::GetByID($log_id))
			{
				$log_entity_type = $arLog["ENTITY_TYPE"];
				$arListParams = (
				mb_strpos($log_entity_type, "CRM") === 0
					&& IsModuleInstalled("crm")
						? array("IS_CRM" => "Y", "CHECK_CRM_RIGHTS" => "Y")
						: array("CHECK_RIGHTS" => "Y", "USE_SUBSCRIBE" => "N")
				);

				if (
					!($rsLog = CSocNetLog::GetList(array(), array("ID" => $log_id), false, false, array(), $arListParams))
					|| !($arLog = $rsLog->Fetch())
				)
				{
					$log_id = 0;
				}
			}
			else
			{
				$log_id = 0;
			}

			if ($log_id > 0)
			{
				$arResult["status"] = 'success';
				$arResult["CommentCanEdit"] = 'Y';
				$arResult["messageBBCode"] = htmlspecialcharsback($arComment["MESSAGE"]);
				$arResult["messageFields"] = $arComment;
				$entityXmlId = $_REQUEST["ENTITY_XML_ID"];
				$entityXmlId = preg_replace("/[^a-z0-9_]/i", "", $entityXmlId);
				$arResult['messageId'] = array(
					$entityXmlId,
					(
						intval($arComment["SOURCE_ID"]) > 0
							? intval($arComment["SOURCE_ID"])
							: intval($arComment["ID"])
					)
				);
			}
			else
			{
				$arResult["errorMessage"] = GetMessage("SONET_LOG_COMMENT_EDIT_NO_PERMISSIONS");
			}
		}
		else
		{
			$arResult["errorMessage"] = GetMessage("SONET_LOG_COMMENT_EDIT_NO_PERMISSIONS");
		}
	}
	elseif ($action == "get_comments")
	{
		$arResult["arComments"] = array();

		$log_tmp_id = intval($_REQUEST["logid"]);
		$last_comment_id = intval($_REQUEST["last_comment_id"]);
		$last_comment_ts = intval($_REQUEST["last_comment_ts"]);

		if ($arLog = CSocNetLog::GetByID($log_tmp_id))
		{
			$log_entity_type = $arLog["ENTITY_TYPE"];
			$arListParams = (
				mb_strpos($log_entity_type, "CRM") === 0
				&& IsModuleInstalled("crm")
					? array("IS_CRM" => "Y", "CHECK_CRM_RIGHTS" => "Y")
					: array("CHECK_RIGHTS" => "Y", "USE_SUBSCRIBE" => "N")
			);

			$postContentTypeId = $commentContentTypeId = $commentEntitySuffix = '';
			$contentId = \Bitrix\Socialnetwork\Livefeed\Provider::getContentId($arLog);

			if (
				!empty($contentId['ENTITY_TYPE'])
				&& ($postProvider = \Bitrix\Socialnetwork\Livefeed\Provider::getProvider($contentId['ENTITY_TYPE']))
				&& ($commentProvider = $postProvider->getCommentProvider())
			)
			{
				$postContentTypeId = $postProvider->getContentTypeId();
				$commentProviderClassName = get_class($commentProvider);
				$reflectionClass = new \ReflectionClass($commentProviderClassName);

				$canGetCommentContent = ($reflectionClass->getMethod('initSourceFields')->class == $commentProviderClassName);
				if ($canGetCommentContent)
				{
					$commentContentTypeId = $commentProvider->getContentTypeId();
				}
				$commentProvider->setLogEventId($arLog['EVENT_ID']);
				$suffix = $commentProvider->getSuffix();
				if (!empty($suffix))
				{
					$commentEntitySuffix = $suffix;
				}
			}
		}
		else
		{
			$log_tmp_id = 0;
		}

		if (
			$log_tmp_id > 0
			&& ($rsLog = CSocNetLog::GetList(
				array(),
				array("ID" => $log_tmp_id),
				false,
				false,
				array(
					"ID", "EVENT_ID", "SOURCE_ID",
					'ENTITY_TYPE', 'ENTITY_ID',
					'PARAMS',
				),
				$arListParams
			))
			&& ($arLog = $rsLog->Fetch())
		)
		{
			$liveFeedCommentsParams = \Bitrix\Socialnetwork\ComponentHelper::getLFCommentsParams([
				"ID" => $arLog["ID"],
				"EVENT_ID" => $arLog["EVENT_ID"],
				"ENTITY_TYPE" => $arLog["ENTITY_TYPE"],
				"ENTITY_ID" => $arLog["ENTITY_ID"],
				"SOURCE_ID" => $arLog["SOURCE_ID"],
				"PARAMS" => $arLog["PARAMS"]
			]);

			$entityXmlId = $liveFeedCommentsParams['ENTITY_XML_ID'];

			$arCommentEvent = CSocNetLogTools::FindLogCommentEventByLogEventID($arLog["EVENT_ID"]);

			$bHasEditCallback = (
				is_array($arCommentEvent)
				&& isset($arCommentEvent["UPDATE_CALLBACK"])
				&& (
					$arCommentEvent["UPDATE_CALLBACK"] == "NO_SOURCE"
					|| is_callable($arCommentEvent["UPDATE_CALLBACK"])
				)
			);

			$bHasDeleteCallback = (
				is_array($arCommentEvent)
				&& isset($arCommentEvent["DELETE_CALLBACK"])
				&& (
					$arCommentEvent["DELETE_CALLBACK"] == "NO_SOURCE"
					|| is_callable($arCommentEvent["DELETE_CALLBACK"])
				)
			);

			$arParams = array(
				"PATH_TO_USER" => $_REQUEST["p_user"],
				"NAME_TEMPLATE" => $_REQUEST["nt"],
				"SHOW_LOGIN" => $_REQUEST["sl"],
				"AVATAR_SIZE_COMMENT" => $as,
				"PATH_TO_SMILE" => $_REQUEST["p_smile"],
				"DATE_TIME_FORMAT" => $_REQUEST["dtf"]
			);

			$cache_time = 31536000;
			$cache = new CPHPCache;

			$arCacheID = array();
			$arKeys = array(
				"AVATAR_SIZE_COMMENT",
				"NAME_TEMPLATE",
				"NAME_TEMPLATE_WO_NOBR",
				"SHOW_LOGIN",
				"DATE_TIME_FORMAT",
				"PATH_TO_USER",
				"PATH_TO_GROUP",
				"PATH_TO_CONPANY_DEPARTMENT"
			);
			foreach($arKeys as $param_key)
			{
				$arCacheID[$param_key] = (array_key_exists($param_key, $arParams) ? $arParams[$param_key] : false);
			}

			$cache_id = "log_comments_".$log_tmp_id."_".md5(serialize($arCacheID))."_mobile_app_".SITE_ID."_".LANGUAGE_ID."_".FORMAT_DATETIME."_".CTimeZone::GetOffset();
			$cache_path = "/sonet/log/".intval($log_tmp_id / 1000)."/".$log_tmp_id."/comments/";

			if (
				is_object($cache)
				&& $cache->InitCache($cache_time, $cache_id, $cache_path)
			)
			{
				$arCacheVars = $cache->GetVars();
				$arResult["arComments"] = $arCacheVars["COMMENTS_FULL_LIST"];
			}
			else
			{
				$arCommentsFullList = array();

				if (is_object($cache))
				{
					$cache->StartDataCache($cache_time, $cache_id, $cache_path);
				}

				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->StartTagCache($cache_path);
				}

				$arFilter = array("LOG_ID" => $log_tmp_id);

				$arSelect = array(
					"ID", "LOG_ID", "SOURCE_ID", "ENTITY_TYPE", "ENTITY_ID", "USER_ID", "EVENT_ID", "LOG_DATE", "MESSAGE", "TEXT_MESSAGE", "URL", "MODULE_ID",
					"GROUP_NAME", "GROUP_OWNER_ID", "GROUP_VISIBLE", "GROUP_OPENED", "GROUP_IMAGE_ID",
					"USER_NAME", "USER_LAST_NAME", "USER_SECOND_NAME", "USER_LOGIN", "USER_PERSONAL_PHOTO", "USER_PERSONAL_GENDER",
					"CREATED_BY_NAME", "CREATED_BY_LAST_NAME", "CREATED_BY_SECOND_NAME", "CREATED_BY_LOGIN", "CREATED_BY_PERSONAL_PHOTO", "CREATED_BY_PERSONAL_GENDER",
					"LOG_SITE_ID", "LOG_SOURCE_ID",
					"SHARE_DEST",
					"RATING_TYPE_ID", "RATING_ENTITY_ID",
					"UF_*"
				);

				$arListParams = array("USE_SUBSCRIBE" => "N");

				$arUFMeta = __SLMGetUFMeta();

				$dbComments = CSocNetLogComments::GetList(
					array("LOG_DATE" => "ASC"),
					$arFilter,
					false,
					false,
					$arSelect,
					$arListParams
				);

				while($arComments = $dbComments->GetNext())
				{
					if (defined("BX_COMP_MANAGED_CACHE"))
					{
						$CACHE_MANAGER->RegisterTag("USER_NAME_".intval($arComments["USER_ID"]));
					}

					$arComments["UF"] = $arUFMeta;
					foreach($arUFMeta as $field_name => $arUF)
					{
						if (array_key_exists($field_name, $arComments))
						{
							$arComments["UF"][$field_name]["VALUE"] = $arComments[$field_name];
							$arComments["UF"][$field_name]["ENTITY_VALUE_ID"] = $arComments["ID"];
						}
					}
					$arResult["arComments"][] = __SLMGetLogCommentRecord($arComments, $arParams, false);
				}

				if (is_object($cache))
				{
					$arCacheData = Array(
						"COMMENTS_FULL_LIST" => $arResult["arComments"]
					);
					$cache->EndDataCache($arCacheData);
					if(defined("BX_COMP_MANAGED_CACHE"))
					{
						$CACHE_MANAGER->EndTagCache();
					}
				}
			}

			$count = 0;
			foreach ($arResult["arComments"] as $key => $arCommentTmp)
			{
				if ($key === 0)
				{
					$rating_entity_type = $arCommentTmp["EVENT"]["RATING_TYPE_ID"];
				}

				if (
					(
						$last_comment_ts > 0
						&& $arCommentTmp["LOG_DATE_TS"] >= $last_comment_ts
					)
					|| (
						$last_comment_ts <= 0
						&& $arCommentTmp["EVENT"]["ID"] >= $last_comment_id
					)

				)
				{
					$count++;
					unset($arResult["arComments"][$key]);
				}
				else
				{
					$arCommentID[] = $arCommentTmp["EVENT"]["RATING_ENTITY_ID"];
				}
			}

			$arRatingComments = array();
			if(
				!empty($arCommentID)
				&& $rating_entity_type <> ''
			)
			{
				$arRatingComments = CRatings::GetRatingVoteResult($rating_entity_type, $arCommentID);
			}

			foreach($arResult["arComments"] as $key => $arCommentTmp)
			{
				if (array_key_exists($arCommentTmp["EVENT"]["RATING_ENTITY_ID"], $arRatingComments))
				{
					$arResult["arComments"][$key]["EVENT"]["RATING_USER_VOTE_VALUE"] = $arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["USER_VOTE"];
					$arResult["arComments"][$key]["EVENT"]["RATING_USER_HAS_VOTED"] = ($arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["USER_HAS_VOTED"] == "Y" ? "Y" : "N");
					$arResult["arComments"][$key]["EVENT"]["RATING_TOTAL_POSITIVE_VOTES"] = intval($arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["TOTAL_POSITIVE_VOTES"]);
					$arResult["arComments"][$key]["EVENT"]["RATING_TOTAL_NEGATIVE_VOTES"] = intval($arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["TOTAL_NEGATIVE_VOTES"]);
					$arResult["arComments"][$key]["EVENT"]["RATING_TOTAL_VALUE"] = $arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["TOTAL_VALUE"];
					$arResult["arComments"][$key]["EVENT"]["RATING_TOTAL_VOTES"] = intval($arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["TOTAL_VOTES"]);
				}
				else
				{
					$arResult["arComments"][$key]["EVENT"]["RATING_USER_VOTE_VALUE"] = 0;
					$arResult["arComments"][$key]["EVENT"]["RATING_USER_HAS_VOTED"] = "N";
					$arResult["arComments"][$key]["EVENT"]["RATING_TOTAL_POSITIVE_VOTES"] = 0;
					$arResult["arComments"][$key]["EVENT"]["RATING_TOTAL_NEGATIVE_VOTES"] = 0;
					$arResult["arComments"][$key]["EVENT"]["RATING_TOTAL_VALUE"] = 0;
					$arResult["arComments"][$key]["EVENT"]["RATING_TOTAL_VOTES"] = 0;
				}

				if ($rating_entity_type <> '')
				{
					$arResult["arComments"][$key]["EVENT_FORMATTED"]["ALLOW_VOTE"] = CRatings::CheckAllowVote(
						array(
							"ENTITY_TYPE_ID" => $rating_entity_type,
							"OWNER_ID" => $arResult["arComments"][$key]["EVENT"]["USER_ID"]
						)
					);
				}

				if (
					is_array($arResult["arComments"][$key]["UF"])
					&& count($arResult["arComments"][$key]["UF"]) > 0
				)
				{
					ob_start();

					$eventHandlerID = false;
					$eventHandlerID = AddEventHandler("main", "system.field.view.file", "__logUFfileShowMobile");
					foreach ($arResult["arComments"][$key]["UF"] as $FIELD_NAME => $arUserField)
					{
						if(!empty($arUserField["VALUE"]))
						{
							$APPLICATION->IncludeComponent(
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
						RemoveEventHandler("main", "system.field.view.file", $eventHandlerID);
					}

					$strUFBlock = ob_get_contents();
					ob_end_clean();

					$arResult["arComments"][$key]["EVENT_FORMATTED"]["UF_FORMATTED"] = $strUFBlock;
				}

				$arResult["arComments"][$key]["EVENT_FORMATTED"]["CAN_EDIT"] = (
					$bHasEditCallback
					&& intval($arResult["arComments"][$key]["EVENT"]["USER_ID"]) > 0
					&& intval($arResult["arComments"][$key]["EVENT"]["USER_ID"]) == $USER->GetId()
						? "Y"
						: "N"
				);

				$arResult["arComments"][$key]["EVENT_FORMATTED"]["CAN_DELETE"] = (
					$bHasDeleteCallback
					&& $arResult["arComments"][$key]["EVENT_FORMATTED"]["CAN_EDIT"] == "Y"
						? "Y"
						: "N"
				);

				$timestamp = MakeTimeStamp($arResult["arComments"][$key]["EVENT"]["LOG_DATE"]);
				$arFormat = Array(
					"tommorow" => "tommorow, ".GetMessage("SONET_LOG_COMMENT_FORMAT_TIME"),
					"today" => "today, ".GetMessage("SONET_LOG_COMMENT_FORMAT_TIME"),
					"yesterday" => "yesterday, ".GetMessage("SONET_LOG_COMMENT_FORMAT_TIME"),
					"" => (
						date("Y", $timestamp) == date("Y")
							? GetMessage("SONET_LOG_COMMENT_FORMAT_DATE")
							: GetMessage("SONET_LOG_COMMENT_FORMAT_DATE_YEAR")
					)
				);

				$arResult["arComments"][$key]["EVENT_FORMATTED"]["DATETIME"] = FormatDate($arFormat, $timestamp);

				$handlerManager = new Bitrix\Socialnetwork\CommentAux\HandlerManager();
				if (
					isset($arResult["arComments"][$key]["EVENT"]['MESSAGE'])
					&& ($handler = $handlerManager->getHandlerByPostText($arResult["arComments"][$key]["EVENT"]['MESSAGE']))
				)
				{
					if ($handler->checkRecalcNeeded($arResult["arComments"][$key]['EVENT'], array()))
					{
						$commentAuxFields = $arResult["arComments"][$key]['EVENT'];
						$params = $handler->getParamsFromFields($commentAuxFields);
						if (!empty($params))
						{
							$handler->setParams($params);
						}

						$handler->setOptions(array(
							'mobile' => false,
							'cache' => false,
							'suffix' => (!empty($commentEntitySuffix) ? $commentEntitySuffix : ''),
							'logId' => $log_tmp_id,
						));
						$arResult["arComments"][$key]['EVENT_FORMATTED']['MESSAGE']  = $handler->getText();
						$arResult["arComments"][$key]["AUX"] = $handler->getType();
						$arResult["arComments"][$key]["CAN_DELETE"] = ($handler->canDelete() ? 'Y' : 'N');
					}
				}
			}

			$db_res = new CDBResult();
			$db_res->InitFromArray(array_reverse($arResult["arComments"], true));
			$db_res->NavNum = 1;
			$db_res->NavStart(20, false);
			$records = array();
			$arResult["LAST_LOG_TS"] = intval($_REQUEST["last_log_ts"]);
			$arResult["COUNTER_TYPE"] = $_REQUEST["counter_type"];

			$commentData = $commentInlineDiskData = $inlineDiskObjectIdList = $inlineDiskAttachedObjectIdList = array();

			while($arComment = $db_res->fetch())
			{
				$commentId = (
					isset($arComment["EVENT"]["SOURCE_ID"])
					&& intval($arComment["EVENT"]["SOURCE_ID"]) > 0
						? intval($arComment["EVENT"]["SOURCE_ID"])
						: $arComment["EVENT"]["ID"]
				);

				if ($ufData = \Bitrix\Mobile\Livefeed\Helper::getDiskDataByCommentText($arComment["EVENT"]["MESSAGE"]))
				{
					$commentInlineDiskData[$commentId] = $ufData;
					$inlineDiskObjectIdList = array_merge($inlineDiskObjectIdList, $ufData['OBJECT_ID']);
					$inlineDiskAttachedObjectIdList = array_merge($inlineDiskAttachedObjectIdList, $ufData['ATTACHED_OBJECT_ID']);
				}

				$commentData[] = $arComment;
			}

			$inlineDiskAttachedObjectIdImageList = $entityAttachedObjectIdList = array();
			if ($ufData = \Bitrix\Mobile\Livefeed\Helper::getDiskUFDataForComments($inlineDiskObjectIdList, $inlineDiskAttachedObjectIdList))
			{
				$inlineDiskAttachedObjectIdImageList = $ufData['ATTACHED_OBJECT_DATA'];
				$entityAttachedObjectIdList = $ufData['ENTITIES_DATA'];
			}

			foreach($commentData as $arComment)
			{
				$commentId = (
					isset($arComment["EVENT"]["SOURCE_ID"])
					&& intval($arComment["EVENT"]["SOURCE_ID"]) > 0
						? intval($arComment["EVENT"]["SOURCE_ID"])
						: $arComment["EVENT"]["ID"]
				);

				$last_comment_ts = ($last_comment_ts ?: $arComment["LOG_DATE_TS"]);
				$records[$commentId] = array(
					"ID" => $commentId,
					"NEW" => (
						($arResult["COUNTER_TYPE"] == "**")
						&& $arComment["EVENT"]["USER_ID"] != $USER->getID()
						&& intval($arResult["LAST_LOG_TS"]) > 0
						&& (MakeTimeStamp($arComment["EVENT"]["LOG_DATE"]) - intval($arResult["TZ_OFFSET"])) > $arResult["LAST_LOG_TS"]
					) ? "Y" : "N",
					"APPROVED" => "Y",
					"POST_TIMESTAMP" => $arComment["LOG_DATE_TS"],
					"AUTHOR" => array(
						"ID" => $arComment["EVENT"]["USER_ID"],
						"NAME" => $arComment["EVENT"]["~CREATED_BY_NAME"],
						"LAST_NAME" => $arComment["EVENT"]["~CREATED_BY_LAST_NAME"],
						"SECOND_NAME" => $arComment["EVENT"]["~CREATED_BY_SECOND_NAME"],
						"PERSONAL_GENDER" => $arComment["EVENT"]["~CREATED_BY_PERSONAL_GENDER"],
						"AVATAR" => $arComment["AVATAR_SRC"]
					),
					"FILES" => $arComment["EVENT_FORMATTED"]["FILES"],
					"UF" => $arComment["UF"],
					"~POST_MESSAGE_TEXT" => $arComment["EVENT"]["MESSAGE"],
					"POST_MESSAGE_TEXT" => CSocNetTextParser::closetags(htmlspecialcharsback((
						array_key_exists("EVENT_FORMATTED", $arComment)
						&& array_key_exists("MESSAGE", $arComment["EVENT_FORMATTED"])
							? $arComment["EVENT_FORMATTED"]["MESSAGE"]
							: $arComment["EVENT"]["MESSAGE"]
					))),
					"RATING_VOTE_ID" => $arComment["EVENT"]["RATING_TYPE_ID"].'_'.$arComment["EVENT"]["RATING_ENTITY_ID"].'-'.(time()+random_int(0, 1000)),
					"AUX" => (isset($arComment["AUX"]) ? $arComment["AUX"] : ''),
				);

				if (
					!empty($inlineDiskAttachedObjectIdImageList)
					&& isset($commentInlineDiskData[$commentId])
				)
				{
					$inlineAttachedImagesId = \Bitrix\Mobile\Livefeed\Helper::getCommentInlineAttachedImagesId([
						'commentId' => $commentId,
						'inlineDiskAttachedObjectIdImageList' => $inlineDiskAttachedObjectIdImageList,
						'commentInlineDiskData' => $commentInlineDiskData[$commentId],
						'entityAttachedObjectIdList' => $entityAttachedObjectIdList[$commentId],
					]);

					$records[$commentId]["UF"]["UF_SONET_COM_DOC"]['VALUE_INLINE'] = $inlineAttachedImagesId;
				}
			}

			$eventHandlerID = AddEventHandler('main', 'system.field.view.file', Array('CBlogTools', 'blogUFfileShow'));

			$rights = CSocNetLogComponent::getCommentRights(array(
				"EVENT_ID" => $arLog["EVENT_ID"],
				"SOURCE_ID" => $arLog["SOURCE_ID"]
			));

			$APPLICATION->IncludeComponent(
				"bitrix:main.post.list",
				"",
				array(
					"RATING_TYPE_ID" => $rating_entity_type,
					"ENTITY_XML_ID" => $entityXmlId,
					"POST_CONTENT_TYPE_ID" => $postContentTypeId,
					"COMMENT_CONTENT_TYPE_ID" => $commentContentTypeId,
					"RECORDS" => $records,
					"NAV_STRING" => SITE_DIR.'mobile/ajax.php?'.http_build_query(array(
							"logid" => $arLog["ID"],
							"as" => $arParams["AVATAR_SIZE_COMMENT"],
							"nt" => $arParams["NAME_TEMPLATE"],
							"sl" => $arParams['SHOW_LOGIN'],
							"dtf" => $arParams["DATE_TIME_FORMAT"],
							"p_user" => $arParams["PATH_TO_USER"],
							"p_le" => $arParams["PATH_TO_LOG_ENTRY"],
							"action" => 'get_comments',
							"mobile_action" => 'get_comments',
							"last_comment_ts" => $last_comment_ts,
							"last_log_ts" => $arResult["LAST_LOG_TS"],
							"counter_type" => $arResult["COUNTER_TYPE"]
						)),
					"NAV_RESULT" => $db_res,
					"PREORDER" => "N",
					"RIGHTS" => array(
						"MODERATE" => "N",
						"EDIT" => $rights["COMMENT_RIGHTS_EDIT"],
						"DELETE" => $rights["COMMENT_RIGHTS_DELETE"],
						"CREATETASK" => (
							\Bitrix\Main\ModuleManager::isModuleInstalled('tasks')
							&& (
								!\Bitrix\Main\Loader::includeModule('bitrix24')
								|| CBitrix24BusinessTools::isToolAvailable($USER->getId(), "tasks")
							)
						)
					),
					"VISIBLE_RECORDS_COUNT" => $count,

					"VIEW_URL" => str_replace("#log_id#", $arLog["ID"], $arParams["PATH_TO_LOG_ENTRY"]).(mb_strpos($arParams["PATH_TO_LOG_ENTRY"], "?") === false ? "?" : "&")."empty_get_comments=Y",
					"EDIT_URL" => SITE_DIR."mobile/ajax.php?".
						"action=get_comment_data&mobile_action=get_log_comment_data&".
						"log_id=".($arLog["ID"])."&".
						"cid=#ID#",
					"MODERATE_URL" => "",
					"DELETE_URL" => SITE_DIR."mobile/ajax.php?".
						"action=delete_comment&mobile_action=delete_comment&".
						"log_id=".($arLog["ID"])."&".
						"delete_id=#ID#",
					"AUTHOR_URL" => $arParams["PATH_TO_USER"],

					"AVATAR_SIZE" => $arParams["AVATAR_SIZE_COMMENT"],
					"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
					"SHOW_LOGIN" => $arParams['SHOW_LOGIN'],

					"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
					"LAZYLOAD" => "Y",

					"IMAGE_SIZE" => $arParams["IMAGE_SIZE"],
					"SHOW_POST_FORM" => "Y",
				),
				null
			);
			RemoveEventHandler('main', 'system.field.view.file', $eventHandlerID);
		}
	}
	elseif ($action == "get_more_destination")
	{
		$arResult["arDestinations"] = false;
		$log_id = intval($_REQUEST["log_id"]);
		$author_id = intval($_REQUEST["author_id"]);
		$iDestinationLimit = intval($_REQUEST["dlim"]);

		if ($log_id > 0)
		{
			$dbRight = CSocNetLogRights::GetList(array(), array("LOG_ID" => $log_id));
			while ($arRight = $dbRight->Fetch())
			{
				$arRights[] = $arRight["GROUP_CODE"];
			}

			$arParams = array(
				"MOBILE" => "Y",
				"PATH_TO_USER" => $_REQUEST["p_user"],
				"PATH_TO_GROUP" => $_REQUEST["p_group"],
				"PATH_TO_CONPANY_DEPARTMENT" => $_REQUEST["p_dep"],
				"PATH_TO_CRMLEAD" => $_REQUEST["p_crmlead"],
				"PATH_TO_CRMDEAL" => $_REQUEST["p_crmdeal"],
				"PATH_TO_CRMCONTACT" => $_REQUEST["p_crmcontact"],
				"PATH_TO_CRMCOMPANY" => $_REQUEST["p_crmcompany"],
				"NAME_TEMPLATE" => $_REQUEST["nt"],
				"SHOW_LOGIN" => $_REQUEST["sl"],
				"DESTINATION_LIMIT" => 100,
				"CHECK_PERMISSIONS_DEST" => "N",
				"CREATED_BY" => $author_id
			);

			if (
				($logFields = \Bitrix\Socialnetwork\LogTable::getList([
					'filter' => [
						'=ID' => $log_id,
					],
					'select' => [ 'ENTITY_TYPE', 'ENTITY_ID', 'EVENT_ID', 'MODULE_ID' ],
				])->fetch())
				&& ($logFields['MODULE_ID'] === 'crm_shared')
				&& \Bitrix\Main\Loader::includeModule('crm')
			)
			{
				\CCrmLiveFeed::OnBeforeSocNetLogEntryGetRights($logFields, $arRights);
			}

			$arDestinations = CSocNetLogTools::FormatDestinationFromRights($arRights, $arParams, $iMoreCount);
			if (is_array($arDestinations))
			{
				$iDestinationsHidden = 0;
				$arGroupID = CSocNetLogTools::GetAvailableGroups();

				foreach($arDestinations as $key => $arDestination)
				{
					if (
						array_key_exists("TYPE", $arDestination)
						&& array_key_exists("ID", $arDestination)
						&& $arDestination["TYPE"] == "SG"
						&& !in_array(intval($arDestination["ID"]), $arGroupID)
					)
					{
						unset($arDestinations[$key]);
						$iDestinationsHidden++;
					}
				}

				$arResult["arDestinations"] = array_slice($arDestinations, $iDestinationLimit);
				$arResult["iDestinationsHidden"] = $iDestinationsHidden;
			}
		}
	}
	elseif (
		$action == "send_comment_writing"
		&& CModule::IncludeModule("pull")
	)
	{
		$arParams = array(
			"ENTITY_XML_ID" => $_REQUEST["ENTITY_XML_ID"],
			"NAME_TEMPLATE" => $_REQUEST["nt"],
			"SHOW_LOGIN" => $_REQUEST["sl"],
			"AVATAR_SIZE_COMMENT" => intval($as),
		);

		$rsUser = CUser::GetList(
			"last_name",
			"asc",
			array(
				"ID" => intval($USER->GetId())
			),
			array("FIELDS" => array("ID", "NAME", "LAST_NAME", "SECOND_NAME", "LOGIN", "PERSONAL_GENDER", "PERSONAL_PHOTO"))
		);
		if ($arUser = $rsUser->Fetch())
		{
			$arFileTmp = CFile::ResizeImageGet(
				$arUser["PERSONAL_PHOTO"],
				array("width" => $arParams["AVATAR_SIZE_COMMENT"], "height" => $arParams["AVATAR_SIZE_COMMENT"]),
				BX_RESIZE_IMAGE_EXACT,
				false
			);

			CPullWatch::AddToStack('UNICOMMENTS'.$_REQUEST["ENTITY_XML_ID"],
				Array(
					'module_id' => 'unicomments',
					'command' => 'answer',
					'expiry' => 60,
					'params' => Array(
						"USER_ID" => $arUser["ID"],
						"ENTITY_XML_ID" => $_REQUEST["ENTITY_XML_ID"],
						"TS" => time(),
						"NAME" => CUser::FormatName($arParams["NAME_TEMPLATE"], $arUser, ($arParams["SHOW_LOGIN"] != "N" ? true : false)),
						"AVATAR" => ($arFileTmp && isset($arFileTmp['src']) ? $arFileTmp['src'] : false)
					)
				)
			);

			$arResult["SUCCESS"] = 'Y';
		}
	}

	header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo \Bitrix\Main\Web\Json::encode($arResult);
}

define('PUBLIC_AJAX_MODE', true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
die();
?>