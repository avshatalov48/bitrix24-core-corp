<?
require($_SERVER["DOCUMENT_ROOT"]."/mobile/headers.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

if (IsModuleInstalled("bitrix24"))
{
	GetGlobalID();
}

$arPostProperty = array("UF_BLOG_POST_DOC");
if (
	IsModuleInstalled("webdav")
	|| IsModuleInstalled("disk")
)
{
	$arPostProperty[] = "UF_BLOG_POST_FILE";
}

if (
	CModule::IncludeModule("extranet")
	&& CExtranet::IsExtranetSite()
	&& CModule::IncludeModule("socialnetwork")
)
{
	$arSonetGroups = CSocNetLogDestination::GetSocnetGroup(
		array(
			'features' => array(
				"blog", 
				array("premoderate_post", "moderate_post", "write_post", "full_post")
			)
		)
	);

	if (count($arSonetGroups) == 1)
	{
		foreach($arSonetGroups as $key => $arGroupTmp)
		{
			$group_id = intval($arGroupTmp['entityId']);
		}
	}
}

if (!$group_id)
{
	$group_id = intval($_REQUEST["group_id"]);
}

$group_id = intval($group_id);

$APPLICATION->IncludeComponent("bitrix:main.post.form", "mobile", array(
		"FORM_ACTION_URL" => SITE_DIR."mobile/log/".($group_id > 0 ? "?group_id=".$group_id : ""), // post action
		"SOCNET_GROUP_ID" => $group_id,
		"POST_PROPERTY" => $arPostProperty,
		"FORM_ID" => "blogPostForm",
		"FORM_TARGET" => "_self",
		"IS_EXTRANET" => "Y",
		"POST_ID" => intval($_REQUEST["post_id"])
	),
	false,
	Array("HIDE_ICONS" => "Y")
);
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php")?>