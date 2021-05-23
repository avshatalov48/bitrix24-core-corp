<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if ($arParams['MEETING_ID'] > 0 && $arParams['FORUM_ID'] > 0)
{
?>
<span class="meeting-new-agenda-title"><?=GetMessage("ME_COMMENTS")?></span>
<div style="clear: both;">
<?
	$APPLICATION->IncludeComponent("bitrix:forum.comments", ".default", array(
		"FORUM_ID" => $arParams['FORUM_ID'],
		"ENTITY_TYPE" => MEETING_COMMENTS_ENTITY_TYPE,
		"ENTITY_ID" => $arParams['MEETING_ID'],
		"ENTITY_XML_ID" => "MEETING_".$arParams['MEETING_ID'],
		"URL_TEMPLATES_PROFILE_VIEW" => COption::GetOptionString('intranet', 'path_user', '/company/personal/user/#USER_ID#/', SITE_ID),
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "36000",
		"MESSAGES_PER_PAGE" => "50",
		"PAGE_NAVIGATION_TEMPLATE" => "",
		"DATE_TIME_FORMAT" =>  CDatabase::DateFormatToPHP(FORMAT_DATETIME),
		"PATH_TO_SMILE" => "/bitrix/images/forum/smile/",
		"EDITOR_CODE_DEFAULT" => "N",
		"SHOW_MODERATION" => "Y",
		"SHOW_AVATAR" => "Y",
		"SHOW_RATING" => "Y",
		"SHOW_MINIMIZED" => "Y",
		"USE_CAPTCHA" => "N",
		"PREORDER" => "N",
		"SHOW_LINK_TO_FORUM" => "N",
		"SHOW_SUBSCRIBE" => "N",
		"FILES_COUNT" => 10,
		"AUTOSAVE" => false,
		"PERMISSION" => "M",
		"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
		"BIND_VIEWER" => "Y",
		),
		null, array('HIDE_ICONS' => 'Y')
	);
}
?>
</div>