<?
use Bitrix\Socialnetwork\Item\WorkgroupFavorites;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

CUserOptions::SetOption("intranet", "left_menu_group_converted_".SITE_ID, "Y");

if (!CModule::IncludeModule("socialnetwork") || !$GLOBALS["USER"]->isAuthorized())
{
	return;
}

$items = CUserOptions::GetOption("intranet", "left_menu_standard_items_".SITE_ID);

if (!is_array($items) || empty($items))
{
	return;
}

foreach ($items as $item)
{
	if (preg_match("~^/workgroups/group/([0-9]+)/$~i", $item["LINK"], $match))
	{
		try
		{
			WorkgroupFavorites::set(array(
				"GROUP_ID" => $match[1],
				"USER_ID" => $GLOBALS["USER"]->getId(),
				"VALUE" => "Y"
			));
		}
		catch(Exception $e)
		{

		}

	}
}


