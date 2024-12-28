<?

use Bitrix\Tasks\Internals\Task\Status;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (
	isset($arParams['TASK']['REAL_STATUS'])
	&& ((int)$arParams['TASK']['REAL_STATUS'] !== Status::DECLINED)
	&& ((int)$arParams['TASK']['REAL_STATUS'] !== Status::SUPPOSEDLY_COMPLETED)
	&& ($arParams['TASK']['REAL_STATUS'] > 0)
	&& ($arParams["TYPE"] === 'status')
)
{
	if (
		isset($arParams['PREV_REAL_STATUS'])
		&& ($arParams['PREV_REAL_STATUS'] !== false)
		&& (
			((int)$arParams['TASK']['REAL_STATUS'] === Status::NEW)
			|| ((int)$arParams['TASK']['REAL_STATUS'] === Status::PENDING)
		)
		&& ((int)$arParams['PREV_REAL_STATUS'] === Status::SUPPOSEDLY_COMPLETED)
	)
	{
		//$message = GetMessage('TASKS_SONET_TASK_STATUS_MESSAGE_REDOED');
		//$message_24 = GetMessage('TASKS_SONET_TASK_STATUS_MESSAGE_REDOED_24');
	}
	else
	{
		//$message = GetMessage("TASKS_SONET_TASK_STATUS_MESSAGE_" . $arParams['TASK']['REAL_STATUS']);
		//$message_24 = GetMessage("TASKS_SONET_TASK_STATUS_MESSAGE_" . $arParams['TASK']['REAL_STATUS'] . '_24');

		if ((int)$arParams['TASK']['REAL_STATUS'] === Status::DECLINED)
		{
			$arParams['~MESSAGE'] = str_replace("#TASK_DECLINE_REASON#", $arParams['TASK']["DECLINE_REASON"], $message);
			$arParams['~MESSAGE_24_1'] = str_replace("#TASK_DECLINE_REASON#", $arParams['TASK']["DECLINE_REASON"], $message_24);
		}
	}

	//$arParams['~MESSAGE'] = $message;
	//$arParams['~MESSAGE_24_1'] = $message_24;
}

if (
	!array_key_exists("AVATAR_SIZE", $arParams)
	||  ((int)$arParams["AVATAR_SIZE"] <= 0)
)
{
	if ($arParams["MOBILE"] === "Y")
	{
		$arParams["AVATAR_SIZE"] = 58;
	}
	else
	{
		$arParams["AVATAR_SIZE"] = 30;
	}
}

if (empty($arParams["NAME_TEMPLATE"]))
{
	$arParams["NAME_TEMPLATE"] = CSite::GetNameFormat();
}

$arResult['PHOTO'] = false;

$rsUser = CUser::GetByID($arParams['TASK']["RESPONSIBLE_ID"]);
if ($arResult['USER'] = $rsUser->Fetch())
{
	if(defined("BX_COMP_MANAGED_CACHE"))
	{
		$GLOBALS["CACHE_MANAGER"]->RegisterTag("USER_NAME_" . intval($arResult["USER"]["ID"]));
	}

	if (!$arResult['USER']['PERSONAL_PHOTO'])
	{
		$suffix = match ($arResult['USER']['PERSONAL_GENDER'])
		{
			"M" => "male",
			"F" => "female",
			default => "unknown",
		};
		$arResult['USER']['PERSONAL_PHOTO'] = COption::GetOptionInt("socialnetwork", "default_user_picture_".$suffix, false, SITE_ID);
	}

	if ($arResult['USER']['PERSONAL_PHOTO'] > 0 && CModule::IncludeModule("intranet"))
	{
		$arResult['PHOTO'] = CIntranetUtils::InitImage($arResult['USER']['PERSONAL_PHOTO'], $arParams["AVATAR_SIZE"], 0, BX_RESIZE_IMAGE_EXACT);
	}

	$arResult['USER']['IS_COLLABER'] =
		Bitrix\Tasks\Integration\Extranet\User::isCollaber((int)$arResult['USER']['ID'])
	;

	$arResult['PATH_TO_USER'] = CComponentEngine::MakePathFromTemplate(($arParams["PATH_TO_USER"] <> '' ? $arParams["PATH_TO_USER"] : COption::GetOptionString('intranet', 'path_user', '/company/personal/user/#USER_ID#/')), array("USER_ID" => $arResult['USER']["ID"], "user_id" => $arResult['USER']["ID"]));
}

if (isset($arParams['TASK']["DESCRIPTION"]) && $arParams['TASK']["DESCRIPTION"])
{
	if (isset($arParams['TASK']["~DESCRIPTION"]) && $arParams['TASK']["~DESCRIPTION"])
	{
		$arParams['TASK']["DESCRIPTION"] = $arParams['TASK']["~DESCRIPTION"];
	}
}

$folderUsers = COption::GetOptionString("socialnetwork", "user_page", false, SITE_ID);
$arResult["PATH_TO_LOG_TAG"] = $folderUsers."log/?TAG=#tag#";
if (defined('SITE_TEMPLATE_ID') && SITE_TEMPLATE_ID === 'bitrix24')
{
	$arResult["PATH_TO_LOG_TAG"] .= "&apply_filter=Y";
}

if ($this->getTemplateName() === 'mobile')
{
	$this->setSiteTemplateId('mobile_app');
}

$this->IncludeComponentTemplate();