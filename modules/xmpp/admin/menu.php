<?
IncludeModuleLangFile(__FILE__);

if($APPLICATION->GetGroupRight("xmpp") >= "R")
{
	$aMenu = array(
		"parent_menu" => "global_menu_settings",
		"section" => "xmpp",
		"sort" => 550,
		"text" => "XMPP",
		"title"=> "XMPP",
		"url" => "xmpp_server.php?lang=".LANGUAGE_ID,
		"icon" => "xmpp_menu_icon",
		"page_icon" => "xmpp_page_icon",
		"items_id" => "xmpp_sonet",
		"items" => array(
			array(
				"text" => GetMessage("XMPP_MENU_SERVER"),
				"url" => "xmpp_server.php?lang=".LANGUAGE_ID,
				"more_url" => array(),
				"title" => GetMessage("XMPP_MENU_SERVER_TITLE")
			),
		)
	);

	return $aMenu;
}
return false;
?>
