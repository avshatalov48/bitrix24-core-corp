<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */
/** @global CCacheManager $CACHE_MANAGER */

global $CACHE_MANAGER;

use Bitrix\Socialnetwork\Livefeed;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/socialnetwork.log.entry/include.php");

if (!Loader::includeModule("socialnetwork"))
{
	ShowError(GetMessage("SONET_MODULE_NOT_INSTALL"));
	return;
}

if (
	!isset($arParams["LOG_ID"])
	|| intval($arParams["LOG_ID"]) <= 0
)
{
	return;
}

if (
	!isset($arParams["IND"]) 
	|| strlen($arParams["IND"]) <= 0
)
{
	$arParams["IND"] = RandString(8);
}

if (empty($arParams["LOG_PROPERTY"]))
{
	$arParams["LOG_PROPERTY"] = array("UF_SONET_LOG_FILE");
	if (IsModuleInstalled("webdav")  || IsModuleInstalled("disk"))
	{
		$arParams["LOG_PROPERTY"][] = "UF_SONET_LOG_DOC";
	}
}

if (empty($arParams["COMMENT_PROPERTY"]))
{
	$arParams["COMMENT_PROPERTY"] = array("UF_SONET_COM_FILE");
	if (IsModuleInstalled("webdav") || IsModuleInstalled("disk"))
		$arParams["COMMENT_PROPERTY"][] = "UF_SONET_COM_DOC";

	$arParams["COMMENT_PROPERTY"][] = "UF_SONET_COM_URL_PRV";
}

if (empty($arParams["PATH_TO_LOG_TAG"]))
{
	$folderUsers = COption::GetOptionString("socialnetwork", "user_page", false, SITE_ID);
	$arParams["PATH_TO_LOG_TAG"] = $folderUsers."log/?TAG=#tag#";
	if (SITE_TEMPLATE_ID == 'bitrix24')
	{
		$arParams["PATH_TO_LOG_TAG"] .= "&apply_filter=Y";
	}
}

CSocNetLogComponent::processDateTimeFormatParams($arParams);

if (isset($arParams["CURRENT_PAGE_DATE"]))
	$current_page_date = $arParams["CURRENT_PAGE_DATE"];

$bCurrentUserIsAdmin = CSocNetUser::IsCurrentUserModuleAdmin();

$arParams["COMMENT_ID"] = IntVal($arParams["COMMENT_ID"]);

$arResult["TZ_OFFSET"] = CTimeZone::GetOffset();
$arResult["LAST_LOG_TS"] = intval($arParams["LAST_LOG_TS"]);
$arResult["COUNTER_TYPE"] = $arParams["COUNTER_TYPE"];
$arResult["AJAX_CALL"] = $arParams["AJAX_CALL"];
$arResult["bReload"] = $arParams["bReload"];
$arResult["bGetComments"] = $arParams["bGetComments"];
$arResult["bIntranetInstalled"] = ModuleManager::isModuleInstalled("intranet");

$arResult["bPublicPage"] = (isset($arParams["PUB"]) && $arParams["PUB"] == "Y");

$arResult["bTasksInstalled"] = Loader::includeModule("tasks");
$arResult["bTasksAvailable"] = (
	!$arResult["bPublicPage"]
	&& $arResult["bTasksInstalled"]
	&& (
		!Loader::includeModule('bitrix24')
		|| CBitrix24BusinessTools::isToolAvailable($USER->getId(), "tasks")
	)
	&& CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_USER, $USER->getId(), "tasks", "create_tasks")
);

$arResult["Event"] = false;
$arCurrentUserSubscribe = array("TRANSPORT" => array());

$arEvent = __SLEGetLogRecord($arParams["LOG_ID"], $arParams, $arCurrentUserSubscribe, $current_page_date);
if ($arEvent)
{
	$contentId = Livefeed\Provider::getContentId($arEvent['EVENT']);

	$arResult["canGetCommentContent"] = false;
	$arResult["POST_CONTENT_TYPE_ID"] = false;
	$arResult["COMMENT_CONTENT_TYPE_ID"] = false;

	if (
		!empty($contentId['ENTITY_TYPE'])
		&& ($postProvider = \Bitrix\Socialnetwork\Livefeed\Provider::getProvider($contentId['ENTITY_TYPE']))
	)
	{
		$postProviderClassName = get_class($postProvider);
		$reflectionClass = new ReflectionClass($postProviderClassName);
		$arResult["canGetPostContent"] = ($reflectionClass->getMethod('initSourceFields')->class == $postProviderClassName);
		if ($arResult["canGetPostContent"])
		{
			$arResult["POST_CONTENT_TYPE_ID"] = $postProvider->getContentTypeId();
			$arResult["POST_CONTENT_ID"] = $contentId['ENTITY_ID'];
		}

		if ($commentProvider = $postProvider->getCommentProvider())
		{
			$commentProviderClassName = get_class($commentProvider);
			$reflectionClass = new ReflectionClass($commentProviderClassName);

			$arResult["canGetCommentContent"] = (
//				false &&
				$reflectionClass->getMethod('initSourceFields')->class == $commentProviderClassName
			);
			if ($arResult["canGetCommentContent"])
			{
				$arResult["COMMENT_CONTENT_TYPE_ID"] = $commentProvider->getContentTypeId();
			}

			$commentProvider->setLogEventId($arEvent['EVENT']['EVENT_ID']);
			$suffix = $commentProvider->getSuffix();
			if (!empty($suffix))
			{
				$arParams['COMMENT_ENTITY_SUFFIX'] = $suffix;
			}
		}
	}

	if (
		isset($arEvent["HAS_COMMENTS"])
		&& $arEvent["HAS_COMMENTS"] == "Y"
	)
	{
		$commentEvent = CSocNetLogTools::FindLogCommentEventByLogEventID($arEvent["EVENT"]["EVENT_ID"]);
		if (
			!empty($commentEvent)
			&& isset($commentEvent["METHOD_GET_URL"])
			&& is_callable($commentEvent["METHOD_GET_URL"])
		)
		{
			$arResult["COMMENT_URL"] = call_user_func_array($commentEvent["METHOD_GET_URL"], array(array(
				"ENTRY_ID" => $arEvent["EVENT"]["SOURCE_ID"],
				"ENTRY_USER_ID" => $arEvent["EVENT"]["USER_ID"]
			)));
		}
		else
		{
			$arResult["COMMENT_URL"] = false;
		}

		$cache_time = 31536000;

		if ($arParams["COMMENT_ID"] <= 0)
		{
			$cache = new CPHPCache;
		}

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
			$arCacheID[$param_key] = (
				array_key_exists($param_key, $arParams)
					? $arParams[$param_key]
					: false
			);
		}

		$nTopCount = 20;

		$cache_id = "log_comments_".$arParams["LOG_ID"]."_".md5(serialize($arCacheID))."_".SITE_TEMPLATE_ID."_".SITE_ID."_".LANGUAGE_ID."_".FORMAT_DATETIME."_".$arResult["TZ_OFFSET"]."_".$nTopCount;
		$cache_path = "/sonet/log/".intval(intval($arParams["LOG_ID"]) / 1000)."/".$arParams["LOG_ID"]."/comments/";

		if (
			is_object($cache)
			&& $cache->InitCache($cache_time, $cache_id, $cache_path)
		)
		{
			$arCacheVars = $cache->GetVars();
			$arCommentsFullList = $arCacheVars["COMMENTS_FULL_LIST"];

			if (!empty($arCacheVars["Assets"]))
			{
				if (!empty($arCacheVars["Assets"]["CSS"]))
				{
					foreach($arCacheVars["Assets"]["CSS"] as $cssFile)
					{
						\Bitrix\Main\Page\Asset::getInstance()->addCss($cssFile);
					}
				}

				if (!empty($arCacheVars["Assets"]["JS"]))
				{
					foreach($arCacheVars["Assets"]["JS"] as $jsFile)
					{
						\Bitrix\Main\Page\Asset::getInstance()->addJs($jsFile);
					}
				}
			}
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
				$CACHE_MANAGER->startTagCache($cache_path);
			}

			$arFilter = array(
				"LOG_ID" => $arParams["LOG_ID"]
			);

			if ($arParams["COMMENT_ID"] > 0)
			{
				$logCommentId = $arParams["COMMENT_ID"];
				if (!empty($commentEvent))
				{
					$rsLogComment = CSocNetLogComments::getList(
						array(),
						array(
							"EVENT_ID" => $commentEvent['EVENT_ID'],
							"SOURCE_ID" => $arParams["COMMENT_ID"]
						),
						false,
						false,
						array('ID')
					);

					if ($arLogComment = $rsLogComment->Fetch())
					{
						$logCommentId = $arLogComment["ID"];
					}
				}

				$arFilter[">=ID"] = $logCommentId;
			}

			$arSelect = array(
				"ID", "LOG_ID", "SOURCE_ID", "ENTITY_TYPE", "ENTITY_ID", "USER_ID", "EVENT_ID", "LOG_DATE", "MESSAGE", "LOG_DATE_TS", "TEXT_MESSAGE", "URL", "MODULE_ID",
				"GROUP_NAME", "GROUP_OWNER_ID", "GROUP_VISIBLE", "GROUP_OPENED", "GROUP_IMAGE_ID",
				"USER_NAME", "USER_LAST_NAME", "USER_SECOND_NAME", "USER_LOGIN", "USER_PERSONAL_PHOTO", "USER_PERSONAL_GENDER",
				"CREATED_BY_NAME", "CREATED_BY_LAST_NAME", "CREATED_BY_SECOND_NAME", "CREATED_BY_LOGIN", "CREATED_BY_PERSONAL_PHOTO", "CREATED_BY_PERSONAL_GENDER", "CREATED_BY_EXTERNAL_AUTH_ID",
				"SHARE_DEST",
				"LOG_SITE_ID", "LOG_SOURCE_ID",
				"RATING_TYPE_ID", "RATING_ENTITY_ID",
				"UF_*"
			);

			$arListParams = array(
				"USE_SUBSCRIBE" => "N",
				"CHECK_RIGHTS" => "N"
			);

			$arUFMeta = __SLGetUFMeta();
			$arNavParams = (
				$arParams["COMMENT_ID"] <= 0
					? array("nTopCount" => $nTopCount)
					: false
			);

			$arAssets = array(
				"CSS" => array(),
				"JS" => array()
			);

			$dbComments = CSocNetLogComments::getList(
				array("LOG_DATE" => "DESC"), // revert then
				$arFilter,
				false,
				$arNavParams,
				$arSelect,
				$arListParams
			);

			if (
				!empty($arEvent["EVENT_FORMATTED"])
				&& !empty($arEvent["EVENT_FORMATTED"]["DESTINATION"])
				&& is_array($arEvent["EVENT_FORMATTED"]["DESTINATION"])
			)
			{
				foreach($arEvent["EVENT_FORMATTED"]["DESTINATION"] as $destination)
				{
					if (!empty($destination["CRM_USER_ID"]))
					{
						$arParams["ENTRY_HAS_CRM_USER"] = true;
						break;
					}
				}
			}

			$commentsList = $commentSourceIdList = array();
			while($arComment = $dbComments->getNext())
			{
				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->registerTag("USER_NAME_".intval($arComment["USER_ID"]));
				}

				$arComment["UF"] = $arUFMeta;
				foreach($arUFMeta as $field_name => $arUF)
				{
					if (array_key_exists($field_name, $arComment))
					{
						$arComment["UF"][$field_name]["VALUE"] = $arComment[$field_name];
						$arComment["UF"][$field_name]["ENTITY_VALUE_ID"] = $arComment["ID"];
					}
				}
				$commentsList[] = $arComment;
				if (intval($arComment['SOURCE_ID']) > 0)
				{
					$commentSourceIdList[] = intval($arComment['SOURCE_ID']);
				}
			}

			if (
				!empty($commentSourceIdList)
				&& !empty($commentProvider)
			)
			{
				$sourceAdditonalData = $commentProvider->getAdditionalData(array(
					'id' => $commentSourceIdList
				));

				if (!empty($sourceAdditonalData))
				{
					foreach($commentsList as $key => $comment)
					{
						if (
							!empty($comment['SOURCE_ID'])
							&& isset($sourceAdditonalData[$comment['SOURCE_ID']])
						)
						{
							$commentsList[$key]['ADDITIONAL_DATA'] = $sourceAdditonalData[$comment['SOURCE_ID']];
						}
					}
				}
			}

			foreach($commentsList as $arComment)
			{
				$arCommentsFullList[] = __SLEGetLogCommentRecord($arComment, $arParams, $arAssets);
			}

			if (is_object($cache))
			{
				$arCacheData = Array(
					"COMMENTS_FULL_LIST" => $arCommentsFullList,
					"Assets" => $arAssets
				);
				$cache->EndDataCache($arCacheData);
				if(defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->endTagCache();
				}
			}
		}

		$arCommentsFullListCut = array();
		$arCommentID = array();

		$handlerManager = new Bitrix\Socialnetwork\CommentAux\HandlerManager();

		foreach ($arCommentsFullList as $key => $arCommentTmp)
		{
			if ($key === 0)
			{
				$rating_entity_type = $arCommentTmp["EVENT"]["RATING_TYPE_ID"];
			}

			if (
				isset($arCommentTmp['EVENT_FORMATTED'])
				&& isset($arCommentTmp['EVENT_FORMATTED']['MESSAGE'])
				&& ($handler = $handlerManager->getHandlerByPostText($arCommentTmp['EVENT_FORMATTED']['MESSAGE']))
			)
			{
				if ($handler->checkRecalcNeeded($arCommentTmp['EVENT'], array(
					'bPublicPage' => $arResult["bPublicPage"]
				)))
				{
					$commentAuxFields = $arCommentTmp['EVENT'];
					$params = $handler->getParamsFromFields($commentAuxFields);
					if (!empty($params))
					{
						$handler->setParams($params);
					}

					$handler->setOptions(array(
						'mobile' => false,
						'bPublicPage' => (isset($arParams["bPublicPage"]) && $arParams["bPublicPage"]),
						'cache' => false,
						'suffix' => (!empty($arParams['COMMENT_ENTITY_SUFFIX']) ? $arParams['COMMENT_ENTITY_SUFFIX'] : ''),
						'logId' => $arParams["LOG_ID"],
					));
					$arCommentTmp['EVENT_FORMATTED']['FULL_MESSAGE_CUT']  = $handler->getText();
					$arCommentTmp["AUX"] = $handler->getType();
				}
			}

			if (
				$arResult["bGetComments"]
				&& intval($arParams["CREATED_BY_ID"]) > 0
			)
			{
				if ($arCommentTmp["EVENT"]["USER_ID"] == $arParams["CREATED_BY_ID"])
				{
					$arCommentsFullListCut[] = $arCommentTmp;
				}
			}
			else
			{
				$event_date_log_ts = (
					isset($arCommentTmp["EVENT"]["LOG_DATE_TS"])
						? $arCommentTmp["EVENT"]["LOG_DATE_TS"]
						: (MakeTimeStamp($arCommentTmp["EVENT"]["LOG_DATE"]) - intval($arResult["TZ_OFFSET"]))
				);

				if (
					$arParams["COMMENT_ID"] <= 0
					&& (
						(
							$event_date_log_ts > $arResult["LAST_LOG_TS"]
							&& $key >= $nTopCount
						) // new comments, no more than 20
						|| (
							(
								$event_date_log_ts <= $arResult["LAST_LOG_TS"]
								|| $arResult["LAST_LOG_TS"] <= 0
							)
							&& $key >= $arParams["COMMENTS_IN_EVENT"]
						) // old comments, no more than 3
					)
				)
				{
				}
				else
				{
					$arCommentsFullListCut[] = $arCommentTmp;
				}
			}

			$arCommentID[] = $arCommentTmp["EVENT"]["RATING_ENTITY_ID"];
		}

		$arCommentRights = CSocNetLogComponent::getCommentRights(array(
			"EVENT_ID" => $arEvent["EVENT"]["EVENT_ID"],
			"SOURCE_ID" => $arEvent["EVENT"]["SOURCE_ID"],
			"USER_ID" => $USER->getId()
		));
		$arResult["COMMENT_RIGHTS_EDIT"] = $arCommentRights["COMMENT_RIGHTS_EDIT"];
		$arResult["COMMENT_RIGHTS_DELETE"] = $arCommentRights["COMMENT_RIGHTS_DELETE"];

		$arEvent["COMMENTS"] = array_reverse($arCommentsFullListCut);
		$arResult["RATING_COMMENTS"] = array();
		if(
			!empty($arCommentID)
			&& $arParams["SHOW_RATING"] == "Y"
			&& strlen($rating_entity_type) > 0
		)
		{
			$arResult["RATING_COMMENTS"] = CRatings::GetRatingVoteResult($rating_entity_type, $arCommentID);
		}
	}

	$liveFeedEntity = Livefeed\Provider::init(array(
		'ENTITY_TYPE' => $contentId['ENTITY_TYPE'],
		'ENTITY_ID' => $contentId['ENTITY_ID'],
		'LOG_ID' => $arEvent["EVENT"]["ID"]
	));

	if (
		(
			isset($arParams["FROM_LOG"])
			&& $arParams["FROM_LOG"] == 'N'
		)
		&& !empty($arEvent["EVENT"])
		&& $contentId
	)
	{
		if ($liveFeedEntity)
		{
			$liveFeedEntity->setContentView();
		}
	}

	if (
		$liveFeedEntity
		&& $contentId
	)
	{
		$arResult["CONTENT_ID"] = (!empty($arParams["CONTENT_ID"]) ? $arParams["CONTENT_ID"] : $contentId['ENTITY_TYPE'].'-'.intval($contentId['ENTITY_ID']));

		if (isset($arParams["CONTENT_VIEW_CNT"]))
		{
			$arResult["CONTENT_VIEW_CNT"] = intval($arParams["CONTENT_VIEW_CNT"]);
		}
		else
		{
			if (
				($contentViewData = \Bitrix\Socialnetwork\Item\UserContentView::getViewData(array(
					'contentId' => array($arResult["CONTENT_ID"])
				)))
				&& !empty($contentViewData[$arResult["CONTENT_ID"]])
			)
			{
				$arResult["CONTENT_VIEW_CNT"] = intval($contentViewData[$arResult["CONTENT_ID"]]["CNT"]);
			}
			else
			{
				$arResult["CONTENT_VIEW_CNT"] = 0;
			}
		}
	}
}
else
{
	return;
}

$arResult["Event"] = $arEvent;
$arResult["WORKGROUPS_PAGE"] = COption::GetOptionString("socialnetwork", "workgroups_page", "/workgroups/", SITE_ID);

$arResult["GET_COMMENTS"] = ($bGetComments ? "Y" : "N");

$this->IncludeComponentTemplate();
?>