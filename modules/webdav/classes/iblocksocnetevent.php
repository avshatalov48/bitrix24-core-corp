<?php
IncludeModuleLangFile(__FILE__);

class CWebDavSocNetEvent
{
	private static $instance;

	var $arPath;
	var $IBlockID = null;
	var $forumID = null;
	var $event_id = null;
	var $object = null;

	public static function GetRuntime() 
	{
		if (!isset(self::$instance))
		{
			$c = __CLASS__;
			self::$instance = new $c;
			if (!CModule::IncludeModule('webdav') || !CModule::IncludeModule('iblock') || !CModule::IncludeModule('socialnetwork'))
				return false;
			}

		return self::$instance;
	}

	public function __clone()
	{
		trigger_error('Clone in not allowed.', E_USER_ERROR);
	}

	public function __construct()
	{
		if (IsModuleInstalled('intranet')) // try to get defaults
		{
			$userPath = COption::GetOptionString('intranet', 'path_user', '', SITE_ID);
			if (!empty($userPath))
			{
				$userPath = str_replace("#USER_ID#", $GLOBALS['USER']->GetID(), $userPath);
				$this->arPath['PATH_TO_USER'] = $userPath;
				$this->arPath['PATH_TO_FILES'] = $userPath.'files/lib/';
				$this->arPath['ELEMENT_UPLOAD_URL'] = $userPath.'files/element/upload/0/';
				$this->arPath['ELEMENT_SHOW_INLINE_URL'] = $userPath.'files/element/edit/#element_id#/VIEW/';
				$this->arPath['ELEMENT_EDIT_INLINE_URL'] = $userPath.'files/element/edit/#element_id#/EDIT/';
				$this->arPath['ELEMENT_HISTORYGET_URL'] = $userPath.'files/element/historyget/#element_id#/#element_name#';
			}
		}
	}

	public function IsInitiated()
	{
		return ($this->IBlockID != null);
	}

	public function SetParams($arParams)
	{
		$this->arPath['PATH_TO_USER'] = $arParams['PATH_TO_USER'];
		$this->arPath['PATH_TO_FILES_ELEMENT'] = $arParams['PATH_TO_FILES_ELEMENT'];
		$this->IBlockID = $arParams['IBLOCK_ID'];
		$this->forumID = $arParams['FORUM_ID'];
		$this->event_id = ENTITY_FILES_COMMON_EVENT_ID;
		$this->event_comments_id = ENTITY_FILES_COMMON_COMMENTS_EVENT_ID;

		CWebDavIblock::LibOptions('lib_paths', true, $this->IBlockID, $this->arPath['PATH_TO_FILES_ELEMENT']);
	}

	public function SetSocnetVars($arResult, $arParams = array())
	{
		if (isset($arParams['PATH_TO_SMILE']))
			$this->arPath["PATH_TO_SMILE"] = $arParams["PATH_TO_SMILE"];

		$this->bIsGroup = array_key_exists("GROUP", $arResult) || (array_key_exists("VARIABLES", $arResult) && array_key_exists("group_id", $arResult['VARIABLES']));
		if ($this->bIsGroup && isset($arResult['VARIABLES']['group_id']))
		{
			$this->entity_id = intval($arResult['VARIABLES']['group_id']);
		}

		if (isset($arParams['OBJECT']))
			$this->entity_id = ($this->bIsGroup ? $arParams["OBJECT"]->attributes["group_id"] : $arParams["OBJECT"]->attributes["user_id"]);

		$userID = $GLOBALS['USER']->GetID();
		$this->arPath['PATH_TO_USER'] = (
			isset($arParams["PATH_TO_USER"]) ? $arParams["PATH_TO_USER"] : 
			(isset($arResult["PATH_TO_USER"]) ? $arResult["PATH_TO_USER"] : '')
		);
		$this->arPath['SEF_FOLDER'] = $arResult["SEF_FOLDER"];
		if ($this->bIsGroup)
		{
			$this->arPath['PATH_TO_FILES'] = str_replace(array("#group_id#", "#path#"), array($this->entity_id, ''), $arResult["PATH_TO_GROUP_FILES"]);
			$this->arPath["ELEMENT_UPLOAD_URL"] = str_replace(array("#group_id#", "#section_id#"), array($this->entity_id, 0), $arResult["PATH_TO_GROUP_FILES_ELEMENT_UPLOAD"]);
			$this->arPath["ELEMENT_SHOW_INLINE_URL"] = str_replace(array("#group_id#", "#action#"), array($this->entity_id, 'VIEW'), $arResult["PATH_TO_GROUP_FILES_ELEMENT_EDIT"]);
			$this->arPath["ELEMENT_EDIT_INLINE_URL"] = str_replace(array("#group_id#", "#action#"), array($this->entity_id, 'EDIT'), $arResult["PATH_TO_GROUP_FILES_ELEMENT_EDIT"]);
			$this->arPath['ELEMENT_HISTORYGET_URL'] = str_replace("#group_id#", $this->entity_id, $arResult["PATH_TO_GROUP_FILES_ELEMENT_HISTORY_GET"]);
		}
		else
		{
			$this->arPath['PATH_TO_FILES'] = str_replace(array("#user_id#", "#path#"), array($userID, ''), $arResult["PATH_TO_USER_FILES"]);
			$this->arPath["ELEMENT_UPLOAD_URL"] = str_replace(array("#user_id#", "#section_id#"), array($userID, 0), $arResult["PATH_TO_USER_FILES_ELEMENT_UPLOAD"]);
			$this->arPath["ELEMENT_SHOW_INLINE_URL"] = str_replace(array("#user_id#", "#action#"), array($userID, 'VIEW'), $arResult["PATH_TO_USER_FILES_ELEMENT_EDIT"]);
			$this->arPath["ELEMENT_EDIT_INLINE_URL"] = str_replace(array("#user_id#", "#action#"), array($userID, 'EDIT'), $arResult["PATH_TO_USER_FILES_ELEMENT_EDIT"]);
			$this->arPath['ELEMENT_HISTORYGET_URL'] = str_replace("#user_id#", $userID, $arResult["PATH_TO_USER_FILES_ELEMENT_HISTORY_GET"]);
		}
		//$this->arPath['PATH_TO_GROUP'] = (isset($arParams['PATH_TO_GROUP'])?$arParams['PATH_TO_GROUP']:'');
		$this->arPath['PATH_TO_GROUP'] = (
			isset($arParams["PATH_TO_GROUP"]) ? $arParams["PATH_TO_GROUP"] : 
			(isset($arResult["PATH_TO_GROUP"]) ? $arResult["PATH_TO_GROUP"] : '')
		);
		$this->arPath['PATH_TO_USER'] = (isset($arParams['PATH_TO_USER'])?$arParams['PATH_TO_USER']:'');
		$this->arPath['PATH_TO_GROUP_FILES_ELEMENT'] = $arResult["PATH_TO_GROUP_FILES_ELEMENT"];
		$this->arPath['PATH_TO_USER_FILES_ELEMENT'] = $arResult["PATH_TO_USER_FILES_ELEMENT"];
		$this->event_id = ENTITY_FILES_SOCNET_EVENT_ID;
		$this->event_comments_id = ENTITY_FILES_SOCNET_COMMENTS_EVENT_ID;

		$this->IBlockID = (
							array_key_exists("FILES_GROUP_IBLOCK_ID", $arParams) && intval($arParams["FILES_GROUP_IBLOCK_ID"]) > 0
								? $arParams["FILES_GROUP_IBLOCK_ID"]
								: (
									array_key_exists("FILES_USER_IBLOCK_ID", $arParams) && intval($arParams["FILES_USER_IBLOCK_ID"]) > 0
										? $arParams["FILES_USER_IBLOCK_ID"]
										: null
								)
						);
		$this->forumID = (
							array_key_exists("FILES_FORUM_ID", $arParams) && intval($arParams["FILES_FORUM_ID"]) > 0
								? $arParams["FILES_FORUM_ID"]
								: null
						);

		CWebDavIblock::LibOptions('lib_paths', true, $this->IBlockID,
			(strlen($this->arPath['PATH_TO_GROUP_FILES_ELEMENT']) > 0) ? $this->arPath['PATH_TO_GROUP_FILES_ELEMENT'] : $this->arPath['PATH_TO_USER_FILES_ELEMENT']);
	}

	public function SocNetGroupRename($id, $arParams)
	{
		if ($this->IBlockID == null)
			return;
		if (! (isset($arParams['NAME']) && strlen($arParams['NAME'])>0))
			return;

		$arGroup = CSocNetGroup::GetByID($id);
		$sOldName = GetMessage('SONET_GROUP_PREFIX').$arGroup['NAME'];
		$sNewName = GetMessage('SONET_GROUP_PREFIX').$arParams['NAME'];
		
		if($sOldName === $sNewName)
		{
			return;
		}
		
		$arFilter = array(
			"IBLOCK_ID" => $this->IBlockID,
			"SOCNET_GROUP_ID" => intval($id),
		);
		$se = new CIBlockSection;
		$dbSection = $se->GetList(array(), $arFilter, false, array('ID'));
		if ($arGroupSection = $dbSection->Fetch())
		{
			$sectionID = $arGroupSection['ID'];
			$se->Update($sectionID, array("NAME" => $sNewName));
			$GLOBALS["CACHE_MANAGER"]->ClearByTag("webdav_aggregator");
		}
	}

	public function GetSocnetLogByFileID($sourceID, $eventID)
	{
		$arLog = null;
		$rsLog = CSocNetLog::GetList(Array("ID" => "DESC"), array(
			'SOURCE_ID' => $sourceID,
			'EVENT_ID' => $eventID,
		));
		if ($rsLog)
			$arLog = $rsLog->Fetch();
		return $arLog;
	}

	public function SocnetLogUpdateRights($ID, $iblockID, $eventID)
	{
		if (!CModule::IncludeModule("socialnetwork"))
		{
			return null;
		}

		$arReaders = CWebDavIblock::GetReaders($ID, $iblockID);

		if ($arLog = self::GetSocnetLogByFileID($ID, $eventID))
		{
			CSocNetLogRights::DeleteByLogID($arLog['ID']);

			if (CModule::IncludeModule("extranet"))
			{
				$arSiteID = CExtranet::GetSitesByLogDestinations($arReaders);
				CSocNetLog::Update($arLog['ID'], array("SITE_ID" => $arSiteID));
			}

			CSocNetLogRights::Add($arLog['ID'], $arReaders);
		}
	}

	public function SocnetLogFileMove($arParams)
	{
		$this->SocnetLogUpdateRights($arParams["ELEMENT"]["id"], $this->IBlockID, $this->event_id);
		CWebDavIblock::UpdateSearchRights($arParams["ELEMENT"]["id"], $this->IBlockID);
	}

	public function SocnetLogFileDelete($arParams)
	{
		if ($arLog = $this->GetSocnetLogByFileID($arParams["ELEMENT"]["id"], $this->event_id))
		{
			CSocNetLog::Delete($arLog['ID']);
		}
	}

	private function CheckParams($mode)
	{
		$arError = array();
		if (!isset($this->arPath["PATH_TO_USER"]))
			$arError[] = 'NO_PATH_TO_USER';
		if ($mode == 'U' && !isset($this->arPath["PATH_TO_USER_FILES_ELEMENT"]))
			$arError[] = 'NO_PATH_TO_ELEMENT';
		elseif ($mode == 'G' && !isset($this->arPath["PATH_TO_GROUP_FILES_ELEMENT"]))
			$arError[] = 'NO_PATH_TO_ELEMENT';
		elseif ( $mode == 'F' && !isset($this->arPath["PATH_TO_FILES_ELEMENT"]))
			$arError[] = 'NO_PATH_TO_ELEMENT';

		if (! (intval($this->IBlockID) > 0))
			$arError[] = 'NO_IBLOCK_ID';

		return (empty($arError));
	}

	public function SocnetLogFileAdd($arParams, $file=null)
	{
		return true;
	}

	public function SocnetNotify($arParams, $file = null)
	{
		if(!class_exists('CSocNetSubscription'))
			return;

		if (
			!array_key_exists("group_id", $arParams["OBJECT"]["ATTRIBUTES"])
			|| intval($arParams["OBJECT"]["ATTRIBUTES"]["group_id"]) <= 0
		)
			return;

		if (
			array_key_exists("dropped", $arParams["ELEMENT"])
			&& $arParams["ELEMENT"]["dropped"]
		)
			return;			

		$arReaders = CWebDavIblock::GetReaders($arParams["ELEMENT"]["id"], $arParams["OBJECT"]["IBLOCK_ID"]);
		if (!in_array("SG".intval($arParams["OBJECT"]["ATTRIBUTES"]["group_id"])."_".SONET_ROLES_USER, $arReaders))
			return;

		$url = $this->arPath["PATH_TO_GROUP_FILES_ELEMENT"];
		if (IsModuleInstalled("extranet") && strlen($this->arPath["SEF_FOLDER"]) > 0 && strpos($url, $this->arPath["SEF_FOLDER"]) === 0)
			$url = str_replace($this->arPath["SEF_FOLDER"], "#GROUPS_PATH#", $url);
			
		$urlParams = array(
			"SECTION_ID" => isset($arParams["OBJECT"]["SECTION_ID"])? $arParams["OBJECT"]["SECTION_ID"]: $arParams["section_id"],
			"ELEMENT_ID" => $arParams["ELEMENT"]["id"],
			"element_id" => $arParams["ELEMENT"]["id"],
			"ID" => $arParams["ELEMENT"]["id"],
			"group_id" => intval($arParams["OBJECT"]["ATTRIBUTES"]["group_id"]),
			"GROUP_ID" => intval($arParams["OBJECT"]["ATTRIBUTES"]["group_id"])
		);

		if (
			(strpos($url, "#PATH#") !== false) 
			&& ($this->object != null)
		)
			$urlParams["PATH"] = $this->object->GetObjectPath($this->object->GetObject(array("element_id" => $arParams["ELEMENT"]["id"])));

		$arNotifyParams = array(
			"LOG_ID" => false,
			"GROUP_ID" => array(intval($arParams["OBJECT"]["ATTRIBUTES"]["group_id"])),
			"NOTIFY_MESSAGE" => "",
			"FROM_USER_ID" => intval($arParams["ELEMENT"]["element"]["element_array"]["CREATED_BY"]),
			"URL" => str_replace(array('///','//'), '/', CComponentEngine::MakePathFromTemplate($url, $urlParams)),
			"MESSAGE" => GetMessage("SONET_IM_NEW_FILE", Array(
				"#title#" => "<a href=\"#URL#\" class=\"bx-notifier-item-action\">".$arParams["ELEMENT"]["name"]."</a>",
			)),
			"MESSAGE_OUT" => GetMessage("SONET_IM_NEW_FILE", Array(
				"#title#" => $arParams["ELEMENT"]["name"]
			))." (#URL#)",
			"EXCLUDE_USERS" => array(intval($arParams["ELEMENT"]["element"]["element_array"]["CREATED_BY"]))
		);

		CSocNetSubscription::NotifyGroup($arNotifyParams);

		return true;
	}	

	public function SocnetLogFileUpdate($arParams, $file=null)
	{
		/*

$arParams :
$arParams:array (
	'OPERATION' => 
	array (
		'UPDATE_TYPE' => 
		array (
			0 => 'PREVIEW_TEXT',
		),
		'NAME' => 'UPDATE',
	), ....
UPDATE_TYPE:
	'PREVIEW_TEXT' - изменение описание
	'TAGS' - измение тегов
	'FILE' - изменение файла
		 */

		global $USER;

		if ($file === null) // webdav 11.0.0
		{
			$file = array(
				"status" => "success",
				"hidden" => isset($arParams["ELEMENT"]["hidden"]) ? $arParams["ELEMENT"]["hidden"] : false,
				"dropped" => isset($arParams["ELEMENT"]["dropped"]) ? $arParams["ELEMENT"]["dropped"] : false,
				"title" => $arParams["ELEMENT"]["name"],
				"id" => $arParams["ELEMENT"]["id"]
			);
		}

		if ($file['dropped'] || $file['hidden'] || $file["status"] != "success") // don't notificate about attached files
			return;

		if (! $arLog = $this->GetSocnetLogByFileID($arParams["ELEMENT"]["id"], $this->event_id)) // file does'n exist in Log for some reason. Let's try to add it!
		{
			self::SocnetLogFileAdd($arParams);
		}
/*
		else
		{

			if (
				strlen($arLog["PARAMS"]) > 0
				&& $arTmp = unserialize($arLog["PARAMS"])
			)
				$arLogParams = $arTmp;
			else
				$arLogParams = array();

			$arLogParams["action"] = "!!!!!!!!!!!!!!!!"; // write kind for update to use it in format callback

			$sAuthorName = GetMessage("SONET_LOG_GUEST");
			$sAuthorUrl = "";
			if ($USER->IsAuthorized())
			{
				$sAuthorName = trim($USER->GetFullName());
				$sAuthorName = (empty($sAuthorName) ? $USER->GetLogin() : $sAuthorName);
				$sAuthorUrl = CComponentEngine::MakePathFromTemplate($this->arPath["PATH_TO_USER"],
					array("USER_ID" => $USER->GetID()));
			}

			$arFields = array_merge($arFields, array(
				"=LOG_DATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"=LOG_UPDATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"TITLE_TEMPLATE" => str_replace("#AUTHOR_NAME#", $sAuthorName, GetMessage("SONET_FILES_UPDATE_LOG")),
				"TITLE" => $file["title"],
				"MODULE_ID" => false,
				"CALLBACK_FUNC" => false,
				"SOURCE_ID" => $file["id"],
				"RATING_TYPE_ID" => "IBLOCK_ELEMENT",
				"RATING_ENTITY_ID" => intval($arParams["ELEMENT"]["id"]),
				"PARAMS" => serialize($arLogParams)
			));

			if ($USER->IsAuthorized())
				$arFields["USER_ID"] = $USER->GetID();			
			
			if (IsModuleInstalled("extranet"))
				$serverName = "#SERVER_NAME#";			
			else
				$serverName = (defined("SITE_SERVER_NAME") && strLen(SITE_SERVER_NAME) > 0) ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name");

			$arFields["MESSAGE"] = str_replace(array("#AUTHOR_NAME#", "#AUTHOR_URL#"), array(htmlspecialcharsEx($sAuthorName), $sAuthorUrl),
				($USER->IsAuthorized() ? GetMessage("SONET_LOG_TEMPLATE_AUTHOR") : GetMessage("SONET_LOG_TEMPLATE_GUEST"))."");
			$arFields["TEXT_MESSAGE"] = str_replace(array("#URL#", "#TITLE#"),
				array("http://".$serverName.$arFields["URL"],  $arFields["TITLE"]),
				GetMessage("SONET_FILES_UPDATE_LOG_TEXT"));



			$logID = CSocNetLog::Update($log_id, $arFields);
			if (intval($logID) > 0)
			{
				CSocNetLog::SendEvent($logID, "SONET_NEW_EVENT", $logID);
			}
			else
			{
				global $APPLICATION;
				if($ex = $APPLICATION->GetException())
				{
					$strError = $ex->GetString();
				}
			}

		}
*/
	}

	private function _getSocnetLogEntityByComment(&$arCommentFields, $create_new_log_entry = true)
	{
		$result = false;

		if (
			is_array($arCommentFields)
			&& (
				!array_key_exists("PARAM1", $arCommentFields) 
				|| empty($arCommentFields["PARAM1"])
			)
			&& array_key_exists("PARAM2", $arCommentFields)
			&& intval($arCommentFields["PARAM2"]) > 0
		)
		{
			$dbRes = CSocNetLog::GetList(
				array("ID" => "DESC"),
				array(
					"EVENT_ID" => array(ENTITY_FILES_SOCNET_EVENT_ID, ENTITY_FILES_COMMON_EVENT_ID),
					"SOURCE_ID" => $arCommentFields["PARAM2"] // file element id
				),
				false,
				false,
				array("ID", "ENTITY_TYPE", "ENTITY_ID", "TMP_ID", "COMMENTS_COUNT")
			);

			if ($arRes = $dbRes->Fetch())
			{
				$result = $arRes;
			}
			else
			{
				if ($create_new_log_entry == true)
				{
					$this->_fake_update_document($arCommentFields["PARAM2"]);
					$result = $this->_getSocnetLogEntityByComment($arCommentFields, false);
				}
			}
		}
		return $result;
	}

	private function _fake_update_document($docID)
	{
		if (is_object($this->object))
		{
			$this->object->_onEvent('Update', $docID, 'FILE', array('UPDATE_TYPE' => array('PUBLISH')));
		}
	}

	private function _getSocnetLogCommentByForumComment($forumPostID, $arSocnetLogEntity)
	{
		$commentID = false;
		$arFilter = array(
			"ENTITY_TYPE" => $arSocnetLogEntity["ENTITY_TYPE"],
			"ENTITY_ID" => $arSocnetLogEntity["ENTITY_ID"],
			"EVENT_ID" => $this->event_comments_id,
			"SOURCE_ID" => $forumPostID
		);
		$arListParams = array("USE_SUBSCRIBE" => "N");

		$dbComments = CSocNetLogComments::GetList(
			array(),
			$arFilter,
			false,
			false,
			array(),
			$arListParams
		);

		if ($arComments = $dbComments->GetNext())
		{
			$commentID = $arComments['ID'];
		}
		return $commentID;
	}

	public function SocnetLogMessageUpdate($ID, $arFields) // update log comment
	{
		$this->SocnetLogMessageAdd($ID, $arFields, true);
	}

	public function SocnetLogMessageDelete($ID, $arFields) // delete log comment
	{
		$arRes = $this->_getSocnetLogEntityByComment($arFields);

		if ($arRes && (intval($arRes["TMP_ID"]) > 0))
		{
			$commentID = $this->_getSocnetLogCommentByForumComment($ID, $arRes);
			if ($commentID)
			{
				CSocNetLogComments::Delete($commentID);
			}
		}
	}

	public function SocnetLogMessageAdd($ID, $arFields, $bUpdate = false) // add log comment
	{
		$arForum = CForumNew::GetByID($this->forumID);
		$arMessage = CForumMessage::GetByIDEx($ID);
		if ($arMessage["TOPIC_ID"])
			$arTopic = CForumTopic::GetByID($arMessage["TOPIC_ID"]);

		$arRes = $this->_getSocnetLogEntityByComment($arMessage);

		if ($arRes && (intval($arRes["TMP_ID"]) > 0))
		{
			$parser = new textParser(LANGUAGE_ID, $this->arPath["PATH_TO_SMILE"]);
			$parser->image_params["width"] = false;
			$parser->image_params["height"] = false;

			$arAllow = array(
				"HTML" => "N",
				"ANCHOR" => "N",
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
			
			if (intval($arRes["COMMENTS_COUNT"]) == intval($arTopic["POSTS"]))
			{
				$url = CComponentEngine::MakePathFromTemplate($arParams["~URL_TEMPLATES_MESSAGE"],
					array("FID" => $arMessage["FORUM_ID"], "TID" => $arMessage["TOPIC_ID"], "MID"=> $ID));

				$arFieldsForSocnet = array(
					"ENTITY_TYPE" => $arRes["ENTITY_TYPE"],
					"ENTITY_ID" => $arRes["ENTITY_ID"],
					"EVENT_ID" => $this->event_comments_id,
					"=LOG_DATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
					"MESSAGE" => $parser->convert($arMessage["POST_MESSAGE"], $arAllow),
					"TEXT_MESSAGE" => $parser->convert4mail($arMessage["POST_MESSAGE"]),
					"URL" => $url,
					"MODULE_ID" => false,
					"SOURCE_ID" => $ID,
					"LOG_ID" => $arRes["TMP_ID"],
					"RATING_TYPE_ID" => "FORUM_POST",
					"RATING_ENTITY_ID" => intval($ID)
				);

				if (intVal($arMessage["AUTHOR_ID"]) > 0)
					$arFieldsForSocnet["USER_ID"] = $arMessage["AUTHOR_ID"];

				if ($bUpdate)
				{
					$commentID = $this->_getSocnetLogCommentByForumComment($ID, $arRes);
					if ($arMessage['APPROVED'] == 'Y')
					{
						if ($commentID)
						{
							CSocNetLogComments::Update($commentID, $arFieldsForSocnet);
						}
						else
						{
							$log_comment_id = CSocNetLogComments::Add($arFieldsForSocnet, false, false); //, true
							CSocNetLog::CounterIncrement($log_comment_id, false, false, "LC");
						}
					}
					else
					{
						if ($commentID)
						{
							CSocNetLogComments::Delete($commentID);
						}
					}
				}
				else
				{
					$log_comment_id = CSocNetLogComments::Add($arFieldsForSocnet, false, false); //, true
					CSocNetLog::CounterIncrement($log_comment_id, false, false, "LC");
				}
			}
			else // new socnetlog record created- we need to add all comments
			{
				$dbComments = CForumMessage::GetListEx(
					array(),
					array('TOPIC_ID' => $arMessage["TOPIC_ID"], "NEW_TOPIC" => "N")
				);

				while ($arComment = $dbComments->GetNext())
				{
					$url = CComponentEngine::MakePathFromTemplate($arParams["~URL_TEMPLATES_MESSAGE"],
						array("FID" => $arComment["FORUM_ID"], "TID" => $arComment["TOPIC_ID"], "MID"=> $arComment["ID"]));

					$arFieldsForSocnet = array(
						"ENTITY_TYPE" => $arRes["ENTITY_TYPE"],
						"ENTITY_ID" => $arRes["ENTITY_ID"],
						"EVENT_ID" => $this->event_comments_id,
						"=LOG_DATE" => $GLOBALS["DB"]->CharToDateFunction($arComment['POST_DATE'], "FULL", SITE_ID),
						"MESSAGE" => $parser->convert($arComment["POST_MESSAGE"], $arAllow),
						"TEXT_MESSAGE" => $parser->convert4mail($arComment["POST_MESSAGE"]),
						"URL" => $url,
						"MODULE_ID" => false,
						"SOURCE_ID" => $arComment["ID"],
						"LOG_ID" => $arRes["TMP_ID"],
						"RATING_TYPE_ID" => "FORUM_POST",
						"RATING_ENTITY_ID" => intval($arComment["ID"])
					);

					if (intVal($arComment["AUTHOR_ID"]) > 0)
						$arFieldsForSocnet["USER_ID"] = $arComment["AUTHOR_ID"];

					$log_comment_id = CSocNetLogComments::Add($arFieldsForSocnet, false, false); //, true
					CSocNetLog::CounterIncrement($log_comment_id, false, false, "LC");
				}
			}
		}
	}

	static function OnFillSocNetAllowedSubscribeEntityTypes(&$arSocNetAllowedSubscribeEntityTypes)
	{
		$arSocNetAllowedSubscribeEntityTypes[] = SONET_SUBSCRIBE_ENTITY_FILES;

		global $arSocNetAllowedSubscribeEntityTypesDesc;
		$arSocNetAllowedSubscribeEntityTypesDesc[SONET_SUBSCRIBE_ENTITY_FILES] = array(
			"TITLE_LIST" => GetMessage("WEBDAV_SOCNET_LOG_LIST_F_ALL"),
			"TITLE_ENTITY" => GetMessage("WEBDAV_SOCNET_LOG_F"),
			"TITLE_ENTITY_XDI" => GetMessage("WEBDAV_SOCNET_LOG_XDI_F"),
			"CLASS_DESC" => "",
			"METHOD_DESC" => "",
			"CLASS_DESC_GET" => __CLASS__,
			"METHOD_DESC_GET" => "GetIBlockByID",
			"CLASS_DESC_SHOW" => __CLASS__,
			"METHOD_DESC_SHOW" => "ShowIBlockByID",
			"USE_CB_FILTER" => "Y",
		);
	}

	static function OnFillSocNetLogEvents(&$arSocNetLogEvents)
	{
		$arSocNetLogEvents[ENTITY_FILES_COMMON_EVENT_ID] = array(
			"ENTITIES" =>	array(
				SONET_SUBSCRIBE_ENTITY_FILES => array(
					"TITLE" => GetMessage("WEBDAV_SOCNET_LOG_FILES"),
					"TITLE_SETTINGS" => GetMessage("WEBDAV_SOCNET_LOG_FILES_SETTINGS"),
					"TITLE_SETTINGS_1" => GetMessage("WEBDAV_SOCNET_LOG_FILES_SETTINGS_1"),
					"TITLE_SETTINGS_2" => GetMessage("WEBDAV_SOCNET_LOG_FILES_SETTINGS_2"),
				),
			),
			"CLASS_FORMAT" => __CLASS__,
			"METHOD_FORMAT" => "FormatEvent_Files",
			"HAS_CB" => "Y",
			"FULL_SET" => array(ENTITY_FILES_COMMON_EVENT_ID, ENTITY_FILES_COMMON_COMMENTS_EVENT_ID),
			"COMMENT_EVENT" => array(
				"EVENT_ID" => ENTITY_FILES_COMMON_COMMENTS_EVENT_ID,
				"CLASS_FORMAT" => __CLASS__,
				"METHOD_FORMAT" => "FormatComment_Files",
				"ADD_CALLBACK" => array(__CLASS__, "AddComment_Files")
			)
		);
	}

	static function GetEntity_Files($arFields, $bMail)
	{
		$arEntity = array();

		if (!CModule::IncludeModule("socialnetwork"))
		{
			return $arEntity;
		}

		$arEventParams = unserialize(strlen($arFields["~PARAMS"]) > 0 ? $arFields["~PARAMS"] : $arFields["PARAMS"]);

		if (intval($arFields["ENTITY_ID"]) > 0)
		{
			if (
				is_array($arEventParams)
				&& count($arEventParams) > 0
				&& array_key_exists("ENTITY_NAME", $arEventParams)
				&& strlen($arEventParams["ENTITY_NAME"]) > 0
			)
			{
				if (
					!$bMail
					&& array_key_exists("ENTITY_URL", $arEventParams)
					&& strlen($arEventParams["ENTITY_URL"]) > 0
				)
				{
					$arSocNetAllowedSubscribeEntityTypesDesc = CSocNetAllowed::GetAllowedEntityTypesDesc();
					$arEntity["FORMATTED"]["TYPE_NAME"] = $arSocNetAllowedSubscribeEntityTypesDesc[$arFields["ENTITY_TYPE"]]["TITLE_ENTITY"];
					$arEntity["FORMATTED"]["URL"] = $arEventParams["ENTITY_URL"];
					$arEntity["FORMATTED"]["NAME"] = $arEventParams["ENTITY_NAME"];
				}
				elseif(!$bMail)
					$arEntity["FORMATTED"]["NAME"] = $arEventParams["ENTITY_NAME"];
				else
				{
					$arEntity["FORMATTED"] = $arEventParams["ENTITY_NAME"];
					$arEntity["TYPE_MAIL"] = GetMessage("WEBDAV_SOCNET_LOG_ENTITY_MAIL");
				}
			}
		}

		return $arEntity;
	}

	static function FormatEvent_Files($arFields, $arParams, $bMail = false)
	{
		if (!CModule::IncludeModule("socialnetwork"))
			return null;

		$arResult = array(
			"EVENT" => $arFields,
			"CREATED_BY" => CSocNetLogTools::FormatEvent_GetCreatedBy($arFields, $arParams, $bMail),
			"ENTITY" => self::GetEntity_Files($arFields, $bMail),
			"URL" => ""
		);

		if (!$bMail)
		{
			$arResult["AVATAR_SRC"] = CSocNetLogTools::FormatEvent_CreateAvatar($arFields, $arParams);
			switch ($arFields["CREATED_BY_PERSONAL_GENDER"])
			{
				case "M":
					$suffix = "_M";
					break;
				case "F":
					$suffix = "_F";
					break;
				default:
					$suffix = "";
			}
			$title_tmp_24 = GetMessage("WEBDAV_SONET_EVENT_TITLE_FILE_24".$suffix);
		}

		$title = "";
		if (strlen($arFields["TITLE"]) > 0)
		{

			if (!$bMail && strlen($arFields["URL"]) > 0)
				$title_tmp = '<a href="'.$arFields["URL"].'">'.$arFields["TITLE"].'</a>';
			else
				$title_tmp = $arFields["TITLE"];

			$title = str_replace(
				array("#TITLE#", "#ENTITY#"),
				array($title_tmp, ($bMail ? $arResult["ENTITY"]["FORMATTED"] : $arResult["ENTITY"]["FORMATTED"]["NAME"])),
				($bMail ? GetMessage("WEBDAV_SOCNET_LOG_FILES_TITLE_MAIL") : GetMessage("WEBDAV_SOCNET_LOG_FILES_TITLE"))
			);
		}
		else
			$title_tmp = "";

		$url = false;

		if (
			strlen($arFields["URL"]) > 0
			&& strlen($arFields["SITE_ID"]) > 0
		)
		{
			$rsSites = CSite::GetByID($arFields["SITE_ID"]);
			$arSite = $rsSites->Fetch();

			if (strlen($arSite["SERVER_NAME"]) > 0)
				$server_name = $arSite["SERVER_NAME"];
			else
				$server_name = COption::GetOptionString("main", "server_name", $GLOBALS["SERVER_NAME"]);

			$protocol = (CMain::IsHTTPS() ? "https" : "http");
			$url = $protocol."://".$server_name.$arFields["URL"];
		}

		if ($arParams["MOBILE"] == "Y")
			$arResult["EVENT_FORMATTED"] = array(
				"TITLE_24" => GetMessage("WEBDAV_SONET_EVENT_TITLE_FILE_24_MOBILE"),
				"MESSAGE" => $arFields["MESSAGE"]
			);
		else
			$arResult["EVENT_FORMATTED"] = array(
				"TITLE" => $title,
				"MESSAGE_TITLE_24" => $title_tmp_24,
				"MESSAGE" => ($bMail ? $arFields["TEXT_MESSAGE"] : $arFields["MESSAGE"])
			);

		if (!$bMail)
			$arResult["EVENT_FORMATTED"]["IS_MESSAGE_SHORT"] = true;

		if (strlen($url) > 0)
			$arResult["EVENT_FORMATTED"]["URL"] = $url;

		$arResult["HAS_COMMENTS"] = "N";
		if (
			intval($arFields["SOURCE_ID"]) > 0 
			&& array_key_exists("PARAMS", $arFields) 
			&& strlen($arFields["PARAMS"]) > 0
		)
		{
			$arFieldsParams = explode("&", $arFields["PARAMS"]);
			if (is_array($arFieldsParams) && count($arFieldsParams) > 0)
			{
				foreach ($arFieldsParams as $tmp)
				{
					list($key, $value) = explode("=", $tmp);
					if ($key == "forum_id")
					{
						$arResult["HAS_COMMENTS"] = "Y";
						break;
					}
				}
			}
		}

		if (!$bMail)
		{
			$dbRight = CSocNetLogRights::GetList(array(), array("LOG_ID" => $arFields["ID"]));
			while ($arRight = $dbRight->Fetch())
				$arRights[] = $arRight["GROUP_CODE"];

			$arResult["EVENT_FORMATTED"]["DESTINATION"] = CSocNetLogTools::FormatDestinationFromRights($arRights, array_merge($arParams, array("CREATED_BY" => $arFields["USER_ID"])), $iMoreCount);
			if (intval($iMoreCount) > 0)
				$arResult["EVENT_FORMATTED"]["DESTINATION_MORE"] = $iMoreCount;
		}

		return $arResult;
	}

	static function FormatComment_Files($arFields, $arParams, $bMail = false, $arLog = array())
	{
		$arResult = array(
			"EVENT_FORMATTED"	=> array(),
		);

		if (!CModule::IncludeModule("socialnetwork"))
			return $arResult;

		if ($bMail)
		{
			$arResult["CREATED_BY"] = CSocNetLogTools::FormatEvent_GetCreatedBy($arFields, $arParams, $bMail);
			$arResult["ENTITY"] = self::GetEntity_Files($arLog, $bMail);
		}
		elseif($arParams["USE_COMMENT"] != "Y")
		{
			$arLog["ENTITY_ID"] = $arFields["ENTITY_ID"];
			$arLog["ENTITY_TYPE"] = $arFields["ENTITY_TYPE"];
			$arResult["ENTITY"] = self::GetEntity_Files($arLog, false);
		}

		if (
			!$bMail
			&& array_key_exists("URL", $arLog)
			&& strlen($arLog["URL"]) > 0
		)
			$file_tmp = '<a href="'.$arLog["URL"].'">'.$arLog["TITLE"].'</a>';
		else
			$file_tmp = $arLog["TITLE"];

		$title_tmp = ($bMail ? GetMessage("WEBDAV_SOCNET_LOG_FILES_COMMENT_TITLE_MAIL") : GetMessage("WEBDAV_SOCNET_LOG_FILES_COMMENT_TITLE"));

		$title = str_replace(
			array("#TITLE#", "#ENTITY#", "#CREATED_BY#"),
			array($file_tmp, $arResult["ENTITY"]["FORMATTED"], ($bMail ? $arResult["CREATED_BY"]["FORMATTED"] : "")),
			$title_tmp
		);

		$arResult["EVENT_FORMATTED"] = array(
			"TITLE" => ($bMail || $arParams["USE_COMMENT"] != "Y" ? $title : ""),
			"MESSAGE" => ($bMail ? $arFields["TEXT_MESSAGE"] : $arFields["MESSAGE"])
		);

		if ($bMail)
		{
			$url = CSocNetLogTools::FormatEvent_GetURL($arLog);
			if (strlen($url) > 0)
				$arResult["EVENT_FORMATTED"]["URL"] = $url;
		}

		return $arResult;
	}

	static function AddComment_Files($arFields)
	{
		if (!CModule::IncludeModule("forum"))
			return false;

		if (!CModule::IncludeModule("iblock"))
			return false;

		if (!CModule::IncludeModule("socialnetwork"))
			return false;

		$dbResult = CSocNetLog::GetList(
			array("ID" => "DESC"),
			array("TMP_ID" => $arFields["LOG_ID"]),
			false,
			false,
			array("ID", "SOURCE_ID", "PARAMS")
		);

		$bFound = false;
		$FORUM_ID = 0;
		if ($arLog = $dbResult->Fetch())
		{
			if (intval($arLog["SOURCE_ID"]) > 0)
			{
				$arFilter = array(
					"ID" => $arLog["SOURCE_ID"],
					"SHOW_HISTORY" => "Y"
				);
				$arSelectedFields = array("IBLOCK_ID", "ID", "CREATED_BY", "NAME", "PROPERTY_FORUM_TOPIC_ID", "PROPERTY_FORUM_MESSAGE_CNT");
				$db_res = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelectedFields);
				if ($db_res && $res = $db_res->GetNext())
				{
					$arElement = $res;

					// get forum_id
					if (preg_match("#forum_id=(\d+)#", $arLog['PARAMS'], $matches) > 0)
						$FORUM_ID = intval($matches[1]);

					if (intval($FORUM_ID) > 0)
					{
						CSocNetLogTools::AddComment_Review_CheckIBlock($arElement);

						$dbMessage = CForumMessage::GetList(
							array(), 
							array("PARAM2" => $arElement["ID"])
						);

						if (!$arMessage = $dbMessage->Fetch())
						{
							// Add Topic
							$TOPIC_ID = CSocNetLogTools::AddComment_Review_CreateRoot($arElement, $FORUM_ID);
							$bNewTopic = true;
						}
						else
							$TOPIC_ID = $arMessage["TOPIC_ID"];

						if(intval($TOPIC_ID) > 0)
						{
							// Add comment
							$messageID = false;

							$bError = false;
							if (CForumMessage::CanUserAddMessage($TOPIC_ID, $GLOBALS["USER"]->GetUserGroupArray(), $GLOBALS["USER"]->GetID(), false))
							{
								$bSHOW_NAME = true;
								$res = CForumUser::GetByUSER_ID($GLOBALS["USER"]->GetID());
								if ($res)
									$bSHOW_NAME = ($res["SHOW_NAME"]=="Y");

								if ($bSHOW_NAME)
									$AUTHOR_NAME = $GLOBALS["USER"]->GetFormattedName(false);

								if (strlen(Trim($AUTHOR_NAME))<=0)
									$AUTHOR_NAME = $GLOBALS["USER"]->GetLogin();

								if (strlen($AUTHOR_NAME)<=0)
									$bError = true;
							}

							if (!$bError)
							{
								if ($bNewTopic)
								{
									$arFieldsMessage = Array(
										"POST_MESSAGE" => "New document",
										"USE_SMILES" => "Y",
										"APPROVED" => "Y",
										"PARAM1" => "IB",
										"PARAM2" => $arElement["ID"],
										"AUTHOR_NAME" => $AUTHOR_NAME,
										"AUTHOR_ID" => IntVal($GLOBALS["USER"]->GetParam("USER_ID")),
										"FORUM_ID" => $FORUM_ID,
										"TOPIC_ID" => $TOPIC_ID,
										"NEW_TOPIC" => "Y",
										"GUEST_ID" => $_SESSION["SESS_GUEST_ID"],
										"ADD_TO_LOG" => "N"
									);

									$AUTHOR_IP = ForumGetRealIP();
									$AUTHOR_IP_tmp = $AUTHOR_IP;
									$AUTHOR_REAL_IP = $_SERVER['REMOTE_ADDR'];
									if (COption::GetOptionString("forum", "FORUM_GETHOSTBYADDR", "N") == "Y")
									{
										$AUTHOR_IP = @gethostbyaddr($AUTHOR_IP);
										if ($AUTHOR_IP_tmp==$AUTHOR_REAL_IP)
											$AUTHOR_REAL_IP = $AUTHOR_IP;
										else
											$AUTHOR_REAL_IP = @gethostbyaddr($AUTHOR_REAL_IP);
									}

									$arFieldsMessage["AUTHOR_IP"] = ($AUTHOR_IP!==False) ? $AUTHOR_IP : "<no address>";
									$arFieldsMessage["AUTHOR_REAL_IP"] = ($AUTHOR_REAL_IP!==False) ? $AUTHOR_REAL_IP : "<no address>";

									$messageID = CForumMessage::Add($arFieldsMessage, false);
								}
								$arFieldsMessage = Array(
									"POST_MESSAGE" => $arFields["TEXT_MESSAGE"],
									"USE_SMILES" => "Y",
									"APPROVED" => "Y",
									"PARAM2" => $arElement["ID"],
									"AUTHOR_NAME" => $AUTHOR_NAME,
									"AUTHOR_ID" => IntVal($GLOBALS["USER"]->GetParam("USER_ID")),
									"FORUM_ID" => $FORUM_ID,
									"TOPIC_ID" => $TOPIC_ID,
									"NEW_TOPIC" => "N",
									"GUEST_ID" => $_SESSION["SESS_GUEST_ID"],
									"ADD_TO_LOG" => "N"
								);

								$AUTHOR_IP = ForumGetRealIP();
								$AUTHOR_IP_tmp = $AUTHOR_IP;
								$AUTHOR_REAL_IP = $_SERVER['REMOTE_ADDR'];
								if (COption::GetOptionString("forum", "FORUM_GETHOSTBYADDR", "N") == "Y")
								{
									$AUTHOR_IP = @gethostbyaddr($AUTHOR_IP);
									if ($AUTHOR_IP_tmp==$AUTHOR_REAL_IP)
										$AUTHOR_REAL_IP = $AUTHOR_IP;
									else
										$AUTHOR_REAL_IP = @gethostbyaddr($AUTHOR_REAL_IP);
								}

								$arFieldsMessage["AUTHOR_IP"] = ($AUTHOR_IP!==False) ? $AUTHOR_IP : "<no address>";
								$arFieldsMessage["AUTHOR_REAL_IP"] = ($AUTHOR_REAL_IP!==False) ? $AUTHOR_REAL_IP : "<no address>";

								$messageID = CForumMessage::Add($arFieldsMessage, false);
								if (intVal($messageID)<=0)
									$bError = true;
								else
								{
									if (CModule::IncludeModule("statistic"))
									{
										$arForum = CForumNew::GetByID($FORUM_ID);
										$F_EVENT1 = $arForum["EVENT1"];
										$F_EVENT2 = $arForum["EVENT2"];
										$F_EVENT3 = $arForum["EVENT3"];
										if (strlen($F_EVENT3)<=0)
										{
											$arForumSite_tmp = CForumNew::GetSites($FORUM_ID);
											$F_EVENT3 = CForumNew::PreparePath2Message($arForumSite_tmp[SITE_ID], array("FORUM_ID"=>$FORUM_ID, "TOPIC_ID"=>$TOPIC_ID, "MESSAGE_ID"=>$messageID));
										}
										CStatistic::Set_Event($F_EVENT1, $F_EVENT2, $F_EVENT3);
									}
									CForumMessage::SendMailMessage($messageID, array(), false, "NEW_FORUM_MESSAGE");
									CSocNetLogTools::AddComment_Review_UpdateElement($arElement, $TOPIC_ID, $bNewTopic);
								}
							}
						}
					}
				}
			}
		}

		if (intval($messageID) <= 0)
			$strError = GetMessage("SONET_ADD_COMMENT_SOURCE_ERROR");

		return array(
			"SOURCE_ID"	=> $messageID,
			"RATING_TYPE_ID" => "FORUM_POST",
			"RATING_ENTITY_ID" => $messageID,
			"ERROR"		=> $strError,
			"NOTES"		=> ""
		);
	}

	static function GetIBlockByID($ID)
	{
		if (!CModule::IncludeModule("iblock"))
			return false;

		$ID = IntVal($ID);

		$dbIBlock = CIBlock::GetByID($ID);
		$arIBlock = $dbIBlock->GetNext();
		if ($arIBlock)
		{
			$arIBlock["NAME_FORMATTED"] = $arIBlock["NAME"];
			return $arIBlock;
		}
		else
			return false;
	}

	static function ShowIBlockByID($arEntityDesc, $strEntityURL, $arParams)
	{
		$url = str_replace("#SITE_DIR#", SITE_DIR, $arEntityDesc["LIST_PAGE_URL"]);
		if (strpos($url, "/") === 0)
			$url = "/".ltrim($url, "/");

		$name = "<a href=\"".$url."\">".$arEntityDesc["NAME"]."</a>";
		return $name;
	}	
}