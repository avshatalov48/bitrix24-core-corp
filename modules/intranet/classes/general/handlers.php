<?php

use Bitrix\Main\Analytics\AnalyticsEvent;
use Bitrix\Main\Loader;
use Bitrix\Main\Event;
use Bitrix\Intranet\Internals\InvitationTable;

IncludeModuleLangFile(__FILE__);

class CIntranetEventHandlers
{
	protected static $userDepartmentCache = array();
	protected static $userActiveCache = false;
	protected static $fieldsCache = [];

	public static function SPRegisterUpdatedItem($arFields)
	{
		if (!CBXFeatures::IsFeatureEnabled('intranet_sharepoint')
			|| CIntranetSharepoint::$bUpdateInProgress || empty($arFields['IBLOCK_ID']))
		{
			return;
		}

		if (!self::$fieldsCache[$arFields['IBLOCK_ID']])
		{
			$dbRes = CIntranetSharepoint::GetByID($arFields['IBLOCK_ID']);
			if ($arRes = $dbRes->Fetch())
			{
				self::$fieldsCache[$arFields['IBLOCK_ID']] = $arRes;
			}
		}

		if (self::$fieldsCache[$arFields['IBLOCK_ID']])
		{
			CIntranetSharepoint::AddToUpdateLog(self::$fieldsCache[$arFields['IBLOCK_ID']]);
		}
	}

	public static function UpdateActivity($arFields)
	{
		if ($arFields['RESULT'] && isset($arFields['ACTIVE']))
		{
			$dbRes = \CIBlockElement::getList(
				array(),
				array(
					'IBLOCK_ID' => (int) \Bitrix\Main\Config\Option::get('intranet', 'iblock_state_history'),
					'PROPERTY_USER' => $arFields['ID'],
				),
				false,
				false,
				array('ID', 'IBLOCK_ID')
			);
			while ($arRes = $dbRes->Fetch())
			{
				CIBlockElement::SetPropertyValues($arRes['ID'], $arRes['IBLOCK_ID'], $arFields['ACTIVE'], 'USER_ACTIVE');
			}

			if ($arFields['ACTIVE'] == 'N')
			{
				$departmentRepository = \Bitrix\Intranet\Service\ServiceContainer::getInstance()
					->departmentRepository();
				$departmentCollection = $departmentRepository->getDepartmentByHeadId((int)$arFields['ID']);
				foreach ($departmentCollection as $department)
				{
					$departmentRepository->unsetHead($department->getId());
				}
			}
		}
	}

	public static function UpdateActivityIBlock(&$arFields)
	{
		global $DB, $USER;

		if ($arFields['RESULT'])
		{
			// absence
			$iblock = COption::GetOptionInt('intranet', 'iblock_absence');
			if (!$iblock)
			{
				$iblock = array();
				$dbRes = CSite::GetList();
				while ($arRes = $dbRes->Fetch())
				{
					if ($ib = COption::GetOptionInt('intranet', 'iblock_absence', false, $arRes['ID']))
						$iblock[] = $ib;
				}
			}
			else
			{
				$iblock = array($iblock);
			}

			if (count($iblock) > 0)
			{
				foreach ($iblock as $ib)
				{
					if ($arFields['IBLOCK_ID'] == $ib)
					{
						static $PROPERTY_USER = 0;

						if ($PROPERTY_USER <= 0)
						{
							$dbRes = CIBlockProperty::GetByID('USER', $arFields['IBLOCK_ID']);
							if ($arRes = $dbRes->Fetch())
								$PROPERTY_USER = $arRes['ID'];
						}

						if ($PROPERTY_USER > 0)
						{
//							$arPropertyValue = array_values($arFields['PROPERTY_VALUES']);
//							$USER_ID = $arPropertyValue[0];
							$USER_ID = $arFields['PROPERTY_VALUES']['USER'] ?? 0;
							$dbRes = CUser::GetByID($USER_ID);
							if ($arUser = $dbRes->Fetch())
								CIBlockElement::SetPropertyValues($arFields['ID'], $arFields['IBLOCK_ID'], $arUser['ACTIVE'], 'USER_ACTIVE');
						}
					}
				}
			}
			// -- absence

			// news
			if ((int)$arFields["IBLOCK_ID"] > 0)
			{
				if (
					(string)CIBlock::GetArrayByID($arFields["IBLOCK_ID"], 'IBLOCK_TYPE_ID') == "news"
					&& CModule::IncludeModule("socialnetwork")
				)
				{
					CSocNetAllowed::GetAllowedEntityTypes();

					$dbLog = CSocNetLog::GetList(array("ID" => "DESC"), array("EVENT_ID" => "news", "SOURCE_ID" => $arFields["ID"]));
					if ($arLog = $dbLog->Fetch())
					{
						if (
							$arFields["ACTIVE"] == "Y"
							&&
							(
								$arFields["PREVIEW_TEXT"] <> ''
								|| $arFields["DETAIL_TEXT"] <> ''
							)
							&&
							(
								!array_key_exists("WF", $arFields)
								|| $arFields["WF"] == "N"
								|| (
									$arFields["WF_STATUS_ID"] == 1
									&& (
										$arFields["WF_PARENT_ELEMENT_ID"] == $arFields["ID"]
										|| empty($arFields["WF_PARENT_ELEMENT_ID"])
									)
								)
							)
						)
						{
							$arSoFields = Array(
								"=LOG_DATE" => (
									$arFields["ACTIVE_FROM"] <> ''
									?
										(
											MakeTimeStamp($arFields["ACTIVE_FROM"], CSite::GetDateFormat("FULL", $site_id)) > time()
											?
												$DB->CharToDateFunction($arFields["ACTIVE_FROM"], "FULL", $site_id)
											:
												$DB->CurrentTimeFunction()
										)
									:
										$DB->CurrentTimeFunction()
								),
								"=LOG_UPDATE" => $DB->CurrentTimeFunction(),
								"TITLE" => $arFields["NAME"],
								"MESSAGE" => (
									$arFields["DETAIL_TEXT"] <> ''
									? ($arFields["DETAIL_TEXT_TYPE"] == "text" ? htmlspecialcharsbx($arFields["DETAIL_TEXT"]) : $arFields["DETAIL_TEXT"])
									: ($arFields["PREVIEW_TEXT_TYPE"] == "text" ? htmlspecialcharsbx($arFields["PREVIEW_TEXT"]) : $arFields["PREVIEW_TEXT"])
								)
							);

							$logID = CSocNetLog::Update($arLog["ID"], $arSoFields);
							if (intval($logID) > 0)
							{
								$rsRights = CSocNetLogRights::GetList(array(), array("LOG_ID" => $logID));
								$arRights = $rsRights->Fetch();
								if (!$arRights)
									CSocNetLogRights::Add($logID, "G2");

								CSocNetLog::SendEvent($logID, "SONET_NEW_EVENT");
							}
						}
						else
						{
							CSocNetLog::Delete($arLog["ID"]);
						}
					}
					else
					{
						if (
							$arFields["ACTIVE"] == "Y"
							&& (
								$arFields["PREVIEW_TEXT"] <> ''
								|| $arFields["DETAIL_TEXT"] <> ''
							)
							&& (
								!array_key_exists("WF", $arFields)
								|| $arFields["WF"] == "N"
								|| ($arFields["WF_STATUS_ID"] == 1
									&& (
										$arFields["WF_PARENT_ELEMENT_ID"] == $arFields["ID"]
										|| empty($arFields["WF_PARENT_ELEMENT_ID"])
									)
								)
							)
						)
						{
							$dbIBlock = CIBlock::GetByID($arFields["IBLOCK_ID"]);
							if($arIBlock = $dbIBlock->Fetch())
							{
								$rsSite = CIBlock::GetSite($arFields["IBLOCK_ID"]);
								if ($arSite = $rsSite->Fetch())
									$site_id = $arSite["SITE_ID"];

								$val = COption::GetOptionString("intranet", "sonet_log_news_iblock", "", $site_id);
								if ($val <> '')
								{
									$arIBCode = unserialize($val, ["allowed_classes" => false]);
									if (!is_array($arIBCode) || count($arIBCode) <= 0)
										$arIBCode = array();
								}
								else
									$arIBCode = array();

								if (in_array($arIBlock["CODE"], $arIBCode))
								{
									$entity_url = str_replace(
										"#SITE_DIR#",
										$arSite["DIR"],
										$arIBlock["LIST_PAGE_URL"]
									);
									if (mb_strpos($entity_url, "/") === 0)
										$entity_url = "/".ltrim($entity_url, "/");

									$url = str_replace(
										array("#SITE_DIR#", "#ID#", "#CODE#"),
										array($arSite["DIR"], $arFields["ID"], $arFields["CODE"]),
										$arIBlock["DETAIL_PAGE_URL"]
									);
									if (mb_strpos($url, "/") === 0)
										$url = "/".ltrim($url, "/");

									$val = COption::GetOptionString("intranet", "sonet_log_news_iblock_forum");
									if ($val <> '')
										$arIBlockForum = unserialize($val, ["allowed_classes" => false]);
									else
										$arIBlockForum = array();

									$strMessage = (
										$arFields["DETAIL_TEXT"] <> ''
										? ($arFields["DETAIL_TEXT_TYPE"] == "text" ? htmlspecialcharsbx($arFields["DETAIL_TEXT"]) : $arFields["DETAIL_TEXT"])
										: ($arFields["PREVIEW_TEXT_TYPE"] == "text" ? htmlspecialcharsbx($arFields["PREVIEW_TEXT"]) : $arFields["PREVIEW_TEXT"])
									);

									$dtFormatSite = (defined("ADMIN_SECTION") && ADMIN_SECTION===true ? SITE_ID : $site_id);
									$dtValue = (
										$arFields["ACTIVE_FROM"] <> ''
										?
											(
												MakeTimeStamp($arFields["ACTIVE_FROM"], CSite::GetDateFormat("FULL", $dtFormatSite)) > time()
												?
													$DB->CharToDateFunction($arFields["ACTIVE_FROM"], "FULL", $dtFormatSite)
												:
													$DB->CurrentTimeFunction()
											)
										:
											$DB->CurrentTimeFunction()
									);

									$arSoFields = Array(
										"SITE_ID" => $site_id,
										"ENTITY_TYPE" => SONET_SUBSCRIBE_ENTITY_NEWS,
										"ENTITY_ID" => $arIBlock["ID"],
										"EVENT_ID" => "news",
										"USER_ID" => $USER->GetID(),
										"=LOG_DATE" => $dtValue,
										"=LOG_UPDATE" => $dtValue,
										"TITLE_TEMPLATE" => GetMessage("INTR_SOCNET_LOG_NEWS_TITLE"),
										"TITLE" => $arFields["NAME"],
										"MESSAGE" => $strMessage,
										"TEXT_MESSAGE" => "",
										"URL"	=> $url,
										"MODULE_ID" => "intranet",
										"CALLBACK_FUNC" => false,
										"TMP_ID" => false,
										"PARAMS" => serialize(array(
											"ENTITY_NAME" => $arIBlock["NAME"],
											"ENTITY_URL" => $entity_url
										)),
										"SOURCE_ID" => $arFields["ID"],
										"ENABLE_COMMENTS" => (array_key_exists($arIBlock["ID"], $arIBlockForum) ? "Y" : "N"),
										"TAG" => (!empty($arFields['TAGS']) ? explode(', ', $arFields['TAGS']) : array())
									);

									$logID = CSocNetLog::Add($arSoFields, false);
									if (intval($logID) > 0)
									{
										CSocNetLog::Update($logID, array("TMP_ID" => $logID));
										CSocNetLogRights::Add($logID, "G2");
										CSocNetLog::SendEvent($logID, "SONET_NEW_EVENT");

										if (Loader::includeModule('forum'))
										{
											$res = CForumTopic::GetList(
												array(),
												array(
													'XML_ID' => 'IBLOCK_'.$arFields['ID']
												)
											);
											if ($topic = $res->fetch())
											{
												$res = CForumMessage::GetList(
													array(),
													array(
														'NEW_TOPIC' => 'N',
														'TOPIC_ID' => $topic['ID']
													)
												);
												$forumCommentsList = array();
												while($message = $res->fetch())
												{
													$forumCommentsList[$message['ID']] = $message;
												}

												if (!empty($forumCommentsList))
												{
													$res = CSocNetLogComments::getList(
														array(),
														array(
															'EVENT_ID' => 'news_comment',
															'SOURCE_ID' => array_unique(array_keys($forumCommentsList))
														),
														false,
														false,
														array('ID', 'SOURCE_ID')
													);
													while($sonetComment = $res->fetch())
													{
														unset($forumCommentsList[$sonetComment['SOURCE_ID']]);
													}

													if (!empty($forumCommentsList))
													{
														$arForum = CForumNew::GetByID($arIBlockForum[$arIBlock["ID"]]);

														$parser = new textParser(LANGUAGE_ID); // second parameter - path to smile!
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
															"UPLOAD" => ($arForum ? $arForum["ALLOW_UPLOAD"] : "N"),
															"NL2BR" => "N",
															"VIDEO" => "N",
															"SMILES" => "N"
														);

														foreach($forumCommentsList as $forumComment)
														{
															self::addNewsComment(array(
																'entityId' => $arIBlock["ID"],
																'message' => $parser->convert($forumComment["POST_MESSAGE"], $arAllow),
																'textMessage' => $parser->convert4mail($forumComment["POST_MESSAGE"]),
																'forumMessageId' => $forumComment['ID'],
																'authorId' => intval($forumComment['AUTHOR_ID']),
																'logId' => $logID,
																'logDate' => $forumComment['POST_DATE']
															));
														}
													}
												}
											}
										}
									}
								}
							}
						}
					}

					if (
						$logID > 0
						&& $arFields["ACTIVE_TO"] <> ''
					)
					{
						$agent = "CIntranetEventHandlers::DeleteLogEntry(".$arFields["ID"].");";
						CAgent::RemoveAgent($agent, "intranet");
						CAgent::AddAgent($agent, "intranet", "N", 0, $arFields["ACTIVE_TO"], "Y", $arFields["ACTIVE_TO"]);
					}
				}
			}
			// --news
		}
	}

	public static function DeleteLogEntry($elementID)
	{
		if (
			intval($elementID) > 0
			&& CModule::IncludeModule("socialnetwork")
		)
		{
			CSocNetAllowed::GetAllowedEntityTypes();

			$rsLog = CSocNetLog::GetList(
				array(),
				array(
					"ENTITY_TYPE" => SONET_SUBSCRIBE_ENTITY_NEWS,
					"EVENT_ID" => "news",
					"SOURCE_ID" => $elementID
				),
				false,
				false,
				array("ID")
			);
			if (
				($arLog = $rsLog->Fetch())
				&& intval($arLog["ID"]) > 0
			)
			{
				CSocNetLog::Delete($arLog["ID"]);
			}

			return "";
		}
	}

/*
	RegisterModuleDependences("iblock", "OnBeforeIBlockSectionUpdate", "intranet", "CIntranetEventHandlers", "OnBeforeIBlockSectionUpdate");
	RegisterModuleDependences("iblock", "OnBeforeIBlockSectionAdd", "intranet", "CIntranetEventHandlers", "OnBeforeIBlockSectionAdd");
*/
	/**
	 * @Deprecated
	 *
	 * Will be deleted after transferring department data to the "humanresources" module
	 *
	 * @param $arParams
	 * @return false|void
	 */
	public static function OnBeforeIBlockSectionAdd($arParams)
	{
		global $APPLICATION;

		if ($arParams['IBLOCK_ID'] == COption::GetOptionInt('intranet', 'iblock_structure', 0))
		{
			if(!array_key_exists("IBLOCK_SECTION_ID", $arParams)
				|| (is_array($arParams['IBLOCK_SECTION_ID']) && count($arParams['IBLOCK_SECTION_ID']) <= 0)
				|| $arParams['IBLOCK_SECTION_ID'] <= 0)
			{
				$dbRes = CIBlockSection::GetList(array(), array('IBLOCK_ID' => $arParams['IBLOCK_ID'], 'SECTION_ID' => 0));
				if ($dbRes->Fetch())
				{
					$APPLICATION->ThrowException(GetMessage('INTR_IBLOCK_TOP_SECTION_WARNING'));
					return false;
				}
			}
		}
	}

	/**
	 * @Deprecated
	 *
	 * Will be deleted after transferring department data to the "humanresources" module
	 *
	 * @param $arParams
	 * @return false|void
	 */
	public static function OnBeforeIBlockSectionUpdate($arParams)
	{
		global $APPLICATION;

		if (
			$arParams['IBLOCK_ID'] == COption::GetOptionInt('intranet', 'iblock_structure', 0)
			&& array_key_exists("IBLOCK_SECTION_ID", $arParams)
			&& (
				(is_array($arParams['IBLOCK_SECTION_ID']) && count($arParams['IBLOCK_SECTION_ID']) <= 0)
				|| $arParams['IBLOCK_SECTION_ID'] <= 0
			)
		)
		{
			$dbRes = CIBlockSection::GetList(array(), array('IBLOCK_ID' => $arParams['IBLOCK_ID'], '!ID' => $arParams['ID'], 'SECTION_ID' => 0));
			if ($dbRes->Fetch())
			{
				$APPLICATION->ThrowException(GetMessage('INTR_IBLOCK_TOP_SECTION_WARNING'));
				return false;
			}
		}
	}

	public static function onAfterForumMessageAdd($ID, $arForumMessage, $arTopicInfo, $arForumInfo, $arFields)
	{
		// add log comment
		if (
			array_key_exists("ADD_TO_LOG", $arFields)
			&& $arFields["ADD_TO_LOG"] == "N"
		)
		{
			return;
		}

		if (
			array_key_exists("NEW_TOPIC", $arFields)
			&& $arFields["NEW_TOPIC"] == "Y"
		)
		{
			return;
		}

		if (
			!array_key_exists("TOPIC_INFO", $arForumMessage)
			|| !is_array($arForumMessage["TOPIC_INFO"])
			|| !array_key_exists("XML_ID", $arForumMessage["TOPIC_INFO"])
			|| empty($arForumMessage["TOPIC_INFO"]["XML_ID"])
			|| mb_strpos($arForumMessage["TOPIC_INFO"]["XML_ID"], "IBLOCK_") !== 0
		)
		{
			return;
		}

		$val = COption::GetOptionString("intranet", "sonet_log_news_iblock_forum");
		$arIBlockForum = ($val <> '' ? unserialize($val, ["allowed_classes" => false]) : array());

		if (
			CModule::IncludeModule("socialnetwork")
			&& CModule::IncludeModule("forum")
			&& in_array($arFields["FORUM_ID"], $arIBlockForum)
			&& array_key_exists("PARAM2", $arFields)
			&& intval($arFields["PARAM2"]) > 0
		)
		{
			CSocNetAllowed::GetAllowedEntityTypes();

			$dbRes = CSocNetLog::GetList(
				array("ID" => "DESC"),
				array(
					"EVENT_ID"	=> "news",
					"SOURCE_ID"	=> $arFields["PARAM2"] // file element id
				),
				false,
				false,
				array("ID", "ENTITY_TYPE", "ENTITY_ID", "TMP_ID")
			);

			if ($arRes = $dbRes->Fetch())
			{
				$arForum = CForumNew::GetByID($arFields["FORUM_ID"]);

				$parser = new forumTextParser(LANGUAGE_ID);
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
					"VIDEO" => "N",
					"SMILES" => "N"
				);

				$arMessage = CForumMessage::GetByIDEx($ID);

				self::addNewsComment(array(
					'entityId' => $arRes["ENTITY_ID"],
					'message' => $parser->convert($arFields["POST_MESSAGE"], $arAllow),
					'textMessage' => $parser->convert4mail($arFields["POST_MESSAGE"]),
					'url' => $arMessage['URL'],
					'forumMessageId' => $ID,
					'authorId' => intval($arMessage["AUTHOR_ID"]),
					'logId' => $arRes["ID"],
				));
			}
		}
	}

	private static function addNewsComment($params = array())
	{
		global $DB, $USER_FIELD_MANAGER;

		$entityId = (!empty($params['entityId']) ? intval($params['entityId']) : 0);
		$logDate = (!empty($params['logDate']) ? $params['logDate'] : false);
		$message = (!empty($params['message']) ? $params['message'] : '');
		$textMessage = (!empty($params['textMessage']) ? $params['textMessage'] : '');
		$url = (!empty($params['url']) ? $params['url'] : '');
		$forumMessageId = (!empty($params['forumMessageId']) ? intval($params['forumMessageId']) : 0);
		$logId = (!empty($params['logId']) ? intval($params['logId']) : 0);
		$authorId = (!empty($params['authorId']) ? intval($params['authorId']) : 0);

		if (
			$entityId <= 0
			|| $forumMessageId <= 0
		)
		{
			return;
		}

		$fields = array(
			"ENTITY_TYPE" => SONET_SUBSCRIBE_ENTITY_NEWS,
			"ENTITY_ID" => $entityId,
			"EVENT_ID" => "news_comment",
			"MESSAGE" => $message,
			"TEXT_MESSAGE" => $textMessage,
			"URL" => $url,
			"MODULE_ID" => false,
			"SOURCE_ID" => $forumMessageId,
			"LOG_ID" => $logId,
			"RATING_TYPE_ID" => "FORUM_POST",
			"RATING_ENTITY_ID" => $forumMessageId
		);

		if ($logDate)
		{
			$fields["LOG_DATE"] = $logDate;
		}
		else
		{
			$fields["=LOG_DATE"] = $DB->currentTimeFunction();
		}

		if ($authorId > 0)
		{
			$fields["USER_ID"] = $authorId;
		}

		$ufFileID = array();
		if (
			Loader::includeModule('forum')
			&& Loader::includeModule('socialnetwork')
		)
		{
			$res = CForumFiles::getList(array("ID" => "ASC"), array("MESSAGE_ID" => $forumMessageId));
			while ($forumFile = $res->fetch())
			{
				$ufFileID[] = $forumFile["FILE_ID"];
			}

			if (count($ufFileID) > 0)
			{
				$fields["UF_SONET_COM_FILE"] = $ufFileID;
			}

			$ufDocID = $USER_FIELD_MANAGER->getUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_DOC", $forumMessageId, LANGUAGE_ID);
			if ($ufDocID)
			{
				$fields["UF_SONET_COM_DOC"] = $ufDocID;
			}

			$commentId = CSocNetLogComments::add($fields, false, false);
			if (
				!is_array($commentId)
				&& intval($commentId) > 0
			)
			{
				CSocNetLog::counterIncrement($commentId, false, false, "LC");
			}
		}
	}

	public static function onAfterForumMessageDelete($ID, $arFields)
	{
		$val = COption::GetOptionString("intranet", "sonet_log_news_iblock_forum");
		if ($val <> '')
			$arIBlockForum = unserialize($val, ["allowed_classes" => false]);
		else
			$arIBlockForum = array();

		if (
			CModule::IncludeModule("socialnetwork")
			&& in_array($arFields["FORUM_ID"], $arIBlockForum)
		)
		{
			$dbRes = CSocNetLogComments::GetList(
				array("ID" => "DESC"),
				array(
					"EVENT_ID" => "news_comment",
					"SOURCE_ID" => $ID
				),
				false,
				false,
				array("ID")
			);

			if ($arRes = $dbRes->Fetch())
				CSocNetLogComments::Delete($arRes["ID"]);
		}
	}

	public static function AddComment_News($arFields)
	{
		global $USER, $USER_FIELD_MANAGER;

		if (
			!CModule::IncludeModule("forum")
			|| !CModule::IncludeModule("iblock")
			|| !CModule::IncludeModule("socialnetwork")
		)
		{
			return false;
		}

		$ufFileID = array();
		$ufDocID = array();

		$dbResult = CSocNetLog::GetList(
			array("ID" => "DESC"),
			array("TMP_ID" => $arFields["LOG_ID"]),
			false,
			false,
			array("ID", "SOURCE_ID", "PARAMS")
		);

		$bFound = false;
		if ($arLog = $dbResult->Fetch())
		{
			if (intval($arLog["SOURCE_ID"]) > 0)
			{
				$arFilter = array("ID" => $arLog["SOURCE_ID"]);
				$arSelectedFields = array("IBLOCK_ID", "ID", "CREATED_BY", "NAME", "PROPERTY_FORUM_TOPIC_ID", "PROPERTY_FORUM_MESSAGE_CNT");
				$db_res = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelectedFields);
				if ($db_res && $res = $db_res->GetNext())
				{
					$arElement = $res;

					$val = COption::GetOptionString("intranet", "sonet_log_news_iblock_forum");
					$arIBlockForum = (
						$val <> ''
							? unserialize($val, ["allowed_classes" => false])
							: array()
					);

					if (array_key_exists($arElement["IBLOCK_ID"], $arIBlockForum))
					{
						$FORUM_ID = $arIBlockForum[$arElement["IBLOCK_ID"]];
					}

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
						{
							$TOPIC_ID = $arMessage["TOPIC_ID"];
						}

						if(intval($TOPIC_ID) > 0)
						{
							// Add comment
							$messageID = false;

							$bError = false;
							if (CForumMessage::CanUserAddMessage($TOPIC_ID, $USER->GetUserGroupArray(), $USER->GetID(), false))
							{
								$bSHOW_NAME = true;
								if ($res = CForumUser::GetByUSER_ID($USER->GetID()))
								{
									$bSHOW_NAME = ($res["SHOW_NAME"] == "Y");
								}

								if ($bSHOW_NAME)
								{
									$AUTHOR_NAME = $USER->GetFullName();
								}

								if (Trim($AUTHOR_NAME) == '')
								{
									$AUTHOR_NAME = $USER->GetLogin();
								}

								if ($AUTHOR_NAME == '')
								{
									$bError = true;
								}
							}

							if (!$bError)
							{
								$arFieldsMessage = Array(
									"POST_MESSAGE" => $arFields["TEXT_MESSAGE"],
									"USE_SMILES" => "Y",
									"APPROVED" => "Y",
									"PARAM2" => $arElement["ID"],
									"AUTHOR_NAME" => $AUTHOR_NAME,
									"AUTHOR_ID" => intval($USER->GetParam("USER_ID")),
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
									$AUTHOR_REAL_IP = (
										$AUTHOR_IP_tmp == $AUTHOR_REAL_IP
											? $AUTHOR_IP
											: @gethostbyaddr($AUTHOR_REAL_IP)
									);
								}

								$arFieldsMessage["AUTHOR_IP"] = ($AUTHOR_IP!==False) ? $AUTHOR_IP : "<no address>";
								$arFieldsMessage["AUTHOR_REAL_IP"] = ($AUTHOR_REAL_IP!==False) ? $AUTHOR_REAL_IP : "<no address>";

								$USER_FIELD_MANAGER->EditFormAddFields("SONET_COMMENT", $arTmp);

								if (is_array($arTmp))
								{
									if (array_key_exists("UF_SONET_COM_DOC", $arTmp))
									{
										$arFieldsMessage["UF_FORUM_MESSAGE_DOC"] = $arTmp["UF_SONET_COM_DOC"];
									}
									elseif (array_key_exists("UF_SONET_COM_FILE", $arTmp))
									{
										$arFieldsMessage["FILES"] = array();
										foreach($arTmp["UF_SONET_COM_FILE"] as $file_id)
										{
											$arFieldsMessage["FILES"][$file_id] = array("FILE_ID" => $file_id);
										}

										if (!empty($arFieldsMessage["FILES"]))
										{
											$arFileParams = array("FORUM_ID" => $arMessage["FORUM_ID"], "TOPIC_ID" => $arMessage["TOPIC_ID"]);
											if (CForumFiles::CheckFields($arFieldsMessage["FILES"], $arFileParams, "NOT_CHECK_DB"))
											{
												CForumFiles::Add(array_keys($arFieldsMessage["FILES"]), $arFileParams);
											}
										}
									}
								}

								$messageID = CForumMessage::Add($arFieldsMessage, false);
								if (intval($messageID) <= 0)
								{
									$bError = true;
								}
								else
								{
									if ($messageID > 0)
									{
										$dbAddedMessageFiles = CForumFiles::GetList(array("ID" => "ASC"), array("MESSAGE_ID" => $messageID));
										while ($arAddedMessageFiles = $dbAddedMessageFiles->Fetch())
										{
											$ufFileID[] = $arAddedMessageFiles["FILE_ID"];
										}

										$ufDocID = $USER_FIELD_MANAGER->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_DOC", $messageID, LANGUAGE_ID);
									}

									if (CModule::IncludeModule("statistic"))
									{
										$arForum = CForumNew::GetByID($FORUM_ID);
										$F_EVENT1 = $arForum["EVENT1"];
										$F_EVENT2 = $arForum["EVENT2"];
										$F_EVENT3 = $arForum["EVENT3"];
										if ($F_EVENT3 == '')
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
		{
			$strError = GetMessage("SONET_ADD_COMMENT_SOURCE_ERROR");
		}

		return array(
				"SOURCE_ID"	=> $messageID,
				"RATING_TYPE_ID" => "FORUM_POST",
				"RATING_ENTITY_ID" => $messageID,
				"ERROR" => $strError,
				"NOTES" => "",
				"UF" => array(
					"FILE" => $ufFileID,
					"DOC" => $ufDocID
				)
		);
	}

	public static function OnAfterIBlockElementDelete($arFields)
	{
		// news
		if (
			!array_key_exists("WF_STATUS_ID", $arFields)
			|| $arFields["WF_STATUS_ID"] == 1
		)
		{
			$dbIBlock = CIBlock::GetByID($arFields["IBLOCK_ID"]);
			if($arIBlock = $dbIBlock->Fetch())
			{
				$rsSite = CIBlock::GetSite($arFields["IBLOCK_ID"]);
				if ($arSite = $rsSite->Fetch())
					$site_id = $arSite["SITE_ID"];

				$val = COption::GetOptionString("intranet", "sonet_log_news_iblock", "", $site_id);
				if ($val <> '')
				{
					$arIBCode = unserialize($val, ["allowed_classes" => false]);
					if (!is_array($arIBCode) || count($arIBCode) <= 0)
						$arIBCode = array();
				}
				else
					$arIBCode = array();

				if (
					in_array($arIBlock["CODE"], $arIBCode)
					&& CModule::IncludeModule("socialnetwork")
				)
				{
					CSocNetAllowed::GetAllowedEntityTypes();

					$dbRes = CSocNetLog::GetList(
						array("ID" => "DESC"),
						array(
							"ENTITY_TYPE" => SONET_SUBSCRIBE_ENTITY_NEWS,
							"EVENT_ID" => "news",
							"SOURCE_ID" => $arFields["ID"]
						),
						false,
						false,
						array("ID")
					);
					while ($arRes = $dbRes->Fetch())
						CSocNetLog::Delete($arRes["ID"]);
				}
			}
		}
		// --news
	}

	public static function OnUserDelete($USER_ID)
	{
		if (CModule::IncludeModule('socialnetwork'))
		{
			$dbRes = CSocNetLog::GetList(array(), array(
				'ENTITY_TYPE' => SONET_INTRANET_NEW_USER_ENTITY,
				'EVENT_ID' => SONET_INTRANET_NEW_USER_EVENT_ID,
				'ENTITY_ID' => $USER_ID,
				'SOURCE_ID' => $USER_ID,
			), false, array('ID'));

			$arRes = $dbRes->Fetch();
			if ($arRes)
			{
				CSocNetLog::Delete($arRes['ID']);
			}
		}

		$departmentRepository = \Bitrix\Intranet\Service\ServiceContainer::getInstance()
			->departmentRepository();
		$departmentCollection = $departmentRepository->getDepartmentByHeadId((int)$USER_ID);
		foreach ($departmentCollection as $department)
		{
			$departmentRepository->unsetHead($department->getId());
		}

	}

	public static function OnAfterUserInitialize($userId)
	{
		if (!IsModuleInstalled('bitrix24'))
		{
			$dbUser = CUser::GetByID($userId);
			if ($arUser = $dbUser->Fetch())
			{
				CIntranetEventHandlers::OnAfterUserAdd($arUser);
			}
		}

		$res = InvitationTable::getList([
			'filter' => [
				'USER_ID' => $userId,
				'INITIALIZED' => 'N'
			],
			'select' => ['ID', 'INVITATION_TYPE', 'IS_MASS', 'IS_DEPARTMENT', 'IS_INTEGRATOR', 'IS_REGISTER'],
			'limit' => 1
		]);
		$invitationFields = $res->fetch();

		if ($invitationFields)
		{
			InvitationTable::update($invitationFields['ID'], [
				'INITIALIZED' => 'Y'
			]);
		}

		(new \Bitrix\Main\Event('intranet', 'onUserFirstInitialization', [
			'invitationFields' => $invitationFields,
			'userId' => $userId
		]))->send();
	}

	public static function OnAfterUserAdd($arUser)
	{
		static $processedIdListIblock = [];

		if (
			isset($arUser['ID'])
			&& $arUser['ID'] > 0
			&& $arUser['ACTIVE'] === 'Y'
			&& empty($arUser['CONFIRM_CODE'])
			&& (
				!isset($arUser['EXTERNAL_AUTH_ID'])
				|| !in_array($arUser['EXTERNAL_AUTH_ID'], \Bitrix\Main\UserTable::getExternalUserTypes())
			)
			&& !defined('INTR_SKIP_EVENT_ADD')
			&& ($IBLOCK_ID = COption::GetOptionInt('intranet', 'iblock_state_history', ''))
			&& !in_array($arUser['ID'], $processedIdListIblock)
		)
		{
			static $ACCEPTED_ENUM_ID = null;

			if (null == $ACCEPTED_ENUM_ID)
			{
				$dbRes = CIBlockPropertyEnum::GetList(
					array('id' => 'asc'),
					array(
						'IBLOCK_ID' => $IBLOCK_ID,
						'CODE' => 'STATE',
						'XML_ID' => 'ACCEPTED'
					)
				);

				if ($arRes = $dbRes->Fetch())
				{
					$ACCEPTED_ENUM_ID = $arRes['ID'];
				}
			}

			$arFields = array(
				'IBLOCK_ID' => $IBLOCK_ID,
				'NAME' => GetMessage('INTR_HIRED').' - '.trim($arUser['LAST_NAME'].' '.$arUser['NAME']),
				'ACTIVE' => 'Y',
				'DATE_ACTIVE_FROM' => ConvertTimeStamp(),
				'PREVIEW_TEXT' => GetMessage('INTR_HIRED'),

				'PROPERTY_VALUES' => array(
					'USER' => $arUser['ID'],
					'DEPARTMENT' => $arUser['UF_DEPARTMENT'] ?? null,
					'POST' => $arUser['WORK_POSITION'] ? $arUser['WORK_POSITION'] : $arUser['PERSONAL_PROFESSION'],
					'STATE' => $ACCEPTED_ENUM_ID
				),
			);

			$obIB = new CIBlockElement();
			$obIB->Add($arFields);

			if (!IsModuleInstalled('bitrix24'))
			{
				CIntranetNotify::NewUserMessage($arUser['ID']);
			}

			$processedIdListIblock[] = $arUser['ID'];
		}
	}

	/*
	RegisterModuleDependences("main", "OnBeforeUserUpdate", "intranet", "CIntranetEventHandlers", "OnBeforeUserUpdate");
	*/
	public static function OnBeforeUserUpdate(&$fields)
	{
		if (
			!is_array($fields)
			|| !isset($fields['ID'])
			|| intval($fields['ID']) <= 0
		)
		{
			return;
		}

		$userId = intval($fields['ID']);

		$res = CUser::getList("id", "asc", array("ID_EQUAL_EXACT" => $userId), array("SELECT" => array("UF_DEPARTMENT"), "FIELDS" => array("ID", "ACTIVE")));
		if ($user = $res->fetch())
		{
			self::$userDepartmentCache[$userId] = $user["UF_DEPARTMENT"];
			self::$userActiveCache = $user["ACTIVE"];
		}

		if (!defined('BX_COMP_MANAGED_CACHE') || !BX_COMP_MANAGED_CACHE)
		{
			return;
		}

		global $CACHE_MANAGER;

		$queryObject = CUser::getList(
			"id", "asc",
			["ID_EQUAL_EXACT" => intval($fields['ID'])],
			['SELECT' => ['UF_DEPARTMENT']]
		);
		if ($oldFields = $queryObject->fetch())
		{
			if (
				isset($fields['UF_DEPARTMENT'])
				&& is_array($fields['UF_DEPARTMENT'])
				&& $fields['UF_DEPARTMENT'] != $oldFields['UF_DEPARTMENT']
			)
			{
				if (!is_array($oldFields['UF_DEPARTMENT']))
				{
					$oldFields['UF_DEPARTMENT'] = [];
				}

				$arDepts = array_merge($fields['UF_DEPARTMENT'], $oldFields['UF_DEPARTMENT']);
				if (count($arDepts) > 0)
				{
					$CACHE_MANAGER->ClearByTag('intranet_department_structure');

					foreach ($arDepts as $dpt)
					{
						$CACHE_MANAGER->ClearByTag('intranet_department_'.$dpt);
					}
				}
			}
		}
	}

	/*
	RegisterModuleDependences("main", "OnAfterUserUpdate", "intranet", "CIntranetEventHandlers", "OnAfterUserUpdate");
	*/
	public static function OnAfterUserUpdate(&$fields)
	{
		if(array_key_exists('UF_DEPARTMENT', $fields))
		{
			if(
				is_array(self::$userDepartmentCache[$fields['ID']])
				&& (
					array_diff($fields['UF_DEPARTMENT'], self::$userDepartmentCache[$fields['ID']])
					|| count($fields['UF_DEPARTMENT']) !== count(self::$userDepartmentCache[$fields['ID']])
				)
			)
			{
				$event = new Event("intranet", "onEmployeeDepartmentsChanged", array(
					'userId' => $fields['ID'],
					'oldDepartmentList' => self::$userDepartmentCache[$fields['ID']],
					'newDepartmentList' => $fields['UF_DEPARTMENT']
				));
				$event->send();

				if (defined('BX_COMP_MANAGED_CACHE'))
				{
					global $CACHE_MANAGER;

					$CACHE_MANAGER->ClearByTag('intranet_department_structure');
				}
			}

			if (
				$fields['ID'] > 0
				&& isset($fields['UF_DEPARTMENT'])
				&& is_array($fields['UF_DEPARTMENT'])
				&& !empty($fields['UF_DEPARTMENT'][0])
				&& isset($fields['ACTIVE'])
				&& $fields['ACTIVE'] === 'Y'
				&& (
					!isset($fields['EXTERNAL_AUTH_ID'])
					|| !in_array($fields['EXTERNAL_AUTH_ID'], \Bitrix\Main\UserTable::getExternalUserTypes())
				)
				&& (
					!is_array(self::$userDepartmentCache[$fields['ID']])
					|| !self::$userDepartmentCache[$fields['ID']][0]
					|| self::$userActiveCache === 'N'
				)
				&& !defined('INTR_SKIP_EVENT_ADD')
				&& !IsModuleInstalled('bitrix24')
			)
			{
				CIntranetNotify::NewUserMessage($fields['ID']);
			}
		}
	}

	public static function OnFillSocNetAllowedSubscribeEntityTypes(&$arSocNetAllowedSubscribeEntityTypes)
	{
		define("SONET_SUBSCRIBE_ENTITY_NEWS", "N");
		$arSocNetAllowedSubscribeEntityTypes[] = SONET_SUBSCRIBE_ENTITY_NEWS;

		global $arSocNetAllowedSubscribeEntityTypesDesc;
		$arSocNetAllowedSubscribeEntityTypesDesc[SONET_SUBSCRIBE_ENTITY_NEWS] = array(
			"TITLE_LIST" => GetMessage("INTR_SOCNET_LOG_LIST_N_ALL"),
			"TITLE_ENTITY" => GetMessage("INTR_SOCNET_LOG_N"),
			"TITLE_ENTITY_XDI" => GetMessage("INTR_SOCNET_LOG_XDI_N"),
			"CLASS_DESC" => "",
			"METHOD_DESC" => "",
			"CLASS_DESC_GET" => "CIntranetUtils",
			"METHOD_DESC_GET" => "GetIBlockByID",
			"CLASS_DESC_SHOW" => "CIntranetUtils",
			"METHOD_DESC_SHOW" => "ShowIBlockByID",
			"XDIMPORT_ALLOWED" => "Y"
		);
	}

	public static function OnFillSocNetLogEvents(&$arSocNetLogEvents)
	{
		$arSocNetLogEvents["news"] = array(
			"ENTITIES" =>	array(
				SONET_SUBSCRIBE_ENTITY_NEWS => array(
					"TITLE" => GetMessage("INTR_SOCNET_LOG_NEWS"),
					"TITLE_SETTINGS" => GetMessage("INTR_SOCNET_LOG_NEWS_SETTINGS"),
					"TITLE_SETTINGS_1" => GetMessage("INTR_SOCNET_LOG_NEWS_SETTINGS_1"),
					"TITLE_SETTINGS_2" => GetMessage("INTR_SOCNET_LOG_NEWS_SETTINGS_2"),
				),
			),
			"CLASS_FORMAT" => "CIntranetEventHandlers",
			"METHOD_FORMAT" => "FormatEvent_News",
			"FULL_SET" => array("news", "news_comment"),
			"COMMENT_EVENT" => array(
				"EVENT_ID" => "news_comment",
				"CLASS_FORMAT" => "CIntranetEventHandlers",
				"METHOD_FORMAT" => "FormatComment_News",
				"ADD_CALLBACK" => array("CIntranetEventHandlers", "AddComment_News"),
				"UPDATE_CALLBACK" => array("CSocNetLogTools", "UpdateComment_Forum"),
				"DELETE_CALLBACK" => array("CSocNetLogTools", "DeleteComment_Forum"),
				"RATING_TYPE_ID" => "FORUM_POST"
			),
			"XDIMPORT_ALLOWED" => "Y"
		);
	}

/* clear cache handlers */

/*
RegisterModuleDependences('main', 'onUserDelete', 'intranet', 'CIntranetEventHandlers', 'ClearAllUsersCache');
RegisterModuleDependences('main', 'onAfterUserAdd', 'intranet', 'CIntranetEventHandlers', 'ClearAllUsersCache');

clear single user cache if it's deleted and clear whole users cache
*/
	public static function ClearAllUsersCache($ID = false)
	{
		if (!defined('BX_COMP_MANAGED_CACHE') || !BX_COMP_MANAGED_CACHE) return true;

		global $CACHE_MANAGER;
		if ($ID && !is_array($ID))
		{
			$CACHE_MANAGER->ClearByTag("intranet_user_".$ID);
			$CACHE_MANAGER->ClearByTag("USER_NAME_".$ID);
		}
		$CACHE_MANAGER->ClearByTag("intranet_users");
		return true;
	}

/*
RegisterModuleDependences('main', 'onBeforeUserUpdate', 'intranet', 'CIntranetEventHandlers', 'ClearSingleUserCache');

clear single user cache and clear all users cache in case of change user's activity
*/
/*
TODO: what do we should check in case of user's departments change? variant: if they're changed - use both $CACHE_MANAGER->ClearByTag('iblock_id_'.$old_dept) and $CACHE_MANAGER->ClearByTag('iblock_id_'.$new_dept)
*/
	public static function ClearSingleUserCache($arFields)
	{
		if (!defined('BX_COMP_MANAGED_CACHE') || !BX_COMP_MANAGED_CACHE) return true;

		global $CACHE_MANAGER;

		$dbRes = CUser::GetList(
			"id", "asc",
			array("ID_EQUAL_EXACT" => intval($arFields['ID'])),
			array('SELECT' => array('UF_DEPARTMENT'))
		);

		$arRecacheFields = array('ACTIVE', 'LAST_NAME');

		$bRecache = false;
		if ($arOldFields = $dbRes->Fetch())
		{
			if (
				isset($arFields['PERSONAL_BIRTHDAY'])
				&& $arOldFields['PERSONAL_BIRTHDAY'] != $arFields['PERSONAL_BIRTHDAY']
			)
				$CACHE_MANAGER->ClearByTag("intranet_birthday");

			if (
				isset($arFields['UF_DEPARTMENT'])
				&& is_array($arFields['UF_DEPARTMENT'])
				&& $arFields['UF_DEPARTMENT'] != $arOldFields['UF_DEPARTMENT']
			)
			{
				if (!is_array($arOldFields['UF_DEPARTMENT']))
					$arOldFields['UF_DEPARTMENT'] = array();

				$arDepts = array_merge($arFields['UF_DEPARTMENT'], $arOldFields['UF_DEPARTMENT']);
				if(count($arDepts) > 0)
				{
					$CACHE_MANAGER->ClearByTag('intranet_department_structure');

					foreach ($arDepts as $dpt)
					{
						$CACHE_MANAGER->ClearByTag('intranet_department_'.$dpt);
					}
				}
			}

			foreach ($arRecacheFields as $fld)
			{
				if (isset($arFields[$fld]) && $arOldFields[$fld] != $arFields[$fld])
				{
					$bRecache = true;
					break;
				}
			}
		}

		$fieldsForClearComposite = [
			"NAME", "LAST_NAME", "SECOND_NAME", "ACTIVE", "LOGIN", "EMAIL", "PERSONAL_GENDER", "PERSONAL_PHOTO",
			"WORK_POSITION", "PERSONAL_PROFESSION", "PERSONAL_WWW", "PERSONAL_BIRTHDAY", "TITLE",
			"EXTERNAL_AUTH_ID", "UF_DEPARTMENT", "AUTO_TIME_ZONE", "TIME_ZONE", "TIME_ZONE_OFFSET"
		];

		$clearComposite = false;
		foreach ($fieldsForClearComposite as $code)
		{
			if (isset($arFields[$code]) && $arOldFields[$code] != $arFields[$code])
			{
				$clearComposite = true;
				break;
			}
		}

		if ($clearComposite && \CHTMLPagesCache::IsOn())
		{
			\Bitrix\Intranet\Composite\CacheProvider::deleteUserCache(intval($arFields['ID']));
		}

		if ($bRecache)
			CIntranetEventHandlers::ClearAllUsersCache($arFields['ID']);
		else
			$CACHE_MANAGER->ClearByTag("intranet_user_".$arFields['ID']);

		return true;
	}

/*
RegisterModuleDependences('iblock', 'OnAfterIBlockSectionUpdate', 'intranet', 'CIntranetEventHandlers', 'ClearDepartmentCache');
*/
	public static function ClearDepartmentCache($arFields)
	{
		global $CACHE_MANAGER;

		if (!defined('BX_COMP_MANAGED_CACHE') || !BX_COMP_MANAGED_CACHE) return true;

		if (COption::GetOptionString('intranet', 'iblock_structure', '') == $arFields['IBLOCK_ID'])
		{
			$CACHE_MANAGER->ClearByTag('intranet_department_'.$arFields['ID']);
		}
	}

	// unregistered
	public static function OnBeforeProlog()
	{
		$conditionList = array();
		if (\Bitrix\Main\ModuleManager::isModuleInstalled('sale'))
		{
			$conditionList[] = array(
				'PATTERN' => 'EXTERNAL_AUTH_ID',
				'VALUE' => "WHEN %s = 'saleanonymous' THEN 'sale'"
			);
		}
		if (\Bitrix\Main\ModuleManager::isModuleInstalled('imconnector'))
		{
			$conditionList[] = array(
				'PATTERN' => 'EXTERNAL_AUTH_ID',
				'VALUE' => "WHEN %s = 'imconnector' THEN 'imconnector'"
			);
		}
		if (\Bitrix\Main\ModuleManager::isModuleInstalled('im'))
		{
			$conditionList[] = array(
				'PATTERN' => 'EXTERNAL_AUTH_ID',
				'VALUE' => "WHEN %s = 'bot' THEN 'bot'"
			);
		}
		if (\Bitrix\Main\ModuleManager::isModuleInstalled('mail'))
		{
			$conditionList[] = array(
				'PATTERN' => 'EXTERNAL_AUTH_ID',
				'VALUE' => "WHEN %s = 'email' THEN 'email'"
			);
		}
		if (\Bitrix\Main\ModuleManager::isModuleInstalled('extranet'))
		{
			$conditionList[] = array(
				'PATTERN' => 'UF_DEPARTMENT',
				'VALUE' => "WHEN %s = 'a:0:{}' THEN 'extranet'"
			);
			$conditionList[] = array(
				'PATTERN' => 'UF_DEPARTMENT',
				'VALUE' => "WHEN %s IS NULL THEN 'extranet'"
			);
		}

		$condition = "CASE ";
		$patternList = array();

		foreach($conditionList as $conditionFields)
		{
			$condition .= ' '.$conditionFields['VALUE'].' ';
			$patternList[] = $conditionFields['PATTERN'];
		}
		$condition .= "ELSE 'employee' END";

		\Bitrix\Main\UserTable::getEntity()->addField(
			new Bitrix\Main\Entity\ExpressionField('USER_TYPE',
				$condition,
				$patternList
			)
		);
	}
/*
RegisterModuleDependences('main', 'OnBeforeProlog', 'intranet', 'CIntranetEventHandlers', 'OnCreatePanel');
*/
	public static function OnCreatePanel()
	{
		global $USER, $APPLICATION;

		if(defined("ADMIN_SECTION") && ADMIN_SECTION == true)
			return;

		if (self::isSkipWizardButton())
		{
			return;
		}

		if($USER->IsAdmin())
		{
			$hint = GetMessage('INTR_SET_BUT_HINT');
			$arMenu = Array(
				Array(
					"ACTION" => "jsUtils.Redirect([], '".CUtil::JSEscape("/bitrix/admin/wizard_install.php?lang=".LANGUAGE_ID."&wizardName=bitrix:portal&wizardSiteID=".SITE_ID."&".bitrix_sessid_get())."');",
					"ICON" => "wizard",
					"TITLE" => GetMessage('INTR_SET_WIZ_TITLE'),
					"TEXT" => GetMessage('INTR_SET_WIZ_TEXT'),
					"DEFAULT" => true,
				),
			);

			if(IsModuleInstalled('extranet'))
			{
				$hint .= GetMessage('INTR_SET_BUT_HINT_EXTRANET');
				$arMenu[] = Array(
					"ACTION" => "jsUtils.Redirect([], '".CUtil::JSEscape("/bitrix/admin/wizard_install.php?lang=".LANGUAGE_ID."&wizardName=bitrix:extranet&".bitrix_sessid_get())."');",
					"ICON" => "wizard",
					"TITLE" => GetMessage('INTR_SET_EXT_TITLE'),
					"TEXT" => GetMessage('INTR_SET_EXT_TEXT'),
				);
			}
			if(COption::GetOptionString("main", "wizard_clear_exec", "N", SITE_ID) <> "Y")
			{
				$hint .= GetMessage('INTR_SET_BUT_HINT_CLEARING');
				$arMenu[] = Array(
					"ACTION" => "jsUtils.Redirect([], '".CUtil::JSEscape("/bitrix/admin/wizard_install.php?lang=".LANGUAGE_ID."&wizardSiteID=".SITE_ID."&wizardName=bitrix:portal_clear&".bitrix_sessid_get())."');",
					"ICON" => "wizard-clear",
					"TITLE" => GetMessage('INTR_SET_CLEAN_TITLE'),
					"TEXT" => GetMessage('INTR_SET_CLEAN_TEXT'),
				);
			}

			$arButton = array(
				"HREF" => "/bitrix/admin/wizard_install.php?lang=".LANGUAGE_ID."&wizardName=bitrix:portal&wizardSiteID=".SITE_ID."&".bitrix_sessid_get(),
				"ID" => "portal_wizard",
				"ICON" => "bx-panel-site-wizard-icon",
				"ALT" => GetMessage('INTR_SET_BUT_TITLE'),
				"TEXT" => GetMessage('INTR_SET_BUT_TEXT'),
				"MAIN_SORT" => 2500,
				"TYPE" => "BIG",
				"SORT" => 10,
				"MENU" => (count($arMenu) > 1? $arMenu : array()),
				"HINT" => array(
					"TITLE" => str_replace('#BR#', ' ', GetMessage('INTR_SET_BUT_TEXT')),
					"TEXT" => $hint
				)
			);

			$APPLICATION->AddPanelButton($arButton);
		}
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	private static function isSkipWizardButton()
	{
		if (\Bitrix\Main\Config\Option::get("sale", "~IS_SALE_CRM_SITE_MASTER_FINISH") === "Y"
			|| \Bitrix\Main\Config\Option::get("sale", "~IS_SALE_BSM_SITE_MASTER_FINISH") === "Y"
		)
		{
			if (\Bitrix\Main\Config\Option::get("main", "wizard_solution", "bitrix:portal", SITE_ID) != "bitrix:portal")
			{
				return true;
			}
		}

		return false;
	}

	public static function GetEntity_News($arFields, $bMail)
	{
		$arEntity = array();

		$arEventParams = unserialize($arFields["~PARAMS"] <> '' ? $arFields["~PARAMS"] : $arFields["PARAMS"], ["allowed_classes" => false]);

		if (intval($arFields["ENTITY_ID"]) > 0)
		{
			if (
				is_array($arEventParams)
				&& count($arEventParams) > 0
				&& array_key_exists("ENTITY_NAME", $arEventParams)
				&& $arEventParams["ENTITY_NAME"] <> ''
			)
			{
				if (
					!$bMail
					&& array_key_exists("ENTITY_URL", $arEventParams)
					&& $arEventParams["ENTITY_URL"] <> ''
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
					$arEntity["TYPE_MAIL"] = GetMessage("INTR_SOCNET_LOG_ENTITY_MAIL");
				}
			}
		}

		return $arEntity;
	}

	public static function FormatEvent_News($arFields, $arParams, $bMail = false)
	{
		global $APPLICATION;
		$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/intranet_sonet_log.css");

		$arResult = array(
			"EVENT" => $arFields,
			"ENTITY" => CIntranetEventHandlers::GetEntity_News($arFields, $bMail),
			"URL" => "",
			"CACHED_CSS_PATH" => "/bitrix/themes/.default/intranet_sonet_log.css"
		);

		if (!CModule::IncludeModule("socialnetwork"))
			return $arResult;

		$title = "";
		if ($arFields["TITLE_TEMPLATE"] <> '')
		{
			$title_tmp = (
				!$bMail
				&& $arFields["URL"] <> ''
					? '<a href="'.$arFields["URL"].'">'.$arFields["TITLE"].'</a>'
					: $arFields["TITLE"]
			);

			$title = str_replace(
				array("#TITLE#", "#ENTITY#"),
				array($title_tmp, ($bMail ? $arResult["ENTITY"]["FORMATTED"] : $arResult["ENTITY"]["FORMATTED"]["NAME"])),
				($bMail ? GetMessage("INTR_SOCNET_LOG_NEWS_TITLE_MAIL") : GetMessage("INTR_SOCNET_LOG_NEWS_TITLE"))
			);
		}

		$url = false;

		if (
			$arFields["URL"] <> ''
			&& $arFields["SITE_ID"] <> ''
		)
		{
			if (mb_substr($arFields["URL"], 0, 1) === "/")
			{
				$rsSites = CSite::GetByID($arFields["SITE_ID"]);
				$arSite = $rsSites->Fetch();

				$server_name = (
					$arSite["SERVER_NAME"] <> ''
						? $arSite["SERVER_NAME"]
						: COption::GetOptionString("main", "server_name", $GLOBALS["SERVER_NAME"])
				);

				$protocol = (CMain::IsHTTPS() ? "https" : "http");
				$url = $protocol."://".$server_name.$arFields["URL"];
			}
			else
			{
				$url = $arFields["URL"];
			}
		}

		$arResult["EVENT_FORMATTED"] = array(
			"TITLE" => $title,
			"MESSAGE" => ($bMail ? CSocNetTextParser::killAllTags($arFields["MESSAGE"]) : $arFields["MESSAGE"]),
			"IS_IMPORTANT" => true,
			"TITLE_24" => GetMessage("INTR_SONET_LOG_DATA_TITLE_IMPORTANT_24"),
			"TITLE_24_2" => $arFields["TITLE"],
			"STYLE" => "imp-post",
		);

		if ($arParams["MOBILE"] == "Y")
		{
			$arResult["EVENT_FORMATTED"]["STYLE"] = "item-top-text-important";
			$arResult["EVENT_FORMATTED"]["AVATAR_STYLE"] = "avatar-info";
		}
		else
		{
			$arResult["EVENT_FORMATTED"]["STYLE"] = "info";
		}

		if ($url <> '')
		{
			$arResult["EVENT_FORMATTED"]["URL"] = $url;
		}

		if (!$bMail)
		{
			if (
				intval($arFields["SOURCE_ID"]) > 0
				&& CModule::IncludeModule("iblock")
			)
			{
				$rsIBlockElement = CIBlockElement::GetList(
					array(),
					array("ID" => $arFields["SOURCE_ID"]),
					false,
					false,
					array("ID", "PREVIEW_TEXT", "PREVIEW_TEXT_TYPE", "DETAIL_TEXT", "DETAIL_TEXT_TYPE")
				);
				if ($arIBlockElement = $rsIBlockElement->fetch())
				{
					if (!empty($arIBlockElement["DETAIL_TEXT"]))
					{
						$detailText = $arIBlockElement["DETAIL_TEXT"];
						$detailTextType = $arIBlockElement["DETAIL_TEXT_TYPE"];
					}
					else
					{
						$detailText = $arIBlockElement["PREVIEW_TEXT"];
						$detailTextType = $arIBlockElement["PREVIEW_TEXT_TYPE"];
					}

					if ($detailTextType != 'html')
					{
						$detailText = htmlspecialcharsEx(htmlspecialcharsEx($detailText));
					}
					else
					{
						$sanitizer = new CBXSanitizer();
						$sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_LOW);
						$detailText = $sanitizer->SanitizeHtml($detailText);

						if($arParams["MOBILE"] == "Y")
						{
							$detailText = preg_replace("/<iframe [^src]*src=[\'\"]?([^>\s\'\"]+)[\'\"]?[^>]*>/i", '<a href="\\1">\\1</a>', $detailText);
						}
					}

					$arResult["EVENT_FORMATTED"]["MESSAGE"] = $detailText;
				}
			}

			if (
				$arParams["MOBILE"] != "Y"
				&& $arParams["NEW_TEMPLATE"] != "Y"
			)
			{
				$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);
				$arAllow = array("HTML" => "Y", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N", "MULTIPLE_BR" => "N", "VIDEO" => "Y", "LOG_VIDEO" => "N");
				$arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"] = $parserLog->html_cut(
					$parserLog->convert(htmlspecialcharsback(str_replace("#CUT#", "", $arResult["EVENT_FORMATTED"]["MESSAGE"])), array(), $arAllow),
					1000
				);
				$arResult["EVENT_FORMATTED"]["IS_MESSAGE_SHORT"] = CSocNetLogTools::FormatEvent_IsMessageShort($arResult["EVENT_FORMATTED"]["MESSAGE"], $arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"]);
			}

			if ($arParams["MOBILE"] != "Y")
			{
				$rsRight = CSocNetLogRights::GetList(array(), array("LOG_ID" => $arFields["ID"]));
				$arRights = array();
				while ($arRight = $rsRight->Fetch())
					$arRights[] = $arRight["GROUP_CODE"];
				$arResult["EVENT_FORMATTED"]["DESTINATION"] = CSocNetLogTools::FormatDestinationFromRights($arRights, $arParams);
			}
		}

		$arResult["HAS_COMMENTS"] = (intval($arFields["SOURCE_ID"]) > 0 ? "Y" : "N");

		return $arResult;
	}

	public static function FormatComment_News($arFields, $arParams, $bMail = false, $arLog = array())
	{
		$arResult = array(
				"EVENT_FORMATTED"	=> array(),
			);

		if (!CModule::IncludeModule("socialnetwork"))
			return $arResult;

		if ($bMail)
		{
			$arResult["CREATED_BY"] = CSocNetLogTools::FormatEvent_GetCreatedBy($arFields, $arParams, $bMail);
			$arResult["ENTITY"] = CIntranetEventHandlers::GetEntity_News($arLog, $bMail);
		}
		elseif($arParams["USE_COMMENT"] != "Y")
		{
			$arLog["ENTITY_ID"] = $arFields["ENTITY_ID"];
			$arLog["ENTITY_TYPE"] = $arFields["ENTITY_TYPE"];
			$arResult["ENTITY"] = CIntranetEventHandlers::GetEntity_News($arLog, false);
		}

		if (
			!$bMail
			&& array_key_exists("URL", $arLog)
			&& $arLog["URL"] <> ''
		)
			$news_tmp = '<a href="'.$arLog["URL"].'">'.$arLog["TITLE"].'</a>';
		else
			$news_tmp = $arLog["TITLE"];

		$title_tmp = ($bMail ? GetMessage("INTR_SOCNET_LOG_NEWS_COMMENT_TITLE_MAIL") : GetMessage("INTR_SOCNET_LOG_NEWS_COMMENT_TITLE"));

		$title = str_replace(
			array("#TITLE#", "#ENTITY#", "#CREATED_BY#"),
			array($news_tmp, $arResult["ENTITY"]["FORMATTED"], ($bMail ? $arResult["CREATED_BY"]["FORMATTED"] : "")),
			$title_tmp
		);

		$arResult["EVENT_FORMATTED"] = array(
			"TITLE" => ($bMail || $arParams["USE_COMMENT"] != "Y" ? $title : ""),
			"MESSAGE" => ($bMail ? $arFields["TEXT_MESSAGE"] : $arFields["MESSAGE"])
		);

		if ($bMail)
		{
			$url = CSocNetLogTools::FormatEvent_GetURL($arLog);
			if ($url <> '')
				$arResult["EVENT_FORMATTED"]["URL"] = $url;
		}
		else
		{
			static $parserLog = false;
			if (CModule::IncludeModule("forum"))
			{
				if (!$parserLog)
				{
					$parserLog = new forumTextParser(LANGUAGE_ID);
				}

				$arAllow = array(
					"HTML" => "N",
					"ALIGN" => "Y",
					"ANCHOR" => "Y", "BIU" => "Y",
					"IMG" => "Y", "QUOTE" => "Y",
					"CODE" => "Y", "FONT" => "Y",
					"LIST" => "Y", "SMILES" => "Y",
					"NL2BR" => "Y", "VIDEO" => "Y",
					"LOG_VIDEO" => "N", "SHORT_ANCHOR" => "Y",
					"USERFIELDS" => $arFields["UF"],
					"USER" => ($arParams["IM"] == "Y" ? "N" : "Y")
				);

				$parserLog->pathToUser = $parserLog->userPath = $arParams["PATH_TO_USER"];
				$parserLog->arUserfields = $arFields["UF"];
				$parserLog->bMobile = ($arParams["MOBILE"] == "Y");
				$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), $arAllow));
				$arResult["EVENT_FORMATTED"]["MESSAGE"] = preg_replace("/\[user\s*=\s*([^\]]*)\](.+?)\[\/user\]/isu", "\\2", $arResult["EVENT_FORMATTED"]["MESSAGE"]);
			}
			else
			{
				if (!$parserLog)
				{
					$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);
				}

				$arAllow = array(
					"HTML" => "Y", "ANCHOR" => "Y", "BIU" => "Y",
					"IMG" => "Y", "LOG_IMG" => "N",
					"QUOTE" => "Y", "LOG_QUOTE" => "N",
					"CODE" => "Y", "LOG_CODE" => "N",
					"FONT" => "Y", "LOG_FONT" => "N",
					"LIST" => "Y",
					"SMILES" => "Y",
					"NL2BR" => "Y",
					"MULTIPLE_BR" => "N",
					"VIDEO" => "Y", "LOG_VIDEO" => "N"
				);

				$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow));
			}

			if (
				$arParams["MOBILE"] != "Y"
				&& $arParams["NEW_TEMPLATE"] != "Y"
			)
			{
				if (CModule::IncludeModule("forum"))
					$arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"] = $parserLog->html_cut(
						$parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), $arAllow),
						500
					);
				else
					$arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"] = $parserLog->html_cut(
						$parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow),
						500
					);

				$arResult["EVENT_FORMATTED"]["IS_MESSAGE_SHORT"] = CSocNetLogTools::FormatEvent_IsMessageShort($arResult["EVENT_FORMATTED"]["MESSAGE"], $arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"]);
			}
		}

		return $arResult;
	}

	public static function OnIBlockModuleUnInstall()
	{
		global $APPLICATION;

		$APPLICATION->ThrowException(GetMessage('INTR_IBLOCK_REQUIRED_EXTENDED'));

		return false;
	}

	public static function OnAfterUserAuthorize($arParams)
	{
		unset($_SESSION["OTP_ADMIN_INFO"]);
		unset($_SESSION["OTP_EMPLOYEES_INFO"]);
		unset($_SESSION["OTP_MANDATORY_INFO"]);

		if (!empty($arParams["user_fields"]["CONFIRM_CODE"]))
		{
			$user = new CUser();
			$user->Update($arParams["user_fields"]["ID"], array("CONFIRM_CODE" => ""));
		}
	}

	public static function onRestAppInstall($params)
	{
		if (!isset($params['APP_ID']) || !\Bitrix\Main\Loader::includeModule('rest'))
		{
			return;
		}

		$text = '';
		$dbRes = \Bitrix\Rest\AppTable::getList(
			[
				'filter' => [
					'=ID' => $params['APP_ID'],
				],
				'select' => [
					'ID',
					'CODE',
					'CLIENT_ID',
					'MENU_NAME' => 'LANG.MENU_NAME',
					'MENU_NAME_DEFAULT' => 'LANG_DEFAULT.MENU_NAME',
					'MENU_NAME_LICENSE' => 'LANG_LICENSE.MENU_NAME',
				],
			]
		);

		foreach ($dbRes->fetchCollection() as $app)
		{
			$appInfo = [
				'ID' => $app->getId(),
				'CODE' => $app->getCode(),
				'CLIENT_ID' => $app->getClientId(),
				'MENU_NAME' => !is_null($app->getLang()) ? $app->getLang()->getMenuName() : '',
				'MENU_NAME_DEFAULT' => !is_null($app->getLangDefault()) ? $app->getLangDefault()->getMenuName() : '',
				'MENU_NAME_LICENSE' => !is_null($app->getLangLicense()) ? $app->getLangLicense()->getMenuName() : '',
			];

			if ($appInfo['CODE'] === \CRestUtil::BITRIX_1C_APP_CODE)
			{
				return;
			}

			if (
				$appInfo['MENU_NAME'] === ''
				&& $appInfo['MENU_NAME_DEFAULT'] === ''
				&& $appInfo['MENU_NAME_LICENSE'] === ''
			)
			{
				$app->fillLangAll();
				if (!is_null($app->getLangAll()))
				{
					$langList = [];
					foreach ($app->getLangAll() as $appLang)
					{
						if ($appLang->getMenuName() !== '')
						{
							$langList[$appLang->getLanguageId()] = $appLang->getMenuName();
						}
					}

					$defaultLang = \Bitrix\Main\Localization\Loc::getDefaultLang(LANGUAGE_ID);
					if (!empty($langList[LANGUAGE_ID]))
					{
						$text = $langList[LANGUAGE_ID];
					}
					elseif (!empty($langList[$defaultLang]))
					{
						$text = $langList[$defaultLang];
					}
					elseif (!empty($langList['en']))
					{
						$text = $langList['en'];
					}
					elseif (count($langList) > 0)
					{
						$text = reset($langList);
					}
				}
			}
			else
			{
				$text = $appInfo['MENU_NAME'];
				if ($text == '')
				{
					$text = $appInfo['MENU_NAME_DEFAULT'];
				}
				if ($text == '')
				{
					$text = $appInfo['MENU_NAME_LICENSE'];
				}
			}

			if ($text === '')
			{
				return;
			}

			$menuItem = array(
				"LINK" => \CRestUtil::getApplicationPage($appInfo['ID']),
				"TEXT" => $text,
				"ADDITIONAL_LINKS" => array(
					\CRestUtil::getApplicationPage($appInfo['ID'], 'CODE'),
				),
			);
			$menuItem["ID"] = crc32($menuItem["LINK"]);

			$adminOption = COption::GetOptionString("intranet", "left_menu_items_marketplace_".SITE_ID);

			if (!empty($adminOption))
			{
				$itemExists = false;
				$adminOption = unserialize($adminOption, ["allowed_classes" => false]);

				foreach ($adminOption as $key => $item)
				{
					if ($item["ID"] == $menuItem["ID"])
					{
						if ($item["TEXT"] == $menuItem["TEXT"])
						{
							return;
						}
						else
						{
							$itemExists = true;
							$adminOption[$key]["TEXT"] = $menuItem["TEXT"];
							break;
						}
					}
				}
				if (!$itemExists && (!isset($params["IS_NEW_APP"]) || !$params["IS_NEW_APP"]))
				{
					$adminOption[] = $menuItem;
				}
			}
			else
			{
				$adminOption = array($menuItem);
			}

			COption::SetOptionString("intranet", "left_menu_items_marketplace_".SITE_ID, serialize($adminOption), false, SITE_ID);

			if (defined("BX_COMP_MANAGED_CACHE"))
			{
				global $CACHE_MANAGER;
				$CACHE_MANAGER->ClearByTag("bitrix24_left_menu");
			}
		}
	}

	public static function onRestAppDelete($params)
	{
		if (!isset($params["APP_ID"]) || !\Bitrix\Main\Loader::includeModule("rest"))
			return;

		$dbRes = \Bitrix\Rest\AppTable::getList(array(
			'filter' => array(
				'=ID' => $params["APP_ID"]
			),
			'select' => array('ID', 'CODE')
		));

		if ($appInfo = $dbRes->fetch())
		{
			$itemId =  crc32(SITE_DIR."marketplace/app/".$appInfo["ID"]."/");
			$itemIdCode =  crc32(SITE_DIR."marketplace/app/".$appInfo["CODE"]."/");

			$adminOption = COption::GetOptionString("intranet", "left_menu_items_marketplace_".SITE_ID);

			if (!empty($adminOption))
			{
				$adminOption = unserialize($adminOption, ["allowed_classes" => false]);
				foreach ($adminOption as $key => $item)
				{
					if ($item["ID"] == $itemId || $item['ID'] == $itemIdCode)
					{
						unset($adminOption[$key]);
						if (empty($adminOption))
						{
							COption::RemoveOption("intranet", "left_menu_items_marketplace_".SITE_ID);
							break;
						}
						else
						{
							COption::SetOptionString("intranet", "left_menu_items_marketplace_".SITE_ID, serialize($adminOption), false, SITE_ID);
						}
					}
				}
			}

			if (defined("BX_COMP_MANAGED_CACHE"))
			{
				global $CACHE_MANAGER;
				$CACHE_MANAGER->ClearByTag("bitrix24_left_menu");
			}
		}
	}

	public static function OnAfterSocServUserAdd($event)
	{
		global $USER;
		$userId = 0;
		$authId = '';
		if ($event instanceof \Bitrix\Main\Entity\Event)
		{
			$fields = $event->getParameter("fields");
			$userId = $fields["USER_ID"];
			$authId = $fields["EXTERNAL_AUTH_ID"];
		}
		elseif (is_array($event))
		{
			$userId = $event["USER_ID"];
			$authId = $event["EXTERNAL_AUTH_ID"];
		}

		if ($userId > 0 && $authId == CSocServBitrix24Net::ID)
		{
			if (is_object($USER) && $USER->isAuthorized() && $userId == $USER->getId())
			{
				$arGroups = $USER->GetUserGroupArray();
			}
			else
			{
				$obUser = new CUser();
				$arGroups = $obUser->GetUserGroup(intval($userId));
			}
			$isAdmin = in_array(1, $arGroups);

			CIntranetInviteDialog::logAction($userId, 'socialservices', 'user_init', $isAdmin? 'is_admin': 'is_user');
		}
	}

	public static function OnAfterUserTypeAdd($arFields)
	{
		global $DB;

		$iblockId = COption::GetOptionInt('intranet', 'iblock_structure', 0);
		if ($iblockId > 0)
		{
			if($arFields['ENTITY_ID'] === "IBLOCK_{$iblockId}_SECTION" && $arFields['FIELD_NAME'] === "UF_HEAD")
			{
				if(!$DB->IndexExists("b_uts_iblock_{$iblockId}_section", ["UF_HEAD"], true))
				{
					$DB->Query("CREATE INDEX ix_uts_iblock_section_uf_head ON b_uts_iblock_{$iblockId}_section(UF_HEAD)");
				}
			}
		}
	}
}
