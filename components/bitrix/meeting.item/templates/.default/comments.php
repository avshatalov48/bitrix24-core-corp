<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if ($arParams['FORUM_ID'] > 0)
{
	if ($arParams['MINIMAL'])
	{
		?><script type="text/javascript">
			if (!window.onForumImageLoad)
			{
				BX.loadCSS('<?=CUtil::GetAdditionalFileURL('/bitrix/components/bitrix/forum.comments/templates/.default/style.css', true)?>');
				BX.loadScript('<?=CUtil::GetAdditionalFileURL('/bitrix/components/bitrix/forum.comments/templates/.default/script.js', true)?>');
			}
			else
			{
				window.onForumImagesLoad();
			}
		</script><?
	}

	$APPLICATION->IncludeComponent("bitrix:forum.comments", ".default", array(
		"FORUM_ID" => $arParams['FORUM_ID'],
		"ENTITY_TYPE" => MEETING_ITEMS_COMMENTS_ENTITY_TYPE,
		"ENTITY_ID" => $arResult['ITEM']['ID'],
		"ENTITY_XML_ID" => "MEETING_ITEM_".$arResult['ITEM']['ID'],
		"URL_TEMPLATES_PROFILE_VIEW" => COption::GetOptionString('intranet', 'path_user', '/company/personal/user/#USER_ID#/', SITE_ID),
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "36000",
		"MESSAGES_PER_PAGE" => "50",
		"PAGE_NAVIGATION_TEMPLATE" => "",
		"DATE_TIME_FORMAT" => CDatabase::DateFormatToPHP(FORMAT_DATETIME),
		"PATH_TO_SMILE" => "/bitrix/images/forum/smile/",
		"EDITOR_CODE_DEFAULT" => "N",
		"SHOW_MODERATION" => "Y",
		"SHOW_AVATAR" => "Y",
		"SHOW_RATING" => $arParams['MINIMAL'] ? "N" : "Y",
		"SHOW_MINIMIZED" => "N",
		"USE_CAPTCHA" => "N",
		"PREORDER" => "N",
		"SHOW_LINK_TO_FORUM" => "N",
		"SHOW_SUBSCRIBE" => "N",
		"FILES_COUNT" => 10,
//		"AJAX_MODE" => 'Y',
		"AJAX_OPTION_HISTORY" => $arParams['MINIMAL'] ? "N" : "Y",
		"AJAX_OPTION_ADDITIONAL" => "MEETING_ITEM_".$arResult['ITEM']['ID']."_".$arParams['COMMENTS'],
		"SHOW_WYSIWYG_EDITOR" => $arParams['MINIMAL'] ? "N" : "Y",
		"AUTOSAVE" => $arParams['MINIMAL'] ? false : true,
		"PERMISSION" => "M",
		"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
		"BIND_VIEWER" => "Y",
		),
		null, array('HIDE_ICONS' => 'Y')
	);
}
?>