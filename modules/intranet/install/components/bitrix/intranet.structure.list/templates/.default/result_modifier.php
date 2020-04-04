<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

TrimArr($arParams['USER_PROPERTY']);

$arParams['NAME_TEMPLATE'] = $arParams['NAME_TEMPLATE'] ? $arParams['NAME_TEMPLATE'] : CSite::GetNameFormat(false);

$arResult['USER_PROP'] = array();

$arResult['USER_PROP'] = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("USER", 0, LANGUAGE_ID);

$arResult['CAN_EDIT_USER'] = $USER->CanDoOperation('edit_all_users');

if (isset($arResult['USERS']) && is_array($arResult['USERS']))
{
	foreach ($arResult['USERS'] as $i => $arUser)
	{
		$arUser['CAN_MESSAGE'] = false;
		$arUser['CAN_VIDEO_CALL'] = false;
		if (CModule::IncludeModule('socialnetwork') && $GLOBALS["USER"]->IsAuthorized())
		{
			$arUser["CurrentUserPerms"] = CSocNetUserPerms::InitUserPerms($GLOBALS["USER"]->GetID(), $arUser["ID"], CSocNetUser::IsCurrentUserModuleAdmin());

			if (
				($GLOBALS["USER"]->GetID() != $arUser["ID"])
				&& (IsModuleInstalled("im") || $arUser["CurrentUserPerms"]["Operations"]["message"])
			)
				$arUser['CAN_MESSAGE'] = true;

			if (
				($GLOBALS["USER"]->GetID() != $arUser["ID"])
				&& $arUser["CurrentUserPerms"]["Operations"]["videocall"]
			)
				$arUser['CAN_VIDEO_CALL'] = true;

			if(!CModule::IncludeModule("video"))
				$arUser['CAN_VIDEO_CALL'] = false;
			elseif(!CVideo::CanUserMakeCall())
				$arUser['CAN_VIDEO_CALL'] = false;
		}

		$arUser["Urls"] = array("VideoCall" => CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_VIDEO_CALL"], array("user_id" => $arUser["ID"], "USER_ID" => $arUser["ID"], "ID" => $arUser["ID"])));

		$arResult['USERS'][$i] = $arUser;
	}
}


if (!CModule::IncludeModule('extranet') || !CExtranet::IsExtranetSite())
{
	if ($arResult['bAdmin']):
		global $INTRANET_TOOLBAR;
		
		__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

		$current_dep = (intval($_REQUEST['structure_UF_DEPARTMENT']) > 0? '&def_UF_DEPARTMENT='.intval($_REQUEST['structure_UF_DEPARTMENT']) : '');

		$INTRANET_TOOLBAR->AddButton(array(
				'ONCLICK' => $APPLICATION->GetPopupLink(array(
				'URL' => "/bitrix/admin/user_edit.php?lang=".LANGUAGE_ID."&bxpublic=Y&from_module=main".$current_dep,
				'PARAMS' => array(
					'height' => 500,
					'width' => 900,
					'resize' => false
				)
			)),
			"TEXT" => GetMessage('INTR_ABSC_TPL_ADD_ENTRY'),
			"ICON" => 'add',
			"SORT" => 1000,
		));

		if ($USER->CanDoOperation('edit_all_users'))
		{
			$INTRANET_TOOLBAR->AddButton(array(
				'HREF' => "/bitrix/admin/user_import.php?lang=".LANGUAGE_ID,
				"TEXT" => GetMessage('INTR_ABSC_TPL_IMPORT'),
				'ICON' => 'import-users',
				"SORT" => 1100,
			));
		}

		$INTRANET_TOOLBAR->AddButton(array(
			'HREF' => "/bitrix/admin/user_admin.php?lang=".LANGUAGE_ID,
			"TEXT" => GetMessage('INTR_ABSC_TPL_EDIT_ENTRIES'),
			'ICON' => 'settings',
			"SORT" => 1100,
		));
	endif;
}
?>