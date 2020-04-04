<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arGadgetParams["DETAIL_URL"] = (isset($arGadgetParams["DETAIL_URL"])?$arGadgetParams["DETAIL_URL"]:"/about/gallery/#SECTION_ID#/#ELEMENT_ID#/");
$arGadgetParams["DETAIL_SLIDE_SHOW_URL"] = (isset($arGadgetParams["DETAIL_SLIDE_SHOW_URL"])?$arGadgetParams["DETAIL_SLIDE_SHOW_URL"]:"/about/gallery/#SECTION_ID#/#ELEMENT_ID#/slide_show/");
$arGadgetParams["LIST_URL"] = ($arGadgetParams["LIST_URL"]?$arGadgetParams["LIST_URL"]:"/about/gallery/");
$arGadgetParams["PAGE_ELEMENTS"] = intval($arGadgetParams["PAGE_ELEMENTS"]);
$arGadgetParams["PAGE_ELEMENTS"] = ($arGadgetParams["PAGE_ELEMENTS"]>0 && $arGadgetParams["PAGE_ELEMENTS"]<=50 ? $arGadgetParams["PAGE_ELEMENTS"] : "6");
$arGadgetParams["USE_COMMENTS"] = (isset($arGadgetParams["USE_COMMENTS"]) && $arGadgetParams["USE_COMMENTS"] == "Y") ? "Y" : "N";
?>
<?
$APPLICATION->IncludeComponent(
	"bitrix:photogallery.detail.list.ex",
	"",
	Array(
		"IBLOCK_TYPE"	=>	$arGadgetParams["IBLOCK_TYPE"],
		"IBLOCK_ID"	=>	$arGadgetParams["IBLOCK_ID"],
		"DETAIL_URL" => $arGadgetParams["DETAIL_URL"],
		"DETAIL_SLIDE_SHOW_URL" => $arGadgetParams["DETAIL_SLIDE_SHOW_URL"],
		"PAGE_ELEMENTS" => $arGadgetParams["PAGE_ELEMENTS"],

		"BEHAVIOUR" => "SIMPLE",
		"USER_ALIAS" => "",
		"ELEMENT_LAST_TYPE" => "none",
		"USE_DESC_PAGE" => "N",
		"ELEMENT_SORT_FIELD" => "timestamp_x",
		"ELEMENT_SORT_ORDER" => "desc",
		"SEARCH_URL" => "/about/gallery/search/",
		"CACHE_TYPE" => $arGadgetParams["CACHE_TYPE"],
		"CACHE_TIME" => $arGadgetParams["CACHE_TIME"],
		"PAGE_NAVIGATION_TEMPLATE" => "",
		"USE_PERMISSIONS" => "N",
		"GROUP_PERMISSIONS" => array(0=>"1",1=>"",),
		"COMMENTS_TYPE" => "none",
		"SET_TITLE" => "N",
		"DATE_TIME_FORMAT" => $arParams["DATE_FORMAT"],
		"ADDITIONAL_SIGHTS" => array(),
		"PICTURES_SIGHT" => "",
		"THUMBNAIL_SIZE" => "90",
		"SHOW_PAGE_NAVIGATION" => "none",
		"SHOW_CONTROLS" => "N",
		"SHOW_RATING" => "N",
		"SHOW_SHOWS" => "N",
		"USE_COMMENTS" => $arGadgetParams["USE_COMMENTS"],
		"SHOW_COMMENTS" => $arGadgetParams["USE_COMMENTS"],
		"SHOW_TAGS" => "N",
		"MAX_VOTE" => "5",
		"VOTE_NAMES" => array(0=>"1",1=>"2",2=>"3",3=>"4",4=>"5",5=>""),

		"DRAG_SORT" => "N",
		"MORE_PHOTO_NAV" => "N",
		"PATH_TO_USER" => $arGadgetParams["PATH_TO_USER"],
		"NAME_TEMPLATE" => $arGadgetParams["NAME_TEMPLATE"],
		"SHOW_LOGIN" => $arGadgetParams["SHOW_LOGIN"],
		"RELOAD_ITEMS_ONLOAD" => "Y",

		"COMMENTS_TYPE" => $arGadgetParams["COMMENTS_TYPE"],
		"COMMENTS_COUNT" => $arGadgetParams["COMMENTS_COUNT"],
		"PATH_TO_SMILE" => $arGadgetParams["PATH_TO_SMILE"],
		"FORUM_ID" => $arGadgetParams["FORUM_ID"],
		"USE_CAPTCHA" => $arGadgetParams["USE_CAPTCHA"],
		"BLOG_URL" => $arGadgetParams["BLOG_URL"],
		"PATH_TO_BLOG" => $arGadgetParams["PATH_TO_BLOG"]
	),
	false,
	Array("HIDE_ICONS"=>"Y")
);?>

<?if(strlen($arGadgetParams["LIST_URL"])>0):?>
<br />
<div align="right"><a href="<?=htmlspecialcharsbx($arGadgetParams["LIST_URL"])?>"><?echo GetMessage("GD_PHOTOS_MORE")?></a> <a href="<?=htmlspecialcharsbx($arGadgetParams["LIST_URL"])?>"><img width="7" height="7" border="0" src="/images/icons/arrows.gif" /></a>
<br /></div>
<?endif?>
