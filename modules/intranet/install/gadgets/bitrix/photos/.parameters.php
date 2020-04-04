<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arComponentProps = CComponentUtil::GetComponentProps("bitrix:photogallery.detail.list.ex", $arCurrentValues);
$arComponentProps2 = CComponentUtil::GetComponentProps("bitrix:photogallery", $arCurrentValues);

$arParameters = Array(
		"PARAMETERS"=> Array(
			"IBLOCK_TYPE"	=>	$arComponentProps["PARAMETERS"]["IBLOCK_TYPE"],
			"IBLOCK_ID"	=>	$arComponentProps["PARAMETERS"]["IBLOCK_ID"],
			"LIST_URL"	=> Array(
				"NAME" => GetMessage("GD_PHOTOS_P_URL"),
				"TYPE" => "STRING",
				"MULTIPLE" => "N",
				"DEFAULT" => "/about/gallery/",
			),
			"DETAIL_URL"	=> $arComponentProps["PARAMETERS"]["DETAIL_URL"],
			"DETAIL_SLIDE_SHOW_URL"	=> $arComponentProps["PARAMETERS"]["DETAIL_SLIDE_SHOW_URL"],
			"CACHE_TYPE"=>$arComponentProps["PARAMETERS"]["CACHE_TYPE"],
			"CACHE_TIME"=>$arComponentProps["PARAMETERS"]["CACHE_TIME"],
			"PATH_TO_USER"=>$arComponentProps["PARAMETERS"]["PATH_TO_USER"],
			"NAME_TEMPLATE"=>$arComponentProps["PARAMETERS"]["NAME_TEMPLATE"],
			"SHOW_LOGIN"=>$arComponentProps["PARAMETERS"]["SHOW_LOGIN"],
			"USE_COMMENTS" => $arComponentProps2["PARAMETERS"]["USE_COMMENTS"]
		),
		"USER_PARAMETERS"=> Array(
			"PAGE_ELEMENTS" => Array(
				"NAME" => GetMessage("GD_PHOTOS_P_COUNT"),
				"TYPE" => "STRING",
				"MULTIPLE" => "N",
				"DEFAULT" => "6",
			),
		),
	);

if (isset($arComponentProps2["PARAMETERS"]["COMMENTS_TYPE"]))
	$arParameters["PARAMETERS"]["COMMENTS_TYPE"] = $arComponentProps2["PARAMETERS"]["COMMENTS_TYPE"];
if (isset($arComponentProps2["PARAMETERS"]["COMMENTS_COUNT"]))
	$arParameters["PARAMETERS"]["COMMENTS_COUNT"] = $arComponentProps2["PARAMETERS"]["COMMENTS_COUNT"];
if (isset($arComponentProps2["PARAMETERS"]["PATH_TO_SMILE"]))
	$arParameters["PARAMETERS"]["PATH_TO_SMILE"] = $arComponentProps2["PARAMETERS"]["PATH_TO_SMILE"];
if (isset($arComponentProps2["PARAMETERS"]["FORUM_ID"]))
	$arParameters["PARAMETERS"]["FORUM_ID"] = $arComponentProps2["PARAMETERS"]["FORUM_ID"];
if (isset($arComponentProps2["PARAMETERS"]["USE_CAPTCHA"]))
	$arParameters["PARAMETERS"]["USE_CAPTCHA"] = $arComponentProps2["PARAMETERS"]["USE_CAPTCHA"];
if (isset($arComponentProps2["PARAMETERS"]["BLOG_URL"]))
	$arParameters["PARAMETERS"]["BLOG_URL"] = $arComponentProps2["PARAMETERS"]["BLOG_URL"];
if (isset($arComponentProps2["PARAMETERS"]["PATH_TO_BLOG"]))
	$arParameters["PARAMETERS"]["PATH_TO_BLOG"] = $arComponentProps2["PARAMETERS"]["PATH_TO_BLOG"];

	
$arParameters["PARAMETERS"]["DETAIL_URL"]["DEFAULT"] = "/about/gallery/#SECTION_ID#/#ELEMENT_ID#/";
$arParameters["PARAMETERS"]["DETAIL_SLIDE_SHOW_URL"]["DEFAULT"] = "/about/gallery/#SECTION_ID#/#ELEMENT_ID#/slide_show/";
?>
