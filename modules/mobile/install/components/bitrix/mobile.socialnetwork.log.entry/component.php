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

use Bitrix\Socialnetwork\Livefeed;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Loader;

global $CACHE_MANAGER;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/mobile.socialnetwork.log.entry/include.php");

if (!CModule::IncludeModule("socialnetwork"))
{
	ShowError(GetMessage("SONET_MODULE_NOT_INSTALL"));
	return;
}

if (!$USER->IsAuthorized())
{
	ShowError(GetMessage("SONET_SLM_NOT_AUTHORIZED"));
	return;
}

$arParams["PATH_TO_USER"] = trim($arParams["PATH_TO_USER"]);
$arParams["PATH_TO_GROUP"] = trim($arParams["PATH_TO_GROUP"]);
$arParams["PATH_TO_SMILE"] = trim($arParams["PATH_TO_SMILE"]);
if (strlen($arParams["PATH_TO_SMILE"]) <= 0)
	$arParams["PATH_TO_SMILE"] = "/bitrix/images/socialnetwork/smile/";

$arParams["GROUP_ID"] = IntVal($arParams["GROUP_ID"]); // group page
$arParams["USER_ID"] = IntVal($arParams["USER_ID"]); // profile page
$arParams["LOG_ID"] = IntVal($arParams["LOG_ID"]); // log entity page

$arResult["LAST_LOG_TS"] = intval($arParams["LAST_LOG_TS"]);
$arResult["COUNTER_TYPE"] = $arParams["COUNTER_TYPE"];

if ($arParams["LOG_ID"] <= 0)
	return false;

$arParams["NAME_TEMPLATE"] = $arParams["NAME_TEMPLATE"] ? $arParams["NAME_TEMPLATE"] : CSite::GetNameFormat();
$arParams["NAME_TEMPLATE_WO_NOBR"] = str_replace(
	array("#NOBR#", "#/NOBR#"),
	array("", ""),
	$arParams["NAME_TEMPLATE"]
);
$arParams["NAME_TEMPLATE"] = $arParams["NAME_TEMPLATE_WO_NOBR"];
$bUseLogin = $arParams["SHOW_LOGIN"] != "N" ? true : false;

$arParams["AVATAR_SIZE"] = (isset($arParams["AVATAR_SIZE"]) ? intval($arParams["AVATAR_SIZE"]) : 100);
$arParams["AVATAR_SIZE_COMMENT"] = (isset($arParams["AVATAR_SIZE_COMMENT"]) ? intval($arParams["AVATAR_SIZE_COMMENT"]) : 100);

$arParams["DESTINATION_LIMIT"] = (isset($arParams["DESTINATION_LIMIT"]) ? intval($arParams["DESTINATION_LIMIT"]) : 3);
$arParams["COMMENTS_IN_EVENT"] = (isset($arParams["COMMENTS_IN_EVENT"]) && intval($arParams["COMMENTS_IN_EVENT"]) > 0 ? $arParams["COMMENTS_IN_EVENT"] : "3");

$arResult["FOLLOW_DEFAULT"] = ($arParams["FOLLOW_DEFAULT"] == "N" ? "N" : "Y");

if (intval($arParams["PHOTO_COUNT"]) <= 0)
	$arParams["PHOTO_COUNT"] = 5;
if (intval($arParams["PHOTO_THUMBNAIL_SIZE"]) <= 0)
	$arParams["PHOTO_THUMBNAIL_SIZE"] = 76;

$arResult["TZ_OFFSET"] = CTimeZone::GetOffset();
$arResult["WORKGROUPS_PAGE"] = COption::GetOptionString("socialnetwork", "workgroups_page", "/workgroups/", SITE_ID);

$arResult["bIntranetInstalled"] = ModuleManager::isModuleInstalled("intranet");
$arResult["bTasksInstalled"] = Loader::includeModule("tasks");
$arResult["bTasksAvailable"] = (
	$arResult["bTasksInstalled"]
	&& (
		!Loader::includeModule('bitrix24')
		|| CBitrix24BusinessTools::isToolAvailable($USER->getId(), "tasks")
	)
);

if (isset($arParams["CURRENT_PAGE_DATE"]))
	$current_page_date = $arParams["CURRENT_PAGE_DATE"];

$arEvent = __SLMGetLogRecord($arParams["LOG_ID"], $arParams, $current_page_date);

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

			$arResult["canGetCommentContent"] = ($reflectionClass->getMethod('initSourceFields')->class == $commentProviderClassName);
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
			if (array_key_exists($param_key, $arParams))
				$arCacheID[$param_key] = $arParams[$param_key];
			else
				$arCacheID[$param_key] = false;
		}

		$nTopCount = 20;

		$cache_id = "log_comments_".$arParams["LOG_ID"]."_".md5(serialize($arCacheID))."_".SITE_TEMPLATE_ID."_".SITE_ID."_".LANGUAGE_ID."_".FORMAT_DATETIME."_".CTimeZone::GetOffset()."_".$nTopCount;
		$cache_path = "/sonet/log/".intval(intval($arParams["LOG_ID"]) / 1000)."/".$arParams["LOG_ID"]."/comments/";

		if (
			is_object($cache)
			&& $cache->InitCache($cache_time, $cache_id, $cache_path)
		)
		{
			$arCacheVars = $cache->GetVars();
			$arCommentsFullList = $arCacheVars["COMMENTS_FULL_LIST"];
		}
		else
		{
			$arCommentsFullList = array();

			if (is_object($cache))
				$cache->StartDataCache($cache_time, $cache_id, $cache_path);

			if (defined("BX_COMP_MANAGED_CACHE"))
			{
				$CACHE_MANAGER->StartTagCache($cache_path);
			}

			$arFilter = array(
				"LOG_ID" => $arParams["LOG_ID"]
			);

			$arSelect = array(
				"ID", "LOG_ID", "SOURCE_ID", "ENTITY_TYPE", "ENTITY_ID", "USER_ID", "EVENT_ID", "LOG_DATE", "MESSAGE", "TEXT_MESSAGE", "URL", "MODULE_ID",
				"GROUP_NAME", "GROUP_OWNER_ID", "GROUP_VISIBLE", "GROUP_OPENED", "GROUP_IMAGE_ID",
				"USER_NAME", "USER_LAST_NAME", "USER_SECOND_NAME", "USER_LOGIN", "USER_PERSONAL_PHOTO", "USER_PERSONAL_GENDER",
				"CREATED_BY_NAME", "CREATED_BY_LAST_NAME", "CREATED_BY_SECOND_NAME", "CREATED_BY_LOGIN", "CREATED_BY_PERSONAL_PHOTO", "CREATED_BY_PERSONAL_GENDER",
				"SHARE_DEST",
				"LOG_SITE_ID", "LOG_SOURCE_ID",
				"RATING_TYPE_ID", "RATING_ENTITY_ID",
				"UF_*"
			);

			$arListParams = array(
				"USE_SUBSCRIBE" => "N",
				"CHECK_RIGHTS" => "N"
			);

			$arUFMeta = __SLMGetUFMeta();

			$dbComments = CSocNetLogComments::GetList(
				array("LOG_DATE" => "DESC"), // revert then
				$arFilter,
				false,
				array("nTopCount" => $nTopCount),
				$arSelect,
				$arListParams
			);

			$commentsList = $commentSourceIdList = array();
			while($arComment = $dbComments->GetNext())
			{
				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->RegisterTag("USER_NAME_".intval($arComment["USER_ID"]));
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
				$arCommentsFullList[] = __SLMGetLogCommentRecord($arComment, $arParams, false);
			}

			if (is_object($cache))
			{
				$arCacheData = Array(
					"COMMENTS_FULL_LIST" => $arCommentsFullList
				);
				$cache->EndDataCache($arCacheData);
				if(defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->EndTagCache();
				}
			}
		}

		$handlerManager = new Bitrix\Socialnetwork\CommentAux\HandlerManager();

		foreach ($arCommentsFullList as $key => $arCommentTmp)
		{
			if (
				isset($arCommentTmp['EVENT_FORMATTED'])
				&& isset($arCommentTmp['EVENT_FORMATTED']['MESSAGE'])
				&& ($handler = $handlerManager->getHandlerByPostText($arCommentTmp['EVENT']['MESSAGE']))
			)
			{
				if ($handler->checkRecalcNeeded($arCommentTmp['EVENT'], array()))
				{
					$commentAuxFields = $arCommentTmp['EVENT'];
					$params = $handler->getParamsFromFields($commentAuxFields);
					if (!empty($params))
					{
						$handler->setParams($params);
					}

					$handler->setOptions(array(
						'mobile' => false,
						'cache' => false,
						'suffix' => (!empty($arParams['COMMENT_ENTITY_SUFFIX']) ? $arParams['COMMENT_ENTITY_SUFFIX'] : ''),
						'logId' => $arParams["LOG_ID"],
					));
					$arCommentTmp['EVENT_FORMATTED']['MESSAGE']  = $handler->getText();
					$arCommentTmp["AUX"] = $handler->getType();
					$arCommentsFullList[$key] = $arCommentTmp;
				}
			}
		}

		$arResult["NEW_COMMENTS"] = 0;

		if (
			$arResult["COUNTER_TYPE"] == "**" 
			|| $arParams["LOG_ID"] > 0
		)
		{
			$arCommentsFullListCut = array();
			$arCommentID = array();


			if (!empty($arCommentsFullList))
			{
				$arCommentRights = CSocNetLogComponent::getCommentRights(array(
					"EVENT_ID" => $arEvent["EVENT"]["EVENT_ID"],
					"SOURCE_ID" => $arEvent["EVENT"]["SOURCE_ID"],
					"USER_ID" => $USER->getId(),
					"CHECK_ADMIN_SESSION" => "N"
				));

				$arResult["COMMENT_RIGHTS_EDIT"] = $arCommentRights["COMMENT_RIGHTS_EDIT"];
				$arResult["COMMENT_RIGHTS_DELETE"] = $arCommentRights["COMMENT_RIGHTS_DELETE"];
			}

			foreach ($arCommentsFullList as $key => $arCommentTmp)
			{
				if ($key === 0)
				{
					$rating_entity_type = $arCommentTmp["EVENT"]["RATING_TYPE_ID"];
				}

				if (
					$key >= $arParams["COMMENTS_IN_EVENT"]
					&& (
						intval($arResult["LAST_LOG_TS"]) <= 0
						|| (
							$arResult["COUNTER_TYPE"] == "**"
							&& (MakeTimeStamp($arCommentTmp["EVENT"]["LOG_DATE"]) - intval($arResult["TZ_OFFSET"])) <= $arResult["LAST_LOG_TS"]
						)
					)
				)
				{
					// 
				}
				else
				{
					if (
						$arResult["COUNTER_TYPE"] == "**"
						&& intval($arResult["LAST_LOG_TS"]) > 0
						&& (MakeTimeStamp($arCommentTmp["EVENT"]["LOG_DATE"]) - intval($arResult["TZ_OFFSET"])) >= $arResult["LAST_LOG_TS"]
						&& $arCommentTmp["EVENT"]["USER_ID"] != $USER->GetID()
					)
					{
						$arResult["NEW_COMMENTS"]++;
					}

					$arCommentsFullListCut[] = $arCommentTmp;
				}
				$arCommentID[] = $arCommentTmp["EVENT"]["RATING_ENTITY_ID"];
			}

			$arEvent["COMMENTS"] = array_reverse($arCommentsFullListCut);

			$arResult["RATING_COMMENTS"] = array();
			if(
				!empty($arCommentID)
				&& $arParams["SHOW_RATING"] == "Y"
				&& strlen($rating_entity_type) > 0
			)
				$arResult["RATING_COMMENTS"] = CRatings::GetRatingVoteResult($rating_entity_type, $arCommentID);
		}
		elseif ($arResult["COUNTER_TYPE"] == "**")
		{
			foreach ($arCommentsFullList as $key => $arCommentTmp)
			{
				if (
					intval($arResult["LAST_LOG_TS"]) > 0
					&& (MakeTimeStamp($arCommentTmp["EVENT"]["LOG_DATE"]) - intval($arResult["TZ_OFFSET"])) >= $arResult["LAST_LOG_TS"]
				)
				{
					$arResult["NEW_COMMENTS"]++;
				}
			}
		}
	}

	$arResult["CONTENT_ID"] = false;
	if (!empty($arParams["CONTENT_ID"]))
	{
		$arResult["CONTENT_ID"] = $arParams["CONTENT_ID"];
	}
	else
	{
		$event = new \Bitrix\Main\Event(
			'mobile',
			'onGetContentId',
			array(
				'logEventFields' => $arEvent['EVENT']
			)
		);
		$event->send();

		foreach($event->getResults() as $eventResult)
		{
			if($eventResult->getType() == \Bitrix\Main\EventResult::SUCCESS)
			{
				$eventParams = $eventResult->getParameters();

				if (
					is_array($eventParams)
					&& isset($eventParams['contentId'])
				)
				{
					$arResult["CONTENT_ID"] = $eventParams['contentId']['ENTITY_TYPE'].'-'.intval($eventParams['contentId']['ENTITY_ID']);
				}
			}
		}
	}

	if (
		isset($arParams["IS_LIST"])
		&& !$arParams["IS_LIST"]
	)
	{
		$event = new \Bitrix\Main\Event(
			'mobile',
			'onSetContentView',
			array(
				'logEventFields' => $arEvent['EVENT']
			)
		);
		$event->send();
	}

}

$arResult["Event"] = $arEvent;

$this->IncludeComponentTemplate();
?>