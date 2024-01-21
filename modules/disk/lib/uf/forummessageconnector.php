<?php
namespace Bitrix\Disk\Uf;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\CommentAux;
use Bitrix\Disk\Configuration;

Loc::loadMessages(__FILE__);

final class ForumMessageConnector extends StubConnector
{
	protected static $messages = array();
	protected static $topics = array();

	private $canRead = null;

	public function getDataToShow()
	{
		return $this->getDataToShowForUser($this->getUser()->getId());
	}

	public function getDataToShowForUser(int $userId)
	{
		$return = null;
		if(($res = $this->getDataToCheck($this->entityId)) && !empty($res))
		{
			list($message, $topic, $forum) = $res;
			$return = array(
				'TITLE' => Loc::getMessage("DISK_UF_FORUM_MESSAGE_CONNECTOR_MESSAGE01"),
				'DETAIL_URL' => \CForumNew::preparePath2Message(
					$forum["PATH2FORUM_MESSAGE"],
					array(
						"FORUM_ID" => $message["FORUM_ID"],
						"TOPIC_ID" => $message["TOPIC_ID"],
						"MESSAGE_ID" => $message["ID"],
						"SOCNET_GROUP_ID" => $topic["SOCNET_GROUP_ID"],
						"OWNER_ID" => $topic["OWNER_ID"],
						"PARAM1" => $message["PARAM1"],
						"PARAM2" => $message["PARAM2"])),
				'DESCRIPTION' => ($topic['TITLE'] == $topic['XML_ID'] ? '' : $topic["TITLE"]),
				'MEMBERS' => array(),
				'DUPLICATE_TO_SOCNET' => "N"
			);
			if (
				($topic["SOCNET_GROUP_ID"] > 0 || $topic["OWNER_ID"] > 0)
				&& $message["NEW_TOPIC"] == "Y"
				&& Loader::includeModule("socialnetwork")
				&& (
					$res = \CSocNetLog::getList(
						array("ID" => "DESC"),
						array("SOURCE_ID" => $message["ID"], "EVENT_ID" => "forum"),
						false,
						false,
						array("ID", "URL", "ENTITY_TYPE", "ENTITY_ID", "LOG_ID")
					)->fetch()
				)
				&&
				$res
			)
			{
				$return["TITLE"] = ($topic["SOCNET_GROUP_ID"] > 0 ?
					Loc::getMessage("DISK_UF_FORUM_MESSAGE_CONNECTOR_MESSAGE08") :
					Loc::getMessage("DISK_UF_FORUM_MESSAGE_CONNECTOR_MESSAGE09"));
				$return["DETAIL_URL"] = (empty($res["URL"]) ?
					\CComponentEngine::makePathFromTemplate(
						\COption::getOptionString("socialnetwork", "log_entry_page", false, SITE_ID),
						array("log_id" => $res["ID"])
					) : $res["URL"]);
				if (mb_strpos($return["DETAIL_URL"], "#GROUPS_PATH#") !== false)
				{
					$tmp = \CSocNetLogTools::processPath(array("URL" => $return["DETAIL_URL"]), $userId);
					$return["DETAIL_URL"] = $tmp["URLS"]["URL"];
				}
				$return['DUPLICATE_TO_SOCNET'] = "Y";
				$return["ENTITY_TYPE"] = $res["ENTITY_TYPE"];
				$return["ENTITY_ID"] = $res["ENTITY_ID"];
				$return["EVENT_ID"] = "forum";
				$return["SOURCE_ID"] = $message["ID"];
				$return["LOG_ID"] = $res["LOG_ID"];
				$return["MODULE_ID"] = "forum";
			}
			else if(!empty($topic["XML_ID"]) || $topic["SOCNET_GROUP_ID"] > 0 || $topic["OWNER_ID"] > 0)
			{
				$entityId = mb_substr($topic["XML_ID"], (mb_strrpos($topic["XML_ID"], "_") + 1));
				$entityType = mb_substr($topic["XML_ID"], 0, mb_strrpos($topic["XML_ID"], "_"));
				$return["TITLE"] = Loc::getMessage("DISK_UF_FORUM_MESSAGE_CONNECTOR_MESSAGE02");
				if ((
						in_array($entityType, array("FORUM", "TASK", "EVENT", "IBLOCK", "TIMEMAN_ENTRY", "TIMEMAN", "TIMEMAN_REPORT"))
						|| $topic["SOCNET_GROUP_ID"] > 0
						|| $topic["OWNER_ID"] > 0
					)
					&&
					Loader::includeModule("socialnetwork")
					&&
					(
						$res = \CSocNetLogComments::getList(
							array("ID" => "DESC"),
							array("SOURCE_ID" => $message["ID"], "EVENT_ID" => array(
								"calendar_comment",
								"commondocs_comment",
								"files_comment",
								"forum",
								"news_comment",
								"photo_comment",
								"tasks_comment",
								"wiki_comment",
								"report_comment",
								"timeman_entry_comment"
							)),
							false,
							false,
							array("ID", "SOURCE_ID", "LOG_ID", "EVENT_ID", "ENTITY_TYPE", "ENTITY_ID", "URL", "MODULE_ID")
						)->fetch()
					)
					&&
					$res
				)
				{
					$return["DETAIL_URL"] = (empty($res["URL"]) ?
						\CComponentEngine::makePathFromTemplate(
							\COption::getOptionString("socialnetwork", "log_entry_page", false, SITE_ID),
							array("log_id" => $res["LOG_ID"])
						)."?commentId=".$res["ID"]
						: $res["URL"]);
					if (mb_strpos($return["DETAIL_URL"], "#GROUPS_PATH#") !== false)
					{
						$tmp = \CSocNetLogTools::processPath(array("URL" => $return["DETAIL_URL"]), $userId);
						$return["DETAIL_URL"] = $tmp["URLS"]["URL"];
					}

					switch ($res["EVENT_ID"])
					{
						case "tasks_comment":
							$return["TITLE"] = Loc::getMessage("DISK_UF_FORUM_MESSAGE_CONNECTOR_MESSAGE03");
							if(Loader::includeModule("tasks"))
							{
								$connector = new \Bitrix\Tasks\Integration\Disk\Connector\Task($entityId);
								$subData = $connector->tryToGetDataToShowForUser($userId);
								if($subData["MEMBERS"])
								{
									$return["MEMBERS"] = $subData["MEMBERS"];
								}
							}
							break;
						case "calendar_comment":
							$return["TITLE"] = Loc::getMessage("DISK_UF_FORUM_MESSAGE_CONNECTOR_MESSAGE04");
							$return["DETAIL_URL"] = null;
							break;
						case "commondocs_comment":
							$return["TITLE"] = Loc::getMessage("DISK_UF_FORUM_MESSAGE_CONNECTOR_MESSAGE05");
							break;
						case "crm_activity_add_comment":
							$return["TITLE"] = Loc::getMessage("DISK_UF_FORUM_MESSAGE_CONNECTOR_MESSAGE06");
							break;
						case "files_comment":
							$return["TITLE"] = Loc::getMessage("DISK_UF_FORUM_MESSAGE_CONNECTOR_MESSAGE07");
							break;
						case "forum":
							$return["TITLE"] = ($topic["SOCNET_GROUP_ID"] > 0 ?
								Loc::getMessage("DISK_UF_FORUM_MESSAGE_CONNECTOR_MESSAGE08") :
								Loc::getMessage("DISK_UF_FORUM_MESSAGE_CONNECTOR_MESSAGE09"));
							break;
						case "news_comment":
							$return["TITLE"] = Loc::getMessage("DISK_UF_FORUM_MESSAGE_CONNECTOR_MESSAGE10");
							break;
						case "photo_comment":
							$return["TITLE"] = Loc::getMessage("DISK_UF_FORUM_MESSAGE_CONNECTOR_MESSAGE11");
							break;
						case "wiki_comment":
							$return["TITLE"] = Loc::getMessage("DISK_UF_FORUM_MESSAGE_CONNECTOR_MESSAGE12");
							break;
						case "report_comment":
							$return["TITLE"] = Loc::getMessage("DISK_UF_FORUM_MESSAGE_CONNECTOR_MESSAGE13");
							break;
						case "timeman_entry_comment":
							$return["TITLE"] = Loc::getMessage("DISK_UF_FORUM_MESSAGE_CONNECTOR_MESSAGE14");
							break;
					}
					$return['DUPLICATE_TO_SOCNET'] = "Y";
					$return["ENTITY_TYPE"] = $res["ENTITY_TYPE"];
					$return["ENTITY_ID"] = $res["ENTITY_ID"];
					$return["EVENT_ID"] = $res["EVENT_ID"];
					$return["SOURCE_ID"] = $res["SOURCE_ID"];
					$return["LOG_ID"] = $res["LOG_ID"];
					$return["MODULE_ID"] = $res["MODULE_ID"];
				}
				else
				{
					$return["DETAIL_URL"] = \CForumNew::preparePath2Message(
						$forum["PATH2FORUM_MESSAGE"],
						array(
							"FORUM_ID" => $message["FORUM_ID"],
							"TOPIC_ID" => $message["TOPIC_ID"],
							"MESSAGE_ID" => $message["ID"],
							"SOCNET_GROUP_ID" => $topic["SOCNET_GROUP_ID"],
							"OWNER_ID" => $topic["OWNER_ID"],
							"PARAM1" => $message["PARAM1"],
							"PARAM2" => $entityId));
					switch ($entityType)
					{
						case "EVENT":
							$return["TITLE"] = Loc::getMessage("DISK_UF_FORUM_MESSAGE_CONNECTOR_MESSAGE04");
							break;
						case "TASK":
							$return["TITLE"] = Loc::getMessage("DISK_UF_FORUM_MESSAGE_CONNECTOR_MESSAGE03");
							break;
						case "IBLOCK":
							$return["TITLE"] = Loc::getMessage("DISK_UF_FORUM_MESSAGE_CONNECTOR_MESSAGE15");
							break;
						case "TIMEMAN_ENTRY":
							$return["TITLE"] = Loc::getMessage("DISK_UF_FORUM_MESSAGE_CONNECTOR_MESSAGE14");
							break;
						case "TIMEMAN":
						case "TIMEMAN_REPORT":
							$return["TITLE"] = Loc::getMessage("DISK_UF_FORUM_MESSAGE_CONNECTOR_MESSAGE13");
							break;
						case "MEETING":
							$return["TITLE"] = Loc::getMessage("DISK_UF_FORUM_MESSAGE_CONNECTOR_MESSAGE16");
							break;
					}
				}
			}
		}
		return $return;
	}

	private static function getDataToCheck($messageId)
	{
		$return = false;
		if($messageId > 0 && Loader::includeModule("forum"))
		{
			if(!array_key_exists($messageId, self::$messages))
			{
				$cacheTtl = 2592000;
				$cacheId = 'forum_message_' . $messageId;
				$cachePath = \CComponentEngine::makeComponentPath("forum.topic.read");
				$cache = new \CPHPCache;
				$messages = $topics = array();
				if($cache->initCache($cacheTtl, $cacheId, $cachePath))
				{
					list($messages, $topics) = $cache->getVars();
				}
				else
				{
					$dbForumMessage = \CForumMessage::getList(array(), array("ID" => $messageId));
					if ($messageData = $dbForumMessage->fetch())
					{
						$messages["M" . $messageData["ID"]] = array_intersect_key($messageData, array(
							"ID" => "",
							"TOPIC_ID" => "",
							"FORUM_ID" => "",
							"USER_ID" => "",
							"NEW_TOPIC" => "",
							"APPROVED" => "",
							"PARAM1" => "",
							"PARAM2" => ""
						));
						$dbForumTopic = \CForumTopic::getList(array(), array("ID" => $messageData["TOPIC_ID"]));
						if ($topicData = $dbForumTopic->fetch())
						{
							$topics["T" . $topicData["ID"]] = array(
								"TITLE" => $topicData["TITLE"],
								"USER_ID" => $topicData["USER_START_ID"],
								"XML_ID" => $topicData["XML_ID"],
								"SOCNET_GROUP_ID" => $topicData["SOCNET_GROUP_ID"],
								"OWNER_ID" => $topicData["OWNER_ID"]
							);
						}
					}
					if(!empty($messages))
					{
						$cache->startDataCache();
						$res = reset($topics);

						\CForumCacheManager::setTag($cachePath, "forum_topic_" . $res['ID']);
						$cache->endDataCache(array($messages, $topics));
					}
				}
				if (!empty($messages) && is_array($messages))
					self::$messages += $messages;
				if (!empty($topics) && is_array($topics))
					self::$topics += $topics;
			}
			if(array_key_exists("M" . $messageId, self::$messages))
			{
				$return = array(
					self::$messages["M" . $messageId],
					self::$topics["T" . self::$messages["M" . $messageId]["TOPIC_ID"]],
					\CForumNew::getByIDEx(self::$messages["M" . $messageId]["FORUM_ID"], SITE_ID)
				);
			}
		}
		return $return;
	}

	public function canAccess($userId, $codes)
	{
		$codes = (is_array($codes) ? $codes : array($codes));
		$isEmpty = true;
		foreach($codes as $code)
		{
			if(trim($code) <> '')
			{
				$isEmpty = false;
				break;
			}
		}
		if ($isEmpty)
		{
			$canAccess = false;
		}
		else if ($this->getUser()->getId() == (int)$userId)
		{
			$canAccess = $this->getUser()->canAccess($codes);
		}
		else if (in_array('G2', $codes))
		{
			$canAccess = true;
		}
		else
		{
			$canAccess = array_intersect($codes, \CAccess::getUserCodesArray($userId));
		}

		return $canAccess;
	}

	/**
	 * @param $userId
	 * @return bool
	 */
	public function canRead($userId)
	{
		if($this->canRead !== null)
		{
			return $this->canRead;
		}

		if(($res = $this->getDataToCheck($this->entityId)) && !empty($res))
		{
			list($message, $topic) = $res;

			$entityId = null;
			$entityType = null;
			if(!empty($topic["XML_ID"]))
			{
				$entityId = mb_substr($topic["XML_ID"], (mb_strrpos($topic["XML_ID"], "_") + 1));
				$entityType = mb_substr($topic["XML_ID"], 0, mb_strpos($topic["XML_ID"], "_"));

				if (mb_strpos($topic["XML_ID"], "EVENT_") !== false)
				{
					$XML_ID = explode('_', $topic["XML_ID"]);
					if (is_array($XML_ID) && count($XML_ID) > 1)
					{
						$entityType = $XML_ID[0];
						$entityId = $XML_ID[1];
					}
				}
			}

			switch($entityType)
			{
				case "TASK":
					if(Loader::includeModule("tasks"))
					{
						$connector = new \Bitrix\Tasks\Integration\Disk\Connector\Task($entityId);
						$this->canRead = $connector->canRead($userId);

						return $this->canRead;
					}
					break;
				case "EVENT":
					if(Loader::includeModule("calendar"))
					{
						$connector = new CalendarEventConnector($entityId);
						$connector->setXmlId($topic["XML_ID"] ?? '');
						$this->canRead = $connector->canRead($userId);

						return $this->canRead;
					}
					break;
				case "IBLOCK":
					if ((int)$topic["USER_ID"] > 0 && Loader::includeModule("socialnetwork"))
					{
						$codes = array();
						if (($res = \CSocNetLog::getList(
							array(),
							array("SOURCE_ID" => $entityId, "EVENT_ID" => array("photo_photo", "news", "wiki")),
							false,
							false,
							array("ID")
						)->fetch()) && $res)
						{
							$db_res = \CSocNetLogRights::getList(array(), array("LOG_ID" => $res["ID"]));
							while($res = $db_res->fetch())
								$codes[] = $res["GROUP_CODE"];
						}
						$this->canRead = $this->canAccess($userId, $codes);

						return $this->canRead;
					}
					$this->canRead = true;

					return $this->canRead;
				case "MEETING":
				case "MEETING_ITEM":
					$this->canRead = ((int)$message["FORUM_ID"] == (int)\COption::getOptionInt('meeting', 'comments_forum_id', 0, SITE_ID));

					return $this->canRead;
				case "TIMEMAN_ENTRY":
					if(Loader::includeModule("timeman"))
					{
						$dbEntry = \CTimeManEntry::getList(
							array(),
							array(
								"ID" => $entityId
							),
							false,
							false,
							array("ID", "USER_ID")
						);

						if ($arEntry = $dbEntry->fetch())
						{
							if ($arEntry["USER_ID"] == $userId)
							{
								$this->canRead = true;

								return $this->canRead;
							}
							else
							{
								$arManagers = \CTimeMan::getUserManagers($arEntry["USER_ID"]);
								$this->canRead = in_array($userId, $arManagers);

								return $this->canRead;
							}
						}
					}
					$this->canRead = false;

					return $this->canRead;
				case "TIMEMAN_REPORT":
					if(Loader::includeModule("timeman"))
					{
						$dbReport = \CTimeManReportFull::getList(
							array(),
							array(
								"ID" => $entityId
							),
							array("ID", "USER_ID")
						);

						if ($arReport = $dbReport->fetch())
						{
							if ($arReport["USER_ID"] == $userId)
							{
								$this->canRead = true;

								return $this->canRead;
							}
							else
							{
								$arManagers = \CTimeMan::getUserManagers($arReport["USER_ID"]);
								$this->canRead = in_array($userId, $arManagers);

								return $this->canRead;
							}
						}
					}
					$this->canRead = false;

					return $this->canRead;
				case "WF":
					$this->canRead = false;
					if (Loader::includeModule("bizproc"))
					{
						if($this->getUser()->isAdmin() || $this->getUser()->canDoOperation('bitrix24_config'))
						{
							return true;
						}

						$currentUserId = (int) $this->getUser()->getId();
						$participants = \CBPTaskService::getWorkflowParticipants($entityId);
						if (in_array($currentUserId, $participants))
						{
							$this->canRead = true;
						}
						else
						{
							$state = \CBPStateService::getWorkflowStateInfo($entityId);
							if ($state && $currentUserId === (int) $state['STARTED_BY'])
								$this->canRead = true;
						}
						if (!$this->canRead && Loader::includeModule("iblock"))
						{
							$documentId = \CBPStateService::GetStateDocumentId($entityId);
							$elementQuery = \CIBlockElement::getList(array(), array("ID" => $documentId[2]),
								false, false, array("IBLOCK_ID"));
							$element = $elementQuery->fetch();
							if (!$element['IBLOCK_ID'])
							{
								$this->canRead = false;
							}

							$this->canRead = \CIBlockElementRights::userHasRightTo($element["IBLOCK_ID"],
								$documentId[2], "element_read");
						}
					}
					return $this->canRead;
			}
			if ((!empty($topic["SOCNET_GROUP_ID"]) || !empty($topic["OWNER_ID"])) && Loader::includeModule("socialnetwork"))
			{
				if (!empty($topic["SOCNET_GROUP_ID"]))
				{
					$this->canRead = \CSocNetFeatures::isActiveFeature(SONET_ENTITY_GROUP, $topic["SOCNET_GROUP_ID"], "forum") &&
						\CSocNetFeaturesPerms::canPerformOperation($userId, SONET_ENTITY_GROUP, $topic["SOCNET_GROUP_ID"], "forum", "view");

					return $this->canRead;
				}
				else
				{
					$this->canRead = \CSocNetFeatures::isActiveFeature(SONET_ENTITY_USER, $topic["OWNER_ID"], "forum") &&
						\CSocNetFeaturesPerms::canPerformOperation($userId, SONET_ENTITY_USER, $topic["OWNER_ID"], "forum", "view");

					return $this->canRead;
				}
			}
			if($message)
			{
				$user = $this->getUser();
				if($user && $userId == $user->getId())
				{
					$userGroups = $user->getUserGroupArray();
				}
				else
				{
					$userGroups = array(2);
				}


				if(\CForumUser::isAdmin($userId, $userGroups))
				{
					$this->canRead = true;

					return $this->canRead;
				}

				$perms = \CForumNew::getUserPermission($message["FORUM_ID"], $userGroups);
				if($perms >= "Y")
				{
					$this->canRead = true;

					return $this->canRead;
				}
				if($perms < "E" || ($perms < "Q" && $message["APPROVED"] != "Y"))
				{
					$this->canRead = false;

					return $this->canRead;
				}

				$forum = \CForumNew::getByID($message["FORUM_ID"]);
				$this->canRead = $forum["ACTIVE"] == "Y";

				return $this->canRead;
			}
		}

		$this->canRead = false;
		return $this->canRead;
	}

	/**
	 * @param $userId
	 * @return bool
	 */
	public function canUpdate($userId)
	{
		return $this->canRead($userId);
	}

	public function addComment($authorId, array $data)
	{
		$return = null;
		if(($res = $this->getDataToShowForUser($authorId)) && !empty($res) &&
		   ($res2 = $this->getDataToCheck($this->entityId)) && !empty($res2))
		{
			list($message, $topic, $forum) = $res2;
			$messageFields = array(
				"POST_MESSAGE" => $data["text"],
				"PARAM2" => $this->entityId,
				"APPROVED" => "Y",
				"PERMISSION_EXTERNAL" => "I",
				"PERMISSION" => "I",
			);
			if ($forum["DEDUPLICATION"] == "Y")
			{
				\CForumNew::update($forum["ID"], array("DEDUPLICATION" => "N"), false);
			}
			if(!empty($data['fileId']))
			{
				$messageFields['UF_FORUM_MESSAGE_DOC'] = array($data['fileId']);
				$GLOBALS["UF_FORUM_MESSAGE_DOC"] = array($data['fileId']);
			}
			elseif(!empty($data['versionId']))
			{
				$messageFields['UF_FORUM_MESSAGE_VER'] = $data['versionId'];
				$GLOBALS["UF_FORUM_MESSAGE_VER"] = $data['versionId'];
			}

			$comId = ForumAddMessage("REPLY", $message["FORUM_ID"], $message["TOPIC_ID"], 0, $messageFields,
				$strErrorMessage,
				$strOKMessage);

			if ($res['DUPLICATE_TO_SOCNET'] == "Y" && $comId > 0 && Loader::includeModule("socialnetwork"))
			{
				if ($res['DUPLICATE_TO_SOCNET'] == "Y")
				{
					$arFieldsForSocnet = array(
						"USER_ID" => $authorId,
						'=LOG_DATE' => $GLOBALS['DB']->currentTimeFunction(),
						"ENTITY_TYPE" => $res["ENTITY_TYPE"],
						"ENTITY_ID" => $res["ENTITY_ID"],
						"EVENT_ID" => $res["EVENT_ID"],
						"MESSAGE" => $data["text"],
						"TEXT_MESSAGE" => $data["text"],
						"URL" => $res["DETAIL_URL"],
						"MODULE_ID" => $res["MODULE_ID"],
						"SOURCE_ID" => $comId,
						"LOG_ID" => $res["LOG_ID"],
						"RATING_TYPE_ID" => "FORUM_POST",
						"RATING_ENTITY_ID" => $comId);
					if(!empty($data['fileId']))
					{
						$arFieldsForSocnet['UF_SONET_COM_DOC'] = array($data['fileId']);
						$GLOBALS["UF_SONET_COM_DOC"] = array($data['fileId']);
					}
					elseif(!empty($data['versionId']))
					{
						$arFieldsForSocnet['UF_SONET_COM_VER'] = $data['versionId'];
						$GLOBALS["UF_SONET_COM_VER"] = $data['versionId'];
					}
					\CSocNetLogComments::add($arFieldsForSocnet, false, false, false);
				}
			}

			if ($comId > 0 && Loader::includeModule("pull") && \CPullOptions::getNginxStatus() && $res["DETAIL_URL"] !== null)
			{
				$provider = CommentAux\Base::init(CommentAux\FileVersion::getType(), array(
					'liveParamList' => array(
						'userId' => $authorId,
						'userGender' => (isset($data['authorGender']) ? $data['authorGender'] : ''),
						'isEnabledKeepVersion' => Configuration::isEnabledKeepVersion()
					)
				));

				\CPullWatch::addToStack("UNICOMMENTS".$topic["XML_ID"],
					array(
						'module_id'	=> "unicomments",
						'command'	=> "comment",
						'params'	=> Array(
							"AUTHOR_ID"		=> $authorId,
							"ID"			=> $comId,
							"POST_ID"		=> $this->entityId,
							"TS"			=> time(),
							"ACTION"		=> "REPLY",
							"URL"			=> array(
								"LINK" => str_replace("MID=".$this->entityId, "MID=".$comId, $res["DETAIL_URL"]),
							),
							"ENTITY_XML_ID"	=> $topic["XML_ID"],
							"APPROVED"		=> "Y",
							"AUX"			=> "fileversion",
							"AUX_LIVE_PARAMS"	=> $provider->getLiveParams()
						),
					)
				);
			}
		}
	}
}
